---
title: Trying the example
weight: 20
description: Install the example plugin in an app to see the keyboard mark and the callout block at work before you copy them.
---

Install the example in an app that already uses Rich Editor Plus, so you can see both plugins in an editor before you
copy them. You don't need it installed to create your own package from it: you can copy the folder straight away (see
[Creating your plugin package](creating-your-plugin.md)).

## Install the package

```bash
composer require webwizardsusa/rich-editor-plus-example-plugin
php artisan filament:assets
```

The service provider is discovered automatically. `filament:assets` publishes the built JavaScript module and
stylesheet from the package's `dist/` folder into `public/`, where the editor loads them from. Run it again whenever
you update the package.

## Add the plugins to a field

```php
use Webwizardsusa\RichEditorPlus\RichEditorPlus;
use Webwizardsusa\RichEditorPlusExample\Plugins\CalloutPlugin;
use Webwizardsusa\RichEditorPlusExample\Plugins\CalloutVariant;
use Webwizardsusa\RichEditorPlusExample\Plugins\KeyboardPlugin;

RichEditorPlus::make('content')->addPlugins([
    KeyboardPlugin::make(),
    CalloutPlugin::make(),
]);
```

Now, in the editor:

- Select some text and click the keyboard button in the floating toolbar, or press <kbd>Ctrl</kbd>+<kbd>Shift</kbd>+<kbd>K</kbd>
  (<kbd>Cmd</kbd>+<kbd>Shift</kbd>+<kbd>K</kbd> on a Mac). The text is stored as `<kbd>`.
- Type `/callout` in an empty paragraph, or use the block switcher, and pick a style. Select several paragraphs first
  and the switcher wraps them all in one callout.
- With the cursor in a callout, the block toolbar shows a **Callout style** menu to change the style.

### Options

| Method | Default | What it does |
|---|---|---|
| `KeyboardPlugin::shortcut(string $shortcut)` | `'Mod-Shift-k'` | The key that toggles the mark, in TipTap's notation. The toolbar tooltip shows the same key. |
| `CalloutPlugin::variants(array $variants)` | `[Info, Warning, Success]` | The `CalloutVariant` cases on offer. The first is the default. An empty array throws an `InvalidArgumentException`. |

With a single variant, the callout has no style menu, because there is nothing to choose.

```php
CalloutPlugin::make()->variants([CalloutVariant::Warning, CalloutVariant::Info]);
```

## What gets stored

```html
<p>Press <kbd>Ctrl</kbd> + <kbd>S</kbd> to save.</p>
<div data-variant="warning" class="callout callout-warning" data-type="callout"><p>Mind the gap.</p></div>
```

Stored content with a variant the field doesn't offer gets the default variant when it is parsed or rendered. A field
without the plugin keeps only the text: the `<kbd>` tag or the callout `<div>` is dropped.

## Rendering

Render with the same plugins you give the field:

```php
use Webwizardsusa\RichEditorPlus\Rendering\RichContentPlusRenderer;
use Webwizardsusa\RichEditorPlus\Support\PluginRegistry;
use Webwizardsusa\RichEditorPlusExample\Plugins\CalloutPlugin;
use Webwizardsusa\RichEditorPlusExample\Plugins\KeyboardPlugin;

$plugins = PluginRegistry::mergePlugins(PluginRegistry::defaultPlugins(), [
    KeyboardPlugin::make(),
    CalloutPlugin::make(),
]);

$html = RichContentPlusRenderer::make($post->body)->plugins($plugins)->toHtml();
```

> [!NOTE]
> The package's stylesheet only styles the editor (it is scoped to `.rep-editor`). Pages that show rendered content
> need their own styles for `kbd` and the `.callout`, `.callout-info`, `.callout-warning` and `.callout-success`
> classes.
