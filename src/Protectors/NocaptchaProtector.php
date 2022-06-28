<?php

namespace XD\Honeypot\Protectors;

use SilverStripe\Forms\CompositeField;
use UndefinedOffset\NoCaptcha\Forms\NocaptchaField;
use UndefinedOffset\NoCaptcha\Forms\NocaptchaProtector as OrgNocaptchaProtector;
use XD\Honeypot\Forms\HoneyPotField;

class NocaptchaProtector extends OrgNocaptchaProtector
{
    public function getFormField($name = 'Recaptcha2Field', $title = 'Captcha', $value = null) {
        return CompositeField::create([
            HoneyPotField::create(),
            NocaptchaField::create($name, $title)
        ]);
    }
}
