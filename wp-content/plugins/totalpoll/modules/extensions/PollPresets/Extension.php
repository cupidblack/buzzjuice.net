<?php

namespace TotalPoll\Modules\Extensions\PollPresets;
! defined( 'ABSPATH' ) && exit();


use TotalPoll\Plugin;
use TotalPoll\Poll\Repository as PollsRepository;
use TotalPollVendors\TotalCore\Foundation\Environment;
use TotalPollVendors\TotalCore\Helpers\Tracking;
use TotalPollVendors\TotalCore\Http\Request;
use WP_Post;
use wpdb;

/**
 * Class Extension
 * @package TotalPoll\Modules\Extensions\PollPresets
 */
class Extension extends \TotalPoll\Modules\Extension {

	protected $root = __FILE__;
	/**
	 * @var bool $enqueue A flag to enqueue extension style.
	 */
	protected $enqueue = false;

	/**
	 * @var Request $request
	 */
	protected $request;

	/**
	 * Run the extension.
	 *
	 */
	public function run() {

		add_action('totalpoll/actions/after/bootstrap-extensions', [$this, 'registerProviders']);

		// Admin messages
		add_filter('totalpoll/filters/admin/messages', function (array $messages) {
			return array_merge($messages, [
				'preset-default-success' => [ esc_html__( 'Preset set to default', 'totalpoll' ), 'updated' ],
				'preset-default-error'   => [ esc_html__( 'Preset set to default failed', 'totalpoll' ), 'error' ],
				'preset-apply-success'   => [ esc_html__( 'Preset has been set to poll', 'totalpoll' ), 'updated' ],
				'preset-apply-error'     => [ esc_html__( 'Failed to set the preset', 'totalpoll' ), 'error' ],
			]);
		});

		// Admin Presets Bulk apply page
		add_action('totalpoll/actions/bootstrap-admin', [$this, 'bootstrapAdmin']);

		add_action('totalpoll/actions/bootstrap-ajax', [$this, 'bootstrapAjax']);
	}

	/**
	 *
	 */
	public static function onActivate() {

		/**
		 * @var Request $request
		 * @var wpdb $db
		 * @var Environment $env
		 */
		$request = TotalPoll('http.request');
		$db = TotalPoll('database');
		$env =  TotalPoll('env');

		$repository = new Repository($request, $db, $env->toArray());

		$repository->registerDefaultPreset();
	}


	public function bootstrapAjax() {
		add_action('wp_ajax_totalpoll_presets_polls', function() {
			$page = (int) TotalPoll('http.request')->query('page', 1);
			TotalPoll( 'admin.ajax.polls' )->getList($page - 1);
		});

		add_action('wp_ajax_totalpoll_apply_preset', function() {

			$request = TotalPoll('http.request');
			$repository = TotalPoll('presets.repository');

			$presetId = (int) $request->post('preset', 0);
			$pollId = (int) $request->post('poll', 0);

			if($repository->applyPresetToPoll($presetId, $pollId)) {
				wp_send_json_success();
			}

			wp_send_json_error();
		});
	}

	public function bootstrapAdmin() {
		// Add save poll as preset
		add_action( 'post_submitbox_misc_actions', [ $this, 'renderSaveAsPreset' ] );

		// Activation
		add_action('totalpoll/actions/activated', [$this, 'onPluginActivate']);

		// Filters the settings of poll passed to frontend controller.
		add_filter( 'totalpoll/filters/admin/editor/settings', [$this, 'setDefaultPollSettings'], 10, 2);

		// Preset column header
		add_filter('totalpoll/filters/admin/listing/columns', [$this, 'presetColumnHeader'], 10, 2);

		// Preset column content
		add_filter( 'totalpoll/filters/admin/listing/columns-content/preset', [$this, 'presetColumnContent'], 10, 2 );

		// Register assets
		add_action('totalpoll/actions/admin/assets', [$this, 'adminAssets'], 10, 2);

		// Preset default
		add_action( 'admin_post_default_preset', [ $this, 'setDefaultPreset' ] );
		// Create poll from preset
		add_action( 'admin_post_poll_from_preset', [ $this, 'createPollFromPreset' ] );
		// Apply preset to poll
		add_action( 'admin_post_preset_to_poll', [ $this, 'applyPresetToPoll' ] );
		// Remove default preset option
		add_action('after_delete_post', [$this, 'afterDeletePreset'], 10);

		add_filter('totalpoll/filters/posttypes', function($types) {
			$types[] = TP_PRESET_CPT_NAME;
			return $types;
		});

		// Admin Presets Editor
		add_action('totalpoll/actions/admin/screen/editor', function ($postType) {
			if($postType === TP_PRESET_CPT_NAME) {
				TotalPoll('admin.preset.editor');
			}
		});

		// Admin Presets Listing
		add_action('totalpoll/actions/admin/screen/listing', function ($postType) {

			if($postType === TP_PRESET_CPT_NAME) {
				TotalPoll('admin.preset.listing');
			}
		});

		// Save poll as preset
		add_action( 'save_post_' . TP_POLL_CPT_NAME, [ $this, 'savePollAsPreset' ], 99, 2 );

		// Bulk
        add_action( 'admin_menu', [ $this, 'registerMenu' ] );
        add_filter( 'submenu_file', [$this, 'menu']);
	}

    public function registerMenu() {

	    $batchPage = TotalPoll('admin.pages.presets.batch');

        add_submenu_page(
            'edit.php?post_type=' . TP_POLL_CPT_NAME,
            esc_html__( 'Batch Preset Apply', 'totalpoll' ),
            'Preset Batch',
            'edit_presets',
            'batch_preset',
            function () use ($batchPage){
                $batchPage->render();
            }
        );
    }

    public function menu( $submenu_file ) {
        global $plugin_page;

        // Select another submenu item to highlight (optional).
        if ( $plugin_page && $plugin_page === 'batch_preset' ) {
            $submenu_file = 'edit.php?post_type=' . TP_PRESET_CPT_NAME;
        }

        // Hide the submenu.
        remove_submenu_page( 'edit.php?post_type=' . TP_POLL_CPT_NAME, 'batch_preset' );

        return $submenu_file;
    }

	public function adminAssets($baseUrl, $assetsVersion) {

		// ------------------------------
		// Presets
		// ------------------------------
		/**
		 * @asset-script totalpoll-admin-presets
		 */
		wp_register_script(
			'totalpoll-admin-presets',
			"{$baseUrl}assets/dist/scripts/presets.js",
			[ 'angular', 'angular-resource' ],
			$assetsVersion
		);
		/**
		 * @asset-style totalpoll-admin-presets
		 */
		wp_register_style(
			'totalpoll-admin-presets',
			"{$baseUrl}assets/dist/styles/admin-presets.css",
			[ 'totalpoll-admin-totalcore' ],
			$assetsVersion
		);
	}

	/**
	 * @param $id
	 * @param WP_Post $post
	 */
	public function afterDeletePreset($id) {
		$post = get_post($id);

		if($post && $post->post_type === TP_PRESET_CPT_NAME) {
			$defaultPresetId = (int) get_option('totalpoll_default_preset', 0);

			if($defaultPresetId === $id) {
				delete_option('totalpoll_default_preset');
			}
		}
	}

	public function setDefaultPreset() {
		Tracking::trackEvents( 'default', 'preset' );

		$url        = wp_get_referer();
		$preset_id  = TotalPoll( 'http.request' )->query( 'preset', 0 );
		$repository = TotalPoll( 'presets.repository' );

		if ( check_admin_referer( 'default_preset' ) && $repository->saveDefaultPreset( $preset_id ) ) {
			$url = add_query_arg( 'message', 'preset-default-success', $url );
		} else {
			$url = add_query_arg( 'message', 'preset-default-error', $url );
		}
		wp_redirect( $url );
		exit();
	}

	public function createPollFromPreset() {

		if ( check_admin_referer( 'poll_from_preset' ) ) {
			/**
			 * @var pollsRepository $repository
			 * @var Model|null $preset
			 */
			$repository = TotalPoll( 'presets.repository' );
			$preset     = $repository->getById( (int) TotalPoll( 'http.request' )->query( 'preset', 0 ) );
			$new        = TotalPoll( 'http.request' )->query( 'new', false );

			if ( $preset instanceof Model ) {
				$settings = $preset->getFreshSettings();

				$post = wp_insert_post( [
					'post_title'   => $new ? '' : $preset->getPresetPost()->post_title . ' (Copy)',
					'post_content' => json_encode( $settings ),
					'post_type'    => TP_POLL_CPT_NAME
				] );

				if ( $post ) {
					update_post_meta( $post, 'poll_preset', $preset->getId() );
					$url = get_edit_post_link( $post, '' );
					wp_redirect( $url );
					exit();
				}
			}
		}

		wp_redirect( wp_get_referer() );
		exit();

	}

	public function applyPresetToPoll() {
		$url = wp_get_referer();

		if ( check_admin_referer( 'preset_to_poll' ) ) {
			/**
			 * @var Repository $presetRepository
			 */
			$presetsRepository = TotalPoll( 'presets.repository' );

			$presetId = TotalPoll( 'http.request' )->post( 'preset' );
			$pollId   = TotalPoll( 'http.request' )->post( 'poll' );

			if ( $presetsRepository->applyPresetToPoll( $presetId, $pollId ) ) {
				$url = add_query_arg( [
					'post'    => $pollId,
					'action'  => 'edit',
					'message' => 'preset-apply-success',
				], admin_url( 'post.php' ) );
			} else {
				$url = add_query_arg( 'message', 'preset-apply-error', $url );
			}

			wp_redirect( $url );
			exit();
		}
	}
	/**
	 *
	 * @return array
	 */
	public function presetColumnHeader() {
		return [
			'cb'      => '<input type="checkbox" />',
			'title'   => esc_html__( 'Title' ),
			'preset'  => esc_html__( 'Preset', 'totalpoll' ),
			'votes'   => esc_html__( 'Votes', 'totalpoll' ),
			'entries' => esc_html__( 'Entries', 'totalpoll' ),
			'log'     => esc_html__( 'Log', 'totalpoll' ),
			'date'    => esc_html__( 'Date' ),
		];
	}

	/**
	 * @param $content
	 * @param $id
	 *
	 * @return string
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function presetColumnContent( $content, $id ) {
		$presetId = (int) get_post_meta( $id, 'poll_preset', true );
		$preset = TotalPoll( 'presets.repository' )->getById( $presetId );

		if ( $preset === null) {
			return esc_html__( 'Default', 'totalpoll' );
		}

		return esc_html($preset->getTitle());
	}

	/**
	 * @param array $settings
	 * @param WP_Post $post
	 *
	 * @return array|mixed
	 */
	public function setDefaultPollSettings(array $settings, WP_Post $post) {

		if($post->post_type === TP_POLL_CPT_NAME && $post->post_status === 'auto-draft') {
			$preset = get_option('totalpoll_default_preset', false);

			$settings = TotalPoll( 'presets.repository' )->getDefaultPresetSettings();

			if($preset) {
				update_post_meta($post->ID, 'poll_preset', $preset);
			}
		}

		return $settings;
	}
	/**
	 * @param $pollId
	 * @param WP_Post $post
	 *
	 */
	public function savePollAsPreset( $pollId, $post ) {
		global $action;

		if ($action !== 'editpost' || ! TotalPoll( 'http.request' )->post( 'poll_as_preset', false ) ) {
			return;
		}

		$presetSettings = json_decode( $post->post_content, true );

		unset( $presetSettings['ID'], $presetSettings['uid'] );

		remove_all_actions( 'save_post_' . TP_POLL_CPT_NAME );

		$title = TotalPoll( 'http.request' )->post( 'poll_preset_title', false );

		$presetId = wp_insert_post( [
			'post_title'   => empty( $title ) ? $post->post_title : $title,
			'post_type'    => TP_PRESET_CPT_NAME,
			'post_status'  => 'publish',
			'post_content' => wp_slash( json_encode( $presetSettings ) )
		] );

		update_post_meta( $pollId, 'poll_preset', $presetId );
	}

	/**
	 * @param Plugin $plugin
	 */
	public function registerProviders(Plugin $plugin) {
		$container = $plugin->getApplication()->container();

		// Poll post type
		$container->share( 'presets.cpt', function () {
			return new PostType();
		} );

		// Preset repository
		$container->share( 'presets.repository', function () use ($container) {
			return new Repository( $container->get( 'http.request' ), $container->get( 'database' ), $container->get( 'env' ) );
		} );

		// Preset editor
		$container->share( 'admin.preset.editor', function () use ($container) {
			return new Editor( $container->get( 'env' ), $container->get( 'filesystem' ), $container->get( 'presets.repository' ), $container->get( 'modules.repository' ) );
		} );

		// Preset listing
		$container->share( 'admin.preset.listing', function () use ($container){
			return new Listing( $container->get( 'http.request' ), $container->get( 'presets.repository' ), $container->get( 'env' ) );
		} );

		// Preset Batch
		$container->share( 'admin.pages.presets.batch', function () use ($container) {
			return new Batch( $container->get( 'http.request' ), $container->get( 'env' ), $container->get( 'polls.repository' ), $container->get( 'presets.repository' ) );
		} );

		TotalPoll('presets.cpt');
	}

	/**
	 */
	public function renderSaveAsPreset() {
		global $action, $post;

		if ( $post->post_type === TP_POLL_CPT_NAME && current_user_can( 'create_presets' )):
			$presetsRepository = TotalPoll( 'presets.repository' );
			$presets           = $presetsRepository->get( [ 'perPage' => - 1, 'status' => 'publish' ] );

			if(! empty($presets)) {
				include_once $this->getPath('views/presets.php');
			}

			include_once $this->getPath('views/save-as-preset.php');
		endif;
	}
}
