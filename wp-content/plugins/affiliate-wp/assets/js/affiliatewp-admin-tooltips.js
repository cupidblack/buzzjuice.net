/**
 * AffiliateWP Admin Tooltips
 *
 * Unified tooltip system using Tippy.js with Tailwind CSS styling.
 * Simply add data-tooltip-html attribute to any element.
 *
 * @since 2.29.0
 */

/* eslint-disable wrap-iife */
/* global tippy */
(function () {
	"use strict";

	// Configuration
	const CONFIG = {
		// Main selector for tooltip elements
		selector: "[data-tooltip-html]:not([data-tippy-processed])",

		// Legacy selector for backwards compatibility
		legacySelector: "[data-affwp-tooltip]:not([data-tippy-processed])",

		// Default Tippy.js options
		tippyDefaults: {
			theme: "affiliatewp",
			appendTo: () => document.body,
			interactive: true,
			maxWidth: 300,
			arrow: true, // Will be replaced with tippy.roundArrow at runtime
			animation: "scale-subtle",
			duration: [250, 200],
			trigger: "mouseenter focus",
			hideOnClick: true,
		},
	};

	// Store active instances for cleanup
	const state = {
		instances: [],
		initialized: false,
	};

	/**
	 * Initialize the tooltip system
	 * @since 2.29.0
	 */
	function init() {
		// Prevent double initialization
		if (
			state.initialized &&
			document.querySelectorAll("[data-tippy-processed]").length > 0
		) {
			return;
		}

		// Set global Tippy defaults to prevent CSS injection
		if (typeof tippy !== "undefined") {
			tippy.setDefaultProps({
				appendTo: () => document.body,
				theme: "none",
			});
		}

		// Clean up any existing tooltips
		destroy();

		// Initialize tooltips
		initTooltips();

		state.initialized = true;
	}

	/**
	 * Initialize tooltips on elements with data-tooltip-html
	 * @since 2.29.0
	 */
	function initTooltips() {
		// Process main selector
		document.querySelectorAll(CONFIG.selector).forEach((element) => {
			const content = element.getAttribute("data-tooltip-html");
			if (!content) {
				return;
			}

			createTooltipInstance(element, content);
		});

		// Process legacy selector for backwards compatibility
		document.querySelectorAll(CONFIG.legacySelector).forEach((element) => {
			const content = element.getAttribute("data-affwp-tooltip");
			if (!content) {
				return;
			}

			// Wrap plain text in basic container if not HTML
			const finalContent = /<[a-z][\s\S]*>/i.test(content)
				? content
				: `<div class="affwp-ui affwp-tooltip-wrapper bg-white rounded-lg shadow-lg border border-gray-200 p-3 min-w-[200px] max-w-[400px]">
					<div class="text-sm text-gray-700">${escapeHtml(content)}</div>
				</div>`;

			createTooltipInstance(element, finalContent);
		});
	}

	/**
	 * Create a Tippy instance for an element
	 * @param {HTMLElement} element - The trigger element
	 * @param {string}      content - The tooltip content
	 * @since 2.29.0
	 */
	function createTooltipInstance(element, content) {
		const placement = element.getAttribute("data-tooltip-placement") || "top";

		const instance = tippy(element, {
			...CONFIG.tippyDefaults,
			arrow: tippy.roundArrow,
			content,
			allowHTML: true,
			placement,
			onShow(instance) {
				// Hide other tooltips to prevent overlap
				state.instances.forEach((tooltip) => {
					if (tooltip !== instance && tooltip.state.isVisible) {
						tooltip.hide();
					}
				});
			},
			onCreate(instance) {
				// Track instance
				state.instances.push(instance);
			},
			onDestroy(instance) {
				// Remove from tracked instances
				const index = state.instances.indexOf(instance);
				if (index > -1) {
					state.instances.splice(index, 1);
				}
			},
		});

		// Mark element as processed
		element.setAttribute("data-tippy-processed", "true");

		return instance;
	}

	/**
	 * Escape HTML for safe display
	 * @param {string} text - Text to escape
	 * @return {string} Escaped text
	 * @since 2.29.0
	 */
	function escapeHtml(text) {
		const div = document.createElement("div");
		div.textContent = text;
		return div.innerHTML;
	}

	/**
	 * Destroy all tooltip instances and clean up
	 * @since 2.29.0
	 */
	function destroy() {
		// Destroy tracked instances
		state.instances.forEach((instance) => {
			if (instance && instance.destroy) {
				instance.destroy();
			}
		});
		state.instances = [];

		// Clean up processed markers
		document.querySelectorAll("[data-tippy-processed]").forEach((element) => {
			if (element._tippy) {
				element._tippy.destroy();
			}
			element.removeAttribute("data-tippy-processed");
		});

		// Remove orphaned tooltip elements
		document.querySelectorAll(".tippy-box").forEach((tooltip) => {
			if (tooltip.parentNode) {
				tooltip.parentNode.removeChild(tooltip);
			}
		});

		state.initialized = false;
	}

	/**
	 * Refresh all tooltips (destroy and reinitialize)
	 * @since 2.29.0
	 */
	function refresh() {
		destroy();
		init();
	}

	/**
	 * Set up event listeners for initialization and updates
	 */
	function setupEventListeners() {
		// Initialize on DOM ready
		if (document.readyState === "loading") {
			document.addEventListener("DOMContentLoaded", init);
		} else {
			init();
		}

		// Reinitialize after AJAX updates
		document.addEventListener("affwp_ajax_loaded", () => {
			setTimeout(refresh, 100);
		});

		// WordPress admin AJAX support
		if (typeof wp !== "undefined" && wp.heartbeat) {
			document.addEventListener("heartbeat-tick", () => {
				setTimeout(refresh, 100);
			});
		}

		// Reinitialize when settings tabs change
		document.addEventListener("click", (e) => {
			const navTabLink = e.target.closest(".affwp-nav-tab-wrapper a");
			if (navTabLink) {
				setTimeout(refresh, 100);
			}
		});

		// Clean up on page unload
		window.addEventListener("beforeunload", destroy);

		// Reinitialize after browser back/forward navigation (bfcache).
		window.addEventListener("pageshow", (event) => {
			if (event.persisted) {
				setTimeout(refresh, 100);
			}
		});
	}

	// Initialize the system
	setupEventListeners();

	// Expose public API
	window.affwpTooltips = {
		init,
		destroy,
		refresh,
		createTooltipInstance,
		getState: () => state,
	};
})();
