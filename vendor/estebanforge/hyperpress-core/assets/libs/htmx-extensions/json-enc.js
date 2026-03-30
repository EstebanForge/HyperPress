/**
 * Minified by jsDelivr using Terser v5.39.0.
 * Original file: /npm/htmx-ext-json-enc@2.0.3/json-enc.js
 *
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
!function(){let n;htmx.defineExtension("json-enc",{init:function(e){n=e},onEvent:function(n,e){"htmx:configRequest"===n&&(e.detail.headers["Content-Type"]="application/json")},encodeParameters:function(e,t,o){e.overrideMimeType("text/json");const i={};t.forEach((function(n,e){Object.hasOwn(i,e)?(Array.isArray(i[e])||(i[e]=[i[e]]),i[e].push(n)):i[e]=n}));const s=n.getExpressionVars(o);return Object.keys(i).forEach((function(n){i[n]=Object.hasOwn(s,n)?s[n]:i[n]})),JSON.stringify(i)}})}();
//# sourceMappingURL=/sm/b8de7541f9a9441388a80611ad8e4d48fb8b5bdd8ff94a29fa35571e58740232.map