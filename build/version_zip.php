<?php

$options = getopt('', ['format:', 'db:']);
   
define('SHORTINIT', true);

require(dirname(__FILE__) . '/../../../../wp-load.php');

global $va_xxx;
$va_xxx = new wpdb('root', '', $options['db'], 'localhost:3311');

include_once dirname(__FILE__) . '/../../../../wp-includes/default-constants.php';

wp_plugin_directory_constants();

define ('VA_PLUGIN_PATH', WP_PLUGIN_DIR . '/verba-alpina');
define ('VA_PLUGIN_URL', 'https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/plugins/verba-alpina/'); //Needed in xml file for schema location

include_once dirname(__FILE__) . '/../export/api.php';
include_once dirname(__FILE__) . '/../export/converter.php';

$_REQUEST['format'] = $options['format'];
$ids = va_get_api_db_results(false, false, false);

$version = substr($options['db'], 3);

foreach ($ids as $id_arr){
    $id = $id_arr[0];
    ob_start();
    $fileName = va_api_get_record($id, $version, $options['format'], false, false);
    $htmlStr = ob_get_contents();
    ob_end_clean();
    file_put_contents('D:/results/' . $version . '/' . $options['format'] . '/' . $fileName, $htmlStr);
    echo 'Handled ' . $fileName . ' ...' . "\n";
}