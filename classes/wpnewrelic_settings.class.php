<?php
class wpnewrelic_settings {
    public $settings = array();
    public $updated;
    function __construct($parentobj) {
        $this->updated = false;
        $this->parent = $parentobj;
        add_action('admin_menu', array(&$this, 'admin_menu_hook'));
        add_action(WPNEWRELIC.'-settings-after', array(&$this, 'settings_app_summary'));
        //add_action(WPNEWRELIC.'-settings-after', array(&$this, 'test_query'));//temp action to run tests on the settings page with


        $this->settings = array(
            'application_id' => array(
                'value' => get_bloginfo('name'),
                'label' =>  __('NewRelic Application ID', WPNEWRELIC),
                'type' => 'text',
                'help' => 'Enter the name of your application as you would like it displayed in the NewRelic control panel.',
                'required' => true,
            ),
            'enable_newrelic_errors' => array(
                'value' => 1,
                'label' => __('Enable NewRelic Error Logging', WPNEWRELIC),
                'type' => 'checkbox',
                'help' => 'This setting will inject the WordPress Error logging system so you can see these errors in your NewRelic dashboard.',
            ),
            'ignore_cron' => array(
                'value' => 1,
                'label' => __('Ignore cron jobs in reporting', WPNEWRELIC),
                'type' => 'checkbox',
                'help' => 'fires <code>newrelic_ignore_transaction()</code> when the WordPress Cron script is spun up.',
            ),
            'account_id' => array(
                'value' => '',
                'label' =>  __('NewRelic Account ID', WPNEWRELIC),
                'type' => 'text',
                'help' => 'found at the end of your account settings url, example: <code>https://rpm.newrelic.com/accounts/xxxxxx</code>',
                'required' => true,
            ),
            'api_key' => array(
                'value' => '',
                'label' =>  __('NewRelic API Key', WPNEWRELIC),
                'type' => 'text',
                'help' => 'To find your api key, login to your NewRelic account and Select (account name) > Account Settings > Integrations > Data Sharing > API Access',
                'required' => true,
            ),
            'actual_newrelic_app_id' => array(
                'value' => '',
                'type' => 'internal',
            ),
            'dashboard_widget_display' => array(
                'value' => '',
                'label' => __('Dashboard Widget Display', WPNEWRELIC),
                'type' => 'select',
                'callback' => array(&$this, 'widgets'),
                'mapped_field' => false,
                'help' => 'select what view the dashboard widget should show.'
            ),
        );
        $this->refresh_settings();
    }
    function widgets() {
        return $this->parent->widgets->select_widget();
    }
    function refresh_settings() {
        $db = get_option(WPNEWRELIC, array());
        foreach ($db as $handle => $settings) {
            if ($settings['value'] != $this->settings[$handle]['value']) {
                $this->update_setting($handle, $settings['value']);
            }
        }
    }
    function update_setting($setting, $value) {
        $this->settings[$setting]['value'] = $value;
    }
    function save_settings() {
        update_option(WPNEWRELIC, $this->settings);
    }
    function get_setting($setting) {
        $return = null;
        if (isset($this->settings[$setting]['value'])) {
            $return = $this->settings[$setting]['value'];
        }
        return $return;
    }
    function admin_menu_hook() {
        $this->wp_settings_page = $page = add_options_page(__("WordPress New Relic Integration"), 'WPNewRelic', 'manage_options', WPNEWRELIC, array(&$this, 'settings_page'));
        add_action( "load-$page", array(&$this, 'update_settings'), 15 );
    }
    function render_field($name, $data) {
        //$name = WPNEWRELIC."_$name";
        switch ($data['type']) {
            case 'select':
                if (!isset($data['callback'])) { throw new ErrorException('oops, there\'s no call back for this field'); }
                $items = call_user_func($data['callback']);
                //d($items);
                print $this->make_a_label($name, $data['label']);
                print '<select id="'.$name.'" name="'.$name.'">';
                print '<option value="" '.selected($data['value'], '', false).'></option>';
                foreach ($items as $item) {
                    print '<option value="'.$item['value'].'" '.selected($data['value'], $item['value'], false).'>'.$item['name'].'</option>';
                }
                print '</select><br /><em>'.__($data['help'], WPNEWRELIC).'</em>';
                break;
            case 'text' :
                print $this->make_a_label($name, $data['label']);
                print '<input type="text" id="'.$name.'" name="'.$name.'" value="'.$data['value'].'" />';
                print '<br /><em>'.__($data['help'], WPNEWRELIC).'</em>';
                break;
            case 'textarea' :
                print $this->make_a_label($name, $data['label']);
                wp_editor(stripslashes($data['value']), $name, array('textarea_rows'=>4, 'tinymce'=>false, 'media_buttons'=>false));
                print '<br /><em>'.__($data['help'], WPNEWRELIC).'</em>';
                break;
            case 'section_header' :
                print '<div class="gmapstnv_section_header">';
                _e($data['value'], WPNEWRELIC);
                print '</div>';
                break;
            case 'checkbox' :
                print $this->make_a_label($name, $data['label']);
                print '<input type="checkbox" id="'.$name.'" name="'.$name.'" '.checked($data['value'], 1, false).' value="1" />';
                print '<br /><em>'.__($data['help'], WPNEWRELIC).'</em>';
                break;
        }
    }

    /**
     * formats and returns html for a form label tag
     * @param $name string, html form element
     * @param $label string, label text
     * @return string, html
     */
    function make_a_label($name, $label) {
        return '<label for="'.$name.'">'.$label.'</label>&nbsp;&nbsp;&nbsp;';
    }

    function settings_page() {
        do_action(WPNEWRELIC.'-presettings');
        /*if (!extension_loaded("newrelic")) {
            $warning = __("NewRelic PHP extentions not found. Learn how to install them at: <a href=\"".WPNEWRELIC_INSTALL_URL."\">".WPNEWRELIC_INSTALL_URL."</a>", WPNEWRELIC);
        }*/
        ?>
        <div class="wrap" id="custom-background">
        <style>label { font-weight: bold;}</style>
        <?php //screen_icon(); ?>
        <h2><?php _e('WordPress NewRelic Integration', WPNEWRELIC);?></h2>
        <form action="" method="post">
            <?php wp_nonce_field(WPNEWRELIC, WPNEWRELIC);
            if ( isset($this->updated) && $this->updated == true ) { ?>
                <div id="message" class="updated">
                    <p><?php
                        if (empty($this->updated_message)) {
                            _e( 'Settings saved.', WPNEWRELIC);
                        } else {
                            _e($this->updated_message, WPNEWRELIC);
                            unset($this->updated_message);
                        } ?></p>
                </div>
                <?php
                unset($this->updated);
            }
            if ( isset($warning) || isset($this->errormsg) ) {
                if (isset($this->errormsg)) {
                    foreach ($this->errormsg as $msg) {
                        $warning .= '<br />'.$msg;
                    }
                }
                ?>
                <div id="message" class="error">
                    <p><?php print $warning;unset($warning); ?></p>
                </div>
                <?php
            }
            do_action(WPNEWRELIC.'-settings-before');

            foreach ($this->settings as $setting => $meta) {
                $this->render_field($setting, $meta);
                print '<br />';
            }

            do_action(WPNEWRELIC.'-settings-after');

            ?>
            <br /><br /><input name="Submit" type="submit" value="<?php _e('Save Changes'); ?>" />
        </form>
            </div><?php
    }
    function update_settings() {
        if (isset($_POST[WPNEWRELIC])) {
            check_admin_referer(WPNEWRELIC, WPNEWRELIC);
            //d($_POST);
            //iterate through our expected settings and update them if need be.
            foreach ($this->settings as $handle => $setting) {
                if (isset($_POST[$handle])) {
                    if ($setting['value'] != $_POST[$handle]) {
                        $this->update_setting($handle, $_POST[$handle]);
                        $this->updated = true;
                    }
                } else {
                    if (isset($setting['value']) && $setting['value'] != '') {
                        $this->update_setting($handle, '');
                        $this->updated = true;
                    }
                }
            }
            if ($this->updated) {
                $this->save_settings();//store to db
            }
            do_action(WPNEWRELIC.'-settings-update');
        }
    }
    function settings_app_summary() {
        $appid = $this->get_setting('application_id');
        $apikey = $this->get_setting('api_key');
        $accountid = $this->get_setting('account_id');
        if ($appid != '' && $apikey != '' && $accountid != '') {
            print '<h3>'.__("Application Summary", WPNEWRELIC).'</h3>';
            print $this->parent->widgets->summary_widget_display();
        }
    }
    function test_query() {
        try {
            $h = $this->parent->widgets->chart_display();
            print $h;
            d(get_defined_vars());
        } catch (Exception $e) {
            print $e->getMessage();
        }
    }
    function setup_finished() {
        foreach ($this->settings as $handle => $setting) {
            if (isset($setting['required']) && $setting['required'] == true) {
                if ($setting['value'] == '') { return false; }
            }
        }
        return true;
    }
}