<?php
class wp_newrelic_widgets {
    function __construct($parent) {
        $this->parent = $parent;
        $this->widgets = array(
            'simple_summary' => array(
                'label' => 'This Application Summary',
                'callback' => array(&$this, 'summary_widget_display'),
            ),
            'html_single_summary' => array(
                'label' => 'This Application Summary (fancy)',
                'callback' => array(&$this, 'summary_widget_display_fancy'),
            ),
            'all_app_html' => array(
                'label' => 'All Apps Summary',
                'callback' => array(&$this, 'all_apps_summary_display'),
            ),
        );
        if ($this->parent->settingsobj->setup_finished()) {
            add_action('wp_dashboard_setup', array(&$this, 'add_widget'));
        }
    }
    function add_widget() {
        wp_add_dashboard_widget(WPNEWRELIC, 'NewRelic Data', array(&$this, 'show_widget'));
    }
    function show_widget() {
        $selected = $this->parent->settingsobj->get_setting('dashboard_widget_display');
        $selected = ($selected) ? $selected : 'simple_summary';
        if ($selected) {
            $content = call_user_func($this->widgets[$selected]['callback']);
        }
        print $content;
    }
    function select_widget() {
        $return = array();
        foreach ($this->widgets as $handle => $widget) {
            $return[] = array(
                'name' => $widget['label'],
                'value' => $handle,
            );
        }
        return $return;
    }
    function summary_widget_display() {
        $html = '<table>';
        $summarydata = $this->parent->get_app_summary();
        foreach ($summarydata->items as $item => $data) {
            $th[] = $item;
            $tr[] = $data['formatted_metric_value'];
        }
        $html .= '<tr>';
        foreach ($th as $cell) {
            $html .= "<td><strong>$cell</strong></td>";
        }
        $html .= '</tr><tr>';
        foreach ($tr as $cell) {
            $html .= "<td><strong>$cell</strong></td>";
        }
        $html .= "</tr></table>";
        return $html;
    }
    function summary_widget_display_fancy() {
        $html = '';
        if ($this->parent->settingsobj->setup_finished()) {
            $html = get_transient(WPNEWRELIC.'_appsumsinghtml');
            $appid = $this->parent->get_newrelic_app_id();
            $url = "/application_dashboard?application_id=$appid";
            if (!$html) {
                $r = $this->parent->get_newrelic_url($url);
                if ($r['response']['code'] == 200) {
                    $html = str_replace("<meta content='60' http-equiv='refresh'>", '', $r['body']);
                    set_transient(WPNEWRELIC.'_appsumsinghtml', $html, 60);
                }
            }

        }
        return $html;
    }
    function all_apps_summary_display() {
        $html = '';
        if ($this->parent->settingsobj->setup_finished()) {
            $html = get_transient(WPNEWRELIC.'_appsumhtml');
            $url = "/application_dashboard";
            if (!$html) {
                $r = $this->parent->get_newrelic_url($url);
                if ($r['response']['code'] == 200) {
                    $html = str_replace("<meta content='60' http-equiv='refresh'>", '', $r['body']);
                    set_transient(WPNEWRELIC.'_appsumhtml', $html, 60);
                }
            }

        }
        return $html;
    }
    function chart_display() {
        $appid = $this->parent->get_newrelic_app_id();
        $url = "/api/v1/agents/$appid/metrics.json";
        $r = $this->parent->get_newrelic_url($url);
        d(get_defined_vars());
    }
}
