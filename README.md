# Rich Editor Plus example plugin

A working plugin package to copy when you write your own plugin for
[Rich Editor Plus](../rich-editor-plus/README.md). It adds two plugins that use their own JS module:

- **`KeyboardPlugin`**: a mark. Text shown as a keyboard key, stored as `<kbd>`, with a toolbar button and a shortcut.
- **`CalloutPlugin`**: a block node. A box that holds paragraphs, lists and other blocks, in an info, warning or
  success style. It is offered in the slash menu and block switcher (which can also wrap several selected paragraphs),
  with a block toolbar menu for changing the style. It joins the editor's `container` group, so the editor's block
  tools treat it like a quote.

```php
use Webwizardsusa\RichEditorPlusExample\Plugins\CalloutPlugin;
use Webwizardsusa\RichEditorPlusExample\Plugins\CalloutVariant;
use Webwizardsusa\RichEditorPlusExample\Plugins\KeyboardPlugin;

RichEditorPlus::make('content')->addPlugins([
    KeyboardPlugin::make()->shortcut('Mod-Shift-k'),
    CalloutPlugin::make()->variants([CalloutVariant::Info, CalloutVariant::Warning]),
]);
```

Both are on the playground pages of this repo's app, at the end of the sample content.

## What goes where

```
composer.json                 Requires webwizardsusa/rich-editor-plus, registers the service provider
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
dist/                         The built module and stylesheet. Commit them: they are what Composer installs
tests/                        Pest tests for the PHP side
```

## How the halves fit

Every plugin is declared twice, once for each side, and the two must agree:

| PHP | JS |
|---|---|
| `getId()`: `example/callout` | `registerExtension('example/callout', factory)` |
| `JsExtension::make($id, ['variants' => …])` | the factory's first argument: `{ variants }` |
| `JsExtension::module(…::moduleUrl())` | `resources/js/index.js`, built to `dist/rich-editor-plus-example.js` |
| `NodeSpec::make('callout')` / `MarkSpec::make('keyboard')` | `Node.create({ name: 'callout' })` / `Mark.create({ name: 'keyboard' })` |
| how the spec parses and renders HTML | `parseHTML()` and `renderHTML()`, writing the same attributes in the same order |
| `Tool::command('toggleKeyboard')`, `BlockType::command('setCallout', [[…]])` | the commands in `addCommands()` |
| `BlockType::multiBlock()` | `setCallout` wraps the selection with `wrapIn()` |
| — | `group: 'block container'`: the slash menu, "select parent" and level commands work inside it |

The server only keeps what a spec declares. If the JS adds a node or mark that PHP doesn't declare, the tag is dropped
from stored and rendered HTML and only its text is kept. Whatever the browser sends is checked again on the server:
`CalloutPlugin` only renders a variant it offers, because the value ends up in a class name.

## Building

In this repo, from the app root:

```bash
npm run build:example   # writes dist/
npm run dev:example     # dev server on port 5179, with public/rich-editor-plus-example.hot
php artisan filament:assets
```

While the dev server runs, in a `local` environment, `moduleUrl()` points the editor at it; otherwise at the asset
`filament:assets` published. After a build, check that `dist/rich-editor-plus-example.js` is small (about 2 KB here)
and doesn't contain `Duplicate use of selection JSON ID`: TipTap and ProseMirror come from the editor through
`sharedTiptap()` and must never be bundled.

## Making it your own

1. Copy the folder and rename the Composer and npm package names, the namespace, the service provider and the
   translation namespace (`rich-editor-plus-example::`).
2. Give `assetPackage()` your Composer package name, and `assetId()` a unique id. Use the same id for `assetId` in
   `vite.config.js`, which names the built files and the hot file.
3. Give each plugin an id under your own prefix, such as `acme/callout`, and use it in `registerExtension()`.
4. In `vite.config.js`, import `sharedTiptap()` from `./vendor/webwizardsusa/rich-editor-plus/vite/shared-tiptap.js`, and
   change the dev server's port and app root to suit your setup.
5. The CSS only styles the editor. Pages that show rendered content need their own styles for your markup.

The [Writing a plugin with JavaScript](../rich-editor-plus/README.md#writing-a-plugin-with-javascript) section of the
editor's README covers the rest of the API, such as asking the server from the editor and accepting uploads.

## Tests

```bash
vendor/bin/pest packages/rich-editor-plus-example --compact
```
