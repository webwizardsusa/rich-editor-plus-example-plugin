<?php

namespace Webwizardsusa\RichEditorPlusExample\Plugins;

use Webwizardsusa\RichEditorPlus\Content\MarkSpec;
use Webwizardsusa\RichEditorPlus\Plugins\Plugin;
use Webwizardsusa\RichEditorPlus\Support\JsExtension;
use Webwizardsusa\RichEditorPlus\Support\Tool;
use Webwizardsusa\RichEditorPlusExample\RichEditorPlusExampleServiceProvider;

/**
 * A mark: text shown as a keyboard key, stored as `<kbd>`.
 *
 * The smallest useful shape of a plugin that ships its own JS. The mark is declared here for the server and again in
 * `resources/js/keyboard.js` for the browser, and the two must agree on the tag. The shortcut is a PHP setting passed
 * to the JS factory as an option, so the key the editor binds and the key the tooltip shows can't drift apart.
 */
class KeyboardPlugin extends Plugin
{
    protected string $shortcut = 'Mod-Shift-k';

    /**
     * The key that toggles the mark, in TipTap's notation such as `Mod-Shift-k`.
     */
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
                // Mirrors `excludes` in the JS mark, so the server keeps the same marks the editor would.
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
