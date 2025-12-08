<?php
@session_start();
echo "session_name: " . session_name() . "<br>";
echo "session_id: " . session_id() . "<br>";
echo "session.save_path: " . ini_get('session.save_path') . "<br>";
echo "session.serialize_handler: " . ini_get('session.serialize_handler') . "<br>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";
?>