<?php
// ===== Secure Delete Page Endpoint: SDT, Deletion, Cooldown, Debug Logging =====
require_once __DIR__ . '/assets/init.php';
require_once __DIR__ . '/../shared/wwqd_bridge.php';
header("Content-Type: application/json");

// ---- LOGGING ----
function Wo_DeleteLog($msg){
    $file = __DIR__.'/assets/custom/logs/delete_page.log';
    $time = date("Y-m-d H:i:s");
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if(!file_exists(dirname($file))) mkdir(dirname($file),0755,true);
    file_put_contents($file,"[$time][$ip] $msg\n", FILE_APPEND);
}

// ---- JSON DEBUG RESPONSE ----
function debug_response($status, $message, $details=[]){
    $res = ["status"=>$status,"message"=>$message];
    if(!empty($details)) $res['debug'] = $details;
    Wo_DeleteLog("DEBUG: $message " . json_encode($details));
    echo json_encode($res); exit;
}

// ---- PHP ERROR HANDLER ----
set_error_handler(function($errno,$errstr,$errfile,$errline){
    Wo_DeleteLog("PHP_ERROR [$errno]: $errstr in $errfile:$errline");
});
register_shutdown_function(function(){
    $err = error_get_last();
    if($err) Wo_DeleteLog("SHUTDOWN_ERROR: ".json_encode($err));
});

// ---- SESSION/USER PERMISSION ----
if(!Wo_IsLogged()) debug_response(403,"Not logged in", ["wo_user"=>$wo['user'] ?? null]);
$user_id = $wo['user']['user_id'] ?? 0;
$page_id = intval($_POST['page_id'] ?? 0);
$page = Wo_PageData($page_id);
if(empty($page)) debug_response(404,"Page not found", ["page_id"=>$page_id,"user_id"=>$user_id]);
if($page['user_id']!=$user_id && !Wo_IsCanPageUpdate($page_id,'delete_page')){
    debug_response(403,"Permission denied", ["page_user"=>$page['user_id'],"session_user"=>$user_id]);
}

// ---- CONFIG/IP/DB ----
$secret = $wo['config']['site_encryption_key'] ?? 'CHANGE_ME_RANDOM_SECRET';
$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
global $sqlConnect;
$queue_enabled = false;
$table = "bz_delete_attempts";

// ---- SQL TABLE CHECK ----
if (!$sqlConnect) debug_response(500,"SQL connection invalid");
$res_check = mysqli_query($sqlConnect,"SHOW TABLES LIKE '$table'");
if(!$res_check) debug_response(500,"SQL error checking table",["mysqli_error"=>mysqli_error($sqlConnect)]);
if(mysqli_num_rows($res_check)==0) debug_response(500,"SQL table not found",["table"=>$table]);

// ---- COOLDOWN ----
$action = $_POST['action'] ?? '';
$res = mysqli_query($sqlConnect,"SELECT COUNT(*) as cnt, MAX(attempt_time) as lt FROM $table WHERE ip='$ip' AND attempt_time>".(time()-86400));
if(!$res) debug_response(500,"SQL error fetching attempts",["mysqli_error"=>mysqli_error($sqlConnect)]);
$row = mysqli_fetch_assoc($res);
$cnt = intval($row['cnt'] ?? 0);
$last_attempt = intval($row['lt'] ?? 0);

$waitTimes = [60,120,300,1200,3600,12345,86400,172800,216000]; // escalate as needed
$wait = $waitTimes[min($cnt,count($waitTimes)-1)];
$next_cooldown_sec = max(0,$wait-(time()-$last_attempt));

if($next_cooldown_sec>0 && $action=='send_code'){
    debug_response(429,"Wait $next_cooldown_sec seconds before requesting again.",["cooldown"=>$next_cooldown_sec,"cnt"=>$cnt,"last_attempt"=>$last_attempt]);
}

// ---- ATTEMPT LOG ----
$log_res = mysqli_query($sqlConnect,"INSERT INTO $table (user_id, ip, attempt_time, success) VALUES ($user_id,'$ip',".time().",0)");
if(!$log_res) Wo_DeleteLog("SQL_INSERT_ERROR: ".mysqli_error($sqlConnect));

// ---- TOKEN FUNCTIONS ----
function Wo_CreateDeleteToken($user_id,$page_id,$method='code'){
    global $secret;
    $prefix = strtoupper(substr(bin2hex(random_bytes(4)),0,4));
    $suffix = strtoupper(substr(bin2hex(random_bytes(5)),0,5));
    $issued = time();
    $expiry = $issued + 12345;
    $payload = json_encode([
        'user'=>$user_id,'page'=>$page_id,'prefix'=>$prefix,'suffix'=>$suffix,
        'method'=>$method,'iat'=>$issued,'exp'=>$expiry
    ]);
    $payload_enc = base64_encode($payload);
    $sig = hash_hmac('sha256',$payload_enc,$secret);
    $token = $payload_enc.'.'.$sig;
    return ['token'=>$token,'prefix'=>$prefix,'suffix'=>$suffix,'expiry'=>$expiry];
}
function Wo_VerifyDeleteToken($token,$suffix_input=null){
    global $secret;
    $parts = explode('.',$token);
    if(count($parts)!=2) return false;
    $payload_enc = $parts[0]; $sig = $parts[1];
    if(!hash_equals($sig,hash_hmac('sha256',$payload_enc,$secret))) return false;
    $data = json_decode(base64_decode($payload_enc), true);
    if(!$data || time()>$data['exp']) return false;
    if($data['method']=='code' && strtoupper($suffix_input)!=$data['suffix']) return false;
    return $data;
}

// ================== SEND CODE ==================
if($action=='send_code'){
    $data = Wo_CreateDeleteToken($user_id,$page_id,'code');
    $email = $wo['user']['email'] ?? '';
    $siteEmail = $wo['config']['siteEmail'] ?? '';
    $subject = "Buzzjuice Secure Page Deletion Code";
    $message = "Buzzjuice page deletion code: ".$data['prefix']."-".$data['suffix']."\n"
             . "Enter the last 5 characters only. Expires: ".date('Y-m-d H:i:s',$data['expiry'])
             . "\n\nIf you did not request this, please ignore.";
    $headers = "From: ".$wo['config']['siteName']." <".$siteEmail.">";
    // Validate emails
    if(!$email || !filter_var($email,FILTER_VALIDATE_EMAIL)) 
        debug_response(500,"Invalid user email",["email"=>$email]);
    if(!$siteEmail || !filter_var($siteEmail,FILTER_VALIDATE_EMAIL))
        debug_response(500,"Invalid site email",["siteEmail"=>$siteEmail]);
    $mail_result = @mail($email,$subject,$message,$headers);
    Wo_DeleteLog("SDT_MAIL user:$user_id page:$page_id to:$email result:".($mail_result?"SUCCESS":"FAIL"));
    if(!$mail_result)
        debug_response(500,"Failed to send SDT code",["email"=>$email,"headers"=>$headers]);
    Wo_DeleteLog("SDT_SENT user:$user_id page:$page_id prefix:$data[prefix] suffix:$data[suffix]");
    echo json_encode([
        "status"=>200,
        "token"=>$data['token'],
        "prefix"=>$data['prefix'],
        "expiry"=>$data['expiry'],
        "next_cooldown_sec"=>$wait,
        "message"=>"Code has been sent to $email"
    ]);
    exit;
}

// ================== CONFIRM DELETE ==================
if($action=='confirm_delete'){
    $token = $_POST['token'] ?? '';
    $suffix = $_POST['suffix'] ?? '';
    $verify = Wo_VerifyDeleteToken($token,$suffix);
    if(!$verify) debug_response(400,"Invalid or expired SDT code",["token"=>$token,"suffix"=>$suffix]);
    $page_id = $verify['page'];
    if($queue_enabled){
        $qres = mysqli_query($sqlConnect,"INSERT INTO bz_delete_queue (page_id,user_id,token,requested_at,status) VALUES ($page_id,$user_id,'$token',".time().",'pending')");
        Wo_DeleteLog("QUEUE user:$user_id page:$page_id token:" . substr($token,0,16));
        if(!$qres) debug_response(500,"SQL error: queue insert",["mysqli_error"=>mysqli_error($sqlConnect)]);
        debug_response(200,"Page deletion queued for admin approval");
    }
    $delete_result = Wo_DeletePage($page_id);
    Wo_DeleteLog("PAGE_DELETE_ATTEMPT user:$user_id page:$page_id result:".($delete_result?"SUCCESS":"FAIL"));
    if($delete_result){
        mysqli_query($sqlConnect,"UPDATE $table SET success=1 WHERE user_id=$user_id AND ip='$ip' ORDER BY attempt_time DESC LIMIT 1");
        debug_response(200,"Page deleted successfully",["page_id"=>$page_id]);
    } else debug_response(500,"Deletion failed",["page_id"=>$page_id]);
}

// ---- Default: Invalid request ----
debug_response(400,"Invalid request");