# Usage

What you get back from the field and what to do with it.

The plugin stores and validates a single IANA identifier on the element.
Everything below is what you do with that value. The display and input markup
are yours to write; the recipes here are examples, not the only way.

Install and setup live in the [README](../README.md).

## Field settings

Each Timezone field instance has three settings, edited at **Settings ->
Fields -> (your field)**:

- **Display format**: how each timezone is labeled in the control panel
  picker. Limited to `identifier`, `city_offset`, and `city_abbreviation`
  (default `city_offset`), the formats that stay distinct for every zone. A
  collapsing format like `generic` would label `America/New_York` and
  `America/Detroit` both as "Eastern Time", so it is output-only (see the
  [display table](#display-the-timezone)). The stored value is always the
  raw IANA identifier regardless of this setting.
- **Limit to regions**: restrict the picker to one or more region prefixes
  (`America`, `Europe`, ...). Empty means every region.
- **Limit to specific timezones**: restrict the picker to an explicit list
  (`America/New_York`, `America/Los_Angeles`). When set, this overrides the
  region limit.

The active limit also applies to validation: a value outside the allowed set
is rejected on save, including values posted from a front-end form.

If an entry already has a value that a later limit no longer allows, the
picker shows an empty current selection rather than the now-unusable value.
The editor picks a valid zone; saving without picking clears the field.

## The value

The raw value is the IANA identifier string, for example `America/New_York`,
or `null` when the field is empty. There is no object or wrapper.

- Twig: `entry.myTimezoneField` returns the string.
- PHP: `$element->myTimezoneField` returns the string.
- GraphQL: resolves as a `String`.

The control panel input is a searchable list of every identifier, validated
on save, so only a valid identifier can be stored regardless of source.

## Display the timezone

The plugin ships a `timezone` Twig filter that turns the stored identifier
into a readable string. No JavaScript, server-side, daylight-saving aware.

```twig
{{ entry.myTimezoneField|timezone }}              {# New York (GMT-04:00) #}
{{ entry.myTimezoneField|timezone('specific') }}  {# Eastern Daylight Time #}
```

Pass a format name. Default is `city_offset`. Examples are for
`America/New_York` in summer:

| Format | Output |
| --- | --- |
| `identifier` | `America/New_York` |
| `city` | `New York` |
| `offset` | `GMT-04:00` |
| `city_offset` (default) | `New York (GMT-04:00)` |
| `abbreviation` | `EDT` (`EST` in winter) |
| `city_abbreviation` | `New York (EDT)` |
| `short_generic` | `ET` |
| `generic` | `Eastern Time` |
| `specific` | `Eastern Standard Time` / `Eastern Daylight Time` |
| `location` | `New York Time` |

The `specific` and `abbreviation` values switch with daylight saving, based
on the current date in that zone. An empty or invalid value renders an empty
string. Names are localized to the site language; pass a locale as the second
argument to override (`|timezone('generic', 'fr')`).

Zones without a letter abbreviation (for example `Asia/Kolkata`) fall back to
the GMT form for `abbreviation` (`GMT+5:30`). `offset` is universal.

Only canonical IANA identifiers validate. Backward-compatibility aliases
(`US/Eastern`, `Asia/Calcutta`) are not accepted; use the canonical name
(`America/New_York`, `Asia/Kolkata`).

In PHP, use the helper directly:

```php
use fostercommerce\timezonepicker\enums\TimezoneFormat;
use fostercommerce\timezonepicker\helpers\TimezoneFormatter;

TimezoneFormatter::format($element->myTimezoneField, TimezoneFormat::Specific);
```

If you want a different rendering than any built-in format, the raw value is
always the identifier, so you can format it however you like.

## Localize a date with the value

The field stores only the timezone. To show a stored UTC datetime in the
user's timezone, parse the stored value with Craft's helper and re-target it:

```php
use craft\helpers\DateTimeHelper;

$local = DateTimeHelper::toDateTime($utcValue, false, false)
    ->setTimezone(new \DateTimeZone($element->myTimezoneField));
```

`toDateTime($utcValue, false, false)` reads a timezone-less stored value as
UTC and keeps it there; `setTimezone` moves the display to the field's zone
without changing the instant.

```twig
{{ entry.dateField|date('Y-m-d H:i', entry.myTimezoneField) }}
```

## Front-end input (example)

The field validates whatever identifier is posted to
`fields[myTimezoneField]`, so the input is yours to build (a native select, a
search select, a typeahead, whatever the site needs). This example builds a
native select client-side with a human-readable label and the raw identifier
as the value:

```html
<select name="fields[myTimezoneField]"></select>
<script>
  const select = document.currentScript.previousElementSibling;

  const label = zone => {
    const offset = new Intl.DateTimeFormat('en-US', {
      timeZone: zone,
      timeZoneName: 'shortOffset',
    }).formatToParts(new Date()).find(part => part.type === 'timeZoneName').value;
    const city = zone.split('/').pop().replace(/_/g, ' ');
    return city + ' (' + offset + ')';
  };

  Intl.supportedValuesOf('timeZone').forEach(zone =>
    select.add(new Option(label(zone), zone))
  );

  select.value =
    {{ entry.myTimezoneField|json_encode|raw }} ||
    Intl.DateTimeFormat().resolvedOptions().timeZone;
</script>
```

The user sees `New York (GMT-4)`; the form posts `America/New_York`. To group
the list, wrap options in `<optgroup>` keyed by the region segment
(`zone.split('/')[0]`).
