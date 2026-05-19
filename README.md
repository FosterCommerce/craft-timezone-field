# Timezone Picker

A Craft CMS **field** that stores an [IANA timezone identifier](https://www.iana.org/time-zones).

## What it does

Adds a Timezone field you can drop into any field layout that...
- Stores a single IANA identifier (for example `America/New_York`).
- Gives editors a searchable list of every identifier in the control panel.
- Rejects anything that is not a real timezone on save, no matter where the value comes from.
- Keeps dates correct year-round, including daylight saving changes.
- Renders the value in readable formats with a `timezone` Twig filter (`Eastern Daylight Time`, `EDT`, `New York (GMT-04:00)`, and more).
- Accepts a value posted from your own front-end form, not just the control panel.

## Requirements

- Craft CMS `^5.0`
- PHP `^8.2`

## Install

```sh
composer require fostercommerce/timezone-picker
./craft plugin/install timezone-picker
```

## Configure

There is nothing to configure. Once it is installed, add a **Timezone** field
wherever you need one: **Settings -> Fields -> New field**, then pick
**Timezone** as the field type.

## Usage

Add a Timezone field to any field layout. The value you get back is the IANA
identifier string (for example `America/New_York`), or `null` when empty. From
there you can show it in a friendly format or use it to localize dates.

See [usage docs](./docs/usage.md) for further documentation and copy-paste recipes.

## License

Proprietary. See [LICENSE.md](./LICENSE.md).
