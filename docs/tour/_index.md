---
title: A tour of the files
weight: 40
---

These pages walk through the example package file by file, in the order you would write them. Each one explains what
the file does and the choices behind it, so you know what to keep when you replace the plugins with your own.

```text
composer.json                 Requires Rich Editor Plus, registers the service provider
package.json                  Vite and the TipTap packages, as dev dependencies only
vite.config.js                `vite build` writes dist/; `vite` runs the dev server
src/
  RichEditorPlusExampleServiceProvider.php   Translations, and the built assets via RegistersPluginAssets
  Plugins/KeyboardPlugin.php  The mark: MarkSpec, Tool, JsExtension
  Plugins/CalloutPlugin.php   The node: NodeSpec, BlockTypes, a menu Tool, JsExtension
  Plugins/CalloutVariant.php  The styles a callout can have
resources/
  js/index.js                 The module entry: registers both JS extensions
  js/keyboard.js              The TipTap mark
  js/callout.js               The TipTap node
  css/index.css               Editor styles, built into dist/
  lang/en/plugins.php         Labels
dist/                         The built module and stylesheet, committed
tests/                        Pest tests for the PHP side
```

## How the two halves fit

Every plugin is declared twice: in PHP, for the server, which parses, validates and renders content, and in
JavaScript, for the TipTap editor in the browser. The two must agree on these points:

| PHP | JavaScript |
|---|---|
| `getId()`: `example/callout` | `registerExtension('example/callout', factory)` |
| `JsExtension::make($id, ['variants' => …])` | the factory's argument: `{ variants }` |
| `JsExtension::module(…::moduleUrl())` | `resources/js/index.js`, built to `dist/rich-editor-plus-example.js` |
| `NodeSpec::make('callout')`, `MarkSpec::make('keyboard')` | `Node.create({ name: 'callout' })`, `Mark.create({ name: 'keyboard' })` |
| how the spec parses and renders HTML | `parseHTML()` and `renderHTML()`, writing the same attributes in the same order |
| `Tool::command('toggleKeyboard')`, `BlockType::command('setCallout', [[…]])` | the commands in `addCommands()` |

The server keeps only what a spec declares. If the JavaScript adds a node or mark the PHP side doesn't declare, the
tag is dropped from stored and rendered HTML and only its text is kept. Everything the browser sends is checked again
on the server.

## Pages

- [Package files](package-files.md): `composer.json`, `package.json`, `.gitattributes` and the service provider.
- [The keyboard mark](keyboard-plugin.md): `KeyboardPlugin.php` and `keyboard.js`.
- [The callout block](callout-plugin.md): `CalloutPlugin.php`, `CalloutVariant.php` and `callout.js`.
- [The JS module and styles](javascript-module.md): `index.js`, `index.css` and the translations.
- [The Vite config](vite-config.md): `vite.config.js`, its two modes, and `dist/`.
