---
title: The JS module and styles
weight: 40
description: The module entry that registers the TipTap extensions, the editor stylesheet and the translation file.
---

## resources/js/index.js

The module entry is the file both plugins point `JsExtension::module()` at:

```js
import '../css/index.css'
import { createCallout } from './callout'
import { createKeyboard } from './keyboard'

export default function ({ registerExtension }) {
    registerExtension('example/keyboard', (options) => createKeyboard(options))
    registerExtension('example/callout', (options) => createCallout(options))
}

// The editor imports the module once, so a changed module can only take effect on a fresh page.
if (import.meta.hot) {
    import.meta.hot.accept(() => window.location.reload())
}
```

Rich Editor Plus imports the module once per page, before it builds an editor, and calls its default export with its
shared API. Each `registerExtension()` id matches a PHP plugin's `getId()` (more exactly, the id passed to
`JsExtension::make()`), and the factory receives the options that plugin passed. Several plugins can share one module,
as these two do; the editor imports it only once.

The object the default export receives has:

| Key | What it is for |
|---|---|
| `registerExtension(id, factory)` | Registers the factory that builds the TipTap extension, or an array of them, for a JS extension id. The factory receives the options, the shared TipTap modules and the editor's context. |
| `extendExtension(id, decorator)` | Changes an extension another plugin registered, instead of importing it again. |
| `registerActionArguments(name, callback)` | Supplies arguments from the editor when a tool opens a Filament action. |
| `tiptap` | The editor's shared TipTap and ProseMirror modules. |

The example only uses `registerExtension`. [Where to look next](../next-steps.md) points at packages that use the
others.

### The HMR handler

The editor imports each module once, so a changed module can't be swapped into a running editor. During development,
the `import.meta.hot` block reloads the page when the module changes. CSS changes still apply without a reload.
`import.meta.hot` doesn't exist in the build, so the block is dropped from `dist/`.

### Importing the CSS

`import '../css/index.css'` puts the stylesheet into the build, which writes it to `dist/rich-editor-plus-example.css`.
During development the dev server injects it with hot reloading.

## resources/css/index.css

```css
.rep-editor .tiptap kbd {
    padding: 0.0625rem 0.375rem;
    border: 1px solid var(--rep-border);
    border-bottom-width: 2px;
    border-radius: 0.25rem;
    background: var(--rep-code-background);
    font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
    font-size: 0.85em;
}

.rep-editor .tiptap .callout-warning {
    --callout-color: var(--warning-600, #d97706);
}

.dark .rep-editor .tiptap .callout {
    background: color-mix(in srgb, var(--callout-color) 18%, transparent);
}
```

The styles are for the editor only:

- Every selector starts with `.rep-editor .tiptap`, so they can't leak into the rest of the panel.
- They build on the custom properties Rich Editor Plus defines on `.rep-editor`, such as `--rep-border` and
  `--rep-code-background`, and on Filament's palette variables, such as `--warning-600`. That way they follow the
  panel's colours and its light and dark themes.
- `.dark` is Filament's dark mode class.

The stylesheet is registered as a Filament asset, so panels load it on every page. Pages outside the panel that show
rendered content need their own styles for your markup.

## resources/lang/en/plugins.php

```php
<?php

return [
    'keyboard' => [
        'label' => 'Keyboard key',
    ],
    'callout' => [
        'block' => ':variant callout',
        'description' => 'A box that makes a note stand out',
        'variant' => 'Callout style',
        'current_variant' => 'Callout style: :item',
        'variants' => [
            'info' => 'Info',
            'warning' => 'Warning',
            'success' => 'Success',
        ],
    ],
];
```

Every label goes through `__()` with the package's namespace, such as
`__('rich-editor-plus-example::plugins.keyboard.label')`, so apps can translate or override them in
`lang/vendor/rich-editor-plus-example/{locale}/plugins.php`. `current_variant` keeps `:item` for the editor to fill in;
see `Tool::currentLabel()`.

A [test](../testing.md#translation-keys) checks that every key used in `src/` exists.
