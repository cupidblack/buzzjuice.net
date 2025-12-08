<?php
/**
 * Plugin Name: Gutenberg Per-Page Scoped CSS (MU) with Important Toggle, Reset & Undo
 * Description: MU plugin — per-page CSS with !important toggle, Reset button, confirmation popup, and Undo Reset feature.
 * Version: 1.4.0
 * Author: Elliot Yamoah
 * License: GPLv2+
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Meta keys
if ( ! defined( 'GB_PG_CSS_META_KEY' ) ) define( 'GB_PG_CSS_META_KEY', '_gb_page_css' );
if ( ! defined( 'GB_PG_CSS_IMPORTANT_KEY' ) ) define( 'GB_PG_CSS_IMPORTANT_KEY', '_gb_page_css_important' );

/**
 * Register per-page CSS and toggle meta
 */
add_action( 'init', function() {
	$post_types = get_post_types( [ 'public' => true ], 'names' );
	foreach ( $post_types as $pt ) {
		register_post_meta( $pt, GB_PG_CSS_META_KEY, [
			'single'        => true,
			'type'          => 'string',
			'show_in_rest'  => true,
			'auth_callback' => fn($allowed, $meta_key, $post_id, $user_id) => user_can($user_id,'edit_post',$post_id),
		] );
		register_post_meta( $pt, GB_PG_CSS_IMPORTANT_KEY, [
			'single'        => true,
			'type'          => 'boolean',
			'show_in_rest'  => true,
			'auth_callback' => fn($allowed, $meta_key, $post_id, $user_id) => user_can($user_id,'edit_post',$post_id),
		] );
	}
}, 20 );

/**
 * Add unique body class for scoping
 */
add_filter( 'body_class', function( $classes ) {
	if ( is_singular() ) {
		$id = get_queried_object_id();
		if ( $id ) $classes[] = 'gb-post-' . intval($id);
	}
	return $classes;
} );

/**
 * Output scoped CSS in front-end
 */
add_action( 'wp_head', function() {
	if ( ! is_singular() ) return;
	$post_id = get_queried_object_id();
	if ( ! $post_id ) return;

	$raw_css = (string) get_post_meta( $post_id, GB_PG_CSS_META_KEY, true );
	$force_important = (bool) get_post_meta( $post_id, GB_PG_CSS_IMPORTANT_KEY, true );

	if ( trim($raw_css) !== '' ) {
		$scope_class = '.gb-post-' . intval($post_id);
		$scoped = gb_scope_css($raw_css, $scope_class, $force_important);
		$minified = gb_minify_css($scoped);
		printf("<style id=\"gb-page-css-%d\">%s</style>\n", $post_id, $minified);
	}
}, 25 );

/**
 * Gutenberg editor: Document settings panel
 */
add_action( 'enqueue_block_editor_assets', function() {
	$handle = 'gb-page-css-editor';

	wp_register_script( $handle, '', [
		'wp-plugins','wp-edit-post','wp-data','wp-element','wp-components',
	], false, true );

	wp_localize_script( $handle, 'GBPAGEMETA', [
		'cssKey' => GB_PG_CSS_META_KEY,
		'importantKey' => GB_PG_CSS_IMPORTANT_KEY,
	] );

	$inline_js = <<<'JS'
(function(wp){
	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editPost;
	const { TextareaControl, ToggleControl, Button } = wp.components;
	const { withSelect, withDispatch, compose } = wp.data;
	const { createElement, Fragment, useEffect, useState } = wp.element;

	const cssKey = GBPAGEMETA.cssKey;
	const importantKey = GBPAGEMETA.importantKey;

	const Panel = (props) => {
		const meta = props.meta || {};
		const postId = props.postId || 0;
		const cssVal = meta[cssKey] || '';
		const forceImportant = meta[importantKey] || false;

		// store last saved state for Undo Reset
		const [lastSaved, setLastSaved] = useState({ css: cssVal, important: forceImportant });

		useEffect(()=>{
			if(!postId) return;
			const editor = document.querySelector('.editor-styles-wrapper') || document.querySelector('.edit-post-visual-editor');
			if(!editor) return;
			const prefix = 'gb-post-'+postId;
			if(!editor.classList.contains(prefix)) editor.classList.add(prefix);

			const styleId = 'gb-editor-page-css';
			let styleEl = document.getElementById(styleId);
			if(cssVal && cssVal.trim()){
				if(!styleEl){
					styleEl = document.createElement('style');
					styleEl.id = styleId;
					document.head.appendChild(styleEl);
				}
				let scopedCss = cssVal.split('}').map(rule=>{
					if(!rule.trim()) return '';
					const parts = rule.split('{');
					if(parts.length<2) return rule;
					let decl = parts[1].trim();
					if(forceImportant){
						decl = decl.replace(/([^;]+);/g,'$1 !important;');
					}
					const sel = parts[0].trim().split(',').map(s=>prefix+' '+s.trim()).join(',');
					return sel+'{'+decl+'}';
				}).join('');
				styleEl.textContent = scopedCss;
			}else if(styleEl) styleEl.remove();
		}, [cssVal, forceImportant, postId]);

		const resetCSS = () => {
			if(confirm('Are you sure you want to reset all custom CSS and the !important toggle for this page?')){
				// save current state for undo
				setLastSaved({ css: cssVal, important: forceImportant });
				props.setMeta({...meta,[cssKey]:'',[importantKey]:false});
			}
		};

		const undoReset = () => {
			props.setMeta({...meta,[cssKey]:lastSaved.css,[importantKey]:lastSaved.important});
		};

		return createElement(Fragment,null,
			createElement(PluginDocumentSettingPanel,{name:'gb-page-css-panel',title:'Per-Page CSS',initialOpen:false},
				createElement(TextareaControl,{
					label:'Custom CSS for this page/post',
					rows:10,
					value:cssVal,
					onChange: val=>{
						props.setMeta({...meta,[cssKey]:val});
						setLastSaved({ css: val, important: forceImportant });
					},
					help:'CSS scoped to this page/post. Preview mirrors front-end.'
				}),
				createElement(ToggleControl,{
					label:'Force !important on all rules',
					checked: forceImportant,
					onChange: val=>{
						props.setMeta({...meta,[importantKey]:val});
						setLastSaved({ css: cssVal, important: val });
					}
				}),
				createElement('div', { style: { marginTop:'10px' } },
					createElement(Button,{isSecondary:true,onClick:resetCSS},'Reset CSS'),
					' ',
					createElement(Button,{isSecondary:true,onClick:undoReset},'Undo Reset')
				)
			)
		);
	};

	const Enhanced = compose(
		withSelect(select=>{
			const meta = select('core/editor').getEditedPostAttribute('meta')||{};
			const postId = typeof select('core/editor').getCurrentPostId==='function'
				? select('core/editor').getCurrentPostId()
				: select('core/editor').getEditedPostAttribute('id')||0;
			return {meta, postId};
		}),
		withDispatch(dispatch=>({ setMeta(meta){ dispatch('core/editor').editPost({meta}); } }))
	)(Panel);

	registerPlugin('gb-page-css-plugin',{render:Enhanced});
})(window.wp);
JS;

	wp_enqueue_script($handle);
	wp_add_inline_script($handle,$inline_js);
});

/**
 * PHP helpers
 */
function gb_scope_css($css,$prefix,$force_important=false){
	$css = trim((string)$css);
	if($css==='') return '';
	$css = preg_replace('#/\*.*?\*/#s','',$css);
	$tokens = preg_split('/(?<=})/',$css,-1,PREG_SPLIT_NO_EMPTY);
	$out='';
	foreach($tokens as $t){
		$t = trim($t);
		if(preg_match('/^@(media|supports)[^{]*\{(.*)\}$/is',$t,$m)){
			$inner = rtrim($m[2],'}');
			$scoped_inner = gb_scope_css($inner,$prefix,$force_important);
			$at_header = preg_replace('/\{[\s\S]*$/','{',$t);
			$out .= $at_header."\n".$scoped_inner."\n}\n";
			continue;
		}
		if(preg_match('/^(.*?)\{([\s\S]*)\}$/s',$t,$m)){
			$selectors = preg_split('/\s*,\s*/',$m[1]);
			$prefixed=[];
			foreach($selectors as $sel){
				$sel = trim($sel);
				if(in_array(strtolower($sel),['html','body',':root'],true)){$prefixed[]=$prefix;continue;}
				$prefixed[] = $prefix.' '.$sel;
			}
			$decl = $m[2];
			if($force_important) $decl = preg_replace('/([^;]+);/','${1} !important;',$decl);
			$out .= implode(', ',$prefixed).'{'.$decl."}\n";
			continue;
		}
		$out .= $t."\n";
	}
	return $out;
}

function gb_minify_css($css){
	$css = preg_replace('#/\*.*?\*/#s','',$css);
	$css = preg_replace('/\s+/',' ',$css);
	$css = preg_replace('/\s*([{}:;,])\s*/','$1',$css);
	return trim($css);
}
