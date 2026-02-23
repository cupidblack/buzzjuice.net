<?php
/**
 * REST API Rules controller
 *
 * @package YITH\AutomaticRoleChanger\RestApi
 */

defined( 'ABSPATH' ) || exit;

/**
 * REST API Global Availability Rules controller class.
 *
 * @package YITH\Booking\RestApi
 */
class YITH_WCARC_REST_Rules_Controller extends WP_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'yith-automatic-role-changer/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'rules';

	/**
	 * Register the routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/apply',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'apply_rules' ),
					'permission_callback' => array( $this, 'apply_rules_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\w+]+)',
			array(
				'args'   => array(
					'id' => array(
						'type' => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/order_ids',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_order_ids' ),
					'permission_callback' => array( $this, 'get_order_ids_permissions_check' ),
				),
			)
		);
	}

	/**
	 * Retrieves order stats.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_items( $request ) {
		$rules = get_option( 'ywarc_rules' );
		$items = array();

		foreach ( $rules as $rule_id => $item ) {
			$item['id'] = $rule_id;
			$data       = $this->prepare_item_for_response( $item, $request );
			$items[]    = $this->prepare_response_for_collection( $data );
		}

		return rest_ensure_response( $items );
	}

	private function parse_rule_data( $data ) {

		ob_start();

		$rule = array(
			'title'               => $data['title'] ?? '',
			'rule_type'           => $data['rule_type'] ?? 'add',
			'role_selected'       => $data['role_selected'] ?? array(),
			'replace_roles'       => ! empty( $data['replace_roles'] ) ? array( $data['replace_roles'][0] ?? '', $data['replace_roles'][1] ?? '' ) : '',
			'radio_group'         => $data['radio_group'] ?? 'product',
			'product_selected'    => $data['product_selected'] ?? '',
			'price_range_from'    => $data['price_range_from'] ?? '',
			'price_range_to'      => $data['price_range_to'] ?? '',
			'categories_selected' => array_filter( array_map( 'absint', (array) $data['categories_selected'] ?? array() ) ),
			'tags_selected'       => array_filter( array_map( 'absint', (array) $data['tags_selected'] ?? array() ) ),
			'date_from'           => $data['date_from'] ?? null,
			'date_to'             => $data['date_to'] ?? null,
			'duration'            => absint( $data['duration'] ?? 0 ),
			'role_filter'         => $data['role_filter'] ?? array(),
		);

		if ( $rule['rule_type'] !== 'add' ) {
			$rule['role_selected'] = array();
		}

		if ( $rule['rule_type'] !== 'replace' ) {
			$rule['replace_roles'] = array();
		}

		if ( $rule['radio_group'] !== 'product' ) {
			$rule['product_selected'] = array();
		}

		if ( ! in_array( $rule['radio_group'], array( 'range', 'overall' ), true ) ) {
			$rule['price_range_from'] = '';
			$rule['price_range_to']   = '';
		}

		if ( $rule['radio_group'] !== 'taxonomy' ) {
			$rule['categories_selected'] = array();
			$rule['tags_selected']       = array();
		}

		if ( isset( $data['id'] ) ) {
			$rule['id'] = $data['id'];
		}

		return $rule;
	}

	/**
	 * Save one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array|null
	 */
	private function save_rule_from_request( $request ) {
		$rule_id = $request['id'] ?? null;
		$rule    = null;

		if ( $rule_id ) {
			$rule = $this->parse_rule_data( $request );

			$rule = apply_filters( 'ywarc_save_rule_array', $rule );

			$rules             = get_option( 'ywarc_rules' );
			$rules[ $rule_id ] = $rule;
			update_option( 'ywarc_rules', $rules );

			$rule['id'] = $rule_id;
		}

		return $rule;
	}

	/**
	 * Creates one item from the collection.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function create_item( $request ) {
		$request['id'] = uniqid();
		$rule          = $this->save_rule_from_request( $request );

		return $this->prepare_item_for_response( $rule, $request );
	}

	/**
	 * Update item
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function update_item( $request ) {
		$rule = $this->save_rule_from_request( $request );

		return $this->prepare_item_for_response( $rule, $request );
	}

	/**
	 * Delete item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function delete_item( $request ) {
		$id = $request['id'];

		$rules = get_option( 'ywarc_rules' );
		$rule  = $rules[ $id ] ?? null;

		if ( isset( $rules[ $id ] ) ) {
			unset( $rules[ $id ] );
			update_option( 'ywarc_rules', $rules );
		}

		return $this->prepare_item_for_response( $rule, $request );
	}

	/**
	 * Prepare a single register output for response.
	 *
	 * @param array|null      $rule    The rule object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response|WP_Error $data
	 */
	public function prepare_item_for_response( $rule, $request ) {
		if ( $rule ) {
			$rule                = $this->parse_rule_data( $rule );
			$rule['title']       = sanitize_text_field( $rule['title'] );
			$rule['date_from']   = ! empty( $rule['date_from'] ) ? $rule['date_from'] : null;
			$rule['date_to']     = ! empty( $rule['date_to'] ) ? $rule['date_to'] : null;
			$rule['radio_group'] = ! empty( $rule['radio_group'] ) ? $rule['radio_group'] : 'product';
			$rule['role_filter'] = ! empty( $rule['role_filter'] ) ? $rule['role_filter'] : null;

			return rest_ensure_response( $rule );
		} else {
			return new WP_Error( 'yith_automatic_role_changer_rule_not_found', __( 'Rule not found!', 'yith-automatic-role-changer-for-woocommerce' ), array( 'status' => 404 ) );
		}
	}

	/**
	 * Apply rules.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function apply_rules( $request ) {
		$order_ids = $request['order_ids'] ?? false;
		if ( $order_ids ) {
			foreach ( $order_ids as $order_id ) {
				yith_role_changer()->set_roles->search_for_rules( $order_id );
			}

			return rest_ensure_response( array( 'order_ids' => $order_ids, 'success' => true ) );
		} else {
			$required = array( 'order_ids' );
			$message  = sprintf(
			// translators: %s is the list of missing params.
				__( 'The following parameters are required: %.', 'yith-automatic-role-changer-for-woocommerce' ),
				'"' . implode( '", "', $required ) . '"'
			);

			return new WP_Error( 'yith_automatic_role_changer_rest_missing_params', $message, array( 'status' => 400 ) );
		}
	}

	/**
	 * Get order ids.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
	 */
	public function get_order_ids( $request ) {
		$date_filter = $request['date_filter'] ?? '';
		$from        = $request['from'] ?? '';
		$to          = $request['to'] ?? '';
		$date_value  = '';
		if ( $from && $to ) {
			$date_value = $from . '...' . $to;
		} elseif ( $from && ! $to ) {
			$date_value = '>=' . $from;
		} elseif ( ! $from && $to ) {
			$date_value = '<=' . $to;
		}

		$args = array(
			'type'   => 'shop_order',
			'return' => 'ids',
			'limit'  => -1,
			'order'  => 'ASC',
		);

		if ( $date_filter && $date_value ) {
			$args[ $date_filter ] = $date_value;
		}

		$order_ids = wc_get_orders( $args );

		return rest_ensure_response( $order_ids );
	}

	/**
	 * Checks if a given request has access to read items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_items_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'yith_automatic_role_changer_rest_cannot_view_rules', __( 'Sorry, you cannot view this resource.', 'yith-automatic-role-changer-for-woocommerce' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Checks if a given request has access to create items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'yith_automatic_role_changer_rest_cannot_create_rules', __( 'Sorry, you cannot create this resource.', 'yith-automatic-role-changer-for-woocommerce' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Checks if a given request has access to update items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function update_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'yith_automatic_role_changer_rest_cannot_update_rules', __( 'Sorry, you cannot update this resource.', 'yith-automatic-role-changer-for-woocommerce' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Checks if a given request has access to delete items.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function delete_item_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'yith_automatic_role_changer_rest_cannot_delete_rules', __( 'Sorry, you cannot delete this resource.', 'yith-automatic-role-changer-for-woocommerce' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Checks if a given request has access to apply rules.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function apply_rules_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'yith_automatic_role_changer_rest_cannot_apply_rules', __( 'Sorry, you cannot apply rules.', 'yith-automatic-role-changer-for-woocommerce' ), array( 'status' => 403 ) );
		}

		return true;
	}

	/**
	 * Checks if a given request has access to get order ids.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
	 */
	public function get_order_ids_permissions_check( $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error( 'yith_automatic_role_changer_rest_cannot_view_order_ids', __( 'Sorry, you cannot view this resource.', 'yith-automatic-role-changer-for-woocommerce' ), array( 'status' => 403 ) );
		}

		return true;
	}
}
