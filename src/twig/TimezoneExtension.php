<?php

namespace fostercommerce\timezonepicker\twig;

use fostercommerce\timezonepicker\enums\TimezoneFormat;
use fostercommerce\timezonepicker\helpers\TimezoneFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Adds a `timezone` filter for rendering a stored IANA identifier:
 *
 *     {{ entry.myTimezoneField|timezone }}            {# New York (GMT-04:00) #}
 *     {{ entry.myTimezoneField|timezone('specific') }} {# Eastern Daylight Time #}
 */
class TimezoneExtension extends AbstractExtension
{
	/**
	 * @return list<TwigFilter>
	 */
	public function getFilters(): array
	{
		return [
			new TwigFilter('timezone', $this->format(...)),
		];
	}

	public function format(
		?string $identifier,
		string $format = TimezoneFormat::CityOffset->value,
		?string $locale = null,
	): string {
		return TimezoneFormatter::format(
			$identifier,
			TimezoneFormat::tryFrom($format) ?? TimezoneFormat::CityOffset,
			null,
			$locale,
		);
	}
}
