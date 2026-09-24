<?php
/**
 * BZJ internal signed connection-management client.
 * Pure PHP: no WordPress functions are required in Streams or Socials.
 */
if (!defined('BZJ_CONNECTION_CLIENT_LOADED')) {
    define('BZJ_CONNECTION_CLIENT_LOADED', true);

    function bzj_connection_uuid() {
        try { $d = random_bytes(16); }
        catch (Throwable $e) { $d = md5(uniqid('', true), true); }
        $d[6] = chr((ord($d[6]) & 0x0f) | 0x40);
        $d[8] = chr((ord($d[8]) & 0x3f) | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($d),4));
    }
    function bzj_connection_env($name) {
        $v=getenv($name);
        if ($v!==false && $v!=='') return (string)$v;
        if (defined($name) && constant($name)!=='') return (string)constant($name);
        return '';
    }
    function bzj_connection_secret($origin) {
        $name=$origin==='streams'?'BZJ_STREAMS_SYNC_SECRET':'BZJ_SOCIALS_SYNC_SECRET';
        $v=bzj_connection_env($name);
        return $v!==''?$v:bzj_connection_env('BUZZ_SSO_SECRET');
    }
    function bzj_connection_log($message,$context=array()) {
        $dir=dirname(__DIR__).'/data/logs';
        if(!is_dir($dir))@mkdir($dir,0755,true);
        @file_put_contents($dir.'/bzj-connections-client.log',json_encode(array(
            'time'=>gmdate('c'),'message'=>(string)$message,'context'=>$context
        ),JSON_UNESCAPED_SLASHES).PHP_EOL,FILE_APPEND|LOCK_EX);
    }
    function bzj_connection_client($origin,$operation,$actor_id,$target_id,$extra=array()) {
        $origin=strtolower((string)$origin);$operation=strtolower((string)$operation);
        $allowed=array('connection_request','connection_accept','connection_reject','connection_withdraw','connection_remove','follow','unfollow','block','unblock');
        if(!in_array($origin,array('streams','socials'),true)||!in_array($operation,$allowed,true))throw new InvalidArgumentException('Unsupported sync command.');
        $actor_id=(int)$actor_id;$target_id=(int)$target_id;
        if($actor_id<1||$target_id<1||$actor_id===$target_id)throw new InvalidArgumentException('Invalid actor/target.');
        $secret=bzj_connection_secret($origin);if($secret==='')throw new RuntimeException('Synchronization secret unavailable.');
        $event=isset($extra['event_id'])&&preg_match('/^[0-9a-f-]{36}$/i',(string)$extra['event_id'])?$extra['event_id']:bzj_connection_uuid();
        $payload=array('origin'=>$origin,'operation'=>$operation,'event_id'=>$event,'actor_external_id'=>$actor_id,'target_external_id'=>$target_id);
        foreach($extra as $k=>$v)if(!in_array($k,array('event_id','origin','operation','actor_external_id','target_external_id'),true))$payload[$k]=$v;
        $raw=json_encode($payload,JSON_UNESCAPED_SLASHES);if($raw===false)throw new RuntimeException('Could not encode synchronization payload.');
        $ts=(string)time();$sig=hash_hmac('sha256',$ts.'.'.$raw,$secret);
        $base=bzj_connection_env('WORDPRESS_API_BASE');if($base==='')$base='https://buzzjuice.net/wp-json';
        $url=rtrim($base,'/').'/bzj/v6/connection-management';
        $ch=curl_init($url);
        curl_setopt_array($ch,array(
            CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>$raw,
            CURLOPT_HTTPHEADER=>array('Content-Type: application/json','Accept: application/json','X-BZJ-Platform: '.$origin,'X-BZJ-Timestamp: '.$ts,'X-BZJ-Signature: '.$sig),
            CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>20
        ));
//        $response=curl_exec($ch);$http=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$err=curl_error($ch);curl_close($ch);

        $header_lines = array();

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($curl, $header) use (&$header_lines) {
            $header = trim($header);
        
            if ($header === '') {
                return strlen($header) + 2;
            }
        
            /*
             * Keep only diagnostic response headers.
             * Never log Set-Cookie or authentication-related headers.
             */
            if (preg_match('/^(HTTP\/|Server:|Via:|X-.*:|Retry-After:|RateLimit.*:|X-RateLimit.*:|Content-Type:)/i', $header)) {
                $header_lines[] = $header;
            }
        
            return strlen($header) + 2;
        });
        
        $response = curl_exec($ch);
        
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        
        curl_close($ch);

        $decoded=is_string($response)?json_decode($response,true):null;

//        bzj_connection_log('Signed sync request',array('origin'=>$origin,'operation'=>$operation,'actor_id'=>$actor_id,'target_id'=>$target_id,'http_code'=>$http,'event_id'=>$event,'error'=>$err,'response'=>$decoded));
  
        bzj_connection_log('Signed sync request',array(
            'origin'=>$origin,
            'operation'=>$operation,
            'actor_id'=>$actor_id,
            'target_id'=>$target_id,
            'http_code'=>$http,
            'event_id'=>$event,
            'error'=>$err,
            'response_headers'=>$header_lines,
            'response'=>$decoded
        ));
  
        if($response===false||$err!=='')return array('success'=>false,'event_id'=>$event,'http_code'=>$http,'error'=>$err?:'HTTP request failed');
        return is_array($decoded)?$decoded:array('success'=>false,'event_id'=>$event,'http_code'=>$http,'raw'=>$response);
    }
}
