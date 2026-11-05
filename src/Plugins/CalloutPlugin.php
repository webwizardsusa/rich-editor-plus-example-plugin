<?php

namespace Hollyit\RichEditorPlusExample\Plugins;

use DOMElement;
use Hollyit\RichEditorPlus\Content\NodeSpec;
use Hollyit\RichEditorPlus\Plugins\Plugin;
use Hollyit\RichEditorPlus\Support\BlockType;
use Hollyit\RichEditorPlus\Support\JsExtension;
use Hollyit\RichEditorPlus\Support\Tool;
use Hollyit\RichEditorPlus\Support\ToolMenuItem;
use Hollyit\RichEditorPlusExample\RichEditorPlusExampleServiceProvider;
use InvalidArgumentException;

/**
 * A block node: a callout box that holds other blocks, such as paragraphs and lists, stored as
 * `<div data-variant="info" class="callout callout-info" data-type="callout"><p>…</p></div>`.
 *
 * It shows the parts a block plugin can have: a node with an attribute that is checked on the way in and out, one
 * block type per variant for the slash menu and block switcher, and a block toolbar menu for changing the variant.
 * `resources/js/callout.js` defines the same node for the browser.
 */
class CalloutPlugin extends Plugin
{
    /**
     * @var array<CalloutVariant>
     */
    protected array $variants = [CalloutVariant::Info, CalloutVariant::Warning, CalloutVariant::Success];

    /**
     * The variants on offer, the first being the default. Stored content with any other variant gets the default.
     *
     * @param  array<CalloutVariant>  $variants
     */
    public function variants(array $variants): static
    {
        if ($variants === []) {
            throw new InvalidArgumentException('A callout needs at least one variant.');
        }

        $this->variants = array_values($variants);

        return $this;
    }

    /**
     * @return array<CalloutVariant>
     */
    public function getVariants(): array
    {
        return $this->variants;
    }

    public function getId(): string
    {
        return 'example/callout';
    }

    public function getNodes(): array
    {
        $values = $this->getVariantValues();

        $allowed = static fn (mixed $value): string => in_array($value, $values, true) ? $value : $values[0];

        return [
            NodeSpec::make('callout')
                ->parseUsing(static fn (DOMElement $element): bool => strtolower($element->tagName) === 'div'
                    && $element->getAttribute('data-type') === 'callout')
                // The browser controls the state, so the variant is checked both when HTML is parsed and when a
                // document is rendered: it ends up in a class name.
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

    public function getJsExtension(): JsExtension
    {
        return JsExtension::make($this->getId(), ['variants' => $this->getVariantValues()])
            ->module(RichEditorPlusExampleServiceProvider::moduleUrl());
    }

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

    /**
     * @return array<string>
     */
    protected function getVariantValues(): array
    {
        return array_map(static fn (CalloutVariant $variant): string => $variant->value, $this->variants);
    }
}
