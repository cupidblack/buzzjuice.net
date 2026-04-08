<?php


require_once('config.php');

if (!class_exists('MG_myCRED_Toolkit_Github')) :
  class MG_myCRED_Toolkit_Github
  {

    private static  $instance = null;

    private function __construct()
    {
      add_action('plugins_loaded', array($this, 'mg_WC_active_mycred_github_plugin'));
    }
    public function mg_WC_active_mycred_github_plugin()
    {
      if (class_exists('myCRED_Core')) {
        require_once(MG_myCRED_GITHUB_PHP_CLIENT_DIR . 'GitHubClient.php');
        require_once(MG_myCRED_GITHUB_ADMIN_DIR  . 'class-mycred-github-admin.php');
        require_once(MG_myCRED_GITHUB_CLIENT_DIR . 'class-connect-client-with-github.php');
        require_once(MG_myCRED_GITHUB_ADMIN_DIR  . "class-github-application-settings.php");

        $this->mg_create_log_file();
      } else {
        add_action('admin_notices', array($this, 'mg_wc_rental_reservations_inactive_plugin_notice'));
      }
    }

    public function mg_wc_rental_reservations_inactive_plugin_notice()
    {
      MG_myCRED_Toolkit_Github::mg_add_flash_notice(esc_html(__("The myCRED plugin must be active", 'mycred-toolkit')), "error");
    }

    public static function mg_start_session()
    {
      if (!session_id()) {
        session_start();
      }
    }
    public static function getInstance()
    {
      if (!self::$instance instanceof self) {
        self::$instance = new  self();
      }
      return self::$instance;
    }
    function mg_create_log_file()
    {

      $upload = wp_upload_dir();
      $upload_dir = $upload['basedir'];
      $upload_dir = $upload_dir . '/mycred-github';
      if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777);
      }
      $logs = $upload_dir . "/error.log";
      if (!file_exists($logs)) {
        $fh = fopen($logs, "w");
        if (!$fh) {
          MG_myCRED_Toolkit_Github::mg_logger(sprintf(esc_html('Can not create error.log')));
          MG_myCRED_Toolkit_Github::mg_add_flash_notice(esc_html("Can not create error.log"), "error");
        } else {
          fclose($fh);
        }
      }
    }
    /**
     * Construct the log message and write it in log file
     * @param string $message the mssage which should logged
     * @param string $logFile the log file path
     * @return void
     */
    public static function mg_logger($message,  $logFile = MG_myCRED_GITHUB_LOG_FILE)
    {
      $message = "[" . date("F j, Y, g:i a") . "]  " . $message;
      $message .= PHP_EOL;
      return file_put_contents($logFile, $message, FILE_APPEND);
    }
    /**
     * Add a flash notice to {prefix}options table until a full page refresh is done
     *
     * @param string $notice our notice message
     * @param string $type This can be "info", "warning", "error" or "success", "warning" as default
     * @param boolean $dismissible set this to TRUE to add is-dismissible functionality to your notice
     * @return void
     */

    public static function mg_add_flash_notice($notice, $type = "warning", $dismissible = true)
    {
      // Here we return the notices saved on our option, if there are not notices, then an empty array is returned
      $notices = get_option("myCred_github_notices", array());
      $dismissible_text = ($dismissible) ? "is-dismissible" : "";
      $notice=array(
        "notice" => $notice,
        "type" => $type,
        "dismissible" => $dismissible_text
      );
      
      if (strlen(trim($notice["notice"])) != 0 && !in_array($notice, $notices)) {

        

        // We add our new notice.

        array_push($notices, $notice);

        // Then we update the option with our notices array
        update_option("myCred_github_notices", $notices);
      }
    }

    /**
     * Function executed when the 'admin_notices' action is called, here we check if there are notices on
     * our database and display them, after that, we remove the option to prevent notices being displayed forever.
     * @return void
     */

    public static function mg_display_flash_notices()
    {
      $notices = get_option("myCred_github_notices", array());

      // Iterate through our notices to be displayed and print them.
      foreach ($notices as $notice) {
        printf(
          '<div class="notice notice-%1$s %2$s"><p>%3$s</p></div>',
          esc_html( $notice['type'] ),
          esc_html( $notice['dismissible'] ),
          esc_html( $notice['notice'] )
        );
      }

      // Now we reset our options to prevent notices being displayed forever.
      if (!empty($notices)) {
        delete_option("myCred_github_notices", array());
      }
    }
    /**
     * Map action action name and response code of github api's to error messages
     *
     * @param string $notice function name which call the errors_messages_mapper
     * @param numder $code  the actual response code from github api 
     * @param string $content the body of response 
     * @return string error massage
     */
    public static function mg_errors_messages_mapper($action, $code, $content = "",$repo="")
    {

      switch ($action) {
        case MG_LIST_USER_REPOSITORIES_ACTION:
          if ($code == 401) {
            return esc_html(__("Invalid token", 'mycred-toolkit'));
          } else if ($code == 404) {
            return esc_html(__("Invalid GitHub Username", 'mycred-toolkit'));
          }
          break;
        case MG_ADD_REPOSITORIES_HOOKS_ACTION:
          if ($code == 401) {
            return esc_html(__("Invalid token", 'mycred-toolkit'));
          } else if ($code == 404) {
            return esc_html(__("You want create hook for non exist repository", 'mycred-toolkit'));
          } else if ($code == 422) {
            $content = json_decode($content);
            $message = ($content->errors) ? $repo." ".$content->errors[0]->message : "";
            return $message;
          }
          break;
        case MG_DELETE_REPOSITORIES_HOOKS_ACTION:
          if ($code == 401) {
            return esc_html(__("Invalid token", 'mycred-toolkit'));
          }
          break;
      }
    }
  }
  $instance = MG_myCRED_Toolkit_Github::getInstance();
  add_action('init', function () { MG_myCRED_Toolkit_Github::mg_start_session();}, 1);
  add_action('admin_notices', function () {MG_myCRED_Toolkit_Github::mg_display_flash_notices();}, 12);
endif;
