<?php
function get_quickdate_db() {
    static $conn = null;
    if (!$conn) {
        $conn = get_db_connection('localhost', 'koware_iapd', '42v[D=qOXk#E', 'koware_buzzjuice_social', 'QuickDate');
    }
    return $conn;
}

function print_quickdate_user_data($qd_user_id) {
    $conn = get_quickdate_db();

    $qd_user_id = '1';
    $query = "SELECT * FROM users WHERE id = $qd_user_id LIMIT 1";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        echo "Query failed: " . mysqli_error($conn);
        return;
    }

    if (mysqli_num_rows($result) == 0) {
        echo "No user found with QuickDate ID: $qd_user_id";
        return;
    }

    $user_data = mysqli_fetch_assoc($result);

    echo "<pre>";
    print_r($user_data);
    echo "</pre>";
}

print_quickdate_user_data();
