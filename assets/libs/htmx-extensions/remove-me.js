/**
 * Minified by jsDelivr using Terser v5.37.0.
 * Original file: /npm/htmx-ext-remove-me@2.0.1/remove-me.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
!function(){function e(e){var t=e.getAttribute("remove-me")||e.getAttribute("data-remove-me");t&&setTimeout((function(){e.parentElement.removeChild(e)}),htmx.parseInterval(t))}htmx.defineExtension("remove-me",{onEvent:function(t,r){if("htmx:afterProcessNode"===t){var o=r.detail.elt;if(o.getAttribute&&(e(o),o.querySelectorAll))for(var n=o.querySelectorAll("[remove-me], [data-remove-me]"),m=0;m<n.length;m++)e(n[m])}}})}();
//# sourceMappingURL=/sm/98f4911eb6de3b71ab2b7c502190cfa2f8ad3eaafdd15c0e66764768ae3a9b55.map