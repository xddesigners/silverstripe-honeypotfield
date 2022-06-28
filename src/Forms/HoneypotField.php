<?php

namespace XD\Honeypot\Forms;

use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\HiddenField;
use SilverStripe\Forms\TextField;

class HoneypotField extends CompositeField
{
    private static $submitted_in_seconds = 5;

    public function __construct()
    {
        parent::__construct([
            HiddenField::create('MyTime', 'MyTime', time())
                ->setAttribute('autocomplete', 'nope')
                ->setAttribute('tabindex', '-1'),
            TextField::create('MyName', 'MyName')
                ->setAttribute('autocomplete', 'nope')
                ->setAttribute('tabindex', '-1')
        ]);
    }

    public function validate($validator) {
        $children = $this->getChildren();

        // validate time
        $timeField = $children->fieldByName('MyTime');
        if (!$timeField) {
            $validator->validationError($this->name, _t(__CLASS__ . '.VALIDATE_ERROR', 'Your submission could not be validated'));
            return false;
        }

        $fieldCreated = (int) $timeField->Value();
        if (!$fieldCreated) {
            $validator->validationError($this->name, _t(__CLASS__ . '.VALIDATE_ERROR', 'Your submission could not be validated'));
            return false;
        }

        $submittedIn = self::config()->get('submitted_in_seconds');
        $seconds = time() - (int) $fieldCreated;
        if ($seconds < $submittedIn) {
            $validator->validationError($this->name, _t(__CLASS__ . '.SPAM', 'Your submission has been marked as spam'));
            return false;
        }
        
        // validate value
        $valueField = $children->fieldByName('MyName');
        if (!$valueField) {
            $validator->validationError($this->name, _t(__CLASS__ . '.VALIDATE_ERROR', 'Your submission could not be validated'));
            return false;
        }

        if (!empty($valueField->Value())) {
            $validator->validationError($this->name, _t(__CLASS__ . '.SPAM', 'Your submission has been marked as spam'));
            return false;
        }


        return parent::validate($validator);
    }
}
