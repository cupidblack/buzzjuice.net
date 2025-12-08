<?php
if ($wo['loggedin'] == false) {
	header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
    exit();
}

if (!$wo['config']['can_use_affiliate']) {
    $wo['config']['affiliate_system'] = 0;
}

$wo['description'] = $wo['config']['siteDesc'];
$wo['keywords']    = $wo['config']['siteKeywords'];
$wo['page']        = 'setting';
$wo['title']       = $wo['lang']['setting'] . ' | ' . $wo['config']['siteTitle'];

// Server-side guard: require Pro for 'tiers' settings page
// Detect request for the tiers subpage via GET parameter 'page' (common in templates: &page=tiers)
// If the user is not premium, return a premium-required JSON response.
if (!empty($_GET['page']) && $_GET['page'] === 'tiers') {
    require_once $_SERVER['DOCUMENT_ROOT'] . '/shared/subscription-check.php';
    $wow_uid = intval($wo['user']['id'] ?? 0);
    if (!bz_is_premium($wow_uid)) {
        // For Ajax consumption we send JSON 403; if this request is a normal page load,
        // consider redirecting instead. We follow your requested snippet and return JSON.
        bz_json_premium_required('Creating or editing tiers requires a Pro subscription.', Wo_SeoLink('index.php?link1=go-pro'));
    }
}

$wo['content']     = Wo_LoadPage('setting/content');