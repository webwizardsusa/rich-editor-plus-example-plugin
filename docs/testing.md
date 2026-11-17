---
title: Testing
weight: 60
description: Test a plugin's server side with Pest and Orchestra Testbench, as the example package does.
---

The example's tests cover the PHP side of each plugin: what the server keeps, what it drops, and what it sends to the
editor. That is where content is validated and stored, so it is where tests pay off most.

## Running the tests

In a package checked out on its own, install its dependencies and run Pest:

```bash
composer install
composer test
```

In the workbench, run them from the app root:

```bash
vendor/bin/pest packages/rich-editor-plus-example --compact
```

`tests/bootstrap.php` loads the package's own `vendor/autoload.php` when it exists, and the host app's otherwise, so
the same tests run both ways.

## The test case

`tests/TestCase.php` extends Orchestra Testbench and boots Filament, Livewire, Blade Icons (with the Heroicons and
Material Design Icons sets), Rich Editor Plus and the package's own service provider, without an application of its
own:

```php
protected function getPackageProviders($app): array
{
    return [
        BladeIconsServiceProvider::class,
        BladeHeroiconsServiceProvider::class,
        BladeMdiServiceProvider::class,
        LivewireServiceProvider::class,
        SupportServiceProvider::class,
        ActionsServiceProvider::class,
        FormsServiceProvider::class,
        InfolistsServiceProvider::class,
        NotificationsServiceProvider::class,
        SchemasServiceProvider::class,
        TablesServiceProvider::class,
        WidgetsServiceProvider::class,
        FilamentServiceProvider::class,
        RichEditorPlusServiceProvider::class,
        RichEditorPlusExampleServiceProvider::class,
    ];
}
```

Keep the list when you copy it, and replace the last entry with your service provider. `phpunit.xml` sets
`APP_ENV=testing` and array cache and session drivers. Because the environment isn't `local`, `moduleUrl()` never
looks for a dev server in tests.

Each test file opts in with `uses(TestCase::class);`.

## Round trips through the renderer

Most tests render content with `RichContentPlusRenderer` and compare the output:

```php
use Webwizardsusa\RichEditorPlus\Rendering\RichContentPlusRenderer;
use Webwizardsusa\RichEditorPlusExample\Plugins\KeyboardPlugin;

it('keeps kbd text through parsing and rendering', function (): void {
    $html = RichContentPlusRenderer::make('<p>Press <kbd>Ctrl</kbd> + <kbd>S</kbd></p>')
        ->plugins([KeyboardPlugin::make()])
        ->parseHtml()
        ->toHtml();

    expect($html)->toBe('<p>Press <kbd>Ctrl</kbd> + <kbd>S</kbd></p>');
});
```

`parseHtml()` makes the renderer parse the HTML through the plugins' specs, as it does for HTML from outside the field.
An exact `toBe()` comparison checks the attribute order as well, which must match what the TipTap extension writes.

Test what the server drops as well as what it keeps:

- Without the plugin, `<kbd>` is dropped and its text kept.
- A plain `<div class="callout">` isn't a callout.
- A variant that isn't on offer becomes the default, whether it comes from stored HTML or from a document array, as the
  browser sends it:

```php
it('keeps a variant from the browser out of the class name unless it is on offer', function (): void {
    $document = [
        'type' => 'doc',
        'content' => [
            ['type' => 'callout', 'attrs' => ['variant' => 'x" onclick="evil()'], 'content' => [
                ['type' => 'paragraph', 'content' => [['type' => 'text', 'text' => 'Note']]],
            ]],
        ],
    ];

    expect(RichContentPlusRenderer::make($document)->plugins([CalloutPlugin::make()])->toHtml())
        ->toBe('<div data-variant="info" class="callout callout-info" data-type="callout"><p>Note</p></div>');
});
```

## What the editor receives

The PHP side decides the options the JavaScript gets and the tools it shows. Test those directly:

```php
it('gives the editor the same shortcut the tool shows', function (): void {
    $plugin = KeyboardPlugin::make()->shortcut('Mod-Alt-k');

    expect($plugin->getJsExtension()->toArray())->toBe([
        'id' => 'example/keyboard',
        'options' => ['shortcut' => 'Mod-Alt-k'],
        'modules' => [RichEditorPlusExampleServiceProvider::moduleUrl()],
    ])
        ->and($plugin->getTools()[0]->getShortcut())->toBe('Mod-Alt-k');
});
```

`CalloutPluginTest` does the same for block types (`getLabel()`, `getNodeAttributes()`, `getArguments()`,
`isMultiBlock()`) and the menu (`getMenu()`), and checks that `variants([])` throws.

A last test builds a field with `RichEditorPlus::make('content')->addPlugins([...])` and renders with
`$field->getPlugins()`, to check the plugin works alongside the editor's defaults.

## Translation keys

`tests/TranslationKeysTest.php` scans every PHP file in `src/` for keys in the package's namespace and checks that
each one resolves:

```php
use Symfony\Component\Finder\Finder;

it('resolves every translation key the package uses', function (): void {
    $missing = [];

    foreach (Finder::create()->files()->in(dirname(__DIR__).'/src')->name('*.php') as $file) {
        preg_match_all("/'(rich-editor-plus-example::[a-z0-9_.]+[a-z0-9_])'/", $file->getContents(), $matches);

        foreach ($matches[1] as $key) {
            if (__($key) === $key) {
                $missing[] = "{$key} ({$file->getRelativePathname()})";
            }
        }
    }

    expect($missing)->toBe([]);
});
```

Change `rich-editor-plus-example::` in the pattern to your namespace. The pattern only finds keys written in single
quotes. Keys built at runtime, such as `"…variants.{$this->value}"` in `CalloutVariant`, aren't checked; the block type
labels test covers those.

## The JavaScript side

The example has no JavaScript tests. The JS extensions are thin, and the commands and attributes they share with PHP
are covered by the round-trip tests above. A cheap safety net, which the code block package uses, is a PHP test that
reads the JS source and checks that every command a tool runs is defined there.
