<?php

namespace TotalPoll\Modules\Extensions\PollPresets;
! defined( 'ABSPATH' ) && exit();


use TotalPollVendors\TotalCore\Contracts\Http\Request;
use TotalPollVendors\TotalCore\Foundation\Environment;
use TotalPollVendors\TotalCore\Helpers\Tracking;
use WP_Post;

/**
 * Class Listing
 * @package TotalPoll\Admin\Poll
 */
class Listing {
	/**
	 * @var Request
	 */
	protected $request;
	/**
	 * @var Repository
	 */
	protected $presetRepository;
	/**
	 * @var Environment
	 */
	protected $env;

	/**
	 * @var int
	 */
	protected $defaultPreset = 0;

	/**
	 * Listing constructor.
	 *
	 * @param Request $request
	 * @param Repository $pollRepository
	 * @param $env
	 */
	public function __construct( Request $request, Repository $pollRepository, $env ) {
		$this->request          = $request;
		$this->presetRepository = $pollRepository;
		$this->env              = $env;
		$this->defaultPreset    = (int) get_option( 'totalpoll_default_preset', 0 );

		// Assets
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ] );

		// States
		add_filter( 'display_post_states', [ $this, 'states' ], 10, 2 );

		// Columns
		add_filter( 'manage_poll_preset_posts_columns', [ $this, 'columns' ] );

		// Columns content
		add_action( 'manage_poll_preset_posts_custom_column', [ $this, 'columnsContent' ], 10, 2 );
		add_filter( 'manage_edit-poll_preset_sortable_columns', [ $this, 'columnsSortable' ], 10, 1 );

		// Actions
		add_filter( 'post_row_actions', [ $this, 'actions' ], 10, 2 );

		// Scope
		add_filter( 'pre_get_posts', [ $this, 'scope' ] );

		add_action( 'after_delete_post', [ $this, 'afterPresetDeleted' ], 10, 2 );

		add_action( 'manage_posts_extra_tablenav', [ $this, 'bulkButton' ], 10, 2 );

		Tracking::trackScreens( 'presets' );
	}



	public function bulkButton() {
		global $post_type;

		if ( $post_type !== TP_PRESET_CPT_NAME ) {
			return;
		}

		$url = admin_url( 'edit.php?post_type=' . TP_POLL_CPT_NAME . '&page=batch_preset' );
		?>
        <a class="button" href="<?php echo esc_attr( $url ); ?>"><?php esc_html_e( 'Bulk Apply', 'totalpoll' ) ?></a>
		<?php
	}

	/**
	 * @param int $id
	 * @param WP_Post $post
	 */
	public function afterPresetDeleted( $id, $post ) {

		if ( $post->post_type === TP_PRESET_CPT_NAME ) {

			$polls = get_posts( [
				'fields'           => 'ids',
				'suppress_filters' => true,
				'post_type'        => TP_POLL_CPT_NAME,
				'posts_per_page'   => - 1,
				'meta_query'       => array(
					array(
						'key'   => 'poll_preset',
						'value' => $id,
					),
				),
			] );

			foreach ( $polls as $poll ) {
				update_post_meta( $poll, 'poll_preset', 0 );
			}
		}
	}

	/**
	 * Page assets.
	 */
	public function assets() {
		/**
		 * @asset-style totalpoll-admin-poll-listing
		 */
		wp_enqueue_style( 'totalpoll-admin-poll-listing' );
	}

	/**
	 * Columns.
	 *
	 * @param array $originalColumns
	 *
	 * @filter-callback manage_poll_posts_columns
	 * @return array
	 */
	public function columns( $originalColumns ) {
		$columns = [
			'cb'    => '<input type="checkbox" />',
			'title' => esc_html__( 'Title' ),
			'type'  => esc_html__( 'Type' ),
			'date'  => esc_html__( 'Date' ),
		];

		/**
		 * Filters the list of columns in polls listing.
		 *
		 * @param array $columns Array of columns.
		 * @param array $originalColumns Array of original columns.
		 *
		 * @return array
		 * @since 4.0.0
		 */
		return apply_filters( 'totalpoll/filters/admin/presets/listing/columns', $columns, $originalColumns );
	}

	/**
	 * Columns content.
	 *
	 * @param $column
	 * @param $id
	 *
	 * @action-callback manage_preset_posts_custom_column
	 * @return void
	 */
	public function columnsContent( $column, $id ) {

		if ( $column !== 'type' ) {
			return;
		}

		$merging = get_post_meta( $id, 'totalpoll_preset_type', true );

		if ( empty( $merging ) || $merging === 'soft' ) {
			echo esc_html__( 'Soft', 'totalpoll' );
		} elseif ( $merging === 'hard' ) {
			echo esc_html__( 'Hard', 'totalpoll' );
		}
	}

	public function columnsSortable( $columns ) {
		$columns['id']   = 'id';
		$columns['type'] = 'type';

		return $columns;
	}

	/**
	 * Inline actions.
	 *
	 * @param array $actions
	 * @param WP_Post $post
	 *
	 * @filter-callback post_row_actions
	 * @return array
	 */
	public function actions( array $actions, WP_Post $post ) {

		unset( $actions["inline hide-if-no-js"] );

		if ( current_user_can( 'manage_options' ) && $this->defaultPreset !== $post->ID ) :
			$actions['default'] = sprintf(
				'<a href="%s">%s</a>',
				$this->presetRepository->getDefaultUrl( $post->ID ),
				esc_html( esc_html__( 'Make as default preset', 'totalpoll' ) )
			);
		endif;

		if ( current_user_can( 'edit_presets' ) ) :

			$actions['create'] = sprintf( '<a href="%s">%s</a>',
				$this->presetRepository->getCreatePollUrl( $post->ID ),
				esc_html( esc_html__( 'Create poll from this preset', 'totalpoll' ) )
			);

		endif;

		/**
		 * Filters the list of available actions in polls listing (under each poll).
		 *
		 * @param array $actions Array of actions [id => url].
		 * @param WP_Post $post Poll post.
		 *
		 * @return array
		 * @since 4.0.0
		 */
		return apply_filters( 'totalpoll/filters/admin/presets/listing/actions', $actions, $post );
	}

	/**
	 * @param array $states
	 * @param WP_Post $post
	 *
	 * @return array
	 */
	public function states( $states, WP_Post $post ) {

		if ( $this->defaultPreset === $post->ID ) {
			$states[] = esc_html__( 'Default', 'totalpoll' );
		}

		/**
		 * Filters the list of states actions in polls listing (beside each title).
		 *
		 * @param array $states Array of states [id => label].
		 *
		 * @return array
		 * @since 4.0.0
		 */
		return apply_filters( 'totalpoll/filters/admin/presets/listing/states', $states, $post );
	}

	/**
	 * @param $query
	 *
	 * @return mixed
	 */
	public function scope( $query ) {
		if ( ! current_user_can( 'edit_others_presets' ) ):
			$query->set( 'author', get_current_user_id() );
		endif;

		return $query;
	}

}
