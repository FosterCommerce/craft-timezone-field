<?php

namespace fostercommerce\timezonepicker\enums;

/**
 * The display formats {@see \fostercommerce\timezonepicker\helpers\TimezoneFormatter}
 * can render a stored IANA identifier into.
 *
 * The backing value is what a template passes to the `timezone` Twig filter,
 * for example `entry.myTimezoneField|timezone('specific')`.
 */
enum TimezoneFormat: string
{
	// America/New_York
	case Identifier = 'identifier';

	// New York
	case City = 'city';

	// GMT-04:00
	case Offset = 'offset';

	// New York (GMT-04:00)
	case CityOffset = 'city_offset';

	// EST in winter, EDT in summer
	case Abbreviation = 'abbreviation';

	// New York (EST)
	case CityAbbreviation = 'city_abbreviation';

	// ET
	case ShortGeneric = 'short_generic';

	// Eastern Time
	case Generic = 'generic';

	// Eastern Standard Time in winter, Eastern Daylight Time in summer
	case Specific = 'specific';

	// New York Time
	case Location = 'location';

	/**
	 * Whether this format renders a distinct string for every identifier.
	 *
	 * Collapsing formats (e.g. {@see self::Generic} renders both
	 * "America/New_York" and "America/Detroit" as "Eastern Time") are unsafe
	 * for the control panel picker, where each option must be distinguishable
	 * and map back to one identifier. They remain available for output via the
	 * `timezone` Twig filter, which formats a single stored identifier.
	 */
	public function unambiguous(): bool
	{
		return match ($this) {
			self::Identifier, self::CityOffset, self::CityAbbreviation => true,
			default => false,
		};
	}

	/**
	 * A short English label for the format, used in the field's CP settings.
	 * Wrap in {@see \Craft::t()} at the call site if shown to a CP user.
	 */
	public function label(): string
	{
		return match ($this) {
			self::Identifier => 'IANA identifier',
			self::City => 'City',
			self::Offset => 'GMT offset',
			self::CityOffset => 'City with offset',
			self::Abbreviation => 'Abbreviation',
			self::CityAbbreviation => 'City with abbreviation',
			self::ShortGeneric => 'Short generic name',
			self::Generic => 'Generic name',
			self::Specific => 'Specific name',
			self::Location => 'Location name',
		};
	}
}
