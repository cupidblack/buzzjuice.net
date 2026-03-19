<?php
// Blue Crown R&D — Buzzjuice Network Footer Menu (WoWonder + cross-platform)
// This file now references the unified stylesheet (footer-menu.css).

$buzz_user_logged_in = 0;
// WordPress
if (function_exists('is_user_logged_in') && is_user_logged_in()) {
    $buzz_user_logged_in = 1;
}
// WoWonder / QuickDate server cookie patterns
elseif (!empty($_COOKIE['user_id']) || !empty($_COOKIE['wo_user_id']) || !empty($_COOKIE['qd_user_id']) || !empty($_COOKIE['buzz_auth'])) {
    $buzz_user_logged_in = 1;
}
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<!-- Unified footer-menu stylesheet (shared) -->
<link rel="stylesheet" href="https://buzzjuice.net/shared/footer-menu/footer-menu.css">

<!-- Floating Chat FAB -->
<div id="buzzjuice-floating-chat-left" role="button" aria-label="Open chat" tabindex="0" data-href="/chat">
    <span class="buzz-chat-icon" aria-hidden="true">
        <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 576 512" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
            <path d="M416 192c0-88.4-93.1-160-208-160S0 103.6 0 192c0 34.3 14.1 65.9 38 92-13.4 30.2-35.5 54.2-35.8 54.5-2.2 2.3-2.8 5.7-1.5 8.7S4.8 352 8 352c36.6 0 66.9-12.3 88.7-25 32.2 15.7 70.3 25 111.3 25 114.9 0 208-71.6 208-160zm122 220c23.9-26 38-57.7 38-92 0-66.9-53.5-124.2-129.3-148.1.9 6.6 1.3 13.3 1.3 20.1 0 105.9-107.7 192-240 192-10.8 0-21.3-.8-31.7-1.9C207.8 439.6 281.8 480 368 480c41 0 79.1-9.2 111.3-25 21.8 12.7 52.1 25 88.7 25 3.2 0 6.1-1.9 7.3-4.8 1.3-2.9.7-6.3-1.5-8.7-.3-.3-22.4-24.2-35.8-54.5z"></path>
        </svg>
    </span>
    <span class="buzz-chat-unread no-count" aria-live="polite" aria-atomic="true">0</span>
</div>

<!-- Bottom Footer Menu -->
<div class="bottom-footer-menu" role="navigation" aria-label="Bottom navigation">
    <ul>
        <li><a href="/streams/products" aria-label="Catalog"><i class="fa fa-th-large" aria-hidden="true"></i><span>Catalog</span></a></li>
        <li><a href="/streams" aria-label="Streams"><i class="fa fa-image" aria-hidden="true"></i><span>Streams</span></a></li>
        <li><a href="/play" aria-label="Play"><i class="fa fa-gamepad" aria-hidden="true"></i><span>Play</span></a></li>

        <li>
            <a href="javascript:void(0);" id="buzz-home-button" data-buzz-create-trigger aria-label="Create" role="button" tabindex="0">
                <i class="fa fa-angle-double-up" aria-hidden="true"></i><span>Menu</span>
            </a>
        </li>

        <li><a href="/courses" aria-label="Courses"><i class="fa fa-leanpub" aria-hidden="true"></i><span>Courses</span></a></li>
        <li><a href="/streams/directory/pages" aria-label="Collabos"><i class="fa fa-handshake-o" aria-hidden="true"></i><span>Collabos</span></a></li>
        <li><a href="/social" aria-label="Social"><i class="fa fa-users" aria-hidden="true"></i><span>Social</span></a></li>
    </ul>
</div>

<!-- Floating popup (used on WP / QuickDate / fallback) -->
<div id="buzz-create-overlay" aria-hidden="true"></div>

<div id="buzz-create-popup" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="buzz-create-popup-title">
    <button class="buzz-popup-close" aria-label="Close" title="Close">&times;</button>

    <div data-auth="guest" id="buzz-create-guest" <?php if ($buzz_user_logged_in) echo 'hidden'; ?>>
        <div style="padding:10px;text-align:center;color:#444;">Please Log in or Sign up to create content</div>
        <div class="buzz-auth">
            <a href="/login" class="login">Log in</a>
            <a href="/signup" class="signup">Sign up</a>
        </div>
    </div>

    <div class="buzz-popup-list" data-auth="user" id="buzz-create-user" <?php if (!$buzz_user_logged_in) echo 'hidden'; ?>>
        <a href="/" class="buzz-create-link"><i class="fa fa-home"></i><span><strong>Home</strong></span></a>
        <a href="/streams/create-blog/" class="buzz-create-link"><i class="fa fa-pencil"></i><span>Create Blog</span></a>
        <a href="/streams/create-album" class="buzz-create-link"><i class="fa fa-image"></i><span>Create Album</span></a>
        <a href="/streams/my-products" class="buzz-create-link"><i class="fa fa-shopping-bag"></i><span>Create Product</span></a>
        <a href="/streams/ads/create/" class="buzz-create-link"><i class="fa fa-bullhorn"></i><span>Create Ad</span></a>
        <a href="/streams/create-group" class="buzz-create-link"><i class="fa fa-users"></i><span>Create Group</span></a>
        <a href="/streams/events/create-event/" class="buzz-create-link"><i class="fa fa-calendar"></i><span>Create Event</span></a>
        <a href="/streams/create-page" class="buzz-create-link"><i class="fa fa-handshake-o"></i><span>Start a Collabo</span></a>
    </div>

    <div class="buzz-popup-arrow" aria-hidden="true"></div>
</div>

<!-- WoWonder-style Bootstrap modal (used on WoWonder when detected) -->
<div class="modal fade buzz-create-modal" id="buzz-create-modal" tabindex="-1" role="dialog" aria-labelledby="buzz-create-popup-title" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <button type="button" class="buzz-create-close" aria-label="Close" onclick="jQuery('#buzz-create-modal').modal('hide')">&times;</button>
      <div class="modal-body">
        <div data-auth="guest" id="buzz-create-guest-modal" <?php if ($buzz_user_logged_in) echo 'hidden'; ?>>
          <div style="padding:10px;text-align:center;color:#444;">Please Log in or Sign up</div>
          <div class="buzz-auth" style="padding:6px;">
            <a href="/login" class="login">Log in</a>
            <a href="/signup" class="signup">Sign up</a>
          </div>
        </div>

        <div class="buzz-create-list" data-auth="user" id="buzz-create-user-modal" <?php if (!$buzz_user_logged_in) echo 'hidden'; ?>>
            <a href="/" class="buzz-create-link"><i class="fa fa-home"></i><span><strong>Home</strong></span></a>
            <hr />
          <a href="/streams/create-blog/" class="buzz-create-link"><i class="fa fa-pencil"></i><span>Create Blog</span></a>
          <a href="/streams/create-album" class="buzz-create-link"><i class="fa fa-image"></i><span>Create Album</span></a>
          <a href="/streams/my-products" class="buzz-create-link"><i class="fa fa-shopping-bag"></i><span>Create Product</span></a>
          <a href="/streams/ads/create/" class="buzz-create-link"><i class="fa fa-bullhorn"></i><span>Create Ad</span></a>
          <a href="/streams/create-group" class="buzz-create-link"><i class="fa fa-users"></i><span>Create Group</span></a>
          <a href="/streams/events/create-event/" class="buzz-create-link"><i class="fa fa-calendar"></i><span>Create Event</span></a>
          <a href="/streams/create-page" class="buzz-create-link"><i class="fa fa-handshake-o"></i><span>Start a Collabo</span></a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
/* (JS preserved from existing ww-footer-menu.php) */
/* ... the script remains identical to your previous ww-footer-menu.php (delegated capture handler,
   modal fallback and floating popup logic, mutation observer, auth checks). */

(function(){
    'use strict';

    window.buzzServerLoggedIn = <?php echo $buzz_user_logged_in ? 'true' : 'false'; ?>;

    function isWordPress() {
        try {
            var cls = document.body && document.body.className || '';
            return !!(window.wp || cls.indexOf('wp-') !== -1 || cls.indexOf('bb-') !== -1 || cls.indexOf('buddypress') !== -1);
        } catch(e) { return false; }
    }
    function isUserLoggedInClient() {
        if (window.buzzServerLoggedIn) return true;
        try {
            var c = document.cookie || '';
            return /wordpress_logged_in_/.test(c) || c.indexOf('user_id=') !== -1 || c.indexOf('wo_user_id=') !== -1 || c.indexOf('qd_user_id=') !== -1 || c.indexOf('buzz_auth=') !== -1;
        } catch(e) { return false; }
    }

    function isWoWonderPlatform() {
        try {
            if (typeof window.Wo_OpenRequestsMenu === 'function' || typeof window.Wo_OpenNotificationsMenu === 'function') return true;
            if (document.querySelector && document.querySelector('[onclick*="Wo_"]')) return true;
            var cls = document.body && document.body.className || '';
            if (cls.toLowerCase().indexOf('wowonder') !== -1 || cls.toLowerCase().indexOf('wow') !== -1) return true;
        } catch(e){}
        return false;
    }

    var popup = document.getElementById('buzz-create-popup');
    var overlay = document.getElementById('buzz-create-overlay');
    var homeButton = document.getElementById('buzz-home-button');

    function ensureInBody(id) {
        try {
            var el = document.getElementById(id);
            if (el && el.parentNode !== document.body) document.body.appendChild(el);
            return el;
        } catch(e){ return null; }
    }
    ensureInBody('buzz-create-popup');
    ensureInBody('buzz-create-overlay');
    ensureInBody('buzz-create-modal');

    function revealAuthBlocks(rootSelector) {
        var logged = isUserLoggedInClient();
        var user = document.querySelector(rootSelector + ' [data-auth="user"]') || document.querySelector('#buzz-create-user');
        var guest = document.querySelector(rootSelector + ' [data-auth="guest"]') || document.querySelector('#buzz-create-guest');
        if (!user || !guest) {
            user = document.getElementById('buzz-create-user');
            guest = document.getElementById('buzz-create-guest');
        }
        if (window.buzzServerLoggedIn || logged) {
            if (user) user.hidden = false;
            if (guest) guest.hidden = true;
        } else {
            if (user) user.hidden = true;
            if (guest) guest.hidden = false;
        }
    }

    window.Wo_ShowCreateModal = function(ev) {
        try { if (ev && typeof ev.preventDefault === 'function') ev.preventDefault(); } catch(e){}
        revealAuthBlocks('#buzz-create-modal');
        if (window.jQuery && typeof jQuery('#buzz-create-modal').modal === 'function') {
            jQuery('#buzz-create-modal').modal('show');
            return;
        }
        var modal = document.getElementById('buzz-create-modal');
        if (!modal) return;
        modal.style.display = 'block';
        modal.classList.add('in');
    };

    function bindModalLinks() {
        if (window.jQuery) {
            jQuery(document).on('click', '#buzz-create-modal a', function(){
                jQuery('#buzz-create-modal').modal('hide');
            });
        } else {
            document.addEventListener('click', function(e){
                var a = e.target && e.target.closest && e.target.closest('#buzz-create-modal a');
                if (a) {
                    var modal = document.getElementById('buzz-create-modal');
                    if (modal) {
                        modal.style.display = 'none';
                        modal.classList.remove('in');
                    }
                }
            }, false);
        }
    }
    bindModalLinks();

    var overlayClickHandler = null;
    var docKeyHandler = null;

    function openPopupFloating() {
        if (!popup || !homeButton || !overlay) return;
        revealAuthBlocks('#buzz-create-popup');
        popup.style.display = 'block';
        popup.classList.remove('scrollable');
        popup.style.overflowY = 'visible';
        popup.style.maxHeight = '';

        var winW = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
        var winH = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);
        var pad = 8;
        var maxPopupW = 292;
        var isSmall = winW <= 420;

        if (isSmall) {
            popup.style.width = 'calc(100% - 16px)';
            popup.style.left = '8px';
            popup.style.right = '8px';
            popup.style.transform = 'none';
            popup.style.maxHeight = (winH - 80) + 'px';
            popup.style.overflowY = 'auto';
            popup.classList.add('scrollable');
            var arrow = popup.querySelector('.buzz-popup-arrow');
            if (arrow) arrow.style.display = 'none';
        } else {
            var homeRect = homeButton.getBoundingClientRect();
            var centerX = homeRect.left + (homeRect.width / 2);
            var popupW = Math.min(maxPopupW, winW - pad*2);
            popup.style.width = popupW + 'px';

            var left = Math.round(centerX - (popupW / 2));
            if (left < pad) left = pad;
            if ((left + popupW + pad) > winW) left = winW - popupW - pad;
            popup.style.left = left + 'px';
            popup.style.right = 'auto';
            popup.style.transform = 'translateY(0)';

            var availableAbove = Math.max(homeRect.top - 16, 80);
            var maxAllowedHeight = Math.max(80, availableAbove - 12);
            var needed = popup.scrollHeight || popup.offsetHeight || 0;
            if (needed > maxAllowedHeight) {
                popup.classList.add('scrollable');
                popup.style.maxHeight = maxAllowedHeight + 'px';
                popup.style.overflowY = 'auto';
            } else {
                popup.classList.remove('scrollable');
                popup.style.maxHeight = '';
                popup.style.overflowY = 'visible';
            }

            var arrow = popup.querySelector('.buzz-popup-arrow');
            if (arrow) {
                arrow.style.display = 'block';
                var arrowLeft = Math.round(centerX - left);
                var minA = 12, maxA = popupW - 12;
                if (arrowLeft < minA) arrowLeft = minA;
                if (arrowLeft > maxA) arrowLeft = maxA;
                arrow.style.left = arrowLeft + 'px';
            }
        }

        overlay.style.display = 'block';
        overlay.setAttribute('aria-hidden', 'false');
        setTimeout(function(){
            overlayClickHandler = function(){ closePopupFloating(); };
            overlay.addEventListener('click', overlayClickHandler, false);
            docKeyHandler = function(e){ if (e.key === 'Escape') closePopupFloating(); };
            document.addEventListener('keydown', docKeyHandler, true);
        }, 10);

        popup.classList.add('open');
        popup.setAttribute('aria-hidden', 'false');

        var first = popup.querySelector('a, button');
        if (first) try { first.focus(); } catch(e){}
    }

    function closePopupFloating() {
        if (!popup) return;
        popup.classList.remove('open');
        popup.setAttribute('aria-hidden', 'true');
        setTimeout(function(){ if (!popup.classList.contains('open')) popup.style.display = 'none'; }, 160);

        overlay.style.display = 'none';
        overlay.setAttribute('aria-hidden', 'true');

        if (overlayClickHandler) {
            overlay.removeEventListener('click', overlayClickHandler, false);
            overlayClickHandler = null;
        }
        if (docKeyHandler) {
            document.removeEventListener('keydown', docKeyHandler, true);
            docKeyHandler = null;
        }
        try { if (homeButton) homeButton.focus(); } catch(e){}
    }

    function handleCreateTrigger(e) {
        if (isWoWonderPlatform()) {
            window.Wo_ShowCreateModal(e);
            return;
        } else {
            try { if (e && typeof e.preventDefault === 'function') e.preventDefault(); } catch(e){}
            if (popup && popup.classList.contains('open')) closePopupFloating(); else openPopupFloating();
        }
    }

    function delegatedCaptureHandler(e) {
        var t = e.target;
        var trigger = t.closest && (t.closest('#buzz-home-button') || t.closest('[data-buzz-create-trigger]') || t.closest('a[aria-label="Create"]'));
        if (!trigger) return;
        try { e.preventDefault(); } catch(err){}
        try { e.stopImmediatePropagation(); } catch(err2){ try { e.stopPropagation(); } catch(ignore){} }
        handleCreateTrigger(e);
    }

    function bindPopupInternal() {
        if (!popup) return;
        popup.addEventListener('click', function(e){
            var a = e.target && e.target.closest && e.target.closest('a');
            if (a && a.href) {
                closePopupFloating();
            }
        }, false);
        var closeBtn = popup.querySelector('.buzz-popup-close');
        if (closeBtn) closeBtn.addEventListener('click', function(e){ e.preventDefault(); closePopupFloating(); }, false);
    }

    function initMutationObserver() {
        if (typeof MutationObserver === 'undefined') return;
        try {
            var mo = new MutationObserver(function(){
                ensureInBody('buzz-create-popup');
                ensureInBody('buzz-create-overlay');
                ensureInBody('buzz-create-modal');

                var hb = document.getElementById('buzz-home-button');
                if (hb && hb !== homeButton) {
                    homeButton = hb;
                }
            });
            mo.observe(document.body, { childList: true, subtree: true });
        } catch(e){}
    }

    function init() {
        popup = ensureInBody('buzz-create-popup') || document.getElementById('buzz-create-popup');
        overlay = ensureInBody('buzz-create-overlay') || document.getElementById('buzz-create-overlay');
        homeButton = document.getElementById('buzz-home-button');

        try {
            var fab = document.getElementById('buzzjuice-floating-chat-left');
            if (fab) {
                if (!isWordPress()) fab.style.display = 'inline-flex';
                else fab.style.display = 'none';
                fab.addEventListener('click', function(e){ e.preventDefault(); var href = fab.getAttribute('data-href') || '/chat'; window.location.href = href; }, false);
            }
        } catch(e){}

        document.addEventListener('click', delegatedCaptureHandler, true);
        document.addEventListener('pointerdown', delegatedCaptureHandler, true);
        document.addEventListener('touchstart', delegatedCaptureHandler, true);

        bindPopupInternal();
        initMutationObserver();

        window.addEventListener('resize', function(){ 
            if (popup && popup.classList.contains('open') && !isWoWonderPlatform()) openPopupFloating(); 
        });
        window.addEventListener('orientationchange', function(){ setTimeout(function(){ if (popup && popup.classList.contains('open') && !isWoWonderPlatform()) openPopupFloating(); }, 120); });

        revealAuthBlocks('#buzz-create-popup');
        revealAuthBlocks('#buzz-create-modal');
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init); else init();

})();
</script>

<?php
// End footer-menu.php
?>