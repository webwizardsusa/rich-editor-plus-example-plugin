# Rich Editor Plus example plugin

A working plugin package to copy when you write your own plugin for
[Rich Editor Plus](https://github.com/webwizardsusa/filament-rich-editor-plus). It adds a keyboard key mark
(`KeyboardPlugin`, stored as `<kbd>`) and a callout block (`CalloutPlugin`, a box that holds other blocks in an info,
warning or success style), with their own JS module, Vite build, translations and tests.

Requires PHP 8.3+, Filament 5 and Rich Editor Plus.

It isn't published on Packagist: clone it as a reference, or to try it in an app through a Composer path
repository:

```bash
git clone https://github.com/webwizardsusa/rich-editor-plus-example-plugin.git packages/rich-editor-plus-example
composer config repositories.rich-editor-plus-example path packages/rich-editor-plus-example
composer require webwizardsusa/rich-editor-plus-example-plugin:@dev
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
