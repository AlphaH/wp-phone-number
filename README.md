wp-phone-number
===============

Parses, validates and formats a phone number inserted into a post before turning it into a link.

## Usage
### Shortcode
There are may ways to use the shortcode. The following methods will all work:
```
[phone number="2012345678"]
[phone 2012345678]
[phone]2012345678[/phone]
```

To define a format overriding the settings, add the `format` attribute to the shortcode, like so:
```
[phone format="international"]2012345678[/phone] Outputs +1 201-234-5678
[phone format="E.164"]2012345678[/phone] Outputs +12012345678
[phone format="National"]2012345678[/phone] Outputs (201) 234-5678
[phone format="RFC 3966"]2012345678[/phone] Outputs +1-201-234-5678
```
Available formats (not case sensitive)
* E.164: `E164` `E.164`
* International: `INT` `INTERNATIONAL`
* National: `DOMESTIC` `NATIONAL`
* RFC-3966: `RFC 3966` `RFC-3966` `RFC3966`

To set a region overriding the settings, add the `region` attribute to the shortcode. It must be two-letter an ISO country code, like so:
```
[phone format="international" region="NL"]0101234567[/phone] Outputs +31 10 123 4567
```
If your input is an international number (`[phone]+31101234567[/phone]`), you don't need to specify a region as it will detect it automatically.

To turn your output into a link (or prevent it from turning into a link) overriding the settings, add the `linkify` attribute to the shortcode like so:
```
[phone linkify="true"]+31101234567[/phone]
```
Available input  (not case sensitive)
* Yes: `YES` `TRUE` `1`
* No: `NO` `FALSE` `0`

### Template tag
```php
wp_phone_number_parse( $input, $region = null, $format = null, $linkify = null )
```
- `$input` (string) The phone number you want to parse
- `$region` (string) The [ISO 3166-1 alpha 2 country code](ftp://ftp.fu-berlin.de/doc/iso/iso3166-countrycodes.txt) that should be used by default when parsing non-international phone numbers. Defaults to the Wordpress settings.
- `$format` (string) The format in which the phone numbers should be parsed (see "Available formats under **Shortcode**). Defaults to the Wordpress settings.
- `$linkify` (boolean) Whether or not to turn phone numbers into links. Defaults to the Wordpress settings.