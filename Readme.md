# Honeypot field

Add a Honeypot spam protector to use by itself or in combination with a Nocaptcha.

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
