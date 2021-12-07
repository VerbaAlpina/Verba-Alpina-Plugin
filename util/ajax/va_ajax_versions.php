<?php

function va_ajax_versions ($db){

    global $lang;
   
	switch ($_POST['query']){

		case 'get_va_version_text':

	    $version = $_POST['version'];
	    $query = "select Id_Eintrag, Terminus_$lang as Name, Erlaeuterung_$lang as Text from glossar where Kategorie='Sonder' and Terminus_D = 'Version". esc_sql($version) ."'order by Terminus_$lang asc";
	    $data = $db->get_row($query, ARRAY_A);
	    
	    if ($data){
	        $text = $data['Text'];
	    }
	    else {
	        $text = '';
	    }
	 	 
	    parseSyntax($text, true, false, '<br />');
	    echo json_encode([
	        'text' => $text,
	        'data' => va_version_summary($version)
	    ]);

		break;

	}

}

function va_version_summary ($num){
    global $va_xxx;
	$va_xxx->select('va_xxx');
	
    $date_end = $va_xxx->get_var($va_xxx->prepare('SELECT Erstellt_Am FROM Versionen WHERE Nummer = %s', $num));
    $date_start = $va_xxx->get_var($va_xxx->prepare('SELECT Erstellt_Am FROM Versionen WHERE Nummer = %s', va_decrease_version($num)));
    
    $where = 'Erfasst_Am <= "' . $date_end . '"';
    if ($date_start){
        $where = '(' . $where . ' AND Erfasst_Am > "' . $date_start . '")';
    }
    
    if ($num == '151'){
        $where = '(' . $where . ' OR Erfasst_Am IS NULL OR Erfasst_Am = "")';
    }
    
    $sql = 'SELECT Erhebung, count(*) AS num from Aeusserungen JOIN Informanten USING (Id_Informant) WHERE Erhebung != "TEST" AND ' . $where . ' GROUP BY Erhebung ORDER BY num DESC';
    $instances = $va_xxx->get_results($sql, ARRAY_N);
    
    $sql = 'SELECT Sprache, count(*) AS num from morph_Typen WHERE Quelle = "VA" AND Sprache != "" AND ' . str_replace('Erfasst_Am', 'Angelegt_Am', $where) . ' GROUP BY Sprache ORDER BY num DESC';
    $types = $va_xxx->get_results($sql, ARRAY_N);
    
    $sql = 'SELECT Hauptkategorie, count(*) AS num from Konzepte JOIN Konzepte_Kategorien USING (Id_Kategorie) WHERE ' . str_replace('Erfasst_Am', 'Angelegt_Am', $where) . ' GROUP BY Hauptkategorie ORDER BY num DESC';
    $concepts = $va_xxx->get_results($sql, ARRAY_N);
    
    
    return ['instances' => $instances, 'types' => $types, 'concepts' => $concepts];
}

?>