<?php
// Blue Crown R&D — Buzzjuice Network Footer Menu
// Include this file before closing </body> on the hosting surfaces (WoWonder/QuickDate and WordPress).

// Server-side: detect WordPress logged-in status when available.
// If this include runs outside WP context, function_exists('is_user_logged_in') will be false.
$buzz_user_logged_in = (function_exists('is_user_logged_in') && is_user_logged_in()) ? 1 : 0;
?>
<!-- Font Awesome (existing) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<!-- Existing footer-menu stylesheet -->
<link rel="stylesheet" href="https://koware.org/footer-menu/footer-menu.css">

<style>
/* =========================
   Floating chat FAB (unique)
   ========================= */
#buzzjuice-floating-chat-left {
    position: fixed;
    left: 0px;
    bottom: calc(35px + env(safe-area-inset-bottom));
    width: 32px;
    height: 32px;
    background: #63a551;
    color: #fff;
    border-radius: 10px;
    z-index: 10050;
    display: none; /* shown by JS only on non-WordPress pages */
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 6px 18px rgba(0,0,0,0.18);
    -webkit-tap-highlight-color: transparent;
    margin: 0 0 10px !important;
}
#buzzjuice-floating-chat-left svg {
    width: 28px;
    height: 28px;
    display: block;
    color: #fff;
}

/* Unread badge placeholder (hidden if not used) */
#buzzjuice-floating-chat-left .buzz-chat-unread {
    position: absolute;
    top: -6px;
    right: -6px;
    background: #0d6efd;
    color: #fff;
    border-radius: 20%;
    padding: 2px 6px;
    font-size: 11px;
    min-width: 18px;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
}
#buzzjuice-floating-chat-left .buzz-chat-unread.no-count { display: none; }

/* =========================
   Bottom Footer Menu (7 items)
   ========================= */
.bottom-footer-menu ul {
    display: flex;
    justify-content: space-around;
    align-items: center;
    padding: 0;
    margin: 0;
    list-style: none;
}
.bottom-footer-menu ul li a {
    display: flex;
    flex-direction: column;
    align-items: center;
    font-size: 10px;
    color: #333;
    text-decoration: none;
    padding: 6px 4px;
}
.bottom-footer-menu ul li a i {
    font-size: 28px;
    margin-bottom: 3px;
}
.bottom-footer-menu ul li a.active {
    color: #0d6efd;
}
.bottom-footer-menu { z-index: 9999; }

/* =========================
   Create popup (hidden by default)
   Positioned above the footer / home button
   Max width: 292px (as requested)
   ========================= */
#buzz-create-popup {
    position: fixed;
    width: 100%;
    max-width: 256px; /* REQUIRED maximum width */
    background: #fff;
    border-radius: 12px;
    padding: 8px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.18);
    z-index: 11000;
    display: none;
    transform-origin: bottom center;
    transition: transform .14s ease, opacity .14s ease;
    opacity: 0;
    font-size: 14px;
    left: 50%;
    transform: translateX(-50%) translateY(6px) scale(.98);
}

/* visible state */
#buzz-create-popup.open {
    display: block;
    transform: translateX(-50%) translateY(0) scale(1);
    opacity: 1;
}

/* popup arrow pointing down */
#buzz-create-popup .buzz-popup-arrow {
    position: absolute;
    width: 16px;
    height: 8px;
    left: 50%;
    transform: translateX(-50%);
    bottom: -8px;
    pointer-events: none;
}
#buzz-create-popup .buzz-popup-arrow::after {
    content: '';
    position: absolute;
    left: 50%;
    transform: translateX(-50%);
    border-width: 8px 8px 0 8px;
    border-style: solid;
    border-color: #fff transparent transparent transparent;
    display: block;
    width: 0;
    height: 0;
    filter: drop-shadow(0 2px 2px rgba(0,0,0,0.08));
}

/* list inside popup */
#buzz-create-popup .buzz-popup-list .home-block {
    display: flex;
    justify-content: space-between;
}
#buzz-create-popup .buzz-popup-list {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding-top: 4px;
}
#buzz-create-popup .buzz-popup-list a,
#buzz-create-popup .buzz-popup-list button {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 10px;
    border-radius: 8px;
    text-decoration: none;
    color: #222;
    background: transparent;
    border: none;
    text-align: left;
    cursor: pointer;
    font-size: 14px;
}
#buzz-create-popup .buzz-popup-list a:hover,
#buzz-create-popup .buzz-popup-list button:hover {
    background: #f5f7fb;
}
#buzz-create-popup .buzz-popup-list i {
    width: 22px;
    text-align: center;
    color: #666;
}

#buzz-create-user > hr {
    margin-bottom: 0px;
}

/* auth (guest) buttons */
#buzz-create-popup .buzz-auth {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin: 6px 0 0 0;
    flex-direction: column;
    text-align: -webkit-center;
}
#buzz-create-popup .buzz-auth a {
    padding: 8px 12px;
    border-radius: 8px;
    text-decoration: none;
    color: #fff;
    background: #0d6efd;
    font-weight: 600;
    font-size: 13px;
}
#buzz-create-popup .buzz-auth a.signup {
    background: #63a551;
}

/* small close button */
#buzz-create-popup .buzz-popup-close {
    /* position: absolute; */
    top: 6px;
    right: 8px;
    background: transparent;
    border: none;
    font-size: 20px !important;
    color: #888 !important;
    cursor: pointer;
}

/* responsive limits */
@media (max-width: 420px) {
    #buzz-create-popup { left: 50% !important; right: 8px !important; width: 256px; transform: none; }
    #buzz-create-popup .buzz-popup-arrow { display: none; }
}
</style>

<!-- ========================= -->
<!-- Floating Chat Button (LEFT) -->
<!-- ========================= -->
<div id="buzzjuice-floating-chat-left"
     class="buzzjuice-chat-fab-left"
     role="button"
     aria-label="Open chat"
     tabindex="0"
     data-href="https://buzzjuice.net/chat">
    <span class="buzz-chat-icon" aria-hidden="true">
        <svg stroke="currentColor" fill="currentColor" stroke-width="0" viewBox="0 0 576 512" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
            <path d="M416 192c0-88.4-93.1-160-208-160S0 103.6 0 192c0 34.3 14.1 65.9 38 92-13.4 30.2-35.5 54.2-35.8 54.5-2.2 2.3-2.8 5.7-1.5 8.7S4.8 352 8 352c36.6 0 66.9-12.3 88.7-25 32.2 15.7 70.3 25 111.3 25 114.9 0 208-71.6 208-160zm122 220c23.9-26 38-57.7 38-92 0-66.9-53.5-124.2-129.3-148.1.9 6.6 1.3 13.3 1.3 20.1 0 105.9-107.7 192-240 192-10.8 0-21.3-.8-31.7-1.9C207.8 439.6 281.8 480 368 480c41 0 79.1-9.2 111.3-25 21.8 12.7 52.1 25 88.7 25 3.2 0 6.1-1.9 7.3-4.8 1.3-2.9.7-6.3-1.5-8.7-.3-.3-22.4-24.2-35.8-54.5z"></path>
        </svg>
    </span>
    <span class="buzz-chat-unread no-count" aria-live="polite" aria-atomic="true">0</span>
</div>

<!-- ========================= -->
<!-- Bottom Footer Menu (finalized) -->
<!-- ========================= -->
<div class="bottom-footer-menu" role="navigation" aria-label="Bottom navigation">
    <ul>
        <li><a href="/streams/directory/market" aria-label="Catalog"><i class="fa fa-th-large" aria-hidden="true"></i><span>Catalog</span></a></li>
        <li><a href="/streams" aria-label="Streams"><i class="fa fa-image" aria-hidden="true"></i><span>Streams</span></a></li>
        <li><a href="/play" aria-label="Play"><i class="fa fa-gamepad" aria-hidden="true"></i><span>Play</span></a></li>

        <!-- HOME BUTTON: when clicked opens popup -->
        <li>
            <!-- changed href to '#' to avoid navigation; acts as a popup trigger -->
            <a href="#" aria-label="Create" id="buzz-home-button" aria-haspopup="dialog" role="button">
                <i class="fa fa-angle-double-up" aria-hidden="true"></i><span>Menu</span>
            </a>
        </li>

        <li><a href="/courses" aria-label="Courses"><i class="fa fa-leanpub" aria-hidden="true"></i><span>Courses</span></a></li>
        <li><a href="/streams/directory/pages" aria-label="Collabos"><i class="fa fa-handshake-o" aria-hidden="true"></i><span>Collabos</span></a></li>
        <li><a href="/social" aria-label="Social"><i class="fa fa-users" aria-hidden="true"></i><span>Social</span></a></li>
    </ul>
</div>

<!-- ========================= -->
<!-- Create Popup markup (hidden by default) -->
<!-- Note: we render both guest and user blocks. JS will show the correct one.
     Server-side $buzz_user_logged_in (WP) is available when this runs in WP.
-->
<!-- ========================= -->
<div id="buzz-create-popup" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="buzz-create-popup-title">

    <!-- Guest actions (hidden by default) -->
    <div data-auth="guest" hidden id="buzz-create-guest">
<!--        <div style="padding:10px;text-align:center;color:#444;">Please Log in or Sign up</div> -->
        <div class="buzz-auth">
            <a href="/login" class="login">Log in</a>
            <a href="/signup" class="signup">Sign up</a>
        </div>
    </div>

    <!-- Logged-in create actions (hidden by default) -->
    <div class="buzz-popup-list" data-auth="user" hidden id="buzz-create-user">
        <div class="home-block">
            <a href="/" class="buzz-create-link"><i class="fa fa-home"></i><span><strong>Home</strong></span></a>
            <button class="buzz-popup-close" aria-label="Close" title="Close">&times;</button>
        </div>
        <hr />
        <a href="https://buzzjuice.net/streams/create-blog/" class="buzz-create-link"><i class="fa fa-pencil"></i><span>Create Blog</span></a>
        <a href="https://buzzjuice.net/streams/create-album" class="buzz-create-link"><i class="fa fa-image"></i><span>Create Album</span></a>
        <a href="https://buzzjuice.net/streams/my-products" class="buzz-create-link"><i class="fa fa-shopping-bag"></i><span>Create Product</span></a>
        <a href="https://buzzjuice.net/streams/ads/create/" class="buzz-create-link"><i class="fa fa-bullhorn"></i><span>Create Ad</span></a>
        <a href="https://buzzjuice.net/streams/create-group" class="buzz-create-link"><i class="fa fa-users"></i><span>Create Group</span></a>
        <a href="https://buzzjuice.net/streams/events/create-event/" class="buzz-create-link"><i class="fa fa-calendar"></i><span>Create Event</span></a>
        <a href="https://buzzjuice.net/streams/create-page" class="buzz-create-link"><i class="fa fa-handshake-o"></i><span>Start a Collabo</span></a>
    </div>

    <div class="buzz-popup-arrow" aria-hidden="true"></div>
</div>

<script>
(function(){
    'use strict';

    // --- WordPress authentication: strictly by presence of WP login cookie ---
    function isWordPressUserLoggedIn() {
        // This checks ONLY for any cookie whose name begins with 'wordpress_logged_in_'
        // This is always present for logged-in WordPress users (across Buzzjuice SSO surfaces)
        return /(?:^|;\s*)wordpress_logged_in_[^=]+=[^;]+/.test(document.cookie);
    }

    // --- Show guest/user popup blocks according ONLY to WP login cookie ---
    function showAuthBlocks() {
        var isLogged = isWordPressUserLoggedIn();
        var userBlock = document.querySelector('[data-auth="user"]');
        var guestBlock = document.querySelector('[data-auth="guest"]');
        if (isLogged) {
            if (userBlock) userBlock.hidden = false;
            if (guestBlock) guestBlock.hidden = true;
        } else {
            if (userBlock) userBlock.hidden = true;
            if (guestBlock) guestBlock.hidden = false;
        }
    }

    // Floating FAB visibility (keep legacy non-WP show logic)
    function isWordPress() {
        try {
            if (typeof window.wp !== 'undefined') return true;
            var body = document.body;
            if (!body) return false;
            var cls = body.className || '';
            if (cls.indexOf('wp-') !== -1) return true;
            if (cls.indexOf('bb-') !== -1) return true;
            if (cls.indexOf('buddypress') !== -1) return true;
            return false;
        } catch(e) { return false; }
    }
    function initFloatingFab() {
        var fab = document.getElementById('buzzjuice-floating-chat-left');
        if (!fab) return;
        if (!isWordPress()) {
            fab.style.display = 'inline-flex';
        } else {
            fab.style.display = 'none';
        }
        fab.addEventListener('click', function(e){
            e.preventDefault();
            var href = fab.getAttribute('data-href') || 'https://buzzjuice.net/chat';
            window.location.href = href;
        }, false);
        fab.addEventListener('keydown', function(e){
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                var href = fab.getAttribute('data-href') || 'https://buzzjuice.net/chat';
                window.location.href = href;
            }
        }, false);
    }

    // Popup utilities (unchanged except call to showAuthBlocks updated)
    var popup = document.getElementById('buzz-create-popup');
    var homeButton = document.getElementById('buzz-home-button');

    function openPopup() {
        if (!popup || !homeButton) return;

        // Show correct block on each open.
        showAuthBlocks();

        // position popup centered above the home button (within bounds)
        var homeRect = homeButton.getBoundingClientRect();
        var footer = document.querySelector('.bottom-footer-menu');
        var footerRect = footer ? footer.getBoundingClientRect() : {height: 60, top: window.innerHeight};

        // make visible for measurement
        popup.style.display = 'block';
        popup.style.left = '50%';
        popup.style.bottom = (footerRect.height + 12) + 'px';
        popup.style.transform = 'translateX(-50%) translateY(6px) scale(.98)';
        popup.classList.add('open');
        popup.setAttribute('aria-hidden', 'false');

        // measure and adjust horizontal position so arrow points to home center
        var popupRect = popup.getBoundingClientRect();
        var popupW = popupRect.width || 240;
        var centerX = homeRect.left + (homeRect.width / 2);
        var left = centerX - (popupW / 2);

        // keep inside viewport with small padding
        var pad = 8;
        if (left < pad) left = pad;
        if ((left + popupW + pad) > window.innerWidth) left = window.innerWidth - popupW - pad;

        // set left using transform trick: set left px then translateX(0)
        popup.style.left = (left + (popupW / 2)) + 'px';
        popup.style.transform = 'translateX(-50%) translateY(0) scale(1)';

        // position arrow relative to popup left
        var arrow = popup.querySelector('.buzz-popup-arrow');
        if (arrow) {
            var arrowLeft = centerX - left;
            if (arrowLeft < 12) arrowLeft = 12;
            if (arrowLeft > popupW - 12) arrowLeft = popupW - 12;
            arrow.style.left = arrowLeft + 'px';
        }

        // focus first focusable element
        var firstFocusable = popup.querySelector('a, button');
        if (firstFocusable) firstFocusable.focus();

        document.addEventListener('click', docClickHandler, true);
        document.addEventListener('keydown', docKeyHandler, true);
    }

    function closePopup() {
        if (!popup) return;
        popup.classList.remove('open');
        popup.setAttribute('aria-hidden', 'true');
        // hide after transition
        setTimeout(function(){ if (!popup.classList.contains('open')) popup.style.display = 'none'; }, 160);
        document.removeEventListener('click', docClickHandler, true);
        document.removeEventListener('keydown', docKeyHandler, true);
        if (homeButton) homeButton.focus();
    }

    function togglePopup(e) {
        if (!popup) return;
        if (popup.classList.contains('open')) closePopup();
        else openPopup();
    }

    function docClickHandler(e) {
        var target = e.target;
        if (!popup) return;
        if (popup.contains(target) || (homeButton && homeButton.contains(target))) {
            return;
        }
        closePopup();
    }

    function docKeyHandler(e) {
        if (e.key === 'Escape') closePopup();
    }

    function bindHomeButton() {
        if (!homeButton) return;
        homeButton.addEventListener('click', function(ev){
            ev.preventDefault();
            togglePopup();
        }, false);
        homeButton.addEventListener('keydown', function(e){
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                togglePopup();
            }
        }, false);
    }

    function bindPopupLinks() {
        if (!popup) return;
        // close on link click (navigation)
        popup.addEventListener('click', function(e){
            var t = e.target;
            var a = t.closest && t.closest('a');
            if (a && a.href) closePopup();
        });
        var closeBtn = popup.querySelector('.buzz-popup-close');
        if (closeBtn) closeBtn.addEventListener('click', function(e){ e.preventDefault(); closePopup(); }, false);
    }

    // highlight active nav item
    function setActiveNav() {
        try {
            var pathname = window.location.pathname.replace(/\/+$/, '') || '/';
            var links = document.querySelectorAll('.bottom-footer-menu a');
            links.forEach(function(a){ a.classList.remove('active'); });
            var match = document.querySelector('.bottom-footer-menu a[href="'+pathname+'"]');
            if (!match) {
                for (var i=0;i<links.length;i++){
                    var href = links[i].getAttribute('href') || '';
                    if (href === '/' && pathname === '/') { match = links[i]; break; }
                    if (href !== '/' && href !== '' && pathname.indexOf(href) === 0) { match = links[i]; break; }
                }
            }
            if (match) match.classList.add('active');
        } catch(e){}
    }

    document.addEventListener('DOMContentLoaded', function(){
        initFloatingFab();
        bindHomeButton();
        bindPopupLinks();
        setActiveNav();

        // reposition popup on resize/orientation change
        window.addEventListener('resize', function(){ if (popup && popup.classList.contains('open')) openPopup(); });
        window.addEventListener('orientationchange', function(){ setTimeout(function(){ if (popup && popup.classList.contains('open')) openPopup(); }, 120); });
    });

})();
</script>

<?php
// End footer-menu.php
?>