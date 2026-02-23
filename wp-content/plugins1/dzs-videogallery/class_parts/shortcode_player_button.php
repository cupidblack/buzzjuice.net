<?php
$fout = '';
if ( ! defined( 'ABSPATH' ) ) // Or some other WordPress constant
	exit;

$margs = array(
	'link'=>'',
	'style'=>'btn-flat',
	'onclick'=>'',
	'label'=>'',
	'type'=>'',
	'icon'=>'',
	'color'=>'',
	'text_color'=>'',
	'target'=>'',
	'background_color'=>'',
	'extra_classes'=>'',
	'subscriber_count'=>'default',
);

if ($atts) {

	$margs = array_merge($margs, $atts);
}



$tag = 'div';
if($margs['link']) {
	$tag = 'a';
}


$fout .= '<'.$tag;


if($margs['link']){

	$fout.=' href="'.$margs['link'].'"';
}


if($margs['target']){

	$fout.=' target="'.$margs['target'].'"';
}else{

	$fout.=' target="'.''.'"';
}

$fout.=' class="'.$margs['style'].'';

if($margs['style']=='player-but') {
	$fout .= ' dzstooltip-con';
}

if($content){
	$margs['label']= $content;
}

$fout.=' '.$margs['extra_classes'];

$fout.='"';
$fout.=' style="';

if($margs['text_color']){
	$fout.='color: '. $margs['text_color'].';';
}
if($margs['background_color']){
	$fout.='background-color: '. $margs['background_color'].';';
}
$fout.='"';

if($margs['onclick']){
	$fout.=' onclick=\''. $margs['onclick'].'\'';
}


$fout.='>';

if($margs['style']=='player-but'){
	$fout.='<span class="the-icon-bg"></span>';
}
if($margs['style']=='btn-zoomsounds'){
	$fout.='<span class="the-bg" style="';

	if($margs['background_color']){
		$fout.='background-color: '. $margs['background_color'].';';
	}

	$fout.='"></span>';
}

if($margs['style']=='player-but'){
	$fout.='<span class="dzstooltip arrow-from-start transition-slidein arrow-bottom skin-black align-right" style="width: auto; white-space: nowrap;">'.$margs['label'].'</span>';
}
if($margs['style']=='player-but'){
	$fout.='<i class="svg-icon fa '. $margs['icon'].'"></i>';
}
if($margs['style']=='btn-zoomsounds' || $margs['style']=='btn-flat'){
	$fout.='<span class="the-icon"><i class="fa '. $margs['icon'].'"></i></span>';
}


if($margs['style']=='btn-zoomsounds' || $margs['style']=='btn-flat'){
	$fout.='<span class="the-label ">'.$margs['label'].'</span>';
}


$fout.='</'.$tag.'>';



if($margs['type']=='youtube-subscribe'){


  ClassDzsvgHelpers::enqueueDzsVgShowcase();
	wp_enqueue_script('google-api-platform','https://apis.google.com/js/platform.js');

	$fout= '<div class="g-ytsubscribe" style="vertical-align: middle" data-channel="'.($margs['link']).'"  data-count="'.$margs['subscriber_count'].'"  data-onytevent="onYtEvent" data-layout="default"></div>';

	$fout.=' <div id="ytsubscribe-events-log"></div>';
}