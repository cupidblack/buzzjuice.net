<?php
/**
 * migrate_xprofile_optimized.php
 *
 * Optimized xProfile merge from koware_db1 -> koware_buzzjuice
 * CLI: php migrate_xprofile_optimized.php
 *
 * IMPORTANT: Backup both DBs before running.
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);

// -------------------- CONFIG --------------------
$dbOld = [
    'host' => 'localhost',
    'user' => 'koware_system',
    'pass' => '#4i+WztDw%V%',
    'name' => 'koware_db1'
];

$dbNew = [
    'host' => 'localhost',
    'user' => 'koware_iapd',
    'pass' => '42v[D=qOXk#E',
    'name' => 'koware_buzzjuice'
];

$tables = [
    'old_users'  => 'wp_rudryp_users',
    'old_groups' => 'wp_rudryp_bp_xprofile_groups',
    'old_fields' => 'wp_rudryp_bp_xprofile_fields',
    'old_data'   => 'wp_rudryp_bp_xprofile_data',
    'old_meta'   => 'wp_rudryp_bp_xprofile_meta',
    'new_users'  => 'wp_users',
    'new_groups' => 'wp_bp_xprofile_groups',
    'new_fields' => 'wp_bp_xprofile_fields',
    'new_data'   => 'wp_bp_xprofile_data',
    'new_meta'   => 'wp_bp_xprofile_meta',
];

$logFile = __DIR__ . '/migration_log.txt';
@unlink($logFile);
function logLine($msg) {
    global $logFile;
    $t = date('Y-m-d H:i:s') . " - " . $msg . PHP_EOL;
    echo $t;
    file_put_contents($logFile, $t, FILE_APPEND);
}

// -------------------- DB CONNECT --------------------
function db_connect($c) {
    $m = new mysqli($c['host'], $c['user'], $c['pass'], $c['name']);
    if ($m->connect_error) {
        die("Connect error: " . $m->connect_error . PHP_EOL);
    }
    $m->set_charset('utf8mb4');
    return $m;
}

$oldDB = db_connect($dbOld);
$newDB = db_connect($dbNew);

logLine("Starting optimized migration");

// -------------------- PRELOAD target users/groups/fields --------------------
logLine("Preloading target users, groups, fields...");

// Preload users: user_login => ID
$newUsers = [];
$r = $newDB->query("SELECT ID, user_login FROM {$tables['new_users']}");
while ($row = $r->fetch_assoc()) {
    $newUsers[ $row['user_login'] ] = (int)$row['ID'];
}
$r->free();
logLine("Preloaded " . count($newUsers) . " target users");

// Preload groups: name => id (if duplicates names exist we'll keep first match)
$newGroups = [];
$r = $newDB->query("SELECT id, name FROM {$tables['new_groups']}");
while ($row = $r->fetch_assoc()) {
    $newGroups[ $row['name'] ] = (int)$row['id'];
}
$r->free();
logLine("Preloaded " . count($newGroups) . " target groups");

// Preload fields: composite key name|group_id => id (group_id is target group id)
$newFields = [];
$r = $newDB->query("SELECT id, name, group_id FROM {$tables['new_fields']}");
while ($row = $r->fetch_assoc()) {
    $key = $row['name'] . '||' . $row['group_id'];
    $newFields[$key] = (int)$row['id'];
}
$r->free();
logLine("Preloaded " . count($newFields) . " target fields");

// -------------------- BUILD USER MAP (old_user_id => new_user_id) --------------------
logLine("Building user map by user_login...");
$userMap = [];
$res = $oldDB->query("SELECT ID, user_login FROM {$tables['old_users']}");
while ($row = $res->fetch_assoc()) {
    $oldID = (int)$row['ID'];
    $login = $row['user_login'];
    if (isset($newUsers[$login])) {
        $userMap[$oldID] = $newUsers[$login];
    } else {
        // Try a normalized match (trim + lowercase) as a fallback
        $nlogin = strtolower(trim($login));
        foreach ($newUsers as $ulogin => $uid) {
            if (strtolower(trim($ulogin)) === $nlogin) {
                $userMap[$oldID] = $uid;
                break;
            }
        }
        if (!isset($userMap[$oldID])) {
            file_put_contents($logFile, date('Y-m-d H:i:s') . " - UNMATCHED_USER oldID={$oldID} login={$login}\n", FILE_APPEND);
        }
    }
}
$res->free();
logLine("User map contains " . count($userMap) . " matched users");

// -------------------- LOAD old groups and fields into memory --------------------
logLine("Loading old groups and fields...");
$oldGroups = [];
$res = $oldDB->query("SELECT id, name, description, group_order, can_delete FROM {$tables['old_groups']}");
while ($row = $res->fetch_assoc()) {
    $oldGroups[(int)$row['id']] = $row;
}
$res->free();
logLine("Loaded " . count($oldGroups) . " old groups");

$oldFields = [];
$res = $oldDB->query("SELECT id, group_id, parent_id, type, name, description, is_required, is_default_option, field_order, option_order, order_by, can_delete FROM {$tables['old_fields']}");
while ($row = $res->fetch_assoc()) {
    $oldFields[(int)$row['id']] = $row;
}
$res->free();
logLine("Loaded " . count($oldFields) . " old fields");

// -------------------- MAP / CREATE groups in target --------------------
logLine("Mapping/creating groups in target...");
$groupMap = []; // old_group_id => new_group_id

$insGroupStmt = $newDB->prepare("INSERT INTO {$tables['new_groups']} (name, description, group_order, can_delete) VALUES (?, ?, ?, ?)");
if (!$insGroupStmt) {
    die("Prepare failed for group insert: " . $newDB->error . PHP_EOL);
}

foreach ($oldGroups as $oldGID => $g) {
    $name = $g['name'];
    if (isset($newGroups[$name])) {
        $groupMap[$oldGID] = $newGroups[$name];
    } else {
        // create group
        $insGroupStmt->bind_param('ssis', $name, $g['description'], $g['group_order'], $g['can_delete']);
        if ($insGroupStmt->execute()) {
            $newGID = $insGroupStmt->insert_id;
            $groupMap[$oldGID] = (int)$newGID;
            $newGroups[$name] = (int)$newGID; // update preload map
            file_put_contents($logFile, date('Y-m-d H:i:s')." - CREATED_GROUP oldG={$oldGID} name=\"{$name}\" -> newG={$newGID}\n", FILE_APPEND);
        } else {
            file_put_contents($logFile, date('Y-m-d H:i:s')." - ERROR_CREATE_GROUP oldG={$oldGID} name=\"{$name}\" err=".$insGroupStmt->error."\n", FILE_APPEND);
        }
    }
}
$insGroupStmt->close();
logLine("Group mapping complete: " . count($groupMap) . " entries");

// -------------------- MAP / CREATE fields in target (two-pass for parents) --------------------
logLine("Mapping/creating fields (pass 1: create fields with parent_id = 0)...");
$fieldMap = []; // old_field_id => new_field_id

$insFieldStmt = $newDB->prepare(
    "INSERT INTO {$tables['new_fields']} (group_id, parent_id, type, name, description, is_required, is_default_option, field_order, option_order, order_by, can_delete)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
if (!$insFieldStmt) {
    die("Prepare failed for field insert: " . $newDB->error . PHP_EOL);
}

// Create / map fields without setting parent relationships yet
foreach ($oldFields as $oldFID => $f) {
    $oldGroup = (int)$f['group_id'];

    if (!isset($groupMap[$oldGroup])) {
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - MISSING_GROUP_FOR_FIELD oldField={$oldFID} oldGroup={$oldGroup}\n", FILE_APPEND);
        continue; // skip this field (group missing)
    }

    $newGroupID = $groupMap[$oldGroup];
    $key = $f['name'] . '||' . $newGroupID;
    if (isset($newFields[$key])) {
        $fieldMap[$oldFID] = $newFields[$key];
        continue;
    }

    // Insert with parent_id = 0 for now
    $parent_zero = 0;
    // Cast / sanitize fields for binding; use strings for some unpredictable columns
    $type = $f['type'];
    $name = $f['name'];
    $desc = $f['description'];
    $is_required = (int)$f['is_required'];
    $is_default_option = (int)$f['is_default_option'];
    $field_order = (int)$f['field_order'];
    $option_order = $f['option_order'] === null ? '' : $f['option_order'];
    $order_by = $f['order_by'] === null ? '' : $f['order_by'];
    $can_delete = (int)$f['can_delete'];

    $insFieldStmt->bind_param(
        'iisssiiissi',
        $newGroupID,
        $parent_zero,
        $type,
        $name,
        $desc,
        $is_required,
        $is_default_option,
        $field_order,
        $option_order,
        $order_by,
        $can_delete
    );

    if ($insFieldStmt->execute()) {
        $newFID = $insFieldStmt->insert_id;
        $fieldMap[$oldFID] = (int)$newFID;
        $newFields[$key] = (int)$newFID; // add to preload map
        file_put_contents($logFile, date('Y-m-d H:i:s')." - CREATED_FIELD oldF={$oldFID} name=\"{$name}\" -> newF={$newFID}\n", FILE_APPEND);
    } else {
        file_put_contents($logFile, date('Y-m-d H:i:s')." - ERROR_CREATE_FIELD oldF={$oldFID} name=\"{$name}\" err=".$insFieldStmt->error."\n", FILE_APPEND);
    }
}
$insFieldStmt->close();
logLine("Field creation pass 1 complete: " . count($fieldMap) . " fields mapped/created");

// Second pass: update parent_id where applicable
logLine("Updating field parent relationships (pass 2)...");
$updParentStmt = $newDB->prepare("UPDATE {$tables['new_fields']} SET parent_id = ? WHERE id = ?");
if (!$updParentStmt) {
    die("Prepare failed for parent update: " . $newDB->error . PHP_EOL);
}

foreach ($oldFields as $oldFID => $f) {
    $oldParent = (int)$f['parent_id'];
    if ($oldParent && isset($fieldMap[$oldFID]) && isset($fieldMap[$oldParent])) {
        $newFID = $fieldMap[$oldFID];
        $newParent = $fieldMap[$oldParent];
        $updParentStmt->bind_param('ii', $newParent, $newFID);
        if (!$updParentStmt->execute()) {
            file_put_contents($logFile, date('Y-m-d H:i:s')." - ERROR_UPDATE_PARENT newF={$newFID} newParent={$newParent} err=".$updParentStmt->error."\n", FILE_APPEND);
        }
    } elseif ($oldParent && isset($fieldMap[$oldFID]) && !isset($fieldMap[$oldParent])) {
        file_put_contents($logFile, date('Y-m-d H:i:s')." - MISSING_PARENT_MAPPING oldField={$oldFID} oldParent={$oldParent}\n", FILE_APPEND);
    }
}
$updParentStmt->close();
logLine("Parent update pass complete");

// -------------------- MIGRATE xprofile_data --------------------
logLine("Migrating xprofile_data...");
$insDataStmt = $newDB->prepare(
    "INSERT INTO {$tables['new_data']} (field_id, user_id, value, last_updated)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE value = VALUES(value), last_updated = VALUES(last_updated)"
);
if (!$insDataStmt) {
    die("Prepare failed for data insert: " . $newDB->error . PHP_EOL);
}

$dataCount = 0;
$res = $oldDB->query("SELECT id, field_id, user_id, value, last_updated FROM {$tables['old_data']}");
while ($row = $res->fetch_assoc()) {
    $oldUID = (int)$row['user_id'];
    $oldFID = (int)$row['field_id'];

    if (!isset($userMap[$oldUID])) {
        file_put_contents($logFile, date('Y-m-d H:i:s')." - SKIP_DATA missing_user oldDataID={$row['id']} oldUID={$oldUID}\n", FILE_APPEND);
        continue;
    }
    if (!isset($fieldMap[$oldFID])) {
        file_put_contents($logFile, date('Y-m-d H:i:s')." - SKIP_DATA missing_field oldDataID={$row['id']} oldFID={$oldFID}\n", FILE_APPEND);
        continue;
    }

    $newUID = $userMap[$oldUID];
    $newFID = $fieldMap[$oldFID];
    $value = $row['value'];
    $last_updated = $row['last_updated'];

    $insDataStmt->bind_param('iiss', $newFID, $newUID, $value, $last_updated);
    if ($insDataStmt->execute()) {
        $dataCount++;
    } else {
        file_put_contents($logFile, date('Y-m-d H:i:s')." - ERROR_INSERT_DATA oldDataID={$row['id']} err=".$insDataStmt->error."\n", FILE_APPEND);
    }
}
$res->free();
$insDataStmt->close();
logLine("Migrated $dataCount xprofile_data rows");

// -------------------- MIGRATE xprofile_meta --------------------
logLine("Migrating xprofile_meta...");
$insMetaStmt = $newDB->prepare(
    "INSERT INTO {$tables['new_meta']} (object_id, object_type, meta_key, meta_value)
     VALUES (?, ?, ?, ?)
     ON DUPLICATE KEY UPDATE meta_value = VALUES(meta_value)"
);
if (!$insMetaStmt) {
    die("Prepare failed for meta insert: " . $newDB->error . PHP_EOL);
}

$metaCount = 0;
$res = $oldDB->query("SELECT id, object_id, object_type, meta_key, meta_value FROM {$tables['old_meta']}");
while ($row = $res->fetch_assoc()) {
    $oldOID = (int)$row['object_id'];
    $otype = $row['object_type'];
    $meta_key = $row['meta_key'];
    $meta_value = $row['meta_value'];
    $newOID = null;

    if ($otype === 'field') {
        if (isset($fieldMap[$oldOID])) $newOID = $fieldMap[$oldOID];
    } elseif ($otype === 'group') {
        if (isset($groupMap[$oldOID])) $newOID = $groupMap[$oldOID];
    } else {
        // Unknown object type: log and skip
        file_put_contents($logFile, date('Y-m-d H:i:s')." - SKIP_META unknown_object_type id={$row['id']} type={$otype}\n", FILE_APPEND);
        continue;
    }

    if (!$newOID) {
        file_put_contents($logFile, date('Y-m-d H:i:s')." - SKIP_META missing_map id={$row['id']} oldOID={$oldOID} type={$otype}\n", FILE_APPEND);
        continue;
    }

    $insMetaStmt->bind_param('isss', $newOID, $otype, $meta_key, $meta_value);
    if ($insMetaStmt->execute()) {
        $metaCount++;
    } else {
        file_put_contents($logFile, date('Y-m-d H:i:s')." - ERROR_INSERT_META id={$row['id']} err=".$insMetaStmt->error."\n", FILE_APPEND);
    }
}
$res->free();
$insMetaStmt->close();
logLine("Migrated $metaCount xprofile_meta rows");

// -------------------- FINISH --------------------
logLine("Migration finished. See $logFile for details.");
$newDB->close();
$oldDB->close();
