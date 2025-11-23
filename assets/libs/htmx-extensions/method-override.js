/**
 * Minified by jsDelivr using Terser v5.39.0.
 * Original file: /npm/htmx-ext-method-override@2.0.3/dist/method-override.esm.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import htmx from"htmx.org";htmx.defineExtension("method-override",{onEvent:function(e,t){if("htmx:configRequest"===e){var o=t.detail.verb;"get"!==o&&"post"!==o&&(t.detail.headers["X-HTTP-Method-Override"]=o.toUpperCase(),t.detail.verb="post")}}});
//# sourceMappingURL=/sm/9b1413300cd2a2f6a137102e3885260b6b96308d137f821574b8fe3b7e2f3a01.map