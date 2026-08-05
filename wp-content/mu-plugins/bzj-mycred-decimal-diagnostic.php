<?php
/**
 * Plugin Name: bzj-mycred-decimal-diagnostic (MU)
 * Description: Diagnostic and conservative compatibility layer to help myCRED accept/process decimal point values. Writes to wp-content/mycred-custom.log. Place in wp-content/mu-plugins/.
 * Version: 1.0.1
 * Author: bzj
 * License: GPLv2-or-later
 *
 * Safety: diagnostic-first. Does not alter DB or plugin files. Compatibility mode attempts
 * a temporary formatting shim only for testing; do NOT enable in production long-term.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( defined( 'BZJ_MYCREDD_LOADED' ) ) {
	return;
}
define( 'BZJ_MYCREDD_LOADED', true );

/* -------------------------
   Configurable constants
   ------------------------- */

if ( ! defined( 'BZJ_MYCREDD_LOG_ENABLE' ) ) {
	define( 'BZJ_MYCREDD_LOG_ENABLE', true );
}
if ( ! defined( 'BZJ_MYCREDD_LOG_PATH' ) ) {
	define( 'BZJ_MYCREDD_LOG_PATH', rtrim( defined( 'WP_CONTENT_DIR' ) ? WP_CONTENT_DIR : ABSPATH . 'wp-content', '/' ) . '/mycred-custom.log' );
}
if ( ! defined( 'BZJ_MYCREDD_LOG_LEVEL' ) ) {
	define( 'BZJ_MYCREDD_LOG_LEVEL', 'INFO' ); // OFF, ERROR, WARN, INFO, DEBUG, TRACE
}
if ( ! defined( 'BZJ_MYCREDD_LOG_MAX_BYTES' ) ) {
	define( 'BZJ_MYCREDD_LOG_MAX_BYTES', 5 * 1024 * 1024 );
}
// Conservative compatibility mode: when true plugin will try to preserve decimals by replacing formatted values at hooks.
if ( ! defined( 'BZJ_MYCREDD_COMPAT_MODE' ) ) {
	define( 'BZJ_MYCREDD_COMPAT_MODE', false );
}
// Optional forced decimals value (int). If set, overrides detected decimals for formatting.
if ( ! defined( 'BZJ_MYCREDD_FORCED_DECIMALS' ) ) {
	define( 'BZJ_MYCREDD_FORCED_DECIMALS', null );
}

/* -------------------------
   Logging utilities
   ------------------------- */

if ( ! function_exists( 'bzj_mycredd_log_level_value' ) ) {
	function bzj_mycredd_log_level_value( $level ) {
		switch ( strtoupper( $level ) ) {
			case 'TRACE': return 6;
			case 'DEBUG': return 5;
			case 'INFO':  return 4;
			case 'WARN':  return 3;
			case 'ERROR': return 2;
			case 'OFF':   return 1;
			default:      return 4;
		}
	}
}

if ( ! function_exists( 'bzj_mycredd_log' ) ) {
	function bzj_mycredd_log( $level, $message, $context = array() ) {
		try {
			if ( defined( 'BZJ_MYCREDD_LOG_ENABLE' ) && ! BZJ_MYCREDD_LOG_ENABLE ) {
				return false;
			}
			$desired = bzj_mycredd_log_level_value( BZJ_MYCREDD_LOG_LEVEL );
			$cur     = bzj_mycredd_log_level_value( $level );
			if ( $cur > $desired ) {
				return false;
			}
			$path = BZJ_MYCREDD_LOG_PATH;
			$dir  = dirname( $path );
			if ( ! is_dir( $dir ) ) {
				@mkdir( $dir, 0755, true );
			}
			// Rotate
			if ( file_exists( $path ) && filesize( $path ) > BZJ_MYCREDD_LOG_MAX_BYTES ) {
				$bak = $path . '.' . gmdate( 'Ymd-Hi' );
				@rename( $path, $bak );
			}
			$ts = current_time( 'mysql' );
			$entry = array(
				'time' => $ts,
				'level' => strtoupper( $level ),
				'message' => (string) $message,
				'context' => is_array( $context ) ? $context : array( 'data' => $context ),
			);
			$line = wp_json_encode( $entry );
			@error_log( $line . PHP_EOL, 3, $path );
		} catch ( Exception $e ) {
			@error_log( 'bzj_mycredd_log error: ' . $e->getMessage() );
		}
	}
}

/* -------------------------
   Error/Exception/Shutdown handlers
   ------------------------- */

if ( ! function_exists( 'bzj_mycredd_error_handler' ) ) {
	function bzj_mycredd_error_handler( $errno, $errstr, $errfile, $errline ) {
		$levels = array( E_ERROR => 'ERROR', E_WARNING => 'WARN', E_NOTICE => 'INFO', E_USER_ERROR => 'ERROR', E_USER_WARNING => 'WARN', E_USER_NOTICE => 'INFO' );
		$level = isset( $levels[ $errno ] ) ? $levels[ $errno ] : 'ERROR';
		bzj_mycredd_log( $level, 'PHP error', array( 'errno' => $errno, 'message' => $errstr, 'file' => $errfile, 'line' => $errline ) );
		return false; // continue normal error handler after logging
	}
	set_error_handler( 'bzj_mycredd_error_handler' );
}

if ( ! function_exists( 'bzj_mycredd_exception_handler' ) ) {
	function bzj_mycredd_exception_handler( $e ) {
		bzj_mycredd_log( 'ERROR', 'Uncaught exception', array( 'message' => $e->getMessage(), 'file' => $e->getFile(), 'line' => $e->getLine(), 'trace' => $e->getTrace() ) );
	}
	set_exception_handler( 'bzj_mycredd_exception_handler' );
}

if ( ! function_exists( 'bzj_mycredd_shutdown_handler' ) ) {
	function bzj_mycredd_shutdown_handler() {
		$err = error_get_last();
		if ( $err ) {
			bzj_mycredd_log( 'ERROR', 'Shutdown due to fatal error', $err );
		}
	}
	register_shutdown_function( 'bzj_mycredd_shutdown_handler' );
}

/* -------------------------
   Helpers
   ------------------------- */

if ( ! function_exists( 'bzj_mycredd_compact_backtrace' ) ) {
	function bzj_mycredd_compact_backtrace( $max = 12 ) {
		$trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		$ret = array();
		$skip = 1;
		$len = count( $trace );
		for ( $i = $skip; $i < min( $skip + $max, $len ); $i++ ) {
			$frame = $trace[ $i ];
			$ret[] = array(
				'function' => isset( $frame['function'] ) ? $frame['function'] : '',
				'class'    => isset( $frame['class'] ) ? $frame['class'] : '',
				'file'     => isset( $frame['file'] ) ? wp_basename( $frame['file'] ) : '',
				'line'     => isset( $frame['line'] ) ? $frame['line'] : 0,
			);
		}
		return $ret;
	}
}

if ( ! function_exists( 'bzj_mycredd_get_decimals' ) ) {
	function bzj_mycredd_get_decimals( $mycred_obj_or_key = null ) {
		$default = 4;
		// allow override
		if ( defined( 'BZJ_MYCREDD_FORCED_DECIMALS' ) && is_int( BZJ_MYCREDD_FORCED_DECIMALS ) ) {
			return BZJ_MYCREDD_FORCED_DECIMALS;
		}
		try {
			if ( empty( $mycred_obj_or_key ) ) {
				if ( function_exists( 'mycred' ) ) {
					$mycred = mycred();
				} else {
					return $default;
				}
			} elseif ( is_string( $mycred_obj_or_key ) ) {
				$mycred = mycred( $mycred_obj_or_key );
			} elseif ( is_object( $mycred_obj_or_key ) ) {
				$mycred = $mycred_obj_or_key;
			} else {
				return $default;
			}
			if ( is_object( $mycred ) ) {
				// try format property
				if ( isset( $mycred->format ) && is_array( $mycred->format ) && isset( $mycred->format['decimals'] ) ) {
					return (int) $mycred->format['decimals'];
				}
				// fallback: try core property
				if ( isset( $mycred->core ) && is_array( $mycred->core ) && isset( $mycred->core['format']['decimals'] ) ) {
					return (int) $mycred->core['format']['decimals'];
				}
			}
		} catch ( Exception $e ) {
			bzj_mycredd_log( 'ERROR', 'bzj_mycredd_get_decimals error: ' . $e->getMessage() );
		}
		return $default;
	}
}

if ( ! function_exists( 'bzj_mycredd_has_decimals' ) ) {
	function bzj_mycredd_has_decimals( $value ) {
		if ( ! is_numeric( $value ) ) return false;
		$str = (string) $value;
		return ( strpos( $str, '.' ) !== false );
	}
}

if ( ! function_exists( 'bzj_mycredd_format_fixed' ) ) {
	function bzj_mycredd_format_fixed( $value, $decimals ) {
		if ( ! is_numeric( $value ) ) return '0';
		$dec = max( 0, (int) $decimals );
		$fmt = sprintf( '%%.%df', $dec );
		$val = sprintf( $fmt, (float) $value );
		$val = str_replace( ',', '', $val );
		return $val;
	}
}

/* -------------------------
   DB & Schema inspection
   ------------------------- */

if ( ! function_exists( 'bzj_mycredd_inspect_db_schema' ) ) {
	function bzj_mycredd_inspect_db_schema() {
		if ( ! function_exists( 'mycred' ) ) {
			bzj_mycredd_log( 'ERROR', 'myCRED not available for DB inspection.' );
			return;
		}
		global $wpdb;
		try {
			$mc = mycred();
			$tbl = isset( $mc->log_table ) ? $mc->log_table : null;
			if ( empty( $tbl ) ) {
				bzj_mycredd_log( 'ERROR', 'myCRED log_table not found on object.' );
				return;
			}
			$cols = $wpdb->get_results( "SHOW COLUMNS FROM {$tbl}" );
			if ( empty( $cols ) ) {
				bzj_mycredd_log( 'ERROR', "Could not read columns for myCRED log table: {$tbl}" );
				return;
			}
			$creds_col = null;
			foreach ( $cols as $c ) {
				if ( isset( $c->Field ) && $c->Field === 'creds' ) {
					$creds_col = $c;
					break;
				}
			}
			$decimals_expected = bzj_mycredd_get_decimals( $mc );
			$analysis = array( 'table' => $tbl, 'expected_decimals' => $decimals_expected );
			if ( ! $creds_col ) {
				bzj_mycredd_log( 'ERROR', "myCRED log table {$tbl} does not have a 'creds' column.", $analysis );
				return;
			}
			$type = isset( $creds_col->Type ) ? $creds_col->Type : '';
			$analysis['creds_type'] = $type;
			$create = $wpdb->get_row( "SHOW CREATE TABLE {$tbl}", ARRAY_A );
			if ( ! empty( $create ) && isset( $create['Create Table'] ) ) {
				$analysis['create'] = $create['Create Table'];
			}
			// Detection
			if ( stripos( $type, 'decimal' ) !== false ) {
				preg_match( '/decimal\s*\(\s*(\d+)\s*,\s*(\d+)\s*\)/i', $type, $m );
				if ( ! empty( $m ) && isset( $m[2] ) ) {
					$col_decimals = (int) $m[2];
					$analysis['col_decimals'] = $col_decimals;
					if ( $col_decimals < $decimals_expected ) {
						$analysis['warning'] = sprintf( "creds column supports %d decimals but point type expects %d.", $col_decimals, $decimals_expected );
						bzj_mycredd_log( 'ERROR', 'DB precision mismatch', $analysis );
						return;
					}
				}
				bzj_mycredd_log( 'INFO', 'DB creds column is DECIMAL and appears to support required precision.', $analysis );
				return;
			} elseif ( preg_match( '/int/i', $type ) ) {
				$analysis['warning'] = 'creds column is integer - cannot store decimals.';
				bzj_mycredd_log( 'ERROR', 'DB integer creds column detected', $analysis );
				return;
			} elseif ( stripos( $type, 'float' ) !== false || stripos( $type, 'double' ) !== false ) {
				$analysis['note'] = 'creds column is float/double - binary precision may occur; DECIMAL recommended.';
				bzj_mycredd_log( 'WARN', 'DB float/double creds column', $analysis );
				return;
			}
			bzj_mycredd_log( 'INFO', 'myCRED log table creds column appears to support decimals (unknown type).', $analysis );
		} catch ( Exception $e ) {
			bzj_mycredd_log( 'ERROR', 'bzj_mycredd_inspect_db_schema exception: ' . $e->getMessage() );
		}
	}
}

/* -------------------------
   Hook Discovery
   ------------------------- */

if ( ! function_exists( 'bzj_mycredd_discover_mycred_hooks' ) ) {
	function bzj_mycredd_discover_mycred_hooks() {
		global $wp_filter;
		$found = array();
		foreach ( (array) $wp_filter as $hook => $obj ) {
			if ( strpos( $hook, 'mycred' ) === 0 || strpos( $hook, 'myc' ) === 0 ) {
				$callbacks = array();
				if ( is_a( $obj, 'WP_Hook' ) && ! empty( $obj->callbacks ) ) {
					foreach ( $obj->callbacks as $priority => $items ) {
						foreach ( $items as $cb ) {
							$callbacks[] = array(
								'priority' => $priority,
								'function' => is_array( $cb['function'] ) ? ( ( is_object( $cb['function'][0] ) ? get_class( $cb['function'][0] ) : $cb['function'][0] ) . '::' . $cb['function'][1] ) : ( is_string( $cb['function'] ) ? $cb['function'] : 'closure' ),
							);
						}
					}
				}
				$found[ $hook ] = $callbacks;
			}
		}
		bzj_mycredd_log( 'DEBUG', 'Discovered myCRED-related hooks/filters', array( 'count' => count( $found ), 'hooks' => $found ) );
		return $found;
	}
}

/* -------------------------
   SQL monitoring (lightweight)
   ------------------------- */

if ( ! function_exists( 'bzj_mycredd_log_queries' ) ) {
	function bzj_mycredd_log_queries( $query ) {
		try {
			if ( defined( 'BZJ_MYCREDD_LOG_ENABLE' ) && ! BZJ_MYCREDD_LOG_ENABLE ) return $query;
			$l = strtolower( ltrim( $query ) );
			if ( strpos( $l, 'insert into' ) !== 0 ) return $query;
			if ( ! function_exists( 'mycred' ) ) return $query;
			$mc = mycred();
			if ( empty( $mc ) || empty( $mc->log_table ) ) return $query;
			$tbl = $mc->log_table;
			if ( stripos( $query, $tbl ) !== false ) {
				$snippet = ( strlen( $query ) > 1600 ) ? substr( $query, 0, 1600 ) . '...[truncated]' : $query;
				bzj_mycredd_log( 'DEBUG', 'SQL INSERT into myCRED log table', array( 'query' => $snippet ) );
			}
		} catch ( Exception $e ) {
			bzj_mycredd_log( 'ERROR', 'bzj_mycredd_log_queries exception: ' . $e->getMessage() );
		}
		return $query;
	}
	add_filter( 'query', 'bzj_mycredd_log_queries', 1, 1 );
}

/* -------------------------
   Trace add_creds (incoming)
   ------------------------- */

if ( ! function_exists( 'bzj_mycredd_trace_add_creds' ) ) {
	add_action( 'mycred_add_creds', 'bzj_mycredd_trace_add_creds', 5, 7 );
	function bzj_mycredd_trace_add_creds( $ref = null, $user_id = null, $amount = null, $entry = null, $ref_id = null, $data = null, $type = null ) {
		try {
			if ( ! function_exists( 'mycred' ) ) return;
			$trace_id = uniqid( 'bzj_trace_', true );
			$trace = array(
				'trace_id' => $trace_id,
				'ref' => $ref,
				'user_id' => $user_id,
				'amount_in' => $amount,
				'type' => $type,
				'time' => current_time( 'mysql' ),
				'backtrace' => bzj_mycredd_compact_backtrace( 12 ),
			);
			set_transient( $trace_id, $trace, HOUR_IN_SECONDS );
			bzj_mycredd_log( 'DEBUG', 'add_creds trace start', $trace );
			if ( ! isset( $GLOBALS['bzj_mycredd_current_traces'] ) || ! is_array( $GLOBALS['bzj_mycredd_current_traces'] ) ) {
				$GLOBALS['bzj_mycredd_current_traces'] = array();
			}
			$GLOBALS['bzj_mycredd_current_traces'][] = $trace_id;
		} catch ( Exception $e ) {
			bzj_mycredd_log( 'ERROR', 'bzj_mycredd_trace_add_creds exception: ' . $e->getMessage() );
		}
	}
}

/* -------------------------
   mycred_pre_add_to_log interception (if available)
   ------------------------- */

if ( ! function_exists( 'bzj_mycredd_preserve_before_insert' ) ) {
	add_filter( 'mycred_pre_add_to_log', 'bzj_mycredd_preserve_before_insert', 5, 2 );
	function bzj_mycredd_preserve_before_insert( $insert_array, $mycred_obj ) {
		try {
			if ( ! is_array( $insert_array ) || ! isset( $insert_array['creds'] ) ) return $insert_array;
			$orig = $insert_array['creds'];
			$context = array( 'ref' => isset( $insert_array['ref'] ) ? $insert_array['ref'] : '(unknown)', 'user_id' => isset( $insert_array['user_id'] ) ? $insert_array['user_id'] : '(unknown)' );
			if ( ! is_numeric( $orig ) ) {
				bzj_mycredd_log( 'INFO', 'pre_add_to_log: non-numeric creds', array_merge( $context, array( 'creds' => $orig, 'type' => gettype( $orig ) ) ) );
				return $insert_array;
			}
			$decimals = bzj_mycredd_get_decimals( $mycred_obj );
			$formatted = bzj_mycredd_format_fixed( $orig, $decimals );
			$has_dec = bzj_mycredd_has_decimals( $orig );
			bzj_mycredd_log( 'DEBUG', 'pre_add_to_log detected creds', array_merge( $context, array( 'orig' => (string)$orig, 'formatted' => $formatted, 'decimals' => $decimals, 'has_decimals' => $has_dec ) ) );
			if ( BZJ_MYCREDD_COMPAT_MODE ) {
				// Replace with formatted numeric string for DB insert attempt (for testing only).
				$insert_array['creds'] = $formatted;
				bzj_mycredd_log( 'INFO', 'pre_add_to_log: replaced creds (compat mode)', array_merge( $context, array( 'replaced_with' => $formatted ) ) );
			}
		} catch ( Exception $e ) {
			bzj_mycredd_log( 'ERROR', 'bzj_mycredd_preserve_before_insert exception: ' . $e->getMessage() );
		}
		return $insert_array;
	}
}

/* -------------------------
   mycred_type_number filter instrumentation and optional fix
   ------------------------- */

if ( ! function_exists( 'bzj_mycredd_mycred_type_number_filter' ) ) {
	add_filter( 'mycred_type_number', 'bzj_mycredd_mycred_type_number_filter', 10, 3 );
	function bzj_mycredd_mycred_type_number_filter( $result, $number, $mycred_obj ) {
		try {
			$context = array();
			$context['incoming'] = ( is_scalar( $number ) ? (string) $number : json_encode( $number ) );
			$context['result']   = ( is_scalar( $result ) ? (string) $result : json_encode( $result ) );
			$context['type']     = is_object( $mycred_obj ) && isset( $mycred_obj->cred_id ) ? $mycred_obj->cred_id : '(unknown)';
			$context['decimals_config'] = bzj_mycredd_get_decimals( $mycred_obj );
			$context['backtrace'] = bzj_mycredd_compact_backtrace( 12 );
			bzj_mycredd_log( 'DEBUG', 'mycred_type_number invoked', $context );
			$incoming_has_dec = is_numeric( $number ) && bzj_mycredd_has_decimals( $number );
			$result_is_zero_or_int = ( is_numeric( $result ) && ( intval( $result ) == 0 ) && ( (float)$result == 0.0 || (int)$result == 0 ) );
			if ( BZJ_MYCREDD_COMPAT_MODE && $incoming_has_dec && $result_is_zero_or_int ) {
				$dec = bzj_mycredd_get_decimals( $mycred_obj );
				$fixed = bzj_mycredd_format_fixed( $number, $dec );
				bzj_mycredd_log( 'INFO', 'mycred_type_number compat fix applied', array( 'incoming' => (string)$number, 'original_result' => (string)$result, 'fixed' => $fixed, 'decimals' => $dec ) );
				// Return the fixed numeric string (intended to flow into DB write).
				return $fixed;
			}
		} catch ( Exception $e ) {
			bzj_mycredd_log( 'ERROR', 'bzj_mycredd_mycred_type_number_filter exception: ' . $e->getMessage() );
		}
		return $result;
	}
}

/* -------------------------
   Final trace finish hook (if available)
   ------------------------- */

if ( ! function_exists( 'bzj_mycredd_trace_finish' ) ) {
	add_action( 'mycred_add_finished', 'bzj_mycredd_trace_finish', 20, 3 );
	function bzj_mycredd_trace_finish( $execute = null, $run_this = null, $mycred_obj = null ) {
		try {
			if ( empty( $GLOBALS['bzj_mycredd_current_traces'] ) ) return;
			$trace_id = array_pop( $GLOBALS['bzj_mycredd_current_traces'] );
			$trace = get_transient( $trace_id );
			if ( $trace === false ) return;
			$final = array(
				'run_this' => is_array( $run_this ) ? $run_this : null,
				'execute' => $execute,
				'backtrace' => bzj_mycredd_compact_backtrace( 12 ),
			);
			bzj_mycredd_log( 'DEBUG', 'add_creds trace finish', array( 'trace_id' => $trace_id, 'final' => $final ) );
			delete_transient( $trace_id );
		} catch ( Exception $e ) {
			bzj_mycredd_log( 'ERROR', 'bzj_mycredd_trace_finish exception: ' . $e->getMessage() );
		}
	}
}

/* -------------------------
   Warm-up / diagnostics on myCRED ready
   ------------------------- */

add_action( 'muplugins_loaded', function() {
	bzj_mycredd_log( 'INFO', 'bzj-mycred-decimal-diagnostic loaded (MU plugin).' );
}, 1 );

add_action( 'mycred_ready', 'bzj_mycredd_on_mycred_ready', 1 );
function bzj_mycredd_on_mycred_ready() {
	bzj_mycredd_log( 'INFO', 'bzj-mycred-decimal-diagnostic: myCRED ready; running diagnostics.' );
	// discovery
	bzj_mycredd_discover_mycred_hooks();
	// types
	if ( function_exists( 'mycred_get_types' ) ) {
		try {
			$types = mycred_get_types( true );
			bzj_mycredd_log( 'DEBUG', 'Detected myCRED point types', array( 'count' => count( $types ), 'types' => $types ) );
		} catch ( Exception $e ) {
			bzj_mycredd_log( 'ERROR', 'Error retrieving point types: ' . $e->getMessage() );
		}
	}
	// DB schema
	bzj_mycredd_inspect_db_schema();
	// sample number() call to generate a trace
	if ( function_exists( 'mycred' ) ) {
		$mc = mycred();
		if ( is_object( $mc ) && method_exists( $mc, 'number' ) ) {
			try {
				$sample = '0.0700';
				$r = $mc->number( $sample );
				bzj_mycredd_log( 'DEBUG', 'Sample mycred->number() call', array( 'sample' => $sample, 'result' => ( is_scalar( $r ) ? (string)$r : json_encode( $r ) ) ) );
			} catch ( Exception $e ) {
				bzj_mycredd_log( 'ERROR', 'Sample mycred->number() call failed: ' . $e->getMessage() );
			}
		}
	}
	bzj_mycredd_log( 'INFO', 'bzj-mycred-decimal-diagnostic initialization complete.' );
}
?>