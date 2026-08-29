document.addEventListener("DOMContentLoaded", (function() {
	// Auto-submit when the library or the htmx major version changes: the
	// options page rebuilds version-scoped fields on reload. HyperFields
	// renders the DOM id as the bare field name (the name attribute gets the
	// hyperpress_options[...] prefix, the id does not).
	const autoSubmitSelectors = ["#active_library", "#htmx_version"];
	autoSubmitSelectors.forEach((function(selector) {
		const select = document.querySelector(selector);
		if (!select) {
			return;
		}
			select.addEventListener("change", (function() {
				const submitButton = document.querySelector('p.submit input[type="submit"]');
				if (submitButton) {
					const spinner = document.createElement("span");
					spinner.className = "spinner is-active";
					spinner.style.float = "none";
					spinner.style.marginTop = "5px";
					submitButton.parentNode.insertBefore(spinner, submitButton.nextSibling);
				}
				// WP's submit input is named "submit", which shadows
				// form.submit — call through the prototype instead.
				const form = document.querySelector("#hyperpress-options-form");
				if (form) {
					HTMLFormElement.prototype.submit.call(form);
				}
			}));
	}));
	document.querySelectorAll(".copy-api-url").forEach((function(button) {
		button.addEventListener("click", (function() {
			const originalText = this.getAttribute("data-clipboard-text");
			const originalLabel = this.textContent;
			navigator.clipboard.writeText(originalText).then(() => {
				this.textContent = "Copied!";
				this.classList.add("button-primary");
				this.classList.remove("button-secondary");
				setTimeout(() => {
					this.textContent = originalLabel;
					this.classList.add("button-secondary");
					this.classList.remove("button-primary");
				}, 2e3);
			}).catch((error) => {
				console.error("Failed to copy: ", error);
			});
		}));
	}));
}));
