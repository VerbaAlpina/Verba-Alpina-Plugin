<?php 

$options = getopt('', ['all']);

define('SHORTINIT', true);

require(dirname(__FILE__) . '/../../../../wp-load.php');

include_once dirname(__FILE__) . '/../../../../wp-includes/l10n.php';
include_once dirname(__FILE__) . '/../util/tools.php';
include_once dirname(__FILE__) . '/../../../../wp-includes/default-constants.php';

wp_plugin_directory_constants();

$login_data = file(dirname(__FILE__) . '/../login', FILE_IGNORE_NEW_LINES);
list($dbuser, $dbpassw, $dbhost) = va_get_db_creds($login_data);

$va_xxx = new wpdb($dbuser, $dbpassw, 'va_xxx', $dbhost);

$dbs = ['va_xxx'];
if (isset($options['all']) || isset($_REQUEST['all'])){
    $sql = 'SELECT CONCAT("va_", Nummer) FROM Versionen WHERE Website';
    $dbs = array_merge ($dbs, $va_xxx->get_col($sql));
}

foreach ($dbs as $db){
    echo 'Handling ' . $db . "...\n";
    $va_xxx->select($db);
    $va_xxx->query('DELETE FROM a_lex_titles');
    
    $titles = va_get_lex_alp_header_data(false, $va_xxx);
    va_insert_multiple($va_xxx, 'a_lex_titles', $titles, '%s,%d,%s,%s,%s,%d,%s');
}