<?php
class wpnewrelic {
    function __construct() {
        $this->settingsobj = new wpnewrelic_settings($this);
        $this->widgets = new wp_newrelic_widgets($this);
        if (extension_loaded('newrelic')) {
            add_action('init', array(&$this, 'init'));
            if ($this->settingsobj->get_setting('enable_newrelic_errors')) {
                add_filter('wp_die_handler', array(&$this, 'wp_die_handler'));
                set_error_handler("newrelic_notice_error");
            }
        }
    }
    function init() {
        if (defined('DOING_CRON') && DOING_CRON == true) {
            if ($this->settingsobj->get_setting('ignore_cron')) {
                newrelic_ignore_transaction();
            }
        }
        if (defined('DOING_AJAX') && DOING_AJAX == true) {
            newrelic_disable_autorum();
        }
        if ($this->settingsobj->get_setting('application_id')) {
            newrelic_set_appname($this->settingsobj->get_setting('application_id'));
        }
        if ($this->settingsobj->get_setting('enable_newrelic_errors')) {

        }
    }
    function wp_die_handler($function) {
        return array(&$this, 'wp_die');
    }
    function wp_die($message, $title = '', $args = array()) {
        $passedmessage = $message;
        if ( function_exists( 'is_wp_error' ) && is_wp_error( $message ) ) {
            $errors = $message->get_error_messages();
            switch ( count( $errors ) ) :
                case 0 :
                    $message = '';
                    break;
                case 1 :
                    $message = "<p>{$errors[0]}</p>";
                    break;
                default :
                    $message = "<ul>\n\t\t<li>" . join( "</li>\n\t\t<li>", $errors ) . "</li>\n\t</ul>";
                    break;
            endswitch;
        }
        newrelic_notice_error($title.' '.$message);
        if (function_exists('_default_wp_die_handler')) {
            _default_wp_die_handler($passedmessage, $title = '', $args);
        } else {
            die($message);
        }
    }
    function get_newrelic_url($url, $args = array()) {
        $defaults = array(
            'headers'=>'x-api-key:'.$this->settingsobj->get_setting('api_key')
        );
        $args = wp_parse_args($args, $defaults);
        $request = wp_remote_get(WPNEWRELIC_API_BASE.$url, $args);
        //d(get_defined_vars());
        return $request;
    }
    function get_newrelic_app_id($fresh = false) {
        $appid = $this->settingsobj->get_setting('newrelic_app_id');
        if ($fresh || $appid == '') {
            //no data or a refresh is being forced
            $accountid = $this->settingsobj->get_setting('account_id');
            $url ="/accounts/$accountid/applications.xml";
            $query = $this->get_newrelic_url($url);
            //d($query);
            if ($query['response']['code'] == 200) {
                $xml = simplexml_load_string($query['body']);
                //d($xml);
                $match = false;
                foreach ($xml->application as $application) {
                    if ($application->name == $this->settingsobj->get_setting('application_id')) {
                        $appid = (string)$application->id;
                        $this->settingsobj->update_setting('actual_newrelic_app_id', $appid);
                        $this->settingsobj->save_settings();
                        $match = true;
                        break;
                    }
                }
                if (!$match) {
                    $error = new WP_Error('newrelic', 'we couldn\'t find a matching app id', $query);
                }
            } else {
                $error = new WP_Error('http', 'Error getting data from the NewRelic API', $query);
            }
        }
        if ($error) {
            throw new ErrorException($error->get_error_message());
        }
        return $appid;
    }
    function get_app_summary() {
        try {
            $accountid = $this->settingsobj->get_setting("account_id");
            $appid = $this->get_newrelic_app_id();
            if ($accountid == '' || $appid == '') {
                throw new ErrorException('account id or app id is blank, cannot request summary url');
            }
            $url = "/accounts/$accountid/applications/$appid/threshold_values.xml";
            $r = ($this->get_newrelic_url($url));
            if ($r['response']['code'] == 200) {
                $stats = new wpnewrelic_summarystats($r['body']);
            } else {
                $this->errormsg("Got ".$r['response']['code'].' from the server, could not get summary data');
            }
        } catch (Exception $e) {
            print $e->getMessage();
        }
        return $stats;
    }
    function errormsg($msg) {
        $this->settingsobj->errormsg[] = $msg;
    }

}
