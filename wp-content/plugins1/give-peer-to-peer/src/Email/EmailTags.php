<?php

namespace GiveP2P\Email;

use Exception;
use Give\Framework\Support\ValueObjects\Money;
use Give\Log\Log;
use Give_Payment;
use GiveP2P\P2P\Admin\EditCampaign;
use GiveP2P\P2P\Facades\Campaign;
use GiveP2P\P2P\Facades\Fundraiser;
use GiveP2P\P2P\Facades\Team;


/**
 * Email Tags
 *
 * @since 1.5.0
 */
class EmailTags
{

    /**
     * @since 1.5.0
     *
     * @param array $emailTags
     *
     * @return array
     */
    public function loadEmailTags(array $emailTags): array
    {
        $p2pEmailTags = [
            [
                'tag' => 'campaign_name',
                'desc' => esc_html__(
                    'Campaign Name.',
                    'give-peer-to-peer'
                ),
                'func' => [$this, 'getTagContent'],
                'context' => 'p2p',
            ],
            [
                'tag' => 'team_name',
                'desc' => esc_html__(
                    'Team Name.',
                    'give-peer-to-peer'
                ),
                'func' => [$this, 'getTagContent'],
                'context' => 'p2p',
            ],
            [
                'tag' => 'fundraiser_first_name',
                'desc' => esc_html__(
                    'Fundraiser First Name.',
                    'give-peer-to-peer'
                ),
                'func' => [$this, 'getTagContent'],
                'context' => 'p2p',
            ],
            [
                'tag' => 'team_captain_first_name',
                'desc' => esc_html__(
                    'Team Captain First Name.',
                    'give-peer-to-peer'
                ),
                'func' => [$this, 'getTagContent'],
                'context' => 'p2p',
            ],
            [
                'tag' => 'fundraiser_profile_url',
                'desc' => esc_html__(
                    'Fundraiser Profile URL.',
                    'give-peer-to-peer'
                ),
                'func' => [$this, 'getTagContent'],
                'context' => 'p2p',
            ],
            [
                'tag' => 'team_profile_url',
                'desc' => esc_html__(
                    'Team Profile URL.',
                    'give-peer-to-peer'
                ),
                'func' => [$this, 'getTagContent'],
                'context' => 'p2p',
            ],
            [
                'tag' => 'recipient_name',
                'desc' => esc_html__(
                    'The donation recipients name.',
                    'give-peer-to-peer'
                ),
                'func' => [$this, 'getTagContent'],
                'context' => 'donationTeamCaptain',
            ],
            [
                'tag' => 'recipient_goal',
                'desc' => esc_html__(
                    'The donation recipients goal.',
                    'give-peer-to-peer'
                ),
                'func' => [$this, 'getTagContent'],
                'context' => 'donationTeamCaptain',
            ],
            [
                'tag' => 'recipient_progress',
                'desc' => esc_html__(
                    'The donation recipients progress.',
                    'give-peer-to-peer'
                ),
                'func' => [$this, 'getTagContent'],
                'context' => 'donationTeamCaptain',
            ],
            [
                'tag' => 'view_all_fundraisers_url',
                'desc' => esc_html__(
                    'The admins view all fundraisers URL',
                    'give-peer-to-peer'
                ),
                'func' => [$this, 'getTagContent'],
                'context' => 'admin',
            ],
            [
                'tag' => 'view_all_teams_url',
                'desc' => esc_html__(
                    'The admins view all teams URL',
                    'give-peer-to-peer'
                ),
                'func' => [$this, 'getTagContent'],
                'context' => 'admin',
            ],
            [
                'tag' => 'fundraiser_goal',
                'desc' => esc_html__(
                    'The fundraisers goal',
                    'give-peer-to-peer'
                ),
                'func' => [$this, 'getTagContent'],
                'context' => 'fundraiser',
            ],
            [
                'tag' => 'fundraiser_progress',
                'desc' => esc_html__(
                    'The total amount of donations made to a fundraiser',
                    'give-peer-to-peer'
                ),
                'func' => [$this, 'getTagContent'],
                'context' => 'fundraiser',
            ],
            [
                'tag' => 'team_goal',
                'desc' => esc_html__(
                    'The team goal',
                    'give-peer-to-peer'
                ),
                'func' => [$this, 'getTagContent'],
                'context' => 'team',
            ],
            [
                'tag' => 'team_progress',
                'desc' => esc_html__(
                    'The total amount of donations made to a team',
                    'give-peer-to-peer'
                ),
                'func' => [$this, 'getTagContent'],
                'context' => 'team',
            ],
        ];

        return array_merge($emailTags, $p2pEmailTags);
    }

    /**
     * @since 1.5.0
     *
     * @param array  $args
     * @param string $tag
     *
     * @return string|null
     */
    public function getTagContent(array $args, string $tag): ?string
    {
        $content = '';

        if ( ! give_check_variable($args, 'isset', 0, 'payment_id') &&
             ! give_check_variable($args, 'isset', 0, 'user_id')) {
            return $content;
        }

        try {
            switch ($tag) {
                case 'campaign_name':
                    $content = $this->getCampaignName($args);
                    break;
                case 'team_name':
                    $content = $this->getTeamName($args);
                    break;
                case 'fundraiser_first_name':
                    $content = $this->getFundraiserFirstName($args);
                    break;
                case 'team_captain_first_name':
                    $content = $this->getTeamCaptainFirstName($args);
                    break;
                case 'fundraiser_profile_url':
                    $content = $this->getFundraiserProfileUrl($args);
                    break;
                case 'team_profile_url':
                    $content = $this->getTeamProfileUrl($args);
                    break;
                case 'recipient_name':
                    $content = $this->getRecipientName($args);
                    break;
                case 'recipient_goal':
                    $content = $this->getRecipientGoal($args);
                    break;
                case 'recipient_progress':
                    $content = $this->getRecipientProgress($args);
                    break;
                case 'view_all_fundraisers_url':
                    $content = $this->getAllFundraisersUrl($args);
                    break;
                case 'view_all_teams_url':
                    $content = $this->getAllTeamsUrl($args);
                    break;
                case 'fundraiser_goal':
                    $content = $this->getFundraiserGoal($args);
                    break;
                case 'fundraiser_progress':
                    $content = $this->getFundraiserProgress($args);
                    break;
                case 'team_goal':
                    $content = $this->getTeamGoal($args);
                    break;
                case 'team_progress':
                    $content = $this->getTeamProgress($args);
                    break;
            }
        } catch (Exception $e) {
            Log::error(
                'There was an error within the P2P add-on while trying to process email tags.',
                [
                    'Error Message' => $e->getMessage(),
                    'tag' => $tag,
                    'args' => $args,
                    'category' => 'Peer-to-Peer',
                    'source' => 'Peer-to-Peer Add-on',
                ]
            );
        }

        return $content;
    }

    /**
     * @since 1.5.0
     *
     * @param string $template
     *
     * @return string
     */
    public function handleTagsOnEmailPreview(string $template): string
    {
        $get_data = give_clean(filter_input_array(INPUT_GET));

        $args = [
            'payment_id' => give_check_variable($get_data, 'isset_empty', 0, 'preview_id'),
            'user_id' => give_check_variable($get_data, 'isset_empty', 0, 'user_id'),
            'new_fundraiser_joined_user_id' => give_check_variable($get_data, 'isset_empty', 0,
                'new_fundraiser_joined_user_id'),
        ];

        $template = str_replace(
            [
                '{campaign_name}',
                '{team_name}',
                '{fundraiser_first_name}',
                '{team_captain_first_name}',
                '{fundraiser_profile_url}',
                '{team_profile_url}',
                '{recipient_name}',
                '{recipient_goal}',
                '{recipient_progress}',
                '{view_all_fundraisers_url}',
                '{view_all_teams_url}',
                '{fundraiser_goal}',
                '{fundraiser_progress}',
                '{team_goal}',
                '{team_progress}',
            ],
            [
                $this->getCampaignName($args),
                $this->getTeamName($args),
                $this->getFundraiserFirstName($args),
                $this->getTeamCaptainFirstName($args),
                $this->getFundraiserProfileUrl($args),
                $this->getTeamProfileUrl($args),
                $this->getRecipientName($args),
                $this->getRecipientGoal($args),
                $this->getRecipientprogress($args),
                $this->getAllFundraisersUrl($args),
                $this->getAllTeamsUrl($args),
                $this->getFundraiserGoal($args),
                $this->getFundraiserProgress($args),
                $this->getTeamGoal($args),
                $this->getTeamProgress($args),
            ],
            $template
        );

        return $template;
    }

    /**
     * @since 1.5.0
     *
     * @param array $args
     *
     * @return string
     */
    public function getCampaignName(array $args): string
    {
        $campaignName = '***campaign-name***';

        $payment_id = $args['payment_id'];
        $user_id = $args['user_id'];
        $fundraiser = false;

        if ($payment_id) {
            $fundraiser = $this->getFundraiserFromPayment($payment_id);
        }

        if ($user_id) {
            $fundraiser = Fundraiser::getFundraiserByUserId($user_id);
        }

        if ($fundraiser) {
            $campaign = Campaign::getCampaign($fundraiser->getCampaignId());
            $campaignName = $campaign->getTitle();
        }

        return $campaignName;
    }

    /**
     * @since 1.5.0
     *
     * @param array $args
     *
     * @return string
     */
    public function getTeamName(array $args): string
    {
        $teamName = '***team-name***';

        $fundraiser = false;
        $payment_id = $args['payment_id'];
        $user_id = $args['user_id'];

        if ($payment_id) {
            $fundraiser = $this->getFundraiserFromPayment($payment_id);
        }

        if ($user_id) {
            $fundraiser = Fundraiser::getFundraiserByUserId($user_id);
        }

        if ($fundraiser && $fundraiser->getTeamId()) {
            $teamName = Team::getTeam($fundraiser->getTeamId())->getName();
        }

        if ($fundraiser && ! $fundraiser->getTeamId() && $fundraiser->isTeamCaptain()) {
            $teamName = Team::getTeamByOwnerId($fundraiser->getId())->getName();
        }

        return $teamName;
    }

    /**
     * @since 1.5.0
     *
     * @param array $args
     *
     * @return string
     */
    public function getTeamProfileUrl(array $args): string
    {
        $teamProfileUrl = '***team-profile-url***';

        $fundraiser = false;
        $payment_id = $args['payment_id'];
        $user_id = $args['user_id'];

        if ($payment_id) {
            $fundraiser = $this->getFundraiserFromPayment($payment_id);
        }

        if ($user_id) {
            $fundraiser = Fundraiser::getFundraiserByUserId($user_id);
        }

        if ($fundraiser && $fundraiser->getTeamId()) {
            $campaign = Campaign::getCampaign($fundraiser->getCampaignId());
            $teamProfileUrl = home_url(
                '/campaign/' . $campaign->getUrl() . '/team/' . $fundraiser->getTeamId()
            );
        }

        if ($fundraiser && ! $fundraiser->getTeamId() && $fundraiser->isTeamCaptain()) {
            $teamId = Team::getTeamByOwnerId($fundraiser->getId())->getId();
            $campaign = Campaign::getCampaign($fundraiser->getCampaignId());
            $teamProfileUrl = home_url(
                '/campaign/' . $campaign->getUrl() . '/team/' . $teamId
            );
        }

        return $teamProfileUrl;
    }

    /**
     * @since 1.5.0
     *
     * @param array $args
     *
     * @return string
     */
    public function getTeamCaptainFirstName(array $args): string
    {
        $teamCaptainFirstName = '***team-captain-first-name***';

        $fundraiser = false;
        $payment_id = $args['payment_id'];
        $user_id = $args['user_id'];

        if ($payment_id) {
            $fundraiser = $this->getFundraiserFromPayment($payment_id);
        }

        if ($user_id) {
            $fundraiser = Fundraiser::getFundraiserByUserId($user_id);
        }

        if ($fundraiser && $fundraiser->isTeamCaptain()) {
            $user = get_user_by('id', $fundraiser->getUserId());
            $teamCaptainFirstName = $user->first_name;
        }

        if ($fundraiser && ! $fundraiser->isTeamCaptain() && $fundraiser->getTeamId()) {
            $teamOwnerId = Team::getTeam($fundraiser->getTeamId())->getOwnerId();
            $fundraiser = Fundraiser::getFundraiser($teamOwnerId);
            $user = get_user_by('id', $fundraiser->getUserId());
            $teamCaptainFirstName = $user->first_name;
        }

        return $teamCaptainFirstName;
    }

    /**
     * @since 1.5.0
     *
     * @param array $args
     *
     * @return string
     */
    public function getFundraiserFirstName(array $args): string
    {
        $fundraiserFirstName = '***fundraiser-first-name***';

        $fundraiser = false;
        $payment_id = $args['payment_id'];
        $user_id = $args['user_id'];
        $new_fundraiser_joined_user_id = $args['new_fundraiser_joined_user_id'];

        if ($payment_id) {
            $fundraiser = $this->getFundraiserFromPayment($payment_id);
        }

        if ($user_id && ! $new_fundraiser_joined_user_id) {
            $fundraiser = Fundraiser::getFundraiserByUserId($user_id);
        }

        if ($new_fundraiser_joined_user_id) {
            $fundraiser = Fundraiser::getFundraiserByUserId($new_fundraiser_joined_user_id);
        }

        if ($fundraiser) {
            $user = get_user_by('id', $fundraiser->getUserId());
            $fundraiserFirstName = $user->display_name;
        }

        return $fundraiserFirstName;
    }

    /**
     * @since 1.5.0
     *
     * @param array $args
     *
     * @return string
     */
    public function getFundraiserProfileUrl(array $args): string
    {
        $fundraiserProfileUrl = '***fundraiser-profile-url***';

        $fundraiser = false;
        $payment_id = $args['payment_id'];
        $user_id = $args['user_id'];
        $new_fundraiser_joined_user_id = $args['new_fundraiser_joined_user_id'];

        if ($payment_id) {
            $fundraiser = $this->getFundraiserFromPayment($payment_id);
        }

        if ($user_id && ! $new_fundraiser_joined_user_id) {
            $fundraiser = Fundraiser::getFundraiserByUserId($user_id);
        }

        if ($new_fundraiser_joined_user_id) {
            $fundraiser = Fundraiser::getFundraiserByUserId($new_fundraiser_joined_user_id);
        }

        if ($fundraiser) {
            $campaign = Campaign::getCampaign($fundraiser->getCampaignId());
            $fundraiserProfileUrl = home_url(
                '/campaign/' . $campaign->getUrl() . '/fundraiser/' . $fundraiser->getId()
            );
        }

        return $fundraiserProfileUrl;
    }

    /**
     * @since 1.5.0
     *
     * @param array $args
     *
     * @return string
     */
    public function getAllFundraisersUrl(array $args): string
    {
        $allFundraisersUrl = '***view-all-fundraisers-url***';

        $user_id = $args['user_id'];
        $fundraiser = Fundraiser::getFundraiserByUserId($user_id);

        if ($fundraiser) {
            $campaign = Campaign::getCampaign($fundraiser->getCampaignId());

            $allFundraisersUrl = admin_url(
                sprintf(
                    'edit.php?post_type=give_forms&page=%1$s&id=%2$s&tab=fundraisers',
                    EditCampaign::PAGE_SLUG,
                    $campaign->getId()
                ));
        }

        return $allFundraisersUrl;
    }

    /**
     * @since 1.5.0
     *
     * @param array $args
     *
     * @return string
     */
    public function getAllTeamsUrl(array $args): string
    {
        $allTeamsUrl = '***view-all-teams-url***';

        $user_id = $args['user_id'];
        $fundraiser = Fundraiser::getFundraiserByUserId($user_id);


        if ($fundraiser) {
            $campaign = Campaign::getCampaign($fundraiser->getCampaignId());

            $allTeamsUrl = admin_url(
                sprintf(
                    'edit.php?post_type=give_forms&page=%1$s&id=%2$s&tab=teams',
                    EditCampaign::PAGE_SLUG,
                    $campaign->getId()
                ));
        }

        return $allTeamsUrl;
    }

    /**
     * @since 1.5.0
     *
     * @param int $payment_id
     *
     * @return false|\GiveP2P\P2P\Models\Fundraiser
     */
    public function getFundraiserFromPayment(int $payment_id = 0)
    {
        $payment = new Give_Payment($payment_id);

        if ( ! isset($payment->payment_meta['p2pSourceID']) || ! isset($payment->payment_meta['p2pSourceType'])) {
            return false;
        }

        $p2pSourceID = $payment->payment_meta['p2pSourceID'];
        $p2pSourceType = $payment->payment_meta['p2pSourceType'];

        $fundraiser = false;

        if ('fundraiser' === $p2pSourceType && is_numeric($p2pSourceID)) {
            $fundraiser = Fundraiser::getFundraiser($p2pSourceID);
        }

        if ('team' === $p2pSourceType && is_numeric($p2pSourceID)) {
            $teamOwnerId = Team::getTeam($p2pSourceID)->getOwnerId();
            $fundraiser = Fundraiser::getFundraiser($teamOwnerId);
        }

        return $fundraiser;
    }

    /**
     * @since 1.5.0
     *
     *
     * @return false|String
     */
    public function getRecipientName(array $args): string
    {
        $recipientName = '***recipient-name***';

        $payment_id = $args['payment_id'];

        $payment = new Give_Payment($payment_id);

        if ( ! isset($payment->payment_meta['p2pSourceID']) || ! isset($payment->payment_meta['p2pSourceType'])) {
            return $recipientName;
        }

        $p2pSourceID = $payment->payment_meta['p2pSourceID'];
        $p2pSourceType = $payment->payment_meta['p2pSourceType'];

        if ('fundraiser' === $p2pSourceType && is_numeric($p2pSourceID)) {
            $fundraiser = Fundraiser::getFundraiser($p2pSourceID);
            $fundraiserName = get_user_by('id', $fundraiser->getUserId())->display_name;
            $recipientName = $fundraiserName;
        }

        if ('team' === $p2pSourceType && is_numeric($p2pSourceID)) {
            $team = Team::getTeam($p2pSourceID);
            $recipientName = $team->getName();
        }

        return $recipientName;
    }

    /**
     * @since 1.5.0
     *
     *
     * @return false|String
     */
    public function getRecipientGoal(array $args): string
    {
        $recipientGoal = '***recipient-goal***';

        $payment_id = $args['payment_id'];

        $payment = new Give_Payment($payment_id);

        if ( ! isset($payment->payment_meta['p2pSourceID']) || ! isset($payment->payment_meta['p2pSourceType'])) {
            return $recipientGoal;
        }

        $p2pSourceID = $payment->payment_meta['p2pSourceID'];
        $p2pSourceType = $payment->payment_meta['p2pSourceType'];

        if ('fundraiser' === $p2pSourceType && is_numeric($p2pSourceID)) {
            $recipientGoal = $this->getFundraiserGoal($args);
        }

        if ('team' === $p2pSourceType && is_numeric($p2pSourceID)) {
            $recipientGoal = $this->getTeamGoal($args);
        }

        return $recipientGoal;
    }

    /**
     * @since 1.5.0
     *
     *
     * @return false|String
     */
    public function getRecipientProgress(array $args): string
    {
        $recipientProgress = '***recipient-progress***';

        $payment_id = $args['payment_id'];

        $payment = new Give_Payment($payment_id);

        if ( ! isset($payment->payment_meta['p2pSourceID']) || ! isset($payment->payment_meta['p2pSourceType'])) {
            return $recipientProgress;
        }

        $p2pSourceID = $payment->payment_meta['p2pSourceID'];
        $p2pSourceType = $payment->payment_meta['p2pSourceType'];

        if ('fundraiser' === $p2pSourceType && is_numeric($p2pSourceID)) {
            $recipientProgress = $this->getFundraiserProgress($args);
        }

        if ('team' === $p2pSourceType && is_numeric($p2pSourceID)) {
            $recipientProgress = $this->getTeamProgress($args);
        }

        return $recipientProgress;
    }

    /**
     * @since 1.5.0
     *
     *
     * @return false|string
     */
    public function getFundraiserGoal(array $args): string
    {
        $fundraiserGoal = '***fundraiser-goal***';

        $fundraiser = false;
        $payment_id = $args['payment_id'];
        $user_id = $args['user_id'];
        $new_fundraiser_joined_user_id = $args['new_fundraiser_joined_user_id'];

        if ($payment_id) {
            $fundraiser = $this->getFundraiserFromPayment($payment_id);
        }

        if ($user_id && ! $new_fundraiser_joined_user_id) {
            $fundraiser = Fundraiser::getFundraiserByUserId($user_id);
        }

        if ($new_fundraiser_joined_user_id) {
            $fundraiser = Fundraiser::getFundraiserByUserId($new_fundraiser_joined_user_id);
        }

        if ($fundraiser) {
            $goal = new Money($fundraiser->getGoal(), give_get_currency());
            $fundraiserGoal = $goal->formatToLocale();
        }

        return $fundraiserGoal;
    }


    /**
     * @since 1.5.0
     *
     *
     * @return false|string
     */
    public function getFundraiserProgress(array $args): string
    {
        $fundraiserProgress = '***fundraiser-progress***';

        $fundraiser = false;
        $payment_id = $args['payment_id'];
        $user_id = $args['user_id'];

        if ($payment_id) {
            $fundraiser = $this->getFundraiserFromPayment($payment_id);
        }

        if ($user_id) {
            $fundraiser = Fundraiser::getFundraiserByUserId($user_id);
        }

        if ($fundraiser) {
            $amount = new Money(Fundraiser::getRaisedAmount($fundraiser->getId()), give_get_currency());
            $fundraiserProgress = $amount->formatToLocale();
        }

        return $fundraiserProgress;
    }


    /**
     * @since 1.5.0
     *
     * @param array $args
     *
     * @return string
     */
    public function getTeamGoal(array $args): string
    {
        $teamGoal = '***team-goal***';

        $fundraiser = false;
        $payment_id = $args['payment_id'];
        $user_id = $args['user_id'];

        if ($payment_id) {
            $fundraiser = $this->getFundraiserFromPayment($payment_id);
        }

        if ($user_id) {
            $fundraiser = Fundraiser::getFundraiserByUserId($user_id);
        }

        if ($fundraiser && ! $fundraiser->getTeamId() && $fundraiser->isTeamCaptain()) {
            $team = Team::getTeamByOwnerId($fundraiser->getId());
            $amount = new Money($team->getGoal(), give_get_currency());
            $teamGoal = $amount->formatToLocale();
        }

        if ($fundraiser && $fundraiser->getTeamId()) {
            $team = Team::getTeam($fundraiser->getTeamId());
            $goal = new Money($team->getGoal(), give_get_currency());
            $teamGoal = $goal->formatToLocale();
        }

        return $teamGoal;
    }

    /**
     * @since 1.5.0
     *
     * @param array $args
     *
     * @return string
     */
    public function getTeamProgress(array $args): string
    {
        $teamProgress = '***team-progress***';

        $fundraiser = false;
        $payment_id = $args['payment_id'];
        $user_id = $args['user_id'];


        if ($payment_id) {
            $fundraiser = $this->getFundraiserFromPayment($payment_id);
        }

        if ($user_id) {
            $fundraiser = Fundraiser::getFundraiserByUserId($user_id);
        }

        if ($fundraiser && ! $fundraiser->getTeamId() && $fundraiser->isTeamCaptain()) {
            $team = Team::getTeamByOwnerId($fundraiser->getId());
            $amount = new Money(Team::getRaisedAmount($team->getId()), give_get_currency());
            $teamProgress = $amount->formatToLocale();
        }

        if ($fundraiser && $fundraiser->getTeamId()) {
            $team = Team::getTeam($fundraiser->getTeamId());
            $amount = new Money(Team::getRaisedAmount($team->getId()), give_get_currency());
            $teamProgress = $amount->formatToLocale();
        }

        return $teamProgress;
    }


}
