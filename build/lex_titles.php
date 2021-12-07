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
if (isset($options['db'])){
    $dbs = [$options['db']];
}
else if (isset($_REQUEST['db'])){
    $dbs = [$_REQUEST['db']];
}
else if (isset($options['all']) || isset($_REQUEST['all'])){
    $sql = 'SELECT CONCAT("va_", Nummer) FROM Versionen WHERE Website ORDER BY Nummer DESC';
    $dbs = array_merge ($dbs, $va_xxx->get_col($sql));
}

if (isset($_REQUEST['all'])){
    ?>
    <!DOCTYPE html>
    <html>
    <script type="text/javascript">
	function loadURL (){
		var req = new XMLHttpRequest();
	    var url = new URL(window.location.href);
		url.searchParams.delete("all");
		url.searchParams.set("db", dbs[0]);
		url.searchParams.set("ajax", 1);

		var contentDiv = document.getElementById("content");
		contentDiv.innerHTML += "Handling " + dbs[0] + "...<br />";
		
		req.open("GET", url);
		req.send();

	    dbs.shift();

	    req.onreadystatechange = (e) => {
		    if (req.readyState == 4){
    		    if (req.responseText != "success"){
    		    	var contentDiv = document.getElementById("content");
    				contentDiv.innerHTML += "<span style='color: red;'>Error!</span><br />";
    		    }
    		    
    	    	if (dbs.length > 0){
    	    		loadURL();
    	    	}
		    }
	    }
	}
    
    let dbs = [<?php echo implode(',', array_map(function ($e){return '"' . $e . '"';}, $dbs)); ?>];
    window.onload = function (){
    	loadURL();
    }
    
	</script>
	<body>
		<div id="content"></div>
	</body>
	</html>
    <?php
}
else {
    foreach ($dbs as $db){
        if (!isset($_REQUEST['ajax'])){
            echo 'Handling ' . $db . "...\n";
        }
        $va_xxx->select($db);
        $va_xxx->query('DELETE FROM ' . $db . '.a_lex_titles');
		if ($va_xxx->last_error){
			echo 'Deletion not possible!';
			die;
		}
        
        $titles = va_get_lex_alp_header_data(false, $va_xxx);
        va_insert_multiple($va_xxx, 'a_lex_titles', $titles, '%s,%s,%s,%s,%d,%s');
        if (isset($_REQUEST['ajax'])){
            echo 'success';
        }
    }
}