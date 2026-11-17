<?php

namespace Webwizardsusa\RichEditorPlusExample;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Webwizardsusa\RichEditorPlus\Support\RegistersPluginAssets;

/**
 * Registers the package's translations and its built JS module and stylesheet. `RegistersPluginAssets` finds
 * `dist/rich-editor-plus-example.{js,css}`, or the Vite dev server while `public/rich-editor-plus-example.hot` exists.
 */
class RichEditorPlusExampleServiceProvider extends PackageServiceProvider
{
    use RegistersPluginAssets;

    public function configurePackage(Package $package): void
    {
        $package
            ->name('rich-editor-plus-example')
            ->hasTranslations();
    }

    public function packageBooted(): void
    {
        $this->registerPluginAssets();
    }

    public static function assetPackage(): string
    {
        return 'webwizardsusa/rich-editor-plus-example-plugin';
    }

    public static function assetId(): string
    {
        return 'rich-editor-plus-example';
    }
}
