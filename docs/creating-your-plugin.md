---
title: Creating your plugin package
weight: 30
description: Copy the example package and rename it into a plugin package of your own.
---

This page turns a copy of the example into your own package. The example names used below are the ones to replace;
`acme/rich-editor-plus-highlight`, `Acme\RichEditorPlusHighlight` and `rich-editor-plus-highlight` stand in for yours.

## 1. Copy the folder

Copy the whole package, including `dist/`, into a folder of your own. During development the package usually lives
inside a Laravel app that loads it through a Composer path repository, because the Vite dev server expects to run
from the host app (see [Building and HMR](building.md)):

```json
{
    "repositories": [
        { "type": "path", "url": "./packages/*" }
    ],
    "require": {
        "acme/rich-editor-plus-highlight": "@dev"
    }
}
```

Put the copy at `packages/rich-editor-plus-highlight/` in the app, then run `composer update acme/rich-editor-plus-highlight`
once you've finished renaming.

## 2. Rename the Composer package and namespace

In `composer.json`:

- `name`: `webwizardsusa/rich-editor-plus-example-plugin` becomes your package name, such as
  `acme/rich-editor-plus-highlight`. Update `description`, `homepage`, `support` and `authors` too.
- `autoload.psr-4`: `Webwizardsusa\\RichEditorPlusExample\\` becomes your namespace, such as
  `Acme\\RichEditorPlusHighlight\\`. Do the same for `autoload-dev` (the tests namespace).
- `extra.laravel.providers`: point it at your renamed service provider.
- `require`: keep `webwizardsusa/filament-rich-editor-plus`, but give it a real constraint instead of `*`, such as the
  minor version you build against.

Then change the `namespace` and `use` lines in every file under `src/` and `tests/`, and rename
`RichEditorPlusExampleServiceProvider.php` and its class.

In `package.json`, rename `@webwizardsusa/rich-editor-plus-example` to a name of your own. The npm package is never
published (`"private": true`); the name only matters to npm workspaces.

## 3. Set up the service provider

The service provider registers your translations and your built assets. The `RegistersPluginAssets` trait from Rich
Editor Plus does the asset work: it registers `dist/{assetId}.js` and `dist/{assetId}.css` with Filament, and tells the
editor where to load the module from, which is the Vite dev server while it is running.

```php
<?php

namespace Acme\RichEditorPlusHighlight;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Webwizardsusa\RichEditorPlus\Support\RegistersPluginAssets;

class RichEditorPlusHighlightServiceProvider extends PackageServiceProvider
{
    use RegistersPluginAssets;

    public function configurePackage(Package $package): void
    {
        $package
            ->name('rich-editor-plus-highlight')
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        $this->registerPluginAssets();
    }

    public static function assetPackage(): string
    {
        return 'acme/rich-editor-plus-highlight';
    }

    public static function assetId(): string
    {
        return 'rich-editor-plus-highlight';
    }
}
```

The two static methods matter:

- **`assetPackage()`** must return your Composer package name, exactly as it is in `composer.json`. Filament groups the
  assets under it, and `filament:assets` publishes them to `public/js/{assetPackage}/` and `public/css/{assetPackage}/`.
- **`assetId()`** names the built files (`dist/{assetId}.js`, `dist/{assetId}.css`) and the dev server's hot file
  (`public/{assetId}.hot`). Pick an id no other package uses.

> [!WARNING]
> The example's `assetPackage()` returns `webwizardsusa/rich-editor-plus-example`, which is not its Composer package
> name (`webwizardsusa/rich-editor-plus-example-plugin`). The assets still load, because the same value is used to
> register and to look them up, but don't copy that mismatch: return your real package name.

`->name()` sets the translation namespace, so labels become `rich-editor-plus-highlight::plugins.*`. Update every
`__('rich-editor-plus-example::…')` call in `src/` to the new namespace. The
[translation test](testing.md#translation-keys) looks for the prefix too, so update its pattern.

## 4. Match the asset id in Vite

`vite.config.js` has its own copy of the id. Change it to the value `assetId()` returns:

```js
const assetId = 'rich-editor-plus-highlight'
```

While you are in the file:

- Change the `sharedTiptap` import so it finds the copy of Rich Editor Plus your setup has. The example imports it from
  the editor's checkout next to it; see [Finding sharedTiptap](tour/vite-config.md#finding-sharedtiptap).
- Change `server.port` to a port no other dev server in the app uses. The example uses `5179`.

## 5. Give each plugin its own id

Every plugin has an id, returned from `getId()`, and the JS module registers its TipTap extension under the same id.
Use a prefix of your own, so your ids can't collide with another package's:

```php
public function getId(): string
{
    return 'acme/highlight';
}
```

```js
registerExtension('acme/highlight', (options) => createHighlight(options))
```

The example uses `example/keyboard` and `example/callout`. A plugin that returns the same id as another one replaces
it, which is useful when you mean to (the code block package replaces the core code block that way) and confusing when
you don't.

Also rename the node and mark names (`keyboard`, `callout`) if yours are different. They must match between the PHP
spec and the TipTap extension, as the [tour](tour/_index.md) explains.

## 6. Replace the plugins

Rename or delete `KeyboardPlugin`, `CalloutPlugin` and `CalloutVariant`, and their JS files, and write yours. Start
from whichever is closer: a mark from `KeyboardPlugin`, a block from `CalloutPlugin`. Update `resources/js/index.js`
to register your extensions, and `resources/lang/en/plugins.php` with your labels.

## 7. Build, publish and try it

```bash
npm install
npm run build
php artisan filament:assets
```

Add your plugin to a field with `->addPlugins([...])` and open the form. Then commit `dist/` along with the source:
it is what Composer installs. See [Building and HMR](building.md) for the dev server.

## Checklist

| Example value | Where | Becomes |
|---|---|---|
| `webwizardsusa/rich-editor-plus-example-plugin` | `composer.json` `name` | your Composer package name |
| `webwizardsusa/rich-editor-plus-example` | `assetPackage()` | your Composer package name |
| `Webwizardsusa\RichEditorPlusExample` | `composer.json`, `src/`, `tests/` | your namespace |
| `RichEditorPlusExampleServiceProvider` | class, file, `extra.laravel.providers`, `tests/TestCase.php` | your provider |
| `rich-editor-plus-example` | `->name()`, `assetId()`, `vite.config.js`, `__()` keys, translation test | your id |
| `@webwizardsusa/rich-editor-plus-example` | `package.json` `name` | your npm name |
| `example/keyboard`, `example/callout` | `getId()`, `registerExtension()` | your plugin ids |
| `5179` | `vite.config.js` `server.port` | a free port |
