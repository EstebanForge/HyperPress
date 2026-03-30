/**
 * Minified by jsDelivr using Terser v5.39.0.
 * Original file: /npm/htmx-ext-include-vals@2.0.2/dist/include-vals.esm.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
import htmx from"htmx.org";(function(){function mergeObjects(e,t){for(var l in t)t.hasOwnProperty(l)&&(e[l]=t[l]);return e}htmx.defineExtension("include-vals",{onEvent:function(name,evt){if("htmx:configRequest"===name){var includeValsElt=htmx.closest(evt.detail.elt,"[include-vals],[data-include-vals]");if(includeValsElt){var includeVals=includeValsElt.getAttribute("include-vals")||includeValsElt.getAttribute("data-include-vals"),valuesToInclude=eval("({"+includeVals+"})");mergeObjects(evt.detail.parameters,valuesToInclude)}}}})})();
//# sourceMappingURL=/sm/b2cd017ab1095df071c76dcf53b78a24645f1008c0ab0a9c639b7c3670ddf1af.map