<?php
/*Modified by Blue Crown R&<D></D>*/
/*
if ($wo['loggedin'] == false || $wo['config']['common_things'] != 1) {
  header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
  exit();
}
$wo['description'] = $wo['config']['siteDesc'];
$wo['keywords']    = $wo['config']['siteKeywords'];
$wo['page']        = 'common_things';
$wo['title']       = $wo['config']['siteTitle'];
$wo['content']     = Wo_LoadPage('common_things/content');
*/


if ($wo['loggedin'] == false || $wo['config']['common_things'] != 1) {
  header("Location: " . Wo_SeoLink('index.php?link1=welcome'));
  exit();
}
$wo['description'] = $wo['config']['siteDesc'];
$wo['keywords']    = $wo['config']['siteKeywords'];
$wo['page']        = 'common_things';
$wo['title']       = $wo['config']['siteTitle'];
$wo['content']     = Wo_LoadPage('common_things/content');

// Ensure metadata is properly set
$wo['meta_description'] = $wo['config']['siteDesc'];
$wo['meta_keywords']    = $wo['config']['siteKeywords'];
$wo['meta_title']       = $wo['config']['siteTitle'];
?>