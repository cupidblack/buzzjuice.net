<?php
require_once __DIR__ . '/../db_helpers.php';

function bzj_palmier_db() {
    static $conn;

    if (!$conn) {
        $conn = new mysqli(
            PALMIER_DB_HOST,
            PALMIER_DB_USER,
            PALMIER_DB_PASS,
            PALMIER_DB_NAME
        );

        if ($conn->connect_error) {
            error_log("Palmier DB Connection Error: " . $conn->connect_error);
            return null;
        }
    }

    return $conn;
}

function bzj_log_activity($user_id, $action_type, $ref_id = null, $ref_type = null, $value = 1, $source_table = null) {

    $db = bzj_palmier_db();
    if (!$db) return;

    $stmt = $db->prepare("
        INSERT IGNORE INTO Wo_UserActivityLog
        (user_id, action_type, ref_id, ref_type, value, source_table, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");

    if (!$stmt) {
        error_log("Palmier Prepare Failed: " . $db->error);
        return;
    }

    $stmt->bind_param("isisis", $user_id, $action_type, $ref_id, $ref_type, $value, $source_table);

    if (!$stmt->execute()) {
        error_log("Palmier Insert Failed: " . $stmt->error);
    }

    $stmt->close();
}