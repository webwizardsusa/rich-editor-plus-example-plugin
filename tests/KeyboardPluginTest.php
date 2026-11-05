<?php

use Webwizardsusa\RichEditorPlus\Plugins\Core\CodePlugin;
use Webwizardsusa\RichEditorPlus\Rendering\RichContentPlusRenderer;
use Webwizardsusa\RichEditorPlusExample\Plugins\KeyboardPlugin;
use Webwizardsusa\RichEditorPlusExample\RichEditorPlusExampleServiceProvider;
use Webwizardsusa\RichEditorPlusExample\Tests\TestCase;

uses(TestCase::class);

function renderWithKeyboard(string $html, array $plugins = []): string
{
    return RichContentPlusRenderer::make($html)
        ->plugins([KeyboardPlugin::make(), ...$plugins])
        ->parseHtml()
        ->toHtml();
}

it('keeps kbd text through parsing and rendering', function (): void {
    expect(renderWithKeyboard('<p>Press <kbd>Ctrl</kbd> + <kbd>S</kbd></p>'))
        ->toBe('<p>Press <kbd>Ctrl</kbd> + <kbd>S</kbd></p>');
});

it('drops the kbd tag but keeps its text without the plugin', function (): void {
    $html = RichContentPlusRenderer::make('<p>Press <kbd>Ctrl</kbd></p>')->parseHtml()->toHtml();

    expect($html)->toBe('<p>Press Ctrl</p>');
});

it('does not keep inline code and a key on the same text', function (): void {
    expect(renderWithKeyboard('<p><code><kbd>Esc</kbd></code></p>', [CodePlugin::make()]))
        ->not->toContain('<code>');
});

it('gives the editor the same shortcut the tool shows', function (): void {
    $plugin = KeyboardPlugin::make()->shortcut('Mod-Alt-k');

    expect($plugin->getJsExtension()->toArray())->toBe([
        'id' => 'example/keyboard',
        'options' => ['shortcut' => 'Mod-Alt-k'],
        'modules' => [RichEditorPlusExampleServiceProvider::moduleUrl()],
    ])
        ->and($plugin->getTools()[0]->getShortcut())->toBe('Mod-Alt-k');
});
