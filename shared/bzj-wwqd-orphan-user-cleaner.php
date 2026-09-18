<?php
/**
 * Buzzjuice WoWonder + QuickDate Orphan User Cleaner & Platform-ID Auditor
 *
 * Install:
 *   /shared/bzj-wwqd-orphan-user-cleaner.php
 *
 * IMPORTANT:
 * - GET is read-only.
 * - POST requires a logged-in WordPress administrator, a WordPress nonce,
 *   and BZJ_ORPHAN_CLEANER_KEY.
 * - Wo_Users / QuickDate users / WordPress users are NEVER deleted.
 * - Media/S3 objects are NEVER deleted by this utility.
 * - Only explicitly allow-listed direct user-reference columns are cleaned.
 * - Destructive operations perform a fresh live scan immediately before writes.
 * - ID repair is permitted only for an unambiguous username/email match.
 * - Blank platform metadata is normal and is not treated as a mismatch.
 *
 * Based on the supplied Wo_DeleteUser(), QuickDate delete_user(), and
 * shared/db_helpers.php.
 */

declare(strict_types=1);

if (PHP_SAPI === 'cli') {
    http_response_code(403);
    exit("This utility is web-admin only.\n");
}

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
@set_time_limit(0);

const BZJ_OUC_VERSION = '2026-09-18.10';
const BZJ_OUC_NONCE_ACTION = 'bzj_wwqd_orphan_cleaner';
const BZJ_OUC_CLEAN_CONFIRMATION = 'DELETE CONFIRMED ORPHANS';
const BZJ_OUC_REPAIR_CONFIRMATION = 'REPAIR SELECTED ID MISMATCHES';
const BZJ_OUC_SAMPLE_LIMIT = 25;
const BZJ_OUC_SCHEMA_SAMPLE_LIMIT = 10;
const BZJ_OUC_MAX_MAPPING_USERS = 100000;

/* -------------------------------------------------------------------------
 * Bootstrap
 * ---------------------------------------------------------------------- */

$wp_load = dirname(__DIR__) . '/wp-load.php';

if (!is_file($wp_load)) {
    http_response_code(500);
    exit('WordPress bootstrap could not be located.');
}

require_once $wp_load;
require_once __DIR__ . '/db_helpers.php';

if (
    !function_exists('is_user_logged_in') ||
    !function_exists('current_user_can') ||
    !function_exists('wp_create_nonce') ||
    !function_exists('wp_verify_nonce')
) {
    http_response_code(500);
    exit('Required WordPress functions are unavailable.');
}

if (function_exists('nocache_headers')) {
    nocache_headers();
}
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (!is_user_logged_in() || !current_user_can('manage_options')) {
    http_response_code(403);
    exit('Administrator access is required.');
}

/* -------------------------------------------------------------------------
 * Helpers
 * ---------------------------------------------------------------------- */

function bzj_ouc_h(mixed $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function bzj_ouc_json(mixed $value): string
{
    $json = json_encode(
        $value,
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
    return $json === false ? '{}' : $json;
}

function bzj_ouc_fail(string $message): never
{
    throw new RuntimeException($message);
}

function bzj_ouc_log(string $event, array $data = []): void
{
    if (function_exists('bzj_log')) {
        try {
            bzj_log($event, $data);
            return;
        } catch (Throwable $e) {
            error_log('BZJ cleaner logging failed: ' . $e->getMessage());
        }
    }
    error_log('BZJ cleaner [' . $event . '] ' . bzj_ouc_json($data));
}

function bzj_ouc_normalize(mixed $value): string
{
    $value = trim((string)$value);
    return function_exists('mb_strtolower')
        ? mb_strtolower($value, 'UTF-8')
        : strtolower($value);
}

function bzj_ouc_identifier(string $identifier): string
{
    /*
     * Allows ordinary identifiers and the historical WoWonder "from_id "
     * column containing a trailing space.
     */
    if (!preg_match('/^[A-Za-z0-9_]+ ?$/', $identifier)) {
        bzj_ouc_fail('Unsafe SQL identifier: ' . $identifier);
    }
    return '`' . $identifier . '`';
}

function bzj_ouc_query(mysqli $db, string $sql): mysqli_result|bool
{
    $result = $db->query($sql);
    if ($result === false) {
        throw new RuntimeException($db->error);
    }
    return $result;
}

function bzj_ouc_table_exists(mysqli $db, string $table): bool
{
    $safe = $db->real_escape_string($table);
    $result = bzj_ouc_query($db, "SHOW TABLES LIKE '{$safe}'");
    return $result instanceof mysqli_result && $result->num_rows > 0;
}

function bzj_ouc_columns(mysqli $db, string $table): array
{
    $result = bzj_ouc_query($db, 'SHOW COLUMNS FROM ' . bzj_ouc_identifier($table));
    $columns = [];
    while ($row = $result->fetch_assoc()) {
        if (isset($row['Field'])) {
            $columns[strtolower((string)$row['Field'])] = (string)$row['Field'];
        }
    }
    return $columns;
}

function bzj_ouc_engine(mysqli $db, string $table): array
{
    $safe = $db->real_escape_string($table);
    $result = bzj_ouc_query($db, "SHOW TABLE STATUS LIKE '{$safe}'");
    $row = $result instanceof mysqli_result ? $result->fetch_assoc() : [];
    $engine = (string)($row['Engine'] ?? '');

    return [
        'engine' => $engine,
        'transactional' => strcasecmp($engine, 'InnoDB') === 0,
    ];
}

function bzj_ouc_wp_table(string $name): string
{
    global $table_prefix;

    $prefix = 'wp_';
    if (isset($table_prefix) && is_string($table_prefix)) {
        $prefix = $table_prefix;
    } elseif (defined('WP_TABLE_PREFIX')) {
        $prefix = (string)WP_TABLE_PREFIX;
    }

    if (!preg_match('/^[A-Za-z0-9_]+$/', $prefix)) {
        bzj_ouc_fail('Unsafe WordPress table prefix.');
    }
    if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) {
        bzj_ouc_fail('Unsafe WordPress table name.');
    }

    return $prefix . $name;
}

/* -------------------------------------------------------------------------
 * Security
 * ---------------------------------------------------------------------- */

function bzj_ouc_configured_key(): string
{
    $key = getenv('BZJ_ORPHAN_CLEANER_KEY');

    if (!is_string($key) || trim($key) === '') {
        bzj_ouc_fail('BZJ_ORPHAN_CLEANER_KEY is missing from the environment.');
    }

    return trim($key);
}

function bzj_ouc_validate_post(): void
{
    $nonce = trim((string)($_POST['bzj_nonce'] ?? ''));

    if ($nonce === '' || !wp_verify_nonce($nonce, BZJ_OUC_NONCE_ACTION)) {
        bzj_ouc_fail('Invalid security token. Please reload the page.');
    }

    $submitted = trim((string)($_POST['bzj_access_key'] ?? ''));

    if ($submitted === '' || !hash_equals(bzj_ouc_configured_key(), $submitted)) {
        bzj_ouc_fail('Invalid cleaner access key.');
    }
}

/* -------------------------------------------------------------------------
 * Connections
 * ---------------------------------------------------------------------- */

function bzj_ouc_connections(): array
{
    $connections = [
        'wp' => get_wp_db_conn(),
        'wo' => get_wowonder_db(),
        'qd' => get_qd_db_conn(),
    ];

    foreach ($connections as $name => $connection) {
        if (!$connection instanceof mysqli || $connection->connect_errno) {
            bzj_ouc_fail(ucfirst($name) . ' database connection failed.');
        }

        if (!$connection->set_charset('utf8mb4')) {
            bzj_ouc_fail(ucfirst($name) . ' database charset could not be set.');
        }
    }

    return $connections;
}

/* -------------------------------------------------------------------------
 * Platform configuration
 *
 * These are direct user-reference columns represented by the supplied
 * deletion functions. Tables not present on a particular installation are
 * skipped safely.
 * ---------------------------------------------------------------------- */

function bzj_ouc_platforms(): array
{
    return [
        'wowonder' => [
            'label' => 'WoWonder Streams',
            'db' => 'wo',
            'users' => 'Wo_Users',
            'id' => 'user_id',
            'username' => 'username',
            'email' => 'email',
            'meta_key' => 'wo_user_id',
            'references' => [
                'Wo_Users_Fields' => ['user_id'],
                'Wo_RecentSearches' => ['user_id', 'search_id'],
                'Wo_GamesPlayers' => ['user_id'],
                'Wo_UserProjects' => ['user_id'],
                'Wo_UserOpenTo' => ['user_id'],
                'Wo_Followers' => ['follower_id', 'following_id'],
                'Wo_Messages' => ['from_id', 'to_id'],
                'Wo_VideosCalls' => ['from_id', 'to_id'],
                'Wo_VideoCalls' => ['from_id', 'to_id'],
                'Wo_AudioCalls' => ['from_id', 'to_id'],
                'Wo_Agora' => ['from_id', 'from_id ', 'to_id'],
                'Wo_Notification' => ['notifier_id', 'recipient_id'],
                'Wo_Reports' => ['user_id'],
                'Wo_AppSessions' => ['user_id'],
                'Wo_AppsSessions' => ['user_id'],
                'Wo_Comments' => ['user_id'],
                'Wo_AnnouncementViews' => ['user_id'],
                'Wo_Likes' => ['user_id'],
                'Wo_Wonders' => ['user_id'],
                'Wo_CommentRepliesLikes' => ['user_id'],
                'Wo_CommentRepliesWonders' => ['user_id'],
                'Wo_SavedPosts' => ['user_id'],
                'Wo_CommentLikes' => ['user_id'],
                'Wo_CommentWonders' => ['user_id'],
                'Wo_CommentsReplies' => ['user_id'],
                'Wo_EventsGoing' => ['user_id'],
                'Wo_EventsInt' => ['user_id'],
                'Wo_EventsInterested' => ['user_id'],
                'Wo_BmLikes' => ['user_id'],
                'Wo_BmDislikes' => ['user_id'],
                'Wo_UserAdsData' => ['user_id'],
                'Wo_PaymentTransactions' => ['userid'],
                'Wo_Activities' => ['user_id', 'follow_id'],
                'Wo_EventsInv' => ['inviter_id', 'invited_id'],
                'Wo_GroupMembers' => ['user_id'],
                'Wo_PagesInvites' => ['inviter_id', 'invited_id'],
                'Wo_PagesInvaites' => ['inviter_id', 'invited_id'],
                'Wo_PinnedPosts' => ['user_id'],
                'Wo_Apps' => ['app_user_id'],
                'Wo_AppsPermission' => ['user_id'],
                'Wo_Codes' => ['user_id'],
                'Wo_Tokens' => ['user_id'],
                'Wo_BlogReaction' => ['user_id'],
                'Wo_PagesLikes' => ['user_id'],
                'Wo_VerificationRequests' => ['user_id'],
                'Wo_ARequests' => ['user_id'],
                'Wo_Blocks' => ['blocker', 'blocked'],
                'Wo_UChats' => ['conversation_user_id', 'user_id'],
                'Wo_Blog' => ['user'],
                'Wo_BlogCommentsReplies' => ['user_id'],
                'Wo_BlogComments' => ['user_id'],
                'Wo_MovieCommentsReplies' => ['user_id'],
                'Wo_MovieComments' => ['user_id'],
                'Wo_AppsHash' => ['user_id'],
                'Wo_ForumThreads' => ['user'],
                'Wo_ForumThreadReplies' => ['poster_id'],
                'Wo_Events' => ['poster_id'],
                'Wo_UserAds' => ['user_id'],
                'Wo_UserStory' => ['user_id'],
                'Wo_HiddenPosts' => ['user_id'],
                'Wo_GroupChat' => ['user_id'],
                'Wo_GroupChatUsers' => ['user_id'],
                'Wo_PageRating' => ['user_id'],
                'Wo_Family' => ['user_id', 'member_id'],
                'Wo_Relationship' => ['from_id', 'to_id'],
                'Wo_RelShip' => ['from_id', 'to_id'],
                'Wo_PageAdmins' => ['user_id'],
                'Wo_GroupAdmins' => ['user_id'],
                'Wo_Reactions' => ['user_id'],
                'Wo_Job' => ['user_id'],
                'Wo_JobApply' => ['user_id'],
                'Wo_Pokes' => ['received_user_id', 'send_user_id'],
                'Wo_UserGifts' => ['from', 'to'],
                'Wo_StorySeen' => ['user_id'],
                'Wo_Refund' => ['user_id'],
                'Wo_InvitationLinks' => ['user_id', 'invited_id'],
                'Wo_InvitaionLinks' => ['user_id', 'invited_id'],
                'Wo_Mute' => ['user_id'],
                'Wo_MuteStory' => ['user_id', 'story_user_id'],
                'Wo_Cast' => ['user_id'],
                'Wo_CastUsers' => ['user_id'],
                'Wo_LiveSub' => ['user_id'],
                'Wo_LiveSubscriptions' => ['user_id'],
                'Wo_Votes' => ['user_id'],
                'Wo_BankTransfer' => ['user_id'],
                'Wo_UserCard' => ['user_id'],
                'Wo_UserAddress' => ['user_id'],
                'Wo_UserOrders' => ['user_id', 'product_owner_id'],
                'Wo_Purchases' => ['user_id', 'owner_id'],
                'Wo_Email' => ['user_id'],
                'Wo_Emails' => ['user_id'],
                'Wo_Posts' => ['user_id', 'recipient_id'],
                'Wo_Pages' => ['user_id'],
                'Wo_Groups' => ['user_id'],
                'Wo_Funding' => ['user_id'],
                'Wo_FundingRaise' => ['user_id'],
                'Wo_Offer' => ['user_id'],
                'Wo_UserExperience' => ['user_id'],
                'Wo_UserCertification' => ['user_id'],
                'Wo_UserMonetization' => ['user_id'],
                'Wo_UserMonetizations' => ['user_id'],
                'Wo_MonetizationSubscription' => ['user_id'],
                'Wo_MonetizationSubscribtion' => ['user_id'],
                'Wo_MonetizationSubscriptions' => ['user_id'],
            ],
        ],
        'quickdate' => [
            'label' => 'QuickDate Socials',
            'db' => 'qd',
            'users' => defined('QD_USERS_TABLE') ? (string)QD_USERS_TABLE : 'users',
            'id' => 'id',
            'username' => 'username',
            'email' => 'email',
            'meta_key' => 'qd_user_id',
            'references' => [
                'blocks' => ['user_id', 'block_userid'],
                'conversations' => ['sender_id', 'receiver_id'],
                'likes' => ['user_id', 'like_userid'],
                'mediafiles' => ['user_id'],
                'messages' => ['from', 'to'],
                'notifications' => ['notifier_id', 'recipient_id'],
                'reports' => ['user_id', 'report_userid'],
                'user_gifts' => ['from', 'to'],
                'views' => ['user_id', 'view_userid'],
                'sessions' => ['user_id'],
                'payments' => ['user_id'],
                'verification_requests' => ['user_id'],
            ],
        ],
    ];
}

function bzj_ouc_table_aliases(): array
{
    return [
        'Wo_AppSessions' => ['Wo_AppSessions', 'Wo_AppsSessions'],
        'Wo_AppsSessions' => ['Wo_AppsSessions', 'Wo_AppSessions'],
        'Wo_EventsInt' => ['Wo_EventsInt', 'Wo_EventsInterested'],
        'Wo_EventsInterested' => ['Wo_EventsInterested', 'Wo_EventsInt'],
        'Wo_PagesInvites' => ['Wo_PagesInvites', 'Wo_PagesInvaites'],
        'Wo_PagesInvaites' => ['Wo_PagesInvaites', 'Wo_PagesInvites'],
        'Wo_LiveSub' => ['Wo_LiveSub', 'Wo_LiveSubscriptions'],
        'Wo_LiveSubscriptions' => ['Wo_LiveSubscriptions', 'Wo_LiveSub'],
        'Wo_Email' => ['Wo_Email', 'Wo_Emails'],
        'Wo_Emails' => ['Wo_Emails', 'Wo_Email'],
        'Wo_UserMonetization' => ['Wo_UserMonetization', 'Wo_UserMonetizations'],
        'Wo_UserMonetizations' => ['Wo_UserMonetizations', 'Wo_UserMonetization'],
        'Wo_MonetizationSubscription' => [
            'Wo_MonetizationSubscription',
            'Wo_MonetizationSubscribtion',
            'Wo_MonetizationSubscriptions'
        ],
        'Wo_MonetizationSubscribtion' => [
            'Wo_MonetizationSubscribtion',
            'Wo_MonetizationSubscription',
            'Wo_MonetizationSubscriptions'
        ],
        'Wo_MonetizationSubscriptions' => [
            'Wo_MonetizationSubscriptions',
            'Wo_MonetizationSubscription',
            'Wo_MonetizationSubscribtion'
        ],
    ];
}

function bzj_ouc_resolve_tables(mysqli $db, string $configured): array
{
    $candidates = bzj_ouc_table_aliases()[$configured] ?? [$configured];
    $resolved = [];

    foreach ($candidates as $candidate) {
        if (bzj_ouc_table_exists($db, $candidate)) {
            $resolved[$candidate] = true;
        }
    }

    return array_keys($resolved);
}

function bzj_ouc_authoritative(mysqli $db, array $platform): array
{
    $table = (string)$platform['users'];

    if (!bzj_ouc_table_exists($db, $table)) {
        bzj_ouc_fail('Authoritative table does not exist: ' . $table);
    }

    $columns = bzj_ouc_columns($db, $table);

    foreach ([$platform['id'], $platform['username'], $platform['email']] as $required) {
        if (!isset($columns[strtolower((string)$required)])) {
            bzj_ouc_fail('Required column missing from ' . $table . ': ' . $required);
        }
    }

    return [
        'table' => $table,
        'id' => $columns[strtolower((string)$platform['id'])],
        'username' => $columns[strtolower((string)$platform['username'])],
        'email' => $columns[strtolower((string)$platform['email'])],
    ];
}

/* -------------------------------------------------------------------------
 * Orphan detection
 * ---------------------------------------------------------------------- */

function bzj_ouc_orphan_condition(
    string $referenceColumn,
    string $usersTable,
    string $userIdColumn
): string {
    $reference = bzj_ouc_identifier($referenceColumn);
    $users = bzj_ouc_identifier($usersTable);
    $userId = bzj_ouc_identifier($userIdColumn);

    /*
     * Only positive numeric references are considered orphan IDs.
     * NULL, blank, zero and non-numeric values are preserved because their
     * meaning cannot safely be inferred from the supplied deletion methods.
     */
    return "
        {$reference} IS NOT NULL
        AND TRIM(CAST({$reference} AS CHAR)) REGEXP '^[0-9]+$'
        AND CAST({$reference} AS UNSIGNED) > 0
        AND NOT EXISTS (
            SELECT 1
            FROM {$users} AS valid_users
            WHERE CAST(valid_users.{$userId} AS UNSIGNED)
                = CAST({$reference} AS UNSIGNED)
        )
    ";
}

function bzj_ouc_scan_platform(mysqli $db, string $platformKey): array
{
    $platforms = bzj_ouc_platforms();
    if (!isset($platforms[$platformKey])) {
        bzj_ouc_fail('Unknown platform: ' . $platformKey);
    }

    $platform = $platforms[$platformKey];
    $auth = bzj_ouc_authoritative($db, $platform);

    $countResult = bzj_ouc_query(
        $db,
        'SELECT COUNT(*) AS total FROM ' . bzj_ouc_identifier($auth['table'])
    );
    $countRow = $countResult->fetch_assoc();

    $findings = [];
    $orphanRowsTotal = 0;
    $orphanReferencesTotal = 0;

    foreach ($platform['references'] as $configuredTable => $configuredColumns) {
        foreach (bzj_ouc_resolve_tables($db, $configuredTable) as $table) {
            if (strcasecmp($table, $auth['table']) === 0) {
                continue;
            }

            $actualColumns = bzj_ouc_columns($db, $table);
            $conditions = [];
            $columnFindings = [];

            foreach ($configuredColumns as $configuredColumn) {
                $key = strtolower($configuredColumn);
                if (!isset($actualColumns[$key])) {
                    continue;
                }

                $column = $actualColumns[$key];
                $condition = bzj_ouc_orphan_condition(
                    $column,
                    $auth['table'],
                    $auth['id']
                );

                $result = bzj_ouc_query(
                    $db,
                    'SELECT COUNT(*) AS total FROM ' .
                    bzj_ouc_identifier($table) .
                    ' WHERE ' . $condition
                );
                $row = $result->fetch_assoc();
                $count = (int)($row['total'] ?? 0);

                if ($count < 1) {
                    continue;
                }

                $sampleResult = bzj_ouc_query(
                    $db,
                    'SELECT DISTINCT ' . bzj_ouc_identifier($column) .
                    ' AS orphan_id FROM ' . bzj_ouc_identifier($table) .
                    ' WHERE ' . $condition .
                    ' ORDER BY CAST(' . bzj_ouc_identifier($column) .
                    ' AS UNSIGNED) LIMIT ' . BZJ_OUC_SAMPLE_LIMIT
                );

                $samples = [];
                while ($sample = $sampleResult->fetch_assoc()) {
                    $samples[] = (string)($sample['orphan_id'] ?? '');
                }

                $conditions[] = '(' . $condition . ')';
                $orphanReferencesTotal += $count;

                $columnFindings[] = [
                    'column' => $column,
                    'orphan_references' => $count,
                    'sample_ids' => $samples,
                ];
            }

            if (!$conditions) {
                continue;
            }

            $rowCountResult = bzj_ouc_query(
                $db,
                'SELECT COUNT(*) AS total FROM ' .
                bzj_ouc_identifier($table) .
                ' WHERE ' . implode(' OR ', $conditions)
            );
            $rowCount = $rowCountResult->fetch_assoc();
            $orphanRows = (int)($rowCount['total'] ?? 0);
            $orphanRowsTotal += $orphanRows;

            $findings[] = [
                'table' => $table,
                'columns' => $columnFindings,
                'orphan_rows' => $orphanRows,
                'engine' => bzj_ouc_engine($db, $table),
            ];
        }
    }

    return [
        'platform' => $platformKey,
        'label' => $platform['label'],
        'authoritative_table' => $auth['table'],
        'authoritative_id_column' => $auth['id'],
        'existing_user_count' => (int)($countRow['total'] ?? 0),
        'total_orphan_rows' => $orphanRowsTotal,
        'total_orphan_references' => $orphanReferencesTotal,
        'findings' => $findings,
    ];
}

/* -------------------------------------------------------------------------
 * Advisory schema audit
 *
 * This discovers additional columns that look like user IDs but are NOT
 * automatically deleted. This prevents accidental destruction of data whose
 * semantics are not established by the supplied delete functions.
 * ---------------------------------------------------------------------- */

function bzj_ouc_schema_audit(mysqli $db, string $platformKey): array
{
    $platform = bzj_ouc_platforms()[$platformKey];
    $auth = bzj_ouc_authoritative($db, $platform);

    $configured = [];
    foreach ($platform['references'] as $name => $columns) {
        foreach (bzj_ouc_resolve_tables($db, $name) as $table) {
            foreach ($columns as $column) {
                $configured[strtolower($table) . '|' . strtolower($column)] = true;
            }
        }
    }
    $configured[strtolower($auth['table']) . '|' . strtolower($auth['id'])] = true;

    $dbName = $db->real_escape_string((string)$db->query("SELECT DATABASE() AS db")->fetch_assoc()['db']);
    $result = bzj_ouc_query(
        $db,
        "SELECT TABLE_NAME, COLUMN_NAME
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = '{$dbName}'
           AND (
               LOWER(COLUMN_NAME) IN (
                   'user_id','userid','user','owner_id','member_id',
                   'sender_id','receiver_id','from_id','to_id','blocker',
                   'blocked','like_userid','block_userid','report_userid',
                   'view_userid','poster_id','inviter_id','invited_id',
                   'owner_id','product_owner_id'
               )
           )
         ORDER BY TABLE_NAME, ORDINAL_POSITION"
    );

    $allowTables = [];
    foreach ($platform['references'] as $configuredTable => $_) {
        foreach (bzj_ouc_resolve_tables($db, $configuredTable) as $table) {
            $allowTables[strtolower($table)] = true;
        }
    }
    $allowTables[strtolower($auth['table'])] = true;

    $rows = [];
    while ($row = $result->fetch_assoc()) {
        $table = (string)$row['TABLE_NAME'];
        $column = (string)$row['COLUMN_NAME'];

        if (isset($allowTables[strtolower($table)])) {
            if (isset($configured[strtolower($table) . '|' . strtolower($column)])) {
                continue;
            }
            /* A configured table may contain an extra user-like column. */
        }

        /*
         * Only report columns that are not already configured. Never delete
         * these rows automatically.
         */
        if (isset($configured[strtolower($table) . '|' . strtolower($column)])) {
            continue;
        }

        $condition = bzj_ouc_orphan_condition(
            $column,
            $auth['table'],
            $auth['id']
        );

        $countResult = bzj_ouc_query(
            $db,
            'SELECT COUNT(*) AS total FROM ' .
            bzj_ouc_identifier($table) . ' WHERE ' . $condition
        );
        $countRow = $countResult->fetch_assoc();
        $count = (int)($countRow['total'] ?? 0);

        if ($count < 1) {
            continue;
        }

        $sampleResult = bzj_ouc_query(
            $db,
            'SELECT DISTINCT ' . bzj_ouc_identifier($column) .
            ' AS orphan_id FROM ' . bzj_ouc_identifier($table) .
            ' WHERE ' . $condition .
            ' ORDER BY CAST(' . bzj_ouc_identifier($column) .
            ' AS UNSIGNED) LIMIT ' . BZJ_OUC_SCHEMA_SAMPLE_LIMIT
        );

        $samples = [];
        while ($sample = $sampleResult->fetch_assoc()) {
            $samples[] = (string)($sample['orphan_id'] ?? '');
        }

        $rows[] = [
            'table' => $table,
            'column' => $column,
            'orphan_rows' => $count,
            'sample_ids' => $samples,
        ];
    }

    return $rows;
}

/* -------------------------------------------------------------------------
 * Platform users + WordPress metadata mapping
 * ---------------------------------------------------------------------- */

function bzj_ouc_platform_users(mysqli $db, string $platformKey): array
{
    $platform = bzj_ouc_platforms()[$platformKey];
    $auth = bzj_ouc_authoritative($db, $platform);

    $result = bzj_ouc_query(
        $db,
        'SELECT ' . bzj_ouc_identifier($auth['id']) . ' AS platform_id, ' .
        bzj_ouc_identifier($auth['username']) . ' AS username, ' .
        bzj_ouc_identifier($auth['email']) . ' AS email
         FROM ' . bzj_ouc_identifier($auth['table'])
    );

    $users = [];

    while ($row = $result->fetch_assoc()) {
        $id = trim((string)($row['platform_id'] ?? ''));
        if (!ctype_digit($id) || (int)$id < 1) {
            continue;
        }

        $users[(string)((int)$id)] = [
            'id' => (int)$id,
            'username' => (string)($row['username'] ?? ''),
            'email' => (string)($row['email'] ?? ''),
        ];
    }

    return $users;
}

function bzj_ouc_platform_indexes(array $users): array
{
    $indexes = ['username' => [], 'email' => []];

    foreach ($users as $user) {
        $username = bzj_ouc_normalize($user['username']);
        $email = bzj_ouc_normalize($user['email']);

        if ($username !== '') {
            $indexes['username'][$username][] = $user;
        }
        if ($email !== '') {
            $indexes['email'][$email][] = $user;
        }
    }

    return $indexes;
}

function bzj_ouc_mapping_scan(mysqli $wp, mysqli $wo, mysqli $qd): array
{
    $usersTable = bzj_ouc_wp_table('users');
    $metaTable = bzj_ouc_wp_table('usermeta');

    if (!bzj_ouc_table_exists($wp, $usersTable) || !bzj_ouc_table_exists($wp, $metaTable)) {
        bzj_ouc_fail('WordPress users/usermeta tables were not found.');
    }

    $platformUsers = [
        'wowonder' => bzj_ouc_platform_users($wo, 'wowonder'),
        'quickdate' => bzj_ouc_platform_users($qd, 'quickdate'),
    ];

    $indexes = [
        'wowonder' => bzj_ouc_platform_indexes($platformUsers['wowonder']),
        'quickdate' => bzj_ouc_platform_indexes($platformUsers['quickdate']),
    ];

    /*
     * Read every relevant meta row. Do not use MAX(), MIN(), GROUP_CONCAT()
     * or another lossy aggregate: duplicate/conflicting metadata is an
     * anomaly and must be reported, not silently resolved.
     */
    $result = bzj_ouc_query(
        $wp,
        'SELECT u.ID, u.user_login, u.user_email, m.meta_key, m.meta_value
         FROM ' . bzj_ouc_identifier($usersTable) . ' u
         LEFT JOIN ' . bzj_ouc_identifier($metaTable) . ' m
           ON m.user_id = u.ID
          AND m.meta_key IN ("wo_user_id", "qd_user_id")
         ORDER BY u.ID ASC'
    );

    $wpUsers = [];

    while ($row = $result->fetch_assoc()) {
        $wpId = (int)($row['ID'] ?? 0);
        if ($wpId < 1) {
            continue;
        }

        if (!isset($wpUsers[$wpId])) {
            $wpUsers[$wpId] = [
                'ID' => $wpId,
                'user_login' => (string)($row['user_login'] ?? ''),
                'user_email' => (string)($row['user_email'] ?? ''),
                'meta' => [
                    'wo_user_id' => [],
                    'qd_user_id' => [],
                ],
            ];
        }

        $metaKey = (string)($row['meta_key'] ?? '');
        if ($metaKey === 'wo_user_id' || $metaKey === 'qd_user_id') {
            $wpUsers[$wpId]['meta'][$metaKey][] =
                trim((string)($row['meta_value'] ?? ''));
        }

        if (count($wpUsers) > BZJ_OUC_MAX_MAPPING_USERS) {
            bzj_ouc_fail('WordPress mapping scan exceeded the configured safety limit.');
        }
    }

    $summary = [
        'wordpress_users' => count($wpUsers),
        'wowonder_users' => count($platformUsers['wowonder']),
        'quickdate_users' => count($platformUsers['quickdate']),
        'aligned' => 0,
        'blank' => 0,
        'repairable' => 0,
        'ambiguous' => 0,
        'unmatched' => 0,
        'duplicate_meta' => 0,
    ];

    $findings = [];

    foreach ($wpUsers as $wpUser) {
        $wpId = (int)$wpUser['ID'];
        $wpUsername = bzj_ouc_normalize($wpUser['user_login']);
        $wpEmail = bzj_ouc_normalize($wpUser['user_email']);

        foreach ([
            'wowonder' => 'wo_user_id',
            'quickdate' => 'qd_user_id'
        ] as $platformKey => $metaKey) {

            $values = [];
            foreach ($wpUser['meta'][$metaKey] as $value) {
                if ($value !== '') {
                    $values[$value] = true;
                }
            }
            $values = array_keys($values);

            /*
             * No stored mapping is normal: the user may not yet have logged
             * into the referenced platform.
             */
            if (!$values) {
                $summary['blank']++;
                $findings[] = [
                    'key' => $platformKey . ':' . $wpId,
                    'wp_user_id' => $wpId,
                    'username' => (string)$wpUser['user_login'],
                    'email' => (string)$wpUser['user_email'],
                    'platform' => $platformKey,
                    'meta_key' => $metaKey,
                    'stored_id' => '',
                    'candidate_id' => null,
                    'repairable' => false,
                    'status' => 'blank_not_linked',
                    'change' => 'No change — blank platform mapping is allowed.',
                ];
                continue;
            }

            /*
             * More than one distinct value is unsafe to repair automatically.
             */
            if (count($values) > 1) {
                $summary['duplicate_meta']++;
                $findings[] = [
                    'key' => $platformKey . ':' . $wpId,
                    'wp_user_id' => $wpId,
                    'username' => (string)$wpUser['user_login'],
                    'email' => (string)$wpUser['user_email'],
                    'platform' => $platformKey,
                    'meta_key' => $metaKey,
                    'stored_id' => implode(', ', $values),
                    'candidate_id' => null,
                    'repairable' => false,
                    'status' => 'duplicate_meta_values',
                    'change' => 'No automatic change — multiple metadata values exist.',
                ];
                continue;
            }

            $storedId = $values[0];
            $users = $platformUsers[$platformKey];
            $index = $indexes[$platformKey];

            $emailMatches = $wpEmail !== ''
                ? ($index['email'][$wpEmail] ?? [])
                : [];
            $usernameMatches = $wpUsername !== ''
                ? ($index['username'][$wpUsername] ?? [])
                : [];

            $candidateIds = [];
            foreach (array_merge($emailMatches, $usernameMatches) as $match) {
                $candidateIds[(string)$match['id']] = true;
            }
            $candidateIds = array_keys($candidateIds);

            $storedValid = ctype_digit($storedId) && (int)$storedId > 0;
            $storedExists = $storedValid &&
                isset($users[(string)((int)$storedId)]);

            $candidateId = null;
            $repairable = false;
            $status = 'unmatched';
            $change = 'No automatic change.';

            /*
             * A repair requires one and only one platform candidate, and
             * every non-empty identity field must agree with that candidate.
             */
            if (
                count($candidateIds) === 1 &&
                count($emailMatches) <= 1 &&
                count($usernameMatches) <= 1
            ) {
                $candidateId = (int)$candidateIds[0];

                $emailAgrees = $wpEmail === '' ||
                    (
                        count($emailMatches) === 1 &&
                        (int)$emailMatches[0]['id'] === $candidateId
                    );

                $usernameAgrees = $wpUsername === '' ||
                    (
                        count($usernameMatches) === 1 &&
                        (int)$usernameMatches[0]['id'] === $candidateId
                    );

                if ($emailAgrees && $usernameAgrees) {
                    if ((string)$candidateId === (string)$storedId) {
                        $summary['aligned']++;
                        $findings[] = [
                            'key' => $platformKey . ':' . $wpId,
                            'wp_user_id' => $wpId,
                            'username' => (string)$wpUser['user_login'],
                            'email' => (string)$wpUser['user_email'],
                            'platform' => $platformKey,
                            'meta_key' => $metaKey,
                            'stored_id' => $storedId,
                            'candidate_id' => $candidateId,
                            'stored_exists' => true,
                            'repairable' => false,
                            'status' => 'aligned',
                            'change' => 'No change — Platform User ID and WP Meta Value match.',
                        ];
                        continue;
                    }

                    $status = 'repairable_mismatch';
                    $repairable = true;
                    $summary['repairable']++;
                    $change = 'Change ' . $metaKey . ' from ' .
                        $storedId . ' to ' . $candidateId . '.';
                } else {
                    $status = 'conflicting_identity';
                    $summary['ambiguous']++;
                    $change = 'No automatic change — username/email conflict.';
                }
            } elseif (
                count($candidateIds) > 1 ||
                count($emailMatches) > 1 ||
                count($usernameMatches) > 1
            ) {
                $status = 'ambiguous_identity';
                $summary['ambiguous']++;
                $change = 'No automatic change — identity matches are ambiguous.';
            } elseif (!$storedExists) {
                $status = 'stored_id_missing_from_platform';
                $summary['unmatched']++;
                $change = 'No automatic change — stored ID is absent and no unique identity match exists.';
            } else {
                $status = 'stored_id_has_no_identity_match';
                $summary['unmatched']++;
                $change = 'No automatic change — stored ID exists but username/email did not identify it.';
            }

            $findings[] = [
                'key' => $platformKey . ':' . $wpId,
                'wp_user_id' => $wpId,
                'username' => (string)$wpUser['user_login'],
                'email' => (string)$wpUser['user_email'],
                'platform' => $platformKey,
                'meta_key' => $metaKey,
                'stored_id' => $storedId,
                'candidate_id' => $candidateId,
                'stored_exists' => $storedExists,
                'repairable' => $repairable,
                'status' => $status,
                'change' => $change,
            ];
        }
    }

    return ['summary' => $summary, 'findings' => $findings];
}

/* -------------------------------------------------------------------------
 * Full scan
 * ---------------------------------------------------------------------- */

function bzj_ouc_full_scan(array $connections, bool $includeSchemaAudit = true): array
{
    return [
        'version' => BZJ_OUC_VERSION,
        'timestamp_utc' => gmdate('c'),
        'wowonder' => bzj_ouc_scan_platform($connections['wo'], 'wowonder'),
        'quickdate' => bzj_ouc_scan_platform($connections['qd'], 'quickdate'),
        'schema_audit' => $includeSchemaAudit ? [
            'wowonder' => bzj_ouc_schema_audit($connections['wo'], 'wowonder'),
            'quickdate' => bzj_ouc_schema_audit($connections['qd'], 'quickdate'),
        ] : [
            'wowonder' => [],
            'quickdate' => [],
        ],
        'mapping' => bzj_ouc_mapping_scan(
            $connections['wp'],
            $connections['wo'],
            $connections['qd']
        ),
    ];
}

/* -------------------------------------------------------------------------
 * Destructive orphan cleanup
 * ---------------------------------------------------------------------- */

function bzj_ouc_clean_platform(mysqli $db, string $platformKey): array
{
    $platform = bzj_ouc_platforms()[$platformKey];
    $auth = bzj_ouc_authoritative($db, $platform);
    $results = [];

    foreach ($platform['references'] as $configuredTable => $columns) {
        foreach (bzj_ouc_resolve_tables($db, $configuredTable) as $table) {
            if (strcasecmp($table, $auth['table']) === 0) {
                continue;
            }

            $actualColumns = bzj_ouc_columns($db, $table);
            $conditions = [];

            foreach ($columns as $configuredColumn) {
                $key = strtolower($configuredColumn);
                if (!isset($actualColumns[$key])) {
                    continue;
                }

                $conditions[] = '(' . bzj_ouc_orphan_condition(
                    $actualColumns[$key],
                    $auth['table'],
                    $auth['id']
                ) . ')';
            }

            if (!$conditions) {
                continue;
            }

            /*
             * Rebuild the DELETE condition from the live database. Never
             * trust a previous dry-run result as the deletion command.
             */
            $where = implode(' OR ', $conditions);
            $countResult = bzj_ouc_query(
                $db,
                'SELECT COUNT(*) AS total FROM ' .
                bzj_ouc_identifier($table) .
                ' WHERE ' . $where
            );
            $countRow = $countResult->fetch_assoc();
            $candidateRows = (int)($countRow['total'] ?? 0);

            if ($candidateRows < 1) {
                continue;
            }

            $engine = bzj_ouc_engine($db, $table);
            $transactionStarted = false;

            try {
                if ($engine['transactional']) {
                    if (!$db->begin_transaction()) {
                        throw new RuntimeException('Could not start transaction for ' . $table);
                    }
                    $transactionStarted = true;
                }

                bzj_ouc_query(
                    $db,
                    'DELETE FROM ' . bzj_ouc_identifier($table) .
                    ' WHERE ' . $where
                );

                $deleted = (int)$db->affected_rows;

                if ($transactionStarted && !$db->commit()) {
                    throw new RuntimeException('Could not commit transaction for ' . $table);
                }

                $results[] = [
                    'platform' => $platformKey,
                    'table' => $table,
                    'candidate_rows' => $candidateRows,
                    'deleted_rows' => $deleted,
                    'status' => 'committed',
                    'engine' => $engine['engine'],
                    'transactional' => $engine['transactional'],
                ];
            } catch (Throwable $e) {
                if ($transactionStarted) {
                    $db->rollback();
                }

                $results[] = [
                    'platform' => $platformKey,
                    'table' => $table,
                    'candidate_rows' => $candidateRows,
                    'deleted_rows' => 0,
                    'status' => 'failed',
                    'error' => $e->getMessage(),
                ];

                throw $e;
            }
        }
    }

    return $results;
}

/* -------------------------------------------------------------------------
 * ID mapping repair
 * ---------------------------------------------------------------------- */

function bzj_ouc_repair_mappings(
    mysqli $wp,
    array $freshMapping,
    array $selected
): array {
    $metaTable = bzj_ouc_wp_table('usermeta');

    if (!bzj_ouc_table_exists($wp, $metaTable)) {
        bzj_ouc_fail('WordPress usermeta table was not found.');
    }

    $results = [];

    foreach ($freshMapping['findings'] as $finding) {
        $key = (string)$finding['key'];

        if (
            !isset($selected[$key]) ||
            empty($finding['repairable']) ||
            $finding['candidate_id'] === null
        ) {
            continue;
        }

        $wpUserId = (int)$finding['wp_user_id'];
        $metaKey = (string)$finding['meta_key'];
        $oldValue = (string)$finding['stored_id'];
        $newValue = (string)$finding['candidate_id'];

        /*
         * The repair is deliberately conditional on the exact old value.
         * If the mapping changed after the fresh scan, zero rows are changed
         * rather than overwriting a newer administrator/application change.
         */
        $metaKeySql = $wp->real_escape_string($metaKey);
        $oldValueSql = $wp->real_escape_string($oldValue);
        $newValueSql = $wp->real_escape_string($newValue);

        try {
            $transactionStarted = $wp->begin_transaction();
            if (!$transactionStarted) {
                throw new RuntimeException('Could not start WordPress metadata transaction.');
            }

            $sql = 'UPDATE ' . bzj_ouc_identifier($metaTable) .
                " SET meta_value = '{$newValueSql}'
                  WHERE user_id = {$wpUserId}
                    AND meta_key = '{$metaKeySql}'
                    AND meta_value = '{$oldValueSql}'";

            bzj_ouc_query($wp, $sql);
            $affected = (int)$wp->affected_rows;

            if ($affected !== 1) {
                $wp->rollback();
                $results[] = [
                    'platform' => $finding['platform'],
                    'wp_user_id' => $wpUserId,
                    'meta_key' => $metaKey,
                    'old_value' => $oldValue,
                    'new_value' => $newValue,
                    'affected' => $affected,
                    'status' => 'not_changed',
                    'error' => 'Expected exactly one matching metadata row; database was not changed.',
                ];
                continue;
            }

            if (!$wp->commit()) {
                throw new RuntimeException('Could not commit WordPress metadata transaction.');
            }

            /*
             * Direct SQL bypasses WP's metadata cache. Clear it when the
             * WordPress API is available.
             */
            if (function_exists('clean_user_cache')) {
                clean_user_cache($wpUserId);
            }

            $results[] = [
                'platform' => $finding['platform'],
                'wp_user_id' => $wpUserId,
                'meta_key' => $metaKey,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'affected' => $affected,
                'status' => 'committed',
            ];
        } catch (Throwable $e) {
            if (isset($transactionStarted) && $transactionStarted) {
                $wp->rollback();
            }

            $results[] = [
                'platform' => $finding['platform'],
                'wp_user_id' => $wpUserId,
                'meta_key' => $metaKey,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'status' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    return $results;
}

/* -------------------------------------------------------------------------
 * Rendering
 * ---------------------------------------------------------------------- */

function bzj_ouc_render_stats(array $items): string
{
    $html = '<div class="stats">';

    foreach ($items as $label => $value) {
        $html .= '<div class="stat"><span>' . bzj_ouc_h($label) .
            '</span><strong>' . (int)$value . '</strong></div>';
    }

    return $html . '</div>';
}

function bzj_ouc_render_orphans(array $scan): string
{
    $html = '';

    foreach (['wowonder', 'quickdate'] as $platformKey) {
        $platform = $scan[$platformKey];

        $html .= '<h3>' . bzj_ouc_h($platform['label']) . '</h3>';
        $html .= '<p><strong>Authoritative table:</strong> <code>' .
            bzj_ouc_h($platform['authoritative_table']) .
            '</code> &nbsp; <strong>Existing users:</strong> ' .
            (int)$platform['existing_user_count'] .
            ' &nbsp; <strong>Orphan rows:</strong> ' .
            (int)$platform['total_orphan_rows'] .
            ' &nbsp; <strong>Orphan references:</strong> ' .
            (int)$platform['total_orphan_references'] .
            '</p>';

        if (empty($platform['findings'])) {
            $html .= '<p class="success">No confirmed orphan reference rows found.</p>';
            continue;
        }

        $html .= '<table><thead><tr>' .
            '<th>Table</th><th>Column</th><th>Orphan references</th>' .
            '<th>Sample orphan IDs</th><th>Engine</th>' .
            '</tr></thead><tbody>';

        foreach ($platform['findings'] as $finding) {
            $engine = $finding['engine']['engine'] ?: 'Unknown';
            $transactional = !empty($finding['engine']['transactional']) ? 'transactional' : 'non-transactional';

            foreach ($finding['columns'] as $column) {
                $html .= '<tr>' .
                    '<td><code>' . bzj_ouc_h($finding['table']) . '</code></td>' .
                    '<td><code>' . bzj_ouc_h($column['column']) . '</code></td>' .
                    '<td><strong>' . (int)$column['orphan_references'] . '</strong></td>' .
                    '<td>' . bzj_ouc_h(implode(', ', $column['sample_ids'])) . '</td>' .
                    '<td>' . bzj_ouc_h($engine . ' / ' . $transactional) . '</td>' .
                    '</tr>';
            }
        }

        $html .= '</tbody></table>';
    }

    return $html;
}

function bzj_ouc_render_schema_audit(array $scan): string
{
    $html = '<p class="small muted">Advisory only. These columns are not automatically deleted because their meaning is not established by the supplied platform deletion functions.</p>';

    foreach (['wowonder', 'quickdate'] as $platformKey) {
        $label = $platformKey === 'wowonder'
            ? 'WoWonder Streams'
            : 'QuickDate Socials';

        $rows = $scan['schema_audit'][$platformKey] ?? [];

        $html .= '<h3>' . bzj_ouc_h($label) . '</h3>';

        if (!$rows) {
            $html .= '<p class="success">No additional schema-discovered orphan references found.</p>';
            continue;
        }

        $html .= '<table><thead><tr>' .
            '<th>Table</th><th>Column</th><th>Possible orphan rows</th>' .
            '<th>Sample IDs</th></tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr><td><code>' . bzj_ouc_h($row['table']) .
                '</code></td><td><code>' . bzj_ouc_h($row['column']) .
                '</code></td><td><strong>' . (int)$row['orphan_rows'] .
                '</strong></td><td>' . bzj_ouc_h(implode(', ', $row['sample_ids'])) .
                '</td></tr>';
        }

        $html .= '</tbody></table>';
    }

    return $html;
}

function bzj_ouc_render_mapping(array $mapping): string
{
    $summary = $mapping['summary'];

    $html = bzj_ouc_render_stats([
        'WordPress users' => $summary['wordpress_users'],
        'WoWonder users' => $summary['wowonder_users'],
        'QuickDate users' => $summary['quickdate_users'],
        'Aligned' => $summary['aligned'],
        'Blank / not logged in' => $summary['blank'],
        'Repairable mismatches' => $summary['repairable'],
        'Ambiguous' => $summary['ambiguous'],
        'Unmatched' => $summary['unmatched'],
        'Duplicate metadata' => $summary['duplicate_meta'],
    ]);

    $html .= '<div class="mapping-toolbar">' .
        '<label>Show: <select id="mappingFilter" onchange="filterMappingRows()">' .
        '<option value="all">All findings</option>' .
        '<option value="mismatch" selected>Only mismatches</option>' .
        '</select></label> ' .
        '<label><input type="checkbox" onclick="toggleRepairCheckboxes(this)"> Select all repairable mismatches</label>' .
        '</div>';

    if (empty($mapping['findings'])) {
        return $html . '<p class="success">No ID anomalies were found.</p>';
    }

    $html .= '<table id="mappingTable"><thead><tr>' .
        '<th>Repair</th><th>Platform</th><th>User ID</th>' .
        '<th>WP Meta Value</th><th>Username</th><th>Email</th>' .
        '<th>Status</th><th>Change if repaired</th>' .
        '</tr></thead><tbody>';

    foreach ($mapping['findings'] as $finding) {
        $isMismatch = in_array(
            $finding['status'],
            [
                'repairable_mismatch',
                'stored_id_missing_from_platform',
                'stored_id_has_no_identity_match',
                'conflicting_identity',
                'ambiguous_identity',
                'duplicate_meta_values',
            ],
            true
        );

        $checkbox = !empty($finding['repairable'])
            ? '<input class="repair-checkbox" type="checkbox" name="repair[]" value="' .
              bzj_ouc_h($finding['key']) . '">'
            : '<span class="muted">—</span>';

        $platformLabel = $finding['platform'] === 'wowonder'
            ? 'WoWonder'
            : 'QuickDate';

        $html .= '<tr class="mapping-row" data-mismatch="' .
            ($isMismatch ? '1' : '0') . '">' .
            '<td>' . $checkbox . '</td>' .
            '<td>' . bzj_ouc_h($platformLabel) . '</td>' .
            '<td>' . bzj_ouc_h($finding['candidate_id'] ?? '') . '</td>' .
            '<td>' . bzj_ouc_h($finding['stored_id']) . '</td>' .
            '<td>' . bzj_ouc_h($finding['username']) . '</td>' .
            '<td>' . bzj_ouc_h($finding['email']) . '</td>' .
            '<td><code>' . bzj_ouc_h($finding['status']) . '</code></td>' .
            '<td>' . bzj_ouc_h($finding['change']) . '</td>' .
            '</tr>';
    }

    return $html . '</tbody></table>';
}

function bzj_ouc_render_results(array $results): string
{
    if (!$results) {
        return '<p class="success">No rows required processing.</p>';
    }

    $html = '<table><thead><tr><th>Platform</th><th>Table</th>' .
        '<th>Candidate rows</th><th>Changed/deleted</th><th>Status</th><th>Details</th>' .
        '</tr></thead><tbody>';

    foreach ($results as $row) {
        $html .= '<tr>' .
            '<td>' . bzj_ouc_h($row['platform'] ?? '') . '</td>' .
            '<td><code>' . bzj_ouc_h($row['table'] ?? ($row['meta_key'] ?? '')) . '</code></td>' .
            '<td>' . (int)($row['candidate_rows'] ?? 0) . '</td>' .
            '<td>' . (int)($row['deleted_rows'] ?? $row['affected'] ?? 0) . '</td>' .
            '<td><code>' . bzj_ouc_h($row['status'] ?? '') . '</code></td>' .
            '<td>' . bzj_ouc_h(
                $row['error'] ??
                (($row['old_value'] ?? '') !== '' || ($row['new_value'] ?? '') !== ''
                    ? (($row['meta_key'] ?? '') . ': ' . ($row['old_value'] ?? '') . ' → ' . ($row['new_value'] ?? ''))
                    : '')
            ) . '</td>' .
            '</tr>';
    }

    return $html . '</tbody></table>';
}

/* -------------------------------------------------------------------------
 * Request handling
 * ---------------------------------------------------------------------- */

$scan = null;
$operationResults = [];
$message = '';
$error = '';
$schemaAuditRequested = true;

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        bzj_ouc_validate_post();

        $action = (string)($_POST['bzj_action'] ?? '');
        $schemaAuditRequested = !empty($_POST['include_schema_audit']);

        $connections = bzj_ouc_connections();

        if ($action === 'scan') {
            $scan = bzj_ouc_full_scan($connections, $schemaAuditRequested);

            bzj_ouc_log('orphan_cleaner_dry_run', [
                'version' => BZJ_OUC_VERSION,
                'timestamp_utc' => $scan['timestamp_utc'],
                'wowonder_orphan_rows' => $scan['wowonder']['total_orphan_rows'],
                'wowonder_orphan_references' => $scan['wowonder']['total_orphan_references'],
                'quickdate_orphan_rows' => $scan['quickdate']['total_orphan_rows'],
                'quickdate_orphan_references' => $scan['quickdate']['total_orphan_references'],
                'mapping_summary' => $scan['mapping']['summary'],
            ]);

            $message = 'Dry run completed. No database rows were changed.';
        } elseif ($action === 'clean') {
            $confirmation = trim((string)($_POST['confirmation_phrase'] ?? ''));

            if (!hash_equals(BZJ_OUC_CLEAN_CONFIRMATION, $confirmation)) {
                bzj_ouc_fail('The orphan cleanup confirmation phrase is incorrect.');
            }

            /*
             * Fresh scan immediately before destructive processing.
             * The old browser result is informational only.
             */
            $before = bzj_ouc_full_scan($connections, false);

            $operationResults = array_merge(
                bzj_ouc_clean_platform($connections['wo'], 'wowonder'),
                bzj_ouc_clean_platform($connections['qd'], 'quickdate')
            );

            $scan = bzj_ouc_full_scan($connections, $schemaAuditRequested);

            bzj_ouc_log('orphan_cleaner_cleanup', [
                'version' => BZJ_OUC_VERSION,
                'before' => [
                    'wowonder_orphan_rows' => $before['wowonder']['total_orphan_rows'],
                    'quickdate_orphan_rows' => $before['quickdate']['total_orphan_rows'],
                    'mapping_summary' => $before['mapping']['summary'],
                ],
                'results' => $operationResults,
                'after' => [
                    'wowonder_orphan_rows' => $scan['wowonder']['total_orphan_rows'],
                    'quickdate_orphan_rows' => $scan['quickdate']['total_orphan_rows'],
                    'mapping_summary' => $scan['mapping']['summary'],
                ],
            ]);

            $message = 'Cleanup completed. The databases were rescanned after processing.';
        } elseif ($action === 'repair') {
            $confirmation = trim((string)($_POST['confirmation_phrase'] ?? ''));

            if (!hash_equals(BZJ_OUC_REPAIR_CONFIRMATION, $confirmation)) {
                bzj_ouc_fail('The ID repair confirmation phrase is incorrect.');
            }

            $selected = [];

            foreach ((array)($_POST['repair'] ?? []) as $key) {
                $key = (string)$key;
                if (preg_match('/^(wowonder|quickdate):[0-9]+$/', $key)) {
                    $selected[$key] = true;
                }
            }

            if (!$selected) {
                bzj_ouc_fail('Select at least one repairable mismatch.');
            }

            /*
             * Fresh mapping scan protects against repairing an item whose
             * identity or current metadata changed after the dry run.
             */
            $before = bzj_ouc_mapping_scan(
                $connections['wp'],
                $connections['wo'],
                $connections['qd']
            );

            $operationResults = bzj_ouc_repair_mappings(
                $connections['wp'],
                $before,
                $selected
            );

            $scan = bzj_ouc_full_scan($connections, $schemaAuditRequested);

            bzj_ouc_log('orphan_cleaner_mapping_repair', [
                'version' => BZJ_OUC_VERSION,
                'selected' => array_keys($selected),
                'before_summary' => $before['summary'],
                'results' => $operationResults,
                'after_summary' => $scan['mapping']['summary'],
            ]);

            $message = 'Selected ID mismatches were processed and the mapping was rescanned.';
        } else {
            bzj_ouc_fail('Unknown cleaner operation.');
        }
    }
} catch (Throwable $e) {
    $error = $e->getMessage();

    bzj_ouc_log('orphan_cleaner_error', [
        'version' => BZJ_OUC_VERSION,
        'error' => $e->getMessage(),
    ]);
}

$nonce = wp_create_nonce(BZJ_OUC_NONCE_ACTION);
$currentUser = function_exists('wp_get_current_user')
    ? wp_get_current_user()
    : null;

?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Buzzjuice — WoWonder + QuickDate Orphan Cleaner</title>
<style>
:root {
    color-scheme: light;
    --bg:#f3f4f6;
    --panel:#fff;
    --text:#17202a;
    --muted:#64748b;
    --line:#d9dee7;
    --accent:#1358a8;
    --accent2:#0d47a1;
    --success:#166534;
    --success-bg:#ecfdf5;
    --danger:#991b1b;
    --danger-bg:#fef2f2;
    --warn:#92400e;
    --warn-bg:#fffbeb;
}
* { box-sizing:border-box; }
body {
    margin:0;
    padding:24px;
    background:var(--bg);
    color:var(--text);
    font:14px/1.55 system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
}
.wrap { max-width:1500px; margin:0 auto; }
.panel {
    background:var(--panel);
    border:1px solid var(--line);
    border-radius:12px;
    padding:20px;
    margin:0 0 18px;
    box-shadow:0 2px 8px rgba(0,0,0,.04);
}
h1,h2,h3 { margin-top:0; }
h1 { font-size:26px; }
h2 { font-size:20px; border-bottom:1px solid var(--line); padding-bottom:10px; }
h3 { margin-top:24px; }
.small { font-size:12px; }
.muted { color:var(--muted); }
.notice,.success,.warning,.error {
    border-radius:8px;
    padding:12px 14px;
    margin:12px 0;
}
.notice { background:#eff6ff; border:1px solid #bfdbfe; }
.success { color:var(--success); background:var(--success-bg); border:1px solid #bbf7d0; }
.warning { color:var(--warn); background:var(--warn-bg); border:1px solid #fde68a; }
.error { color:var(--danger); background:var(--danger-bg); border:1px solid #fecaca; }
label { display:inline-flex; gap:8px; align-items:center; margin:6px 10px 6px 0; }
input[type=password], input[type=text], select {
    padding:8px 10px;
    border:1px solid #cbd5e1;
    border-radius:6px;
    min-width:220px;
}
button {
    border:0;
    border-radius:7px;
    padding:10px 16px;
    cursor:pointer;
    background:var(--accent);
    color:#fff;
    font-weight:600;
}
button:hover { background:var(--accent2); }
button.danger { background:#b91c1c; }
button.repair { background:#7c3aed; }
table {
    width:100%;
    border-collapse:collapse;
    margin:12px 0 20px;
    background:#fff;
}
th,td {
    border:1px solid var(--line);
    padding:8px 9px;
    vertical-align:top;
    text-align:left;
}
th { background:#f8fafc; }
code {
    background:#f1f5f9;
    border-radius:4px;
    padding:1px 4px;
}
.stats {
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(150px,1fr));
    gap:10px;
    margin:12px 0 18px;
}
.stat {
    border:1px solid var(--line);
    border-radius:8px;
    padding:10px;
    background:#f8fafc;
}
.stat span { display:block; color:var(--muted); font-size:12px; }
.stat strong { display:block; font-size:22px; }
.mapping-toolbar {
    position:sticky;
    top:0;
    z-index:2;
    background:#fff;
    border:1px solid var(--line);
    padding:10px;
    margin:10px 0;
    border-radius:8px;
}
.form-row {
    display:flex;
    flex-wrap:wrap;
    gap:12px;
    align-items:end;
}
form { margin:0; }
hr { border:0; border-top:1px solid var(--line); margin:18px 0; }
@media(max-width:900px) {
    body { padding:10px; }
    .panel { padding:13px; }
    table { display:block; overflow-x:auto; white-space:nowrap; }
}
</style>
<script>
function toggleRepairCheckboxes(master) {
    document.querySelectorAll('.repair-checkbox').forEach(function(cb) {
        cb.checked = master.checked;
    });
}
function filterMappingRows() {
    const mode = document.getElementById('mappingFilter').value;
    document.querySelectorAll('.mapping-row').forEach(function(row) {
        row.style.display =
            mode === 'all' || row.dataset.mismatch === '1' ? '' : 'none';
    });
}
function confirmDestructive(form, message) {
    return window.confirm(message);
}
document.addEventListener('DOMContentLoaded', function() {
    filterMappingRows();
});
</script>
</head>
<body>
<div class="wrap">

<div class="panel">
    <h1>Buzzjuice WoWonder + QuickDate Orphan User Cleaner</h1>
    <p>
        Version <code><?=bzj_ouc_h(BZJ_OUC_VERSION)?></code>.
        Signed in as
        <strong><?=bzj_ouc_h($currentUser ? $currentUser->user_login : 'administrator')?></strong>.
    </p>
    <div class="notice">
        <strong>Safety model:</strong>
        The cleaner never deletes a WoWonder/QuickDate authoritative user,
        a WordPress user, or media/S3 objects. It only removes orphan
        reference rows represented by the configured platform deletion
        functions. Platform-ID repairs update WordPress user metadata only.
    </div>
</div>

<?php if ($message !== ''): ?>
<div class="success"><strong><?=bzj_ouc_h($message)?></strong></div>
<?php endif; ?>

<?php if ($error !== ''): ?>
<div class="error"><strong>Operation failed:</strong> <?=bzj_ouc_h($error)?></div>
<?php endif; ?>

<div class="panel">
    <h2>1. Start a Dry Run</h2>
    <p>
        The dry run is read-only. It checks direct user-reference tables on
        both platforms and audits WordPress-to-platform ID alignment.
    </p>
    <form method="post">
        <input type="hidden" name="bzj_action" value="scan">
        <input type="hidden" name="bzj_nonce" value="<?=bzj_ouc_h($nonce)?>">
        <div class="form-row">
            <label>
                Cleaner access key:
                <input type="password" name="bzj_access_key" autocomplete="off" required>
            </label>
            <label>
                <input type="checkbox" name="include_schema_audit" value="1"
                    <?= $schemaAuditRequested ? 'checked' : '' ?>>
                Include advisory schema audit
            </label>
            <button type="submit">Start Dry Run</button>
        </div>
    </form>
</div>

<?php if ($scan !== null): ?>

<div class="panel">
    <h2>2. Dry-Run / Current Results</h2>
    <p class="small muted">
        Scan timestamp:
        <?=bzj_ouc_h($scan['timestamp_utc'])?> UTC.
        No database rows were changed by this scan.
    </p>
    <?=bzj_ouc_render_orphans($scan)?>
</div>

<div class="panel">
    <h2>3. WordPress Platform-ID Alignment</h2>
    <p>
        For each WordPress user, the cleaner compares the authoritative
        WoWonder <code>user_id</code> or QuickDate <code>id</code> against
        <code>wo_user_id</code> or <code>qd_user_id</code>. Username and email
        are used to identify the correct platform account.
    </p>
    <p>
        A blank platform metadata value is treated as normal and is not shown
        as a mismatch. Repairs require a unique, non-conflicting identity
        match. Ambiguous matches and duplicate metadata values are never
        repaired automatically.
    </p>

    <form method="post">
        <input type="hidden" name="bzj_action" value="repair">
        <input type="hidden" name="bzj_nonce" value="<?=bzj_ouc_h($nonce)?>">
        <?=bzj_ouc_render_mapping($scan['mapping'])?>

        <hr>

        <div class="warning">
            <strong>Repair changes:</strong>
            Only selected rows marked <code>repairable_mismatch</code> can be
            changed. The cleaner performs another live mapping scan before
            writing and requires the exact previous metadata value to still
            exist.
        </div>

        <div class="form-row">
            <label>
                Cleaner access key:
                <input type="password" name="bzj_access_key" autocomplete="off" required>
            </label>
            <label>
                Type
                <code><?=bzj_ouc_h(BZJ_OUC_REPAIR_CONFIRMATION)?></code>:
                <input type="text" name="confirmation_phrase" autocomplete="off" required>
            </label>
            <button class="repair" type="submit">
                Repair Selected ID Mismatches
            </button>
        </div>
    </form>
</div>

<div class="panel">
    <h2>4. Advisory Schema Audit</h2>
    <?=bzj_ouc_render_schema_audit($scan)?>
</div>

<div class="panel">
    <h2>5. Clean Confirmed Orphan References</h2>
    <div class="warning">
        <strong>Destructive operation.</strong>
        Take current database backups before proceeding. The cleaner will
        perform a fresh live scan immediately before deleting rows; the
        displayed dry-run results are not blindly reused.
    </div>

    <form method="post"
          onsubmit="return confirmDestructive(this,'This will permanently delete confirmed orphan reference rows from the WoWonder and QuickDate databases. Continue?');">
        <input type="hidden" name="bzj_action" value="clean">
        <input type="hidden" name="bzj_nonce" value="<?=bzj_ouc_h($nonce)?>">
        <input type="hidden" name="include_schema_audit" value="<?= $schemaAuditRequested ? '1' : '0' ?>">

        <div class="form-row">
            <label>
                Cleaner access key:
                <input type="password" name="bzj_access_key" autocomplete="off" required>
            </label>
            <label>
                Type
                <code><?=bzj_ouc_h(BZJ_OUC_CLEAN_CONFIRMATION)?></code>:
                <input type="text" name="confirmation_phrase" autocomplete="off" required>
            </label>
            <button class="danger" type="submit">Clean Confirmed Orphans</button>
        </div>
    </form>
</div>

<?php if ($operationResults): ?>
<div class="panel">
    <h2>6. Operation Results</h2>
    <?=bzj_ouc_render_results($operationResults)?>
    <p class="small muted">
        The page above also contains the post-operation rescan, so the
        remaining orphan count and mapping state can be reviewed immediately.
    </p>
</div>
<?php endif; ?>

<?php endif; ?>

</div>
</body>
</html>
