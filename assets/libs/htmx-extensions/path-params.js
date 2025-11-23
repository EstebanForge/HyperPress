/**
 * Minified by jsDelivr using Terser v5.39.0.
 * Original file: /npm/htmx-ext-path-params@2.0.2/dist/path-params.esm.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import htmx from"htmx.org";htmx.defineExtension("path-params",{onEvent:function(e,t){"htmx:configRequest"===e&&(t.detail.path=t.detail.path.replace(/{([^}]+)}/g,(function(e,a){var n=t.detail.parameters[a];return delete t.detail.parameters[a],void 0===n?"{"+a+"}":encodeURIComponent(n)})))}});
//# sourceMappingURL=/sm/b130af9ff78c90abcdcb74a7587323fee1b61bb941ba9ba7aa97668b4f3cdccb.map