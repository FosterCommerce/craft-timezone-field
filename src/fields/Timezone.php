<?php

namespace fostercommerce\timezonepicker\fields;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Cp;
use DateTimeZone;
use fostercommerce\timezonepicker\enums\TimezoneFormat;
use fostercommerce\timezonepicker\helpers\TimezoneFormatter;
use yii\db\Schema;

/**
 * A field that stores an IANA timezone identifier (e.g. "America/New_York").
 *
 * The identifier is the only DST-safe value to persist. Render a stored UTC
 * datetime in that zone with:
 *
 *     \craft\helpers\DateTimeHelper::toDateTime($utcValue, false, false)
 *         ->setTimezone(new \DateTimeZone($fieldValue));
 */
class Timezone extends Field
{
	public string $displayFormat = TimezoneFormat::CityOffset->value;

	/**
	 * @var list<string>
	 */
	public array $groups = [];

	/**
	 * @var list<string>
	 */
	public array $allowedIdentifiers = [];

	public static function displayName(): string
	{
		return Craft::t('timezone-picker', 'Timezone');
	}

	public static function icon(): string
	{
		return 'clock';
	}

	public static function phpType(): string
	{
		return 'string|null';
	}

	public static function dbType(): string
	{
		return Schema::TYPE_STRING;
	}

	public function getSettingsHtml(): ?string
	{
		$formatOptions = array_map(
			static fn (TimezoneFormat $format): array => [
				'label' => sprintf(
					'%s (%s)',
					Craft::t('timezone-picker', $format->label()),
					TimezoneFormatter::format('America/New_York', $format),
				),
				'value' => $format->value,
			],
			$this->pickerFormats(),
		);

		$regionOptions = array_map(
			static fn (string $region): array => [
				'label' => $region,
				'value' => $region,
			],
			$this->regions(),
		);

		$identifierOptions = array_map(
			static fn (string $identifier): array => [
				'label' => $identifier,
				'value' => $identifier,
			],
			$this->allIdentifiers(),
		);

		return Cp::selectFieldHtml([
			'label' => Craft::t('timezone-picker', 'Display format'),
			'instructions' => Craft::t('timezone-picker', 'How each timezone is labeled in the control panel. The stored value is always the IANA identifier.'),
			'id' => 'displayFormat',
			'name' => 'displayFormat',
			'value' => $this->displayFormat,
			'options' => $formatOptions,
		]) . Cp::selectizeFieldHtml([
			'label' => Craft::t('timezone-picker', 'Limit to regions'),
			'instructions' => Craft::t('timezone-picker', 'Restrict the editor to timezones in these regions. Leave empty for every region.'),
			'id' => 'groups',
			'name' => 'groups',
			'values' => $this->groups,
			'options' => $regionOptions,
			'multi' => true,
		]) . Cp::selectizeFieldHtml([
			'label' => Craft::t('timezone-picker', 'Limit to specific timezones'),
			'instructions' => Craft::t('timezone-picker', 'Restrict the editor to these timezones only. When set, this overrides the region limit.'),
			'id' => 'allowedIdentifiers',
			'name' => 'allowedIdentifiers',
			'values' => $this->allowedIdentifiers,
			'options' => $identifierOptions,
			'multi' => true,
		]);
	}

	public function normalizeValue(mixed $value, ?ElementInterface $element = null): ?string
	{
		$identifier = is_string($value) ? trim($value) : '';

		return $identifier === '' ? null : $identifier;
	}

	/**
	 * @return list<array<int|string, mixed>>
	 */
	public function getElementValidationRules(): array
	{
		return [
			[
				'in',
				'range' => $this->availableIdentifiers(),
			],
		];
	}

	/**
	 * @return array<int, mixed>
	 */
	protected function defineRules(): array
	{
		$rules = parent::defineRules();

		$rules[] = [
			['displayFormat'],
			'in',
			'range' => array_map(
				static fn (TimezoneFormat $format): string => $format->value,
				$this->pickerFormats(),
			),
		];

		$rules[] = [
			['groups', 'allowedIdentifiers'],
			'filter',
			'filter' => fn (mixed $value): array => $this->cleanList($value),
		];

		$rules[] = [
			['groups'],
			'each',
			'rule' => [
				'in',
				'range' => $this->regions(),
			],
		];

		$rules[] = [
			['allowedIdentifiers'],
			'each',
			'rule' => [
				'in',
				'range' => $this->allIdentifiers(),
			],
		];

		return $rules;
	}

	protected function inputHtml(mixed $value, ?ElementInterface $element = null, bool $inline = false): string
	{
		$format = TimezoneFormat::tryFrom($this->displayFormat) ?? TimezoneFormat::CityOffset;
		if (! $format->unambiguous()) {
			$format = TimezoneFormat::CityOffset;
		}

		$identifiers = $this->availableIdentifiers();

		$options = array_map(
			static function (string $identifier) use ($format): array {
				$label = TimezoneFormatter::format($identifier, $format);

				return [
					'label' => $label === '' ? $identifier : $label,
					'value' => $identifier,
				];
			},
			$identifiers,
		);

		$selected = is_string($value) ? $value : '';

		// A value stored before the field was restricted is no longer in the
		// available set. Show it as an empty current selection (rather than
		// surfacing an unusable identifier) so the editor must pick a valid
		// zone.
		if ($selected !== '' && ! in_array($selected, $identifiers, true)) {
			array_unshift($options, [
				'label' => '',
				'value' => '',
			]);
			$selected = '';
		}

		return Cp::selectizeHtml([
			'id' => $this->getInputId(),
			'name' => $this->handle,
			'value' => $selected,
			'options' => $options,
		]);
	}

	/**
	 * The identifiers the editor may pick and that pass validation.
	 *
	 * `allowedIdentifiers` wins when set: only those are offered. Otherwise
	 * `groups` restricts to identifiers whose region prefix is listed. When
	 * both are empty, every identifier is available.
	 *
	 * @return list<string>
	 */
	private function availableIdentifiers(): array
	{
		$allowed = $this->cleanList($this->allowedIdentifiers);
		if ($allowed !== []) {
			$restricted = array_values(array_intersect($this->allIdentifiers(), $allowed));

			return $restricted === [] ? $this->allIdentifiers() : $restricted;
		}

		$groups = $this->cleanList($this->groups);
		if ($groups !== []) {
			$restricted = array_values(array_filter(
				$this->allIdentifiers(),
				static fn (string $identifier): bool => in_array(
					explode('/', $identifier)[0],
					$groups,
					true,
				),
			));

			return $restricted === [] ? $this->allIdentifiers() : $restricted;
		}

		return $this->allIdentifiers();
	}

	/**
	 * Drops non-string and blank entries. Craft's multi-selectize posts an
	 * empty placeholder value when the control is touched and left empty; an
	 * unfiltered "" would turn a restriction into an impossible set.
	 *
	 * @return list<string>
	 */
	private function cleanList(mixed $values): array
	{
		if (! is_array($values)) {
			return [];
		}

		return array_values(array_filter(
			$values,
			static fn (mixed $value): bool => is_string($value) && trim($value) !== '',
		));
	}

	/**
	 * The distinct region prefixes across every identifier (e.g. "America").
	 *
	 * @return list<string>
	 */
	private function regions(): array
	{
		$regions = array_unique(array_map(
			static fn (string $identifier): string => explode('/', $identifier)[0],
			$this->allIdentifiers(),
		));
		sort($regions);

		return array_values($regions);
	}

	/**
	 * The display formats safe to offer in the control panel picker: those
	 * that render a distinct string per identifier. Collapsing formats stay
	 * available for output through the `timezone` Twig filter.
	 *
	 * @return list<TimezoneFormat>
	 */
	private function pickerFormats(): array
	{
		return array_values(array_filter(
			TimezoneFormat::cases(),
			static fn (TimezoneFormat $format): bool => $format->unambiguous(),
		));
	}

	/**
	 * @return list<string>
	 */
	private function allIdentifiers(): array
	{
		static $identifiers = null;

		return $identifiers ??= DateTimeZone::listIdentifiers();
	}
}
