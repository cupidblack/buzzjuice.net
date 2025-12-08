<?php

namespace TotalPoll\Modules\Extensions\PollPresets;
! defined( 'ABSPATH' ) && exit();


use TotalPoll\Contracts\Preset\Model as ModelContract;
use TotalPollVendors\TotalCore\Helpers\Arrays;
use TotalPollVendors\TotalCore\Http\Request;
use WP_Post;
use WP_Query;
use wpdb;

/**
 * Poll repository.
 * @package TotalPoll\Poll
 * @since   4.0.0
 */
class Repository implements \TotalPoll\Contracts\Preset\Repository {
	/**
	 * @var Request $request
	 */
	protected $request;
	/**
	 * @var wpdb $database
	 */
	protected $database;
	/**
	 * @var array $env
	 */
	protected $env;

	/**
	 * Repository constructor.
	 *
	 * @param Request $request
	 * @param wpdb $database
	 * @param array $env
	 */
	public function __construct( Request $request, wpdb $database, $env ) {
		$this->request  = $request;
		$this->database = $database;
		$this->env      = $env;
	}

	/**
	 * Get presets.
	 *
	 * @param $query
	 *
	 * @return ModelContract[]
	 */
	public function get( $query ) {
		$args = Arrays::parse( $query, [
			'page'           => 1,
			'perPage'        => 10,
			'orderBy'        => 'date',
			'orderDirection' => 'DESC',
			'status'         => 'publish',
			'wpQuery'        => [],
		] );

		// Models
		$presetModels = [];
		// Query
		$wpQueryArgs = Arrays::parse(
			[
				'post_type'      => TP_PRESET_CPT_NAME,
				'post_status'    => $args['status'],
				'paged'          => $args['page'],
				'posts_per_page' => $args['perPage'],
				'order'          => $args['orderDirection'],
				'orderby'        => $args['orderBy'],
			],
			$args['wpQuery']
		);

		/**
		 * Filters the list of arguments used for get presets query.
		 *
		 * @param array $wpQueryArgs WP_Query arguments.
		 * @param array $args Arguments.
		 * @param array $query Query.
		 *
		 * @return array
		 * @since 4.0.0
		 */
		$wpQueryArgs = apply_filters( 'totalpoll/filters/presets/get/query', $wpQueryArgs, $args, $query );

		$wpQuery = new WP_Query( $wpQueryArgs );

		// Iterate and convert each row to log model
		foreach ( $wpQuery->get_posts() as $presetPost ):
			$presetModels[] = $this->getById( $presetPost );
		endforeach;

		/**
		 * Filters the results of get presets query.
		 *
		 * @param ModelContract[] $presetModels presets models.
		 * @param array $wpQueryArgs WP_Query arguments.
		 * @param array $args Arguments.
		 * @param array $query Query.
		 *
		 * @return array
		 * @since 4.0.0
		 */
		$presetModels = apply_filters( 'totalpoll/filters/presets/get/results', $presetModels, $wpQueryArgs, $args, $query );

		// Return models
		return $presetModels;
	}

	/**
	 * Get poll.
	 *
	 * @param $presetId
	 *
	 * @return null|Model
	 * @since 4.0.0
	 */
	public function getById( $presetId ) {
		$attributes = [];
		// Post
		if ( $presetId instanceof WP_Post ):
			$attributes['post'] = $presetId;
		else:
			$attributes['post'] = get_post( $presetId );
			if ( ! $attributes['post'] ):
				return null;
			endif;
		endif;

		$attributes['id']          = $attributes['post']->ID;
		$attributes['action']      = $this->request->request( 'totalpoll.action' );
		$attributes['ip']          = $this->request->ip();
		$attributes['currentPage'] = (int) get_query_var( 'current_page', (int) get_query_var( 'paged', 1 ) );
		if ( empty( $attributes['currentPage'] ) ):
			$attributes['currentPage'] = $this->request->request( 'totalpoll.page', 1 );
		endif;

		$container = TotalPoll()->container();

		if ( ! $container->has( "preset.instances.{$attributes['id']}" ) ):

			/**
			 * Filters the poll model attributes after retrieving.
			 *
			 * @param array $attributes Entry attributes.
			 *
			 * @return array
			 * @since 4.0.0
			 */
			$attributes  = apply_filters( 'totalpoll/filters/presets/get/attributes', $attributes );
			$presetModel = new Model( $attributes );

			/**
			 * Filters the poll model after creation and before adding it to container.
			 *
			 * @param ModelContract $model Poll model object.
			 * @param array $attributes Poll attributes.
			 *
			 * @return array
			 * @since 4.0.0
			 */
			$presetModel = apply_filters( 'totalpoll/filters/presets/get/model', $presetModel, $attributes );

			$container->share( "preset.instances.{$attributes['id']}", $presetModel );
		endif;

		return $container->get( "preset.instances.{$attributes['id']}" );
	}

	/**
	 * @return array
	 */
	public function getList() {

		$posts = get_posts( [
			'post_type'      => TP_PRESET_CPT_NAME,
			'posts_per_page' => - 1,
			'post_status'    => 'publish',
			'meta_key'       => 'totalpoll_preset_type',
			'meta_value'     => 'soft'
		] );

		$presets = [];

		foreach ( $posts as $post ) {
			$presets[] = [
				'ID'         => $post->ID,
				'post_title' => $post->post_title,
				'type'       => get_post_meta( $post->ID, 'totalpoll_preset_type', true )
			];
		}

		return $presets;
	}

	/**
	 * @param $preset_id
	 *
	 * @return bool
	 * @noinspection PhpUnused
	 */
	public function saveDefaultPreset( $preset_id ) {
		$preset = get_post( $preset_id );

		if ( $preset ) {
			return update_option( 'totalpoll_default_preset', (int) $preset->ID, true );
		}

		return false;
	}

	public function getDefaultPreset() {
		return $this->getById(get_option( 'totalpoll_default_preset', 0 ));
	}

	/**
	 * @return array|mixed
	 */
	public function getDefaultPresetSettings() {
		$preset = $this->getDefaultPreset();

		if ( $preset instanceof ModelContract && $preset->getPresetPost()->post_status === 'publish') {
			return $preset->getFreshSettings();
		}

		return TotalPoll( 'polls.defaults' );
	}

	/**
	 * Count presets.
	 *
	 * @return int
	 * @since 4.3.0
	 */
	public function count() {
		return (int) wp_count_posts( TP_PRESET_CPT_NAME )->publish;
	}

	/**
	 * @param int $id
	 *
	 * @return string
	 */
	public function getDefaultUrl( $id ) {
		$url = admin_url( sprintf( 'admin-post.php?action=default_preset&post_type=%s&preset=%d', TP_PRESET_CPT_NAME, $id ) );

		return add_query_arg( '_wpnonce', wp_create_nonce( 'default_preset' ), $url );
	}

	/**
	 * @param int $id
	 *
	 * @return string
	 */
	public function getCreatePollUrl( $id ) {
		$url = admin_url( sprintf( 'admin-post.php?action=poll_from_preset&post_type=%s&preset=%d', TP_PRESET_CPT_NAME, $id ) );

		return add_query_arg( '_wpnonce', wp_create_nonce( 'poll_from_preset' ), $url );
	}

	/**
	 * @param int $presetId
	 * @param int $pollId
	 *
	 * @return bool
	 */
	public function applyPresetToPoll( $presetId, $pollId ) {
		$preset = $this->getById( $presetId );

		if ( ! $preset ) {
			return false;
		}

		/**
		 * @var \TotalPoll\Poll\Repository $pollsRepository
		 * @var \TotalPoll\Poll\Model $poll
		 */
		$poll = TotalPoll( 'polls.repository' )->getById( $pollId );

		if ( ! $poll ) {
			return false;
		}

		$presetSettings = $preset->getFreshSettings();
		$pollSettings   = $poll->getSettings();

		unset( $presetSettings['id'], $presetSettings['uid'], $presetSettings['presetUid'] );

		$pollSettings           = array_merge( $pollSettings, $presetSettings );
		$pollSettings['preset'] = $preset->getId();

		$poll->setSettings( $pollSettings );


		if ( $poll->save() ) {
			update_post_meta( $pollId, 'poll_preset', $presetId );

			return true;
		}

		return false;
	}


	/**
	 * @param int $presetId
	 *
	 */
	public function updatePolls( $presetId ) {

		$preset = $this->getById( $presetId );

		$polls = TotalPoll( 'polls.repository' )->get( [
			'perPage' => - 1,
			'status'  => ['publish', 'draft'],
			'wpQuery' => [
				'meta_query' => array(
					array(
						'key'   => 'poll_preset',
						'value' => $preset->getId(),
					),
				),
			],
		] );

		foreach ( $polls as $poll ) {
			$presetSettings = $preset->getFreshSettings();
			$pollSettings   = $poll->getSettings();

			unset( $presetSettings['id'], $presetSettings['uid'], $presetSettings['presetUid'] );

			$pollSettings           = array_merge( $pollSettings, $presetSettings );
			$pollSettings['preset'] = $preset->getId();

			$poll->setSettings( $pollSettings );

			$poll->save();
		}
	}

	/**
	 * Register the default preset
	 */
	public function registerDefaultPreset() {
		$preset = get_option( 'totalpoll_default_preset', 0 );

		if ( ! $preset ) {
			$settings = TotalPoll( 'polls.defaults' );

			$presetId = wp_insert_post( [
				'post_title'   => esc_html__( 'Default', 'totalpoll' ),
				'post_type'    => 'poll_preset',
				'post_status'  => 'publish',
				'post_content' => wp_slash( json_encode( $settings ) )
			] );

			if ( ! is_wp_error( $presetId ) ) {
				update_post_meta((int) $presetId, 'totalpoll_preset_type', 'soft');
				update_option( 'totalpoll_default_preset', (int) $presetId, true );
			}
		}
	}
}
