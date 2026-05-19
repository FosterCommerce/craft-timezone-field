<?php

namespace fostercommerce\timezonepicker\helpers;

use Craft;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use fostercommerce\timezonepicker\enums\TimezoneFormat;
use IntlTimeZone;

/**
 * Renders a stored IANA identifier into a human-readable string.
 *
 * Daylight saving is resolved from the reference instant, so an
 * {@see TimezoneFormat::Specific} or {@see TimezoneFormat::Abbreviation}
 * value reads "Eastern Standard Time" / "EST" in winter and
 * "Eastern Daylight Time" / "EDT" in summer.
 */
class TimezoneFormatter
{
	public static function format(
		?string $identifier,
		TimezoneFormat $format,
		?DateTimeInterface $instant = null,
		?string $locale = null,
	): string {
		if ($identifier === null || $identifier === '') {
			return '';
		}

		try {
			$zone = new DateTimeZone($identifier);
		} catch (Exception) {
			return '';
		}

		$reference = $instant instanceof DateTimeInterface
			? DateTimeImmutable::createFromInterface($instant)
			: new DateTimeImmutable('now');
		$local = $reference->setTimezone($zone);

		$isDaylight = $local->format('I') === '1';
		$locale ??= Craft::$app->language;

		$segments = explode('/', $identifier);
		$city = str_replace('_', ' ', $segments[array_key_last($segments)]);
		$offset = 'GMT' . $local->format('P');

		return match ($format) {
			TimezoneFormat::Identifier => $identifier,
			TimezoneFormat::City => $city,
			TimezoneFormat::Offset => $offset,
			TimezoneFormat::CityOffset => "{$city} ({$offset})",
			TimezoneFormat::Abbreviation => self::named(
				$identifier,
				$isDaylight,
				IntlTimeZone::DISPLAY_SHORT,
				$locale,
				$local->format('T'),
			),
			TimezoneFormat::CityAbbreviation => sprintf(
				'%s (%s)',
				$city,
				self::named($identifier, $isDaylight, IntlTimeZone::DISPLAY_SHORT, $locale, $local->format('T')),
			),
			TimezoneFormat::ShortGeneric => self::named(
				$identifier,
				false,
				IntlTimeZone::DISPLAY_SHORT_GENERIC,
				$locale,
				$city,
			),
			TimezoneFormat::Generic => self::named(
				$identifier,
				false,
				IntlTimeZone::DISPLAY_LONG_GENERIC,
				$locale,
				$city,
			),
			TimezoneFormat::Specific => self::named(
				$identifier,
				$isDaylight,
				IntlTimeZone::DISPLAY_LONG,
				$locale,
				$city,
			),
			TimezoneFormat::Location => self::named(
				$identifier,
				false,
				IntlTimeZone::DISPLAY_GENERIC_LOCATION,
				$locale,
				$city,
			),
		};
	}

	private static function named(
		string $identifier,
		bool $daylight,
		int $style,
		string $locale,
		string $fallback,
	): string {
		$zone = IntlTimeZone::createTimeZone($identifier);
		if (! $zone instanceof IntlTimeZone) {
			return $fallback;
		}

		$name = $zone->getDisplayName($daylight, $style, $locale);

		return $name === '' ? $fallback : $name;
	}
}
