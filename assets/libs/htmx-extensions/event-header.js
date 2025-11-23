/**
 * Minified by jsDelivr using Terser v5.39.0.
 * Original file: /npm/htmx-ext-event-header@2.0.2/dist/event-header.esm.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import htmx from"htmx.org";htmx.defineExtension("event-header",{onEvent:function(e,t){"htmx:configRequest"===e&&t.detail.triggeringEvent&&(t.detail.headers["Triggering-Event"]=function(e){var t={};for(var n in e)t[n]=e[n];return JSON.stringify(t,(function(e,t){if(t instanceof Node){var n=t.tagName;return n?(n=n.toLowerCase(),t.id&&(n+="#"+t.id),t.classList&&t.classList.length&&(n+="."+t.classList.toString().replace(" ",".")),n):"Node"}return t instanceof Window?"Window":t}))}(t.detail.triggeringEvent))}});
//# sourceMappingURL=/sm/5b737cdcb145da6f036f0a6544b577ceffd345bf56db6d275a5ca4a06a070c55.map