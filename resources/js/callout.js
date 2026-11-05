import { mergeAttributes, Node } from '@tiptap/core'

/**
 * The browser half of `CalloutPlugin`: a box that holds other blocks, such as paragraphs and lists, like a quote. The
 * name, tag, attributes and commands match what the PHP plugin declares, and `variants` comes from PHP, the first being
 * the default.
 */
export function createCallout({ variants }) {
    const allowed = (variant) => (variants.includes(variant) ? variant : variants[0])

    return Node.create({
        name: 'callout',

        // The `container` group gives it the block tools a quote has: the slash menu works in paragraphs inside it,
        // "select parent" stops at it, and the level commands lift blocks out of it.
        group: 'block container',

        content: 'block+',

        // Pasted content keeps the callout around it, as with a quote.
        defining: true,

        addAttributes() {
            return {
                variant: {
                    default: variants[0],
                    parseHTML: (element) => allowed(element.getAttribute('data-variant')),
                    // Same attributes, in the same order, as the PHP spec renders.
                    renderHTML: ({ variant }) => ({
                        'data-variant': allowed(variant),
                        class: `callout callout-${allowed(variant)}`,
                    }),
                },
            }
        },

        parseHTML() {
            return [{ tag: 'div[data-type="callout"]' }]
        },

        renderHTML({ HTMLAttributes }) {
            return ['div', mergeAttributes(HTMLAttributes, { 'data-type': 'callout' }), 0]
        },

        addCommands() {
            return {
                // Used by the block types: `BlockType::command('setCallout', [['variant' => 'info']])`. It wraps the
                // selected blocks, so several selected paragraphs become one callout (`BlockType::multiBlock()`).
                setCallout:
                    (attributes = {}) =>
                    ({ commands }) =>
                        commands.wrapIn(this.name, { variant: allowed(attributes.variant) }),

                // Used by the toolbar menu: `ToolMenuItem::command('setCalloutVariant', ['warning'])`.
                setCalloutVariant:
                    (variant) =>
                    ({ commands }) =>
                        variants.includes(variant) && commands.updateAttributes(this.name, { variant }),
            }
        },
    })
}
