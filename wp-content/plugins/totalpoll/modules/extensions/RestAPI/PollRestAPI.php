<?php

namespace TotalPoll\Modules\Extensions\RestAPI;
! defined( 'ABSPATH' ) && exit();



use TotalPoll\Contracts\Poll\Model;
use TotalPoll\Contracts\Poll\Repository;
use TotalPollVendors\TotalCore\Contracts\Http\Request;
use WP_Error;

/**
 * Poll Rest API.
 * @package TotalPoll\Poll
 */
class PollRestAPI {
	/**
	 * @var Request $request
	 */
	protected $request;
	/**
	 * @var Repository $repository
	 */
	protected $repository;
	/**
	 * @var array $env
	 */
	protected $env;
	/**
	 * @var Model $poll
	 */
	protected $poll;

	/**
	 * Rest constructor.
	 *
	 * @param Request    $request
	 * @param Repository $repository
	 * @param array      $env
	 */
	public function __construct( Request $request, Repository $repository, $env ) {
		$this->request    = $request;
		$this->repository = $repository;
		$this->env        = $env;
	}

	/**
	 * Register routes.
	 */
	public function registerRoutes() {
		register_rest_route(
			$this->env['rest-namespace'], '/poll/(?P<id>\d+)/vote',
			[
				'methods'  => 'POST',
				'callback' => [ $this, 'postVote' ],
				'permission_callback' => '__return_true'
			]
		);

		register_rest_route(
			$this->env['rest-namespace'], '/poll/(?P<id>\d+)',
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'getPoll' ],
				'permission_callback' => '__return_true'
			]
		);
	}

	/**
	 * Load poll.
	 *
	 * @param $pollId
	 *
	 * @throws \ErrorException
	 */
	public function loadPoll( $pollId ) {
		$status = get_post_status( (int) $pollId );
		if ( $status === 'publish' ):
			$this->poll = $this->repository->getById( (int) $pollId );
		else:
			throw new \ErrorException( 'Invalid Poll ID', 404 );
		endif;
	}

	/**
	 * Get.
	 *
	 * @param \WP_REST_Request $restRequest
	 *
	 * @return array|WP_Error
	 */
	public function getPoll( \WP_REST_Request $restRequest ) {
		try {
			$this->loadPoll( (int) $restRequest['id'] );
			$pollArray = $this->adjustPollArray( $this->poll->toArray(), $this->poll->isResultsHidden(), true );

			$pollArray['acceptingVotes'] = $this->poll->isAcceptingVotes();
			$pollArray['hasVoted']       = $this->poll->hasVoted();

			return [
				'code'    => 200,
				'message' => '',
				'data'    => [
					'poll'   => $pollArray,
					'status' => 200,
				],
			];

		} catch ( \Exception $exception ) {
			return new WP_Error( $exception->getCode(), $exception->getMessage(), [ 'status' => 404 ] );
		}
	}

	/**
	 * Vote.
	 *
	 * @param \WP_REST_Request $restRequest
	 *
	 * @return array|WP_Error
	 */
	public function postVote( \WP_REST_Request $restRequest ) {
		$this->request['totalpoll'] = $restRequest->get_param( 'totalpoll' );

		try {
			$this->loadPoll( (int) $restRequest['id'] );

			$this->poll->setScreen( 'vote' );

			if ( $this->poll->isAcceptingVotes() ):
				if ( $this->poll->getForm()->validate() ):
					$countVote = TotalPoll( 'polls.commands.vote.count', [ $this->poll ] )->execute();
					$log = TotalPoll( 'polls.commands.vote.log', [ $this->poll ] )->execute();

					if ( $countVote && is_wp_error( $countVote ) ):
						throw new \ErrorException( $countVote->get_error_message(), 404 );
					else:
						if ( ! is_wp_error( $log ) ):
							TotalPoll( 'polls.commands.vote.entry', [ $this->poll ] )->execute();
							TotalPoll( 'polls.commands.vote.notify.newVote', [ $this->poll ] )->execute();
							TotalPoll( 'polls.commands.vote.notify.newChoice', [ $this->poll ] )->execute();
						endif;

						$this->poll->getRestrictions()->apply();
						$this->poll->setScreen( 'thankyou' );

						$pollArray = $this->adjustPollArray( $this->poll->toArray(), $this->poll->isResultsHidden() );
						$pollArray['acceptingVotes'] = $this->poll->isAcceptingVotes();
						$pollArray['hasVoted']       = $this->poll->hasVoted();

						return [
							'code'    => 200,
							'message' => 'Your vote has been casted successfully.',
							'data'    => [
								'poll'   => $pollArray,
								'status' => 200,
							],
						];
					endif;
				else:
					throw new \ErrorException( 'Fields validation failed.', 403 );
				endif;
			else:
				throw new \ErrorException( 'This poll does not accept new votes.', 403 );
			endif;

		} catch ( \Exception $exception ) {
			return new WP_Error( $exception->getCode(), $exception->getMessage(), [
				'status'     => 422,
				'pollError'  => $this->poll->getError(),
				'formErrors' => $this->poll->getForm()->errors(),
			] );
		}
	}

	/**
	 * Adjust poll array for public rest response.
	 *
	 * @param array $pollArray
	 * @param bool  $hideVotes
	 * @param bool  $hideReceivedVotes
	 *
	 * @return array
	 */
	protected function adjustPollArray( $pollArray, $hideVotes = false, $hideReceivedVotes = false ) {
		foreach ( $pollArray['questions'] as $questionUid => $question ):
			foreach ( $question['choices'] as $choiceUid => $choice ):
				if ( ! $choice['visibility'] ):
					unset( $pollArray['questions'][ $questionUid ]['choices'][ $choiceUid ] );
					continue;
				endif;
				unset( $pollArray['questions'][ $questionUid ]['choices'][ $choiceUid ]['votesOverride'] );
				unset( $pollArray['questions'][ $questionUid ]['choices'][ $choiceUid ]['collapsed'] );
				unset( $pollArray['questions'][ $questionUid ]['choices'][ $choiceUid ]['visibility'] );

				if ( $hideReceivedVotes ):
					unset( $pollArray['questions'][ $questionUid ]['choices'][ $choiceUid ]['receivedVotes'] );
				endif;

				if ( $hideVotes ):
					$pollArray['questions'][ $questionUid ]['choices'][ $choiceUid ]['votes']       = 0;
					$pollArray['questions'][ $questionUid ]['choices'][ $choiceUid ]['votesHidden'] = true;
				endif;
			endforeach;

			if ( $hideVotes ):
				unset( $pollArray['questions'][ $questionUid ]['votes'] );
				$pollArray['questions'][ $questionUid ]['votes']       = 0;
				$pollArray['questions'][ $questionUid ]['votesHidden'] = true;
			endif;

			unset( $pollArray['questions'][ $questionUid ]['votesOverride'] );

			if ( $hideReceivedVotes ):
				unset( $pollArray['questions'][ $questionUid ]['receivedVotes'] );
			endif;
		endforeach;

		unset( $pollArray['receivedVotes'] );

		return $pollArray;
	}

}
