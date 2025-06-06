/**
 * Minified by jsDelivr using Terser v5.39.0.
 * Original file: /npm/htmx-ext-disable-element@2.0.1/disable-element.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
!function(){"use strict";htmx.defineExtension("disable-element",{onEvent:function(e,t){const n=t.detail.elt,l=n.getAttribute("hx-disable-element"),i="self"==l?[n]:document.querySelectorAll(l);for(var s=0;s<i.length;s++)"htmx:beforeRequest"===e&&i[s]?i[s].disabled=!0:"htmx:afterRequest"==e&&i[s]&&(i[s].disabled=!1)}})}();
//# sourceMappingURL=/sm/add5dcccbe2ad0c28da18bdf720dbda3befd8a37c68b62371fc60040ca40e082.map