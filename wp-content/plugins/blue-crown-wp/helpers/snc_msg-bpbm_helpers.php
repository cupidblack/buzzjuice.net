<?php
// Returns true if BP Better Messages plugin is active
function snc_msg_is_bpbm_active() {
    return function_exists('Better_Messages') || class_exists('Better_Messages');
}
?>