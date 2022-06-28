# Honeypot field

Add a Honeypot spam protector to use by itself or in combination with a Nocaptcha.
The honeypot is inspired by [spatie/laravel-honeypot](https://github.com/spatie/laravel-honeypot) as it uses an honeypot field that is invalidated when filled and validates an field that has a timestamp. The timestamp is checked to make sure the form wasn't submitted crazy fast.

## Installation

```bash
composer require xddesigners/honeypotfield
```

## Usage

Configure the honeypot spam protector by itself or with the nocapcha protector.

```yml
# configure if you want to use the honeypot by itself
SilverStripe\SpamProtection\Extension\FormSpamProtectionExtension:
  default_spam_protector: XD\Honeypot\Protectors\HoneypotProtector

# configure if you want to use both capcha and honeypot protection
SilverStripe\SpamProtection\Extension\FormSpamProtectionExtension:
  default_spam_protector: XD\Honeypot\Protectors\NocaptchaProtector
```

Configure the dis-allowed time in seconds to submit a form.

```yml
XD\Honeypot\Forms\HoneypotField:
  submitted_in_seconds: 5 
```
