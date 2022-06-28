<?php

namespace XD\Honeypot\Protectors;

use SilverStripe\SpamProtection\SpamProtector;
use XD\Honeypot\Forms\HoneypotField;

class HoneypotProtector implements SpamProtector 
{    
    public function getFormField($name = null, $title = null, $value = null) {
        return HoneypotField::create();
    }
    
    public function setFieldMapping($fieldMapping) {}
}
