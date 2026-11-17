---
title: Introduction
weight: 10
description: What the example plugin package demonstrates, and how to use these pages to write a plugin of your own.
---

This package is a complete, working plugin package for [Rich Editor Plus](docs:rich-editor-plus/introduction). It is
small on purpose: copy it, rename it, and you have the skeleton of your own plugin, with the PHP side, its own
JavaScript module, a Vite build, translations and tests already wired together.

It adds two plugins to the editor:

- **`KeyboardPlugin`**, a mark. Text shown as a keyboard key and stored as `<kbd>`, with a toolbar button and a
  keyboard shortcut you can change with `shortcut()`.
- **`CalloutPlugin`**, a block node. A box that holds paragraphs, lists and other blocks, in an info, warning or success
  style. It appears in the slash menu and the block switcher (which can also wrap several selected paragraphs), and has
  a block toolbar menu for changing the style. Choose the styles on offer with `variants()` and the `CalloutVariant`
  enum.

```php
use Webwizardsusa\RichEditorPlus\RichEditorPlus;
use Webwizardsusa\RichEditorPlusExample\Plugins\CalloutPlugin;
use Webwizardsusa\RichEditorPlusExample\Plugins\CalloutVariant;
use Webwizardsusa\RichEditorPlusExample\Plugins\KeyboardPlugin;

RichEditorPlus::make('content')->addPlugins([
    KeyboardPlugin::make()->shortcut('Mod-Shift-k'),
    CalloutPlugin::make()->variants([CalloutVariant::Info, CalloutVariant::Warning]),
]);
```

## What it demonstrates

Between them, the two plugins cover the parts most plugins need:

| Topic | Where to look |
|---|---|
| The smallest plugin that ships its own JS: a mark with a tool and a shortcut | [The keyboard mark](tour/keyboard-plugin.md) |
| A PHP setting passed to the browser as an option | `KeyboardPlugin::shortcut()`, `CalloutPlugin::variants()` |
| A node that holds other blocks, with an attribute checked on the way in and out | [The callout block](tour/callout-plugin.md) |
| Block types for the slash menu and block switcher, including wrapping several blocks | `CalloutPlugin::getBlockTypes()` |
| A block toolbar menu | `CalloutPlugin::getTools()` |
| A JS module that registers TipTap extensions | [The JS module and styles](tour/javascript-module.md) |
| Registering built assets, with a Vite dev server in development | [Package files](tour/package-files.md) and [the Vite config](tour/vite-config.md) |
| Translated labels, and a test that every key exists | [Testing](testing.md) |

It doesn't show everything a plugin can do. [Where to look next](next-steps.md) points at the packages that use
Filament actions, server requests from the editor, rendering hooks and Markdown rules.

## How to read these pages

1. [Trying the example](installation.md) installs it in an app, so you can see what you are about to copy.
2. [Creating your plugin package](creating-your-plugin.md) walks through copying and renaming it.
3. [A tour of the files](tour/_index.md) explains each file and why it is written the way it is.
4. [Building and HMR](building.md) and [Testing](testing.md) cover the development loop.

The editor's own documentation describes the plugin API in full. These pages explain the API only as far as the
example uses it.

## Requirements

- PHP 8.3 or later
- Filament 5 and Rich Editor Plus (`webwizardsusa/filament-rich-editor-plus`)
- Node.js and npm, to build the JavaScript module
