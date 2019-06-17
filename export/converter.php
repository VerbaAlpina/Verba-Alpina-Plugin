<?php
abstract class VA_Converter {
	
	/* Format
	 * 0 => Name (+ alias)
	 * 1 => true == add to toplevel, array == element hierarchy
	 * 2 => true (or missing) == add, false == do not add
	 * 3 => false (or missing) == element, string == attribute with this name
	 */
	protected static $fields = [
		[['CONCAT(External_Id, "_v", Version, SUBSTRING(DATABASE(), 3))', 'Id_Instance'], true, true, 'id'],
		['Instance', true],
		['Instance_Encoding', true],
		['Instance_Original', true],
		['Instance_Source', true],
		['Stimulus', true],
		['Id_Concept', 'concept', false],
		[['IF(Name_D != "", CONCAT(Name_D, " (", Beschreibung_D, ")"), Beschreibung_D)', 'Concept_Description'], 'concept'],
		[['IF(Konzepte.QID = 0, NULL, CONCAT("Q", Konzepte.QID))', 'QID'], 'concept'],
		['Community_Name', true],
		[['ST_AsText(ST_Envelope(Geodaten))', 'Community_Bounding_Box'], true],
		['Year_Publication', true],
		['Year_Survey', true],
		['Informant_Lang', true],
		['Id_Type', 'type', false],
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
	
	protected $data;
	
	public function __construct ($id, $db){
		global $va_xxx;
		
		$query = $this->create_query($id, $db);
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
			throw new Exception('Unknown id type: ' . $id);
		}
	
		$from_clause = 'FROM ' . $db . '.z_ling LEFT JOIN ' . $db . '.Orte ON Id_Community = Id_Ort LEFT JOIN Konzepte ON Id_Konzept = Id_Concept LEFT JOIN A_Versionen ON id = External_Id LEFT JOIN Stimuli USING (Id_Stimulus)';
		$where_clause = 'WHERE Id_Instance IN (SELECT Id_Instance FROM ' . $db . '.z_ling WHERE ' . $condition . ')';
		$appendix = 'GROUP BY Id_Instance, Id_Concept, Id_Type, Id_Base_Type ORDER BY Id_Instance ASC, Type_Kind ASC, Id_Type ASC, Id_Base_Type ASC, Id_Concept ASC';
											
		return 'SELECT ' . implode(',', array_map(function ($e){
			if (is_string($e[0])){
				return $e[0];
			}
			else {
				return $e[0][0] . ' AS ' . $e[0][1];
			}
		}, self::$fields)) . ' ' . $from_clause . ' ' . $where_clause . ' ' . $appendix;
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
		$this->url = get_site_url();
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
			
			$first = true;
			foreach (self::$fields as $field){
				$field_name = is_string($field[0])? $field[0]: $field[0][1];
				
				if ($field[1] === true){
					if ($newInstance){
						if ($field[3]){ //Attribute
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
									if ($field[3]){ //Attribute
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
					else if ($key == 'Community_Bounding_Box'){
						$this->split_bounding_box($sub_node, $doc, $val);
					}
					else if ($key == 'Community_Name'){
						$this->split_comm_name($sub_node, $doc, $val);
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
					if ($this->add_empty || ($part !== '' && $part !== null)){
						$sub_node->appendChild($doc->createElementNS($this->url, self::underscore_to_camel($key), $part));
					}
				}
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
		
		$parent->appendChild($doc->createElementNS($this->url, 'source', $data[0]));
		$parent->appendChild($doc->createElementNS($this->url, 'mapNumber', $data[1]));
		$parent->appendChild($doc->createElementNS($this->url, 'subNumber', $data[2]));
		$parent->appendChild($doc->createElementNS($this->url, 'informantNumber', $data[3]));
		$parent->appendChild($doc->createElementNS($this->url, 'locationName', $data[4]));
	}
	
	private function split_instance (DOMElement &$parent, DOMDocument &$doc, $soure_str){
		$posHashes = mb_strpos($soure_str, '###');
		
		if ($posHashes === false){
			$parent->appendChild($doc->createElementNS($this->url, 'text', $soure_str));
		}
		else {
			$parent->appendChild($doc->createElementNS($this->url, 'text', mb_substr($soure_str, 0, $posHashes)));
			$parent->appendChild($doc->createElementNS($this->url, 'partOf', mb_substr($soure_str, $posHashes + 3)));
		}
		
		
	}
	
	private function split_comm_name (DOMElement &$parent, DOMDocument &$doc, $source_str){
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
		
		$parent->appendChild($doc->createElementNS($this->url, 'officialName', $name));
		
		if ($this->add_empty || $transl){
			$tnode = $doc->createElementNS($this->url, 'translations');
			foreach ($transl as $lang => $name){
				$tsub_node = $doc->createElementNS($this->url, 'translation', $name);
				$tsub_node->setAttribute('lang', self::va_lang_to_iso($lang));			
				$tnode->appendChild($tsub_node);
			}
			$parent->appendChild($tnode);
		}
	}

	private function split_bounding_box (DOMElement $parent, DOMDocument $doc, $source_str){
		$source_str = substr($source_str, 9, -2);
		$coords = explode(',', $source_str);
		foreach ($coords as $coord){
			$point = $doc->createElementNS($this->url, 'point');

			$latlng = explode(' ', $coord);
			$point->appendChild($doc->createElementNS($this->url, 'latitude', $latlng[1]));
			$point->appendChild($doc->createElementNS($this->url, 'longitude', $latlng[0]));
			
			$parent->appendChild($point);
		}
	}
}