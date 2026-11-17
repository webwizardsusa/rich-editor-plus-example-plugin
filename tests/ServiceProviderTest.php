<?php

use Webwizardsusa\RichEditorPlusExample\RichEditorPlusExampleServiceProvider;
use Webwizardsusa\RichEditorPlusExample\Tests\TestCase;

uses(TestCase::class);

it('registers its assets under its Composer package name', function (): void {
    $composerName = json_decode((string) file_get_contents(__DIR__.'/../composer.json'), true)['name'];

    expect(RichEditorPlusExampleServiceProvider::assetPackage())->toBe($composerName)
        ->and(RichEditorPlusExampleServiceProvider::moduleUrl())
        ->toContain("/js/{$composerName}/components/rich-editor-plus-example.js");
});
