# HyperPress - Hypermedia for WordPress

**HyperPress** is a developer-focused WordPress plugin (and library) that integrates powerful hypermedia libraries like [HTMX](https://htmx.org), [Alpine AJAX](https://alpine-ajax.js.org/), and [Datastar](https://data-star.dev/). It provides a robust foundation for building modern, dynamic, and high-performance websites and applications directly within the WordPress ecosystem.

HyperPress equips developers with a powerful toolkit, including:

* A dedicated REST API endpoint (`/wp-html/v1/`) for serving hypermedia template partials.
* HyperFields: a streamlined PHP API for registering custom data fields.
* HyperBlocks: a simple but powerful PHP API for building dynamic, render-on-the-fly Gutenberg blocks. Adiós JavaScript nonsense.

<div align="center">

[![HyperPress for WordPress Demo](https://img.youtube.com/vi/6mrRA5QIcRw/0.jpg)](https://www.youtube.com/watch?v=6mrRA5QIcRw "HyperPress for WordPress Demo")

<small>

[Watch a quick demo of HyperPress in action.](https://www.youtube.com/watch?v=6mrRA5QIcRw)

</small>

</div>

---

## The HyperPress Advantage: Modern UIs, Classic Simplicity

In standard modern WordPress development, creating dynamic user interfaces often requires complex JavaScript tooling: Node.js, npm, bundlers (like Vite or Webpack), and extensive knowledge of a framework like React. This introduces a heavy build step, increases complexity, and moves development away from the PHP-centric simplicity that WordPress is known for.

**HyperPress eliminates this complexity.**

It empowers you to build rich, interactive experiences—including SPA-like behavior, partial page updates, and dynamic Gutenberg blocks—using the skills you already have.

### Why Choose HyperPress?

* **🚀 Drastically Faster Workflow**: Skip the JavaScript build process entirely. There's no need to compile assets or manage complex dependencies. Write your logic in PHP and render dynamic HTML directly.
* **🧠 Simplified Development**: Build modern user experiences without writing complex client-side JavaScript. Leverage the simple, attribute-based syntax of HTMX to handle AJAX, WebSockets, SSE and more.
* **💪 PHP-First Gutenberg Blocks**: Create dynamic and interactive Gutenberg blocks using only PHP. Avoid the steep learning curve and cumbersome boilerplate of the standard React-based block development.
* **⚡️ Lightweight & High-Performance**: By sending lean HTML fragments from the server instead of large JSON payloads, you create faster, more responsive user experiences with a minimal client-side footprint.

Hypermedia is a powerful approach for building the vast majority of modern web applications without the overhead of a full frontend framework. For a deeper dive into this philosophy, this video provides an excellent explanation:

<div align="center">

[![You don't need a frontend framework by Andrew Schmelyun](https://img.youtube.com/vi/Fuz-jLIo2g8/0.jpg)](https://www.youtube.com/watch?v=Fuz-jLIo2g8)

</div>

## Why mix it with WordPress?

Because I share the same sentiment as Carson Gross, the creator of HTMX, that the software stack used to build the web today has become too complex without good reason (most of the time). And, just like him, I also want to see the world burn.

(Seriously) Because Hypermedia is awesome, and WordPress is awesome (sometimes). So, why not?

I'm using this in production for a few projects, and it's working great, stable, and ready to use. So, I decided to share it with the world.

## Documentation

See the Documentation Index: [docs/index.md](./docs/index.md)

## Suggestions, Support

Please, open [a discussion](https://github.com/EstebanForge/HyperPress/discussions).

## Bugs and Error reporting

Please, open [an issue](https://github.com/EstebanForge/HyperPress/issues).

## FAQ
[FAQ available here](./docs/faq.md).

## Changelog

[Changelog available here](./CHANGELOG.md).

## Contributing

You are welcome to contribute to this plugin.

If you have a feature request or a bug report, please open an issue on the [GitHub repository](https://github.com/EstebanForge/HyperPress/issues).

If you want to contribute with code, please open a pull request.

## License

This plugin is licensed under the GPLv2 or later.

You can find the full license text in the `LICENSE` file.
