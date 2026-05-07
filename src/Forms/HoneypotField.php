<?php

namespace XD\Honeypot\Forms;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;
use SilverStripe\View\Requirements;

class HoneypotField extends CompositeField
{
    /**
     * Minimum seconds between form load and submission.
     */
    private static $submitted_in_seconds_min = 3;

    /**
     * Maximum seconds between form load and submission (.5 hour).
     * Expired sessions / cached pages with stale forms are rejected.
     */
    private static $submitted_in_seconds_max = 1800;

    /**
     * Maximum allowed submissions per IP within the rate-limit window.
     */
    private static $rate_limit_max = 10;

    /**
     * Rate-limit window in seconds.
     */
    private static $rate_limit_window = 3600;

    /**
     * Prefix for the first decoy trap field.
     * Must look like a legitimate field name to attract bot fill-in.
     */
    private static $trap_field_a = 'phone';

    /**
     * Prefix for the second decoy trap field.
     */
    private static $trap_field_b = 'website';

    /**
     * Name of the hidden JS-interaction field.
     * Set to '1' by JS on real user activity; empty submissions are bots.
     */
    private static $interaction_field = 'page_token';

    public function __construct()
    {
        parent::__construct();
        $time = $this->storeTimeInSession();

        $trapA = self::config()->get('trap_field_a');
        $trapB = self::config()->get('trap_field_b');
        $interaction = self::config()->get('interaction_field');

        $this->setChildren(
            new FieldList([
                TextField::create($trapA . '_' . $time, 'Phone')
                    ->setAttribute('autocomplete', 'nope')
                    ->setAttribute('tabindex', '-1'),
                TextField::create($trapB . '_' . $time, 'Website')
                    ->setAttribute('autocomplete', 'nope')
                    ->setAttribute('tabindex', '-1'),
                HiddenField::create($interaction, ''),
            ])
        );

        // Neutral composite name — no "honeypot" hint in HTML output
        $this->setName('ContactFields');

        $this->addRequirements();
    }

    /**
     * Register CSS and JS needed to hide the trap fields and track real interaction.
     */
    public function addRequirements()
    {
        Requirements::css('xddesigners/honeypotfield:client/css/honeypot.css');
        Requirements::javascript('xddesigners/honeypotfield:client/javascript/honeypot.js');
    }

    /**
     * Store the current timestamp in the session when the form is first loaded (GET).
     *
     * @return int
     */
    public function storeTimeInSession()
    {
        $session = $this->getSession();

        $controller = Controller::curr();
        if ($controller->getRequest()->httpMethod() !== 'GET') {
            return (int) $session->get('honeypot_time');
        }

        $time = time();
        $session->set('honeypot_time', $time);
        return $time;
    }

    /**
     * @return \SilverStripe\Control\Session
     */
    public function getSession()
    {
        $controller = Controller::curr();
        return $controller->getRequest()->getSession();
    }

    /**
     * Return the visitor's IP address.
     *
     * @return string
     */
    protected function getClientIp()
    {
        $controller = Controller::curr();
        return $controller->getRequest()->getIP() ?: 'unknown';
    }

    /**
     * Check and increment the per-IP submission counter.
     * Returns false when the limit is exceeded.
     *
     * @return bool
     */
    protected function checkRateLimit()
    {
        try {
            /** @var CacheInterface $cache */
            $cache    = Injector::inst()->get(CacheInterface::class . '.honeypot');
            $ip       = $this->getClientIp();
            $cacheKey = 'hp_' . md5($ip);
            $count    = (int) $cache->get($cacheKey, 0);

            if ($count >= (int) self::config()->get('rate_limit_max')) {
                return false;
            }

            $cache->set($cacheKey, $count + 1, (int) self::config()->get('rate_limit_window'));
        } catch (\Exception $e) {
            // Cache unavailable — fail open so legitimate users aren't blocked
        }

        return true;
    }

    public function validate($validator)
    {
        $spam    = _t(__CLASS__ . '.SPAM', 'Your submission has been marked as spam');
        $request = Controller::curr()->getRequest();
        $post    = $request->postVars();

        // 1. Timestamp: must exist, be >= min seconds, and <= max seconds old.
        //    Checked before rate-limit so crawlers that skip JS don't consume quota.
        $session      = $this->getSession();
        $fieldCreated = $session->get('honeypot_time');

        if (!$fieldCreated) {
            $validator->validationError($this->name, $spam);
            return false;
        }

        $seconds = time() - (int) $fieldCreated;
        $minSec  = (int) self::config()->get('submitted_in_seconds_min');
        $maxSec  = (int) self::config()->get('submitted_in_seconds_max');

        if ($seconds < $minSec || $seconds > $maxSec) {
            $validator->validationError($this->name, $spam);
            return false;
        }

        // 2. JS interaction token — read from raw POST, not field object.
        //    CompositeField children are not hydrated via the normal form data path.
        $interactionName  = self::config()->get('interaction_field');
        $interactionValue = isset($post[$interactionName]) ? $post[$interactionName] : '';

        if (empty($interactionValue)) {
            $validator->validationError($this->name, $spam);
            return false;
        }

        // 3. Trap fields must be empty — read from raw POST for the same reason.
        $trapA      = self::config()->get('trap_field_a');
        $trapB      = self::config()->get('trap_field_b');
        $trapAValue = null;
        $trapBValue = null;

        foreach ($post as $key => $value) {
            if (strpos($key, $trapA . '_') === 0) {
                $trapAValue = $value;
            } elseif (strpos($key, $trapB . '_') === 0) {
                $trapBValue = $value;
            }
        }

        if ($trapAValue === null || $trapBValue === null) {
            // Trap fields missing from submission entirely
            $validator->validationError($this->name, $spam);
            return false;
        }

        if (!empty($trapAValue) || !empty($trapBValue)) {
            $validator->validationError($this->name, $spam);
            return false;
        }

        // 4. IP rate limiting — only counted after all other checks pass,
        //    so bots filling trap fields don't exhaust the legitimate user's quota.
        if (!$this->checkRateLimit()) {
            $validator->validationError($this->name, $spam);
            return false;
        }

        return parent::validate($validator);
    }
}
