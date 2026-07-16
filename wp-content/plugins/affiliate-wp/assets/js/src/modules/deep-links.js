/**
 * Deep Link Handler for Admin Settings
 *
 * Provides automatic highlighting and scrolling to elements based on URL hash
 * Supports nested panels and automatic accordion/panel opening
 *
 * @since 2.29.0
 */

/* global Alpine, setTimeout */

export function initDeepLinkHandler() {
	// Register the deep link handler as an Alpine store
	document.addEventListener("alpine:init", () => {
		Alpine.store("deepLink", {
			/**
			 * Initialize the deep link handler
			 */
			init() {
				// Check for deep link after Alpine components are ready
				this.checkDeepLink();

				// Listen for hash changes
				window.addEventListener("hashchange", () => this.checkDeepLink());
			},

			/**
			 * Check and process deep link from URL hash
			 */
			checkDeepLink() {
				const hash = window.location.hash.replace("#", "");
				if (!hash) return;

				// Small delay to ensure Alpine components are initialized
				setTimeout(() => {
					this.processDeepLink(hash);
				}, 100);
			},

			/**
			 * Process a deep link target
			 * @param {string} targetId - The ID to target
			 */
			processDeepLink(targetId) {
				// Try to find the target element
				const target = document.getElementById(targetId);

				// Also check for elements with data-deeplink-id attribute (for flexibility)
				const altTarget = document.querySelector(
					`[data-deeplink-id="${targetId}"]`,
				);

				const element = target || altTarget;

				if (!element) {
					// Try with underscores replaced by hyphens (common variation)
					const alternateId = targetId.replace(/_/g, "-");
					const alternateElement = document.getElementById(alternateId);
					if (alternateElement) {
						this.processElement(alternateElement);
					}
					return;
				}

				this.processElement(element);
			},

			/**
			 * Process the found element
			 * @param {HTMLElement} element - The element to process
			 */
			processElement(element) {
				// Open parent panels if the element is hidden
				this.openParentPanels(element);

				// Wait for panels to open
				setTimeout(() => {
					// Scroll to and highlight the element
					this.highlightElement(element);
				}, 350); // Wait for panel animations
			},

			/**
			 * Open any parent collapsible panels
			 * @param {HTMLElement} element - The target element
			 */
			openParentPanels(element) {
				let parent = element.parentElement;

				while (parent && parent !== document.body) {
					// Check for Alpine x-show or x-collapse attributes
					if (
						parent.hasAttribute("x-show") ||
						parent.hasAttribute("x-collapse")
					) {
						// Try to find the Alpine component that controls this panel
						const alpineParent = this.findAlpineParent(parent);
						if (alpineParent) {
							this.openAlpinePanel(alpineParent, parent);
						}
					}

					// Check for payment method cards (special case)
					const paymentCard = parent.closest(".affwp-payment-card");
					if (paymentCard) {
						const methodId = paymentCard.getAttribute("data-method");
						if (methodId) {
							// Open the payment method panel by dispatching event
							const component = Alpine.$data(paymentCard);
							if (component && typeof component.panelOpen !== "undefined") {
								component.panelOpen = true;
							}
						}
					}

					parent = parent.parentElement;
				}
			},

			/**
			 * Find the Alpine component that controls a panel
			 * @param {HTMLElement} panel - The panel element
			 * @returns {HTMLElement|null} The Alpine component element
			 */
			findAlpineParent(panel) {
				let current = panel;
				while (current && current !== document.body) {
					if (current.hasAttribute("x-data")) {
						return current;
					}
					current = current.parentElement;
				}
				return null;
			},

			/**
			 * Open an Alpine-controlled panel
			 * @param {HTMLElement} component - The Alpine component
			 * @param {HTMLElement} panel - The panel to open
			 */
			openAlpinePanel(component, panel) {
				// Get the x-show attribute value
				const showAttribute = panel.getAttribute("x-show");
				if (showAttribute) {
					// Parse the variable name from x-show
					const matches = showAttribute.match(/^(\w+)/);
					if (matches) {
						const varName = matches[1];
						// Set the variable to true using Alpine's reactivity
						const alpineData = Alpine.$data(component);
						if (alpineData && typeof alpineData[varName] !== "undefined") {
							alpineData[varName] = true;
						}
					}
				}
			},

			/**
			 * Highlight an element with visual feedback
			 * @param {HTMLElement} element - The element to highlight
			 */
			highlightElement(element) {
				// Scroll to element
				element.scrollIntoView({
					behavior: "smooth",
					block: "center",
				});

				// Add highlight classes
				const highlightClasses = [
					"ring-2",
					"ring-affwp-brand-500",
					"ring-offset-2",
					"transition-all",
				];

				// Check if element has custom highlight classes
				const customClasses = element.getAttribute("data-deeplink-highlight");
				if (customClasses) {
					element.classList.add(...customClasses.split(" "));
				} else {
					element.classList.add(...highlightClasses);
				}

				// Remove highlight after delay
				const duration = element.getAttribute("data-deeplink-duration") || 2000;
				setTimeout(() => {
					if (customClasses) {
						element.classList.remove(...customClasses.split(" "));
					} else {
						element.classList.remove(...highlightClasses);
					}
				}, parseInt(duration));
			},

			/**
			 * Programmatically navigate to a deep link
			 * @param {string} targetId - The ID to navigate to
			 * @param {boolean} updateUrl - Whether to update the URL hash
			 */
			navigateTo(targetId, updateUrl = true) {
				if (updateUrl) {
					window.location.hash = targetId;
				} else {
					this.processDeepLink(targetId);
				}
			},
		});

		// Auto-initialize after Alpine starts
		Alpine.nextTick(() => {
			Alpine.store("deepLink").init();
		});
	});
}
