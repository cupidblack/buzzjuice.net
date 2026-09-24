<?php
/*
if ($f == 'follow_user' && $wo['loggedin'] === true) {
    if (isset($_GET['following_id']) && Wo_CheckMainSession($hash_id) === true) {
        $user_followers = Wo_CountFollowing($wo['user']['id'], true);
        $friends_limit  = $wo['config']['connectivitySystemLimit'];
        if (Wo_IsFollowing($_GET['following_id'], $wo['user']['user_id']) === true || Wo_IsFollowRequested($_GET['following_id'], $wo['user']['user_id']) === true) {
            if (Wo_DeleteFollow($_GET['following_id'], $wo['user']['user_id'])) {
                $data = array(
                    'status' => 200,
                    'can_send' => 0,
                    'html' => ''
                );
            }
        } else if ($wo['config']['connectivitySystem'] == 1 && $user_followers >= $friends_limit) {
            $data = array(
                'status' => 400,
                'can_send' => 0
            );
        } else {
            if (Wo_RegisterFollow($_GET['following_id'], $wo['user']['user_id'])) {
                $data = array(
                    'status' => 200,
                    'can_send' => 0,
                    'html' => ''
                );
                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }
            }
        }
    }
    if ($wo['loggedin'] == true) {
        Wo_CleanCache();
    }
    header("Content-type: application/json");
    echo json_encode($data);
    exit();
}
*/
/**
 * Buzzjuice Streams - Follow / Unfollow endpoint
 *
 * File:
 * streams/xhr/follow_user.php
 *
 * Behaviour:
 * - Validates the main Streams session.
 * - Uses the currently authenticated Streams user.
 * - Unfollow is always evaluated before the follow limit.
 * - The configured connectivitySystemLimit applies only to
 *   creation of a NEW active follow.
 * - The configured limit is read on every request.
 * - Wo_RegisterFollow() remains responsible for creating the
 *   immediate active relationship.
 * - The response explicitly identifies the resulting state.
 * - No optimistic client-side state is required.
 *
 * Security:
 * - No raw session ID is logged.
 * - No hash_id or main_hash_id is logged.
 */

if (
    !isset($f) ||
    $f !== 'follow_user' ||
    empty($wo['loggedin']) ||
    $wo['loggedin'] !== true
) {
    exit();
}

header('Content-Type: application/json; charset=UTF-8');


/* ---------------------------------------------------------
 * Default response
 * --------------------------------------------------------- */

$data = array(
    'status'       => 400,
    'can_send'     => 0,
    'follow_state' => 'follow',
    'limit_reached'=> 0,
    'message'      => 'Unable to process the follow request.'
);


/* ---------------------------------------------------------
 * Resolve authenticated user
 * --------------------------------------------------------- */

$logged_user_id = 0;

if (
    isset($wo['user']['user_id']) &&
    is_numeric($wo['user']['user_id'])
) {
    $logged_user_id = (int) $wo['user']['user_id'];
}

if ($logged_user_id < 1) {

    $data['status'] = 401;
    $data['message'] = 'Please log in again.';

    if (function_exists('bz_streams_log')) {
        bz_streams_log(
            'follow_request_rejected',
            array(
                'reason' => 'invalid_authenticated_user'
            )
        );
    }

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit();
}


/* ---------------------------------------------------------
 * Resolve target user
 *
 * POST is preferred.
 * GET remains supported for compatibility.
 * --------------------------------------------------------- */

$following_id = 0;

if (
    isset($_POST['following_id']) &&
    is_string($_POST['following_id']) &&
    is_numeric($_POST['following_id'])
) {
    $following_id = (int) $_POST['following_id'];
}
elseif (
    isset($_GET['following_id']) &&
    is_numeric($_GET['following_id'])
) {
    $following_id = (int) $_GET['following_id'];
}


if ($following_id < 1) {

    $data['status'] = 400;
    $data['message'] = 'Invalid user.';

    if (function_exists('bz_streams_log')) {
        bz_streams_log(
            'follow_request_rejected',
            array(
                'reason' => 'invalid_following_id',
                'logged_user_id' => $logged_user_id
            )
        );
    }

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit();
}


/* ---------------------------------------------------------
 * Prevent self-follow
 * --------------------------------------------------------- */

if ($following_id === $logged_user_id) {

    $data['status'] = 400;
    $data['message'] = 'You cannot follow yourself.';

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit();
}


/* ---------------------------------------------------------
 * Resolve hash
 *
 * The JavaScript sends hash_id through POST.
 *
 * GET compatibility is retained.
 * --------------------------------------------------------- */

$hash_id = '';

if (
    isset($_POST['hash_id']) &&
    is_string($_POST['hash_id'])
) {
    $hash_id = trim($_POST['hash_id']);
}
elseif (
    isset($_GET['hash_id']) &&
    is_string($_GET['hash_id'])
) {
    $hash_id = trim($_GET['hash_id']);
}


/* ---------------------------------------------------------
 * Validate main session
 * --------------------------------------------------------- */

$main_session_valid = false;

if (function_exists('Wo_CheckMainSession')) {
    $main_session_valid = (
        Wo_CheckMainSession($hash_id) === true
    );
}

if (!$main_session_valid) {

    $data['status'] = 403;
    $data['message'] =
        'Your session has expired. Please refresh the page.';

    if (function_exists('bz_streams_log')) {

        bz_streams_log(
            'follow_request_rejected',
            array(
                'reason' => 'main_session_validation_failed',
                'logged_user_id' => $logged_user_id,
                'following_id' => $following_id,
                'session_active' => (
                    function_exists('session_status') &&
                    session_status() === PHP_SESSION_ACTIVE
                ) ? 1 : 0,
                'main_session_present' => (
                    isset($_SESSION['main_hash_id']) &&
                    is_string($_SESSION['main_hash_id']) &&
                    $_SESSION['main_hash_id'] !== ''
                ) ? 1 : 0,
                'hash_received' => (
                    $hash_id !== ''
                ) ? 1 : 0
            )
        );
    }

    echo json_encode(
        $data,
        JSON_UNESCAPED_SLASHES |
        JSON_UNESCAPED_UNICODE
    );

    exit();
}


/* ---------------------------------------------------------
 * Determine current relationship
 * --------------------------------------------------------- */

$is_following = (
    Wo_IsFollowing(
        $following_id,
        $logged_user_id
    ) === true
);

$is_requested = (
    Wo_IsFollowRequested(
        $following_id,
        $logged_user_id
    ) === true
);


/* ---------------------------------------------------------
 * EXISTING RELATIONSHIP
 *
 * Unfollow must ALWAYS happen before the follow-limit check.
 *
 * This means a user at the configured maximum can still
 * unfollow somebody.
 * --------------------------------------------------------- */

if ($is_following || $is_requested) {

    $deleted = Wo_DeleteFollow(
        $following_id,
        $logged_user_id
    );

    if ($deleted) {

        $data = array(
            'status'        => 200,
            'can_send'      => 0,
            'follow_state'  => 'follow',
            'limit_reached' => 0,
            'message'       => 'Unfollow successful.'
        );

        if (function_exists('bz_streams_log')) {

            bz_streams_log(
                'follow_request_success',
                array(
                    'action' => 'unfollow',
                    'logged_user_id' => $logged_user_id,
                    'following_id' => $following_id
                )
            );
        }

    } else {

        $data['status'] = 400;
        $data['message'] =
            'Unable to unfollow this user.';

        if (function_exists('bz_streams_log')) {

            bz_streams_log(
                'follow_request_failed',
                array(
                    'action' => 'unfollow',
                    'logged_user_id' => $logged_user_id,
                    'following_id' => $following_id,
                    'reason' => 'Wo_DeleteFollow_failed'
                )
            );
        }
    }

}


/* ---------------------------------------------------------
 * NEW FOLLOW
 *
 * The limit is checked ONLY here.
 * --------------------------------------------------------- */

else {

    global $sqlConnect;


    /*
     * Read the current administrative configuration.
     *
     * This is deliberately read for every request so that
     * changing the limit from 5000 to 10 or 2 takes effect
     * without requiring a restart or cached JavaScript.
     */
    $connectivity_system = 0;

    if (
        isset($wo['config']['connectivitySystem']) &&
        is_numeric($wo['config']['connectivitySystem'])
    ) {
        $connectivity_system =
            (int) $wo['config']['connectivitySystem'];
    }


    $friends_limit = 0;

    if (
        isset($wo['config']['connectivitySystemLimit']) &&
        is_numeric($wo['config']['connectivitySystemLimit'])
    ) {
        $friends_limit =
            (int) $wo['config']['connectivitySystemLimit'];
    }


    /*
     * Count the user's current ACTIVE outgoing follows
     * directly from T_FOLLOWERS.
     *
     * We intentionally do not rely solely on a cached or
     * indirectly derived value.
     */
    $current_follow_count = 0;

    if (
        isset($sqlConnect) &&
        $sqlConnect
    ) {

        $safe_logged_user_id =
            Wo_Secure($logged_user_id);

        $count_query = mysqli_query(
            $sqlConnect,
            "SELECT COUNT(`id`) AS `follow_count`
             FROM " . T_FOLLOWERS . "
             WHERE `follower_id` = {$safe_logged_user_id}
             AND `active` = '1'
             AND `following_id` <> {$safe_logged_user_id}"
        );

        if ($count_query) {

            $count_row =
                mysqli_fetch_assoc($count_query);

            if (
                is_array($count_row) &&
                isset($count_row['follow_count'])
            ) {
                $current_follow_count =
                    (int) $count_row['follow_count'];
            }
        }
    }


    /*
     * IMPORTANT:
     *
     * connectivitySystemLimit is enforced when the
     * connectivity/friend system is active.
     *
     * A limit of 0 means no configured numerical limit.
     */
    $limit_reached = false;

    if (
        $connectivity_system === 1 &&
        $friends_limit > 0 &&
        $current_follow_count >= $friends_limit
    ) {
        $limit_reached = true;
    }


    if ($limit_reached) {

        $data = array(
            'status'        => 400,
            'can_send'      => 0,
            'follow_state'  => 'follow',
            'limit_reached' => 1,
            'message'       =>
                'You have reached the maximum follow limit of '
                . $friends_limit
                . ' users.'
        );


        if (function_exists('bz_streams_log')) {

            bz_streams_log(
                'follow_limit_reached',
                array(
                    'logged_user_id' => $logged_user_id,
                    'following_id' => $following_id,
                    'current_follow_count' =>
                        $current_follow_count,
                    'configured_follow_limit' =>
                        $friends_limit,
                    'connectivity_system' =>
                        $connectivity_system
                )
            );
        }

    }


    /* -----------------------------------------------------
     * Create NEW follow
     * ----------------------------------------------------- */

    else {

        $registered = Wo_RegisterFollow(
            $following_id,
            $logged_user_id
        );


        if ($registered) {

            /*
             * Wo_RegisterFollow() is expected to create
             * active = 1.
             *
             * Verify the database state before reporting
             * success to JavaScript.
             */
            $persisted = (
                Wo_IsFollowing(
                    $following_id,
                    $logged_user_id
                ) === true
            );


            if ($persisted) {

                $data = array(
                    'status'        => 200,
                    'can_send'      => 0,
                    'follow_state'  => 'following',
                    'limit_reached' => 0,
                    'message'       => 'Follow successful.'
                );


                if (Wo_CanSenEmails()) {
                    $data['can_send'] = 1;
                }


                if (function_exists('bz_streams_log')) {

                    bz_streams_log(
                        'follow_request_success',
                        array(
                            'action' => 'follow',
                            'logged_user_id' =>
                                $logged_user_id,
                            'following_id' =>
                                $following_id,
                            'current_follow_count_before' =>
                                $current_follow_count,
                            'configured_follow_limit' =>
                                $friends_limit
                        )
                    );
                }

            } else {

                $data = array(
                    'status'        => 500,
                    'can_send'      => 0,
                    'follow_state'  => 'follow',
                    'limit_reached' => 0,
                    'message'       =>
                        'The follow could not be confirmed.'
                );


                if (function_exists('bz_streams_log')) {

                    bz_streams_log(
                        'follow_persistence_verification_failed',
                        array(
                            'logged_user_id' =>
                                $logged_user_id,
                            'following_id' =>
                                $following_id
                        )
                    );
                }
            }

        } else {

            $data = array(
                'status'        => 400,
                'can_send'      => 0,
                'follow_state'  => 'follow',
                'limit_reached' => 0,
                'message'       =>
                    'Unable to create the follow.'
            );


            if (function_exists('bz_streams_log')) {

                bz_streams_log(
                    'follow_registration_failed',
                    array(
                        'logged_user_id' =>
                            $logged_user_id,
                        'following_id' =>
                            $following_id
                    )
                );
            }
        }
    }
}


/* ---------------------------------------------------------
 * Clean cache
 * --------------------------------------------------------- */

if ($wo['loggedin'] === true) {
    Wo_CleanCache();
}


/* ---------------------------------------------------------
 * Return JSON
 * --------------------------------------------------------- */

echo json_encode(
    $data,
    JSON_UNESCAPED_SLASHES |
    JSON_UNESCAPED_UNICODE
);

exit();