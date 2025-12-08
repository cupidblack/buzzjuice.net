<?php
/**
 * MU Plugin Name: BuzzJuice Embed Content Shortcode (MU)
 * Description: Provides [bz_embed_content] to embed templates, pages, posts, CPTs or external URLs into any content. Improved template and post-type resolution to match theme behavior. Supports object-cache/transient caching, sanitize control, wrapper class, admin diagnostics, and cache invalidation.
 * Version:     1.2.1
 * Author:      BuzzJuice
 * License:     GPLv2+
 *
 * Install: Save this file as: wp-content/mu-plugins/bz-embed-content.php
 *
 * Examples:
 *  [bz_embed_content type="template" value="page-courses-landing-grid"]
 *  [bz_embed_content type="page" id_or_slug="slug" value="courses/courses-landing-guest"]
 *  [bz_embed_content type="page" id_or_slug="id" value="327"]
 *  [bz_embed_content type="post" id_or_slug="id" value="1470"]
 *  [bz_embed_content type="post" id_or_slug="slug" value="my-new-post"]
 *  [bz_embed_content type="url" value="https://example.com/data.json" cache="true" cache_time="3600"]
 *
 * Attributes:
 *  - type: template | url | page | post | <any_cpt>  (default: page)
 *  - id_or_slug: id | slug  (default: slug)
 *  - value: id, slug, template name or URL (required)
 *  - cache: true|false (default: true)
 *  - cache_time: seconds (default: 3600)
 *  - cache_for_logged_in: true|false (default: false)
 *  - sanitize: true|false (default: true)
 *  - wrapper_class: optional CSS class wrapper
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BZ_EMBED_PLUGIN_VERSION' ) ) {
	define( 'BZ_EMBED_PLUGIN_VERSION', '1.2.1' );
}
if ( ! defined( 'BZ_EMBED_CACHE_PREFIX' ) ) {
	define( 'BZ_EMBED_CACHE_PREFIX', 'bz_embed_' );
}

/* ------------------------------
 * Locate a template file: try child theme, parent theme, theme subfolders, mu-plugins/bz-templates, absolute paths
 * Accepts templates with or without .php extension and returns the full path or false.
 * ------------------------------ */
if ( ! function_exists( 'bz_embed_locate_template' ) ) {
	function bz_embed_locate_template( $candidate ) {
		$candidate = (string) $candidate;
		$candidate = trim( $candidate );
		$candidate = preg_replace( '/\.php$/i', '', $candidate );

		// possible filenames to check
		$filenames = array(
			$candidate . '.php',
			trailingslashit( $candidate ) . 'index.php',
		);

		// try locate_template which searches child and parent and theme subfolders
		foreach ( $filenames as $fn ) {
			$located = locate_template( $fn, false, false );
			if ( $located ) {
				return $located;
			}
		}

		// direct stylesheet directory check (handles templates in subfolders)
		foreach ( $filenames as $fn ) {
			$path = get_stylesheet_directory() . '/' . ltrim( $fn, '/' );
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		// parent theme directory
		foreach ( $filenames as $fn ) {
			$path = get_template_directory() . '/' . ltrim( $fn, '/' );
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		// mu-plugins shared templates folder
		foreach ( $filenames as $fn ) {
			$path = trailingslashit( WP_CONTENT_DIR ) . 'mu-plugins/bz-templates/' . ltrim( $fn, '/' );
			if ( file_exists( $path ) ) {
				return $path;
			}
		}

		// if an absolute path was provided and exists, return it
		if ( file_exists( $candidate ) ) {
			return $candidate;
		}
		if ( file_exists( $candidate . '.php' ) ) {
			return $candidate . '.php';
		}

		return false;
	}
}

/* ------------------------------
 * Admin-friendly message helper (visible debug only to capable users)
 * ------------------------------ */
if ( ! function_exists( 'bz_embed_message' ) ) {
	function bz_embed_message( $short_message, $debug_info = '', $class = 'bz-embed-error' ) {
		$short_html = '<div class="' . esc_attr( $class ) . '">⚠️ ' . wp_kses_post( $short_message ) . '</div>';
		if ( current_user_can( 'manage_options' ) && $debug_info ) {
			$debug_html = '<pre class="bz-embed-debug" style="background:#f9f9f9;border-left:4px solid #ddd;padding:8px;margin-top:6px;white-space:pre-wrap;">' . esc_html( $debug_info ) . '</pre>';
			return $short_html . $debug_html;
		}
		return $short_html;
	}
}

/* ------------------------------
 * Cache wrappers: prefer object cache (wp_cache), fallback to transients
 * ------------------------------ */
if ( ! function_exists( 'bz_embed_get_cache' ) ) {
	function bz_embed_get_cache( $key ) {
		$key = (string) $key;
		if ( wp_using_ext_object_cache() ) {
			$val = wp_cache_get( $key, 'bz_embed' );
			if ( false !== $val ) {
				return $val;
			}
		}
		return get_transient( $key );
	}
}

if ( ! function_exists( 'bz_embed_set_cache' ) ) {
	function bz_embed_set_cache( $key, $value, $ttl = HOUR_IN_SECONDS ) {
		$key = (string) $key;
		if ( wp_using_ext_object_cache() ) {
			wp_cache_set( $key, $value, 'bz_embed', (int) $ttl );
		}
		set_transient( $key, $value, (int) $ttl );
	}
}

/* ------------------------------
 * Conservative cache deletion by prefix
 * ------------------------------ */
if ( ! function_exists( 'bz_embed_delete_cache_by_prefix' ) ) {
	function bz_embed_delete_cache_by_prefix( $prefix = BZ_EMBED_CACHE_PREFIX ) {
		global $wpdb;
		$prefix = esc_sql( $prefix );

		// Try to flush object cache if available (this is conservative; used on explicit admin/post-change events)
		if ( wp_using_ext_object_cache() && function_exists( 'wp_cache_flush' ) ) {
			wp_cache_flush();
		}

		// Delete transients matching prefix
		$option_table = $wpdb->options;
		$like_name    = $wpdb->esc_like( '_transient_' . $prefix ) . '%';
		$like_time    = $wpdb->esc_like( '_transient_timeout_' . $prefix ) . '%';

		$option_names = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$option_table} WHERE option_name LIKE %s OR option_name LIKE %s",
				$like_name,
				$like_time
			)
		);

		if ( ! empty( $option_names ) ) {
			foreach ( $option_names as $opt ) {
				$key = preg_replace( '/^_transient_/', '', $opt );
				$key = preg_replace( '/^_transient_timeout_/', '', $key );
				delete_transient( $key );
			}
		}
	}
}

/* ------------------------------
 * Main shortcode [bz_embed_content]
 * ------------------------------ */
add_shortcode( 'bz_embed_content', 'bz_embed_content_shortcode_handler' );
if ( ! function_exists( 'bz_embed_content_shortcode_handler' ) ) {
	function bz_embed_content_shortcode_handler( $atts = array() ) {
		$defaults = array(
			'type'                => 'page',
			'id_or_slug'          => 'slug', // default to slug to match common editor use
			'value'               => '',
			'cache'               => 'true',
			'cache_time'          => '3600',
			'cache_for_logged_in' => 'false',
			'sanitize'            => 'true',
			'wrapper_class'       => '',
		);

		$atts = shortcode_atts( $defaults, (array) $atts, 'bz_embed_content' );

		$type_raw            = sanitize_key( $atts['type'] );
		$id_or_slug_raw      = sanitize_key( $atts['id_or_slug'] );
		$value_raw           = trim( wp_unslash( $atts['value'] ) );
		$use_cache_attr      = filter_var( $atts['cache'], FILTER_VALIDATE_BOOLEAN );
		$cache_time_seconds  = max( 0, intval( $atts['cache_time'] ) );
		$cache_for_logged_in = filter_var( $atts['cache_for_logged_in'], FILTER_VALIDATE_BOOLEAN );
		$sanitize_attr       = filter_var( $atts['sanitize'], FILTER_VALIDATE_BOOLEAN );
		$wrapper_class       = sanitize_html_class( $atts['wrapper_class'] );

		if ( '' === $value_raw ) {
			return bz_embed_message( 'Missing value attribute for [bz_embed_content].', 'Provided attributes: ' . print_r( $atts, true ) );
		}

		// Normalize inferred types
		$type = $type_raw;
		if ( 'url' === $type || 0 === strpos( $value_raw, 'http' ) ) {
			$type = 'url';
		} elseif ( 'template' === $type ) {
			$type = 'template';
		}

		$use_cache = (bool) ( $use_cache_attr && ( ! is_user_logged_in() || $cache_for_logged_in ) );

		$key_parts = array(
			'bz_embed',
			get_current_blog_id(),
			$type,
			$id_or_slug_raw,
			$value_raw,
			$sanitize_attr ? 'san' : 'raw',
			$wrapper_class ?: 'nowrap',
			is_user_logged_in() ? 'user:' . get_current_user_id() : 'guest',
		);
		$cache_key = BZ_EMBED_CACHE_PREFIX . md5( implode( '|', $key_parts ) );

		if ( $use_cache ) {
			$cached = bz_embed_get_cache( $cache_key );
			if ( false !== $cached && '' !== $cached ) {
				return $cached;
			}
		}

		ob_start();
		$output_ok = false;

		switch ( $type ) {
			case 'template':
				// Allow value to be either 'page-courses-landing-grid' or 'templates/subdir/my-grid.php'
				$template_candidate = preg_replace( '/\.php$/i', '', $value_raw );
				$template_file      = bz_embed_locate_template( $template_candidate );

				if ( $template_file ) {
					// include template in isolated scope
					$include_template = function( $file ) {
						// phpcs:ignore WordPress.PHP.Include
						include $file;
					};
					try {
						$include_template( $template_file );
						$output_ok = true;
					} catch ( Throwable $e ) {
						echo bz_embed_message( 'Template include error.', "Error: {$e->getMessage()}\nFile: {$template_file}" );
					}
				} else {
					$debug = "Template not found: {$value_raw}. Searched: locate_template, child theme, parent theme, wp-content/mu-plugins/bz-templates/";
					echo bz_embed_message( 'Template not found: ' . esc_html( $value_raw ), $debug );
				}
				break;

			case 'url':
				$url = esc_url_raw( $value_raw );
				if ( empty( $url ) || ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
					echo bz_embed_message( 'Invalid URL provided.', 'URL: ' . $value_raw );
					break;
				}
				$response = wp_remote_get( $url, array( 'timeout' => 15, 'sslverify' => true ) );
				if ( is_wp_error( $response ) ) {
					echo bz_embed_message( 'Error fetching URL.', $response->get_error_message() );
					break;
				}
				$code  = intval( wp_remote_retrieve_response_code( $response ) );
				$body  = wp_remote_retrieve_body( $response );
				$ctype = wp_remote_retrieve_header( $response, 'content-type' );

				if ( 200 !== $code || '' === $body ) {
					echo bz_embed_message( 'Unable to retrieve content from URL.', "URL: {$url}\nHTTP code: {$code}" );
					break;
				}
				if ( ! empty( $ctype ) && false !== stripos( $ctype, 'application/json' ) ) {
					$json = json_decode( $body, true );
					$pretty = ( null !== $json ) ? wp_json_encode( $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE ) : $body;
					echo '<pre class="bz-embed-json">' . esc_html( $pretty ) . '</pre>';
					$output_ok = true;
				} else {
					// HTML handling: sanitize by default unless user has unfiltered_html and explicitly requested raw
					if ( ! $sanitize_attr && current_user_can( 'unfiltered_html' ) ) {
						echo '<div class="bz-embedded-content bz-type-url" data-bz-url="' . esc_attr( $url ) . '">' . $body . '</div>';
						$output_ok = true;
					} else {
						echo '<div class="bz-embedded-content bz-type-url" data-bz-url="' . esc_attr( $url ) . '">';
						echo wp_kses_post( $body );
						echo '</div>';
						$output_ok = true;
					}
				}
				break;

			default:
				// treat as post type (page, post, or CPT)
				$post_obj = null;
				$post_type = $type;

				if ( 'id' === $id_or_slug_raw && is_numeric( $value_raw ) ) {
					$post_obj = get_post( intval( $value_raw ) );
				} else {
					// slug or path lookup: support nested page paths like 'courses/courses-landing-guest'
					$post_obj = get_page_by_path( $value_raw, OBJECT, $post_type );
					if ( ! $post_obj ) {
						// fallback: search by post_name (slug) via get_posts (handles numeric slugs too)
						$found = get_posts( array(
							'name'           => $value_raw,
							'post_type'      => $post_type,
							'post_status'    => array( 'publish', 'private' ),
							'posts_per_page' => 1,
						) );
						if ( ! empty( $found ) ) {
							$post_obj = $found[0];
						}
					}
				}

				if ( $post_obj && in_array( $post_obj->post_status, array( 'publish', 'private' ), true ) ) {
					// permission check for private
					if ( 'private' === $post_obj->post_status && ! current_user_can( 'read_post', $post_obj->ID ) ) {
						echo bz_embed_message( 'Content is unavailable (private).', "Requested ID: {$post_obj->ID}" );
						break;
					}

					// Preserve existing global post & WP_Query then restore after
					$old_post     = isset( $GLOBALS['post'] ) ? $GLOBALS['post'] : null;
					$old_wp_query = isset( $GLOBALS['wp_query'] ) ? $GLOBALS['wp_query'] : null;

					$GLOBALS['post'] = $post_obj;
					setup_postdata( $post_obj );

					$content = apply_filters( 'the_content', $post_obj->post_content );
					$content = do_shortcode( $content );
					$content = shortcode_unautop( $content );

					$wrapper_start = $wrapper_class ? '<div class="' . esc_attr( $wrapper_class ) . '">' : '<div>';
					$wrapper_end   = '</div>';

					if ( $sanitize_attr && ! current_user_can( 'unfiltered_html' ) ) {
						echo $wrapper_start . wp_kses_post( $content ) . $wrapper_end;
					} else {
						echo $wrapper_start . $content . $wrapper_end;
					}

					// restore
					wp_reset_postdata();
					$GLOBALS['post'] = $old_post;
					$GLOBALS['wp_query'] = $old_wp_query;

					$output_ok = true;
				} else {
					$debug = "Lookup attempt failed:\n type={$post_type}\n id_or_slug={$id_or_slug_raw}\n value={$value_raw}\n";
					echo bz_embed_message( 'Content not found or unavailable.', $debug );
				}
				break;
		}

		$output = ob_get_clean();

		// ensure wrapper class is present if provided
		if ( $wrapper_class && false === strpos( $output, 'class="' . esc_attr( $wrapper_class ) . '"' ) ) {
			$output = '<div class="' . esc_attr( $wrapper_class ) . '">' . $output . '</div>';
		}

		if ( $use_cache && '' !== $output ) {
			$ttl = ( $cache_time_seconds > 0 ) ? $cache_time_seconds : HOUR_IN_SECONDS;
			bz_embed_set_cache( $cache_key, $output, $ttl );
		}

		return $output;
	}
}

/* ------------------------------
 * Invalidate cache on content changes or theme switch
 * ------------------------------ */
add_action( 'save_post', 'bz_embed_invalidate_on_post_change', 10, 1 );
add_action( 'deleted_post', 'bz_embed_invalidate_on_post_change', 10, 1 );
add_action( 'after_switch_theme', 'bz_embed_invalidate_on_theme_switch' );
add_action( 'switch_theme', 'bz_embed_invalidate_on_theme_switch' );

if ( ! function_exists( 'bz_embed_invalidate_on_post_change' ) ) {
	function bz_embed_invalidate_on_post_change( $post_id = 0 ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}
		bz_embed_delete_cache_by_prefix( BZ_EMBED_CACHE_PREFIX );
	}
}

if ( ! function_exists( 'bz_embed_invalidate_on_theme_switch' ) ) {
	function bz_embed_invalidate_on_theme_switch() {
		bz_embed_delete_cache_by_prefix( BZ_EMBED_CACHE_PREFIX );
	}
}

/* ------------------------------
 * Admin AJAX to clear embed cache (secure)
 * ------------------------------ */
add_action( 'wp_ajax_bz_clear_embed_cache', 'bz_ajax_clear_embed_cache' );
if ( ! function_exists( 'bz_ajax_clear_embed_cache' ) ) {
	function bz_ajax_clear_embed_cache() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'forbidden', 403 );
		}
		check_ajax_referer( 'bz_clear_embed_cache', 'nonce', false );
		bz_embed_delete_cache_by_prefix( BZ_EMBED_CACHE_PREFIX );
		wp_send_json_success( 'cache_cleared' );
	}
}

/* ------------------------------
 * Small inline styles for admin-visible messages
 * ------------------------------ */
add_action( 'wp_head', 'bz_embed_inline_styles' );
if ( ! function_exists( 'bz_embed_inline_styles' ) ) {
	function bz_embed_inline_styles() {
		echo '<style>
			.bz-embed-error { background:#fff5f5; border-left:4px solid #ffbcbc; padding:8px; margin:10px 0; color:#a00; }
			.bz-embed-debug { font-size:12px; color:#333; margin-top:6px; white-space:pre-wrap; }
			.bz-embedded-content { margin-bottom:1rem; }
			.bz-embed-json { background:#f4f4f4;padding:8px;border-radius:4px;overflow:auto; }
		</style>';
	}
}

/* ------------------------------
 * WP-CLI command to clear cache if WP-CLI is present
 * ------------------------------ */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'bz-embed-cache-clear', function() {
		bz_embed_delete_cache_by_prefix( BZ_EMBED_CACHE_PREFIX );
		WP_CLI::success( 'bz_embed cache cleared' );
	} );
}