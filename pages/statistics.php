<?php
function va_statistics (){
	echo '<div class="entry-content"><h1>Belege pro Version</h1>';
	
	global $va_xxx;
	$versions = $va_xxx->get_col('SELECT Nummer FROM Versionen WHERE Website ORDER BY Nummer');
	
	echo '<table class="easy-table easy-table-default"><tr><th>Version</th><th>Neue Belege</th><th>Geänderte Belege</th><th>Unveränderte Belege</th><th>Gesamt</th>';
	foreach ($versions as $version){
		$va_xxx->select('va_' . $version);
		
		$total = $va_xxx->get_var('SELECT count(DISTINCT Id_Instance) FROM z_ling');
		$version_data = $va_xxx->get_row('
			SELECT 
				sum(IF(Changed AND Version = 1, 1, 0)) AS new,
				sum(IF(Changed AND Version > 1, 1, 0)) AS changed,
				sum(IF(!Changed, 1, 0)) AS old
			FROM A_Versionen 
			WHERE Id LIKE "S%" OR Id LIKE "G%"', ARRAY_A);
		
		echo "<tr><td>$version</td><td>{$version_data['new']}</td><td>{$version_data['changed']}</td><td>{$version_data['old']}</td><td>$total</td></tr>";
	}
	echo '</table>';
	
	echo '</div>';
}