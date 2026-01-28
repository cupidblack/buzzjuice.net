/* Delegated handlers for post action buttons.
	 This file calls the existing Wo_* functions when available and provides
	 defensive fallbacks so clicks on SVGs or nested nodes still trigger the action.
*/
(function () {
	'use strict';

	function safeInt(v) {
		var n = parseInt(v, 10);
		return isNaN(n) ? 0 : n;
	}

		document.addEventListener('click', function (ev) {
		var btn;

		// Open delete modal
		btn = ev.target.closest && ev.target.closest('.wo-open-post-delete');
		if (btn) {
			ev.preventDefault();
			var postId = safeInt(btn.getAttribute('data-post-id') || btn.dataset.postId);
			if (typeof Wo_OpenPostDeleteBox === 'function') {
				try { Wo_OpenPostDeleteBox(postId); } catch (e) { console.error('Wo_OpenPostDeleteBox error', e); }
			} else {
				// fallback: try to open #delete-post modal and set a hidden field if present
				var modal = document.getElementById('delete-post');
				if (modal) {
					var input = modal.querySelector('input[name="post_id"], input#post_id');
					if (input) { input.value = postId; }
					try { if (window.jQuery) { jQuery(modal).modal('show'); } }
					catch (e) { console.warn('Could not show delete modal', e); }
				} else {
					console.warn('No Wo_OpenPostDeleteBox and no #delete-post modal found for delete fallback.');
				}
			}
			return;
		}

		// Toggle comments
		btn = ev.target.closest && ev.target.closest('.wo-toggle-comments');
		if (btn) {
			ev.preventDefault();
			var postId = safeInt(btn.getAttribute('data-post-id') || btn.dataset.postId);
			var status = safeInt(btn.getAttribute('data-comments-status') || btn.dataset.commentsStatus);
			if (typeof Wo_DisableComment === 'function') {
				try { Wo_DisableComment(postId, status); } catch (e) { console.error('Wo_DisableComment threw', e); }
			} else {
				console.warn('Wo_DisableComment not defined for post', postId);
			}
			return;
		}

		// Pin post
		btn = ev.target.closest && ev.target.closest('.pin-post');
		if (btn) {
			ev.preventDefault();
			var postId = safeInt(btn.getAttribute('data-post-id') || btn.dataset.postId);
			var pinTarget = safeInt(btn.getAttribute('data-pin-target') || btn.dataset.pinTarget || 0);
			var pinText = btn.getAttribute('data-pin-text') || btn.dataset.pinText || '';
			if (typeof Wo_PinPost === 'function') {
				try { Wo_PinPost(postId, pinTarget, pinText); } catch (e) { console.error('Wo_PinPost threw', e); }
			} else {
				console.warn('Wo_PinPost not defined for post', postId);
			}
			return;
		}

		// Boost post
		btn = ev.target.closest && ev.target.closest('.boost-post');
		if (btn) {
			ev.preventDefault();
			var postId = safeInt(btn.getAttribute('data-post-id') || btn.dataset.postId);
			if (typeof Wo_BoostPost === 'function') {
				try { Wo_BoostPost(postId); } catch (e) { console.error('Wo_BoostPost threw', e); }
			} else {
				console.warn('Wo_BoostPost not defined for post', postId);
			}
			return;
		}

	}, false);

})();

	// Defensive enhancement: mark existing inline onclick elements with useful classes/data-attributes
	// so they work with the delegated handlers above (non-invasive).
	(function () {
		if (!document.querySelector) { return; }

		function addClassAndData(el, cls, data) {
			if (!el.classList.contains(cls)) { el.classList.add(cls); }
			Object.keys(data).forEach(function (k) { el.setAttribute('data-' + k, data[k]); });
		}

		// Helper to extract first integer in a string
		function extractInt(s) {
			var m = (s || '').match(/(\d+)/);
			return m ? parseInt(m[1], 10) : 0;
		}

		// Map inline onclick snippets to classes/data
		var mappings = [
			{ re: /Wo_OpenPostDeleteBox\(/, cls: 'wo-open-post-delete', key: 'post-id' },
			{ re: /Wo_DisableComment\(/, cls: 'wo-toggle-comments', key: 'post-id' },
			{ re: /Wo_PinPost\(/, cls: 'pin-post', key: 'post-id' },
			{ re: /Wo_BoostPost\(/, cls: 'boost-post', key: 'post-id' }
		];

		Array.prototype.slice.call(document.querySelectorAll('[onclick]')).forEach(function (el) {
			var onclick = el.getAttribute('onclick');
			if (!onclick) { return; }
			mappings.forEach(function (m) {
				if (m.re.test(onclick)) {
					var id = extractInt(onclick);
					var data = {};
					data[m.key] = id;
					addClassAndData(el, m.cls, data);
					// For disable comments, try to capture the second numeric arg as comments-status
					if (m.cls === 'wo-toggle-comments') {
						var parts = onclick.split(/Wo_DisableComment\s*\(/)[1] || '';
						var nums = (parts.match(/(\d+)/g) || []);
						if (nums.length >= 2) { el.setAttribute('data-comments-status', nums[1]); }
					}
				}
			});
		});

	})();
