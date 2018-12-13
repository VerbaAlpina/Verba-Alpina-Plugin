<?php
abstract class VA_Converter {
	
	/* Format
	 * 0 => Name (+ alias)
	 * 1 => true == add to toplevel, array == element hierarchy
	 * 2 => true (or missing) == add, false == do not add
	 * 3 => false (or missing) == element, string == attribute with this name
	 */
	protected static $fields = [
		[['External_Id', 'Id_Instance'], true, true, 'id'],
		['Instance', true],
		['Instance_Encoding', true],
		['Instance_Original', true],
		['Instance_Source', true],
		['Id_Concept', 'concept', false],
		[['IF(Name_D != "", CONCAT(Name_D, " (", Beschreibung_D, ")"), Beschreibung_D)', 'Concept_Description'], 'concept'],
		[['CONCAT("Q", Konzepte.QID)', 'QID'], 'concept'],
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
		['Base_Type_Unsure', ['type', 'base_Type']],
	];
	
	protected $data;
	
	public function __construct ($type, $id, $db){
		global $va_xxx;
		
		$query = $this->create_query($type, $id, $db);
		$va_xxx->select($db);
		$this->data = $va_xxx->get_results($query, ARRAY_A);
	}
	
	private function create_query ($type, $id, $db){
		
		if ($type == 'C'){
			$condition = 'Id_Concept = ' . $id;
		}
		else if ($type == 'A'){
			$condition = 'Id_Community = ' . $id;
		}
		else if ($type == 'L'){
			$condition = 'Id_Type = ' . $id . ' AND Type_Kind = "L"';
		}
		else if ($type == 'I'){
			$condition = 'External_Id = "' . $id  . '"';
		}
		else {
			throw new Exception('Unknown type: ' . $type);
		}
	
		$from_clause = 'FROM ' . $db . '.z_ling JOIN ' . $db . '.Orte ON Id_Community = Id_Ort JOIN Konzepte ON Id_Konzept = Id_Concept';
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
}

class VA_XML_Converter extends VA_Converter {
	private $add_empty;
	
	public function __construct ($type, $id, $db){
		parent::__construct($type, $id, $db);
	}
	
	public function export ($add_empty = true){
		
		$this->add_empty = $add_empty;
		
		$xw = new XMLWriter();
		$xw->openMemory ();
		$xw->setIndent(true);
		$xw->setIndentString("\t");
		
		$xw->startDocument('1.0', 'UTF-8');
		
		$xw->text("\n");
		
		$xw->startElement('instances');
		
		$current_id = NULL;
		$current_data = ['attributes' => []];
		
		foreach ($this->data as $row){

			$row_sub_ids = [];
			$newInstance = false;
			
			if ($row['Id_Instance'] != $current_id){
				if ($current_id != NULL){
					$this->save_row($xw, $current_id, $current_data);
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
		
		$this->save_row($xw, $current_id, $current_data);
		
		$xw->endElement();
		
		$xw->endDocument();
		return $xw->outputMemory();
	}
	
	private function save_row (XMLWriter &$xw, $id, $data){
		$xw->startElement('instance');
		
		foreach ($data['attributes'] as $aname => $aval){
			$xw->startAttribute($aname);
			$xw->text($aval);
			$xw->endAttribute();
		}
		unset($data['attributes']);
		
		foreach ($data as $key => $val){
			if (is_array($val) && ($this->add_empty || count($val) > 0)){
				$this->add_array($xw, $key, $val);				
			}
			else {
				if ($this->add_empty || ($val !== '' && $val !== null)){
					$xw->startElement(self::underscore_to_camel($key));
					if ($key == 'Instance_Source'){
						self::split_source($xw, $val);
					}
					else if ($key == 'Community_Bounding_Box'){
						self::split_bounding_box($xw, $val);
					}
					else if ($key == 'Community_Name'){
						$this->split_comm_name($xw, $val);
					}
					else {
						$xw->text($val);
					}
					$xw->endElement();
				}
			}
		}
		
		$xw->endElement();
	}
	
	
	private function add_array (XMLWriter &$xw, $name, $arr){
		
		$xw->startElement(self::underscore_to_camel($name . 's'));

		foreach ($arr as $element){
			$xw->startElement(self::underscore_to_camel($name));
			
			foreach ($element['attributes'] as $aname => $aval){
				$xw->startAttribute($aname);
				$xw->text($aval);
				$xw->endAttribute();
			}
			unset($element['attributes']);
			
			foreach ($element as $key => $part){
				if (is_array($part)){
					if ($this->add_empty || count($part) > 0){
						$this->add_array($xw, $key, $part);
					}
				}
				else {
					if ($this->add_empty || ($part !== '' && $part !== null)){
						$xw->startElement(self::underscore_to_camel($key));
						$xw->text($part);
						$xw->endElement();
					}
				}
			}
			$xw->endElement();
		}
		
		$xw->endElement();
	}
	
	private static function underscore_to_camel ($str){
		$str = strtolower($str);
		
		return preg_replace_callback('/\_([a-zA-Z])/', function ($match){
			return strtoupper($match[1]);
		}, $str);
	}
	
	private static function split_source (XMLWriter &$xw, $soure_str){
		$data = explode('#', $soure_str);
		
		$xw->startElement('source');
		$xw->text($data[0]);
		$xw->endElement();
		
		$xw->startElement('mapNumber');
		$xw->text($data[1]);
		$xw->endElement();
		
		$xw->startElement('subNumber');
		$xw->text($data[2]);
		$xw->endElement();
		
		$xw->startElement('informantNumber');
		$xw->text($data[3]);
		$xw->endElement();
		
		$xw->startElement('locationName');
		$xw->text($data[4]);
		$xw->endElement();
	}
	
	private function split_comm_name (XMLWriter &$xw, $source_str){
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
		
		$xw->startElement('officialName');
		$xw->text($name);
		$xw->endElement();
		
		if ($this->add_empty || $transl){
			$xw->startElement('translations');
			foreach ($transl as $lang => $name){
				$xw->startElement('translation');
				$xw->startAttribute('lang');
				$xw->text(self::va_lang_to_iso($lang));
				$xw->endAttribute();
				$xw->text($name);				
				$xw->endElement();
			}
			$xw->endElement();
		}
	}

	private static function split_bounding_box (XMLWriter &$xw, $source_str){
		$source_str = substr($source_str, 9, -2);
		$coords = explode(',', $source_str);
		foreach ($coords as $coord){
			$xw->startElement('point');
			
			$latlng = explode(' ', $coord);
			$xw->startElement('latitude');
			$xw->text($latlng[1]);
			$xw->endElement();
			
			$xw->startElement('longitude');
			$xw->text($latlng[0]);
			$xw->endElement();
			
			$xw->endElement();
		}
	}
}