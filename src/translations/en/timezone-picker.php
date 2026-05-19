<?php

return [
	'Timezone' => 'Timezone',

	'Display format' => 'Display format',
	'How each timezone is labeled in the control panel. The stored value is always the IANA identifier.' => 'How each timezone is labeled in the control panel. The stored value is always the IANA identifier.',
	'Limit to regions' => 'Limit to regions',
	'Restrict the editor to timezones in these regions. Leave empty for every region.' => 'Restrict the editor to timezones in these regions. Leave empty for every region.',
	'Limit to specific timezones' => 'Limit to specific timezones',
	'Restrict the editor to these timezones only. When set, this overrides the region limit.' => 'Restrict the editor to these timezones only. When set, this overrides the region limit.',

	// TimezoneFormat::label() values, passed through Craft::t() at the picker
	// call site. Listed here so the canonical strings are extractable.
	'IANA identifier' => 'IANA identifier',
	'City' => 'City',
	'GMT offset' => 'GMT offset',
	'City with offset' => 'City with offset',
	'Abbreviation' => 'Abbreviation',
	'City with abbreviation' => 'City with abbreviation',
	'Short generic name' => 'Short generic name',
	'Generic name' => 'Generic name',
	'Specific name' => 'Specific name',
	'Location name' => 'Location name',
];
