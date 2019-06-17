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
		$types = ['C', 'L', 'A', 'S'];
		
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
			return [
			'select' => 'SELECT DISTINCT CONCAT("L", Id_Type) AS Id',
			'where_full' => ' WHERE Id_Type IS NOT NULL AND Type_Kind = "L" AND Source_Typing = "VA" ORDER BY Id_Type',
			'where_id' => ' WHERE Type_Kind = "L" AND Id_Type = %',
			'name' => ', va_xxx.lex_unique(Type, Type_Lang, Gender) AS Name',
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
		default:
			return [
			'select' => 'SELECT DISTINCT External_Id AS Id',
			'where_full' => ' WHERE Instance != "" OR Type IS NOT NULL ORDER BY Id_Instance',
			'where_id' => ' WHERE External_Id = "%" AND (Instance != "" OR Type IS NOT NULL)',
			'name' => ', Id_Instance, IF(Instance = "", CONCAT("T:", Type), CONCAT("A:", Instance)) AS Record, Instance_Source',
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
		
		return [$arr[0], $res];
		
	}, $records);
}

function va_handle_api_call (){
		
	if (!isset($_REQUEST['action'])){
		va_api_error('No action given!');
	}
	
	global $va_xxx;
	if (isset($_REQUEST['version'])){
		$version = $_REQUEST['version'];
		
		//Check if version exits:
		if (!$va_xxx->get_var($va_xxx->prepare('SELECT Nummer FROM Versionen WHERE Website AND Nummer = %d', $version))){
			
			$versions = $va_xxx->get_col('SELECT Nummer FROM Versionen WHERE Website');
			$versions = array_map(function ($e){ return '<li>' . $e . '</li>';}, $versions);
			
			va_api_error('Invalid version number! Existing version numbers are: <ul>' . implode("\n", $versions) . '</ul>');
		}
	}
	else {
		$version = $va_xxx->get_var('SELECT MAX(Nummer) FROM Versionen');
	}
	
	$va_xxx->select('va_' . $version);
	
	if ($_REQUEST['action'] == 'getIds' || $_REQUEST['action'] == 'getName' || $_REQUEST['action'] == 'getNames'){

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
		else {
			$records = va_get_api_db_results(false, true, $changed);
		}
		

		if ($_REQUEST['action'] == 'getName'){
			echo $records[0][1];
			
			header('Content-type: text/txt; charset=utf-8');
		}
		else {
			$out = fopen('php://output', 'w');
			foreach ($records as $row){
				fputcsv($out, $row);
			}
			fclose($out);
			
			header('Content-type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename=list_' . $version . '.csv');
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
		
		try {
			switch ($format){
				case 'xml':
					$conv = new VA_XML_Converter($_REQUEST['id'], 'va_' . $version);
					break;
					
				case 'csv':
					$conv = new VA_CSV_Converter($_REQUEST['id'], 'va_' . $version);
					break;
					
				case 'json':
					$conv = new VA_JSON_Converter($_REQUEST['id'], 'va_' . $version);
					break;
					
				default:
					va_api_error('Format "' . $format . '" not supported!');
			}
			
			echo $conv->export($empty);
		}
		catch (ErrorException $e){
			va_api_error($e->getMessage());
		}
		
		$record_version = $va_xxx->get_var($va_xxx->prepare('SELECT Version FROM A_Versionen WHERE Id = %s', $_REQUEST['id']));
		
		if(!$record_version){
			va_api_error('Error in the version table!');
		}
		
		header('Content-Type: ' . $conv->get_mime() . '; charset=utf-8');
		header('Content-Disposition: attachment; filename=' . $_REQUEST['id'] . '_v' . $record_version . '_' . $version . '.' . $conv->get_extension());
	}
	else {
		va_api_error('Unknown action "' . $_REQUEST['action'] . '"!');
	}
}