<?php

namespace Hollyit\RichEditorPlusExample;

use Hollyit\RichEditorPlus\Support\RegistersPluginAssets;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

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
        return 'hollyit/rich-editor-plus-example';
    }

    public static function assetId(): string
    {
        return 'rich-editor-plus-example';
    }
}
