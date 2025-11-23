/**
 * Minified by jsDelivr using Terser v5.39.0.
 * Original file: /npm/htmx-ext-morphdom-swap@2.0.2/dist/morphdom-swap.esm.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import htmx from"htmx.org";htmx.defineExtension("morphdom-swap",{isInlineSwap:function(o){return"morphdom"===o},handleSwap:function(o,m,r){if("morphdom"===o)return r.nodeType===Node.DOCUMENT_FRAGMENT_NODE?(morphdom(m,r.firstElementChild||r.firstChild),[m]):(morphdom(m,r.outerHTML),[m])}});
//# sourceMappingURL=/sm/da5f46ca7a395541ac5bc6b003204aabfc3399af780649a7d29a074a1ce046ac.map