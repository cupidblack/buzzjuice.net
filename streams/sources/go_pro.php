<?php
/************ Modified by Blue Crown R&D: WordPress WoWonder Pro Package Logic ************/
if ($wo['loggedin'] == false) {
    Wo_RedirectSmooth(Wo_SeoLink('index.php?link1=welcome'));
    exit();
}

// Redirect if Pro is disabled
if ($wo['config']['pro'] == 0) {
    header("Location: " . $wo['config']['site_url']);
    exit();
}



$wo['description'] = '';
$wo['keywords']    = '';
$wo['page']        = 'go_pro';
$wo['title']       = $wo['config']['siteTitle'];
$wo['content']     = Wo_LoadPage('go-pro/content');
?>