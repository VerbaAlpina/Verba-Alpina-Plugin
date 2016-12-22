<?php
	//TODO SQL injection
	function load_va_data (){
		global $Ue;
		$result = new IM_Result();

		$db = IM_Initializer::$instance->database;

		$lang = strtoupper(substr($_POST['lang'], 0, 1));
		
		switch ($_POST['category']){
			case 0: //Informanten
				$query = "SELECT Ortsname, Nummer, AsText(" . ($_POST['community'] == 'true' ? '(SELECT Mittelpunkt FROM Orte WHERE Id_Ort = Id_Gemeinde)' : 'Georeferenz') . ") as Geo, Id_Informant FROM Informanten WHERE Erhebung = '" . substr($_POST['key'], 1) . "'" . ($_POST['outside'] == 'false' ? ' and Alpenkonvention' : '');
				$data = $db->get_results($query, ARRAY_A);
				
				foreach ($data as $row){
					$result->addMapElement(-1, new IM_SimpleElementInfoWindowData($row['Ortsname'], $row['Nummer']), $row['Geo'], va_get_quantify_data_informant($row['Id_Informant'], $db));
				}
			break;
			
			case 1: //Concept
				if(empty($_POST['filter']['conceptIds']))
					break;
				$id_string = implode(',', $_POST['filter']['conceptIds']);
				$where_clause = "Id_Concept IN ($id_string)";
				va_create_result_object($where_clause, $lang, $result, $Ue, $db);
			break;
			
			case 2: //Phonetic Type
			case 3: //Morphologic Type
				$where_clause = "Id_Instance IN (SELECT DISTINCT Id_Instance FROM Z_Ling WHERE Type_Kind = '" . $_POST['key'][0] . "' AND Id_Type = " . substr($_POST['key'], 1) . ')';
				va_create_result_object($where_clause, $lang, $result, $Ue, $db);
			break;
			
			case 4: //Base Type
				$where_clause = "Id_Instance IN (SELECT DISTINCT Id_Instance FROM Z_Ling WHERE Id_Base_Type = " . substr($_POST['key'], 1) . ')';
				va_create_result_object($where_clause, $lang, $result, $Ue, $db);				
			break;
			
			case 5: //Extralinguistic
				//Community flag is ignored, since it does not make sense for most of the extralinguistic data.
				$query = "
					SELECT Name, Description, astext(Geo_Data), Tags
					FROM Z_Geo
					WHERE Id_Category = " . substr($_POST['key'], 1) . (!isset($_POST['outside']) || $_POST['outside'] == 'false' ? ' and Alpine_Convention' : '') . '
					ORDER BY Id_Geo ASC';
				$data = $db->get_results($query, ARRAY_N);
				
				if(isset($_POST['filter']) && $_POST['filter']['subElementCategory'] == -1){ //Pseudo category
					$subCategoryId = 0;
					foreach ($data as $row){
						$result->addMapElement('?' . $subCategoryId++, new IM_SimpleElementInfoWindowData(va_translate_content($row[0], $Ue), va_translate_content($row[1], $Ue)), $row[2]);
					}
				}
				else {
					if(isset($_POST['filter']['tags'])){
						$tagsNeeded = $_POST['filter']['tags'];
						
						foreach ($data as $row){
							$useRecord = true;
							
							if($row[3] != NULL){
								$tagArray = json_decode($row[3], true);
								foreach ($tagArray as $tagName => $tagValue){
									if(!in_array($tagValue, $tagsNeeded[$tagName])){
										$useRecord = false;
										break;
									}
								}
							}
							if($useRecord){
								$subVal = -1;
								if(isset($_POST['filter']['subElementCategory']) && $_POST['filter']['subElementCategory'] == -3 && isset($tagArray[$_POST['filter']['selectedTag']])){
									$subVal = "#" . $tagArray[$_POST['filter']['selectedTag']];
								}
								$result->addMapElement($subVal, new IM_SimpleElementInfoWindowData(va_translate_content($row[0], $Ue), va_translate_content($row[1], $Ue)), $row[2]);
							}
						}
					}
					else {
						foreach ($data as $row){
							$result->addMapElement(-1, new IM_SimpleElementInfoWindowData(va_translate_content($row[0], $Ue), va_translate_content($row[1], $Ue)), $row[2]);
						}
					}
				}
			break;
		}
		return $result;
	}

function va_translate_content ($text, &$Ue){
	if(isset($Ue[$text])){
		return $Ue[$text];
	}
	
	$text = preg_replace_callback('/Ue\[([^\]]*)\]/', function ($matches) use (&$Ue){
		if(isset($Ue[$matches[1]])){
			return $Ue[$matches[1]];
		}
		return $matches[1];
	}, $text);
	return $text;
}

function va_create_result_object ($where_clause, $lang, IM_Result &$result, &$Ue, &$db){
	
	$t = microtime(true);$times[] = array('Start: ', date("h:i:s") . sprintf(" %06d",($t - floor($t)) * 1000000));
	$query = va_create_record_query($where_clause);

	$dbresult = $db->get_results($query, ARRAY_N);
	
	$t = microtime(true);$times[] = array('SQL: ', date("h:i:s") . sprintf(" %06d",($t - floor($t)) * 1000000));
	
	$subElementType = $_POST['filter']['subElementCategory'];
	
	if($subElementType == 1){ //Concept
		$concept_mapping = va_build_concept_mapping($where_clause, $db);			
	}
	
	$t = microtime(true);$times[] = array('ConceptMapping: ', date("h:i:s") . sprintf(" %06d",($t - floor($t)) * 1000000));
	
	//$dt = $db = $dc = $dtt = 0;
	
	$sub_list = array(); //Stores all sub ids
	foreach ($dbresult as $row){
		$va_sub = '-1'; //The group for the selected record
		$current_array = array($row[0]); //Beleg
		
		$typifications = explode('-+-', $row[1]);
		$type_array = array();
		if($typifications[0] !== ''){
			foreach ($typifications as $t){
				$type_info = mb_split('#', $t);
				$kind = $type_info[0];
				$id = $type_info[1];
				if($kind == 'P')
					$type = $type_info[2];
				else
					$type = va_format_lex_type($type_info[2], $type_info[3], $type_info[4], $type_info[5], $type_info[6]); //util/tools.php
				$source = $type_info[7];
				$ref = $type_info[8];
				
				//For multiple references
				$type_exists = false;
				foreach ($type_array as &$type_descr){
					if($type_descr[0] == $kind && $type_descr[1] == $type && $type_descr[2] == $source){
						$type_descr[3] .= '%%%' . $ref;
						$type_exists = true;
						break;
					}
				}
				if(!$type_exists)
					$type_array[] = array ($kind, $type, $source, $ref);
				
				if($source == 'VA'){
					if($subElementType == 3) { //Lex. Typ
						if(($kind == 'L')){
							$va_sub = 'L' . $id;
						}
					}
					else if ($subElementType == 2) { //Phon. Typ
						if($kind == 'P'){
							$va_sub = 'P'. $id;
						}
					}
				}
				else if ($row[0] == '' && strpos($row[4], $source) === 0){
					$current_array[0] = 'TYP' . $type;
				}
			}
		}
		
		$base_array = array();
		if($row[2] != ''){
			$base_types = explode('-+-', $row[2]);
			foreach ($base_types as $b){
				$posHash1 = mb_strpos($b, '#');
				$posHash2 = mb_strpos($b, '#', $posHash1 + 1);
				
				$id_btyp = mb_substr($b, 0, $posHash1);
				if($posHash2 === false){
					$btyp = mb_substr($b, $posHash1 + 1);
					$base_array[] = array ($btyp);
				}
				else {
					$btyp = mb_substr($b, $posHash1 + 1, $posHash2 - $posHash1 - 1);
					$etymon = mb_substr($b, $posHash2 + 1);
					$base_array[] = array ($btyp, $etymon);
				}
				if($subElementType == 4) { //Basistyp
					if($va_sub === '-1'){
						$va_sub = 'B' . $id_btyp;
					}
					else {
						$va_sub .= '+B' . $id_btyp; //Concatenate multiple base type ids with plus
					}
				}
			}
		}
		
		$conceptArray = $row[3]? explode(',', $row[3]): array();
		if($subElementType == 1 && !empty($conceptArray)){ //Concept
			//TODO unterschiedliche TL-Konzepte
			$va_sub = 'C' . $concept_mapping[$conceptArray[0]];
		}
		
		$current_array[] = va_create_type_table($type_array, $base_array, $lang, $row[4], $Ue);
		
		$current_array[] = $conceptArray;
		
		$current_array[] = $row[4]; //source
		
		$current_array[] = $row[6]; //community
		
		$result->addMapElement($va_sub, new IM_RecordInfoWindowData($current_array), $row[5], va_get_quantify_data_informant($row[7], $db));
	}
}

function va_get_quantify_data_informant ($id_informant, &$db){
	$res = array();
			
	if(va_version_newer_than('va_161')){
		$dbdata = $db->get_results($db->prepare('SELECT Id_Kategorie, AIndex FROM A_Informant_Polygon WHERE Id_Informant = %d AND ' . ($_POST['outside'] == 'false' ? 'Alpenkonvention' : 'NOT Alpenkonvention'), $id_informant), ARRAY_A);
		
		foreach ($dbdata as $row){
			$res['E' . $row['Id_Kategorie']] = $row['AIndex'];
		}
	}
	
	return $res;
}

function va_build_concept_mapping ($where_clause, &$db){
	//Pre-compute used concepts according to the following convention:
	//	- In principle the top-level concept is used
	//	- If all records connected to a certain top-level concept also belong to a "lower" concept, that one is used
	
	$top_level_concept_list = array();
	$id_list = $db->get_results('SELECT DISTINCT Id_Concept, conceptDepth(Id_Concept) FROM Z_Ling WHERE Id_Concept IS NOT NULL AND ' . $where_clause, ARRAY_N);
	
	//Find top-level concepts for all concepts
	foreach ($id_list as $cid){
		$top_level_concept = $db->get_var('SELECT a.Id_Ueberkonzept FROM A_Ueberkonzepte_Erweitert a JOIN Ueberkonzepte u ON a.Id_Ueberkonzept = u.Id_Konzept WHERE u.Id_Ueberkonzept = 707 AND a.Id_Konzept = ' . $cid[0], 0, 0);
		if(!$top_level_concept) //Concept itself is top-level
			$top_level_concept = $cid[0];
		if(isset($top_level_concept_list[$top_level_concept])){
			$top_level_concept_list[$top_level_concept][] = $cid;
		}
		else {
			$top_level_concept_list[$top_level_concept] = array($cid);
		}
	}
	
	//"Downgrade" concepts
	$concept_mapping = array(); 
	foreach ($top_level_concept_list as $clist){
		if(count($clist) == 1){
			$concept_mapping['C' . $clist[0][0]] = $clist[0][0]; //Use concept itself
		}
		else {
			//Find highest concept
			$min_level = 9999;
			$min_concept = 0;
			$multiple_concepts = false;
			foreach ($clist as $centry){
				$cid = $centry[0];
				$level = $centry[1];				
				if($level == $min_level && $cid != $min_concept){
					$multiple_concepts = true;
				}
				else if ($level < $min_level){
					$min_level = $level;
					$min_concept = $cid;
					$multiple_concepts = false;
				}
			}

			if($multiple_concepts){ //Use next-higher concept
				$lowest_possible = $db->get_var('SELECT Id_Ueberkonzept FROM Ueberkonzepte WHERE Id_Konzept = ' . $min_concept, 0, 0);
			}
			else { //Use concept itself
				$lowest_possible = $min_concept;
			}
			
			foreach ($clist as $centry){
				$concept_mapping['C' . $centry[0]] = $lowest_possible;
			}
		}
	}
	return $concept_mapping;
}

function va_create_record_query ($where_clause){
	return "SELECT
						Instance,
						GROUP_CONCAT(DISTINCT CONCAT(Type_Kind, '#', Id_Type, '#', Type, '#', Type_Lang, '#', POS, '#', Gender, '#', Affix, '#', Source_Typing, '#', IF(Type_Reference IS NULL, '', Type_Reference)) SEPARATOR '-+-') AS Typings,
						GROUP_CONCAT(DISTINCT CONCAT(Id_Base_Type, '#', Base_Type, IF(Etymon IS NULL, '', CONCAT('#', Etymon))) SEPARATOR '-+-') AS Base_Types,
						GROUP_CONCAT(DISTINCT CONCAT('C', Id_Concept)) AS Concepts,
						Instance_Source,
						" . ($_POST['community'] == 'true' ? 'Community_Center' : 'Geo_Data') . " AS Geo_Data,
						Community_Name,
						Id_Informant
					FROM Z_Ling
					WHERE " . $where_clause 
							. ($_POST['outside'] == 'false' ? ' AND Alpine_Convention' : '') . "
					GROUP BY Id_Instance";
}

function va_create_type_table (&$types, &$btypes, $lang, $source, &$Ue){

	$result = '<table class="easy-table easy-table-default">';
	
	$va_phon_index = false;
	$va_lex_index = false;
	$source_phon_index = false;
	$source_lex_index = false;
	
	$phon_indexes = array();
	$morph_indexes = array();
	
	//Look for VA-Typings and Source-Typings
	foreach ($types as $index => $type){
		if($type[0] == 'P'){
			if($type[2] == 'VA'){
				$va_phon_index = $index;
			}
			else if(mb_strpos($source, $type[2]) === 0) {
				$source_phon_index = $index;
			}
			$phon_indexes[] = $index;
		}
		else {
			if($type[2] == 'VA'){
				$va_lex_index = $index;
			}
			else if(mb_strpos($source, $type[2]) === 0) {
				$source_lex_index = $index;
			}
			$morph_indexes[] = $index;
		}
	}
	
	//Phonetic types
	if($source_lex_index === false)
		$result .= va_get_type_table_row ($Ue['PHON_TYP'], $Ue['NICHT_TYPISIERT'], $va_phon_index, $source_phon_index, $phon_indexes, $types, $Ue['QUELLE']);
	
	//Morphologic types
	$result .= va_get_type_table_row ($Ue['MORPH_TYP'], $Ue['NICHT_TYPISIERT'], $va_lex_index, $source_lex_index, $morph_indexes, $types, $Ue['QUELLE']);
	
	//Base types
	foreach ($btypes as $btype){
		$result .= '<tr><td>' . $Ue['BASISTYP'] . '</td><td>' . $btype[0]  . (isset($btype[1])? ' (' . $btype[1] . ')' : '') . '</td><td>VA</td></tr>';
	}
		
	return $result . '</table>';
}

function va_get_type_table_row ($name, $empty, $va_index, $source_index, $indexes, &$types, $sourceStr){
	$result = '';
	
	if(count($indexes) == 0){
		$result .= '<tr><td>' . $name . '</td><td>' . $empty . '</td><td>VA</td></tr>';
	}
	else {
		$count_rest = count($indexes);
			
		//Source typing (if exists)
		if($source_index !== false){
			$result .= '<tr><td>' . $name . '</td><td class="atlasSourceB">' . $types[$source_index][1] . '</td><td class="atlasSource">' . $sourceStr . '</td></tr>';
			$count_rest--;
			array_splice($indexes, $source_index, 1);
		}
		
		//Rest typings
		if($count_rest > 0){
			
			if($source_index === false){
				$result .= '<tr><td>' . $name . '</td>';
			}
			else {
				$result .= '<tr><td></td>';
			}
			
			if($count_rest == 1){
				$tname = $types[$indexes[0]][1];
				if($indexes[0] == $va_index){
					$tname = add_references($tname, $types[$va_index][3]);
				}
				$result .= '<td>' . $tname . '</td><td>' . $types[$indexes[0]][2] . '</td>';
			}
			else {
				$type_name_td = $va_index !== false? add_references($types[$va_index][1], $types[$va_index][3]) : $types[$indexes[0]][1];
				$result .= '<td>' . $type_name_td . '</td><td><select class="infoWindowTypeSelect">';
				if($va_index !== false){
					array_splice($indexes, $va_index, 1);
					$result .= '<option value="' . $types[$va_index][1] . '" data-tname="' . add_references($types[$va_index][1], $types[$va_index][3]) . '" selected>VA</option>';
				}
				foreach ($indexes as $index){
					$result .= '<option value="' . $types[$index][1] . '" data-tname="' . $types[$index][1] .  '">' . $types[$index][2] . '</option>';
				}
				$result .= '</select></td>';
			}
			$result .= '</tr>';
		}
	}
	return $result;
}

function add_references ($str, $refs){
	if($refs != ''){
		$ref_data = explode('%%%', $refs);
		
		foreach ($ref_data as $ref){
			$data = explode('|', $ref);
			if($data[0] !== 'VA'){
				if($data[3]){
					$str .= '<a title="' . $data[0] . ': ' . $data[1] . ' ' . $data[2] . '" href="' . $data[3] . '" target="_BLANK" class="encyLink">' . substr($data[0], 0, 1) . '</a>';
				}
				else {
					$str .= '<span title="' . $data[0] . ': ' . $data[1] . ' ' . $data[2] . '" class="encyLink">' . substr($data[0], 0, 1) . '</span>';
				}
			}
		}
	}
	return $str;
}

/*
	 * The result has the following format:
	 * [
	 * 	0 => [<sub id || 0> => 
	 * 					[
	 * 						0 => <record>,
	 * 						1 => <type table html>,
	 * 						2 => [concept id],
	 * 						3 => <source>,
	 * 						4 => <geo data>,
	 * 						5 => <community name>
	 * 					]
	 * 		]
	 * 	1 => [id => [lang => comment]]
	 * 	]
	 */
class IM_RecordInfoWindowData extends IM_ElementInfoWindowData {
	private $data;
	
	function __construct ($arr){
		parent::__construct('record');
		$this->data = array (
			'record' => $arr[0],
			'typeTable' => $arr[1], 
			'concepts' => $arr[2],
			'source' => $arr[3],
			'community' => $arr[4]
		);
	}
	
	protected function getTypeSpecificData (){
	 	return $this->data;
	}
}
?>