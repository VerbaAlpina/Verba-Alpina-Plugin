<?php
function va_api_error($str){
	echo $str;
	
	echo '<br /><br />';
	
	echo 'Please visit the <a href="https://www.verba-alpina.gwi.uni-muenchen.de/?page_id=8844">API information page</a>!';

	exit;
}

function va_get_api_db_results ($id, $use_name, $only_changed = false){
	$queries = [];
	
	if ($id){
		$parts = va_get_api_query_parts($id[0]);
		
		if ($id[0] != 'S' && $id[0] != 'G'){
			$id = substr($id, 1);
		}
		
		$queries[] = [$parts['select'] . $parts['name'] . ' FROM z_ling ' . $parts['join'] . str_replace('%', $id, $parts['where_id']), $parts['function']]; 
	}
	else {
		$all_types = ['C', 'L', 'A', 'S'];
		if (isset($_REQUEST['class'])){
			if (!in_array($_REQUEST['class'], $all_types)){
				va_api_error('Unknown class ' . $_REQUEST['class']);
			}
			$types = [$_REQUEST['class']];
		}
		else {
			$types = $all_types;
		}
		
		foreach ($types as $type){
			$parts = va_get_api_query_parts($type);
			
			if ($use_name){
				$query = $parts['select'] . $parts['name'] . ' FROM z_ling' . $parts['join'] . $parts['where_full'];
				$queries[] = [$query, $parts['function']];
			}
			else {
				$query = $parts['select'] . ' FROM z_ling' . $parts['where_full'];
				$queries[] = [$query, null];
			}
		}
	}
	
	
	global $va_xxx;
	$data = [];
	foreach ($queries as $query){
		$res = $va_xxx->get_results($query[0], ARRAY_N);
		
		if ($query[1]){
			$res = call_user_func($query[1], $res);
		}
		
		$data = array_merge($data, $res);
	}
	
	if (!$id && $only_changed){
		$changed = va_two_dim_to_assoc($va_xxx->get_results('SELECT Id, Changed FROM A_Versionen', ARRAY_N));
		$data = array_filter($data, function ($e) use (&$changed){
			if(!isset($changed[$e[0]])){
				va_api_error('Error in the version table!');
			}
			return $changed[$e[0]];
		});
	}
	
	if ($use_name){
		$data = array_map(function ($row){
			return [$row[0], trim($row[1])];
		}, $data);
	}
	
	if (empty($data)){
		va_api_error('No data for this query!');
	}
	
	return $data;
}

function va_get_api_query_parts ($type){
	switch ($type){
		case 'C':
			return [
			'select' => 'SELECT DISTINCT CONCAT("C", Id_Concept) AS Id',
			'where_full' => ' WHERE Id_Concept IS NOT NULL ORDER BY Id_Concept',
			'where_id' => ' WHERE Id_Concept = %',
			'name' => ', IF(Name_D != "", CONCAT(Name_D, " (", Beschreibung_D, ")"), Beschreibung_D) AS Name',
			'join' => ' JOIN Konzepte ON Id_Concept = Id_Konzept',
			'function' => null
					];
		case 'L':
			$allowed_tlangs = ['gem', 'roa', 'sla'];
			if (isset($_REQUEST['type_lang']) && !in_array($_REQUEST['type_lang'], $allowed_tlangs)){
				va_api_error('Unknown type language: ' . $_REQUEST['type_lang'] . '!');
			}
		
			return [
			'select' => 'SELECT DISTINCT CONCAT("L", Id_Type) AS Id',
			'where_full' => ' WHERE Id_Type IS NOT NULL AND Type_Kind = "L" AND Source_Typing = "VA"' . (isset($_REQUEST['type_lang'])? ' AND Type_Lang = "' . $_REQUEST['type_lang'] . '"': '') . ' ORDER BY Id_Type',
			'where_id' => ' WHERE Type_Kind = "L" AND Id_Type = %',
			'name' => ', va_xxx.lex_unique(Type, ' . (isset($_REQUEST['type_lang'])? '""': 'Type_Lang') . ', Gender) AS Name',
			'join' => '',
			'function' => null
					];
		case 'A':
			return [
			'select' => 'SELECT DISTINCT CONCAT("A", Id_Community) AS Id',
			'where_full' => ' WHERE Id_Community IS NOT NULL ORDER BY Id_Community',
			'where_id' => ' WHERE Id_Community = %',
			'name' => ', Community_Name AS Name',
			'join' => '',
			'function' => null
		];
		
		case 'B':
			return [
			'select' => 'SELECT DISTINCT CONCAT("B", Id_Base_Type) AS Id',
			'where_full' => ' WHERE Id_Base_Type IS NOT NULL ORDER BY Id_Base_Type',
			'where_id' => ' WHERE Id_Base_Type = %',
			'name' => ', Orth AS Name',
			'join' => '',
			'function' => null
		];
		
		default:
			return [
			'select' => 'SELECT External_Id AS Id',
			'where_full' => ' WHERE Instance != "" OR Source_Typing != "VA" OR Source_Typing IS NULL GROUP BY Id_Instance, External_Id ORDER BY Id_Instance',
			'where_id' => ' WHERE (Instance != "" OR Source_Typing != "VA" OR Source_Typing IS NULL) AND External_Id = "%" GROUP BY Id_Instance, External_Id',
			'name' => ', Id_Instance, IF(Instance = "", CONCAT("T:", GROUP_CONCAT(DISTINCT Type SEPARATOR " ")), CONCAT("A:", GROUP_CONCAT(DISTINCT Instance))) AS Record, Instance_Source',
			'join' => '',
			'function' => 'va_api_format_record_names'
		];
	}
}

function va_api_format_record_names ($records){
	return array_map(function ($arr){
		$name = $arr[2];
		
		$res = mb_substr($name, 0, 1) == 'A'? 'Attestation ': 'Type ';
		
		$res .= strip_tags(mb_substr($name, 2));
		
		$indexFull = mb_strpos($res, '###');
		if ($indexFull){
			$res = mb_substr($res, 0, $indexFull) . ' (part of ' . mb_substr($res, $indexFull + 3) . ')';
		}
		
		$sparts = explode('#', $arr[3]);
		
		$res .= ', Source: ' . $sparts[0] . ' ' . $sparts[1] . '#' . $sparts[2] . ' ' . $sparts[3] . ' (' . $sparts[4] . ')';
		
		return [$arr[0], html_entity_decode($res)];
		
	}, $records);
}

function va_handle_api_call (){
		
	if (!isset($_REQUEST['action'])){
		va_api_error('No action given!');
	}
	
	global $va_xxx;
	if (isset($_REQUEST['version'])){
		$version = $_REQUEST['version'];
		
		if ($version == 'latest'){
			$version = $va_xxx->get_var('SELECT MAX(Nummer) FROM va_xxx.Versionen');
		}
		else {
		
			//Check if version exists:
			if (!$va_xxx->get_var($va_xxx->prepare('SELECT Nummer FROM Versionen WHERE Website AND Nummer = %d', $version))){
				
				$versions = $va_xxx->get_col('SELECT Nummer FROM Versionen WHERE Website');
				$versions = array_map(function ($e){ return '<li>' . $e . '</li>';}, $versions);
				
				va_api_error('Invalid version number! Existing version numbers are: <ul>' . implode("\n", $versions) . '</ul>');
			}
		}
	}
	else {
		$version = $va_xxx->get_var('SELECT MAX(Nummer) FROM Versionen');
	}
	
	$va_xxx->select('va_' . $version);
	
	if ($_REQUEST['action'] == 'getIds' || $_REQUEST['action'] == 'getName' || $_REQUEST['action'] == 'getNames' || $_REQUEST['action'] == 'getTextList'){

		if (!isset($_REQUEST['changed'])){
			$changed = false;
		}
		else {
			$changed = $_REQUEST['changed'] === '1';
		}
		
		if ($_REQUEST['action'] == 'getIds'){
			$records = va_get_api_db_results(false, false, $changed);
		}
		else if ($_REQUEST['action'] == 'getName'){
			if (!isset($_REQUEST['id'])){
				va_api_error('No id given!');
			}
			
			$records = va_get_api_db_results($_REQUEST['id'], true);
		}
		else if ($_REQUEST['action'] == 'getTextList'){
			$records = va_get_api_text_list();
		}
		else {
			$records = va_get_api_db_results(false, true, $changed);
		}
		

		if ($_REQUEST['action'] == 'getName'){
			header('Content-type: text/txt; charset=utf-8');
			echo $records[0][1];
		}
		else if ($_REQUEST['action'] == 'getTextList' && isset($_REQUEST['format']) && $_REQUEST['format'] == 'xml'){
			header('Content-type: text/xml; charset=utf-8');
			header('Content-Disposition: attachment; filename=list_' . $version . '.xml');
			
			echo va_api_text_list_to_xml($records);
		}
		else if ($_REQUEST['action'] == 'getTextList' && isset($_REQUEST['format']) && $_REQUEST['format'] == 'json'){
			header('Content-type: text/json; charset=utf-8');
			header('Content-Disposition: attachment; filename=list_' . $version . '.json');
			
			echo va_api_text_list_to_json($records);
		}
		else {
			header('Content-type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename=list_' . $version . '.csv');
			
			$out = fopen('php://output', 'w');
			foreach ($records as $row){
				fputcsv($out, $row);
			}
			fclose($out);
		
		}
	}
	else if ($_REQUEST['action'] == 'getRecord') {
		$empty = !isset($_REQUEST['empty']) || $_REQUEST['empty'];
		
		if (!isset($_REQUEST['id'])){
			va_api_error('No id given!');
		}
		
		if (!isset($_REQUEST['format'])){
			$format = 'csv';
		}
		else {
			$format = $_REQUEST['format'];
		}
		
        va_api_get_record($_REQUEST['id'], $version, $format, $empty);
	}
	else if ($_REQUEST['action'] == 'getText') {
	    
	    if (!isset($_REQUEST['id'])){
	        va_api_error('No id given!');
	    }
	    
	    if (!isset($_REQUEST['format'])){
	        $format = 'html';
	    }
	    else {
	        $format = $_REQUEST['format'];
	    }
	    

	    try {
	        switch ($format){
	            case 'html':
	                $conv = new VA_HTML_TextConverter($_REQUEST['id'], 'va_' . $version);
	                break;
	                
	            default:
	                va_api_error('Format "' . $format . '" not supported!');
	        }
	        
	        $res = $conv->export();
	    
    	    header('Content-Type: ' . $conv->get_mime() . '; charset=utf-8');
    	    header('Content-Disposition: attachment; filename=text_' . $_REQUEST['id'] . '_v' . $version . '.' . $conv->get_extension());
    	    
    	    echo $res;
	    }
	    catch (ErrorException $e){
	        va_api_error($e->getMessage());
	    }
	}
	else {
		va_api_error('Unknown action "' . $_REQUEST['action'] . '"!');
	}
}

function va_api_get_record ($id, $version, $format, $empty, $send_header = true){
    global $va_xxx;
    
	$id = str_replace(' ', '+', $id);
	
    try {
		$res = '';
		$prefix = $id[0];
		foreach (explode('+', substr($id, 1)) as $sid){
			
			$sid = $prefix . $sid;
			switch ($format){
				case 'xml':
					$conv = new VA_XML_Converter($sid, 'va_' . $version);
					break;
					
				case 'csv':
					$conv = new VA_CSV_Converter($sid, 'va_' . $version);
					break;
					
				case 'json':
					$conv = new VA_JSON_Converter($sid, 'va_' . $version);
					break;
					
				default:
					va_api_error('Format "' . $format . '" not supported!');
			}
			
			if ($prefix != 'B'){
				$record_version = $va_xxx->get_var($va_xxx->prepare('SELECT Version FROM A_Versionen WHERE Id = %s', $sid));
				
				if(!$record_version){
					va_api_error('Error in the version table!');
				}
			}
			
			$res .= $conv->export($empty);
		}
        
        if ($send_header){
            header('Content-Type: ' . $conv->get_mime() . '; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $id . ($id[0] != 'B'? ('_v' . $record_version) : '') . '_' . $version . '.' . $conv->get_extension());
        }
        
        echo $res;
        return $id . '_v' . $record_version . '_' . $version . '.' . $conv->get_extension();
    }
    catch (ErrorException $e){
        va_api_error($e->getMessage());
    }
}

function va_get_api_text_list (){
	global $va_xxx;
	
	//Lexicon
	
	$lex = $va_xxx->get_results('SELECT Id, Sprache, "PLACEHOLDER",
			GROUP_CONCAT(IF(Aufgabe = "auct", CONCAT(Name, ",", Vorname), NULL) SEPARATOR ";"), 
			GROUP_CONCAT(IF(Aufgabe = "trad", CONCAT(Name, ",", Vorname), NULL) SEPARATOR ";"),
			GROUP_CONCAT(IF(Aufgabe = "corr", CONCAT(Name, ",", Vorname), NULL) SEPARATOR ";")
		FROM im_comments 
			JOIN VTBL_kommentar_autor ON ID_Kommentar = id AND SUBSTRING(Language, 1, 1) = Sprache
			JOIN Personen USING (Kuerzel)
		WHERE Id != "" AND comment != "" AND SUBSTRING(Id, 1, 1) IN ("B", "L", "C")
		GROUP BY Id, Language
		ORDER BY Id ASC, Language ASC', ARRAY_N);
		
	foreach ($lex as $i => $row){
		$lex[$i][2] = strip_tags(va_get_comment_title($row[0], $row[1]));
	}
		
	//Methodologie
	$sql = 'SELECT CONCAT("M", Id_Eintrag), Sprache, Titel, a, t, c FROM ((';
	$first = true;
	foreach (va_get_lang_array() as $lang){
		if ($first){
			$first = false;
		}
		else {
			$sql .= ' UNION (';
		}
		$sql .= "SELECT Id_Eintrag, '$lang' AS Sprache, Terminus_$lang AS Titel,
				GROUP_CONCAT(IF(Aufgabe = 'auct', CONCAT(Name, ',', Vorname), NULL) SEPARATOR ';') a, 
				GROUP_CONCAT(IF(Aufgabe = 'trad', CONCAT(Name, ',', Vorname), NULL) SEPARATOR ';') t,
				GROUP_CONCAT(IF(Aufgabe = 'corr', CONCAT(Name, ',', Vorname), NULL) SEPARATOR ';') c
			FROM glossar
				JOIN VTBL_eintrag_autor USING (Id_Eintrag)
				JOIN Personen USING (Kuerzel)
			WHERE Sprache = '$lang' AND Terminus_$lang != '' AND Erlaeuterung_D != '' AND Intern = '0' AND Fertig
			GROUP BY Id_Eintrag)";
	}
	$sql .= ') e ORDER BY Id_Eintrag, Sprache ASC';
	
	$meth = $va_xxx->get_results($sql, ARRAY_N);
	
	//Beiträge
	$re = 'va/([a-z]{2}/)?\?p=([0-9]+)';
	$bib_entries = $va_xxx->get_results($va_xxx->prepare('SELECT Download_URL FROM bibliographie WHERE VA_Publikation = "1" AND Download_URL regexp %s', $re), ARRAY_A);
	
	$posts = [];
	foreach ($bib_entries as $be){
		$res = [];
		 preg_replace_callback('#' . $re . '#', function ($match) use (&$res){
			$res = [$match[2], $match[1]? substr($match[1], 0, 2): ''];
		}, $be['Download_URL']);
		
		
		if ($res[1]){
			$va_lang = strtoupper(substr($res[1], 0, 1));
			$blog_id = va_blog_id_from_lang($va_lang);
			switch_to_blog($blog_id);
		}
		else {
			$va_lang = 'D';
		}
		
		
		//Versions need NOT to be checked here, since only posts that are already listed in the bib table in this version are used (the revision id is only needed if the text content is returned)
		$title = html_entity_decode(get_the_title($res[0]));
		$rows = get_field('autoren_neu', $res[0]);
		$authors = [];
		foreach($rows as $row) {
			$authors[] = $row['nachname'] . ',' . $row['vorname'];
		}
		
		if ($res[1]){
			restore_current_blog();
		}
		
		$posts[] = ['P' . $res[0], $va_lang, $title, implode(';', $authors), '', ''];
	}

	$res = array_merge($lex, $meth, $posts);
	
	$orcid_mapping = [];
	
	$res = array_map(function ($e) use (&$orcid_mapping){ return [$e[0] . '_' . va_lang_to_iso($e[1]), $e[2], va_add_orcids($e[3], $orcid_mapping), va_add_orcids($e[4], $orcid_mapping), va_add_orcids($e[5], $orcid_mapping)];}, $res);
	
	return $res;
}


function va_add_orcids ($names, &$map){
	
	$res = [];
	
	foreach (explode(';', $names) as $name){ 
		if (!array_key_exists($name, $map)){
			global $va_xxx;
			$n = explode(',', $name);
			$map[$name] = $va_xxx->get_var($va_xxx->prepare('SELECT orcid FROM va_xxx.personen WHERE Name = %s AND Vorname = %s', $n[0], $n[1]));
		}
		$orcid = $map[$name];
		
		$res[] = $name . ($orcid? ' (' . $orcid . ')': '');
		error_log(json_encode($map));
	}
	
	return implode(';', $res);
}

function va_api_text_list_to_xml ($records){
	$doc = new DOMDocument('1.0', 'UTF-8');
	
	$elements = $doc->createElement('elements');
	
	foreach ($records as $record){
		$element = $doc->createElement('element');
		$element->setAttribute('id', $record[0]);
		
		$title = $doc->createElement('title');
		$title->nodeValue = $record[1];
		$element->appendChild($title);
		
		va_add_person_xml($record[2], $element, 'author', $doc);
		va_add_person_xml($record[3], $element, 'translator', $doc);
		va_add_person_xml($record[4], $element, 'proofreader', $doc);
		
		$elements->appendChild($element);
	}
		
	$doc->appendChild($elements);
	$doc->formatOutput = true;
		
	return $doc->saveXML();
}

function va_api_text_list_to_json ($records){
	$res = [];
	
	foreach ($records as $record){
		$res[] = [
			'ID' => $record[0],
			'title' => $record[1],
			'authors' => va_add_person_json($record[2]),
			'translators' => va_add_person_json($record[3]),
			'proofreaders' => va_add_person_json($record[4])
		];
	}
	
	return json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function va_add_person_json ($str){
	if ($str){
		$res = [];
		
		$data = explode(';', $str);
		foreach ($data as $row){
			$pos_br = mb_strpos($row, ' (');
			if ($pos_br === false){
				$name = $row;
				$orcid = false;
			}
			else {
				$name = mb_substr($row, 0, $pos_br);
				$orcid = mb_substr($row, $pos_br + 2, -1);
			}
			
			$nameParts = explode(',', $name);
			$newEntry = [
				'givenName' => $nameParts[1],
				'familyName' => $nameParts[0]
			];
			
			if ($orcid){
				$newEntry['orcid'] = $orcid;
			}
			$res[] = $newEntry;
		}
		
		return $res;
	}
	
	return [];
}

function va_add_person_xml ($str, &$parent, $type, &$doc){
	$persons = $doc->createElement($type . 's');
	
	if ($str){
		$data = explode(';', $str);
		foreach ($data as $row){
			$person = $doc->createElement($type);
			
			$pos_br = mb_strpos($row, ' (');
			if ($pos_br === false){
				$name = $row;
				$orcid = false;
			}
			else {
				$name = mb_substr($row, 0, $pos_br);
				$orcid = mb_substr($row, $pos_br + 2, -1);
			}
			
			$nameParts = explode(',', $name);
			$pre = $doc->createElement('givenName');
			$pre->nodeValue = $nameParts[1];
			$sur = $doc->createElement('familyName');
			$sur->nodeValue = $nameParts[0];
			
			$person->appendChild($pre);
			$person->appendChild($sur);
			
			if ($orcid){
				$person->setAttribute('orcid', $orcid);
			}
			$persons->appendChild($person);
		}
	}
	
	$parent->appendChild($persons);
}