<?php

namespace TotalPoll\Modules\Extensions\PollPresets;
! defined( 'ABSPATH' ) && exit();


use _WP_Editors;
use TotalPoll\Contracts\Modules\Repository as ModulesRepository;
use TotalPollVendors\TotalCore\Helpers\Arrays;
use TotalPollVendors\TotalCore\Helpers\Misc;
use TotalPollVendors\TotalCore\Helpers\Tracking;
use TotalPollVendors\TotalCore\Http\Request;
use WP_Filesystem_Base;
use WP_Post;

/**
 * Class Editor
 * @package TotalPoll\Admin\Poll
 */
class Editor {
	/**
	 * @var array $env
	 */
	protected $env;
	/**
	 * @var WP_Filesystem_Base $filesystem
	 */
	protected $filesystem;
	/**
	 * @var Repository $presetRepository
	 */
	protected $presetRepository;
	/**
	 * @var ModulesRepository $modulesRepository
	 */
	protected $modulesRepository;
	/**
	 * @var array $templates
	 */
	protected $templates = [];
	/**
	 * @var Model $preset
	 */
	protected $preset;
	/**
	 * @var WP_Post $post
	 */
	protected $post;
	/**
	 * @var array $settings
	 */
	protected $settings = [];

	/**
	 * Editor constructor.
	 *
	 * @param array $env
	 * @param WP_Filesystem_Base $filesystem
	 * @param Repository $presetRepository
	 * @param ModulesRepository $modulesRepository
	 */
	public function __construct( $env, $filesystem, Repository $presetRepository, ModulesRepository $modulesRepository ) {

		$this->env               = $env;
		$this->filesystem        = $filesystem;
		$this->presetRepository  = $presetRepository;
		$this->modulesRepository = $modulesRepository;

		$this->templates = apply_filters( 'totalpoll/filters/admin/editor/templates', $this->modulesRepository->getActiveWhere( [ 'type' => 'template' ] ) );

		foreach ( $this->templates as $templateId => $template ):
			foreach ( [ 'defaults', 'settings', 'preview' ] as $item ):
				$this->templates[ $templateId ][ $item ] = add_query_arg(
					[ 'action' => "totalpoll_templates_get_{$item}", 'template' => $templateId ],
					admin_url( 'admin-ajax.php' )
				);
			endforeach;
		endforeach;

		// Enqueue assets
		add_action( 'admin_enqueue_scripts', [ $this, 'assets' ], 1 );
		add_action( 'admin_enqueue_scripts', [ $this, 'cleanAssets' ], 99 );
		add_action( 'edit_form_after_title', [ $this, 'content' ] );
		// Save preset
		add_filter( 'wp_insert_post_data', [ $this, 'save' ], 10, 2 );

		// Remove WP filters
		remove_filter( 'content_save_pre', 'wp_targeted_link_rel' );

		add_action( "save_post_" . TP_PRESET_CPT_NAME, [ $this, 'savePreset' ], 10, 3 );

		// Add save poll as preset
		add_action( 'post_submitbox_misc_actions', [ $this, 'renderUpdatePolls' ] );
	}

	/**
	 *
	 */
	public function renderUpdatePolls() {
		global $action;

		if (
			current_user_can( 'manage_options' ) && $action === 'edit' ):
			include_once __DIR__ . '/views/meta.php';
		endif;
	}

	/**
	 * @param int $id
	 * @param WP_Post $post
	 * @param bool $update
	 */
	public function savePreset( $id, $post, $update ) {

		/**
		 * @var Request $request
		 */
		$request = TotalPoll( 'http.request' );

		if ( $update ) {
			update_post_meta( $id, 'totalpoll_preset_type', sanitize_text_field( $request->post( 'totalpoll_preset_type', 'soft' ) ) );

			if($request->post('update_polls', false)) {
				$this->presetRepository->updatePolls($id);
			}
		}
	}

	/**
	 * Register assets
	 */
	public function assets() {
		$this->post = get_post();

		if ( ! empty( $this->post ) ):
			$this->preset   = $this->presetRepository->getById( $this->post->ID );
			$this->settings = json_decode( $this->post->post_content, true );
		endif;

		if($this->settings === null):
			$this->settings = [];
		endif;


		// WP Media
		wp_enqueue_media();

		// TinyMCE
		if ( ! class_exists( '_WP_Editors', false ) ):
			require ABSPATH . WPINC . '/class-wp-editor.php';
			_WP_Editors::enqueue_scripts();
		endif;

		/**
		 * @asset-script totalpoll-admin-poll-editor
		 */
		wp_enqueue_script( 'totalpoll-admin-poll-editor' );
		/**
		 * @asset-style totalpoll-admin-poll-editor
		 */
		wp_enqueue_style( 'totalpoll-admin-poll-editor' );

		// Add/set votesOverride property for each choice and set votes property to reflect current votes.
		if ( ! empty( $this->settings['questions'] ) ):
			foreach ( $this->settings['questions'] as $questionIndex => $question ):
				if ( ! empty( $question['choices'] ) ):
					foreach ( $question['choices'] as $choiceIndex => $choice ):
						$votes = $this->preset->getChoiceVotes( $choice['uid'] ) ?: 0;

						$this->settings['questions'][ $questionIndex ]['choices'][ $choiceIndex ]['votes']         = $votes;
						$this->settings['questions'][ $questionIndex ]['choices'][ $choiceIndex ]['votesOverride'] = $votes;
					endforeach;
				endif;
			endforeach;
		endif;

		/**
		 * Filters the defaults settings of poll editor.
		 *
		 * @param array $defaults Array of settings.
		 *
		 * @return array
		 * @since 4.0.0
		 */
		$defaults = apply_filters(
			'totalpoll/filters/admin/editor/defaults',
			TotalPoll( 'polls.defaults' )
		);

		if ( ! empty( $this->settings ) ):
			$defaults['uid'] = '';
		endif;

		/**
		 * Filters the information passed to frontend controller.
		 *
		 * @param array $information Array of values [key => value].
		 *
		 * @return array
		 * @since 4.0.0
		 */
		$information = apply_filters(
			'totalpoll/filters/admin/editor/information',
			[
				'migrated' => true,
				'sidebars' => $GLOBALS['wp_registered_sidebars'],
			]
		);

		/**
		 * Filters the settings of poll passed to frontend controller.
		 *
		 * @param array $settings Array of settings.
		 *
		 * @return array
		 * @since 4.0.0
		 */
		$settings = apply_filters( 'totalpoll/filters/admin/editor/settings', $this->settings, $this->post );

		$i18n = [
			'Are you sure?'                                                                           => esc_html__( 'Are you sure?', 'totalpoll' ),
			'Yes'                                                                                     => esc_html__( 'Yes', 'totalpoll' ),
			'No'                                                                                      => esc_html__( 'No', 'totalpoll' ),
			'Done'                                                                                    => esc_html__( 'Done', 'totalpoll' ),
			'Do you want to receive notifications from TotalPoll?'                                    => esc_html__( 'Do you want to receive notifications from TotalPoll?', 'totalpoll' ),
			'Unfortunately, your browser does not support push notifications.'                        => esc_html__( 'Unfortunately, your browser does not support push notifications.', 'totalpoll' ),
			'ATTENTION! Overriding votes is not reversible. Are you sure you want to override votes?' => esc_html__( 'ATTENTION! Overriding votes is not reversible. Are you sure you want to override votes?', 'totalpoll' ),
		];

		// Send JSON to TotalPoll frontend controller
		wp_localize_script( 'totalpoll-admin-poll-editor', 'TotalPollSettings', $settings );
		wp_localize_script( 'totalpoll-admin-poll-editor', 'TotalPollDefaults', $defaults );
		wp_localize_script( 'totalpoll-admin-poll-editor', 'TotalPollInformation', $information );
		wp_localize_script( 'totalpoll-admin-poll-editor', 'TotalPollTemplates', $this->templates );
		wp_localize_script( 'totalpoll-admin-poll-editor', 'TotalPollLanguages', Misc::getSiteLanguages() );
		wp_localize_script( 'totalpoll-admin-poll-editor', 'TotalPollI18n', $i18n );
		wp_localize_script( 'totalpoll-admin-poll-editor', 'TotalPollPresets', [
			'timeout' => [
				'30'     => esc_html__( '30 Minutes', 'totalpoll' ),
				'60'     => esc_html__( '1 Hour', 'totalpoll' ),
				'360'    => esc_html__( '6 Hours', 'totalpoll' ),
				'1440'   => esc_html__( '1 Day', 'totalpoll' ),
				'10080'  => esc_html__( '1 Week', 'totalpoll' ),
				'43800'  => esc_html__( '1 Month', 'totalpoll' ),
				'262800' => esc_html__( '6 Months', 'totalpoll' ),
				'525600' => esc_html__( '1 Year', 'totalpoll' )
			]
		] );

		// Insights

		
		// Some variables for frontend controller
		wp_localize_script(
			'totalpoll-admin-poll-editor',
			'TotalPollInsights',
			[ 'pollId' => $this->preset->getId() ]
		);
		
	}

	/**
	 * Fix some conflicts.
	 */
	public function cleanAssets() {
		// Disable auto save
		wp_dequeue_script( 'autosave' );
		// ACF
		wp_dequeue_script( 'acf-timepicker' );
		// Chart.js
		wp_dequeue_script( 'gt-chartjs-script' );
	}

	/**
	 * Page content.
	 */
	public function content() {

		/**
		 * Filters tabs list in poll editor.
		 *
		 * @param array $tabs Array of tabs [id => [label, icon]].
		 *
		 * @return array
		 * @since 4.0.0
		 */
		$tabs = apply_filters(
			'totalpoll/filters/admin/editor/tabs',
			[
				'questions'    => [ 'label' => esc_html__( 'Questions', 'totalpoll' ), 'icon' => 'editor-help' ],
				'form'         => [ 'label' => esc_html__( 'Fields', 'totalpoll' ), 'icon' => 'welcome-widgets-menus' ],
				'settings'     => [ 'label' => esc_html__( 'Settings', 'totalpoll' ), 'icon' => 'admin-settings' ],
				'design'       => [ 'label' => esc_html__( 'Design', 'totalpoll' ), 'icon' => 'admin-appearance' ],
				'translations' => [ 'label' => esc_html__( 'Translations', 'totalpoll' ), 'icon' => 'translation' ],
			]
		);

		/**
		 * Filters the list of settings tabs in poll editor.
		 *
		 * @param array $settingsTabs Array of tabs [id => [label, icon, tabs => []]].
		 *
		 * @return array
		 * @since 4.0.0
		 */
		$settingsTabs = apply_filters(
			'totalpoll/filters/admin/editor/settings/tabs',
			[
				'vote'          => [
					'label' => esc_html__( 'Vote', 'totalpoll' ),
					'icon'  => 'marker',
					'tabs'  => [
						'limitations' => [ 'label' => esc_html__( 'Limitations', 'totalpoll' ), 'icon' => 'lock' ],
						'frequency'   => [ 'label' => esc_html__( 'Frequency', 'totalpoll' ), 'icon' => 'backup' ],
					],
				],
				'choices'       => [
					'label' => esc_html__( 'Choices', 'totalpoll' ),
					'icon'  => 'editor-justify',
					'tabs'  => [
						'sort' => [ 'label' => esc_html__( 'Sort', 'totalpoll' ), 'icon' => 'sort' ],
					],
				],
				'results'       => [
					'label' => esc_html__( 'Results', 'totalpoll' ),
					'icon'  => 'chart-pie',
					'tabs'  => [
						'sort'       => [ 'label' => esc_html__( 'Sort', 'totalpoll' ), 'icon' => 'sort' ],
						'visibility' => [ 'label' => esc_html__( 'Visibility', 'totalpoll' ), 'icon' => 'visibility' ],
						'format'     => [ 'label' => esc_html__( 'Format', 'totalpoll' ), 'icon' => 'admin-generic' ],
					],
				],
				'content'       => [
					'label' => esc_html__( 'Content', 'totalpoll' ),
					'icon'  => 'admin-page',
					'tabs'  => [
						'welcome'  => [ 'label' => esc_html__( 'Welcome', 'totalpoll' ), 'icon' => 'admin-home' ],
						'vote'     => [ 'label' => esc_html__( 'Vote', 'totalpoll' ), 'icon' => 'editor-justify' ],
						'thankyou' => [ 'label' => esc_html__( 'Thank you', 'totalpoll' ), 'icon' => 'admin-page' ],
						'results'  => [ 'label' => esc_html__( 'Results', 'totalpoll' ), 'icon' => 'chart-pie' ],
					],
				],
				'seo'           => [ 'label' => esc_html__( 'SEO', 'totalpoll' ), 'icon' => 'search' ],
				'notifications' => [
					'label' => esc_html__( 'Notifications', 'totalpoll' ),
					'icon'  => 'megaphone',
					'tabs'  => [
						'email'   => [ 'label' => esc_html__( 'Email', 'totalpoll' ), 'icon' => 'email' ],
						'push'    => [ 'label' => esc_html__( 'Push', 'totalpoll' ), 'icon' => 'format-status' ],
						'webhook' => [ 'label' => esc_html__( 'WebHook', 'totalpoll' ), 'icon' => 'admin-site' ],
					],
				],
			]
		);

		/**
		 * Filters the list of design tabs in poll editor.
		 *
		 * @param array $designTabs Array of tabs [id => [label]].
		 *
		 * @return array
		 * @since 4.0.0
		 */
		$designTabs = apply_filters(
			'totalpoll/filters/admin/editor/design/tabs',
			[
				'templates' => [ 'label' => esc_html__( 'Templates', 'totalpoll' ) ],
				'layout'    => [ 'label' => esc_html__( 'Layout', 'totalpoll' ) ],
				'colors'    => [ 'label' => esc_html__( 'Colors', 'totalpoll' ) ],
				'text'      => [ 'label' => esc_html__( 'Text', 'totalpoll' ) ],
				'advanced'  => [
					'label' => esc_html__( 'Advanced', 'totalpoll' ),
					'tabs'  => [
						'template-settings' => [ 'label' => esc_html__( 'Template Settings', 'totalpoll' ) ],
						'behaviours'        => [ 'label' => esc_html__( 'Behaviours', 'totalpoll' ) ],
						'effects'           => [ 'label' => esc_html__( 'Effects', 'totalpoll' ) ],
						'custom-css'        => [ 'label' => esc_html__( 'Custom CSS', 'totalpoll' ) ],
					],
				],
			]
		);

		/**
		 * Filters the list of integration tabs in poll editor.
		 *
		 * @param array $tabs Array of tabs [id => [label, description, icon]].
		 *
		 * @return array
		 * @since 4.0.0
		 */
		$integrationTabs = apply_filters(
			'totalpoll/filters/admin/editor/integration/tabs',
			[
				'shortcode' => [
					'label'       => esc_html__( 'Shortcode', 'totalpoll' ),
					'description' => esc_html__( 'WordPress feature', 'totalpoll' ),
					'icon'        => 'editor-code'
				],
				'widget'    => [
					'label'       => esc_html__( 'Widget', 'totalpoll' ),
					'description' => esc_html__( 'WordPress feature', 'totalpoll' ),
					'icon'        => 'welcome-widgets-menus'
				],
				'link'      => [
					'label'       => esc_html__( 'Direct link', 'totalpoll' ),
					'description' => esc_html__( 'Standard link', 'totalpoll' ),
					'icon'        => 'admin-links'
				],
				'embed'     => [
					'label'       => esc_html__( 'Embed', 'totalpoll' ),
					'description' => esc_html__( 'External inclusion', 'totalpoll' ),
					'icon'        => 'admin-site'
				],
				'email'     => [
					'label'       => esc_html__( 'Email', 'totalpoll' ),
					'description' => esc_html__( 'Vote links', 'totalpoll' ),
					'icon'        => 'email'
				],
			]
		);

		$dateTimeFormat = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );

		if ( ! current_user_can( 'edit_theme_options' ) ):
			unset( $integrationTabs['widget'] );
		endif;

		include_once __DIR__ . '/views/editor.php';

		if ( get_current_screen()->action === 'add' ) {
			Tracking::trackScreens( 'new-poll' );
		} else {
			Tracking::trackScreens( 'edit-poll' );
		}
	}

	/**
	 * Save poll action.
	 *
	 * @param array $presetArgs
	 * @param array $post
	 *
	 * @return mixed
	 */
	public function save( $presetArgs, $post ) {
		$presetId = absint( $post['ID'] );

		if ( ! empty( $presetArgs['post_content'] ) ):
			$settings = json_decode( wp_unslash( $presetArgs['post_content'] ), true );
			/**
			 * Filters the settings before saving the poll.
			 *
			 * @param array $settings Array of settings.
			 * @param array $presetArgs Array of post args.
			 * @param int $presetId Poll post ID.
			 *
			 * @return array
			 * @since 4.0.0
			 */
			$settings = apply_filters( 'totalpoll/filters/before/admin/editor/save/settings', $settings, $presetArgs, $presetId, $this );

			// Server-side validations

			// Questions
			$questions = (array) Arrays::getDotNotation( $settings, 'questions', [] );
			foreach ( $questions as $questionIndex => $question ):
				$settings = Arrays::setDotNotation(
					$settings,
					"questions.{$questionIndex}.settings.selection.minimum",
					absint( Arrays::getDotNotation( $question, 'settings.selection.minimum', 1 ) )
				);

				$choices = (array) Arrays::getDotNotation( $question, 'choices', [] );
				foreach ( $choices as $choiceIndex => $choice ):
					$settings = Arrays::setDotNotation(
						$settings,
						"questions.{$questionIndex}.choices.{$choiceIndex}.votes",
						absint( Arrays::getDotNotation( $choice, 'votes', 0 ) )
					);

					$settings = Arrays::setDotNotation(
						$settings,
						"questions.{$questionIndex}.choices.{$choiceIndex}.votesOverride",
						absint( Arrays::getDotNotation( $choice, 'votesOverride', 0 ) )
					);
				endforeach;
			endforeach;

			// Fields
			$fields = (array) Arrays::getDotNotation( $settings, 'fields', [] );
			foreach ( $fields as $fieldIndex => $field ):
				$settings = Arrays::setDotNotation(
					$settings,
					"fields.{$fieldIndex}.name",
					sanitize_title_with_dashes( Arrays::getDotNotation( $field, "name", uniqid( 'untitled', false ) ), '', 'save' )
				);
			endforeach;

			// Settings
			$timePeriodStart = Arrays::getDotNotation( $settings, 'vote.limitations.period.start', '' );
			if ( ! (bool) strtotime( $timePeriodStart ) ):
				$settings = Arrays::setDotNotation(
					$settings,
					'vote.limitations.period.start',
					''
				);
			endif;
			$timePeriodEnd = Arrays::getDotNotation( $settings, 'vote.limitations.period.end', '' );
			if ( ! (bool) strtotime( $timePeriodEnd ) ):
				$settings = Arrays::setDotNotation(
					$settings,
					'vote.limitations.period.end',
					''
				);
			endif;
			$settings = Arrays::setDotNotation(
				$settings,
				'vote.limitations.quota.value',
				absint( Arrays::getDotNotation( $settings, 'vote.limitations.quota.value', 0 ) )
			);

			$settings = Arrays::setDotNotation(
				$settings,
				'vote.frequency.perSession',
				absint( Arrays::getDotNotation( $settings, 'vote.frequency.perSession', 0 ) )
			);

			$settings = Arrays::setDotNotation(
				$settings,
				'vote.frequency.perUser',
				absint( Arrays::getDotNotation( $settings, 'vote.frequency.perUser', 0 ) )
			);

			$settings = Arrays::setDotNotation(
				$settings,
				'vote.frequency.perIP',
				absint( Arrays::getDotNotation( $settings, 'vote.frequency.perIP', 0 ) )
			);

			$settings = Arrays::setDotNotation(
				$settings,
				'vote.frequency.timeout',
				absint( Arrays::getDotNotation( $settings, 'vote.frequency.timeout', 0 ) )
			);

			// Generate a UID based on design settings
			$settings['presetUid'] = md5( json_encode( $settings['design'] ) );

			/**
			 * Filters the settings after validation to be saved.
			 *
			 * @param array $settings Array of settings.
			 * @param array $pollresetArgs Array of post args.
			 * @param int $presetId Poll post ID.
			 *
			 * @return array
			 * @since 4.0.0
			 */
			$settings = apply_filters( 'totalpoll/filters/admin/editor/save/settings', $settings, $presetArgs, $presetId, $this );

			// Prepare settings for insertion
			$presetArgs['post_content'] = json_encode( $settings );
			// Sanitize
			if ( ! current_user_can( 'unfiltered_html' ) ):
				$presetArgs['post_content'] = wp_kses_post( $presetArgs['post_content'] );
			endif;
			// Add slashes
			$presetArgs['post_content'] = wp_slash( $presetArgs['post_content'] );

			/**
			 * Filters the arguments that are passed back to wp_update_post to save the changes.
			 *
			 * @param array $pollresetArgs Array of post args.
			 * @param array $settings Array of settings.
			 * @param int $presetId Poll post ID.
			 *
			 * @return array
			 * @since 4.0.0
			 * @see   Check wp_update_post documentation for more details.
			 *
			 */
			$presetArgs = apply_filters( 'totalpoll/filters/admin/editor/save/post', $presetArgs, $settings, $presetId, $this );
		endif;

		// Adjust redirect url
		add_filter( 'redirect_post_location', function ( $location ) {
			$params = [
				'tab' => empty( $_POST['totalpoll_current_tab'] ) ? null : urlencode( (string) $_POST['totalpoll_current_tab'] ),
			];

			return add_query_arg( $params, $location );
		} );

		return $presetArgs;
	}
}
