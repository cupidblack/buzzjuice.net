<?php
/**
 * Plugin Name: BZJ Connections Synchronization Control Plane
 * Description: BuddyBoss/WordPress canonical relationship control plane for Buzzjuice Streams and Socials.
 * Version: 8.7.0
 * Author: Buzzjuice
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */
defined('ABSPATH') || exit;
if (defined('BZJ_CONNECTIONS_SYNC_LOADED')) return;
define('BZJ_CONNECTIONS_SYNC_LOADED', true);

final class BZJ_Connections_Sync {
    const VERSION='8.7.0';
    const NS='bzj/v6';
    const ROUTE='/connection-management';
    const LEDGER='bzj_relationships';
    const EVENTS='bzj_relationship_events';
    const LOG_DIR='/data/logs';
    const LOG_FILE='bzj-connections-sync.log';
    const LOCK_TIMEOUT=15;
    const AUTH_WINDOW=300;
    const MAX_ATTEMPTS=12;
    const CRON='bzj_connections_sync_recovery';
    const CRON_RECONCILE='bzj_connections_sync_reconcile';

    const WP='wordpress'; const STREAMS='streams'; const SOCIALS='socials';
    const REQUEST='connection_request'; const ACCEPT='connection_accept';
    const REJECT='connection_reject'; const WITHDRAW='connection_withdraw';
    const REMOVE='connection_remove'; const FOLLOW='follow';
    const UNFOLLOW='unfollow'; const BLOCK='block'; const UNBLOCK='unblock';

    private static $instance;
    private $ledger; private $events; private $locks=array(); private $internal=0;

    public static function instance(){ return self::$instance ?: self::$instance=new self(); }

    private function __construct(){
        global $wpdb;
        $this->ledger=$wpdb->prefix.self::LEDGER;
        $this->events=$wpdb->prefix.self::EVENTS;

        add_action('rest_api_init',array($this,'routes'));
        add_action('friends_friendship_requested',array($this,'bb_requested'),20,4);
        add_action('friends_friendship_accepted',array($this,'bb_accepted'),20,4);
        add_action('friends_friendship_rejected',array($this,'bb_rejected'),20,2);
        add_action('friends_friendship_withdrawn',array($this,'bb_withdrawn'),20,2);
        add_action('friends_friendship_whithdrawn',array($this,'bb_withdrawn'),20,2);
        add_action('friends_friendship_deleted',array($this,'bb_deleted'),20,3);
        add_action('friends_friendship_post_delete',array($this,'bb_post_deleted'),20,2);

        add_action('bp_start_following',array($this,'bb_follow_start'),20,1);
        add_action('bp_follow_start_following',array($this,'bb_follow_start'),20,1);
        add_action('bp_stop_following',array($this,'bb_follow_stop'),20,1);
        add_action('bp_follow_stop_following',array($this,'bb_follow_stop'),20,1);

        /* These are the hooks already used by the working v8.4.1 deployment. */
        add_action('bp_moderation_after_save',array($this,'bb_block_saved'),20,1);
        add_action('bb_moderation_after_delete',array($this,'bb_block_deleted'),20,1);

        add_action(self::CRON,array($this,'recover'));
        add_action(self::CRON_RECONCILE,array($this,'reconcile'));

        $this->schema();
        $this->schedule();
        $this->diagnostic_boot();
    }

    private function schema(){
        global $wpdb;
        $version=get_option('bzj_connections_sync_schema_version','');
        if($version===self::VERSION) return;
        require_once ABSPATH.'wp-admin/includes/upgrade.php';
        $c=$wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$this->ledger} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            user_a bigint(20) unsigned NOT NULL,
            user_b bigint(20) unsigned NOT NULL,
            connection_state varchar(20) NOT NULL DEFAULT 'none',
            requested_by bigint(20) unsigned NOT NULL DEFAULT 0,
            follow_a_to_b tinyint(1) unsigned NOT NULL DEFAULT 0,
            follow_b_to_a tinyint(1) unsigned NOT NULL DEFAULT 0,
            block_a_to_b tinyint(1) unsigned NOT NULL DEFAULT 0,
            block_b_to_a tinyint(1) unsigned NOT NULL DEFAULT 0,
            version bigint(20) unsigned NOT NULL DEFAULT 1,
            last_event_uuid char(36) NOT NULL DEFAULT '',
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY(id), UNIQUE KEY user_pair(user_a,user_b),
            KEY state(connection_state), KEY updated_at(updated_at)
        ) $c;");
        dbDelta("CREATE TABLE {$this->events} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            event_uuid char(36) NOT NULL,
            origin varchar(20) NOT NULL,
            operation varchar(40) NOT NULL,
            actor_wp_id bigint(20) unsigned NOT NULL DEFAULT 0,
            target_wp_id bigint(20) unsigned NOT NULL DEFAULT 0,
            pair_a bigint(20) unsigned NOT NULL DEFAULT 0,
            pair_b bigint(20) unsigned NOT NULL DEFAULT 0,
            payload longtext NULL,
            status varchar(20) NOT NULL DEFAULT 'pending',
            attempts int(10) unsigned NOT NULL DEFAULT 0,
            next_attempt_at datetime NOT NULL,
            last_error text NULL,
            processed_at datetime NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY(id), UNIQUE KEY event_uuid(event_uuid),
            KEY queue(status,next_attempt_at), KEY pair_key(pair_a,pair_b)
        ) $c;");
        update_option('bzj_connections_sync_schema_version',self::VERSION,false);
    }

    private function schedule(){
        if(!wp_next_scheduled(self::CRON)) wp_schedule_event(time()+120,'hourly',self::CRON);
        if(!wp_next_scheduled(self::CRON_RECONCILE)) wp_schedule_event(time()+300,'hourly',self::CRON_RECONCILE);
    }

    private function log($message,$ctx=array()){
        $dir=trailingslashit(ABSPATH).ltrim(self::LOG_DIR,'/');
        if(!is_dir($dir)) wp_mkdir_p($dir);
        if(!is_dir($dir)||!is_writable($dir)) return;
        $entry=array('time'=>gmdate('c'),'message'=>(string)$message,'context'=>$ctx);
        @file_put_contents(trailingslashit($dir).self::LOG_FILE,wp_json_encode($entry,JSON_UNESCAPED_SLASHES).PHP_EOL,FILE_APPEND|LOCK_EX);
    }

    private function uuid(){ return wp_generate_uuid4(); }
    private function pair($a,$b){ $a=absint($a);$b=absint($b);return $a<$b?array($a,$b):array($b,$a); }
    private function lock($a,$b){
        global $wpdb;$p=$this->pair($a,$b);$n='bzj_rel_'.$p[0].'_'.$p[1];
        if(isset($this->locks[$n])){$this->locks[$n]++;return true;}
        $ok=$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,%d)',$n,self::LOCK_TIMEOUT));
        if((int)$ok!==1)return false;$this->locks[$n]=1;return true;
    }
    private function unlock($a,$b){
        global $wpdb;$p=$this->pair($a,$b);$n='bzj_rel_'.$p[0].'_'.$p[1];
        if(!isset($this->locks[$n]))return;
        if(--$this->locks[$n]<=0){unset($this->locks[$n]);$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$n));}
    }
    private function push(){ $this->internal++; }
    private function pop(){ if($this->internal>0)$this->internal--; }
    private function is_internal(){ return $this->internal>0; }

    public function routes(){
        register_rest_route(self::NS,self::ROUTE,array(
            'methods'=>WP_REST_Server::CREATABLE,'callback'=>array($this,'command'),
            'permission_callback'=>'__return_true'
        ));
        register_rest_route(self::NS,self::ROUTE.'/diagnostics',array(
            'methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'diagnostics'),
            'permission_callback'=>function(){return current_user_can('manage_options');}
        ));
        register_rest_route(self::NS,self::ROUTE.'/status',array(
            'methods'=>WP_REST_Server::READABLE,'callback'=>array($this,'status'),
            'permission_callback'=>function(){return current_user_can('manage_options');}
        ));
    }

    public function status(WP_REST_Request $r){
        $a=absint($r->get_param('user_a'));$b=absint($r->get_param('user_b'));
        if(!$a||!$b||$a===$b)return new WP_Error('bzj_pair','Two different user IDs are required.',array('status'=>400));
        return rest_ensure_response(array('success'=>true,'version'=>self::VERSION,'relationship'=>$this->ledger($a,$b)));
    }

    public function diagnostics(){
        global $wpdb;
        $s=$this->db(self::STREAMS);$q=$this->db(self::SOCIALS);
        $out=array(
            'plugin_version'=>self::VERSION,
            'wordpress_database'=>defined('DB_NAME')?DB_NAME:'',
            'wordpress_usermeta'=>$wpdb->usermeta,
            'ledger_table'=>$this->ledger,
            'events_table'=>$this->events,
            'streams'=>$this->db_diagnostic($s,self::STREAMS),
            'socials'=>$this->db_diagnostic($q,self::SOCIALS),
            'cron'=>array('recovery'=>wp_next_scheduled(self::CRON),'reconcile'=>wp_next_scheduled(self::CRON_RECONCILE)),
            'log'=>trailingslashit(ABSPATH).ltrim(self::LOG_DIR,'/').self::LOG_FILE
        );
        $this->log('Diagnostics requested',$out);
        return rest_ensure_response($out);
    }

    private function db_diagnostic($db,$platform){
        if(!$db)return array('connected'=>false,'database'=>'','tables'=>array());
        $followers=$this->follow_table($db,$platform);
        $blocks=$this->block_table($db,$platform);
        $need_f=$platform===self::STREAMS?array('following_id','follower_id','active'):array('following_id','follower_id','active');
        $need_b=$platform===self::STREAMS?array('blocker','blocked'):array('user_id','block_userid');
        return array(
            'connected'=>true,'database'=>$this->db_name($db),
            'tables'=>array(
                'followers'=>array('name'=>$followers,'columns'=>$followers?$this->columns($db,$followers):array(),'required'=>$need_f),
                'blocks'=>array('name'=>$blocks,'columns'=>$blocks?$this->columns($db,$blocks):array(),'required'=>$need_b)
            )
        );
    }

    public function command(WP_REST_Request $r){
        $raw=$r->get_body();$p=json_decode($raw,true);
        if(!is_array($p))return new WP_Error('bzj_json','Malformed JSON.',array('status'=>400));
        $origin=sanitize_key(isset($p['origin'])?$p['origin']:'');
        $op=sanitize_key(isset($p['operation'])?$p['operation']:'');
        $event=sanitize_text_field(isset($p['event_id'])?$p['event_id']:'');
        if(!in_array($origin,array(self::STREAMS,self::SOCIALS),true))return new WP_Error('bzj_origin','Invalid origin.',array('status'=>400));
        $ops=array(self::REQUEST,self::ACCEPT,self::REJECT,self::WITHDRAW,self::REMOVE,self::FOLLOW,self::UNFOLLOW,self::BLOCK,self::UNBLOCK);
        if(!in_array($op,$ops,true))return new WP_Error('bzj_operation','Unsupported operation.',array('status'=>400));
        if(!$this->valid_uuid($event))return new WP_Error('bzj_event','Valid event_id required.',array('status'=>400));

        $auth=$this->authenticate($r,$raw,$origin);
        if(is_wp_error($auth))return $auth;

        $ae=absint(isset($p['actor_external_id'])?$p['actor_external_id']:(isset($p['actor_id'])?$p['actor_id']:0));
        $te=absint(isset($p['target_external_id'])?$p['target_external_id']:(isset($p['target_id'])?$p['target_id']:0));
        if(!$ae||!$te||$ae===$te)return new WP_Error('bzj_identity','Valid different actor/target IDs required.',array('status'=>400));

        $a=$this->map_external($origin,$ae);$b=$this->map_external($origin,$te);
        if(!$a||!$b||$a===$b)return new WP_Error('bzj_mapping','Could not map external IDs to WordPress users.',array('status'=>409));
        if($this->event_exists($event))return rest_ensure_response(array('success'=>true,'status'=>'already_processed','event_id'=>$event,'relationship'=>$this->ledger($a,$b)));
        if(!$this->lock($a,$b))return new WP_Error('bzj_busy','Relationship pair is busy.',array('status'=>503));

        try{
            $this->event_insert($event,$origin,$op,$a,$b,$p);
            $result=$this->process_external($origin,$op,$a,$b,$event,$p);
            if(is_wp_error($result)){ $this->retry($event,$result->get_error_message()); return $result; }
            $this->done($event);
            return rest_ensure_response(array('success'=>true,'status'=>'processed','event_id'=>$event,'relationship'=>$this->ledger($a,$b)));
        }catch(Throwable $e){
            $this->retry($event,$e->getMessage());
            $this->log('REST command exception',array('event_id'=>$event,'origin'=>$origin,'operation'=>$op,'actor_wp'=>$a,'target_wp'=>$b,'error'=>$e->getMessage()));
            return new WP_Error('bzj_internal','Synchronization failed.',array('status'=>500,'event_id'=>$event));
        }finally{$this->unlock($a,$b);}
    }

    private function authenticate($r,$raw,$origin){
        $platform=sanitize_key((string)$r->get_header('X-BZJ-Platform'));
        if($platform!==$origin)return new WP_Error('bzj_platform','X-BZJ-Platform does not match origin.',array('status'=>403));
        $ts=(string)$r->get_header('X-BZJ-Timestamp');$sig=trim((string)$r->get_header('X-BZJ-Signature'));
        if($ts===''||$sig===''||!ctype_digit($ts))return new WP_Error('bzj_auth','Missing synchronization authentication.',array('status'=>401));
        if(abs(time()-(int)$ts)>self::AUTH_WINDOW)return new WP_Error('bzj_expired','Synchronization request expired.',array('status'=>401));
        $secret=$this->secret($origin);if($secret==='')return new WP_Error('bzj_secret','Synchronization secret unavailable.',array('status'=>503));
        $expected=hash_hmac('sha256',$ts.'.'.$raw,$secret);
        return hash_equals($expected,$sig)?true:new WP_Error('bzj_auth','Invalid synchronization signature.',array('status'=>401));
    }
    private function secret($platform){
        $names=$platform===self::STREAMS?array('BZJ_STREAMS_SYNC_SECRET','BUZZ_SSO_SECRET'):array('BZJ_SOCIALS_SYNC_SECRET','BUZZ_SSO_SECRET');
        foreach($names as $n){$v=defined($n)?constant($n):getenv($n);if($v)return (string)$v;}
        return '';
    }
    private function process_external($origin,$op,$a,$b,$event,$p){
        if($op===self::FOLLOW && $origin===self::SOCIALS)return $this->bb_follow($a,$b);
        if($op===self::UNFOLLOW && $origin===self::SOCIALS)return $this->bb_unfollow($a,$b);
        switch($op){
            case self::REQUEST:return $this->bb_request($a,$b);
            case self::ACCEPT:return $this->bb_accept($a,$b);
            case self::REJECT:return $this->bb_reject($a,$b);
            case self::WITHDRAW:return $this->bb_withdraw($a,$b);
            case self::REMOVE:return $this->bb_remove($a,$b);
            case self::FOLLOW:return $this->bb_follow($a,$b);
            case self::UNFOLLOW:return $this->bb_unfollow($a,$b);
            case self::BLOCK:return $this->bb_block($a,$b,$event);
            case self::UNBLOCK:return $this->bb_unblock($a,$b,$event);
        }
        return new WP_Error('bzj_operation','Unknown operation.');
    }

    private function bb_request($a,$b){
        if($this->bb_blocked($a,$b))return new WP_Error('bzj_blocked','Blocked users cannot connect.');
        if(!function_exists('friends_add_friend'))return new WP_Error('bzj_bb','BuddyBoss connection unavailable.');
        $s=$this->bb_status($a,$b);
        if($s==='is_friend'||$s==='pending')return true;
        if($s==='awaiting_response')return new WP_Error('bzj_reverse','A reverse connection request already exists.');
        return friends_add_friend($a,$b)?true:new WP_Error('bzj_request','BuddyBoss rejected the request.');
    }
    private function friendship_id($a,$b){
        if(!function_exists('friends_get_friendship_id'))return 0;
        $id=(int)friends_get_friendship_id($a,$b);
        return $id?: (int)friends_get_friendship_id($b,$a);
    }
/*    private function bb_accept($b,$a){
        if(!function_exists('friends_accept_friendship'))return new WP_Error('bzj_bb','BuddyBoss acceptance unavailable.');
        $id=$this->friendship_id($a,$b);if(!$id)return new WP_Error('bzj_missing','No pending connection found.');
        $ok=friends_accept_friendship($id);
        return ($ok||$this->bb_status($a,$b)==='is_friend')?true:new WP_Error('bzj_accept','BuddyBoss could not accept the request.');
    } */
    
    private function bb_accept($acceptor_id, $requester_id) {
        if (!function_exists('friends_accept_friendship')) {
            return new WP_Error(
                'bzj_bb',
                'BuddyBoss acceptance unavailable.'
            );
        }
    
        $acceptor_id  = absint($acceptor_id);
        $requester_id = absint($requester_id);
    
        if (
            !$acceptor_id ||
            !$requester_id ||
            $acceptor_id === $requester_id
        ) {
            return new WP_Error(
                'bzj_accept_identity',
                'Invalid BuddyBoss acceptance identities.'
            );
        }
    
        /*
         * For connection_accept:
         *
         * requester_id = user who originally sent the request
         * acceptor_id  = user who is accepting it
         *
         * BuddyBoss friendship direction:
         *
         *   initiator_user_id = requester
         *   friend_user_id    = acceptor
         */
    
        if (!class_exists('BP_Friends_Friendship')) {
            return new WP_Error(
                'bzj_bb_class',
                'BuddyBoss friendship class unavailable.'
            );
        }
    
        /*
         * Resolve the friendship explicitly in the requester -> acceptor
         * direction. Do not use a generic pair lookup for acceptance.
         */
        $friendship_id = (int) BP_Friends_Friendship::get_friendship_id(
            $requester_id,
            $acceptor_id
        );
    
        if (!$friendship_id) {
            return new WP_Error(
                'bzj_missing',
                'No BuddyBoss connection request found.'
            );
        }
    
        /*
         * Load the friendship and verify its direction.
         */
        $friendship = new BP_Friends_Friendship(
            $friendship_id,
            true,
            false
        );
    
        if (
            empty($friendship->id) ||
            (int) $friendship->initiator_user_id !== $requester_id ||
            (int) $friendship->friend_user_id !== $acceptor_id
        ) {
            return new WP_Error(
                'bzj_accept_direction',
                'BuddyBoss connection request direction could not be verified.'
            );
        }
    
        /*
         * Idempotency:
         *
         * If another process already accepted the request, there is nothing
         * left to do.
         */
        if (!empty($friendship->is_confirmed)) {
            return true;
        }
    
        /*
         * Preserve the original WordPress user context.
         */
        $previous_user_id = function_exists('get_current_user_id')
            ? (int) get_current_user_id()
            : 0;
    
        /*
         * BuddyPress maintains its own logged-in-user context. Changing
         * WordPress's current user alone is not sufficient because
         * friends_accept_friendship() ultimately relies on
         * bp_loggedin_user_id().
         *
         * Temporarily force that BuddyPress value to the actual acceptor.
         */
        $bp_context_filter = function($user_id) use ($acceptor_id) {
            return $acceptor_id;
        };
    
        $ok = false;
    
        try {
            /*
             * Establish WordPress current-user context.
             */
            wp_set_current_user($acceptor_id);
    
            /*
             * Establish BuddyPress current-user context.
             */
            add_filter(
                'bp_loggedin_user_id',
                $bp_context_filter,
                PHP_INT_MAX
            );
    
            /*
             * Verify that the effective contexts agree before performing
             * the native BuddyBoss acceptance.
             */
            $wp_context = function_exists('get_current_user_id')
                ? (int) get_current_user_id()
                : 0;
    
            $bp_context = function_exists('bp_loggedin_user_id')
                ? (int) bp_loggedin_user_id()
                : 0;
    
            if ($wp_context !== $acceptor_id || $bp_context !== $acceptor_id) {
                $this->log(
                    'BuddyBoss acceptance context failed',
                    array(
                        'friendship_id'    => $friendship_id,
                        'requester_id'     => $requester_id,
                        'acceptor_id'      => $acceptor_id,
                        'wp_context'       => $wp_context,
                        'bp_context'       => $bp_context,
                        'previous_user_id' => $previous_user_id,
                    )
                );
    
                return new WP_Error(
                    'bzj_accept_context',
                    'BuddyBoss accepting-user context could not be established.'
                );
            }
    
            /*
             * Native BuddyBoss acceptance.
             *
             * Do NOT replace this with a direct database update. The native
             * function is required so BuddyBoss's normal acceptance hooks and
             * downstream synchronization remain intact.
             */
            $ok = (bool) friends_accept_friendship($friendship_id);
    
            /*
             * A concurrent process may have completed the acceptance even if
             * the native function returned false.
             */
            if (!$ok) {
                $status = $this->bb_status(
                    $requester_id,
                    $acceptor_id
                );
    
                if ($status === 'is_friend') {
                    $ok = true;
                }
            }
    
        } finally {
            /*
             * Remove our temporary BuddyPress context override first.
             */
            remove_filter(
                'bp_loggedin_user_id',
                $bp_context_filter,
                PHP_INT_MAX
            );
    
            /*
             * Restore the original WordPress user.
             */
            wp_set_current_user($previous_user_id);
        }
    
        /*
         * Final authoritative verification.
         */
        if (!$ok) {
            $verify = $this->bb_status(
                $requester_id,
                $acceptor_id
            );
    
            $this->log(
                'BuddyBoss connection acceptance failed',
                array(
                    'friendship_id' => $friendship_id,
                    'requester_id'  => $requester_id,
                    'acceptor_id'   => $acceptor_id,
                    'status_after'  => $verify,
                    'current_user'  => function_exists('get_current_user_id')
                        ? (int) get_current_user_id()
                        : 0,
                )
            );
    
            return new WP_Error(
                'bzj_accept',
                'BuddyBoss could not accept the request.'
            );
        }
    
        /*
         * Confirm the actual BuddyBoss relationship state after acceptance.
         */
        $final_status = $this->bb_status(
            $requester_id,
            $acceptor_id
        );
    
        if ($final_status !== 'is_friend') {
            $this->log(
                'BuddyBoss acceptance verification failed',
                array(
                    'friendship_id' => $friendship_id,
                    'requester_id'  => $requester_id,
                    'acceptor_id'   => $acceptor_id,
                    'status_after'  => $final_status,
                )
            );
    
            return new WP_Error(
                'bzj_accept_verify',
                'BuddyBoss acceptance could not be verified.'
            );
        }
    
        $this->log(
            'BuddyBoss connection accepted',
            array(
                'friendship_id' => $friendship_id,
                'requester_id'  => $requester_id,
                'acceptor_id'   => $acceptor_id,
                'status_after'  => $final_status,
            )
        );
    
        return true;
    }
    
    private function bb_reject($a,$b){
        if(!function_exists('friends_reject_friendship'))return new WP_Error('bzj_bb','BuddyBoss rejection unavailable.');
        $id=$this->friendship_id($a,$b);if(!$id)return true;
        $ok=friends_reject_friendship($id);
        return ($ok||$this->bb_status($a,$b)==='not_friends')?true:new WP_Error('bzj_reject','BuddyBoss could not reject the request.');
    }
    private function bb_withdraw($a,$b){
        if(!function_exists('friends_withdraw_friendship'))return new WP_Error('bzj_bb','BuddyBoss withdrawal unavailable.');
        $ok=friends_withdraw_friendship($a,$b);
        return ($ok||$this->bb_status($a,$b)==='not_friends')?true:new WP_Error('bzj_withdraw','BuddyBoss could not withdraw the request.');
    }
    private function bb_remove($a,$b){
        if(!function_exists('friends_remove_friend'))return new WP_Error('bzj_bb','BuddyBoss removal unavailable.');
        if($this->bb_status($a,$b)==='is_friend'){
            $this->push();try{$ok=friends_remove_friend($a,$b);if(!$ok)$ok=friends_remove_friend($b,$a);}finally{$this->pop();}
            if(!$ok&&$this->bb_status($a,$b)==='is_friend')return new WP_Error('bzj_remove','BuddyBoss could not remove the connection.');
        }
        return true;
    }
    private function bb_follow($a,$b){
        if($this->bb_blocked($a,$b))return new WP_Error('bzj_blocked','Blocked users cannot follow.');
        if(!function_exists('bp_start_following'))return new WP_Error('bzj_follow','BuddyBoss follow unavailable.');
        if(function_exists('bp_is_following')&&bp_is_following(array('leader_id'=>$b,'follower_id'=>$a)))return true;
        $r=bp_start_following(array('leader_id'=>$b,'follower_id'=>$a));
        return is_wp_error($r)?$r:($r===false?new WP_Error('bzj_follow','BuddyBoss rejected the follow.'):true);
    }
    private function bb_unfollow($a,$b){
        if(!function_exists('bp_stop_following'))return new WP_Error('bzj_unfollow','BuddyBoss unfollow unavailable.');
        if(function_exists('bp_is_following')&&!bp_is_following(array('leader_id'=>$b,'follower_id'=>$a)))return true;
        $this->push();try{$r=bp_stop_following(array('leader_id'=>$b,'follower_id'=>$a));}finally{$this->pop();}
        return is_wp_error($r)?$r:($r===false?new WP_Error('bzj_unfollow','BuddyBoss rejected the unfollow.'):true);
    }

    private function bb_block($a,$b,$event){
        if(!class_exists('BP_Moderation')||!class_exists('BP_Moderation_Members'))return new WP_Error('bzj_moderation','BuddyBoss moderation unavailable.');
        if(!$this->bb_block_exists($a,$b)){
            $m=new BP_Moderation($b,BP_Moderation_Members::$moderation_type,$a);
            $m->user_report=0;$m->content='';
            $this->push();try{$ok=$m->save();}finally{$this->pop();}
            if(!$ok)return new WP_Error('bzj_block','BuddyBoss could not create the block.');
        }
        $this->enforce_block($a,$b,$event);
        return true;
    }
    private function bb_unblock($a,$b,$event){
        if(!class_exists('BP_Moderation')||!class_exists('BP_Moderation_Members'))return new WP_Error('bzj_moderation','BuddyBoss moderation unavailable.');
        $m=new BP_Moderation($b,BP_Moderation_Members::$moderation_type,$a);
        if(!empty($m->id)&&empty($m->user_report)){
            $this->push();try{$ok=$m->delete(false);}finally{$this->pop();}
            if(!$ok)return new WP_Error('bzj_unblock','BuddyBoss could not remove the block.');
        }
        $this->project_block($a,$b,false,$event);
        return true;
    }

    public function bb_requested($id,$a,$b){
        if($this->is_internal())return;
        $this->bb_connection('requested',absint($a),absint($b),absint($id));
    }
    public function bb_accepted($id,$a,$b){
        if($this->is_internal())return;
        $this->bb_connection('accepted',absint($a),absint($b),absint($id));
    }
    public function bb_rejected($id,$f=null){
        if($this->is_internal()||!is_object($f))return;
        $this->bb_cancel('rejected',(int)$f->initiator_user_id,(int)$f->friend_user_id,(int)$id);
    }
    public function bb_withdrawn($id,$f=null){
        if($this->is_internal()||!is_object($f))return;
        $this->bb_cancel('withdrawn',(int)$f->initiator_user_id,(int)$f->friend_user_id,(int)$id);
    }
    public function bb_deleted($id,$a,$b){
        if($this->is_internal())return;
        $this->bb_connection('deleted',absint($a),absint($b),absint($id));
    }
    public function bb_post_deleted($a,$b){
        if($this->is_internal())return;
        $this->bb_connection('post_deleted',absint($a),absint($b),0);
    }

    private function bb_connection($event,$a,$b,$id){
        if(!$a||!$b||$a===$b)return;
        if(!$this->lock($a,$b)){ $u=$this->uuid();$this->event_insert($u,self::WP,'connection_'.$event,$a,$b,array('friendship_id'=>$id));$this->retry($u,'Pair lock busy.');return; }
        $u=$this->uuid();
        try{
            $this->event_insert($u,self::WP,'connection_'.$event,$a,$b,array('friendship_id'=>$id));
            if($event==='requested'){
                $this->save_ledger($a,$b,array('connection_state'=>'requested','requested_by'=>$a),$u);
                $this->project_social_connection($a,$b,'requested',$u);
            }elseif($event==='accepted'){
                $this->save_ledger($a,$b,array('connection_state'=>'connected','requested_by'=>0),$u);
                $this->project_social_connection($a,$b,'connected',$u);
            }else{
                $state=$this->bb_status($a,$b);
                if($state==='is_friend'){
                    $this->save_ledger($a,$b,array('connection_state'=>'connected','requested_by'=>0),$u);
                    $this->project_social_connection($a,$b,'connected',$u);
                }elseif($state==='pending'||$state==='awaiting_response'){
                    $rq=$state==='pending'?$a:$b;
                    $this->save_ledger($a,$b,array('connection_state'=>'requested','requested_by'=>$rq),$u);
                    $this->project_social_connection($a,$b,'requested',$u);
                }else{
                    $this->save_ledger($a,$b,array('connection_state'=>'none','requested_by'=>0),$u);
                    $this->remove_qd_pending($a,$b,$u);
                    $this->project_social_connection($a,$b,'none',$u);
                }
            }
            $this->done($u);
        }catch(Throwable $e){$this->retry($u,$e->getMessage());$this->log('BuddyBoss connection hook failed',array('event'=>$event,'error'=>$e->getMessage(),'event_id'=>$u));}
        finally{$this->unlock($a,$b);}
    }

    private function bb_cancel($event,$requester,$recipient,$fid){
        if(!$requester||!$recipient||$requester===$recipient)return;
        if(!$this->lock($requester,$recipient))return;
        $u=$this->uuid();
        try{
            $this->event_insert($u,self::WP,'connection_'.$event,$requester,$recipient,array('friendship_id'=>$fid,'pending_only'=>true));
            $state=$this->bb_status($requester,$recipient);
            if($state==='not_friends'){
                $this->save_ledger($requester,$recipient,array('connection_state'=>'none','requested_by'=>0),$u);
                $this->remove_qd_pending($requester,$recipient,$u);
            }elseif($state==='is_friend'){
                $this->save_ledger($requester,$recipient,array('connection_state'=>'connected','requested_by'=>0),$u);
            }
            $this->done($u);
        }catch(Throwable $e){$this->retry($u,$e->getMessage());$this->log('BuddyBoss cancellation failed',array('event'=>$event,'error'=>$e->getMessage(),'event_id'=>$u));}
        finally{$this->unlock($requester,$recipient);}
    }

    public function bb_follow_start($f){$this->bb_follow_event(self::FOLLOW,$f);}
    public function bb_follow_stop($f){$this->bb_follow_event(self::UNFOLLOW,$f);}
    private function bb_follow_event($op,$f){
        if($this->is_internal()||!is_object($f))return;
        $a=absint(isset($f->follower_id)?$f->follower_id:0);$b=absint(isset($f->leader_id)?$f->leader_id:0);
        if(!$a||!$b||$a===$b)return;
        if(!$this->lock($a,$b))return;
        $u=$this->uuid();
        try{
            $this->event_insert($u,self::WP,$op,$a,$b);
            if($op===self::FOLLOW&&!$this->bb_blocked($a,$b)){
                $this->save_ledger($a,$b,array($this->follow_field($a,$b)=>1),$u);
                $this->project_follow($a,$b,true,self::STREAMS,$u);
            }else{
                $this->save_ledger($a,$b,array($this->follow_field($a,$b)=>0),$u);
                $this->project_follow($a,$b,false,self::STREAMS,$u);
            }
            $this->done($u);
        }catch(Throwable $e){$this->retry($u,$e->getMessage());$this->log('BuddyBoss follow hook failed',array('operation'=>$op,'error'=>$e->getMessage(),'event_id'=>$u));}
        finally{$this->unlock($a,$b);}
    }

    public function bb_block_saved($m){if($this->valid_block($m))$this->bb_block_event(self::BLOCK,(int)$m->user_id,(int)$m->item_id,(int)$m->id);}
    public function bb_block_deleted($m){if($this->valid_block($m))$this->bb_block_event(self::UNBLOCK,(int)$m->user_id,(int)$m->item_id,(int)$m->id);}
    private function valid_block($m){return is_object($m)&&($m->item_type??'')==='user'&&empty($m->user_report)&&absint($m->user_id)>0&&absint($m->item_id)>0;}
    private function bb_block_event($op,$a,$b,$mid){
        if($this->is_internal()||$a===$b)return;if(!$this->lock($a,$b))return;
        $u=$this->uuid();
        try{
            $this->event_insert($u,self::WP,$op,$a,$b,array('moderation_id'=>$mid));
            if($op===self::BLOCK){
                $this->enforce_block($a,$b,$u);
                $this->save_ledger($a,$b,array($this->block_field($a,$b)=>1,'connection_state'=>'none','requested_by'=>0,'follow_a_to_b'=>0,'follow_b_to_a'=>0),$u);
                $this->project_block($a,$b,true,$u);
            }else{
                $this->save_ledger($a,$b,array($this->block_field($a,$b)=>0),$u);
                $this->project_block($a,$b,false,$u);
            }
            $this->done($u);
        }catch(Throwable $e){$this->retry($u,$e->getMessage());$this->log('BuddyBoss block hook failed',array('operation'=>$op,'error'=>$e->getMessage(),'event_id'=>$u));}
        finally{$this->unlock($a,$b);}
    }

    private function enforce_block($a,$b,$event){
        if(function_exists('friends_check_friendship_status')&&function_exists('friends_remove_friend')&&$this->bb_status($a,$b)==='is_friend'){
            $this->push();try{friends_remove_friend($a,$b);}finally{$this->pop();}
        }
        if(function_exists('bp_stop_following')){
            foreach(array(array($a,$b),array($b,$a)) as $x){
                if(function_exists('bp_is_following')&&bp_is_following(array('leader_id'=>$x[1],'follower_id'=>$x[0]))){
                    $this->push();try{bp_stop_following(array('leader_id'=>$x[1],'follower_id'=>$x[0]));}finally{$this->pop();}
                }
            }
        }
    }

    private function project_block($a,$b,$on,$event){
        $this->project_external_block($a,$b,$on,self::STREAMS,$event);
        $this->project_external_block($a,$b,$on,self::SOCIALS,$event);
        if($on){
            $this->project_follow($a,$b,false,self::STREAMS,$event);
            $this->project_follow($b,$a,false,self::STREAMS,$event);
            $this->project_follow($a,$b,false,self::SOCIALS,$event);
            $this->project_follow($b,$a,false,self::SOCIALS,$event);
            $this->project_social_connection($a,$b,'none',$event);
        }
    }

    private function ledger($a,$b){
        global $wpdb;$p=$this->pair($a,$b);
        $r=$wpdb->get_row($wpdb->prepare("SELECT * FROM {$this->ledger} WHERE user_a=%d AND user_b=%d LIMIT 1",$p[0],$p[1]),ARRAY_A);
        return $r?:array('user_a'=>$p[0],'user_b'=>$p[1],'connection_state'=>'none','requested_by'=>0,'follow_a_to_b'=>0,'follow_b_to_a'=>0,'block_a_to_b'=>0,'block_b_to_a'=>0,'version'=>0);
    }
    private function save_ledger($a,$b,$ch,$event=''){
        global $wpdb;$p=$this->pair($a,$b);$e=$this->ledger($a,$b);$now=gmdate('Y-m-d H:i:s');
        $d=array('user_a'=>$p[0],'user_b'=>$p[1],
            'connection_state'=>isset($ch['connection_state'])?sanitize_key($ch['connection_state']):$e['connection_state'],
            'requested_by'=>isset($ch['requested_by'])?absint($ch['requested_by']):absint($e['requested_by']),
            'follow_a_to_b'=>isset($ch['follow_a_to_b'])?(int)!empty($ch['follow_a_to_b']):(int)$e['follow_a_to_b'],
            'follow_b_to_a'=>isset($ch['follow_b_to_a'])?(int)!empty($ch['follow_b_to_a']):(int)$e['follow_b_to_a'],
            'block_a_to_b'=>isset($ch['block_a_to_b'])?(int)!empty($ch['block_a_to_b']):(int)$e['block_a_to_b'],
            'block_b_to_a'=>isset($ch['block_b_to_a'])?(int)!empty($ch['block_b_to_a']):(int)$e['block_b_to_a'],
            'version'=>max(1,(int)$e['version']+1),'last_event_uuid'=>$event?:($e['last_event_uuid']??''),'updated_at'=>$now);
        if(empty($e['version'])){$d['created_at']=$now;$wpdb->insert($this->ledger,$d);}else{$wpdb->update($this->ledger,$d,array('user_a'=>$p[0],'user_b'=>$p[1]));}
        return $this->ledger($a,$b);
    }
    private function follow_field($a,$b){$p=$this->pair($a,$b);return $a===$p[0]?'follow_a_to_b':'follow_b_to_a';}
    private function block_field($a,$b){$p=$this->pair($a,$b);return $a===$p[0]?'block_a_to_b':'block_b_to_a';}
    private function bb_status($a,$b){return function_exists('friends_check_friendship_status')?friends_check_friendship_status($a,$b):'not_friends';}
    private function bb_blocked($a,$b){return $this->bb_block_exists($a,$b)||$this->bb_block_exists($b,$a);}
    private function bb_block_exists($a,$b){
        if(class_exists('BP_Moderation')&&class_exists('BP_Moderation_Members')){
            $m=new BP_Moderation($b,BP_Moderation_Members::$moderation_type,$a);
            if(!empty($m->id)&&empty($m->user_report))return true;
        }
        $l=$this->ledger($a,$b);return !empty($l[$this->block_field($a,$b)]);
    }

    private function load_helpers(){
        static $done=null;if($done!==null)return $done;
        foreach(array(ABSPATH.'shared/db_helpers.php',dirname(ABSPATH).'/shared/db_helpers.php') as $f){
            if(is_file($f)){require_once $f;return $done=true;}
        }
        $this->log('db_helpers.php missing');return $done=false;
    }
    private function db($platform){
        if(!$this->load_helpers())return false;
        $f=$platform===self::STREAMS?'get_wowonder_db':'get_qd_db_conn';
        if(!function_exists($f))return false;$d=$f();return $d instanceof mysqli?$d:false;
    }
    private function db_name($db){$r=$db?$db->query('SELECT DATABASE() AS db'):false;$x=$r?$r->fetch_assoc():array();if($r)$r->free();return isset($x['db'])?$x['db']:'';}
    private function map_wp($wp,$platform){return absint(get_user_meta($wp,$platform===self::STREAMS?'wo_user_id':'qd_user_id',true));}
    private function map_external($platform,$external){
        global $wpdb;$key=$platform===self::STREAMS?'wo_user_id':'qd_user_id';
        return (int)$wpdb->get_var($wpdb->prepare("SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key=%s AND meta_value=%s ORDER BY umeta_id ASC LIMIT 1",$key,(string)absint($external)));
    }
    private function table_exists($db,$t){$t=preg_replace('/[^A-Za-z0-9_]/','',$t);$r=$db->query("SHOW TABLES LIKE '".$db->real_escape_string($t)."'");$ok=$r&&$r->num_rows>0;if($r)$r->free();return $ok;}
    private function first_table($db,$list){foreach($list as $t)if($this->table_exists($db,$t))return $t;return false;}
    private function follow_table($db,$p){return $this->first_table($db,$p===self::STREAMS?array('Wo_Followers','wo_followers','followers'):array('followers'));}
    private function block_table($db,$p){return $this->first_table($db,$p===self::STREAMS?array('Wo_Blocks','wo_blocks','blocks'):array('blocks'));}
    private function columns($db,$t){$o=array();if(!$t)return $o;$r=$db->query("SHOW COLUMNS FROM `".preg_replace('/[^A-Za-z0-9_]/','',$t)."`");if($r){while($x=$r->fetch_assoc())$o[strtolower($x['Field'])]=true;$r->free();}return $o;}

    private function project_follow($a,$b,$on,$platform,$event){
        $ea=$this->map_wp($a,$platform);$eb=$this->map_wp($b,$platform);
        if(!$ea||!$eb){$this->queue($platform,$on?self::FOLLOW:self::UNFOLLOW,$a,$b,array('event_uuid'=>$event,'reason'=>'mapping'));return false;}
        $db=$this->db($platform);$t=$this->follow_table($db,$platform);
        if(!$db||!$t){$this->queue($platform,$on?self::FOLLOW:self::UNFOLLOW,$a,$b,array('event_uuid'=>$event,'reason'=>'database_or_table'));return false;}
        try{
            if($on)$this->external_follow_upsert($db,$t,$eb,$ea,$platform,$a,$b,$event);
            else $this->external_follow_delete($db,$t,$eb,$ea,$platform,$a,$b,$event);
            return true;
        }catch(Throwable $e){$this->queue($platform,$on?self::FOLLOW:self::UNFOLLOW,$a,$b,array('event_uuid'=>$event,'error'=>$e->getMessage()));return false;}
    }
    private function external_follow_upsert($db,$t,$following,$follower,$platform,$a,$b,$event){
        $q=$db->prepare("SELECT id,active FROM `{$t}` WHERE following_id=? AND follower_id=? LIMIT 1");if(!$q)throw new RuntimeException($db->error);
        $q->bind_param('ii',$following,$follower);$q->execute();$r=$q->get_result()->fetch_assoc();$q->close();
        if($r){$id=(int)$r['id'];$q=$db->prepare("UPDATE `{$t}` SET active=1 WHERE id=?");$q->bind_param('i',$id);if(!$q->execute())throw new RuntimeException($q->error);$q->close();}
        else{$cols=$this->columns($db,$t);if(isset($cols['created_at'])){$q=$db->prepare("INSERT INTO `{$t}` (following_id,follower_id,active,created_at) VALUES(?,?,1,?)");$now=time();$q->bind_param('iii',$following,$follower,$now);}else{$q=$db->prepare("INSERT INTO `{$t}` (following_id,follower_id,active) VALUES(?,?,1)");$q->bind_param('ii',$following,$follower);}if(!$q->execute())throw new RuntimeException($q->error);$q->close();}
        $ok=$this->follow_exists($db,$t,$following,$follower,true);$this->external_log($platform,self::FOLLOW,self::WP,$a,$b,$follower,$following,'UPSERT',1,$ok,$event,$ok?'':'follow verification failed');if(!$ok)throw new RuntimeException('External follow verification failed.');
    }
    private function external_follow_delete($db,$t,$following,$follower,$platform,$a,$b,$event){
        $q=$db->prepare("DELETE FROM `{$t}` WHERE following_id=? AND follower_id=?");if(!$q)throw new RuntimeException($db->error);
        $q->bind_param('ii',$following,$follower);if(!$q->execute())throw new RuntimeException($q->error);$n=$q->affected_rows;$q->close();
        $ok=!$this->follow_exists($db,$t,$following,$follower,true);$this->external_log($platform,self::UNFOLLOW,self::WP,$a,$b,$follower,$following,'DELETE',$n,$ok,$event,$ok?'':'follow delete verification failed');if(!$ok)throw new RuntimeException('External follow delete verification failed.');
    }
    private function follow_exists($db,$t,$following,$follower,$active=false){
        $sql="SELECT id FROM `{$t}` WHERE following_id=? AND follower_id=?".($active?' AND active=1':'').' LIMIT 1';$q=$db->prepare($sql);if(!$q)return false;$q->bind_param('ii',$following,$follower);$q->execute();$r=$q->get_result();$ok=$r->num_rows>0;$q->close();return $ok;
    }

    private function project_social_connection($a,$b,$state,$event){
        $qa=$this->map_wp($a,self::SOCIALS);$qb=$this->map_wp($b,self::SOCIALS);$db=$this->db(self::SOCIALS);
        if(!$qa||!$qb||!$db){$this->queue(self::SOCIALS,'connection_'.$state,$a,$b,array('event_uuid'=>$event,'reason'=>'mapping_or_database'));return false;}
        try{
            if($state==='requested'){
                $l=$this->ledger($a,$b);$rq=absint($l['requested_by']);if(!$rq)throw new RuntimeException('No requester in ledger.');
                $recipient=$rq===$a?$b:$a;$this->qd_set_friend($db,$this->map_wp($rq,self::SOCIALS),$this->map_wp($recipient,self::SOCIALS),0,$rq,$recipient,$event);
            }elseif($state==='connected'){
                $this->qd_set_friend($db,$qa,$qb,1,$a,$b,$event);$this->qd_set_friend($db,$qb,$qa,1,$b,$a,$event);
            }else{$this->qd_remove_friend_rows($db,$qa,$qb,$a,$b,$event);}
            return true;
        }catch(Throwable $e){$this->queue(self::SOCIALS,'connection_'.$state,$a,$b,array('event_uuid'=>$event,'error'=>$e->getMessage()));return false;}
    }
    private function qd_set_friend($db,$requester,$recipient,$active,$a,$b,$event){
        $t=$this->follow_table($db,self::SOCIALS);if(!$t)throw new RuntimeException('QuickDate followers table not found.');
        $q=$db->prepare("SELECT id FROM `{$t}` WHERE following_id=? AND follower_id=? LIMIT 1");if(!$q)throw new RuntimeException($db->error);
        $q->bind_param('ii',$recipient,$requester);$q->execute();$r=$q->get_result()->fetch_assoc();$q->close();
        if($r){$id=(int)$r['id'];$q=$db->prepare("UPDATE `{$t}` SET active=? WHERE id=?");$q->bind_param('ii',$active,$id);}
        else{$q=$db->prepare("INSERT INTO `{$t}` (following_id,follower_id,active) VALUES(?,?,?)");$q->bind_param('iii',$recipient,$requester,$active);}
        if(!$q->execute())throw new RuntimeException($q->error);$q->close();
        $ok=$this->qd_row_state($db,$t,$requester,$recipient,$active);$this->external_log(self::SOCIALS,$active?self::ACCEPT:self::REQUEST,self::WP,$a,$b,$requester,$recipient,'UPSERT',1,$ok,$event,$ok?'':'friend projection verification failed');if(!$ok)throw new RuntimeException('QuickDate friend projection verification failed.');
    }
    private function qd_row_state($db,$t,$requester,$recipient,$active){
        $q=$db->prepare("SELECT active FROM `{$t}` WHERE following_id=? AND follower_id=? LIMIT 1");if(!$q)return false;$q->bind_param('ii',$recipient,$requester);$q->execute();$r=$q->get_result()->fetch_assoc();$q->close();return $r&&(int)$r['active']===(int)$active;
    }
    private function qd_remove_friend_rows($db,$qa,$qb,$a,$b,$event){
        $t=$this->follow_table($db,self::SOCIALS);if(!$t)throw new RuntimeException('QuickDate followers table not found.');
        /* Remove only the accepted-friend projection. Preserve a pre-existing ordinary one-way follow. */
        foreach(array(array($qb,$qa),array($qa,$qb)) as $p){
            $q=$db->prepare("DELETE FROM `{$t}` WHERE following_id=? AND follower_id=? AND active=1");if(!$q)throw new RuntimeException($db->error);
            $q->bind_param('ii',$p[0],$p[1]);$q->execute();$q->close();
        }
        $this->external_log(self::SOCIALS,self::REMOVE,self::WP,$a,$b,$qa,$qb,'DELETE_FRIEND_PROJECTION',2,true,$event,'');
    }
    private function remove_qd_pending($requester_wp,$recipient_wp,$event){
        $rq=$this->map_wp($requester_wp,self::SOCIALS);$rc=$this->map_wp($recipient_wp,self::SOCIALS);$db=$this->db(self::SOCIALS);
        if(!$rq||!$rc||!$db){$this->queue(self::SOCIALS,self::WITHDRAW,$requester_wp,$recipient_wp,array('event_uuid'=>$event,'pending_only'=>true));return false;}
        $t=$this->follow_table($db,self::SOCIALS);if(!$t)return false;
        $q=$db->prepare("DELETE FROM `{$t}` WHERE following_id=? AND follower_id=? AND active=0");if(!$q)throw new RuntimeException($db->error);
        $q->bind_param('ii',$rc,$rq);if(!$q->execute())throw new RuntimeException($q->error);$n=$q->affected_rows;$q->close();
        $ok=!$this->qd_pending_exists($db,$t,$rq,$rc);$this->external_log(self::SOCIALS,self::WITHDRAW,self::WP,$requester_wp,$recipient_wp,$rq,$rc,'DELETE_PENDING',$n,$ok,$event,$ok?'':'pending row remains');if(!$ok)throw new RuntimeException('Pending QuickDate deletion verification failed.');return true;
    }
    private function qd_pending_exists($db,$t,$rq,$rc){$q=$db->prepare("SELECT id FROM `{$t}` WHERE following_id=? AND follower_id=? AND active=0 LIMIT 1");if(!$q)return false;$q->bind_param('ii',$rc,$rq);$q->execute();$r=$q->get_result();$ok=$r->num_rows>0;$q->close();return $ok;}

    private function project_external_block($a,$b,$on,$platform,$event){
        $ea=$this->map_wp($a,$platform);$eb=$this->map_wp($b,$platform);$db=$this->db($platform);
        if(!$ea||!$eb||!$db){$this->queue($platform,$on?self::BLOCK:self::UNBLOCK,$a,$b,array('event_uuid'=>$event,'reason'=>'mapping_or_database'));return false;}
        $t=$this->block_table($db,$platform);if(!$t){$this->queue($platform,$on?self::BLOCK:self::UNBLOCK,$a,$b,array('event_uuid'=>$event,'reason'=>'block_table'));return false;}
        try{
            if($on){
                $exists=$this->external_block_exists($db,$t,$ea,$eb,$platform);
                if(!$exists){
                    if($platform===self::STREAMS)$q=$db->prepare("INSERT INTO `{$t}` (blocker,blocked) VALUES (?,?)");
                    else $q=$db->prepare("INSERT INTO `{$t}` (user_id,block_userid) VALUES (?,?)");
                    $q->bind_param('ii',$ea,$eb);if(!$q->execute())throw new RuntimeException($q->error);$n=$q->affected_rows;$q->close();
                }else $n=0;
                $ok=$this->external_block_exists($db,$t,$ea,$eb,$platform);$this->external_log($platform,self::BLOCK,self::WP,$a,$b,$ea,$eb,'INSERT',$n,$ok,$event,$ok?'':'block verification failed');if(!$ok)throw new RuntimeException('External block verification failed.');
            }else{
                $sql=$platform===self::STREAMS?"DELETE FROM `{$t}` WHERE blocker=? AND blocked=?":"DELETE FROM `{$t}` WHERE user_id=? AND block_userid=?";
                $q=$db->prepare($sql);$q->bind_param('ii',$ea,$eb);if(!$q->execute())throw new RuntimeException($q->error);$n=$q->affected_rows;$q->close();
                $ok=!$this->external_block_exists($db,$t,$ea,$eb,$platform);$this->external_log($platform,self::UNBLOCK,self::WP,$a,$b,$ea,$eb,'DELETE',$n,$ok,$event,$ok?'':'block delete verification failed');if(!$ok)throw new RuntimeException('External unblock verification failed.');
            }
            return true;
        }catch(Throwable $e){$this->queue($platform,$on?self::BLOCK:self::UNBLOCK,$a,$b,array('event_uuid'=>$event,'error'=>$e->getMessage()));return false;}
    }
    private function external_block_exists($db,$t,$a,$b,$platform){
        $sql=$platform===self::STREAMS?"SELECT id FROM `{$t}` WHERE blocker=? AND blocked=? LIMIT 1":"SELECT id FROM `{$t}` WHERE user_id=? AND block_userid=? LIMIT 1";$q=$db->prepare($sql);if(!$q)return false;$q->bind_param('ii',$a,$b);$q->execute();$r=$q->get_result();$ok=$r->num_rows>0;$q->close();return $ok;
    }

    private function external_log($platform,$operation,$origin,$aw,$tw,$ae,$te,$sqlop,$affected,$verified,$event,$error){
        $this->log('External database mutation',array('platform'=>$platform,'operation'=>$operation,'origin'=>$origin,'actor_wp_id'=>$aw,'target_wp_id'=>$tw,'actor_external_id'=>$ae,'target_external_id'=>$te,'database'=>$this->db($platform)?$this->db_name($this->db($platform)):'','table'=>$platform===self::STREAMS?'followers/blocks':'followers/blocks','sql_operation'=>$sqlop,'affected_rows'=>$affected,'verified'=>(bool)$verified,'event_uuid'=>$event,'error'=>$error));
    }

    private function event_exists($u){global $wpdb;return (bool)$wpdb->get_var($wpdb->prepare("SELECT id FROM {$this->events} WHERE event_uuid=%s LIMIT 1",$u));}
    private function event_insert($u,$origin,$op,$a,$b,$payload=array()){
        global $wpdb;if($this->event_exists($u))return true;$p=$this->pair($a,$b);$now=gmdate('Y-m-d H:i:s');
        return false!==$wpdb->insert($this->events,array('event_uuid'=>$u,'origin'=>$origin,'operation'=>$op,'actor_wp_id'=>$a,'target_wp_id'=>$b,'pair_a'=>$p[0],'pair_b'=>$p[1],'payload'=>wp_json_encode($payload,JSON_UNESCAPED_SLASHES),'status'=>'pending','attempts'=>0,'next_attempt_at'=>$now,'created_at'=>$now,'updated_at'=>$now));
    }
    private function done($u){global $wpdb;$now=gmdate('Y-m-d H:i:s');$wpdb->update($this->events,array('status'=>'processed','processed_at'=>$now,'updated_at'=>$now,'last_error'=>null),array('event_uuid'=>$u));}
    private function retry($u,$err){
        global $wpdb;$n=(int)$wpdb->get_var($wpdb->prepare("SELECT attempts FROM {$this->events} WHERE event_uuid=%s",$u))+1;$status=$n>=self::MAX_ATTEMPTS?'failed':'pending';$next=gmdate('Y-m-d H:i:s',time()+min(3600,max(30,$n*30)));
        $wpdb->update($this->events,array('attempts'=>$n,'status'=>$status,'last_error'=>substr((string)$err,0,65000),'next_attempt_at'=>$next,'updated_at'=>gmdate('Y-m-d H:i:s')),array('event_uuid'=>$u));
    }
    private function queue($platform,$op,$a,$b,$payload=array()){$u=isset($payload['event_uuid'])?$payload['event_uuid']:$this->uuid();$payload['projection']=true;$this->event_insert($u,$platform,$op,$a,$b,$payload);return $u;}

    public function recover(){
        global $wpdb;$rows=$wpdb->get_results("SELECT * FROM {$this->events} WHERE status='pending' AND next_attempt_at<=UTC_TIMESTAMP() ORDER BY id ASC LIMIT 25");
        foreach((array)$rows as $r){$this->recover_one($r);}
    }
    private function recover_one($r){
        $a=(int)$r->actor_wp_id;$b=(int)$r->target_wp_id;if(!$a||!$b)return;if(!$this->lock($a,$b))return;
        try{
            $p=json_decode((string)$r->payload,true);$projection=!empty($p['projection']);$ok=false;
            if($projection){
                if($r->operation===self::FOLLOW)$ok=$this->project_follow($a,$b,true,$r->origin,$r->event_uuid);
                elseif($r->operation===self::UNFOLLOW)$ok=$this->project_follow($a,$b,false,$r->origin,$r->event_uuid);
                elseif($r->operation===self::BLOCK)$ok=$this->project_external_block($a,$b,true,$r->origin,$r->event_uuid);
                elseif($r->operation===self::UNBLOCK)$ok=$this->project_external_block($a,$b,false,$r->origin,$r->event_uuid);
                elseif(strpos($r->operation,'connection_')===0)$ok=$this->project_social_connection($a,$b,$this->ledger($a,$b)['connection_state'],$r->event_uuid);
            }
            if($ok)$this->done($r->event_uuid);else $this->retry($r->event_uuid,'Recovery projection did not complete.');
        }catch(Throwable $e){$this->retry($r->event_uuid,$e->getMessage());}
        finally{$this->unlock($a,$b);}
    }
    public function reconcile(){
        global $wpdb;$rows=$wpdb->get_results("SELECT user_a,user_b FROM {$this->ledger} WHERE updated_at>=UTC_TIMESTAMP()-INTERVAL 7 DAY ORDER BY updated_at DESC LIMIT 100");
        foreach((array)$rows as $r){$a=(int)$r->user_a;$b=(int)$r->user_b;if($this->lock($a,$b)){try{$this->refresh($a,$b);$this->project_pair($a,$b);}finally{$this->unlock($a,$b);}}}
    }
    private function refresh($a,$b){
        $state='none';$rq=0;$s=$this->bb_status($a,$b);
        if($s==='is_friend')$state='connected';elseif($s==='pending'){$state='requested';$rq=$a;}elseif($s==='awaiting_response'){$state='requested';$rq=$b;}
        $fa=function_exists('bp_is_following')&&bp_is_following(array('leader_id'=>$b,'follower_id'=>$a));
        $fb=function_exists('bp_is_following')&&bp_is_following(array('leader_id'=>$a,'follower_id'=>$b));
        $ba=$this->bb_block_exists($a,$b);$bb=$this->bb_block_exists($b,$a);
        if($ba||$bb){$state='none';$rq=0;$fa=$fb=false;}
        $this->save_ledger($a,$b,array('connection_state'=>$state,'requested_by'=>$rq,'follow_a_to_b'=>$fa?1:0,'follow_b_to_a'=>$fb?1:0,'block_a_to_b'=>$ba?1:0,'block_b_to_a'=>$bb?1:0),$this->uuid());
    }
    private function project_pair($a,$b){
        $l=$this->ledger($a,$b);
        if(!empty($l['block_a_to_b']))$this->project_block($a,$b,true,$l['last_event_uuid']);elseif(!empty($l['block_b_to_a']))$this->project_block($b,$a,true,$l['last_event_uuid']);else{
            $this->project_social_connection($a,$b,$l['connection_state'],$l['last_event_uuid']);
            $this->project_follow($a,$b,!empty($l['follow_a_to_b']),self::STREAMS,$l['last_event_uuid']);
            $this->project_follow($b,$a,!empty($l['follow_b_to_a']),self::STREAMS,$l['last_event_uuid']);
        }
    }
    private function diagnostic_boot(){ $this->log('Plugin bootstrap',array('version'=>self::VERSION,'wp_database'=>defined('DB_NAME')?DB_NAME:''));$this->log('Database/table verification',$this->diagnostics_payload());}
    private function diagnostics_payload(){return array('wordpress'=>array('database'=>defined('DB_NAME')?DB_NAME:'','usermeta'=>isset($GLOBALS['wpdb'])?$GLOBALS['wpdb']->usermeta:'','ledger'=>$this->ledger,'events'=>$this->events),'streams'=>$this->db_diagnostic($this->db(self::STREAMS),self::STREAMS),'socials'=>$this->db_diagnostic($this->db(self::SOCIALS),self::SOCIALS));}
    private function valid_uuid($u){return (bool)preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',(string)$u);}
}
BZJ_Connections_Sync::instance();
