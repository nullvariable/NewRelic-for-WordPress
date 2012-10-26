<?php
/*
Plugin Name: WordPress NewRelic PHP
Plugin URI:
Description: Allows for more advanced PHP app settings easily from the WordPress interface. Uses the NewRelic API.
Author: Doug Cone
Version: 1.0
Author URI: http://www.dougcone.com
*/

include('classes/wpnewrelic_summarystats.class.php');
include('classes/wpnewrelic_widgets.class.php');
include('classes/wpnewrelic_settings.class.php');
include('classes/wpnewrelic.class.php');

//constants
define('WPNEWRELIC', 'wp-newrelic');
define('WPNEWRELIC_INSTALL_URL', 'https://newrelic.com/docs/php/new-relic-for-php');
define('WPNEWRELIC_API_BASE', 'https://api.newrelic.com');


new wpnewrelic();
