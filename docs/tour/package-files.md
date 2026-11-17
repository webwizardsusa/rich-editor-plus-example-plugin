---
title: Package files
weight: 10
description: composer.json, package.json, .gitattributes and the service provider of the example plugin package.
---

## composer.json

The package requires Rich Editor Plus and `spatie/laravel-package-tools`, and registers its service provider for
package discovery:

```json
{
    "name": "webwizardsusa/rich-editor-plus-example-plugin",
    "require": {
        "php": "^8.3",
        "webwizardsusa/filament-rich-editor-plus": "*",
        "spatie/laravel-package-tools": "^1.92"
    },
    "autoload": {
        "psr-4": { "Webwizardsusa\\RichEditorPlusExample\\": "src/" }
    },
    "extra": {
        "laravel": {
            "providers": ["Webwizardsusa\\RichEditorPlusExample\\RichEditorPlusExampleServiceProvider"]
        }
    }
}
```

The dev dependencies are `orchestra/testbench`, `pestphp/pest` and `pestphp/pest-plugin-laravel`, for the
[tests](../testing.md). `composer test` runs Pest.

> [!TIP]
> The example requires the editor at `*` because it lives in the editor's workbench. Give your package a real
> constraint, such as `^0.2`, so an incompatible editor version can't be installed beside it.

## package.json

```json
{
    "name": "@webwizardsusa/rich-editor-plus-example",
    "private": true,
    "type": "module",
    "scripts": {
        "dev": "vite",
        "build": "vite build"
    },
    "devDependencies": {
        "@tiptap/core": "^3.0.0",
        "@tiptap/pm": "^3.0.0",
        "laravel-vite-plugin": "^3.1",
        "vite": "^8.0.0"
    }
}
```

Everything is a dev dependency. `@tiptap/core` and `@tiptap/pm` are installed only so the build can read their export
names: the built module uses the editor's own copies at runtime and never bundles them. Keep them at the same major
version as the editor (TipTap 3). See [The Vite config](vite-config.md) for why.

If your extension imports a TipTap package the editor doesn't share, such as `@tiptap/extension-code-block`, add it
here too. It is bundled into your module.

## .gitattributes

```text
/tests              export-ignore
/resources/js       export-ignore
/resources/css      export-ignore
/.gitattributes     export-ignore
/.gitignore         export-ignore
/package.json       export-ignore
/phpunit.xml        export-ignore
/vite.config.js     export-ignore
```

The archive Composer installs leaves out the tests and the JavaScript and CSS sources. Apps that install your package
get `src/`, `resources/lang/`, `dist/` and the docs, and never need Node.js. `.gitignore` leaves out `vendor/`,
`node_modules/`, `composer.lock` and `.phpunit.cache`; `dist/` is committed.

## The service provider

```php
<?php

namespace Webwizardsusa\RichEditorPlusExample;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Webwizardsusa\RichEditorPlus\Support\RegistersPluginAssets;

class RichEditorPlusExampleServiceProvider extends PackageServiceProvider
{
    use RegistersPluginAssets;

    public function configurePackage(Package $package): void
    {
        $package
            ->name('rich-editor-plus-example')
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        $this->registerPluginAssets();
    }

    public static function assetPackage(): string
    {
        return 'webwizardsusa/rich-editor-plus-example';
    }

    public static function assetId(): string
    {
        return 'rich-editor-plus-example';
    }
}
```

`->name()` with `->hasTranslations()` loads `resources/lang/` under the `rich-editor-plus-example::` namespace.

`RegistersPluginAssets` is a trait from Rich Editor Plus. It expects the service provider to live in `src/`, and
works from the package root above it:

| Method | What it does |
|---|---|
| `registerPluginAssets()` | Registers `dist/{assetId}.js` (as a Filament Alpine component, which is only fetched when asked for) and `dist/{assetId}.css` (as a Filament stylesheet, loaded in panels), whichever exist. While the dev server runs, it also loads Vite's HMR client in every panel. Call it from `packageBooted()`. |
| `moduleUrl()` | Where the browser loads the module from: the dev server's URL while it runs, otherwise the asset `filament:assets` published. Pass it to `JsExtension::module()`. |
| `assetPackage()` | Abstract. The Composer package name the assets are registered under. |
| `assetId()` | Abstract. Names the built files and the hot file. |

The dev server counts as running when the app environment is `local` and `public/{assetId}.hot` exists.

If neither the dev server nor the published asset is available, `moduleUrl()` throws a `LogicException` that tells
you to start the dev server, or to build and run `php artisan filament:assets`.

> [!WARNING]
> `assetPackage()` must return your Composer package name. The example's value,
> `webwizardsusa/rich-editor-plus-example`, differs from its package name, `webwizardsusa/rich-editor-plus-example-plugin`.
> It works because the trait uses the same value for registering and looking up, but use your real name.

The trait's protected methods can be overridden when your layout differs: `packagePath()`, `assetsDistPath()` (the
`dist/` folder), `devServerEntry()` (the entry the dev server serves, `resources/js/index.js` by default) and
`assetsMissingMessage()`. The code block package overrides `registerPluginAssets()` itself, to register its lazily
loaded chunks as well.
