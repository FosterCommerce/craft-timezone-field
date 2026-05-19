<?php

namespace fostercommerce\timezonepicker;

use Craft;
use craft\base\Plugin as BasePlugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use fostercommerce\timezonepicker\fields\Timezone;
use fostercommerce\timezonepicker\twig\TimezoneExtension;
use yii\base\Event;

/**
 * @method static Plugin getInstance()
 */
class Plugin extends BasePlugin
{
	public string $schemaVersion = '1.0.0';

	public function init(): void
	{
		parent::init();

		Event::on(
			Fields::class,
			Fields::EVENT_REGISTER_FIELD_TYPES,
			static function (RegisterComponentTypesEvent $event): void {
				$event->types[] = Timezone::class;
			}
		);

		Craft::$app->getView()->registerTwigExtension(new TimezoneExtension());
	}
}
