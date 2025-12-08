<?php

namespace TotalPoll\Modules\Extensions\PollPresets;
! defined( 'ABSPATH' ) && exit();


use DateInterval;
use TotalPoll\Contracts\Preset\Model as ModelContract;
use TotalPoll\Limitations\Membership;
use TotalPoll\Limitations\Period;
use TotalPoll\Limitations\Quota;
use TotalPoll\Limitations\Region;
use TotalPoll\Restrictions\IPAddress;
use TotalPoll\Restrictions\LoggedInUser;
use TotalPollVendors\TotalCore\Contracts\Form\Form;
use TotalPollVendors\TotalCore\Contracts\Helpers\Arrayable;
use TotalPollVendors\TotalCore\Helpers\Arrays;
use TotalPollVendors\TotalCore\Helpers\Misc;
use TotalPollVendors\TotalCore\Helpers\Strings;
use TotalPollVendors\TotalCore\Limitations\Bag;
use TotalPollVendors\TotalCore\Traits\Cookies;
use TotalPollVendors\TotalCore\Traits\Metadata;
use WP_Error;
use WP_Post;

/**
 * Preset Model
 * @package TotalPoll\Poll
 * @since   1.0.0
 */
class Model implements ModelContract {
	use Cookies;
	use Metadata;

	const SORT_BY_POSITION = 'position';

	const SORT_ASC = 'asc';
	const SORT_DESC = 'desc';

	/**
	 * Preset ID.
	 *
	 * @var int|null
	 * @since 1.0.0
	 */
	protected $id = null;

	/**
	 * Preset UID.
	 *
	 * @var int|null
	 * @since 4.1.3
	 */
	protected $uid = null;

	/**
	 * Preset attributes.
	 *
	 * @var array
	 * @since 1.0.0
	 */
	protected $attributes = [];

	/**
	 * Preset settings.
	 *
	 * @var array|null
	 * @since 1.0.0
	 */
	protected $settings = null;

	/**
	 * Preset seo attributes.
	 *
	 * @var array|null
	 * @since 1.0.0
	 */
	protected $seo = null;

	/**
	 * Preset WordPress post.
	 *
	 * @var array|null|WP_Post
	 * @since 1.0.0
	 */
	protected $presetPost = null;

	/**
	 * Preset questions.
	 * @var array
	 * @since 1.0.0
	 */
	protected $questions = [];

	/**
	 * Preset choices map (choice uid => question uid).
	 * @var array
	 * @since 1.0.0
	 */
	protected $choicesMap = [];

	/**
	 * Preset choices count.
	 * @var int
	 * @since 1.0.0
	 */

	/**
	 * Choice per page.
	 * @var int
	 * @since 1.0.0
	 */
	protected $choicesPerPage = 10;

	/**
	 * Preset total pages.
	 *
	 * @var int|null
	 * @since 1.0.0
	 */
	protected $pagesCount = 0;

	/**
	 * Preset current page.
	 *
	 * @var int|null
	 * @since 1.0.0
	 */
	protected $currentPage = 1;

	/**
	 * Preset upload form.
	 *
	 * @var Form $form
	 * @since 1.0.0
	 */
	protected $form = null;

	/**
	 * Sort choices by field.
	 * @var string
	 * @since 1.0.0
	 */
	protected $sortChoicesBy = self::SORT_BY_POSITION;

	/**
	 * Sort choices direction.
	 * @var string
	 * @since 1.0.0
	 */
	protected $sortChoicesDirection = self::SORT_DESC;

	/**
	 * Sort results by field.
	 * @var string
	 * @since 1.0.0
	 */
	protected $sortResultsBy = self::SORT_BY_POSITION;

	/**
	 * Sort results direction.
	 * @var string
	 * @since 1.0.0
	 */
	protected $sortResultsDirection = self::SORT_DESC;

	/**
	 * Limitations
	 *
	 * @var \TotalPollVendors\TotalCore\Contracts\Limitations\Bag
	 * @since 1.0.0
	 */
	protected $limitations;

	/**
	 * Restrictions
	 *
	 * @var \TotalPollVendors\TotalCore\Contracts\Restrictions\Bag
	 * @since 1.0.0
	 */
	protected $restrictions;

	/**
	 * Error.
	 * @var null|WP_Error
	 * @since 1.0.0
	 */
	protected $error;

	/**
	 * Model constructor.
	 *
	 * @param $attributes array Poll attributes.
	 *
	 * @since 1.0.0
	 */
	public function __construct( $attributes ) {
		/**
		 * Filters the poll attributes.
		 *
		 * @param array $attributes Poll model attributes.
		 * @param ModelContract $poll Poll model object.
		 *
		 * @return array
		 * @since 4.0.0
		 */
		$attributes       = apply_filters( 'totalpoll/filters/preset/attributes', $attributes, $this );
		$this->attributes = $attributes;
		$this->id         = $attributes['id'];
		$this->presetPost = $attributes['post'];
		// Parse settings JSON.
		$this->settings = (array) json_decode( $this->presetPost->post_content, true );

		/**
		 * Filters the poll attributes.
		 *
		 * @param array $settings Poll settings.
		 * @param ModelContract $poll Poll model object.
		 *
		 * @return array
		 * @since 4.0.0
		 */
		$this->settings = apply_filters( 'totalpoll/filters/preset/settings', $this->settings, $this );

		// UID
		$this->uid = $this->getSettingsItem( 'uid' );

		// Locale
		$locale = get_locale();

		// Questions
		$questions = $this->getSettingsItem( 'questions', [] );

		foreach ( $questions as $questionIndex => $question ):
			// Translation
			if ( ! empty( $question['translations'][ $locale ]['content'] ) ):
				$question['content'] = $question['translations'][ $locale ]['content'];
			endif;

			// Move to questions array
			$this->questions[ $question['uid'] ]                  = $question;
			$this->questions[ $question['uid'] ]['index']         = $questionIndex;
			$this->questions[ $question['uid'] ]['votes']         = 0;
			$this->questions[ $question['uid'] ]['receivedVotes'] = 0;

			// Choices
			$choices                                        = (array) $this->questions[ $question['uid'] ]['choices'];
			$this->questions[ $question['uid'] ]['choices'] = [];
			foreach ( $choices as $choiceIndex => $choice ):
				// Translation
				if ( ! empty( $choice['translations'][ $locale ]['label'] ) ):
					$choice['label'] = $choice['translations'][ $locale ]['label'];
				endif;

				// Add votes property
				$choice['votes'] = empty( $attributes['votes'][ $choice['uid'] ] ) ? 0 : $attributes['votes'][ $choice['uid'] ];

				// Add to choices map
				$this->choicesMap[ $choice['uid'] ] = $question['uid'];
				$choice['index']                    = $choiceIndex;
				$choice['questionUid']              = $question['uid'];
				$choice['receivedVotes']            = 0;

				// Alter visibility for users with cookie set to choice UID (Check CountVote command for more details)
				if ( $this->getCookie( $this->getPrefix( $choice['uid'] ) ) ):
					$choice['visibility'] = true;
				endif;

				// Cumulative of votes for current question
				$this->questions[ $question['uid'] ]['choices'][ $choice['uid'] ] = $choice;
			endforeach;

			// Calculate ranking of choices
			$questionChoices = $this->questions[ $question['uid'] ]['choices'];
			uasort( $questionChoices, [ $this, 'orderByVotes' ] );
			$questionChoices = array_reverse( $questionChoices, true );
			$rankedChoices   = array_keys( $questionChoices );
			foreach ( $rankedChoices as $index => $choiceUid ):
				$this->questions[ $question['uid'] ]['choices'][ $choiceUid ]['rank'] = $index + 1;
			endforeach;
		endforeach;

		// Fields
		$fields = $this->getSettingsItem( 'fields', [] );
		foreach ( $fields as &$field ):
			// Translation
			if ( ! empty( $field['translations'][ $locale ]['label'] ) ):
				$field['label'] = $field['translations'][ $locale ]['label'];
			endif;
			// Translation
			if ( ! empty( $field['translations'][ $locale ]['options'] ) ):
				$field['options'] = $field['translations'][ $locale ]['options'];
			endif;

		endforeach;
		$this->setSettingsItem( 'fields', $fields );

		// Current page
		$this->currentPage = empty( $attributes['currentPage'] ) ? $this->currentPage : (int) $attributes['currentPage'];

		// Choice per page
		$this->choicesPerPage = (int) $this->getSettingsItem( 'design.pagination.perPage' ) ?: 10;

		// Sort choices
		$this->sortChoicesBy        = (string) $this->getSettingsItem( 'choices.sort.field', 'position' );
		$this->sortChoicesDirection = (string) $this->getSettingsItem( 'choices.sort.direction', 'desc' );

		// Sort results
		$this->sortResultsBy        = (string) $this->getSettingsItem( 'results.sort.field', 'position' );
		$this->sortResultsDirection = (string) $this->getSettingsItem( 'results.sort.direction', self::SORT_DESC );

		/**
		 * Filters the poll questions.
		 *
		 * @param array $questions Questions array.
		 * @param Model $model Poll model.
		 *
		 * @return array
		 * @since 4.0.1
		 */
		$this->questions = apply_filters( 'totalpoll/filters/preset/questions', $this->questions, $this );


		// Limitations
		$this->limitations = new Bag();

		// Period
		$periodArgs = $this->getSettingsItem( 'vote.limitations.period', [] );
		if ( ! empty( $periodArgs['enabled'] ) ):
			$this->limitations->add( 'period', new Period( $periodArgs ) );
		endif;

		// Membership
		$membershipArgs = $this->getSettingsItem( 'vote.limitations.membership', [] );
		if ( ! empty( $membershipArgs['enabled'] ) ):
			$this->limitations->add( 'membership', new Membership( $membershipArgs ) );
		endif;

		// Quota
		$quotaArgs = $this->getSettingsItem( 'vote.limitations.quota', [] );
		if ( ! empty( $quotaArgs['enabled'] ) ):
			$quotaArgs['currentValue'] = 0;
			$this->limitations->add( 'quota', new Quota( $quotaArgs ) );
		endif;

		// Region
		$regionArgs = $this->getSettingsItem( 'vote.limitations.region', [] );
		if ( ! empty( $regionArgs['enabled'] ) ):
			$regionArgs['ip'] = $attributes['ip'];
			$this->limitations->add( 'region', new Region( $regionArgs ) );
		endif;
		/**
		 * Fires after limitations setup.
		 *
		 * @param ModelContract $poll Poll model object.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/poll/limitations', $this );

		// Restrictions
		$this->restrictions = new \TotalPollVendors\TotalCore\Restrictions\Bag();

		$frequencyArgs              = $this->getSettingsItem( 'vote.frequency', [ 'timeout' => 3600 ] );
		$frequencyArgs['uid']       = $this->getUid();
		$frequencyArgs['poll']      = $this;
		$frequencyArgs['action']    = 'vote';
		$frequencyArgs['fullCheck'] = TotalPoll()->option( 'performance.fullChecks.enabled' );

		if ( ! empty( $frequencyArgs['cookies']['enabled'] ) ):
			$this->restrictions->add( 'cookies', new \TotalPoll\Restrictions\Cookies( $frequencyArgs ) );
		endif;

		if ( ! empty( $frequencyArgs['ip']['enabled'] ) ):
			$this->restrictions->add( 'ip', new IPAddress( $frequencyArgs ) );
		endif;

		if ( ! empty( $frequencyArgs['user']['enabled'] ) ):
			$this->restrictions->add( 'user', new LoggedInUser( $frequencyArgs ) );
		endif;


		// Translation
		$this->setSettingsItem(
			'seo.poll.title',
			$this->getSettingsItem(
				"seo.poll.translations.{$locale}.title",
				$this->getSettingsItem( 'seo.poll.title' )
			)
		);

		$this->setSettingsItem(
			'seo.poll.description',
			$this->getSettingsItem(
				"seo.poll.translations.{$locale}.description",
				$this->getSettingsItem( 'seo.poll.description' )
			)
		);

		$this->setSettingsItem(
			'content.welcome.content',
			$this->getSettingsItem(
				"content.welcome.translations.{$locale}.content",
				$this->getSettingsItem( 'content.welcome.content' )
			)
		);

		$this->setSettingsItem(
			'content.vote.above',
			$this->getSettingsItem(
				"content.vote.translations.{$locale}.above",
				$this->getSettingsItem( 'content.vote.above' )
			)
		);

		$this->setSettingsItem(
			'content.vote.below',
			$this->getSettingsItem(
				"content.vote.translations.{$locale}.below",
				$this->getSettingsItem( 'content.vote.below' )
			)
		);

		$this->setSettingsItem(
			'content.thankyou.content',
			$this->getSettingsItem(
				"content.thankyou.translations.{$locale}.content",
				$this->getSettingsItem( 'content.thankyou.content' )
			)
		);

		$this->setSettingsItem(
			'content.results.above',
			$this->getSettingsItem(
				"content.results.translations.{$locale}.above",
				$this->getSettingsItem( 'content.results.above' )
			)
		);

		$this->setSettingsItem(
			'content.results.below',
			$this->getSettingsItem(
				"content.results.translations.{$locale}.below",
				$this->getSettingsItem( 'content.results.below' )
			)
		);

		$this->setSettingsItem(
			'results.message',
			$this->getSettingsItem(
				"results.translations.{$locale}.message",
				$this->getSettingsItem( 'results.message' )
			)
		);

		/**
		 * Fires after poll model setup is completed.
		 *
		 * @param ModelContract $poll Poll model object.
		 *
		 * @since 4.0.0
		 */
		do_action( 'totalpoll/actions/poll/setup', $this );
	}

	/**
	 * Get settings section or item.
	 *
	 * @param bool $section Settings section.
	 * @param bool $args Path to setting.
	 *
	 * @return mixed|array|null
	 * @since 1.0.0
	 */
	public function getSettings( $section = false, $args = false ) {
		// Deep selection.
		if ( $args !== false && $section && isset( $this->settings[ $section ] ) ):
			$paths = func_get_args();
			unset( $paths[0] );

			return Arrays::getDeep( $this->settings[ $section ], $paths );
		endif;

		// Return specific settings section, otherwise, return all settings.
		if ( $section ):
			return isset( $this->settings[ $section ] ) ? $this->settings[ $section ] : null;
		endif;

		return $this->settings;
	}

	public function getFreshSettings() {
		$settings = $this->getSettings();

		if ( $this->getType() === 'soft' ) {
			unset( $settings['questions'], $settings['choices'] );
		}

		$uids = $this->regenerateUids($settings);
		return json_decode(str_replace( array_keys($uids), array_values($uids), json_encode($settings) ), true);
	}

	/**
	 * @param $array
	 * @param  array  $uids
	 *
	 * @return array
	 */
	protected function regenerateUids($array, array &$uids = []) {
		foreach($array as $key => $value) {
			if($value instanceof Arrayable) {
				$this->regenerateUids($value->toArray(), $uids);
			}elseif(is_array($value)) {
				$this->regenerateUids($value, $uids);
			}elseif ($key === 'uid') {
				$uids[$value] = wp_generate_uuid4();
			}
		}

		return $uids;
	}

	/**
	 * Get settings item.
	 *
	 * @param bool $needle Settings name.
	 * @param bool $default Default value.
	 *
	 * @return mixed|array|null
	 * @since 1.0.0
	 */
	public function getSettingsItem( $needle, $default = null ) {
		/**
		 * Filters the poll settings item.
		 *
		 * @param array $settings Poll settings.
		 * @param string $default Default value.
		 * @param ModelContract $poll Poll model object.
		 *
		 * @return mixed
		 * @since 4.0.0
		 */
		return apply_filters( "totalpoll/filters/preset/settings-item/{$needle}", Arrays::getDotNotation( $this->settings, $needle, $default ), $this->settings, $default, $this );
	}

	/**
	 * Get poll post object.
	 *
	 * @return array|mixed|null|WP_Post
	 */
	public function getPresetPost() {
		return $this->presetPost;
	}

	/**
	 * Get poll id.
	 *
	 * @return int
	 * @since 1.0.0
	 */
	public function getId() {
		return (int) $this->id;
	}

	/**
	 * Get poll uid.
	 *
	 * @return int
	 * @since 4.1.3
	 */
	public function getUid() {
		return (string) $this->uid;
	}

	/**
	 * Get poll title.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function getTitle() {
		return $this->presetPost->post_title;
	}

	/**
	 * Get poll thumbnail.
	 *
	 * @return false|string
	 * @since 1.0.0
	 */
	public function getThumbnail() {
		$thumbnail = wp_get_attachment_image_src( get_post_thumbnail_id( $this->id ), 'post-thumbnail' );

		$thumbnail = empty( $thumbnail[0] ) ? TotalPoll()->env( 'url' ) . 'assets/dist/images/poll/no-preview.png' : $thumbnail[0];

		/**
		 * Filters the poll thumbnail.
		 *
		 * @param array $attributes Poll model attributes.
		 * @param ModelContract $poll Poll model object.
		 *
		 * @return string
		 * @since 4.0.0
		 */
		return apply_filters( 'totalpoll/filters/preset/thumbnail', $thumbnail, $this );
	}

	/**
	 * Get time left to start.
	 *
	 * @return int|DateInterval
	 * @since 1.0.0
	 */
	public function getTimeLeftToStart() {
		$startDate = $this->getSettingsItem( 'vote.limitations.period.start' );
		$startDate = $startDate ? TotalPoll( 'datetime', [ $startDate ] ) : false;

		if ( $startDate && $startDate->getTimestamp() > current_time( 'timestamp' ) ):
			$now = TotalPoll( 'datetime' );

			return $startDate->diff( $now, true );
		endif;

		return 0;
	}

	/**
	 * Get time left to end.
	 *
	 * @return int|DateInterval
	 * @since 1.0.0
	 */
	public function getTimeLeftToEnd() {
		$endDate = $this->getSettingsItem( 'vote.limitations.period.end' );
		$endDate = $endDate ? TotalPoll( 'datetime', [ $endDate ] ) : false;

		if ( $endDate && $endDate->getTimestamp() > current_time( 'timestamp' ) ):
			$now = TotalPoll( 'datetime' );

			return $endDate->diff( $now, true );
		endif;

		return 0;
	}


	/**
	 * Get URL.
	 *
	 * @param array $args
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function getUrl( $args = [] ) {
		$base = is_singular( TP_POLL_CPT_NAME ) ? $this->getPermalink() : wp_get_referer();
		$url  = add_query_arg( $args, $base );

		/**
		 * Filters the poll urls.
		 *
		 * @param string $url URL.
		 * @param array $args Arguments.
		 * @param ModelContract $poll Poll model object.
		 *
		 * @return string
		 * @since 4.0.0
		 */
		return apply_filters( 'totalpoll/filters/preset/url', $url, $args, $this );
	}

	/**
	 * @param null $orderBy
	 * @param string $direction
	 *
	 * @return array
	 */
	public function getQuestions( $orderBy = null, $direction = self::SORT_ASC ) {
		if ( $orderBy ):
			foreach ( $this->questions as &$question ):
				uasort( $question['choices'], [ $this, 'orderBy' . ucfirst( $orderBy ) ] );

				if ( $direction && strtolower( $direction ) === self::SORT_DESC ):
					$question['choices'] = array_reverse( $question['choices'], true );
				endif;
			endforeach;
		endif;

		return $this->questions;
	}

	/**
	 * @return array
	 */
	public function getQuestionsForVote() {
		/**
		 * Filters the poll questions (for vote).
		 *
		 * @param array $questions Questions array.
		 * @param Model $model Poll model.
		 *
		 * @return array
		 * @since 4.0.1
		 */
		return apply_filters(
			'totalpoll/filters/preset/questions-for-vote',
			$this->getQuestions( $this->sortChoicesBy, $this->sortChoicesDirection ),
			$this
		);
	}

	/**
	 * @return array
	 */
	public function getQuestionsForResults() {
		/**
		 * Filters the poll questions (for results).
		 *
		 * @param array $questions Questions array.
		 * @param Model $model Poll model.
		 *
		 * @return array
		 * @since 4.0.1
		 */
		return apply_filters(
			'totalpoll/filters/preset/questions-for-results',
			$this->getQuestions( $this->sortResultsBy, $this->sortResultsDirection ),
			$this
		);
	}

	/**
	 * @return int
	 */
	public function getQuestionsCount() {
		return count( $this->questions );
	}

	/**
	 * @return int
	 */
	public function getChoicesCount() {
		return count( $this->choicesMap );
	}

	/**
	 * @param $questionUid
	 *
	 * @return int
	 */
	public function getQuestionChoicesCount( $questionUid ) {
		return empty( $this->questions[ $questionUid ] ) ? 0 : count( $this->questions[ $questionUid ]['choices'] );
	}

	/**
	 * @param $questionUid
	 *
	 * @return array|mixed|null
	 */
	public function getQuestion( $questionUid ) {
		return empty( $this->questions[ $questionUid ] ) ? null : $this->questions[ $questionUid ];
	}

	/**
	 * @param $questionUid
	 *
	 * @return array
	 */
	public function getQuestionChoices( $questionUid ) {
		return empty( $this->questions[ $questionUid ] ) ? [] : $this->questions[ $questionUid ]['choices'];
	}

	/**
	 * @param $questionUid
	 *
	 * @return int
	 */
	public function getQuestionVotes( $questionUid ) {
		return empty( $this->questions[ $questionUid ] ) ? 0 : $this->questions[ $questionUid ]['votes'];
	}

	/**
	 * @param $questionUid
	 *
	 * @return string
	 */
	public function getQuestionVotesWithLabel( $questionUid ) {
		$votes = $this->getQuestionVotes( $questionUid );

		return sprintf( _n( '%s Vote', '%s Votes', $votes, 'totalpoll' ), number_format( $votes ) );
	}

	/**
	 * @param $choiceUid
	 *
	 * @return array|mixed|null
	 */
	public function getQuestionUidByChoiceUid( $choiceUid ) {
		return empty( $this->choicesMap[ $choiceUid ] ) ? null : $this->choicesMap[ $choiceUid ];
	}

	/**
	 * @param $choiceUid
	 *
	 * @return array|null
	 */
	public function getChoice( $choiceUid ) {
		return empty( $this->choicesMap[ $choiceUid ] ) ? null : $this->questions[ $this->choicesMap[ $choiceUid ] ]['choices'][ $choiceUid ];
	}

	/**
	 * @param $choiceUid
	 *
	 * @return int|null
	 */
	public function getChoiceVotes( $choiceUid ) {
		if ( ! isset( $this->choicesMap[ $choiceUid ] ) ):
			return null;
		endif;

		return $this->questions[ $this->choicesMap[ $choiceUid ] ]['choices'][ $choiceUid ]['votes'];
	}

	/**
	 * @param $choiceUid
	 *
	 * @return null|string
	 */
	public function getChoiceVotesNumber( $choiceUid ) {
		if ( ! isset( $this->choicesMap[ $choiceUid ] ) ):
			return null;
		endif;

		$votes = $this->questions[ $this->choicesMap[ $choiceUid ] ]['choices'][ $choiceUid ]['votes'];

		return number_format( $votes );
	}

	/**
	 * @param $choiceUid
	 *
	 * @return null|string
	 */
	public function getChoiceVotesWithLabel( $choiceUid ) {
		if ( ! isset( $this->choicesMap[ $choiceUid ] ) ):
			return null;
		endif;

		$votes = $this->questions[ $this->choicesMap[ $choiceUid ] ]['choices'][ $choiceUid ]['votes'];

		return sprintf( _n( '%s Vote', '%s Votes', $votes, 'totalpoll' ), number_format( $votes ) );
	}

	/**
	 * @param $choiceUid
	 *
	 * @return null|string
	 */
	public function getChoiceVotesPercentage( $choiceUid ) {
		if ( ! isset( $this->choicesMap[ $choiceUid ] ) ):
			return null;
		endif;

		$votes      = $this->questions[ $this->choicesMap[ $choiceUid ] ]['choices'][ $choiceUid ]['votes'];
		$totalVotes = $this->questions[ $this->choicesMap[ $choiceUid ] ]['votes'];

		return $totalVotes ? number_format( ( $votes / $totalVotes ) * 100, 2 ) : '0.00';
	}

	/**
	 * @param $choiceUid
	 *
	 * @return string
	 */
	public function getChoiceVotesPercentageWithLabel( $choiceUid ) {
		return $this->getChoiceVotesPercentage( $choiceUid ) . '%';
	}

	/**
	 * @param $choiceUid
	 *
	 * @return string
	 */
	public function getChoiceVotesFormatted( $choiceUid ) {
		return Strings::template(
			$this->getSettingsItem( 'results.format', '{{votesWithLabel}}' ),
			[
				'votes'               => $this->getChoiceVotesNumber( $choiceUid ),
				'votesPercentage'     => $this->getChoiceVotesPercentageWithLabel( $choiceUid ),
				'votesWithLabel'      => $this->getChoiceVotesWithLabel( $choiceUid ),
				'votesTotal'          => $this->getQuestionVotes( $this->getQuestionUidByChoiceUid( $choiceUid ) ),
				'votesTotalWithLabel' => $this->getQuestionVotesWithLabel( $this->getQuestionUidByChoiceUid( $choiceUid ) ),
			]
		);
	}

	/**
	 * @return array
	 */
	public function getChoices() {
		$choices = [];
		foreach ( $this->choicesMap as $choiceUid => $questionUid ):
			$choices[ $choiceUid ] = $this->questions[ $questionUid ]['choices'][ $choiceUid ];
		endforeach;

		return $choices;
	}

	/**
	 * @param array $args
	 *
	 * @return array
	 */
	public function getChoicesRows( $args = [] ) {
		$perRow  = $this->getSettingsItem( 'design.layout.columns', 4 );
		$choices = $this->getChoices();

		return $perRow === 0 ? [ $choices ] : array_chunk( $choices, $perRow, true );
	}

	/**
	 * @return float|int|string
	 */
	public function getColumnWidth() {
		$perRow = $this->getSettingsItem( 'design.layout.columns', 4 );

		return 100 / $perRow;
	}

	/**
	 * @return null|WP_Error
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * @return null|string
	 */
	public function getErrorMessage() {
		return is_wp_error( $this->error ) ? $this->error->get_error_message() : null;
	}

	/**
	 * @return \TotalPollVendors\TotalCore\Contracts\Limitations\Bag|Bag
	 */
	public function getLimitations() {
		return $this->limitations;
	}

	/**
	 * @return \TotalPollVendors\TotalCore\Contracts\Restrictions\Bag|\TotalPollVendors\TotalCore\Restrictions\Bag
	 */
	public function getRestrictions() {
		return $this->restrictions;
	}

	/**
	 * @return array
	 */
	public function getFields() {
		$fields = $this->getSettingsItem( 'fields', [] );
		foreach ( $fields as $fieldIndex => $field ):
			$fields[ $field['uid'] ] = [
				'type'         => $field['type'],
				'label'        => $field['label'],
				'name'         => $field['name'],
				'defaultValue' => $field['defaultValue'],
				'options'      => $field['options'],
				'required'     => ! empty( $field['validations']['filled']['enabled'] ),
			];
			unset( $fields[ $fieldIndex ] );
		endforeach;

		/**
		 * Filters the poll custom fields.
		 *
		 * @param array $fields Poll custom fields.
		 * @param ModelContract $poll Poll model object.
		 *
		 * @return array
		 * @since 4.0.0
		 */
		return apply_filters( 'totalpoll/filters/preset/fields', $fields, $this );
	}

	/**
	 * Get prefix.
	 *
	 * @param string $append
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function getPrefix( $append = '' ) {
		/**
		 * Filters poll prefix.
		 *
		 * @param string $prefix Poll prefix.
		 * @param string $append Appended value.
		 * @param ModelContract $poll Poll model object.
		 *
		 * @return array
		 * @since 4.0.3
		 */
		return apply_filters( 'totalpoll/filters/preset/prefix', "tp_{$this->id}_{$append}", $append, $this );
	}

	/**
	 * @return array|mixed|null|string
	 */
	public function getWelcomeContent() {
		return $this->getSettingsItem( 'content.welcome.content' );
	}

	/**
	 * @return array|mixed|null|string
	 */
	public function getThankyouContent() {
		return $this->getSettingsItem( 'content.thankyou.content' );
	}

	/**
	 * @return array|mixed|null|string
	 */
	public function getAboveVoteContent() {
		return $this->getSettingsItem( 'content.vote.above' );
	}

	/**
	 * @return array|mixed|null|string
	 */
	public function getBelowVoteContent() {
		return $this->getSettingsItem( 'content.vote.below' );
	}

	/**
	 * @return array|mixed|null|string
	 */
	public function getAboveResultsContent() {
		return $this->getSettingsItem( 'content.results.above' );
	}

	/**
	 * @return array|mixed|null|string
	 */
	public function getBelowResultsContent() {
		return $this->getSettingsItem( 'content.results.below' );
	}

	/**
	 * @return array|mixed|null|string
	 */
	public function getTemplateId() {
		return $this->getSettingsItem( 'design.template', 'basic-template' );
	}

	/**
	 * @return string
	 */
	public function getPresetUid() {
		return $this->getSettingsItem( 'presetUid', md5( $this->getId() ) );
	}

	/**
	 * Edit link in WordPress dashboard.
	 * @return string
	 */
	public function getAdminEditLink() {
		return admin_url( "post.php?post={$this->getId()}&action=edit" );
	}

	/**
	 * Set/Override settings item value.
	 *
	 * @param $needle
	 * @param $value
	 *
	 * @return void
	 */
	public function setSettingsItem( $needle, $value ) {
		$this->settings = Arrays::setDotNotation( $this->settings, $needle, $value );
	}

	/**
	 * Get received choices.
	 *
	 * @return array
	 */
	public function getReceivedChoices() {
		$choices = [];
		foreach ( $this->choicesMap as $choiceId => $questionUid ):
			$choice = $this->questions[ $questionUid ]['choices'][ $choiceId ];
			if ( ! empty( $choice['receivedVotes'] ) ):
				$choices[ $choiceId ] = $choice;
			endif;
		endforeach;

		return $choices;
	}

	public function getReceivedQuestions() {
		$questions = [];
		foreach ( $this->choicesMap as $choiceId => $questionUid ):
			$choice = $this->questions[ $questionUid ]['choices'][ $choiceId ];
			if ( ! empty( $choice['receivedVotes'] ) ):
				$questions[ $questionUid ] = true;
			endif;
		endforeach;

		return array_keys( $questions );
	}

	/**
	 * Set form.
	 *
	 * @param Form $form
	 *
	 * @return Form Form object
	 * @since 1.0.0
	 */
	public function setForm( Form $form ) {
		return $this->form = $form;
	}

	/**
	 * Set an error.
	 *
	 * @param WP_Error $error
	 *
	 * @since 1.0.0
	 */
	public function setError( $error ) {
		$this->error = $error;
	}

	/**
	 * @return bool
	 */
	public function hasError() {
		return ! empty( $this->error );
	}


	/**
	 * @return bool
	 */
	public function isPaginated() {
		return ! empty( $this->choicesPerPage );
	}

	/**
	 * @return bool
	 */
	public function isPasswordProtected() {
		return post_password_required( $this->presetPost );
	}

	/**
	 * JSON representation of poll.
	 *
	 * @return array
	 * @since 1.0.0
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		return $this->toArray();
	}

	/**
	 * Get the instance as an array.
	 *
	 * @return array
	 */
	public function toArray() {
		$poll = [
			'id'            => $this->getId(),
			'title'         => $this->getTitle(),
			'permalink'     => $this->getPermalink(),
			'questions'     => $this->getQuestions(),
			'fields'        => $this->getFields()
		];

		if ( is_admin() ):
			$poll['admin'] = [
				'editLink' => $this->getAdminEditLink(),
			];
		endif;

		return $poll;
	}

	/**
	 * @param mixed $offset
	 *
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return isset( $this->{$offset} );
	}

	/**
	 * @param mixed $offset
	 *
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return isset( $this->{$offset} ) ? $this->{$offset} : null;
	}

	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {

	}

	/**
	 * @param mixed $offset
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {

	}

	/**
	 * Order by votes.
	 *
	 * @param $current
	 * @param $next
	 *
	 * @return int
	 * @since 1.0.0
	 */
	private function orderByVotes( $current, $next ) {
		if ( $current['votes'] === $next['votes'] ):
			return 0;
		elseif ( $current['votes'] < $next['votes'] ):
			return - 1;
		else:
			return 1;
		endif;
	}

	/**
	 * @param array $question
	 * @param array $choices
	 *
	 * @return mixed|void
	 */
	public function addQuestion( $question, $choices = [] ) {
		// TODO: Implement addQuestion() method.
	}

	/**
	 * @param array $choice
	 * @param string $questionUid
	 * @param bool $persistent
	 *
	 * @return array|bool
	 */
	public function addChoice( $choice, $questionUid, $persistent = true ) {
		$question = $this->getQuestion( $questionUid );
		$choice   = Arrays::parse( $choice, [
			'uid'           => 'custom-' . Misc::generateUid(),
			'type'          => 'text',
			'label'         => '',
			'visibility'    => true,
			'votes'         => 0,
			'votesOverride' => 0,
		] );

		if ( $question ):
			if ( $persistent ):
				$this->settings['questions'][ $question['index'] ]['choices'][] = $choice;
			endif;

			// Add to choices map
			$this->choicesMap[ $choice['uid'] ] = $question['uid'];
			$choice['index']                    = count( $this->settings['questions'][ $question['index'] ]['choices'] ) - 1;
			$choice['questionUid']              = $question['uid'];
			$choice['receivedVotes']            = 0;

			// Cumulative of votes for question
			$this->questions[ $question['uid'] ]['choices'][ $choice['uid'] ] = $choice;
		else:
			return false;
		endif;

		return $choice;
	}

	/**
	 * @inheritDoc
	 */
	public function save() {
		// Remove WP filters
		remove_filter( 'content_save_pre', 'wp_targeted_link_rel' );

		return ! is_wp_error(
			wp_update_post( [
				'ID'           => $this->getId(),
				'post_content' => wp_slash( json_encode( $this->settings ) ),
			] )
		);
	}

	/**
	 * @inheritDoc
	 */
	public function refreshUid() {
		$this->setSettingsItem( 'uid', Misc::generateUid() );

		return $this->save();
	}

	/**
	 * @param $criteria
	 *
	 * @return array
	 */
	public function getQuestionsWhere( $criteria ) {
		return array_filter( $this->questions, function ( $question ) use ( $criteria ) {
			foreach ( $criteria as $key => $value ):
				if ( ! isset( $question[ $key ] ) || $question[ $key ] != $value ):
					return false;
				endif;
			endforeach;

			return true;
		} );
	}

	/**
	 * @param $criteria
	 *
	 * @return array
	 */
	public function getChoicesWhere( $criteria ) {
		return array_filter( $this->choicesMap, function ( $question ) use ( $criteria ) {
			foreach ( $criteria as $key => $value ):
				if ( ! isset( $question[ $key ] ) || $question[ $key ] != $value ):
					return false;
				endif;
			endforeach;

			return true;
		} );
	}

	/**
	 * Set question.
	 *
	 * @param string $questionUid
	 * @param array $override
	 * @param bool $persistent
	 *
	 * @return bool
	 */
	public function setQuestion( $questionUid, $override = [], $persistent = true ) {
		$question = $this->getQuestion( $questionUid );
		if ( $question ):
			$this->questions[ $question['uid'] ] = Arrays::parse( $override, $question );

			if ( $persistent ):
				$this->settings['questions'][ $question['index'] ] = Arrays::parse( $override, $this->settings['questions'][ $question['index'] ] );
			endif;

			return true;
		endif;

		return false;
	}

	/**
	 * Set choice.
	 *
	 * @param string $choiceUid
	 * @param array $override
	 * @param bool $persistent
	 *
	 * @return bool
	 */
	public function setChoice( $choiceUid, $override = [], $persistent = true ) {
		$choice = $this->getChoice( $choiceUid );
		if ( $choice ):
			$question = $this->getQuestion( $choice['questionUid'] );

			$this->questions[ $question['uid'] ]['choices'][ $choice['uid'] ] = Arrays::parse( $override, $choice );

			if ( $persistent ):
				$this->settings['questions'][ $question['index'] ]['choices'][ $choice['index'] ] = Arrays::parse( $override, $this->settings['questions'][ $question['index'] ]['choices'][ $choice['index'] ] );
			endif;

			return true;
		endif;

		return false;
	}

	/**
	 * Remove question.
	 *
	 * @param string $questionUid
	 * @param bool $persistent
	 *
	 * @return bool
	 */
	public function removeQuestion( $questionUid, $persistent = true ) {
		$question = $this->getQuestion( $questionUid );
		if ( $question ):
			foreach ( $question['choices'] as $choiceUid => $choice ):
				$this->removeChoice( $choiceUid, $persistent );
			endforeach;

			if ( $persistent ):
				unset( $this->settings['questions'][ $question['index'] ] );
			endif;

			return true;
		endif;

		return false;
	}

	/**
	 * Remove choice.
	 *
	 * @param string $choiceUid
	 * @param bool $persistent
	 *
	 * @return bool
	 */
	public function removeChoice( $choiceUid, $persistent = true ) {
		$choice = $this->getChoice( $choiceUid );
		if ( $choice ):
			$question = $this->getQuestion( $choice['questionUid'] );

			unset( $this->questions[ $question['uid'] ]['choices'][ $choice['uid'] ], $this->choicesMap[ $choice['uid'] ] );

			if ( $persistent ):
				unset( $this->settings['questions'][ $question['index'] ]['choices'][ $choice['index'] ] );
			endif;

			return true;
		endif;

		return false;
	}

	public function getType() {
		$type = get_post_meta( $this->getId(), 'totalpoll_preset_type', true );

		if ( empty( $type ) ) {
			return 'soft';
		}

		return $type;
	}

	public function getCreateUrl() {
		$url = sprintf(
			'admin-post.php?action=%s&preset=%d&new=true&_wpnonce=%s', 'poll_from_preset', $this->getId(), wp_create_nonce('poll_from_preset')
		);
		return admin_url($url);
	}
	/**
	 * Get poll permalink.
	 *
	 * @return false|string
	 */
	public function getPermalink() {
		return get_permalink( $this->presetPost );
	}
}
