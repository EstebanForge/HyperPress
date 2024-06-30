/**
 * Boost the whole DOM, except for WP's admin bar
 */
document.addEventListener("DOMContentLoaded", function () {
	document.body.setAttribute("hx-boost", "true");
	if (document.getElementById("wpadminbar")) {
		document.getElementById("wpadminbar").setAttribute("hx-boost", "false");
	}
});
