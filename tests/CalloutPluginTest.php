<?php

use Hollyit\RichEditorPlus\Plugins\Core\BlockquotePlugin;
use Hollyit\RichEditorPlus\Rendering\RichContentPlusRenderer;
use Hollyit\RichEditorPlus\RichEditorPlus;
use Hollyit\RichEditorPlus\Support\BlockType;
use Hollyit\RichEditorPlusExample\Plugins\CalloutPlugin;
use Hollyit\RichEditorPlusExample\Plugins\CalloutVariant;
use Hollyit\RichEditorPlusExample\Tests\TestCase;

uses(TestCase::class);

function renderCallout(string|array $content, ?CalloutPlugin $plugin = null): string
{
    return RichContentPlusRenderer::make($content)
        ->plugins([$plugin ?? CalloutPlugin::make(), BlockquotePlugin::make()])
        ->parseHtml()
        ->toHtml();
}

function calloutDocument(mixed $variant): array
{
    return [
        'type' => 'doc',
        'content' => [
            ['type' => 'callout', 'attrs' => ['variant' => $variant], 'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Note']]],
            ]],
        ],
    ];
}

it('keeps a callout, its variant and the blocks inside it through parsing and rendering', function (): void {
    $html = '<div data-variant="warning" class="callout callout-warning" data-type="callout"><p>Mind the gap</p><blockquote><p>Quoted</p></blockquote></div>';

    expect(renderCallout($html))->toBe($html);
});

it('does not treat other divs as callouts', function (): void {
    expect(renderCallout('<div class="callout">Plain</div>'))->toBe('Plain');
});

it('gives stored content with an unknown variant the default one', function (): void {
    expect(renderCallout('<div data-type="callout" data-variant="danger"><p>Note</p></div>'))
        ->toBe('<div data-variant="info" class="callout callout-info" data-type="callout"><p>Note</p></div>');
});

it('keeps a variant from the browser out of the class name unless it is on offer', function (): void {
    $html = renderCallout(calloutDocument('x" onclick="evil()'));

    expect($html)
        ->toBe('<div data-variant="info" class="callout callout-info" data-type="callout"><p>Note</p></div>');
});

it('only accepts the variants the field offers, the first being the default', function (): void {
    $plugin = CalloutPlugin::make()->variants([CalloutVariant::Success, CalloutVariant::Warning]);

    expect(renderCallout(calloutDocument('info'), $plugin))->toContain('callout-success')
        ->and($plugin->getJsExtension()->getOptions())->toBe(['variants' => ['success', 'warning']]);
});

it('offers a block type for each variant', function (): void {
    $plugin = CalloutPlugin::make()->variants([CalloutVariant::Warning, CalloutVariant::Info]);

    expect(array_map(static fn (BlockType $type): array => [
        $type->getLabel(),
        $type->getNodeAttributes(),
        $type->getArguments(),
    ], $plugin->getBlockTypes()))->toBe([
        ['Warning callout', ['variant' => 'warning'], [['variant' => 'warning']]],
        ['Info callout', ['variant' => 'info'], [['variant' => 'info']]],
    ])
        ->and($plugin->getBlockTypes()[0]->isMultiBlock())->toBeTrue();
});

it('only shows the variant menu when there is a choice', function (): void {
    expect(CalloutPlugin::make()->getTools()[0]->getMenu())->toHaveCount(3)
        ->and(CalloutPlugin::make()->variants([CalloutVariant::Info])->getTools())->toBe([]);
});

it('needs at least one variant', function (): void {
    CalloutPlugin::make()->variants([]);
})->throws(InvalidArgumentException::class);

it('keeps callouts in a field that uses the plugin', function (): void {
    $field = RichEditorPlus::make('content')->addPlugins([CalloutPlugin::make()]);

    $html = RichContentPlusRenderer::make('<div data-type="callout" data-variant="success"><p>Saved</p><ul><li><p>Item</p></li></ul></div>')
        ->plugins($field->getPlugins())
        ->parseHtml()
        ->toHtml();

    expect($html)->toBe('<div data-variant="success" class="callout callout-success" data-type="callout"><p>Saved</p><ul><li><p>Item</p></li></ul></div>');
});
