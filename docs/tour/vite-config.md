---
title: The Vite config
weight: 50
description: How vite.config.js builds the module into dist/, runs the dev server, and keeps TipTap out of the bundle.
---

`vite.config.js` does two jobs, depending on the command:

- `vite build` (`npm run build`) writes the module and stylesheet into `dist/`, for `filament:assets` to publish.
- `vite` (`npm run dev`) serves the module to the host app with hot reloading, and writes the hot file `moduleUrl()`
  checks for.

Either way, the `sharedTiptap()` plugin rewrites the TipTap and ProseMirror imports to the editor's own instances.

## The asset id and entry

```js
const assetId = 'rich-editor-plus-example'

const entry = 'resources/js/index.js'
```

`assetId` must match the service provider's `assetId()`. `RegistersPluginAssets` looks for `dist/{assetId}.js`,
`dist/{assetId}.css` and the hot file `public/{assetId}.hot` in the app. The entry is the trait's default
`devServerEntry()`.

## Why sharedTiptap()

The editor exposes its TipTap and ProseMirror modules on `window.RichEditorPlus.tiptap`. Your bundle must use those
instead of bundling a second copy:

- ProseMirror relies on `instanceof` checks and plugin keys, so two copies of `prosemirror-state` break in subtle ways.
- `prosemirror-tables` registers its cell selection type once, so a second copy throws "Duplicate use of selection
  JSON ID cell".
- A copy would add hundreds of kilobytes.

`sharedTiptap()` rewrites imports of the shared modules (`@tiptap/core`, `@tiptap/suggestion`, and the
`@tiptap/pm/*` modules, under either their `@tiptap/pm/*` or `prosemirror-*` names) to read from that object. The
build reads each module's export names from your own `@tiptap/core` and `@tiptap/pm`, which is why they are dev
dependencies.

If the module loads before the editor, it throws a clear error. If it was built against a newer TipTap than the
editor shares and uses an export the editor lacks, it warns in the console and names the missing exports.

> [!IMPORTANT]
> After a build, check that `dist/rich-editor-plus-example.js` is small (under 2 KB for the example) and doesn't
> contain `Duplicate use of selection JSON ID`. Either sign means TipTap was bundled.

### Finding sharedTiptap

The plugin ships with Rich Editor Plus, in its `vite/shared-tiptap.js`. The example imports it from the first copy it
finds: the package's own `vendor/` when you run `composer install` inside the package (as CI does), the host app's
`vendor/` when the package sits in the app's `packages/` folder, or the editor's checkout next to it in the editor's
workbench:

```js
import { existsSync } from 'node:fs'
import path from 'node:path'
import { fileURLToPath, pathToFileURL } from 'node:url'

const packageRoot = path.dirname(fileURLToPath(import.meta.url))

const sharedTiptapPath = [
    path.join(packageRoot, 'vendor/webwizardsusa/filament-rich-editor-plus/vite/shared-tiptap.js'),
    path.join(packageRoot, '../../vendor/webwizardsusa/filament-rich-editor-plus/vite/shared-tiptap.js'),
    path.join(packageRoot, '../rich-editor-plus/vite/shared-tiptap.js'),
].find((candidate) => existsSync(candidate))

if (!sharedTiptapPath) {
    throw new Error('Rich Editor Plus is not installed: run `composer install` first.')
}

const { sharedTiptap } = await import(pathToFileURL(sharedTiptapPath).href)
```

Outside the editor's workbench, the last candidate never matches, so your own package can drop it.

## Build mode

```js
return {
    root: packageRoot,
    publicDir: false,
    plugins: [sharedTiptap()],
    build: {
        minify: true,
        rolldownOptions: {
            output: { minify: true },
        },
        outDir: 'dist',
        emptyOutDir: true,
        cssCodeSplit: false,
        lib: {
            entry: path.resolve(packageRoot, entry),
            formats: ['es'],
            fileName: () => `${assetId}.js`,
            cssFileName: assetId,
        },
    },
}
```

- A library build with one ES module output, named after the asset id, and one stylesheet (`cssCodeSplit: false`).
- `minify: true`, for both Vite and its Rolldown output, because the browser loads the file as it is.
- `emptyOutDir: true` clears `dist/` first, so stale files don't linger.

## Dev server mode

```js
if (command === 'serve') {
    const appRoot = path.resolve(packageRoot, '../..')

    return {
        root: appRoot,
        cacheDir: path.join(appRoot, `node_modules/.vite/${assetId}`),
        plugins: [
            sharedTiptap({ exclude: [] }),
            laravel({
                input: [path.relative(appRoot, path.join(packageRoot, entry))],
                hotFile: path.join(appRoot, `public/${assetId}.hot`),
                refresh: [path.join(packageRoot, 'src/**/*.php')],
            }),
        ],
        server: {
            port: 5179,
            strictPort: true,
        },
    }
}
```

- **`appRoot`**: the dev server runs from the host app, two folders up, because the package sits in the app's
  `packages/` folder. Change it if your package lives elsewhere.
- **`cacheDir`**: each dev server in the app needs its own cache folder, or they wipe each other's pre-bundled
  dependencies.
- **`sharedTiptap({ exclude: [] })`**: list any package you import that imports TipTap itself, such as
  `@tiptap/extension-code-block`. It is kept out of Vite's dependency pre-bundling, which would otherwise inline its
  own copy of the shared modules.
- **`laravel()`**: the Laravel Vite plugin. `input` is the entry relative to the app root, as
  `RegistersPluginAssets::devServerEntry()` expects. `hotFile` is the file the trait checks for. `refresh` reloads the
  page when a PHP file in `src/` changes.
- **`port`** with `strictPort: true`: a fixed port, so it fails loudly rather than moving to another port. Every dev
  server in the app needs its own.

## dist/ is committed

`dist/` holds the built module and stylesheet, and it is committed. It is what Composer installs: apps that use your
package run `filament:assets` and never build anything. Rebuild and commit `dist/` whenever you change
`resources/js/` or `resources/css/`.
