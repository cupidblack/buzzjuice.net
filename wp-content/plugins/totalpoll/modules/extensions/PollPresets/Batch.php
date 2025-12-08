<?php

namespace TotalPoll\Modules\Extensions\PollPresets;
! defined( 'ABSPATH' ) && exit();



use TotalPoll\Poll\Repository as PollsRepository;
use TotalPollVendors\TotalCore\Admin\Pages\Page;
use TotalPollVendors\TotalCore\Contracts\Foundation\Environment as EnvironmentContract;
use TotalPollVendors\TotalCore\Contracts\Http\Request as RequestContract;

/**
 * Class Batch
 * @package TotalPoll\Admin\Preset
 */
class Batch extends Page {

	/**
	 * @var PollsRepository
	 */
	protected $pollsRepository;

	/**
	 * @var Repository
	 */
	protected $presetsRepository;

	/**
	 * @var string
	 */
	protected $parentPage;

	/**
	 * Batch constructor.
	 *
	 * @param RequestContract $request
	 * @param EnvironmentContract $env
	 * @param PollsRepository $pollsRepository
	 * @param Repository $presetsRepository
	 */
	public function __construct( RequestContract $request, EnvironmentContract $env, PollsRepository $pollsRepository, Repository $presetsRepository ) {
		$this->pollsRepository   = $pollsRepository;
		$this->presetsRepository = $presetsRepository;

		parent::__construct( $request, $env );
	}

	public function assets() {
	    global $current_screen, $plugin_page;

	    if($current_screen->post_type === TP_POLL_CPT_NAME && $plugin_page === 'batch_preset') {
            wp_enqueue_script( 'totalpoll-admin-presets' );
            wp_enqueue_style( 'totalpoll-admin-presets' );

            $presets = $this->presetsRepository->getList();
            wp_localize_script('totalpoll-admin-presets', 'TotalPollPresetsPoll', $presets);
        }
	}


	public function render() {
		include_once __DIR__ . '/views/batch.php';
	}
}