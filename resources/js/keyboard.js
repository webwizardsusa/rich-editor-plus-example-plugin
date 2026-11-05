import { Mark } from '@tiptap/core'

/**
 * The browser half of `KeyboardPlugin`: a `<kbd>` mark. Its name and tag match the PHP `MarkSpec`, and its
 * `toggleKeyboard` command is the one the PHP `Tool` runs.
 */
export function createKeyboard({ shortcut }) {
    return Mark.create({
        name: 'keyboard',

        // A key name is one unit: typing straight after it shouldn't make the key longer.
        inclusive: false,

        // Inline code shows code, not keys, so the two don't share text.
        excludes: 'code',

        parseHTML() {
            return [{ tag: 'kbd' }]
        },

        renderHTML({ HTMLAttributes }) {
            return ['kbd', HTMLAttributes, 0]
        },

        addCommands() {
            return {
                toggleKeyboard:
                    () =>
                    ({ commands }) =>
                        commands.toggleMark(this.name),
            }
        },

        addKeyboardShortcuts() {
            return shortcut ? { [shortcut]: () => this.editor.commands.toggleKeyboard() } : {}
        },
    })
}
