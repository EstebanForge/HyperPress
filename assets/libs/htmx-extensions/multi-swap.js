/**
 * Minified by jsDelivr using Terser v5.39.0.
 * Original file: /npm/htmx-ext-multi-swap@2.0.2/multi-swap.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
!function(){var n;htmx.defineExtension("multi-swap",{init:function(t){n=t},isInlineSwap:function(n){return 0===n.indexOf("multi:")},handleSwap:function(t,e,i,r){if(0===t.indexOf("multi:")){var o={},s=t.replace(/^multi\s*:\s*/,"").split(/\s*,\s*/);for(var u in s.forEach((function(n){var t=n.split(/\s*:\s*/),e=t[0],i=void 0!==t[1]?t[1]:"innerHTML";"#"===e.charAt(0)?o[e]=i:console.error("HTMX multi-swap: unsupported selector '"+e+"'. Only ID selectors starting with '#' are supported.")})),o){t=o[u];var a=i.querySelector(u);a?n.oobSwap(t,a,r):console.warn("HTMX multi-swap: selector '"+u+"' not found in source content.")}return!0}}})}();
//# sourceMappingURL=/sm/7a685543f08556b9554c2282973a2cb7e9ba5927747b1c6e1a1b785939b3d879.map