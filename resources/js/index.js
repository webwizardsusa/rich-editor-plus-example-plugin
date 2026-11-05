import '../css/index.css'
import { createCallout } from './callout'
import { createKeyboard } from './keyboard'

/**
 * The module both plugins point `JsExtension::module()` at. Rich Editor Plus imports it once per page, before it
 * builds an editor, and calls this with its shared API. Each `registerExtension()` id matches a PHP plugin's
 * `getId()`, and the factory gets the options that plugin passed to `JsExtension::make()`.
 *
 * The `@tiptap/*` imports resolve to the editor's own TipTap and ProseMirror through the `sharedTiptap()` Vite plugin,
 * so this bundle holds only the code in this folder.
 */
export default function ({ registerExtension }) {
    registerExtension('example/keyboard', (options) => createKeyboard(options))
    registerExtension('example/callout', (options) => createCallout(options))
}

// The editor imports the module once, so a changed module can only take effect on a fresh page.
if (import.meta.hot) {
    import.meta.hot.accept(() => window.location.reload())
}
