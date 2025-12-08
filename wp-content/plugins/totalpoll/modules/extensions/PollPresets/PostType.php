<?php
namespace TotalPoll\Modules\Extensions\PollPresets;
! defined( 'ABSPATH' ) && exit();



use WP_Error;
use WP_Post_Type;

class PostType extends \TotalPollVendors\TotalCore\PostTypes\PostType {

	public function __construct() {
		parent::__construct();
		add_action( 'totalpoll/actions/activated', [ $this, 'capabilities' ] );
	}

	/**
	 * Register post type.
	 *
	 * @return WP_Error|WP_Post_Type
	 */
	public function register() {
		define( 'TP_PRESET_CPT_NAME', $this->getName() );

		return parent::register();
	}

	public function getName() {
		return 'poll_preset';
	}

	/**
	 * Capabilities mapping.
	 */
	public function capabilities() {
		$map = [
			'edit_preset'   => [ 'administrator', 'editor', 'author', 'contributor' ],
			'read_preset'   => [ 'administrator', 'editor', 'author', 'contributor' ],
			'delete_preset' => [ 'administrator', 'editor', 'author', 'contributor' ],

			'edit_presets'    => [ 'administrator', 'editor', 'author', 'contributor' ],
			'delete_presets'  => [ 'administrator', 'editor', 'author', 'contributor' ],
			'publish_presets' => [ 'administrator', 'editor', 'author' ],

			'edit_others_presets'   => [ 'administrator', 'editor' ],
			'delete_others_presets' => [ 'administrator', 'editor' ],

			'edit_published_presets'   => [ 'administrator', 'editor', 'author' ],
			'delete_published_presets' => [ 'administrator', 'editor', 'author' ],

			'read_private_presets'   => [ 'administrator', 'editor' ],
			'edit_private_presets'   => [ 'administrator', 'editor' ],
			'delete_private_presets' => [ 'administrator', 'editor' ],
			'create_presets'         => [ 'administrator', 'editor', 'author', 'contributor' ],
		];

		foreach ( $map as $capability => $roles ):
			foreach ( $roles as $role ):
				$role = get_role( $role );
				if ( $role ):
					$role->add_cap( $capability );
				endif;
			endforeach;
		endforeach;
	}

	public function getArguments() {
		/**
		 * Filters the arguments of poll CPT.
		 *
		 * @param array $args CPT arguments.
		 *
		 * @return array
		 * @since 4.0.0
		 */
		return [
			'labels' => [
				'name'               => esc_html__( 'Presets', 'totalpoll' ),
				'singular_name'      => esc_html__( 'Preset', 'totalpoll' ),
				'add_new'            => esc_html__( 'Create Preset', 'totalpoll' ),
				'add_new_item'       => esc_html__( 'New Preset', 'totalpoll' ),
				'edit_item'          => esc_html__( 'Edit Preset', 'totalpoll' ),
				'new_item'           => esc_html__( 'New Preset', 'totalpoll' ),
				'all_items'          => esc_html__( 'Presets', 'totalpoll' ),
				'view_item'          => esc_html__( 'View Preset', 'totalpoll' ),
				'search_items'       => esc_html__( 'Search Presets', 'totalpoll' ),
				'not_found'          => esc_html__( 'No presets found', 'totalpoll' ),
				'not_found_in_trash' => esc_html__( 'No presets found in Trash', 'totalpoll' ),
				'menu_name'          => esc_html__( 'TotalPoll', 'totalpoll' ),
			],

			'public'             => false,
			'publicly_queryable' => false,
			'show_ui'            => true,
			'show_in_menu'       => sprintf( 'edit.php?post_type=%s', TotalPoll('polls.cpt')->getName() ),
			'query_var'          => true,
			'capabilities'       => [
				'edit_post'          => 'edit_preset',
				'read_post'          => 'read_preset',
				'delete_post'        => 'delete_preset',
				'edit_posts'         => 'edit_presets',
				'edit_others_posts'  => 'edit_others_presets',
				'publish_posts'      => 'publish_presets',
				'read_private_posts' => 'read_private_presets',
				'create_posts'       => 'create_presets',
			],
			'map_meta_cap'       => true,
			'has_archive'        => false,
			'menu_position'      => null,
			'hierarchical'       => false,
			'show_in_rest'       => false,
			'supports'           => [ 'title' ]
		];
	}

	public function getMessages( $post ) {
		return [
			0  => '',
			1  => esc_html__( 'Preset updated.', 'totalpoll'),
			2  => esc_html__( 'Custom field updated.', 'totalpoll' ),
			3  => esc_html__( 'Custom field deleted.', 'totalpoll' ),
			4  => esc_html__( 'Preset updated.', 'totalpoll' ),
			5  => isset( $_GET['revision'] ) ? sprintf( esc_html__( 'Preset restored to revision from %s', 'totalpoll' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => esc_html__( 'Preset published.', 'totalpoll' ),
			7  => esc_html__( 'Preset saved.', 'totalpoll' ),
			8  =>  esc_html__( 'Preset submitted', 'totalpoll' )
		];
	}
}
