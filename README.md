# Rich Editor Plus example plugin

A working plugin package to copy when you write your own plugin for
[Rich Editor Plus](https://github.com/webwizardsusa/filament-rich-editor-plus). It adds a keyboard key mark
(`KeyboardPlugin`, stored as `<kbd>`) and a callout block (`CalloutPlugin`, a box that holds other blocks in an info,
warning or success style), with their own JS module, Vite build, translations and tests.

Requires PHP 8.3+, Filament 5 and Rich Editor Plus.

```bash
composer require webwizardsusa/rich-editor-plus-example-plugin
php artisan filament:assets
```

```php
use Webwizardsusa\RichEditorPlusExample\Plugins\CalloutPlugin;
use Webwizardsusa\RichEditorPlusExample\Plugins\CalloutVariant;
use Webwizardsusa\RichEditorPlusExample\Plugins\KeyboardPlugin;

RichEditorPlus::make('content')->addPlugins([
    KeyboardPlugin::make()->shortcut('Mod-Shift-k'),
    CalloutPlugin::make()->variants([CalloutVariant::Info, CalloutVariant::Warning]),
]);
```

## Documentation

The documentation, including a step-by-step guide to creating your own plugin package from this one, is at
[https://DOCS_SITE_URL/docs/rich-editor-plus-example](https://DOCS_SITE_URL/docs/rich-editor-plus-example).

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).
