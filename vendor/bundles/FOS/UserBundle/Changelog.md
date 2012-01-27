Changelog
=========

### 1.2.0

* Removed the user-level algorithm. Use FOSAdvancedEncoderBundle instead if you need such feature.

### 1.1.0  (2011-12-15)

* Added "custom" as valid driver
* Hide part of the email when requesting a password reset
* Changed the validation messages to translation keys
* Added the default validation group by default
* Fixed updating of changed fields in listener. Github issue #403
* Added support for Propel
* Added composer.json
* Made it possible to override the role constants in derived User class
* Updated translations: da, de, en, es, et, fr, hu, lb, nl, pl, pt_BR, pt_PT, ru
* Added translations: ca, cs, it, ja, ro, sk, sl, sv
* Changed the instanceof check for refreshUser to class instead of interface to allow multiple firewalls and correct use of UnsupportedUserException
* Added an extension point in the form handlers. Closes #291
* Rewrote the documentation entirely

### 1.0.0  (2011-08-01)

* Initial release