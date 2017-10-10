<?php
function show_bib_page (){
	global $va_xxx;
	
	$bib = $va_xxx->get_results('
			SELECT Autor, Titel, Jahr, Ort, Download_URL, Band, Enthalten_In, Seiten, Verlag, Abkuerzung
			FROM Bibliographie 
			WHERE Oeffentlich
			ORDER BY Abkuerzung ASC', ARRAY_N);
	
	echo '<div class="entry-content"><ul>';
	foreach ($bib as $b){
		echo '<li><b>' . $b[9] . '</b> = ' . va_format_bibliography($b[0], $b[1], $b[2], $b[3], $b[4], $b[5], $b[6], $b[7], $b[8], false) . '</li>';
	}
	echo '</ul></div>';
	
}
?>