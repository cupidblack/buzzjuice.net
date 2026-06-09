<?php
/**
 * Plugin Name: Buzzjuice Entitlement Engine
 * Description: Unified gating engine for hybrid (points, subscription, role, usage, micro-unlock, smart) access—selector runtime, partial access, action routers, & Palmier modal integration.
 * Version: 10.1.2
 */

if (!defined('ABSPATH')) exit;

// ---------------------------------------------------
// CONSTANTS
// ---------------------------------------------------

define('BZJ_BRE_VERSION',        '10.1.2');
define('BZJ_BRE_GO_PRO_URL',    'https://buzzjuice.net/streams/ww-sso-bridge.php?redirect_to=go-pro');
define('BZJ_BRE_LOGIN_URL',     wp_login_url());
define('BZJ_BRE_VERIFY_URL',    'https://buzzjuice.net/verification');
define('BZJ_BRE_MODULES_PATH',  ABSPATH . '/shared/bzj-entitlement-modules.php');

// ---------------------------------------------------
// LOAD MODULES REGISTRY
// ---------------------------------------------------

if (file_exists(BZJ_BRE_MODULES_PATH)) require_once BZJ_BRE_MODULES_PATH;

// ---------------------------------------------------
// USER STATE SNAPSHOT
// ---------------------------------------------------

function bzj_bre_user_state($user_id = 0) {
    static $cache = [];
    if (!$user_id) $user_id = get_current_user_id();
    if (isset($cache[$user_id])) return $cache[$user_id];

    $state = [
        'logged_in'   => false,
        'verified'    => false,
        'points'      => 0,
        'plan'        => 'free',
        'roles'       => [],
        'usage'       => [],
        'unlocks'     => [],
    ];

    if ($user_id) {
        $state['logged_in'] = true;
        $state['points']    = function_exists('mycred_get_users_balance') ? (float)mycred_get_users_balance($user_id) : 0;
        $state['verified']  = (bool)get_user_meta($user_id, 'verified', true);
        $state['plan']      = get_user_meta($user_id, 'bzj_plan', true) ?: 'free';
        if ($u = get_userdata($user_id)) $state['roles'] = $u->roles;
        $state['usage']     = [
            'forum_topics_daily'   => (int)get_user_meta($user_id, 'bzj_usage_forum_topics_daily', true),
            'forum_replies_daily'  => (int)get_user_meta($user_id, 'bzj_usage_forum_replies_daily', true)
        ];
        $unlocks = get_user_meta($user_id, 'bzj_unlocked_blocks', true);
        $state['unlocks'] = is_array($unlocks) ? $unlocks : [];
    }
    return $cache[$user_id] = apply_filters('bzj_bre_user_state', $state, $user_id);
}

// ---------------------------------------------------
// BASE POLICIES (for extensibility/inheritance)
// ---------------------------------------------------

function bzj_bre_base_policies() {
    return [
        'premium' => [
            'policy_version' => 1,
            'gate'           => ['subscription'],
            'requirements'   => ['subscription' => 'premium'],
            'mode'           => 'blur',
            'actions'        => ['primary' => 'upgrade'],
        ],
        'points' => [
            'policy_version' => 1,
            'gate'           => ['points'],
            'requirements'   => ['points' => 1],
            'mode'           => 'replace',
            'actions'        => ['primary' => 'buy_points'],
        ],
        'verified' => [
            'policy_version' => 1,
            'gate'           => ['verified'],
            'requirements'   => ['verified' => true],
            'mode'           => 'replace',
            'actions'        => ['primary' => 'verify'],
        ]
    ];
}

// ---------------------------------------------------
// REGISTRY LOADER (sort by priority desc)
// ---------------------------------------------------

function bzj_bre_registry() {
    $reg = [];
    $reg = apply_filters('bzj_bre_registry', $reg);
    uasort($reg, function($a, $b) {
        return ($b['priority'] ?? 0) <=> ($a['priority'] ?? 0);
    });
    return $reg;
}

// ---------------------------------------------------
// RUNTIME INJECT: CONFIG, CSS, JS
// ---------------------------------------------------

add_action('wp_enqueue_scripts', function () {
    if (is_admin()) return;
    $config = [
        'nonce'        => wp_create_nonce('bzj_bre_nonce'),
        'ajax'         => admin_url('admin-ajax.php'),
        'state'        => bzj_bre_user_state(),
        'registry'     => bzj_bre_registry(),
        'basePolicies' => bzj_bre_base_policies(),
        'urls' => [
            'goPro'   => BZJ_BRE_GO_PRO_URL,
            'login'   => BZJ_BRE_LOGIN_URL,
            'verify'  => BZJ_BRE_VERIFY_URL,
        ],
        'labels' => [
            'buyPoints' => 'Unlock',
            'upgrade'   => 'Upgrade',
            'unlock'    => 'Unlock',
            'verify'    => 'Verify',
            'login'     => 'Login',
        ]
    ];
    wp_register_script('bzj-bre-runtime', '', [], BZJ_BRE_VERSION, true);
    wp_enqueue_script('bzj-bre-runtime');
    wp_add_inline_script('bzj-bre-runtime', 'window.bzjBRE='.wp_json_encode($config).';', 'before');
    wp_add_inline_script('bzj-bre-runtime', bzj_bre_runtime_js());
    wp_add_inline_style('wp-block-library', bzj_bre_runtime_css());
}, 98);

// ---------------------------------------------------
// INLINE CSS
// ---------------------------------------------------

function bzj_bre_runtime_css() { return <<<CSS
.bzj-gate-placeholder{background:#fff8ef;border:2px dashed #ff9800;border-radius:14px;padding:10px;text-align:center;margin:14px 0;font-weight:600;color:black;font-size:17px;}
.bzj-gate-btn{background:#2d5bff;color:#fff;border:none;border-radius:8px;padding:5px;cursor:pointer;margin-top:10px;}
.bzj-blur-wrap{position:relative;}
.bzj-blur-content{filter:blur(5px);pointer-events:none;user-select:none;opacity:.65;}
.bzj-overlay{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;z-index:999;}
.bzj-disabled{pointer-events:none;opacity:.45;}
.bzj-gate-debug{outline:2px solid red!important;}
.bzj-micro-badge{background:#ffd600;color:#222;border-radius:6px;padding:1px 7px;margin-left:4px;font-size:11px;vertical-align:middle;font-weight:700;}
CSS;
}

// ---------------------------------------------------
// INLINE JS -- NO PHP VARIABLE INTERPOLATION INSIDE!!
// ---------------------------------------------------

function bzj_bre_runtime_js() { return <<<'JS'
(function(){
if(!window.bzjBRE) return;
const cfg = window.bzjBRE;
const registry = cfg.registry || {};
const state = cfg.state || {};
const basePolicies = cfg.basePolicies || {};
const labels = cfg.labels || {};
const normalizedPolicies = new Map();

// Deep merge helpers
function deepMerge(target, source){
    const out = {...target};
    for(const k in source) {
        if(typeof source[k] === 'object' && !Array.isArray(source[k]) && source[k] !== null) out[k] = deepMerge(target[k]||{}, source[k]);
        else out[k] = source[k];
    }
    return out;
}

// Selector normalization
function normalizePolicy(selector, policy, attrs={}){
    const cacheKey = selector + JSON.stringify(attrs);
    if(normalizedPolicies.has(cacheKey)) return normalizedPolicies.get(cacheKey);
    let norm = {...policy};
    if(norm.extends && basePolicies[norm.extends]) norm = deepMerge(basePolicies[norm.extends], norm);
    norm = deepMerge(norm, attrs);
    normalizedPolicies.set(cacheKey, norm);
    return norm;
}

// Policy logic resolver (supports required/optional/override)
function evaluatePolicy(policy){
    const gate = Array.isArray(policy.gate) ? policy.gate : [policy.gate].filter(Boolean);
    const req = policy.requirements || {};
    const logic = policy.logic || {};

    // OVERRIDE ROLES
    if(logic.override_any && state.roles && req.roles) {
        for (const r of state.roles)
            if(req.roles.includes(r)) return true;
    }

    // REQUIRED GATES
    let requiredPass = true;
    (logic.required||[]).forEach(type=>{
        if(type==='verified' && !state.verified) requiredPass = false;
        if(type==='usage') {
            const action = req.usage_action, limit = +req.usage_limit || 0;
            if(action && limit && state.usage[action] >= limit) requiredPass = false;
        }
    });
    if(!requiredPass) return false;

    // OPTIONAL ANY LOGIC
    if(logic.optional_any) {
        let anyPass = false;
        logic.optional_any.forEach(type=>{
            if(type==='subscription' && state.plan === req.subscription) anyPass = true;
            if(type==='points' && +(state.points||0)>= +(req.points||1)) anyPass = true;
        });
        return anyPass;
    }

    // SIMPLE CHECKS
    for(const type of gate) {
        if(type==='points' && +(state.points||0) < +(req.points||1)) return false;
        if(type==='subscription' && state.plan !== req.subscription) return false;
        if(type==='verified' && !state.verified) return false;
    }
    return true;
}

// Action router
function dispatchGateAction(policy, el){
    if(!policy) return;
    let btn = el.querySelector('.bzj-gate-btn');
    let action = policy.actions && policy.actions.primary || '';
    if(action==='buy_points') { if(window.bzOpenPalmierModal) window.bzOpenPalmierModal(); return; }
    if(action==='upgrade') { location.href=cfg.urls.goPro; return; }
    if(action==='verify') { location.href=cfg.urls.verify; return; }
    if(action==='login') { location.href=cfg.urls.login; return; }
    // Micro unlock
    if(policy.mode==='micro' && policy.blockId && btn) {
        btn.textContent='Unlocking...';
        fetch(cfg.ajax, {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'action=bzj_micro_unlock&nonce='+encodeURIComponent(cfg.nonce)+'&block_id='+encodeURIComponent(policy.blockId)+'&cost='+(policy.cost||1)})
        .then(r=>r.json()).then(j=>{
            if(j.success) location.reload();
            else { alert(j.data?.message||'Unlock failed'); btn.textContent=labels.unlock; }
        });
        return;
    }
}

// Renderers (replace/blur/preview/partial/disable/smart)
function buildPlaceholder(policy){
    let m=policy.messages||{};
    let showMain=policy.show_main!==false;
    let btnLabel = (policy.actions && policy.actions.primary && labels[policy.actions.primary]) || labels.buyPoints || 'Unlock';
    return '<div class="bzj-gate-placeholder" data-mode="' + (policy.mode||'') + '">' +
        (m.above||'') +
        (showMain&&m.main?'<div style="margin:8px 0;">'+m.main+'</div>':'') +
        (m.below||'') +
        '<button type="button" class="bzj-gate-btn">'+btnLabel+'</button></div>';
}

function renderReplace(el, policy){
    el.innerHTML = buildPlaceholder(policy);
    let btn = el.querySelector('.bzj-gate-btn'); if(btn) btn.onclick = ()=>dispatchGateAction(policy, el);
}
function renderBlur(el, policy){
    if(el.classList.contains('bzj-blur-wrap')) return;
    el.innerHTML = '<div class="bzj-blur-content">'+el.innerHTML+'</div><div class="bzj-overlay">'+buildPlaceholder(policy)+'</div>';
    el.classList.add('bzj-blur-wrap');
    let btn = el.querySelector('.bzj-gate-btn'); if(btn) btn.onclick=()=>dispatchGateAction(policy,el);
}
function renderDisable(el, policy){
    el.classList.add('bzj-disabled');
    el.title = (policy.messages && policy.messages.main) || 'Locked';
}
function renderPreview(el, policy){
    el.textContent = (el.textContent||'').substring(0, 120)+'...';
    el.innerHTML += buildPlaceholder(policy);
    let btn = el.querySelector('.bzj-gate-btn'); if(btn) btn.onclick=()=>dispatchGateAction(policy,el);
}
function renderSmart(el, policy){
    if(!state.logged_in) return renderPreview(el, policy);
    if(state.points > 0) return renderReplace(el, policy);
    renderBlur(el, policy);
}
// Main per-selector processor
function processSelector(selector, policy){
    document.querySelectorAll(selector).forEach(function(el){
        if(el.classList.contains('bzj-gated-active')) return;
        el.classList.add('bzj-gated-active');
        const attrs={}; for(const a of el.attributes) if(/^data-/.test(a.name)) {
            attrs[a.name.replace('data-','').replace(/-([a-z])/g,g=>g[1].toUpperCase())]=el.getAttribute(a.name);
        }
        let norm = normalizePolicy(selector, policy, attrs);
        if(norm.mode==='micro' && !norm.blockId)
            norm.blockId = btoa(selector + '|' + (el.textContent||'').substring(0,16));
        if(evaluatePolicy(norm)) return; // Let user access content
        let mode = norm.mode||'replace';
        switch(mode){
            case 'blur':    renderBlur(el, norm); break;
            case 'preview': renderPreview(el, norm); break;
            case 'partial': el.innerHTML=(el.innerHTML||'').slice(0,el.innerHTML.length/2)+buildPlaceholder(norm); break;
            case 'smart':   renderSmart(el, norm); break;
            case 'disable': renderDisable(el, norm); break;
            default:        renderReplace(el, norm); break;
        }
        if(window.BZJ_DEBUG_GATES) el.classList.add('bzj-gate-debug');
    });
}

// DOM observer
function processAll(){
    for(const selector in registry) processSelector(selector, registry[selector]);
    if(document.querySelectorAll('.bzj-gate').length) processSelector('.bzj-gate', {});
}
let mo=new MutationObserver(function(){clearTimeout(window.bzjGateTimer);window.bzjGateTimer=setTimeout(processAll,180);});
mo.observe(document.body,{childList:true,subtree:true});
document.addEventListener('DOMContentLoaded',processAll);
})();
JS;
}

// ---------------------------------------------------
// MICRO UNLOCK AJAX HANDLER
// ---------------------------------------------------

add_action('wp_ajax_bzj_micro_unlock', function(){
    check_ajax_referer('bzj_bre_nonce', 'nonce');
    if(!is_user_logged_in()) wp_send_json_error(['message'=>'Login required.']);
    $uid = get_current_user_id();
    $cost = absint($_POST['cost'] ?? 0);
    $block_id = sanitize_text_field($_POST['block_id'] ?? '');
    if(!$cost || !$block_id) wp_send_json_error(['message'=>'Invalid request.']);
    $bal = function_exists('mycred_get_users_balance') ? (float)mycred_get_users_balance($uid) : 0;
    if($bal < $cost) wp_send_json_error(['message'=>'Low Palmier balance.']);
    if(function_exists('mycred_subtract')) mycred_subtract('bzj_micro_unlock', $uid, $cost, 'Buzzjuice micro unlock');
    $unlocks = get_user_meta($uid, 'bzj_unlocked_blocks', true);
    if(!is_array($unlocks)) $unlocks=[];
    $unlocks[] = $block_id;
    update_user_meta($uid, 'bzj_unlocked_blocks', array_unique($unlocks));
    wp_send_json_success(['message'=>'Unlocked']);
});