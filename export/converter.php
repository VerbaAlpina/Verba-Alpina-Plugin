<?php
abstract class VA_Converter {
	
	/* Format
	 * 0 => Name (+ alias)
	 * 1 => true == add to toplevel, array == element hierarchy
	 * 2 => true (or missing) == add, false == do not add
	 * 3 => false (or missing) == element, string == attribute with this name
	 */
	protected static $fields = [
		[['CONCAT(External_Id, "_v", vrecord.Version, SUBSTRING(DATABASE(), 3))', 'Id_Instance'], true, true, 'id'],
		['Instance', true],
		['Instance_Encoding', true],
		['Instance_Original', true],
		['Instance_Source', true],
		[['IF(Stimulus = "", "empty", Stimulus)', 'Stimulus'], true],
		[['CONCAT("C", Id_Concept, "_v", vconcept.Version, SUBSTRING(DATABASE(), 3))', 'Id_Concept'], 'concept', true, 'id'],
		[['IF(Name_D != "", CONCAT(Name_D, " (", Beschreibung_D, ")"), Beschreibung_D)', 'Concept_Description'], 'concept'],
		[['IF(Konzepte.QID = 0, NULL, CONCAT("Q", Konzepte.QID))', 'QID'], 'concept'],
		[['CONCAT("A", Id_Community, "_v", vcommunity.Version, SUBSTRING(DATABASE(), 3))', 'Id_Community'], ['community'], true, 'id'],
		['Community_Name', 'community'],
		[['ST_AsText(ST_Envelope(Geodaten))', 'Community_Bounding_Box'], 'community'],
		['Geonames_Id', 'community'],
		['Year_Publication', true],
		['Year_Survey', true],
		['Informant_Lang', true],
		[['IF(vtype.Version IS NOT NULL, CONCAT("L", Id_Type, "_v", vtype.Version, SUBSTRING(DATABASE(), 3)), NULL)', 'Id_Type'], 'type', true, 'id'],
		[['Type', 'Type_Name'], 'type'],
		['Type_Kind', 'type', true, 'kind'],
		['Type_Lang', 'type'],
		['Source_Typing', 'type'],
		['POS', 'type'],
		['Affix', 'type'],
		['Gender', 'type'],
		['Id_Base_Type', ['type', 'base_Type'], false],
		[['Base_Type', 'Base_Type_Name'], ['type', 'base_Type']],
		['Base_Type_Lang', ['type', 'base_Type']],
		['Base_Type_Unsure', ['type', 'base_Type']]
	];
	
	protected static $one_element_categories = ['community'];
	
	protected $data;
	
	public function __construct ($id, $db){
		global $va_xxx;
		
		$query = $this->create_query($id, $db);
		//va_query_log($query);
		$va_xxx->select($db);
		$this->data = $va_xxx->get_results($query, ARRAY_A);
		
		if (empty($this->data)){
			throw new ErrorException('No data for this id!');
		}
	}
	
	private function create_query ($id, $db){
		
		if ($id[0] == 'C'){
			$condition = 'Id_Concept = ' . substr($id, 1);
		}
		else if ($id[0] == 'A'){
			$condition = 'Id_Community = ' . substr($id, 1);
		}
		else if ($id[0] == 'L'){
			$condition = 'Id_Type = ' . substr($id, 1) . ' AND Type_Kind = "L" AND Source_Typing = "VA"';
		}
		else if ($id[0] == 'S' || $id[0] == 'G'){
			$condition = 'External_Id = "' . $id  . '"';
		}
		else {
			throw new ErrorException('Unknown id type: ' . $id);
		}
	
		$from_clause = 'FROM (SELECT * FROM ' . $db . '.z_ling WHERE Id_Instance IN (SELECT Id_Instance FROM ' . $db . '.z_ling WHERE ' . $condition . ') GROUP BY Id_Instance, Id_Concept, Id_Type, Id_Base_Type) x
			LEFT JOIN ' . $db . '.Orte ON Id_Community = Id_Ort 
			LEFT JOIN ' . $db . '.Konzepte ON Id_Konzept = Id_Concept 
			LEFT JOIN ' . $db . '.A_Versionen vrecord ON vrecord.id = External_Id
			LEFT JOIN ' . $db . '.A_Versionen vconcept ON vconcept.id = CONCAT("C", Id_Concept)
			LEFT JOIN ' . $db . '.A_Versionen vcommunity ON vcommunity.id = CONCAT("A", Id_Community)
			LEFT JOIN ' . $db . '.A_Versionen vtype ON vtype.id = CONCAT("L", Id_Type) 
			LEFT JOIN ' . $db . '.Stimuli USING (Id_Stimulus)';
		$appendix = 'ORDER BY Id_Instance ASC, Type_Kind ASC, Id_Type ASC, Id_Base_Type ASC, Id_Concept ASC';
											
		return 'SELECT ' . implode(',', array_map(function ($e){
			if (is_string($e[0])){
				return $e[0];
			}
			else {
				return $e[0][0] . ' AS ' . $e[0][1];
			}
		}, self::$fields)) . ' ' . $from_clause  . ' ' . $appendix;
	}
	
	public abstract function export();
	
	public abstract function get_extension();
	
	public abstract function get_mime();
	
	protected static function va_lang_to_iso ($lang){
		switch ($lang){
			case 'D': return 'deu';
			case 'F': return 'fra';
			case 'I': return 'ita';
			case 'S': return 'slv';
			case 'R': return 'roh';
			case 'L': return 'lld';
			case 'E': return 'eng';
		}
	}
	
	public static function random_examples ($sub_class, $db, $num){
		$zip_file = get_home_path() . 'wp-content/uploads/examples.zip';
		$zip = new ZipArchive();
		
		$zip->open($zip_file, ZipArchive::CREATE);
		
		$ids = self::get_random_ids($db, $num);
		foreach ($ids as $id){
			$conv = new $sub_class($id, $db);
			$zip->addFromString($id . '.' . $conv->get_extension(), $conv->export(false));
		}
		
		$zip->close();
	}
	
	private static function get_random_ids ($db, $num){
		global $va_xxx;
		
		$va_xxx->select($db);
		
		$concepts = $va_xxx->get_col('SELECT DISTINCT Id_Concept FROM z_ling WHERE Id_Concept IS NOT NULL');
		$types = $va_xxx->get_col('SELECT DISTINCT Id_Type FROM z_ling WHERE Type_Kind = "L"');
		$communities = $va_xxx->get_col('SELECT DISTINCT Id_Community FROM z_ling WHERE Id_Community IS NOT NULL');
		$instances = $va_xxx->get_col('SELECT DISTINCT External_Id FROM z_ling WHERE External_Id IS NOT NULL');
		
		$res = [];
		
		$i = 0;
		while ($i < $num){
			$rand1 = rand(0, 3);
			
			if ($rand1 == 0){
				$rand2 = rand(0, count($concepts) - 1);
				$id = 'C' . $concepts[$rand2];
			}
			else if ($rand1 == 1){
				$rand2 = rand(0, count($types) - 1);
				$id = 'L' . $types[$rand2];
			}
			else if ($rand1 == 2){
				$rand2 = rand(0, count($communities) - 1);
				$id = 'A' . $communities[$rand2];
			}
			else {
				$rand2 = rand(0, count($instances) - 1);
				$id = $instances[$rand2];
			}
			
			if (in_array($id, $res))
				continue;
			
			$res[] = $id;
		
			$i++;
		}
		
		return $res;
	}
}

class VA_CSV_Converter extends VA_Converter {
	
	public function __construct ($id, $db){
		parent::__construct($id, $db);
	}
	
	public function get_extension (){
		return 'csv';
	}
	
	public function get_mime (){
		return 'text/csv';
	}
	
	public function export (){
		ob_start();
		$out = fopen('php://output', 'w');
		
		$field_names = [];
		foreach (self::$fields as $field){
			if (is_array($field[0])){
				$field_names[] = $field[0][1];
			}
			else {
				$field_names[] = $field[0];
			}
		}
		fputcsv($out, $field_names);
		
		foreach ($this->data as $row){
			fputcsv($out, $row);
		}
		fclose($out);
		
		$res = ob_get_contents();
		ob_end_clean();
		
		return $res;
	}
}

class VA_JSON_Converter extends VA_Converter {
	
	public function __construct ($id, $db){
		parent::__construct($id, $db);
	}
	
	public function get_extension (){
		return 'json';
	}
	
	public function get_mime (){
		return 'application/json';
	}
	
	public function export (){
		return json_encode($this->data);
	}
}

class VA_XML_Converter extends VA_Converter {
	private $add_empty;
	private $url;
	
	public function __construct ($id, $db){
		$this->url = 'https://www.verba-alpina.gwi.uni-muenchen.de'; //get_site_url(); Use constant here to make it possible to create the dumps locally
		parent::__construct($id, $db);
	}
	
	public function get_extension (){
		return 'xml';
	}
	
	public function get_mime (){
		return 'text/xml';
	}
	
	public function export ($add_empty = true, $validate = true){
		
		$this->add_empty = $add_empty;
		
		$doc = new DOMDocument('1.0', 'UTF-8');
		
		$instances = $doc->createElementNS($this->url, 'instances');
		$instances->setAttributeNS('http://www.w3.org/2000/xmlns/', 'xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
		$instances->setAttribute('xsi:schemaLocation', $this->url . ' ' . VA_PLUGIN_URL . '/schemas/VerbaAlpina.xsd');
		
		$current_id = NULL;
		$current_data = ['attributes' => []];
		
		foreach ($this->data as $row){

			$row_sub_ids = [];
			$newInstance = false;
			
			if ($row['Id_Instance'] != $current_id){
				if ($current_id != NULL){
					$instances->appendChild($this->create_row($doc, $current_id, $current_data));
				}
				
				$current_id = $row['Id_Instance'];
				$current_data = ['attributes' => []];
				$newInstance = true;
			}
			
			foreach (self::$fields as $field){
				$field_name = is_string($field[0])? $field[0]: $field[0][1];
				$row[$field_name] = trim(html_entity_decode(preg_replace('/[[:cntrl:]]/', '', $row[$field_name])));
				
				if ($field[1] === true){
					if ($newInstance){
					    if (isset($field[3]) && $field[3]){ //Attribute
							$current_data['attributes'][$field[3]] = $row[$field_name];
						}
						else {
							$current_data[$field_name] = $row[$field_name];
						}
					}
				}
				else {
					$hierch = $field[1];
					if (!is_array($hierch)){
						$hierch = [$field[1]];
					}
					
					$depth = count($hierch);
					$arr = &$current_data;
					foreach ($hierch as $index => $hname){
						if (!isset($row_sub_ids[$hname])){
							$row_sub_ids[$hname] = $row[$field_name];
						}
						
						if (!isset($arr[$hname])){
							$arr[$hname] = [];
						}
						
						if ($row_sub_ids[$hname]){
							if (!isset($arr[$hname][$row_sub_ids[$hname]])){
								$arr[$hname][$row_sub_ids[$hname]] = ['attributes' => []];
							}
							
							if ($index == $depth - 1){
								if (!isset($field[2]) || $field[2]){
								    if (isset($field[3]) && $field[3]){ //Attribute
										$arr[$hname][$row_sub_ids[$hname]]['attributes'][$field[3]] = $row[$field_name];
									}
									else {
										$arr[$hname][$row_sub_ids[$hname]][$field_name] = $row[$field_name];
									}
								}
							}
							else {
								$arr = &$arr[$hname][$row_sub_ids[$hname]];
							}
						}
					}
				}
			}
		}
		
		$instances->appendChild($this->create_row($doc, $current_id, $current_data));
		
		$doc->appendChild($instances);
		
		if ($validate){
			libxml_use_internal_errors(true);

			if (!$doc->schemaValidate(VA_PLUGIN_PATH . '/schemas/VerbaAlpina.xsd')){
				$msg = 'XML could not be validated:';
				$errrors = libxml_get_errors();
				foreach ($errrors as $errror){
					$msg .= "<br />" . $errror->message;
				}
				throw new ErrorException($msg);
			}
		}
		
		$doc->formatOutput = true;
		
		return $doc->saveXML();
	}
	
	private function create_row (DOMDocument &$doc, $id, $data){
		$instance = $doc->createElementNS($this->url, 'instance');
		
		foreach ($data['attributes'] as $aname => $aval){
			$instance->setAttribute($aname, $aval);
		}
		unset($data['attributes']);
		
		foreach ($data as $key => $val){
			if (is_array($val)){
				if ($this->add_empty || count($val) > 0){
					$instance->appendChild($this->element_from_array($doc, $key, $val));		
				}					
			}
			else {
				if ($this->add_empty || ($val !== '' && $val !== null)){
					$sub_node = $doc->createElementNS($this->url, self::underscore_to_camel($key));
					if ($key == 'Instance_Source'){
						$this->split_source($sub_node, $doc, $val);
					}
					else if ($key == 'Instance'){
						$this->split_instance($sub_node, $doc, $val);
					}
					else {
						$sub_node->nodeValue = $val;
					}
					$instance->appendChild($sub_node);
				}
			}
		}
		
		return $instance;
	}
	
	
	private function element_from_array (DOMDocument &$doc, $name, $arr){

		$node = $doc->createElementNS($this->url, self::underscore_to_camel($name . 's'));

		foreach ($arr as $element){
			$sub_node = $doc->createElementNS($this->url, self::underscore_to_camel($name));
			
			foreach ($element['attributes'] as $aname => $aval){
				$sub_node->setAttribute($aname, $aval);
			}
			unset($element['attributes']);
			
			foreach ($element as $key => $part){
				if (is_array($part)){
					if ($this->add_empty || count($part) > 0){
						$sub_node->appendChild($this->element_from_array($doc, $key, $part));
					}
				}
				else {
					if ($key == 'Community_Bounding_Box'){
						$this->split_bounding_box($sub_node, $doc, $part);
					}
					else if ($key == 'Community_Name'){
						$this->split_comm_name($sub_node, $doc, $part);
					}
					else if ($this->add_empty || ($part !== '' && $part !== null)){
						$this->append_text_element($sub_node, $doc, self::underscore_to_camel($key), $part);
					}
				}
			}
			
			//No ...s parent tag needed
			if (in_array($name, VA_Converter::$one_element_categories)){
				return $sub_node;
			}
			
			$node->appendChild($sub_node);
		}
		return $node;
	}
	
	private static function underscore_to_camel ($str){
		$str = strtolower($str);
		
		return preg_replace_callback('/\_([a-zA-Z])/', function ($match){
			return strtoupper($match[1]);
		}, $str);
	}
	
	private function split_source (DOMElement &$parent, DOMDocument &$doc, $soure_str){
		$data = explode('#', $soure_str);
		
		$this->append_text_element($parent, $doc, 'source', $data[0]);
		$this->append_text_element($parent, $doc, 'mapNumber', $data[1]);
		$this->append_text_element($parent, $doc, 'subNumber', $data[2]);
		$this->append_text_element($parent, $doc, 'informantNumber', $data[3]);
		$this->append_text_element($parent, $doc, 'locationName', $data[4]);
	}
	
	private function split_instance (DOMElement &$parent, DOMDocument &$doc, $source_str){
		$posHashes = mb_strpos($source_str, '###');
		
		if ($posHashes === false){
			$this->append_text_element($parent, $doc, 'text', $source_str);
		}
		else {
			$this->append_text_element($parent, $doc, 'text', mb_substr($source_str, 0, $posHashes));
			$this->append_text_element($parent, $doc, 'partOf', mb_substr($source_str, $posHashes + 3));
		}
		
		
	}
	
	private function append_text_element (DOMElement &$parent, DOMDocument &$doc, $name, $str, $attributes = []){
		//Used since the createElmentNS with a third parameter does not escape e.g. ?
		$textNode = $doc->createTextNode($str);
		$elementNode = $doc->createElementNS($this->url, $name);
		$elementNode->appendChild($textNode);
		
		foreach ($attributes as $key => $val){
			$elementNode->setAttribute($key, $val);
		}
		
		$parent->appendChild($elementNode);
	}
	
	private function split_comm_name (DOMElement &$parent, DOMDocument &$doc, $source_str){
		
		$node = $doc->createElementNS($this->url, 'communityName');
		
		$index = mb_strpos($source_str, '###');
		
		$transl = [];
		if ($index === false){
			$name = $source_str;
		}
		else {
			$name = mb_substr($source_str, 0, $index);
			$translation_string = mb_substr($source_str, $index + 3);
			foreach (explode('###', $translation_string) as $ct){
				$parts = explode(':', $ct);
				$transl[$parts[0]] = $parts[1];
			}
		}
		
		$this->append_text_element($node, $doc, 'officialName', $name);
		
		if ($this->add_empty || $transl){
			$tnode = $doc->createElementNS($this->url, 'translations');
			foreach ($transl as $lang => $tname){
				$this->append_text_element($tnode, $doc, 'translation', $tname, ['lang' => self::va_lang_to_iso($lang)]);
			}
			$node->appendChild($tnode);
		}
		
		$parent->appendChild($node);
	}

	private function split_bounding_box (DOMElement $parent, DOMDocument $doc, $source_str){
		
		$node = $doc->createElementNS($this->url, 'communityBoundingBox');
		
		$source_str = substr($source_str, 9, -2);
		$coords = explode(',', $source_str);
		foreach ($coords as $coord){
			$point = $doc->createElementNS($this->url, 'point');

			$latlng = explode(' ', $coord);
			$this->append_text_element($point, $doc, 'latitude', $latlng[1]);
			$this->append_text_element($point, $doc, 'longitude', $latlng[0]);
			
			$node->appendChild($point);
		}
		
		$parent->appendChild($node);
	}
}