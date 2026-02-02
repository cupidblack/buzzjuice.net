<!-- Footer  -->
    <?php if ($data['name'] !== 'login' && $data['name'] !== 'contact' && $data['name'] !== 'register' && $data['name'] !== 'forgot' && $data['name'] !== 'reset' && $data['name'] !== 'verifymail' && IS_LOGGED) { ?>
    <div class="container " style="transform: none;"><?php echo GetAd('footer');?></div>
<?php } ?>
    <footer id="footer" class="page_footer">
		<div class="container-fluid container_new">
			<div class="footer-copyright">
				<div class="valign-wrapper">
					<div>
						<?php require( $theme_path . 'main' . $_DS . 'language.php' );?>
						
						<?php if($config->social_media_links == 'on'){ ?>
						&nbsp;&nbsp;<span class="docial">
						<?php if(!empty($config->facebook_url)){ ?>
							&nbsp;&nbsp;<a href="<?php echo $config->facebook_url;?>" target="_blank">
								<svg height="512" viewBox="0 0 152 152" width="512" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><g><g><path d="m76 0a76 76 0 1 0 76 76 76 76 0 0 0 -76-76zm19.26 68.8-1.26 10.59a2 2 0 0 1 -2 1.78h-11v31.4a1.42 1.42 0 0 1 -1.4 1.43h-11.2a1.42 1.42 0 0 1 -1.4-1.44l.06-31.39h-8.33a2 2 0 0 1 -2-2v-10.58a2 2 0 0 1 2-2h8.27v-10.26c0-11.87 7.07-18.33 17.4-18.33h8.47a2 2 0 0 1 2 2v8.91a2 2 0 0 1 -2 2h-5.19c-5.62.09-6.68 2.78-6.68 6.8v8.85h12.32a2 2 0 0 1 1.94 2.24z"></path></g></g></svg>
							</a>
						<?php }?>
						<?php if(!empty($config->twitter_url)){ ?>
							&nbsp;&nbsp;<a href="<?php echo $config->twitter_url;?>" target="_blank">
								<svg height="512" viewBox="0 0 152 152" width="512" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><g><g><path d="m76 0a76 76 0 1 0 76 76 76 76 0 0 0 -76-76zm37.85 53a32.09 32.09 0 0 1 -6.51 7.15 2.78 2.78 0 0 0 -1 2.17v.25a45.58 45.58 0 0 1 -2.94 15.86 46.45 46.45 0 0 1 -8.65 14.5 42.73 42.73 0 0 1 -18.75 12.39 46.9 46.9 0 0 1 -14.74 2.29 45 45 0 0 1 -22.6-6.09 1.3 1.3 0 0 1 -.62-1.44 1.25 1.25 0 0 1 1.22-.94h1.9a30.31 30.31 0 0 0 16.94-5.14 16.45 16.45 0 0 1 -13-11.17.86.86 0 0 1 1-1.11 15.08 15.08 0 0 0 2.76.26h.35a16.42 16.42 0 0 1 -9.57-15.11.86.86 0 0 1 1.27-.75 14.44 14.44 0 0 0 3.74 1.45 16.42 16.42 0 0 1 -2.65-19.91.86.86 0 0 1 1.41-.11 43 43 0 0 0 29.51 15.77h.08a.62.62 0 0 0 .6-.67 17.39 17.39 0 0 1 .38-6 15.91 15.91 0 0 1 10.7-11.44 17.59 17.59 0 0 1 5.19-.8 16.36 16.36 0 0 1 10.84 4.09 2.12 2.12 0 0 0 1.41.54 2.15 2.15 0 0 0 .5-.07 30.3 30.3 0 0 0 8-3.3.85.85 0 0 1 1.25 1 16.23 16.23 0 0 1 -4.31 6.87 29.38 29.38 0 0 0 5.24-1.77.86.86 0 0 1 1.05 1.23z"></path></g></g></svg>
							</a>
						<?php }?>
						<?php if(!empty($config->google_url)){ ?>
							&nbsp;&nbsp;<a href="<?php echo $config->google_url;?>" target="_blank">
								<svg height="512" viewBox="0 0 152 152" width="512" xmlns="http://www.w3.org/2000/svg" fill="currentColor"><g><g><circle cx="76" cy="76" r="12.01"></circle><path d="m91.36 45.65h-30.72a15 15 0 0 0 -15 15v30.71a15 15 0 0 0 15 15h30.72a15 15 0 0 0 15-15v-30.72a15 15 0 0 0 -15-14.99zm-15.36 50.01a19.66 19.66 0 1 1 19.65-19.66 19.68 19.68 0 0 1 -19.65 19.66zm19.77-34.46a4.86 4.86 0 1 1 4.85-4.85 4.86 4.86 0 0 1 -4.85 4.85z"></path><path d="m76 0a76 76 0 1 0 76 76 76 76 0 0 0 -76-76zm38 91.36a22.66 22.66 0 0 1 -22.64 22.64h-30.72a22.67 22.67 0 0 1 -22.64-22.64v-30.72a22.67 22.67 0 0 1 22.64-22.64h30.72a22.67 22.67 0 0 1 22.64 22.64z"></path></g></g></svg>
							</a>
						<?php }?>
						</span>
						<?php } ?>
					</div>
					
					
					<div class="dt_fotr_spn">
					<ul class="dt_footer_links">
						<li><a href="<?php echo $site_url;?>/about" data-ajax="/about"><?php echo __( 'About Us' );?></a></li>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/terms" data-ajax="/terms"><?php echo __( 'Terms' );?></a></li>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/privacy" data-ajax="/privacy"><?php echo __( 'Privacy Policy' );?></a></li>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/contact" data-ajax="/contact"><?php echo __( 'Contact' );?></a></li>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/faqs" data-ajax="/faqs"><?php echo __( 'faqs' );?></a></li>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/refund" data-ajax="/refund"><?php echo __( 'refund' );?></a></li>
						<?php if ($config->developers_page == '1') { ?>
						&nbsp;-&nbsp;<li><a href="<?php echo $site_url;?>/developers" data-ajax="/developers"><?php echo __( 'Developers' );?></a></li>
						<?php } ?>
					</ul>
					<?php require( $theme_path . 'main' . $_DS . 'custom-page.php' );?>
					
					</div>
					
					<div><?php echo __( 'Copyright' );?> © <?php echo date( "Y" ) . " " . ucfirst( $config->site_name );?>. <?php echo __( 'All rights reserved' );?>.</div>
				</div>
			</div>
		</div>











        <!--Blue Crown R&D: Footer Menu-->
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
    width: 44px;
    height: 44px;
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
    font-size: 12px;
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
    max-width: 292px; /* REQUIRED maximum width */
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

/* auth (guest) buttons */
#buzz-create-popup .buzz-auth {
    display: flex;
    gap: 8px;
    justify-content: center;
    margin: 6px 0 0 0;
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
    position: absolute;
    top: 6px;
    right: 8px;
    background: transparent;
    border: none;
    font-size: 18px;
    color: #888;
    cursor: pointer;
}

/* responsive limits */
@media (max-width: 420px) {
    #buzz-create-popup { left: 8px !important; right: 8px !important; max-width: calc(100% - 16px); transform: none; }
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
        <li><a href="/streams/directory/market" aria-label="Catalog"><i class="fa fa-list" aria-hidden="true"></i><span>Catalog</span></a></li>
        <li><a href="/streams" aria-label="Streams"><i class="fa fa-image" aria-hidden="true"></i><span>Streams</span></a></li>
        <li><a href="/play" aria-label="Play"><i class="fa fa-gamepad" aria-hidden="true"></i><span>Play</span></a></li>

        <!-- HOME BUTTON: when clicked opens popup -->
        <li>
            <!-- changed href to '#' to avoid navigation; acts as a popup trigger -->
            <a href="#" aria-label="Create" id="buzz-home-button" aria-haspopup="dialog" role="button">
                <i class="fa fa-home" aria-hidden="true"></i><span>Home</span>
            </a>
        </li>

        <li><a href="/courses" aria-label="Courses"><i class="fa fa-leanpub" aria-hidden="true"></i><span>Courses</span></a></li>
        <li><a href="/streams/funding" aria-label="Projects"><i class="fa fa-briefcase" aria-hidden="true"></i><span>Projects</span></a></li>
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
    <button class="buzz-popup-close" aria-label="Close" title="Close">&times;</button>

    <!-- Guest actions (hidden by default) -->
    <div data-auth="guest" hidden id="buzz-create-guest">
        <div style="padding:10px;text-align:center;color:#444;">Please Log in or Sign up to create content</div>
        <div class="buzz-auth">
            <a href="/login" class="login">Log in</a>
            <a href="/signup" class="signup">Sign up</a>
        </div>
    </div>

    <!-- Logged-in create actions (hidden by default) -->
    <div class="buzz-popup-list" data-auth="user" hidden id="buzz-create-user">
        <a href="https://buzzjuice.net/streams/create-blog/" class="buzz-create-link"><i class="fa fa-pencil"></i><span>Create Blog</span></a>
        <a href="https://buzzjuice.net/streams/create-album" class="buzz-create-link"><i class="fa fa-image"></i><span>Create Album</span></a>
        <a href="https://buzzjuice.net/streams/my-products" class="buzz-create-link"><i class="fa fa-shopping-bag"></i><span>Create Product</span></a>
        <a href="https://buzzjuice.net/streams/ads/create/" class="buzz-create-link"><i class="fa fa-bullhorn"></i><span>Create Ad</span></a>
        <a href="https://buzzjuice.net/streams/create-group" class="buzz-create-link"><i class="fa fa-users"></i><span>Create Group</span></a>
        <a href="https://buzzjuice.net/streams/events/create-event/" class="buzz-create-link"><i class="fa fa-calendar"></i><span>Create Event</span></a>
        <a href="https://buzzjuice.net/streams/create-page" class="buzz-create-link"><i class="fa fa-building"></i><span>Create Business</span></a>
    </div>

    <div class="buzz-popup-arrow" aria-hidden="true"></div>
</div>

<script>
(function(){
    'use strict';

    // expose server-side indication (WordPress) if available
    window.buzzServerLoggedIn = <?php echo $buzz_user_logged_in ? 'true' : 'false'; ?>;

    // Detect WordPress surface
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
        } catch(e) {
            return false;
        }
    }

    // Cookie-based detection to support WoWonder / QuickDate (fallback)
    function isUserLoggedInClient() {
        try {
            // If server says logged in, trust that.
            if (window.buzzServerLoggedIn) return true;

            // WordPress cookie pattern
            if (document.cookie.match(/wordpress_logged_in_/)) return true;

            // Common WoWonder/QuickDate cookie names - adjust if your install uses different names
            if (document.cookie.indexOf('user_id=') !== -1) return true;
            if (document.cookie.indexOf('wo_user_id=') !== -1) return true;
            if (document.cookie.indexOf('qd_user_id=') !== -1) return true;

            // If your SSO sets a shared token cookie like 'buzz_auth', check it:
            if (document.cookie.indexOf('buzz_auth=') !== -1) return true;

            return false;
        } catch (e) {
            return false;
        }
    }

    // init FAB visibility (only show on non-WP surfaces)
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

    // Popup utilities
    var popup = document.getElementById('buzz-create-popup');
    var homeButton = document.getElementById('buzz-home-button');

    function showAuthBlocks() {
        var isLogged = isUserLoggedInClient();
        var userBlock = document.querySelector('[data-auth="user"]');
        var guestBlock = document.querySelector('[data-auth="guest"]');

        if (window.buzzServerLoggedIn) {
            // If server says logged in, always show the user block
            if (userBlock) userBlock.hidden = false;
            if (guestBlock) guestBlock.hidden = true;
            return;
        }

        if (isLogged) {
            if (userBlock) userBlock.hidden = false;
            if (guestBlock) guestBlock.hidden = true;
        } else {
            if (userBlock) userBlock.hidden = true;
            if (guestBlock) guestBlock.hidden = false;
        }
    }

    function openPopup() {
        if (!popup || !homeButton) return;

        // prepare auth-specific blocks
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


















    </footer>
<!-- End Footer  -->