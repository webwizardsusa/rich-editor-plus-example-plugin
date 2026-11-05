<?php

namespace Webwizardsusa\RichEditorPlusExample\Plugins;

/**
 * The styles a callout can have. The value is what the stored HTML holds in `data-variant` and the end of the
 * `callout-{value}` class, so it never changes once content uses it.
 */
enum CalloutVariant: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Success = 'success';

    public function getLabel(): string
    {
        return __("rich-editor-plus-example::plugins.callout.variants.{$this->value}");
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Info => 'mdi-information-outline',
            self::Warning => 'mdi-alert-outline',
            self::Success => 'mdi-check-circle-outline',
        };
    }
}
