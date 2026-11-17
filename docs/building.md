---
title: Building and HMR
weight: 50
description: Build the plugin's JavaScript module, run its Vite dev server with hot reloading, and publish the assets.
---

A plugin package has two ways to deliver its JavaScript module and stylesheet to the editor:

- **Built**: `npm run build` writes `dist/`, and `php artisan filament:assets` publishes it into the app's `public/`.
  This is what apps that install your package use.
- **Dev server**: `npm run dev` serves the module from Vite with hot reloading. While it runs, the editor loads the
  module from it instead of from `public/`.

`RegistersPluginAssets::moduleUrl()` picks between them on each request. It uses the dev server when the app's
environment is `local` and the hot file `public/{assetId}.hot` exists, and the published asset otherwise.

## In a package of your own

The scripts in the package's `package.json`:

| Script | Runs | What it does |
|---|---|---|
| `npm run build` | `vite build` | Writes `dist/{assetId}.js` and `dist/{assetId}.css`. |
| `npm run dev` | `vite` | Starts the dev server and writes `public/{assetId}.hot` in the host app. |

Run them from the package folder, after installing its dev dependencies:

```bash
cd packages/rich-editor-plus-highlight
npm install
npm run build
```

Then publish the build from the app root:

```bash
php artisan filament:assets
```

Run `filament:assets` again after every build. The editor loads the published copy, not `dist/`.

### Hot reloading

```bash
cd packages/rich-editor-plus-highlight
npm run dev
```

The dev server runs from the host app's root (see [Dev server mode](tour/vite-config.md#dev-server-mode)), so the
package must sit in the app's `packages/` folder, or you must change `appRoot` in `vite.config.js`. The app must run
with `APP_ENV=local`.

While it runs:

- CSS changes apply without a reload.
- A change to the JavaScript reloads the page, because the editor imports each module only once. The module's
  `import.meta.hot` handler does this.
- A change to a PHP file in `src/` reloads the page too (`refresh` in the Vite config).
- Vite's HMR client is loaded in every Filament panel, through a render hook the trait registers.

When you stop the server, the Laravel Vite plugin removes the hot file and the editor goes back to the published
asset. If you have never built and published, the field then throws a `LogicException` saying the assets are not
available. Run `npm run build` and `php artisan filament:assets`.

> [!TIP]
> If the editor keeps loading an old module, check for a leftover `public/{assetId}.hot` file from a dev server that
> didn't shut down cleanly, and delete it.

### Before you commit

```bash
npm run build
```

Commit `dist/` with the source changes. Apps that install your package with Composer get `dist/`, not
`resources/js/`, and `.gitattributes` keeps the sources out of the archive.

Check the build output: the module should be small, and it must not contain `Duplicate use of selection JSON ID`,
which means TipTap was bundled. See [Why sharedTiptap()](tour/vite-config.md#why-sharedtiptap).

## In the Rich Editor Plus workbench

The example lives in the workbench app's `packages/` folder, which is an npm workspace and a Composer path
repository. The app's root `package.json` has scripts for each package. From the app root:

```bash
npm run build:example   # writes packages/rich-editor-plus-example/dist/
npm run dev:example     # dev server on port 5179, writes public/rich-editor-plus-example.hot
php artisan filament:assets
```

`npm run dev:all` starts the app's and every package's dev servers together, and `npm run build:all` builds
everything and runs `filament:assets`.

If you add your package to the workbench, add `dev:` and `build:` scripts for it to the root `package.json` that run
its workspace's scripts, for example:

```json
{
    "scripts": {
        "dev:highlight": "npm run dev --workspace=@acme/rich-editor-plus-highlight",
        "build:highlight": "npm run build --workspace=@acme/rich-editor-plus-highlight"
    }
}
```

## For apps that install your package

They run:

```bash
composer require acme/rich-editor-plus-highlight
php artisan filament:assets
```

and again `filament:assets` after each update. Many Filament apps already run `php artisan filament:upgrade` from
Composer's `post-autoload-dump` script, which publishes assets as well.
