<?php
function show_concept_images() {
	global $va_xxx;
	
	$data = $va_xxx->get_results('
		SELECT Name_D, Beschreibung_D, Dateiname 
		FROM Konzepte JOIN VTBL_Medium_Konzept USING (Id_Konzept) JOIN Medien USING(Id_Medium)
		WHERE Konzeptillustration', ARRAY_N);
	
	?>
	<style type="text/css">
	table, th, td {
	   border: 1px solid black;
	}
	</style>
	<?php
	
	echo '<table>';
	
	foreach ($data as $row){
		echo '<tr><td>' . $row[0] . '</td><td>' . $row[1] . '</td><td><img src="' . $row[2] . '" /></td></tr>';
	}
		
	echo '</table>';
}
?>