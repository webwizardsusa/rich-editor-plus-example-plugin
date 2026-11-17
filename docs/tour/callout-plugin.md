---
title: The callout block
weight: 30
description: CalloutPlugin, CalloutVariant and callout.js, a container node with block types and a toolbar menu.
---

`CalloutPlugin` is a block node that holds other blocks, the way a quote does. It shows the parts a block plugin can
have:

- a node with an attribute that is checked when HTML is parsed and when the document is rendered;
- one block type per variant, for the slash menu and the block switcher;
- a block toolbar menu for changing the variant.

It is stored as:

```html
<div data-variant="info" class="callout callout-info" data-type="callout"><p>…</p></div>
```

## CalloutVariant

`src/Plugins/CalloutVariant.php` is a string-backed enum of the styles a callout can have:

```php
enum CalloutVariant: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Success = 'success';

    public function getLabel(): string
    {
        return __("rich-editor-plus-example::plugins.callout.variants.{$this->value}");
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::Info => 'mdi-information-outline',
            self::Warning => 'mdi-alert-outline',
            self::Success => 'mdi-check-circle-outline',
        };
    }
}
```

The value is what stored HTML holds in `data-variant` and at the end of the `callout-{value}` class. Once content uses
a value, never change it, or that content falls back to the default variant. Labels and icons can change freely.

An enum makes `variants()` type-safe: a field can only offer values the plugin knows how to render.

## The node spec

```php
public function getNodes(): array
{
    $values = $this->getVariantValues();

    $allowed = static fn (mixed $value): string => in_array($value, $values, true) ? $value : $values[0];

    return [
        NodeSpec::make('callout')
            ->parseUsing(static fn (DOMElement $element): bool => strtolower($element->tagName) === 'div'
                && $element->getAttribute('data-type') === 'callout')
            ->attribute(
                'variant',
                parse: static fn (DOMElement $element): string => $allowed($element->getAttribute('data-variant')),
                render: static fn (array $attributes): array => [
                    'data-variant' => $allowed($attributes['variant']),
                    'class' => 'callout callout-'.$allowed($attributes['variant']),
                ],
                default: $values[0],
            )
            ->htmlAttributes(['data-type' => 'callout'])
            ->renderTag('div'),
    ];
}
```

- `parseUsing()` decides which elements are callouts. A plain `<div>` isn't one; only a `div` with
  `data-type="callout"` is. Use a closure like this when a tag name alone (`parseTags()`) would match too much.
- `attribute()` declares the `variant` attribute: how to read it from an element (`parse`), which HTML attributes it
  renders as (`render`), and its `default`.
- `htmlAttributes()` adds fixed attributes to the rendered element.
- `renderTag('div')` is the element it renders as. Its content (the blocks inside) is rendered into it.

### Check values twice

The variant is checked both when HTML is parsed and when a document is rendered. The editor sends a document from the
browser, so anyone can send any value; and the value ends up in a class name. `$allowed()` replaces anything the
field doesn't offer with the default, so `x" onclick="…` can never reach the output. Stored content with a variant the
field no longer offers gets the default too.

Do the same for any attribute of yours whose value lands in markup: validate it against a known list or pattern on
both paths.

### The rendered attributes match the editor's

`render` returns `data-variant` then `class`, and `htmlAttributes()` adds `data-type`. The TipTap node renders the
same attributes in the same order, so HTML stored from the editor and HTML rendered on the server are identical.

## The JS extension

```php
public function getJsExtension(): JsExtension
{
    return JsExtension::make($this->getId(), ['variants' => $this->getVariantValues()])
        ->module(RichEditorPlusExampleServiceProvider::moduleUrl());
}
```

The variants on offer go to the browser as a list of values, the first being the default.

## Block types

```php
public function getBlockTypes(): array
{
    return array_map(
        static fn (CalloutVariant $variant, int $index): BlockType => BlockType::make("callout-{$variant->value}")
            ->label(__('rich-editor-plus-example::plugins.callout.block', ['variant' => $variant->getLabel()]))
            ->description(__('rich-editor-plus-example::plugins.callout.description'))
            ->icon($variant->getIcon())
            ->keywords(['callout', 'note', 'notice', $variant->value])
            ->node('callout', ['variant' => $variant->value])
            ->command('setCallout', [['variant' => $variant->value]])
            ->switchable()
            ->multiBlock()
            ->order(60 + $index),
        $this->variants,
        array_keys($this->variants),
    );
}
```

A `BlockType` is an entry in the slash menu, and with `switchable()` in the block switcher too. There is one per
variant, so "Warning callout" is one choice rather than two steps.

- `label()`, `description()`, `icon()` and `keywords()` are what the menus show and search.
- `node('callout', ['variant' => …])` tells the switcher which node and attributes the type stands for, so it can show
  the current block's type.
- `command('setCallout', [[…]])` is the editor command it runs, with its arguments as a list. Here the one argument
  is an attributes object.
- `multiBlock()` offers the type in the switcher when several paragraphs are selected. Its command must turn the whole
  selection into one block, which `setCallout` does by wrapping them all in one callout.
- `order()` places it in the menus.

## The variant menu

```php
public function getTools(): array
{
    if (count($this->variants) < 2) {
        return [];
    }

    return [
        Tool::make('calloutVariant')
            ->label(__('rich-editor-plus-example::plugins.callout.variant'))
            ->icon($this->variants[0]->getIcon())
            ->currentLabel(__('rich-editor-plus-example::plugins.callout.current_variant'))
            ->menu(array_map(
                static fn (CalloutVariant $variant): ToolMenuItem => ToolMenuItem::make($variant->value)
                    ->label($variant->getLabel())
                    ->icon($variant->getIcon())
                    ->command('setCalloutVariant', [$variant->value])
                    ->active('callout', ['variant' => $variant->value]),
                $this->variants,
            ))
            ->block()
            ->nodes(['callout'])
            ->showWithCursor()
            ->order(5),
    ];
}
```

- `menu()` makes the tool a dropdown of `ToolMenuItem`s, each with its own command and active state.
- `currentLabel()` is the button's accessible name while an item is current, with the item's label filled in for
  `:item`, such as "Callout style: Warning".
- `block()` with `nodes(['callout'])` makes it a block tool: it shows in the block toolbar while the cursor is in a
  callout, not with the inline formatting tools.
- `showWithCursor()` shows the block toolbar with a plain cursor, without selecting text first.

With only one variant there is nothing to choose, so the plugin returns no tools.

## The TipTap node

`resources/js/callout.js`:

```js
import { mergeAttributes, Node } from '@tiptap/core'

export function createCallout({ variants }) {
    const allowed = (variant) => (variants.includes(variant) ? variant : variants[0])

    return Node.create({
        name: 'callout',

        group: 'block container',

        content: 'block+',

        defining: true,

        addAttributes() {
            return {
                variant: {
                    default: variants[0],
                    parseHTML: (element) => allowed(element.getAttribute('data-variant')),
                    renderHTML: ({ variant }) => ({
                        'data-variant': allowed(variant),
                        class: `callout callout-${allowed(variant)}`,
                    }),
                },
            }
        },

        parseHTML() {
            return [{ tag: 'div[data-type="callout"]' }]
        },

        renderHTML({ HTMLAttributes }) {
            return ['div', mergeAttributes(HTMLAttributes, { 'data-type': 'callout' }), 0]
        },

        addCommands() {
            return {
                setCallout:
                    (attributes = {}) =>
                    ({ commands }) =>
                        commands.wrapIn(this.name, { variant: allowed(attributes.variant) }),

                setCalloutVariant:
                    (variant) =>
                    ({ commands }) =>
                        variants.includes(variant) && commands.updateAttributes(this.name, { variant }),
            }
        },
    })
}
```

- `group: 'block container'`: the `container` group is how Rich Editor Plus recognises a node that holds blocks like a
  quote. The slash menu works in paragraphs inside it, "select parent" stops at it, and the level commands lift blocks
  out of it.
- `content: 'block+'`: it holds one or more blocks.
- `defining: true`: pasted content keeps the callout around it, as with a quote.
- `allowed()` mirrors the PHP check. The editor works with the same rules the server enforces, so what you see is what
  gets saved.
- `setCallout` uses `wrapIn()`, which wraps every selected block. That is what makes `multiBlock()` work.
- `setCalloutVariant` refuses a variant the field doesn't offer.

## Tests

`tests/CalloutPluginTest.php` covers the round trip, that other `div`s aren't callouts, the default for unknown and
hostile variants, `variants()` (including the empty-array exception), the block types and the menu. See
[Testing](../testing.md).
