<?php

namespace XD\Honeypot\Forms;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;
use SilverStripe\Control\Session;
use SilverStripe\Forms\FormField;
use SilverStripe\View\Requirements;

class HoneypotField extends CompositeField
{
    private static $submitted_in_seconds = 5;

    public function __construct()
    {
        parent::__construct();
        $time = $this->storeTimeInSession();
        $this->setChildren(
            new FieldList(
                [
                    TextField::create('AltName_' . $time, 'Name')
                        ->setAttribute('autocomplete', 'nope')
                        ->setAttribute('tabindex', '-1'),
                    TextField::create('AltEmail_' . $time, 'Email')
                        ->setAttribute('autocomplete', 'nope')
                        ->setAttribute('tabindex', '-1'),
                ]
            )
        );
        $this->setName('AltNames');

        $this->addJavascript($time);
    }

    public function addJavascript($time)
    {
        // Use the dynamically generated field names in the JS
        $js = "
            document.addEventListener('DOMContentLoaded', function () {
                var altNamesField = document.querySelector('.alt-names-holder');
                if (altNamesField) {
                    altNamesField.style.position = 'absolute';
                    altNamesField.style.left = '-9999px';
                }
            });
        ";
        Requirements::customScript($js);
    }

    public function storeTimeInSession()
    {
        $session = $this->getSession();

        // escape if request method is not GET
        $controller = Controller::curr();
        if ($controller->getRequest()->httpMethod() !== 'GET') {
            return $session->get('honeypot_time');
        }

        $time = time();
        $session->set('honeypot_time', $time);
        return $time;
    }

    public function getSession()
    {
        $controller = Controller::curr();
        $request = $controller->getRequest();
        return $request->getSession();
    }

    public function validate($validator)
    {
        $children = $this->getChildren();

        // validate time from session
        $session = $this->getSession();
        $fieldCreated = $session->get('honeypot_time');

        if (!$fieldCreated) {
            $validator->validationError($this->name, _t(__CLASS__ . '.SPAM', 'Your submission has been marked as spam') . ' - 0' );
            return false;
        }

        $submittedIn = self::config()->get('submitted_in_seconds');
        $seconds = time() - (int)$fieldCreated;
        if ($seconds < $submittedIn) {
            $validator->validationError($this->name, _t(__CLASS__ . '.SPAM', 'Your submission has been marked as spam'). ' - 1 (' . $seconds . ')'  );
            return false;
        }

        // Validate dynamically generated "MyName" and "MyEmail" fields
        $myNameField = null;
        $myEmailField = null;

        // Loop through children and find dynamically created honeypot fields
        foreach ($children as $field) {
            if (strpos($field->getName(), 'AltName') !== false) {
                $myNameField = $field;
            }
            if (strpos($field->getName(), 'AltEmail') !== false) {
                $myEmailField = $field;
            }
        }

        if (!$myNameField || !$myEmailField) {
            $validator->validationError($this->name, _t(__CLASS__ . '.SPAM', 'Your submission has been marked as spam'). ' - 2' );
            return false;
        }

        // Check if any honeypot fields have been filled out (i.e., they should be empty)
        if ($myNameField && !empty($myNameField->Value())) {
            $validator->validationError($this->name, _t(__CLASS__ . '.SPAM', 'Your submission has been marked as spam'). ' - 3' );
            return false;
        }

        if ($myEmailField && !empty($myEmailField->Value())) {
            $validator->validationError($this->name, _t(__CLASS__ . '.SPAM', 'Your submission has been marked as spam'). ' - 4' );
            return false;
        }

        return parent::validate($validator);
    }
}
