
# Hypermedia Libraries

Guidance on choosing and loading the integrated hypermedia libraries in HyperPress.

## Choosing a Hypermedia Library

**Datastar is the default library.**

This plugin comes with [HTMX](https://htmx.org), [Alpine Ajax](https://alpine-ajax.js.org/) and [Datastar](https://data-star.dev/) already integrated and enabled.

You can choose which library to use in the plugin's options page: Settings > HyperPress.

In the case of HTMX, you can also enable any of its extensions in the plugin's options page: Settings > HyperPress.

## Local vs CDN Loading

The plugin includes local copies of all libraries for privacy and offline development. You can choose to load from:

1. **Local files** (default): Libraries are served from your WordPress installation
2. **CDN**: Optional CDN loading from jsdelivr.net. Will always load the latest version of the library.
