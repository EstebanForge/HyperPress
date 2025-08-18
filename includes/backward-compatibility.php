<?php

declare(strict_types=1);

// Exit if accessed directly.
defined('ABSPATH') || exit;

/**
 * A map of old to new class names for backward compatibility.
 *
 * @var array
 */
$class_alias_map = [
    'HMApi\\Admin\\Activation' => 'HyperPress\\Admin\\Activation',
    'HMApi\\Admin\\Options' => 'HyperPress\\Admin\\Options',
    'HMApi\\Admin\\OptionsMigration' => 'HyperPress\\Admin\\OptionsMigration',
    'HMApi\\Admin\\WPSettingsOptions' => 'HyperPress\\Admin\\WPSettingsOptions',
    'HMApi\\Assets' => 'HyperPress\\Assets',
    'HMApi\\Blocks\\Block' => 'HyperPress\\Blocks\\Block',
    'HMApi\\Blocks\\Field' => 'HyperPress\\Blocks\\Field',
    'HMApi\\Blocks\\FieldGroup' => 'HyperPress\\Blocks\\FieldGroup',
    'HMApi\\Blocks\\Registry' => 'HyperPress\\Blocks\\Registry',
    'HMApi\\Blocks\\Renderer' => 'HyperPress\\Blocks\\Renderer',
    'HMApi\\Blocks\\RestApi' => 'HyperPress\\Blocks\\RestApi',
    'HMApi\\Compatibility' => 'HyperPress\\Compatibility',
    'HMApi\\Config' => 'HyperPress\\Config',
    'HMApi\\Fields\\BlockFieldAdapter' => 'HyperPress\\Fields\\BlockFieldAdapter',
    'HMApi\\Fields\\ConditionalLogic' => 'HyperPress\\Fields\\ConditionalLogic',
    'HMApi\\Fields\\Container\\Container' => 'HyperPress\\Fields\\Container\\Container',
    'HMApi\\Fields\\Container\\ContainerFactory' => 'HyperPress\\Fields\\Container\\ContainerFactory',
    'HMApi\\Fields\\Container\\PostMetaContainer' => 'HyperPress\\Fields\\Container\\PostMetaContainer',
    'HMApi\\Fields\\Container\\TermMetaContainer' => 'HyperPress\\Fields\\Container\\TermMetaContainer',
    'HMApi\\Fields\\Container\\UserMetaContainer' => 'HyperPress\\Fields\\Container\\UserMetaContainer',
    'HMApi\\Fields\\CustomField' => 'HyperPress\\Fields\\CustomField',
    'HMApi\\Fields\\Field' => 'HyperPress\\Fields\\Field',
    'HMApi\\Fields\\HeadingField' => 'HyperPress\\Fields\\HeadingField',
    'HMApi\\Fields\\HyperFields' => 'HyperPress\\Fields\\HyperFields',
    'HMApi\\Fields\\OptionField' => 'HyperPress\\Fields\\OptionField',
    'HMApi\\Fields\\OptionsPage' => 'HyperPress\\Fields\\OptionsPage',
    'HMApi\\Fields\\OptionsSection' => 'HyperPress\\Fields\\OptionsSection',
    'HMApi\\Fields\\PostField' => 'HyperPress\\Fields\\PostField',
    'HMApi\\Fields\\Registry' => 'HyperPress\\Fields\\Registry',
    'HMApi\\Fields\\RepeaterField' => 'HyperPress\\Fields\\RepeaterField',
    'HMApi\\Fields\\SeparatorField' => 'HyperPress\\Fields\\SeparatorField',
    'HMApi\\Fields\\TabsField' => 'HyperPress\\Fields\\TabsField',
    'HMApi\\Fields\\TemplateLoader' => 'HyperPress\\Fields\\TemplateLoader',
    'HMApi\\Fields\\TermField' => 'HyperPress\\Fields\\TermField',
    'HMApi\\Fields\\UserField' => 'HyperPress\\Fields\\UserField',
];

foreach ($class_alias_map as $old_class => $new_class) {
    if (class_exists($new_class) && !class_exists($old_class)) {
        class_alias($new_class, $old_class);
    }
}

/**
 * A map of old to new constant names for backward compatibility.
 *
 * @var array
 */
$constant_map = [
    'HMAPI_BOOTSTRAP_LOADED' => 'HYPERPRESS_BOOTSTRAP_LOADED',
    'HMAPI_INSTANCE_LOADED' => 'HYPERPRESS_INSTANCE_LOADED',
    'HMAPI_LOADED_VERSION' => 'HYPERPRESS_LOADED_VERSION',
    'HMAPI_INSTANCE_LOADED_PATH' => 'HYPERPRESS_INSTANCE_LOADED_PATH',
    'HMAPI_VERSION' => 'HYPERPRESS_VERSION',
    'HMAPI_ABSPATH' => 'HYPERPRESS_ABSPATH',
    'HMAPI_BASENAME' => 'HYPERPRESS_BASENAME',
    'HMAPI_PLUGIN_URL' => 'HYPERPRESS_PLUGIN_URL',
    'HMAPI_PLUGIN_FILE' => 'HYPERPRESS_PLUGIN_FILE',
    'HMAPI_ENDPOINT' => 'HYPERPRESS_ENDPOINT',
    'HMAPI_LEGACY_ENDPOINT' => 'HYPERPRESS_LEGACY_ENDPOINT',
    'HMAPI_TEMPLATE_DIR' => 'HYPERPRESS_TEMPLATE_DIR',
    'HMAPI_LEGACY_TEMPLATE_DIR' => 'HYPERPRESS_LEGACY_TEMPLATE_DIR',
    'HMAPI_TEMPLATE_EXT' => 'HYPERPRESS_TEMPLATE_EXT',
    'HMAPI_LEGACY_TEMPLATE_EXT' => 'HYPERPRESS_LEGACY_TEMPLATE_EXT',
    'HMAPI_ENDPOINT_VERSION' => 'HYPERPRESS_ENDPOINT_VERSION',
    'HMAPI_COMPACT_INPUT' => 'HYPERPRESS_COMPACT_INPUT',
    'HMAPI_COMPACT_INPUT_KEY' => 'HYPERPRESS_COMPACT_INPUT_KEY',
];

foreach ($constant_map as $old_const => $new_const) {
    if (defined($new_const) && !defined($old_const)) {
        define($old_const, constant($new_const));
    }
}
