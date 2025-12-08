<?php
/*
Plugin Name: TotalPoll Paginated Results (MU plugin)
Description: Wrapper shortcode to paginate TotalPoll results: [totalpoll_paginated id="..." paginate="N" page="1" screen="results"]. Server-side trimming via TotalPoll render filters + AJAX-friendly client-side controller. Drop into wp-content/mu-plugins/.
Version: 1.0
Author: Adapted
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Global registry so we can request trimming during the same render cycle
 * Format: [ poll_id => [ 'per_page' => int, 'page' => int ] ]
 */
$GLOBALS['tp_paginate_registry'] = array();

/**
 * Shortcode:
 * [totalpoll_paginated id="3223" paginate="3" per_page="3" page="1" screen="results"]
 * Accepts 'paginate' as alias for per_page.
 */
add_shortcode( 'totalpoll_paginated', function( $atts ) {
    $atts = shortcode_atts( array(
        'id'        => 0,
        'per_page'  => 0,
        'paginate'  => '', // alias
        'page'      => 1,
        'screen'    => 'results',
        'template'  => '',
        'class'     => '',
        'lazy'      => '',
    ), $atts, 'totalpoll_paginated' );

    $poll_id = intval( $atts['id'] );
    if ( $poll_id <= 0 ) {
        return '<!-- totalpoll_paginated: missing poll id -->';
    }

    $per_page = intval( $atts['per_page'] );
    if ( ! $per_page && isset( $atts['paginate'] ) ) {
        $per_page = intval( $atts['paginate'] );
    }
    $per_page = max( 0, $per_page );
    $page = max( 1, intval( $atts['page'] ) );

    // Register trimming instruction for this render (shortcode present)
    if ( $per_page > 0 ) {
        $GLOBALS['tp_paginate_registry'][ $poll_id ] = array(
            'per_page' => $per_page,
            'page'     => $page,
            'ts'       => time(),
        );
    }

    // Build native [totalpoll] shortcode to output initial content (server-side may already trim)
    $short_atts = array( 'id' => $poll_id );
    foreach ( array( 'screen', 'template', 'class', 'lazy' ) as $k ) {
        if ( ! empty( $atts[ $k ] ) ) $short_atts[ $k ] = $atts[ $k ];
    }
    $shortcode = '[totalpoll';
    foreach ( $short_atts as $k => $v ) {
        $shortcode .= ' ' . esc_attr( $k ) . '="' . esc_attr( $v ) . '"';
    }
    $shortcode .= ']';

    // Data attrs for JS controller
    $data_attrs  = ' data-tp-poll-id="' . esc_attr( $poll_id ) . '"';
    $data_attrs .= ' data-tp-per-page="' . esc_attr( $per_page ) . '"';
    $data_attrs .= ' data-tp-page="' . esc_attr( $page ) . '"';
    $data_attrs .= ' data-tp-screen="' . esc_attr( $atts['screen'] ) . '"';

    // Ensure JS prints
    add_action( 'wp_footer', function(){ tp_paginated_print_js(); }, 999 );

    $output  = '<div class="tp-paginated-wrapper" ' . $data_attrs . '>';
    $output .= '<div class="tp-paginated-content">';
    $output .= do_shortcode( $shortcode );
    $output .= '</div>';
    $output .= '<div class="tp-paginated-controls" aria-live="polite" style="margin-top:.5rem;">';
    $output .= '<button class="tp-p-prev" type="button" style="margin-right:.5rem;display:none;">&laquo; Prev</button>';
    $output .= '<span class="tp-p-page-indicator" style="font-weight:600;"></span>';
    $output .= '<button class="tp-p-next" type="button" style="margin-left:.5rem;display:none;">Next &raquo;</button>';
    $output .= '</div>';
    $output .= '</div>';

    // cleanup registry to avoid bleed to other shortcodes in same page render
    if ( isset( $GLOBALS['tp_paginate_registry'][ $poll_id ] ) ) {
        unset( $GLOBALS['tp_paginate_registry'][ $poll_id ] );
    }

    return $output;
});

/**
 * Defensive list of TotalPoll render filters to hook into.
 */
$filters = array(
    'totalpoll/filters/render/args',
    'totalpoll/filters/render/choices',
    'totalpoll_render_args',
    'totalpoll_render_choices',
);

/**
 * Attach trimming callback to all candidate filters.
 */
foreach ( $filters as $f ) {
    add_filter( $f, 'tp_paginated_trim_callback', 10, 2 );
}

/**
 * Trimming callback: slice choices to per_page when requested.
 * Triggered when:
 *  - registry entry exists for this poll in current render, OR
 *  - request contains buzz variables (AJAX calls from our JS)
 */
function tp_paginated_trim_callback( $args, $context = null ) {
    if ( empty( $args ) || ! is_array( $args ) ) return $args;

    // find poll id in args defensively
    $poll_id = 0;
    if ( isset( $args['poll']['id'] ) )              $poll_id = intval( $args['poll']['id'] );
    elseif ( isset( $args['poll_id'] ) )             $poll_id = intval( $args['poll_id'] );
    elseif ( isset( $args['id'] ) )                  $poll_id = intval( $args['id'] );
    elseif ( isset( $args['poll']['ID'] ) )          $poll_id = intval( $args['poll']['ID'] );

    if ( ! $poll_id && isset( $_REQUEST['pollId'] ) ) {
        $poll_id = intval( $_REQUEST['pollId'] );
    }
    if ( ! $poll_id ) return $args;

    $should_trim = false;
    $per_page = 0;
    $page = 1;

    // registry (shortcode render in same request)
    if ( ! empty( $GLOBALS['tp_paginate_registry'] ) && isset( $GLOBALS['tp_paginate_registry'][ $poll_id ] ) ) {
        $r = $GLOBALS['tp_paginate_registry'][ $poll_id ];
        $per_page = isset( $r['per_page'] ) ? intval( $r['per_page'] ) : 0;
        $page     = isset( $r['page'] ) ? intval( $r['page'] ) : 1;
        if ( $per_page > 0 ) $should_trim = true;
    }

    // explicit AJAX request vars from our JS
    if ( isset( $_REQUEST['tp_paginate'] ) && intval( $_REQUEST['tp_paginate'] ) === 1 ) {
        $per_page = isset( $_REQUEST['per_page'] ) ? intval( $_REQUEST['per_page'] ) : $per_page;
        $page     = isset( $_REQUEST['page'] )     ? intval( $_REQUEST['page'] )     : $page;
        if ( $per_page > 0 ) $should_trim = true;
    }

    if ( ! $should_trim || $per_page <= 0 ) return $args;
    $page = max( 1, $page );

    // Helper to slice choices array and preserve keys/ids when possible
    $slice_choices = function( $choices ) use ( $per_page, $page ) {
        $choices = array_values( $choices );
        $offset = ( $page - 1 ) * $per_page;
        $sliced = array_slice( $choices, $offset, $per_page );
        $pres = array();
        foreach ( $sliced as $c ) {
            if ( isset( $c['id'] ) ) $pres[ $c['id'] ] = $c;
            else $pres[] = $c;
        }
        return $pres;
    };

    // Case: polls with questions (multi-question polls)
    if ( isset( $args['poll']['questions'] ) && is_array( $args['poll']['questions'] ) ) {
        foreach ( $args['poll']['questions'] as $qi => $q ) {
            if ( empty( $q['choices'] ) || ! is_array( $q['choices'] ) ) continue;
            $args['poll']['questions'][ $qi ]['choices'] = $slice_choices( $q['choices'] );
        }
        return $args;
    }

    // Case: root-level choices
    if ( isset( $args['choices'] ) && is_array( $args['choices'] ) ) {
        $args['choices'] = $slice_choices( $args['choices'] );
        return $args;
    }

    return $args;
}

/**
 * Print inline JS (keeps MU plugin self-contained).
 * JS behavior:
 *  - Locate wrappers
 *  - Compute total pages (fetch full results once if needed)
 *  - Request server-side paginated HTML via admin-ajax.php?action=totalpoll_poll_request + tp_paginate marker
 *  - Fallback to client-side slice if server fails
 *  - Prev/Next controls update page indicator and request pages
 */
function tp_paginated_print_js() {
    static $printed = false;
    if ( $printed ) return;
    $printed = true;
    ?>
    <script id="tp-paginated-js">
    (function(){
        'use strict';
        function toInt(v){ v = parseInt(v,10); return isNaN(v)?0:v; }

        // Heuristic selectors to find choices in TotalPoll markup
        function findChoiceNodes(container){
            var sels = ['.tp-choice','.totalpoll-choice','.tp-result-choice','.tp-result','.tp-results li','.choices li','li'];
            var set = [];
            sels.forEach(function(sel){
                try {
                    var nodes = container.querySelectorAll(sel);
                    if(nodes && nodes.length){
                        for(var i=0;i<nodes.length;i++) set.push(nodes[i]);
                    }
                } catch(e){}
            });
            if(set.length === 0){
                var all = container.querySelectorAll('li,div');
                Array.prototype.forEach.call(all, function(n){
                    var txt = (n.textContent||'');
                    if(/%|vote|votes|count|points/i.test(txt)) set.push(n);
                });
            }
            var uniq = [];
            set.forEach(function(n){ if(uniq.indexOf(n) === -1) uniq.push(n); });
            return uniq;
        }

        function fetchPollHTML(pollId, screen, extra, cb){
            var data = { action:'totalpoll_poll_request', pollId: pollId, screen: screen || 'results' };
            for(var k in extra) if(extra.hasOwnProperty(k)) data[k] = extra[k];
            var params = [];
            for(var p in data) if(data.hasOwnProperty(p)) params.push(encodeURIComponent(p)+'='+encodeURIComponent(data[p]));
            var xhr = new XMLHttpRequest();
            xhr.open('POST','<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>', true);
            xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
            xhr.onreadystatechange = function(){
                if(xhr.readyState===4){
                    if(xhr.status>=200 && xhr.status<300) cb(null, xhr.responseText); else cb(new Error('HTTP '+xhr.status), null);
                }
            };
            xhr.send(params.join('&'));
        }

        function parseHTML(html){
            var d = document.implementation.createHTMLDocument('');
            d.documentElement.innerHTML = html;
            return d;
        }

        function renderPage(wrapper, pollId, screen, perPage, page, totalPages){
            var contentEl = wrapper.querySelector('.tp-paginated-content');
            if(contentEl) contentEl.style.opacity = '0.6';
            fetchPollHTML(pollId, screen, { tp_paginate:1, per_page:perPage, page:page }, function(err, html){
                if(err || !html){ clientSideFallback(wrapper, pollId, screen, perPage, page, totalPages); return; }
                if(contentEl) contentEl.innerHTML = html;
                updateControls(wrapper, page, totalPages);
                if(contentEl) contentEl.style.opacity = '';
            });
        }

        function clientSideFallback(wrapper, pollId, screen, perPage, page, totalPages){
            var contentEl = wrapper.querySelector('.tp-paginated-content');
            if(contentEl) contentEl.style.opacity = '0.6';
            fetchPollHTML(pollId, screen, {}, function(err, html){
                if(err || !html){ if(contentEl) contentEl.style.opacity = ''; return; }
                var doc = parseHTML(html);
                var choices = findChoiceNodes(doc);
                var totalChoices = choices.length || 0;
                var computedPages = Math.max(1, Math.ceil(totalChoices / perPage));
                var frag = document.createElement('div');
                var offset = (page - 1) * perPage;
                for(var i = offset; i < Math.min(offset + perPage, choices.length); i++){
                    try { frag.appendChild(choices[i].cloneNode(true)); } catch(e){}
                }
                contentEl.innerHTML = '';
                contentEl.appendChild(frag);
                updateControls(wrapper, page, computedPages);
                if(contentEl) contentEl.style.opacity = '';
            });
        }

        function updateControls(wrapper, page, totalPages){
            var prev = wrapper.querySelector('.tp-p-prev');
            var next = wrapper.querySelector('.tp-p-next');
            var ind  = wrapper.querySelector('.tp-p-page-indicator');
            if(!prev || !next || !ind) return;
            prev.style.display = (page <= 1) ? 'none' : '';
            next.style.display = (page >= totalPages) ? 'none' : '';
            ind.textContent = 'Page ' + page + ' of ' + totalPages;
            wrapper.setAttribute('data-tp-current-page', page);
            wrapper.setAttribute('data-tp-total-pages', totalPages);
        }

        function initWrapper(wrapper){
            if(!wrapper) return;
            var pollId = toInt(wrapper.getAttribute('data-tp-poll-id'));
            var perPage = toInt(wrapper.getAttribute('data-tp-per-page') || 3);
            var page = toInt(wrapper.getAttribute('data-tp-page') || 1);
            var screen = wrapper.getAttribute('data-tp-screen') || 'results';
            if(!pollId) return;
            var contentEl = wrapper.querySelector('.tp-paginated-content');

            // quick heuristic: if content contains more items than perPage, assume it's full or server-side trimmed; otherwise fetch full to compute totals
            var immediateChoices = contentEl ? findChoiceNodes(contentEl) : [];
            if(immediateChoices && immediateChoices.length > perPage){
                var totalPages = Math.max(1, Math.ceil(immediateChoices.length / perPage));
                renderPage(wrapper, pollId, screen, perPage, page, totalPages);
                attachHandlers();
                return;
            }

            // fetch full results to compute totals, then request paginated page
            fetchPollHTML(pollId, screen, {}, function(err, html){
                var totalPages = 1;
                if(!err && html){
                    var doc = parseHTML(html);
                    var choices = findChoiceNodes(doc);
                    var totalChoices = choices.length || 0;
                    totalPages = Math.max(1, Math.ceil(totalChoices / perPage));
                }
                renderPage(wrapper, pollId, screen, perPage, page, totalPages);
                attachHandlers();
            });

            function attachHandlers(){
                var prev = wrapper.querySelector('.tp-p-prev');
                var next = wrapper.querySelector('.tp-p-next');
                if(prev && !prev._attached){
                    prev.addEventListener('click', function(){ var cur = toInt(wrapper.getAttribute('data-tp-current-page')||page); var np = Math.max(1, cur - 1); renderPage(wrapper, pollId, screen, perPage, np, toInt(wrapper.getAttribute('data-tp-total-pages')||1)); });
                    prev._attached = true;
                }
                if(next && !next._attached){
                    next.addEventListener('click', function(){ var cur = toInt(wrapper.getAttribute('data-tp-current-page')||page); var np = Math.min(toInt(wrapper.getAttribute('data-tp-total-pages')||1), cur + 1); renderPage(wrapper, pollId, screen, perPage, np, toInt(wrapper.getAttribute('data-tp-total-pages')||1)); });
                    next._attached = true;
                }
            }
        }

        function initAll(){
            var wrappers = document.querySelectorAll('.tp-paginated-wrapper');
            for(var i=0;i<wrappers.length;i++){ try{ initWrapper(wrappers[i]); } catch(e){ console && console.error && console.error(e); } }
        }

        if(document.readyState === 'complete' || document.readyState === 'interactive'){ setTimeout(initAll, 10); } else { document.addEventListener('DOMContentLoaded', initAll); }

        // Observe DOM additions
        var observer = new MutationObserver(function(muts){
            muts.forEach(function(mut){
                for(var i=0;i<mut.addedNodes.length;i++){
                    var n = mut.addedNodes[i];
                    if(!(n instanceof Element)) continue;
                    if(n.classList && n.classList.contains('tp-paginated-wrapper')) { try{ initWrapper(n); } catch(e){} }
                    else {
                        var inner = n.querySelector && n.querySelector('.tp-paginated-wrapper');
                        if(inner) { try{ initWrapper(inner); } catch(e){} }
                    }
                }
            });
        });
        observer.observe(document.body || document.documentElement, { childList:true, subtree:true });
    })();
    </script>
    <?php
}
?>