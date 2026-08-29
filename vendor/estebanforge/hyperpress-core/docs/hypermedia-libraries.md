
# Hypermedia Libraries

Guidance on choosing and loading the integrated hypermedia libraries in HyperPress.

## Choosing a Hypermedia Library

**Datastar is the default library.**

This plugin comes with [HTMX](https://htmx.org), [Alpine Ajax](https://alpine-ajax.js.org/) and [Datastar](https://data-star.dev/) already integrated and enabled.

You can choose which library to use in the plugin's options page: Settings > HyperPress.

In the case of HTMX, you can also enable any of its extensions in the plugin's options page: Settings > HyperPress. The extension list is version-scoped: the htmx 2.x and 4.x lines ship different extensions, vendored under `assets/libs/htmx/2/` and `assets/libs/htmx/4/` respectively.

## HTMX Versions

htmx 4.0.0 is mutually incompatible with the 2.x line (explicit attribute inheritance, renamed non-bubbling events, fetch instead of XHR, 4xx/5xx swapping by default, extensions shipped inside the main package). HyperPress supports both:

- **New installs default to htmx 4.x.** hx-live (the official scripting companion) loads with 4.x by default; disable it with the `load_hxlive` option.
- **Existing sites stay on htmx 2.x** until you switch the version select on the HTMX settings tab. A stored options row without a version key belongs to a pre-existing 2.x site and is never silently upgraded; corrupt values also fall back to 2.x.
- Switching to 4.x on an old site? Enable the `htmx-2-compat` extension first (it restores 2.x inheritance, event names and error handling while you migrate templates), and audit templates with `npx htmx.org@4.0.0 upgrade-check -- ./wp-content/themes/your-theme`.
- With 2.x you can still load Hyperscript and pair Alpine.js, as before. On 4.x those are replaced by hx-live.

## Datastar CSP Mode

Datastar ships at 1.0.3+. Sites that enforce a strict Content-Security-Policy can enable CSP mode on the Datastar settings tab (or with the `datastar_csp` option / `hyperpress/datastar/csp_enabled` filter): HyperPress then adds a per-request nonce to `<html>` and to every enqueued script tag, and sends a strict `script-src` policy header, so Datastar runs without `unsafe-eval` and injected scripts are blocked by the browser. Frontend only. Full filter and helper reference: [Developer Configuration](developer-configuration.md#datastar-csp-mode).

## Local vs CDN Loading

The plugin includes local copies of all libraries for privacy and offline development. You can choose to load from:

1. **Local files** (default): Libraries are served from your WordPress installation
2. **CDN**: Optional CDN loading from jsdelivr.net, pinned to the exact versions vendored by this release (htmx 2.0.10 / 4.0.0, Datastar 1.0.3), so CDN and local modes serve the same code. Upstream npm `latest` tags may lag; HyperPress never auto-tracks them.
