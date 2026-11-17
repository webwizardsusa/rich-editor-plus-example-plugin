---
title: Where to look next
weight: 70
description: Plugins that show the features the example leaves out, such as Filament actions, server requests, rendering hooks and Markdown rules.
---

The example covers marks, nodes, block types, tools and a JS module. Rich Editor Plus offers more, and the packages
below use it in working code. Read their source next to the editor's
[plugin documentation](docs:rich-editor-plus/introduction).

## A tool that opens a Filament action

When a tool needs input, such as a URL or a list of lines, it opens a Filament action (a modal form) instead of running
a command. The code block package's "Highlight lines" tool is a compact example. See
[Rich Editor Plus Code](docs:rich-editor-plus-code/introduction).

- `src/Actions/HighlightLinesAction.php` builds the `Action`: a form with one text input and a validation rule. Its
  `action()` sends a command back to the editor with `$component->runCommands([EditorCommand::make(...)], $arguments['editorSelection'] ?? null)`.
- `CodeBlockPlugin::getActions()` returns it, and the tool opens it with `Tool::action(HighlightLinesAction::NAME)`.
- `resources/js/index.js` calls `registerActionArguments('highlightCodeLines', (editor) => ({ ... }))`, so the modal
  opens filled with the current block's value. The action reads it with `fillForm(fn (array $arguments) => ...)`.

## Asking the server from the editor

A plugin whose JavaScript needs data from the server while editing implements `AnswersEditorRequests`. The editor's
`callPlugin(pluginId, request, payload)` reaches the plugin's `answerEditorRequest()` through the field, with the
page's own authorization.

The embed package's `EmbedPlugin` (`webwizardsusa/rich-editor-plus-embed`) asks the server for previews of the URLs in
the document. Look at `answerEditorRequest()` in `src/EmbedPlugin.php` and at `resources/js/previews.js`, which batches
the calls.

Treat the payload as untrusted input, and refuse anything that changes data while `$field->isDisabled()`.

## Changing content when it is rendered

Several contracts let a plugin take part in rendering for display (`toHtml()`):

| Contract | What it does | Used by |
|---|---|---|
| `PreparesRenderedContent` | Changes documents just before rendering, with the whole batch at once, such as filling in data looked up on the server. | `EmbedPlugin::prepareRenderedContents()` |
| `PatchesRenderedMarkup` | Rendered nodes whose element holds markup, such as an embed's `<figure>`. It extends `PreparesRenderedContent`. | `EmbedPlugin` |
| `TransformsRenderedHtml` | Rewrites the rendered HTML, with every piece in the batch at once. It never runs for storage or other formats. | `CodeBlockPlugin`, which highlights code with Phiki |

Stored content is never changed by these: only the HTML for display.

## Markdown

A plugin whose nodes or marks have a Markdown form implements `ProvidesMarkdownRules`, returning `NodeRule` and
`MarkRule` instances from `getMarkdownNodeRules()` and `getMarkdownMarkRules()`. The code block package's
`CodeBlockPlugin::getMarkdownNodeRules()` writes a fence with options after the language, such as
```` ```php showLineNumbers {2,4-6} ````. `EmbedPlugin` has Markdown rules too.

## Replacing or extending another plugin

- **Replace**: return the same `getId()` as the plugin to replace. The code block package's `CodeBlockPlugin` extends
  the core one and keeps its `core/code-block` id, so it takes its place and existing content keeps working.
- **Extend without replacing**: `RichEditorPlus::extendPlugin($idOrClass, fn (PluginExtension $extension) => ...)` adds
  tools, actions, block types, nodes, marks, global attributes or JS modules to an existing plugin. A module added with
  `addModule()` can call `extendExtension(id, decorator)` to change its TipTap extension.

## Uploads, editor styles and global attributes

These are in the editor package itself:

- `AcceptsEditorUploads`: `ImagePlugin` and `AttachmentPlugin`.
- `ProvidesEditorStyles`: `PaletteStylePlugin`, which the colour and font plugins share.
- `ProvidesGlobalAttributes`: `TextAlignPlugin`.
