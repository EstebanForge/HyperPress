/**
 * Minified by jsDelivr using Terser v5.39.0.
 * Original file: /npm/htmx-ext-restored@2.0.1/dist/restored.esm.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import htmx from"htmx.org";htmx.defineExtension("restored",{onEvent:function(e,r){if("htmx:restored"===e){var t=r.detail.document.querySelectorAll("[hx-trigger='restored'],[data-hx-trigger='restored']"),o=Array.from(t).find((e=>e.outerHTML===r.detail.elt.outerHTML));r.detail.triggerEvent(o,"restored")}}});
//# sourceMappingURL=/sm/003df42b50b3829b42663fc17e9d50486fc1bfcbb15120c9ef093dd8b33bbbc2.map