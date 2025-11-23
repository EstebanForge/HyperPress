/**
 * Minified by jsDelivr using Terser v5.39.0.
 * Original file: /npm/htmx-ext-path-deps@2.0.2/path-deps.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
(function(){"use strict";function t(t,e){if("ignore"===t)return!1;for(var n=t.split("/"),r=e.split("/"),i=0;i<r.length;i++){var h=n.shift();if(h!==r[i]&&"*"!==h)return!1;if(0===n.length||1===n.length&&""===n[0])return!0}return!1}function e(e){for(var n=htmx.findAll("[path-deps]"),r=0;r<n.length;r++){var i=n[r];t(i.getAttribute("path-deps"),e)&&htmx.trigger(i,"path-deps")}}htmx.defineExtension("path-deps",{onEvent:function(t,n){if("htmx:beforeOnLoad"===t){var r=n.detail.requestConfig;"get"!==r.verb&&"ignore"!==n.target.getAttribute("path-deps")&&e(r.path)}}}),this.PathDeps={refresh:function(t){e(t)}}}).call(this);
//# sourceMappingURL=/sm/0237ca1d0374c416f4618bd2e2f356fb930bc86cb204097fff7554f1bcb7b258.map