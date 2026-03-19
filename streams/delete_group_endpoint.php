<?php
require_once __DIR__ . '/assets/init.php';
require_once __DIR__ . '/../shared/wwqd_bridge.php';
header("Content-Type: application/json");

// ----------- Logging -----------
function Wo_DeleteLog($msg){
    $file = __DIR__.'/logs/delete_group.log';
    $time = date("Y-m-d H:i:s");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!file_exists(dirname($file))) mkdir(dirname($file),0755,true);
    file_put_contents($file,"[$time][$ip] $msg\n", FILE_APPEND);
}

// ----------- Debug Response -----------
function debug_response($status, $message, $details=[]){
    $res = ["status"=>$status,"message"=>$message];
    if($details) $res["debug"] = $details;
    Wo_DeleteLog("DEBUG: $message " . json_encode($details));
    echo json_encode($res); exit;
}

// PHP error tracing
set_error_handler(function($errno,$errstr,$errfile,$errline){
    Wo_DeleteLog("PHP_ERROR [$errno]: $errstr in $errfile:$errline");
});
register_shutdown_function(function(){
    $err = error_get_last(); if($err) Wo_DeleteLog("SHUTDOWN_ERROR: ".json_encode($err));
});

// ----------- Session/Permission -----------
if(!Wo_IsLogged()) debug_response(403,"Not logged in",["wo_user"=>$wo['user']??null]);
$user_id = $wo['user']['user_id'] ?? 0;
$group_id = intval($_POST['group_id'] ?? 0);
$group = Wo_GroupData($group_id);
if(empty($group)) debug_response(404,"Group not found",["group_id"=>$group_id,"user_id"=>$user_id]);
if($group['user_id']!=$user_id && !Wo_IsCanGroupUpdate($group_id,'delete_group')) {
    debug_response(403,"Permission denied",["group_user"=>$group['user_id'],"session_user"=>$user_id]);
}

// ----------- Config / DB -----------
$secret = $wo['config']['site_encryption_key'] ?? 'CHANGE_ME_RANDOM_SECRET';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
global $sqlConnect;
$queue_enabled = false;
$attempt_table = "bz_delete_attempts";
$queue_table   = "bz_delete_queue";

// Table checks
foreach([$attempt_table,$queue_table] as $tbl){
    $res_check = mysqli_query($sqlConnect,"SHOW TABLES LIKE '$tbl'");
    if(!$res_check || mysqli_num_rows($res_check)==0) debug_response(500,"SQL table not found",$tbl);
}

// ----------- Cooldown / Abuse Protection -----------
$action = $_POST['action'] ?? '';
$res = mysqli_query($sqlConnect,"SELECT COUNT(*) as cnt, MAX(attempt_time) as lt FROM $attempt_table WHERE ip='$ip' AND attempt_time>".(time()-86400));
$row = mysqli_fetch_assoc($res);
$cnt = intval($row['cnt'] ?? 0); $last_attempt = intval($row['lt'] ?? 0);
$waitTimes = [60,120,300,1200,3600,12345,86400,172800,216000];
$wait = $waitTimes[min($cnt,count($waitTimes)-1)];
$next_cooldown_sec = max(0,$wait-(time()-$last_attempt));
if($next_cooldown_sec>0 && $action=='send_code')
    debug_response(429,"Wait $next_cooldown_sec seconds before requesting again.",["cooldown"=>$next_cooldown_sec]);

mysqli_query($sqlConnect,"INSERT INTO $attempt_table (user_id, ip, attempt_time, success) VALUES ($user_id,'$ip',".time().",0)");

// ----------- Token Generation/Validation -----------
function Wo_CreateDeleteToken($user_id,$group_id){
    global $secret;
    $prefix = strtoupper(substr(bin2hex(random_bytes(4)),0,4));
    $suffix = strtoupper(substr(bin2hex(random_bytes(5)),0,5));
    $issued = time();
    $expiry = $issued + 12345;
    $payload = json_encode([
        'user'=>$user_id, 'group'=>$group_id, 'prefix'=>$prefix, 'suffix'=>$suffix,
        'iat'=>$issued, 'exp'=>$expiry
    ]);
    $payload_enc = base64_encode($payload);
    $sig = hash_hmac('sha256',$payload_enc,$secret);
    $token = $payload_enc.'.'.$sig;
    return ['token'=>$token,'prefix'=>$prefix,'suffix'=>$suffix,'expiry'=>$expiry];
}
function Wo_VerifyDeleteToken($token,$suffix_input){
    global $secret;
    $parts = explode('.',$token); if(count($parts)!=2) return false;
    $payload_enc = $parts[0]; $sig = $parts[1];
    if(!hash_equals($sig,hash_hmac('sha256',$payload_enc,$secret))) return false;
    $data = json_decode(base64_decode($payload_enc), true);
    if(!$data || time()>$data['exp']) return false;
    if(strtoupper($suffix_input)!=$data['suffix']) return false;
    return $data;
}

// ----------- SEND SDT CODE -----------
if($action=='send_code'){
    $data = Wo_CreateDeleteToken($user_id,$group_id);
    $email = $wo['user']['email'] ?? '';
    $siteEmail = $wo['config']['siteEmail'] ?? '';
    if(!$email || !filter_var($email,FILTER_VALIDATE_EMAIL)) debug_response(500,"Invalid user email",["email"=>$email]);
    if(!$siteEmail || !filter_var($siteEmail,FILTER_VALIDATE_EMAIL)) debug_response(500,"Invalid site email",["siteEmail"=>$siteEmail]);

    $subject = "Buzzjuice Secure Group Deletion Code";
    $message = "Your Buzzjuice group deletion code: ".$data['prefix']."-".$data['suffix']."\n"
             ."Enter the last 5 characters only.\nExpires: ".date('Y-m-d H:i:s',$data['expiry'])."\n\nIf you did not request this, ignore this email.";
    $headers = "From: ".$wo['config']['siteName']." <".$siteEmail.">";
    $mail_result = @mail($email,$subject,$message,$headers);
    Wo_DeleteLog("SDT_MAIL user:$user_id group:$group_id to:$email result:".($mail_result?"SUCCESS":"FAIL"));
    if(!$mail_result) debug_response(500,"Failed to send SDT code",["email"=>$email]);

    echo json_encode([
        "status"=>200,
        "token"=>$data['token'],
        "prefix"=>$data['prefix'],
        "expiry"=>$data['expiry'],
        "next_cooldown_sec"=>$wait,
        "message"=>"Code sent to $email"
    ]);
    exit;
}

// ----------- CONFIRM DELETE -----------
if($action=='confirm_delete'){
    $token = $_POST['token'] ?? '';
    $suffix = $_POST['suffix'] ?? '';
    $verify = Wo_VerifyDeleteToken($token,$suffix);
    if(!$verify) debug_response(400,"Invalid or expired SDT code");
    $group_id = $verify['group'];

    if($queue_enabled){
        // Insert into bz_delete_queue with group_id (page_id NULL)
        mysqli_query($sqlConnect,"INSERT INTO $queue_table (page_id,user_id,token,requested_at,status,group_id) VALUES (NULL,$user_id,'$token',".time().",'pending',$group_id)");
        Wo_DeleteLog("QUEUE user:$user_id group:$group_id token:" . substr($token,0,16));
        debug_response(200,"Group deletion queued for admin approval");
    }

    $delete_result = Wo_DeleteGroup($group_id);
    Wo_DeleteLog("GROUP_DELETE_ATTEMPT user:$user_id group:$group_id result:".($delete_result?"SUCCESS":"FAIL"));
    if($delete_result){
        mysqli_query($sqlConnect,"UPDATE $attempt_table SET success=1 WHERE user_id=$user_id AND ip='$ip' ORDER BY attempt_time DESC LIMIT 1");
        debug_response(200,"Group deleted successfully");
    } else debug_response(500,"Deletion failed");
}

debug_response(400,"Invalid request");