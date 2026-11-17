---
title: The keyboard mark
weight: 20
description: KeyboardPlugin and keyboard.js, the smallest useful plugin that ships its own JavaScript.
---

`KeyboardPlugin` is the smallest useful shape of a plugin with its own JavaScript: a mark, one toolbar button and a
shortcut. Start here when your plugin formats inline text.

## The PHP side

`src/Plugins/KeyboardPlugin.php`:

```php
<?php

namespace Webwizardsusa\RichEditorPlusExample\Plugins;

use Webwizardsusa\RichEditorPlus\Content\MarkSpec;
use Webwizardsusa\RichEditorPlus\Plugins\Plugin;
use Webwizardsusa\RichEditorPlus\Support\JsExtension;
use Webwizardsusa\RichEditorPlus\Support\Tool;
use Webwizardsusa\RichEditorPlusExample\RichEditorPlusExampleServiceProvider;

class KeyboardPlugin extends Plugin
{
    protected string $shortcut = 'Mod-Shift-k';

    public function shortcut(string $shortcut): static
    {
        $this->shortcut = $shortcut;

        return $this;
    }

    public function getId(): string
    {
        return 'example/keyboard';
    }

    public function getMarks(): array
    {
        return [
            MarkSpec::make('keyboard')
                ->parseTags(['kbd'])
                ->excludes(['code'])
                ->renderTag('kbd'),
        ];
    }

    public function getJsExtension(): JsExtension
    {
        return JsExtension::make($this->getId(), ['shortcut' => $this->shortcut])
            ->module(RichEditorPlusExampleServiceProvider::moduleUrl());
    }

    public function getTools(): array
    {
        return [
            Tool::make('keyboard')
                ->label(__('rich-editor-plus-example::plugins.keyboard.label'))
                ->icon('mdi-keyboard-outline')
                ->shortcut($this->shortcut)
                ->command('toggleKeyboard')
                ->active('keyboard')
                ->inline()
                ->group('format')
                ->order(70),
        ];
    }
}
```

### The plugin class

A plugin extends `Webwizardsusa\RichEditorPlus\Plugins\Plugin` and is created with `make()`, which resolves it from
the container and applies any `configureUsing()` callbacks. Configuration methods such as `shortcut()` return
`static` so they chain.

### getId()

The id names the plugin. It is the key the editor uses to replace a plugin with another of the same id, and here also
the id the JavaScript extension is registered under. Use your own prefix.

### getMarks()

A `MarkSpec` tells the server about the mark, so it can parse stored HTML into the document and render the document
back to HTML:

- `MarkSpec::make('keyboard')`: the mark's name. It must be the `name` of the TipTap mark.
- `parseTags(['kbd'])`: which HTML tags become this mark when HTML is parsed.
- `renderTag('kbd')`: the tag it renders as.
- `excludes(['code'])`: marks that can't share text with it. It mirrors `excludes` in the TipTap mark, so the server
  keeps the same marks the editor would. Inline code shows code, not keys.

Without the spec, the server drops the `<kbd>` tag and keeps only its text, even if the browser sent it.

### getJsExtension()

`JsExtension::make($id, $options)` tells the editor which registered TipTap extension to load for this plugin, and the
options to pass to its factory. `module()` adds the URL of the ES module that registers it; the editor imports each
module once per page before it builds an editor.

The shortcut is a PHP setting passed to the browser as an option. That way the key the editor binds and the key the
tooltip shows come from one value and can't drift apart.

### getTools()

A `Tool` is a toolbar button:

- `label()` and `icon()`: the tooltip and the icon (a Blade icon name; Rich Editor Plus uses Material Design Icons).
- `shortcut()`: shown in the tooltip, in TipTap's notation. `Mod` shows as Cmd on a Mac and Ctrl elsewhere. It
  doesn't bind the key; the TipTap extension does.
- `command('toggleKeyboard')`: the editor command the button runs. The TipTap mark defines it.
- `active('keyboard')`: the button shows as pressed when the `keyboard` mark is active at the selection.
- `inline()`, `group('format')` and `order(70)`: an inline formatting tool, in the format group, placed by its order.

The tool's name, `keyboard`, is also what a field's `toolbarTools()` list uses to place it.

## The JavaScript side

`resources/js/keyboard.js`:

```js
import { Mark } from '@tiptap/core'

export function createKeyboard({ shortcut }) {
    return Mark.create({
        name: 'keyboard',

        inclusive: false,

        excludes: 'code',

        parseHTML() {
            return [{ tag: 'kbd' }]
        },

        renderHTML({ HTMLAttributes }) {
            return ['kbd', HTMLAttributes, 0]
        },

        addCommands() {
            return {
                toggleKeyboard:
                    () =>
                    ({ commands }) =>
                        commands.toggleMark(this.name),
            }
        },

        addKeyboardShortcuts() {
            return shortcut ? { [shortcut]: () => this.editor.commands.toggleKeyboard() } : {}
        },
    })
}
```

The factory gets the options from `JsExtension::make()` and returns a TipTap mark. Compare it with the PHP side:

- `name: 'keyboard'` matches `MarkSpec::make('keyboard')`.
- `parseHTML()` and `renderHTML()` use `kbd`, as `parseTags()` and `renderTag()` do.
- `excludes: 'code'` matches `excludes(['code'])`.
- `toggleKeyboard` is the command `Tool::command()` names.
- `addKeyboardShortcuts()` binds the shortcut from PHP.

`inclusive: false` is editor-only behaviour: typing straight after a key name doesn't make the key longer. Anything
that only affects editing, rather than what is stored, needs no PHP counterpart.

`@tiptap/core` resolves to the editor's own TipTap at runtime, so `Mark` here is the same class the editor uses. See
[The Vite config](vite-config.md).

## Tests

`tests/KeyboardPluginTest.php` checks the parts that matter on the server: `<kbd>` survives parsing and rendering, it
is dropped (text kept) without the plugin, it doesn't share text with inline code, and `getJsExtension()` sends the
shortcut the tool shows. See [Testing](../testing.md).
