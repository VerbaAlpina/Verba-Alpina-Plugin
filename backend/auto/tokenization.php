<?php 
class Tokenizer {
	//TODO angelegt_von, angelegt_am
	private $levels;
	private $replacement_rules = [];
	private $escape_rules = [];
	private $copy_rules = [];
	
	private $postProcessFunctions = [];
	
	private $ignore_areas_escape = [];
	private $ignore_areas_copy = [];
	
	private $data = [];
	
	const DEBUG = false;
	
	public function __construct ($levels){
		foreach ($levels as &$level){
			if(!is_array($level)){
				$level = [$level];
			}
			else { //Longest separators first, in case they are starting with the same characters
				usort($level, function ($val1, $val2){
					count($val1) < count($val2);
				});
			}
		}
		
		$this->levels = $levels;
	}
	
	public function tokenize ($record, $extraData = []){
		
		$record = $this->preprocess($record, $extraData);
		
		$tokens = [['token' => $record, 'offset' => 0, 'indexes' => []]];
		foreach ($this->levels as $num => $level){
			$newTokens = [];
			foreach ($tokens as $token){
				$newParts = $this->customExplode($level, $token['token'], $token['offset'], $num == count($this->levels) - 1);
				foreach ($newParts as $index => $newPart){
					$tokenNew = trim($newPart[0]);
					$offset = $token['offset'] + $newPart[1] + strpos($newPart[0], $tokenNew);
					
					$newData = [
							'token' => $tokenNew, 
							'offset' => $offset, 
							'indexes' => array_merge($token['indexes'], [$index]), 
							'delimiter' => ($newPart[2]? $newPart[2]: (isset($token['delimiter'])? $token['delimiter']: NULL))
					];
					if(isset($newPart[3])){
						$newData['cfields'] = $newPart[3];
					}
					
					$newTokens[] = $newData;
				}
			}
			$tokens = $newTokens;
		}
		
		list($tokens, $global) = $this->postProcess($tokens, $extraData);
		
		return ['global' => $global, 'tokens' => $tokens];
	}
	
	private function preprocess ($record, $extraData){
		
		if (self::DEBUG){
			echo 'Record "' . htmlentities($record) . '"<br />';
		}
		
		$this->ignore_areas_copy = [];
		$this->ignore_areas_escape = [];
		
		$record = trim($record);
		$record = preg_replace('/ +/', ' ', $record);
		
		foreach ($this->replacement_rules as $replaceArray){
			$conditionFun = $replaceArray['condition'];
			if($conditionFun === false || $conditionFun($record, $extraData)){
				if($replaceArray['isRegex']){
					$record = preg_replace($replaceArray['search'], $replaceArray['replace'], $record);
				}
				else {
					$record = str_replace($replaceArray['search'], $replaceArray['replace'], $record);
				}
			}
		}
		
		if (self::DEBUG){
			echo 'After replacements: "' . htmlentities($record) . '"<br>';
		}
		
		//Use merged intervals for escaping
		$matches = [];
		foreach ($this->escape_rules as $escape_rule){
			$conditionFun = $escape_rule['condition'];
			if($conditionFun === false || $conditionFun($record, $extraData)){
				preg_match_all($escape_rule['search'], $record, $matches, PREG_OFFSET_CAPTURE);
				foreach ($matches[0] as $match){
					$this->ignore_areas_escape = va_add_interval($this->ignore_areas_escape, [$match[1], $match[1] + strlen($match[0])]);
				}
			}
		}
		
		//Use just list of (ordered) intervals for copying (if intervals overlap an error is thrown)
		foreach ($this->copy_rules as $copyData){
			$conditionFun = $copyData['condition'];
			if($conditionFun === false || $conditionFun($record, $extraData)){
				preg_match_all($copyData['search'], $record, $matches, PREG_OFFSET_CAPTURE);
				foreach ($matches[0] as $match){
					$this->ignore_areas_copy[] =  [$match[1], $match[1] + strlen($match[0]), $copyData['field'], $copyData['separator'], $copyData['edit']];
				}
			}
		}
		
		usort($this->ignore_areas_copy, function ($e1, $e2){
			return $e1[0] > $e2[0];
		});
		
		for ($i = 1; $i < count($this->ignore_areas_copy); $i++){
			$lastInterval = $this->ignore_areas_copy[$i - 1];
			$currentInterval = $this->ignore_areas_copy[$i];
			
			if($lastInterval[1] > $currentInterval[0]){
				throw new Exception('Overlapping intervals for copy rules!');
			}
		}
		
		if (self::DEBUG){
			echo 'Copy areas: ' . va_add_marking_spans($record, $this->ignore_areas_copy) . '<br>';
			echo 'Escape areas: ' . va_add_marking_spans($record, $this->ignore_areas_escape) . '<br>';
		}
		
		return $record;
	}
	
	public function addPostProcessFunction ($fun){
		$this->postProcessFunctions[] = $fun;
	}
	
	private function postProcess ($tokens, $extraData){
			
		foreach ($tokens as &$token){
			foreach ($this->escape_rules as $escape_rule){
				if($escape_rule['replace'] !== false){
					$token['token'] = preg_replace($escape_rule['search'], $escape_rule['replace'], $token['token']);
				}
			}
		}
		
		$global = [];
		
		foreach ($this->postProcessFunctions as $fun){
			list($tokens, $global) = $fun($this, $tokens, $global, $extraData);
		}
		return [$tokens, $global];
	}
	
	private function customExplode ($delimiters, $str, $offset, $lastLevel){
		$res = [];
		$offsetIncrease = 0;
				
		$chrArray = preg_split('//u', $str, -1, PREG_SPLIT_NO_EMPTY);
		
		$currEscapeInterval = NULL;
		if(count($this->ignore_areas_escape) == 0){
			$currEscapeIntervalIndex = false;
		}
		else {
			$currEscapeIntervalIndex = 0;
			$currEscapeInterval = $this->ignore_areas_escape[0];
		}
		
		$currCopyInterval = NULL;
		if(count($this->ignore_areas_copy) == 0){
			$currCopyIntervalIndex = false;
		}
		else {
			$currCopyIntervalIndex = 0;
			$currCopyInterval = $this->ignore_areas_copy[0];
		}
		
		$currentToken = '';
		$currentOffset = 0;
		$currentCField = NULL;
		$currentCFieldContent = '';
		$cFields = [];
		$skipChar = 0;
		
		if(self::DEBUG){
			echo '<b>Start splitting: "' . htmlentities($str) . '" for delimiters [' . htmlentities(implode(',', va_surround($delimiters, '"'))) . ']</b><br />';
			echo 'Start offset: ' . $offset . '<br /><br />';
		}
		
		foreach ($chrArray as $index => $char){
			if(self::DEBUG){
				echo 'Character "' . $char . '"<br />';
			}
			
			if($skipChar > 0){
				$skipChar--;
				if(self::DEBUG){
					echo 'Skip<br />';
				}
				continue;
			}
			
			$recordIndex = $index + $offset;
			if(self::DEBUG){
				echo 'Record Index:  "' . $recordIndex . '"<br />';
			}
			
			if(!mb_check_encoding($char, 'ASCII')){
				$offset++;
				$offsetIncrease++;
				if (self::DEBUG){
					echo 'Non ASCII char => increase offset <br />';
				}
			}
			
			if($currEscapeIntervalIndex !== false && $recordIndex >= $currEscapeInterval[1]){
				if(count($this->ignore_areas_escape) > $currEscapeIntervalIndex + 1){
					$currEscapeInterval = $this->ignore_areas_escape[++$currEscapeIntervalIndex];
				}
				else {
					$currEscapeIntervalIndex = false;
				}
			}
			
			if($currCopyIntervalIndex !== false && $recordIndex >= $currCopyInterval[1]){
				if($currentCFieldContent){
					$this->appendToCField($currentCFieldContent, $currentCField, $currCopyInterval, $cFields);
				}
				
				$currentCField = NULL;
				$currentCFieldContent = '';
				
				if(count($this->ignore_areas_copy) > $currCopyIntervalIndex + 1){
					$currCopyInterval = $this->ignore_areas_copy[++$currCopyIntervalIndex];
				}
				else {
					$currCopyIntervalIndex = false;
				}
			}

			//Escaping has priority over copying
			if($currEscapeIntervalIndex !== false && $recordIndex >= $currEscapeInterval[0] && $recordIndex < $currEscapeInterval[1]){
				$currentToken .= $char;
				if (self::DEBUG){
					echo 'Char in escape interval<br />';
				}
				continue;
			}
			//Copying
			else if($currCopyIntervalIndex !== false && $recordIndex >= $currCopyInterval[0] && $recordIndex < $currCopyInterval[1]){
				if($lastLevel){
					if($currentCField === NULL){
						$currentCField = $currCopyInterval[2];
					}
	
					$currentCFieldContent .= $char;
					if (self::DEBUG){
						echo 'Char in copy interval<br />';
					}
				}
				else {
					$currentToken .= $char;
				}
				continue;
			}
			
			//Look for delimiters
			$delimiterFound = NULL;
			foreach ($delimiters as $delimiter){
				$isDelimiter = true;
				foreach (str_split($delimiter) as $num_del_char => $del_char){
					if(count($chrArray) <= $index + $num_del_char || $chrArray[$index + $num_del_char] != $del_char){
						$isDelimiter = false;
						break;
					}
				}
				if($isDelimiter){
					$delimiterFound = $delimiter;
					break;
				}
			}
			
			if ($delimiterFound !== NULL){
				$res[] = [$currentToken, $currentOffset, $delimiterFound, $cFields];
				$currentToken = '';
				$currentOffset = $index + $offsetIncrease + 1;
				$cFields = [];
				
				if(strlen($delimiterFound) > 1){
					$skipChar = strlen($delimiterFound) - 1;
				}
			}
			else {
				$currentToken .= $char;
			}
		}
		
		if($currentCFieldContent){
			$this->appendToCField($currentCFieldContent, $currentCField, $currCopyInterval, $cFields);
		}
		
		$res[] = [$currentToken, $currentOffset, NULL, $cFields];
		
		return $res;
	}
	
	private function appendToCField ($text, $field, $copyInterval, &$fields){
		if(is_callable($copyInterval[4])){
			$savedContent = $copyInterval[4]($text);
		}
		else {
			$savedContent = $text;
		}
		
		if (array_key_exists($field, $fields)){
			$fields[$field] .= $copyInterval[3] . trim($savedContent);
		}
		else {
			$fields[$field] = trim($savedContent);
		}
	}
	
	public function addReplacementString ($search, $replace, $conditionFun = false){
		$this->replacement_rules[] = ['search' => $search, 'replace' => $replace, 'isRegex' => false, 'condition' => $conditionFun];
	}
	
	public function addReplacementRegex ($search, $replace, $conditionFun = false){
		$this->replacement_rules[] = ['search' => $search, 'replace' => $replace, 'isRegex' => true, 'condition' => $conditionFun];
	}
	
	public function addEscapeRegex ($regexp, $repl = false, $conditionFun = false){
		$this->escape_rules[] = ['search' => $regexp, 'replace' => $repl, 'condition' => $conditionFun];
	}
	
	public function addCopyRegex ($regexp, $fieldName, $seperator, $editFun, $conditionFun = false){
		$this->copy_rules[] = ['search' => $regexp, 'field' => $fieldName, 'separator' => $seperator, 'edit' => $editFun, 'condition' => $conditionFun];
	}
	
	public function addData ($name, $data){
		$this->data[$name] = $data;
	}
	
	public function getData ($name){
		return $this->data[$name];
	}
}

function va_create_tokenizer ($source){
	
	if($source == 'ALD-I'){
		$sourceUsed = 'ALD-II';
	}
	else {
		$sourceUsed = $source;
	}
	
	global $va_xxx;
	$spaceTypes = $va_xxx->get_col("SELECT Beta FROM Codepage_IPA WHERE Erhebung = '$sourceUsed' AND Art = 'Trennzeichen'");
	if(!$spaceTypes){
		$spaceTypes = [' '];
	}
	
	$tokenizer = new Tokenizer([';', ',', $spaceTypes]);
	$articlesDB = $va_xxx->get_results('SELECT Artikel, Genus, Sprache FROM Artikel', ARRAY_N);
	$articles = [];
	foreach ($articlesDB as $article){
		$articles[$article[0]] = [$article[1], $article[2]];
	}
	$tokenizer->addData('articles', $articles);
	$tokenizer->addData('schars', $va_xxx->get_col('SELECT Zeichen FROM Sonderzeichen'));
	$tokenizer->addData('source', $source);
	
	$tokenizer->addEscapeRegex('/\\\\\\\\([;,])/', '$1');
	$tokenizer->addEscapeRegex('/\\\\\\\\(.)/');
	
	switch ($source){
		case 'Crowd':
			$tokenizer->addReplacementString('(', '<');
			$tokenizer->addReplacementRegex('/(?<!;-)\)/', '>');
			break;
	}
	
	if($source != 'ALD-I' && $source != 'ALD-II'){
		$tokenizer->addReplacementRegex('/\s+</', '<');
		$tokenizer->addReplacementRegex('/>\s+/', '>');
		
		$tokenizer->addCopyRegex('/<.*>/U', 'notes', ' ', function ($str){return substr($str, 1, strlen($str) - 2);});
	}
	
	$tokenizer->addPostProcessFunction('va_tokenize_to_db_cols');
	$tokenizer->addPostProcessFunction('va_tokenize_split_double_genders');
	$tokenizer->addPostProcessFunction('va_tokenize_handle_groups_and_concepts');
	$tokenizer->addPostProcessFunction('va_tokenize_handle_source_types');
	
	return $tokenizer;
}

function va_tokenize_handle_groups_and_concepts ($tokenizer, $tokens, $global, $extraData){

	$global['groups'] = [];
	
	$articles = $tokenizer->getData('articles');
	$schars = $tokenizer->getData('schars');
	
	//Split into groups
	$groups = [];
	$current_index = 0;
	foreach ($tokens as $token){
		if($token['Ebene_3'] == 1){
			$current_index++;
			$groups[$current_index] = [$token];
		}
		else {
			$groups[$current_index][] = $token;
		}
	}
	
	$result = [];
	
	$isConcept = function ($token, $concept){
		return isset($token['Konzepte']) && count($token['Konzepte']) == 1 && $token['Konzepte'][0] == $concept;
	};
	
	//Handle groups
	foreach ($groups as &$group){
		$len = count($group);
		
		if($len == 1){ //One Token
			$group[0]['Id_Tokengruppe'] = NULL;
			if (in_array($token['Token'], $schars)){
				$group[0]['Konzepte'] = [779];
			}
			else {
				$group[0]['Konzepte'] = $extraData['concepts'];
			}
		}
		else {
			$group_gender_from_article = '';
			//Mark articles and special characters
			foreach ($group as $index => $token){
				if(array_key_exists($token['Token'], $articles) && ($articles[$token['Token']][1] == '' || strpos($extraData['lang'], $articles[$token['Token']][1]) !== false)){
					$group[$index]['Konzepte'] = [699];
					$group[$index]['Genus'] = $articles[$token['Token']][0];
					
					if($index == 0 || ($index == 1 && $isConcept($group[$index-1], 779))){
						$group_gender_from_article = $articles[$token['Token']][0];
					}
				}
				else if (in_array($token['Token'], $schars)){
					$group[$index]['Konzepte'] = [779];
				}
			}
			
			
			//Article + token => no group
			if($len == 2 && $isConcept($group[0], 699)){
				$group[0]['Id_Tokengruppe'] = NULL;
				$group[1]['Id_Tokengruppe'] = NULL;
				$group[1]['Konzepte'] = $extraData['concepts'];
				
				if($group[1]['Genus'] == ''){
					$group[1]['Genus'] = $group_gender_from_article;
				}
			}
			//special char + token => no group
			else if ($len == 2 && $isConcept($group[0], 779)){
				$group[0]['Id_Tokengruppe'] = NULL;
				$group[1]['Id_Tokengruppe'] = NULL;
				$group[1]['Konzepte'] = $extraData['concepts'];
			}
			//special char + token + special char => no group
			else if ($len == 3 && $isConcept($group[0], 779) && $isConcept($group[2], 779)){
				$group[0]['Id_Tokengruppe'] = NULL;
				$group[1]['Id_Tokengruppe'] = NULL;
				$group[2]['Id_Tokengruppe'] = NULL;
				$group[1]['Konzepte'] = $extraData['concepts'];
			
				//Move notes to "real" token
				$group[1]['Bemerkung'] = $group[2]['Bemerkung'];
				$group[2]['Bemerkung'] = '';
			}
			//Article + token + special char => no group
			else if ($len == 3 && $isConcept($group[0], 699) && $isConcept($group[2], 779)){
				$group[0]['Id_Tokengruppe'] = NULL;
				$group[1]['Id_Tokengruppe'] = NULL;
				$group[1]['Konzepte'] = $extraData['concepts'];
				$group[2]['Id_Tokengruppe'] = NULL;
				
				if($group[1]['Genus'] == ''){
					$group[1]['Genus'] = $group_gender_from_article;
				}
				
				//Move notes to "real" token
				$group[1]['Bemerkung'] = $group[2]['Bemerkung'];
				$group[2]['Bemerkung'] = '';
			}
			//Special char + article + token => no group
			else if ($len == 3 && $isConcept($group[1], 699) && $isConcept($group[0], 779)){
				$group[0]['Id_Tokengruppe'] = NULL;
				$group[1]['Id_Tokengruppe'] = NULL;
				$group[2]['Konzepte'] = $extraData['concepts'];
				$group[2]['Id_Tokengruppe'] = NULL;
				
				if($group[2]['Genus'] == ''){
					$group[2]['Genus'] = $group_gender_from_article;
				}
			}
			//Group
			else {
				$indexGroup = count($global['groups']);
				$group_gender = '';
				$group_notes = $group[$len - 1]['Bemerkung'];
				$group[$len - 1]['Bemerkung'] = '';
				
				if($group[$len - 1]['Genus'] == ''){
					$group_gender = $group_gender_from_article;
				}
				else {
					$group_gender = $group[$len - 1]['Genus'];
				}
				
				$global['groups'][] = ['Genus' => $group_gender, 'Bemerkung' => $group_notes, 'Konzepte' => $extraData['concepts'], 'MTyp' => NULL, 'PTyp' => NULL];
				
				foreach ($group as $index => $token){
					$group[$index]['Id_Tokengruppe'] = 'NEW' . $indexGroup;
					if(!array_key_exists('Konzepte', $group[$index])){
						$group[$index]['Konzepte'] = [];
					}
					if(!$isConcept($group[$index], 699)){
						$group[$index]['Genus'] = '';
					}
					$group[$index]['Bemerkung'] = '';
				}
			}
		}
		
		foreach ($group as $token){
			$result[] = $token;
		}
	}
	
	return [$result, $global];
}

function va_tokenize_to_db_cols ($tokenizer, $tokens, $global, $extraData){
	$result = [];
	
	foreach ($tokens as $token){
		$newToken = [];
		
		$newToken['Token'] = $token['token'];
		
		if($token['delimiter'] == ';' || $token['delimiter'] == ','){
			$newToken['Trennzeichen'] = NULL;
		}
		else {
			$newToken['Trennzeichen'] = $token['delimiter'];
		}
		
		foreach ($token['indexes'] as $index => $num){
			$newToken['Ebene_' . ($index + 1)] = $num + 1;
		}

		$notesList = [];
		if($extraData['notes'] && $newToken['Trennzeichen'] === NULL){
			$notesList[] = $extraData['notes'];
		}
		if(isset($token['cfields']['notes'])){
			$notesList[] = $token['cfields']['notes'];
		}
		$newToken['Bemerkung'] = implode(' ', $notesList);
		
		$result[] = $newToken;
	}
	return [$result, $global];
}

function va_tokenize_handle_source_types ($tokenizer, $tokens, $global, $extraData){
	global $va_xxx;
	
	$global['mtypes'] = [];
	$global['ptypes'] = [];
	
	$currentGroupTypes = [];
	$indexGroup = 0;
	
	if($extraData['class'] != 'B'){
		foreach ($tokens as $index => &$token){
			//Entferne doppelte Backslashes (sollten eigentlich beim Transkribieren von Typen gar nicht benutzt werden)
			$token['Token'] = str_replace('\\\\', '', $token['Token']);
			
			//Ersetze im Beta-Code transkribierte Umlaute in Typen
			//TODO use codepage here
			//TODO auch gleich für tokens
			$token['Token'] = str_replace('u:', 'ü', $token['Token']);
			$token['Token'] = str_replace('o:', 'ö', $token['Token']);
			$token['Token'] = str_replace('a:', 'ä', $token['Token']);
			$token['Token'] = str_replace('a1', 'α', $token['Token']);
			
			$orth = $token['Token'];
			$gender = $token['Genus'];
			
			if($extraData['class'] == 'M'){
				$type_id = $va_xxx->get_var($va_xxx->prepare('SELECT Id_morph_Typ FROM morph_Typen WHERE Orth = %s AND Genus = %s', $orth, $gender));
				if($type_id){
					$token['MTyp'] = intval($type_id);
				}
				else {
					$token['MTyp'] = 'NEW' . count($global['mtypes']);
					$global['mtypes'][] = ['Orth' => $orth, 'Genus' => $gender, 'Quelle' => $tokenizer->getData('source')];
				}
				$token['PTyp'] = NULL;
			}
			else {
				$type_id = $va_xxx->get_var($va_xxx->prepare('SELECT Id_phon_Typ FROM phon_Typen WHERE Beta = %s', $orth));
				if($type_id){
					$token['PTyp'] = intval($type_id);
				}
				else {
					$token['PTyp'] = 'NEW' . count($global['ptypes']);
					$global['ptypes'][] = ['Beta' => $orth, 'Quelle' => $tokenizer->getData('source')];
				}
				$token['MTyp'] = NULL;
			}
			
			$currentGroupTypes[] = $orth . $token['Trennzeichen'];
			
			//Last token of group
			if($index == count($tokens) - 1 || $tokens[$index + 1]['Ebene_3'] == 1){
				if($token['Id_Tokengruppe'] !== NULL){
					$orthGroup = implode('', $currentGroupTypes);
					$group = &$global['groups'][$indexGroup];
					$groupGender = $group['Genus'];
					
					if($extraData['class'] == 'M'){
						$gtype_id = $va_xxx->get_var($va_xxx->prepare('SELECT Id_morph_Typ FROM morph_Typen WHERE Orth = %s AND Genus = %s', $orthGroup, $groupGender));
						if ($gtype_id){
							$group['MTyp'] = $gtype_id;
						}
						else {
							$group['MTyp'] = 'NEW' . count($global['mtypes']);
							$global['mtypes'][] = ['Orth' => $orthGroup, 'Genus' => $groupGender, 'Quelle' => $tokenizer->getData('source')];
						}
					}
					else {
						$gtype_id = $va_xxx->get_var($va_xxx->prepare('SELECT Id_phon_Typ FROM phon_Typen WHERE Beta = %s', $orthGroup));
						if ($gtype_id){
							$group['PTyp'] = intval($gtype_id);
						}
						else {
							$group['PTyp'] = 'NEW' . count($global['ptypes']);
							$global['ptypes'][] = ['Beta' => $orthGroup, 'Quelle' => $tokenizer->getData('source')];
						}
					}
					
					$addGroup = $tokenizer->getData('source') . '-Typ ' . $orthGroup;
					$group['Bemerkung'] = ($group['Bemerkung']? $group['Bemerkung'] . ' ' . $addGroup: $addGroup);
					$indexGroup++;
				}
				$currentGroupTypes = [];
			}
			
			$token['Token'] = '';
			$add = $tokenizer->getData('source') . '-Typ ' . $orth;
			$token['Bemerkung'] = ($token['Bemerkung']? $token['Bemerkung'] . ' ' . $add: $add);
		}
	}
	else {
		foreach ($tokens as &$token){
			$token['MTyp'] = NULL;
			$token['PTyp'] = NULL;
		}
	}

	return [$tokens, $global];
}

function va_tokenize_split_double_genders ($tokenizer, $tokens, $global, $extraData){
	$result = [];
	
	//Duplicate tokens with multiple gender information
	$currentGroup = [];
	
	foreach ($tokens as $index => $token){
		//Last token in group
		if($index == count($tokens) - 1 || $tokens[$index + 1]['Ebene_3'] == 1){

			if(isset($token['Bemerkung'])){
				$genderRegex = '/(?<=^|[ .,;])[mfn](?=$|[ .,;])/';
				$notes = $token['Bemerkung'];
				preg_match_all($genderRegex, $notes, $matches, PREG_OFFSET_CAPTURE);
				
				if(count($matches[0]) > 0){
					$genderStrs = [];
					$offset = 0;
					foreach ($matches[0] as $match){
						$start = $match[1] - $offset;
						$len = ($start == strlen($notes) - 1 || $notes[$start + 1] != '.'? 1: 2);
						$genderStrs[] = substr($notes, $start, $len);
						$notes = substr($notes, 0, $start) . substr($notes, $start + $len);
						$offset += $len;
					}
					
					$notes = trim(preg_replace('/ +/', ' ', $notes));
					
					foreach ($genderStrs as $genderStr){
						foreach ($currentGroup as $gtoken){
							$result[] = $gtoken;
						}
						$newToken = $token;
						$newToken['Bemerkung'] = $genderStr . ($notes? ' ' . $notes: '');
						$newToken['Genus'] = $genderStr[0];
						$result[] = $newToken;
					}
					$currentGroup = [];
					continue;
				}
			}
			
			$token['Genus'] = '';
			foreach ($currentGroup as $gtoken){
				$result[] = $gtoken;
			}
			$result[] = $token;
			$currentGroup = [];
		}
		else {
			$token['Genus'] = '';
			$currentGroup[] = $token;
		}
	}
	return [$result, $global];
}

function va_tokenize_test (){
	$tests = [
		[	
			'AIS', 
			'\\\\: cascina de l{ }alpe e del{ }uffa <test>; blubb, blubbe <inf.> <m.>', 
			['class' => 'B', 'concepts' => [1], 'notes' => '', 'lang' => 'rom'],
			[ 	'tokens' =>
				[
					['Token' => '\\\\:', 'Trennzeichen' => ' ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Genus' => '', 'Bemerkung' => '', 'Konzepte' => [779], 'Id_Tokengruppe' => 'NEW0', 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'cascina', 'Trennzeichen' => ' ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 2, 'Genus' => '', 'Bemerkung' => '', 'Konzepte' => [], 'Id_Tokengruppe' => 'NEW0', 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'de', 'Trennzeichen' => ' ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 3, 'Genus' => '', 'Bemerkung' => '', 'Konzepte' => [], 'Id_Tokengruppe' => 'NEW0', 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'l', 'Trennzeichen' => '{ }', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 4, 'Genus' => 'm', 'Bemerkung' => '', 'Konzepte' => [699], 'Id_Tokengruppe' => 'NEW0', 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'alpe', 'Trennzeichen' => ' ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 5, 'Genus' => '', 'Bemerkung' => '', 'Konzepte' => [], 'Id_Tokengruppe' => 'NEW0', 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'e', 'Trennzeichen' => ' ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 6, 'Genus' => '', 'Bemerkung' => '', 'Konzepte' => [], 'Id_Tokengruppe' => 'NEW0', 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'del', 'Trennzeichen' => '{ }', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 7, 'Genus' => '', 'Bemerkung' => '', 'Konzepte' => [], 'Id_Tokengruppe' => 'NEW0', 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'uffa', 'Trennzeichen' => NULL, 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 8, 'Bemerkung' => '', 'Genus' => '', 'Konzepte' => [], 'Id_Tokengruppe' => 'NEW0', 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'blubb', 'Trennzeichen' => NULL, 'Ebene_1' => 2, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Genus' => '', 'Bemerkung' => '', 'Konzepte' => [1], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'blubbe', 'Trennzeichen' => NULL, 'Ebene_1' => 2, 'Ebene_2' => 2, 'Ebene_3' => 1, 'Bemerkung' => 'm. inf.', 'Genus' => 'm', 'Konzepte' => [1], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL]
				],
				'global' => [
						'groups' => [
								0 => ['Genus' => '', 'Bemerkung' => 'test', 'Konzepte' => [1], 'MTyp' => NULL, 'PTyp' => NULL]
						],
						'mtypes' => [],
						'ptypes' => []
				]
			]	
		],
		
		[	
			'BSA',
			'nase\\\\, hut <m f>',
				['class' => 'B', 'concepts' => [77], 'notes' => '', 'lang' => 'ger'],
				[ 	'tokens' =>
					[
						['Token' => 'nase,', 'Trennzeichen' => ' ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Genus' => '', 'Bemerkung' => '', 'Konzepte' => [], 'Id_Tokengruppe' => 'NEW0' , 'MTyp' => NULL, 'PTyp' => NULL],
						['Token' => 'hut', 'Trennzeichen' => NULL, 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 2, 'Genus' => '', 'Bemerkung' => '', 'Konzepte' => [], 'Id_Tokengruppe' => 'NEW0' , 'MTyp' => NULL, 'PTyp' => NULL],
						['Token' => 'nase,', 'Trennzeichen' => ' ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => '', 'Genus' => '', 'Konzepte' => [], 'Id_Tokengruppe' => 'NEW1' , 'MTyp' => NULL, 'PTyp' => NULL],
						['Token' => 'hut', 'Trennzeichen' => NULL, 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 2, 'Bemerkung' => '', 'Genus' => '', 'Konzepte' => [], 'Id_Tokengruppe' => 'NEW1' , 'MTyp' => NULL, 'PTyp' => NULL]
					],
				'global' => [
						'groups' => [
							['Genus' => 'm', 'Bemerkung' => 'm', 'Konzepte' => [77], 'MTyp' => NULL, 'PTyp' => NULL],
							['Genus' => 'f', 'Bemerkung' => 'f', 'Konzepte' => [77], 'MTyp' => NULL, 'PTyp' => NULL]
						],
						'mtypes' => [],
						'ptypes' => []
				]
			]
				
		],
			
		[
			'ALD-I',
			'1c1a1s1 1d1e1l<1b1>1 1c1a1s',
				['class' => 'B', 'concepts' => [99, 77], 'notes' => '', 'lang' => 'rom'],
			[
			'tokens' =>
				[
					['Token' => '1c1a1s', 'Trennzeichen' => '1 ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Genus' => '', 'Bemerkung' => '', 'Konzepte' => [], 'Id_Tokengruppe' => 'NEW0', 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => '1d1e1l<1b1>', 'Trennzeichen' => '1 ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 2, 'Genus' => '', 'Bemerkung' => '', 'Konzepte' => [], 'Id_Tokengruppe' => 'NEW0', 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => '1c1a1s', 'Trennzeichen' => NULL, 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 3, 'Genus' => '', 'Bemerkung' => '', 'Konzepte' => [], 'Id_Tokengruppe' => 'NEW0', 'MTyp' => NULL, 'PTyp' => NULL]
				],
			'global' => 
				[
					'groups' => [
						['Genus' => '', 'Bemerkung' => '', 'Konzepte' => [99,77], 'MTyp' => NULL, 'PTyp' => NULL]
					],
					'mtypes' => [],
					'ptypes' => []
				]
			]
				
		],
			
		[
			'ALD-II',
			'1c1a1s1 1d1e1l<1b1>1 1c1a1s',
				['class' => 'B', 'concepts' => [99, 77], 'notes' => '', 'lang' => 'rom'],
				[
				'tokens' =>
				[
					['Token' => '1c1a1s', 'Trennzeichen' => '1 ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Genus' => '', 'Bemerkung' => '', 'Konzepte' => [], 'Id_Tokengruppe' => 'NEW0', 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => '1d1e1l<1b1>', 'Trennzeichen' => '1 ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 2, 'Genus' => '', 'Bemerkung' => '', 'Konzepte' => [], 'Id_Tokengruppe' => 'NEW0', 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => '1c1a1s', 'Trennzeichen' => NULL, 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 3, 'Genus' => '', 'Bemerkung' => '', 'Konzepte' => [], 'Id_Tokengruppe' => 'NEW0', 'MTyp' => NULL, 'PTyp' => NULL]
				],
				'global' =>
				[
					'groups' => [
						['Genus' => '', 'Bemerkung' => '', 'Konzepte' => [99,77], 'MTyp' => NULL, 'PTyp' => NULL]
					],
					'mtypes' => [],
					'ptypes' => []
				]
			]
		],
		
		[
			'Crowd',
			'Suppe (ganz dolle Sache, schmeckt super), Haustür (oder so), étrôngé',
				['class' => 'B', 'concepts' => [5], 'notes' => '', 'lang' => 'rom'],
				[
				'tokens' =>
				[
					['Token' => 'Suppe', 'Trennzeichen' => NULL, 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => 'ganz dolle Sache, schmeckt super', 'Genus' => '', 'Konzepte' => [5], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'Haustür', 'Trennzeichen' => NULL, 'Ebene_1' => 1, 'Ebene_2' => 2, 'Ebene_3' => 1, 'Bemerkung' => 'oder so', 'Genus' => '', 'Konzepte' => [5], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'étrôngé', 'Trennzeichen' => NULL, 'Ebene_1' => 1, 'Ebene_2' => 3, 'Ebene_3' => 1, 'Genus' => '', 'Bemerkung' => '', 'Konzepte' => [5], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL]
				],
				'global' =>
				[
					'groups' => [],
					'mtypes' => [],
					'ptypes' => []
				]
			]
		],
			
		[
			'AIS',
			' cal <m. f. > ; caso <n.> ; ca <m.f.n.> <toll>; cas<inf.>  <n.f.> <veraltet>',
				['class' => 'B', 'concepts' => [4], 'notes' => '', 'lang' => 'rom'],
				[
				'tokens' =>
				[
					['Token' => 'cal', 'Trennzeichen' => NULL, 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => 'm.', 'Genus' => 'm', 'Konzepte' => [4], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'cal', 'Trennzeichen' => NULL, 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => 'f.', 'Genus' => 'f', 'Konzepte' => [4], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'caso', 'Trennzeichen' => NULL, 'Ebene_1' => 2, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => 'n.', 'Genus' => 'n', 'Konzepte' => [4], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'ca', 'Trennzeichen' => NULL, 'Ebene_1' => 3, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => 'm. toll', 'Genus' => 'm', 'Konzepte' => [4], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'ca', 'Trennzeichen' => NULL, 'Ebene_1' => 3, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => 'f. toll', 'Genus' => 'f', 'Konzepte' => [4], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'ca', 'Trennzeichen' => NULL, 'Ebene_1' => 3, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => 'n. toll', 'Genus' => 'n', 'Konzepte' => [4], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'cas', 'Trennzeichen' => NULL, 'Ebene_1' => 4, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => 'n. inf. veraltet', 'Genus' => 'n', 'Konzepte' => [4], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL],
					['Token' => 'cas', 'Trennzeichen' => NULL, 'Ebene_1' => 4, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => 'f. inf. veraltet', 'Genus' => 'f', 'Konzepte' => [4], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL]
				],
				'global' =>
					[
						'groups' => [],
						'mtypes' => [],
						'ptypes' => []
					]
				]
		],
	
		[
			'SDS',
			'Der Tennen; die Haustu:r',
				['class' => 'M', 'concepts' => [11], 'notes' => '', 'lang' => 'ger'],
				[
				'tokens' =>
					[
						['Token' => '', 'MTyp' => 'NEW0', 'Trennzeichen' => ' ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Genus' => 'm', 'Bemerkung' => 'SDS-Typ Der', 'Konzepte' => [699], 'Id_Tokengruppe' => NULL, 'PTyp' => NULL],
						['Token' => '', 'Genus' => 'm', 'MTyp' => 1076, 'Trennzeichen' => NULL, 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 2, 'Genus' => 'm', 'Bemerkung' => 'SDS-Typ Tennen', 'Konzepte' => [11], 'Id_Tokengruppe' => NULL, 'PTyp' => NULL],
						['Token' => '', 'MTyp' => 2009, 'Trennzeichen' => ' ', 'Ebene_1' => 2, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Genus' => 'f', 'Bemerkung' => 'SDS-Typ die', 'Konzepte' => [699], 'Id_Tokengruppe' => NULL, 'PTyp' => NULL],
						['Token' => '', 'Genus' => 'f', 'MTyp' => 'NEW1', 'Trennzeichen' => NULL, 'Ebene_1' => 2, 'Ebene_2' => 1, 'Ebene_3' => 2, 'Genus' => 'f', 'Bemerkung' => 'SDS-Typ Haustür', 'Konzepte' => [11], 'Id_Tokengruppe' => NULL, 'PTyp' => NULL]
					],
				'global' =>
					[
						'groups' => [ ],
						'mtypes' => [
							['Orth' => 'Der', 'Genus' => 'm', 'Quelle' => 'SDS'],
							['Orth' => 'Haustür', 'Genus' => 'f', 'Quelle' => 'SDS']
						],
						'ptypes' => []
					]
				]
		],
			
		[
			'AIS',
			'la cascina, il domani',
				['class' => 'B', 'concepts' => [5], 'notes' => '', 'lang' => 'rom'],
				[
				'tokens' =>
					[
						['Token' => 'la', 'Trennzeichen' => ' ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => '', 'Genus' => 'f', 'Konzepte' => [699], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL],
						['Token' => 'cascina', 'Trennzeichen' => NULL, 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 2, 'Bemerkung' => '', 'Genus' => 'f', 'Konzepte' => [5], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL],
						['Token' => 'il', 'Trennzeichen' => ' ', 'Ebene_1' => 1, 'Ebene_2' => 2, 'Ebene_3' => 1, 'Bemerkung' => '', 'Genus' => 'm', 'Konzepte' => [699], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL],
						['Token' => 'domani', 'Trennzeichen' => NULL, 'Ebene_1' => 1, 'Ebene_2' => 2, 'Ebene_3' => 2, 'Bemerkung' => '', 'Genus' => 'm', 'Konzepte' => [5], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL]
				],
				'global' =>
				[
					'groups' => [],
					'mtypes' => [],
					'ptypes' => []
				]
			]
		],
			
		[
			'TSA',
			'auf der Oim',
				['class' => 'M', 'concepts' => [44], 'notes' => '', 'lang' => 'ger'],
			[
				'tokens' => 
				[
					['Token' => '', 'Trennzeichen' => ' ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => 'TSA-Typ auf', 'Genus' => '', 'Konzepte' => [], 'MTyp' => 4231, 'PTyp' => NULL, 'Id_Tokengruppe' => 'NEW0'],
					['Token' => '', 'Trennzeichen' => ' ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 2, 'Bemerkung' => 'TSA-Typ der', 'Genus' => 'm', 'Konzepte' => [699], 'MTyp' => 'NEW0', 'PTyp' => NULL, 'Id_Tokengruppe' => 'NEW0'],
					['Token' => '', 'Trennzeichen' => NULL, 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 3, 'Bemerkung' => 'TSA-Typ Oim', 'Genus' => '', 'Konzepte' => [], 'MTyp' => 'NEW1', 'PTyp' => NULL, 'Id_Tokengruppe' => 'NEW0']
				],
				'global' =>
				[
					'groups' => [
						['Genus' => '', 'Bemerkung' => 'TSA-Typ auf der Oim', 'Konzepte' => [44], 'MTyp' => 'NEW2', 'PTyp' => NULL]
					],
					'mtypes' => [
						['Orth' => 'der', 'Genus' => 'm', 'Quelle' => 'TSA'],
						['Orth' => 'Oim', 'Genus' => '', 'Quelle' => 'TSA'],
						['Orth' => 'auf der Oim', 'Genus' => '', 'Quelle' => 'TSA']
					],
					'ptypes' => []
				]
			]
				
		],
			
		[
			'SDS',
			'chno:dle < verbale Bezeichnung> ; tu:u:mlige <adverbiale Bezeichnung>; gurkensaft; z tu:u:mlige <adverbiale Bezeichnung>',
				['class' => 'P', 'concepts' => [44], 'notes' => '', 'lang' => 'ger'],
				[
				'tokens' =>
				[
					['Token' => '', 'Trennzeichen' => NULL, 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => 'verbale Bezeichnung SDS-Typ chnödle', 'Genus' => '', 'Konzepte' => [44], 'PTyp' => 1089, 'Id_Tokengruppe' => NULL, 'MTyp' => NULL],
					['Token' => '', 'Trennzeichen' => NULL, 'Ebene_1' => 2, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => 'adverbiale Bezeichnung SDS-Typ tüümlige', 'Genus' => '', 'Konzepte' => [44], 'PTyp' => 1087, 'Id_Tokengruppe' => NULL, 'MTyp' => NULL],
					['Token' => '', 'Trennzeichen' => NULL, 'Ebene_1' => 3, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => 'SDS-Typ gurkensaft', 'Genus' => '', 'Konzepte' => [44], 'PTyp' => 'NEW0', 'Id_Tokengruppe' => NULL, 'MTyp' => NULL],
					['Token' => '', 'Trennzeichen' => ' ', 'Ebene_1' => 4, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => 'SDS-Typ z', 'Genus' => '', 'Konzepte' => [], 'PTyp' => 1085, 'MTyp' => NULL, 'Id_Tokengruppe' => 'NEW0'],
					['Token' => '', 'Trennzeichen' => NULL, 'Ebene_1' => 4, 'Ebene_2' => 1, 'Ebene_3' => 2, 'Bemerkung' => 'SDS-Typ tüümlige', 'Genus' => '', 'Konzepte' => [], 'PTyp' => 1087, 'MTyp' => NULL, 'Id_Tokengruppe' => 'NEW0']
				],
				'global' =>
				[
						'groups' => [
							['Genus' => '', 'Bemerkung' => 'adverbiale Bezeichnung SDS-Typ z tüümlige', 'PTyp' => 1153, 'Konzepte' => [44], 'MTyp' => NULL]
						],
						'ptypes' => [
							['Beta' => 'gurkensaft', 'Quelle' => 'SDS']
						],
						'mtypes' => []
				]
			]
				
		],
			
			[
				'AIS',
				'\\\\*\\\\? la  malga',
				['class' => 'B', 'concepts' => [5], 'notes' => '', 'lang' => 'rom'],
				[
					'tokens' =>
					[
						['Token' => '\\\\*\\\\?', 'Trennzeichen' => ' ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 1, 'Bemerkung' => '', 'Genus' => '', 'Konzepte' => [779], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL],
						['Token' => 'la', 'Trennzeichen' => ' ', 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 2, 'Bemerkung' => '', 'Genus' => 'f', 'Konzepte' => [699], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL],
						['Token' => 'malga', 'Trennzeichen' => NULL, 'Ebene_1' => 1, 'Ebene_2' => 1, 'Ebene_3' => 3, 'Bemerkung' => '', 'Genus' => 'f', 'Konzepte' => [5], 'Id_Tokengruppe' => NULL, 'MTyp' => NULL, 'PTyp' => NULL],
					],
					'global' =>
					[
						'groups' => [],
						'mtypes' => [],
						'ptypes' => []
					]
				]
			],
	];
	
	foreach ($tests as $index => $test){
		$tokenizer = va_create_tokenizer($test[0]);
		
		$res = $tokenizer->tokenize($test[1], $test[2]);
		
		$comp = va_deep_assoc_array_compare($test[3], $res);
		if($comp === true){
			echo 'Test ' . $index . ' successfull!<br />';
		}
		else {
			echo 'Error "' . $comp . '":<br />Result:<br />' . va_array_to_html_string($res) . '<br />Expected:<br />' . va_array_to_html_string($test[3]) . '<br /><br />';
		}
	}
}

function va_check_tokenizer (){
	echo va_tokenize_test();
	echo '<br /><br />';
	echo '<input type="button" value="Check tokenized data" id="va_tok_check_button" />';
	echo '<div id="va_tok_check_result"></div>';
	
	?>
	<script type="text/javascript">
		jQuery(function (){
			jQuery("#va_tok_check_button").click(function (){
				jQuery("#va_tok_check_result").html("");
				jQuery.post(ajaxurl, {
					"action": "va",
					"namespace": "util",
					"query" : "check_tokenizer"
				},
				function (response){
					jQuery("#va_tok_check_result").html(response);
				});
			});
		});
	</script>
	
	<?php
}

function va_tokenization_test_ajax (&$db) {
	$errorFound = false;
	$index = 0;
	$tokenizer = NULL;
	$lastSource = '';
	$limit = 500;
	
	$records = $db->get_results('
			SELECT Id_Aeusserung, Aeusserung, Klassifizierung, a.Bemerkung, Erhebung, Sprache
			FROM Aeusserungen a JOIN Informanten USING (Id_Informant)
			WHERE Tokenisiert AND Not Nicht_Verifizieren AND (Verifiziert_Am IS NULL OR Geaendert_Am > Verifiziert_Am) AND Erhebung = "CROWD"
			ORDER BY Erhebung ASC, Id_Stimulus, Id_Informant
			LIMIT '  . $limit, ARRAY_A);
	
	while ($index < count($records)){
			
		$next_record = $records[$index];
		
		$tokensDB = va_tokenization_get_token_data_from_db($next_record['Id_Aeusserung']);
		$concepts = array_map('intval', $db->get_col($db->prepare('SELECT Id_Konzept FROM VTBL_Aeusserung_Konzept WHERE Id_Aeusserung = %d', $next_record['Id_Aeusserung'])));
		
		if($tokenizer == NULL || $lastSource != $next_record['Erhebung']){
			$tokenizer = va_create_tokenizer($next_record['Erhebung']);
			$lastSource = $next_record['Erhebung'];
		}
		
		$tokensNew = va_tokenization_data_explicit(
				$tokenizer->tokenize(
						$next_record['Aeusserung'],
						['class' => $next_record['Klassifizierung'], 'concepts' => $concepts, 'notes' => $next_record['Bemerkung'], 'lang' => $next_record['Sprache']]));
		
		if(count($tokensDB) == count($tokensNew)){
			foreach ($tokensDB as $i => $token){
				if($token['Id_Tokengruppe'] && count($token['Konzepte']) == 1){
					if(count($tokensNew[$i]['Konzepte']) == 1){
						$conceptDB = $token['Konzepte'][0];
						$conceptNew = $tokensNew[$i]['Konzepte'][0];
						
						if($conceptDB != $conceptNew){
							$gramDB = $db->get_var($db->prepare('SELECT Grammatikalisch FROM Konzepte WHERE Id_Konzept = %d', $conceptDB));
							$gramNew = $db->get_var($db->prepare('SELECT Grammatikalisch FROM Konzepte WHERE Id_Konzept = %d', $conceptNew));
							
							if($gramDB == '1' && $gramNew == '1'){
								$tokensDB[$i]['Konzepte'] = ['GRAM'];
								$tokensNew[$i]['Konzepte'] = ['GRAM'];
								$tokensNew[$i]['Genus'] = '';
								if(count($tokensDB) > $i + 1)
									$tokensNew[$i + 1]['Genus'] = '';
							}
						}
					}
					else {
						//Remove manuelly added concepts for parts of groups
						$tokensDB[$i]['Konzepte'] = [];
					}
				}
			}
			
			$added = false;
			foreach ($tokensDB as $i => $token){
				if($token['Ebene_3'] != 0){
					if($added)
						$tokensDB[$i]['Id_Tokengruppe']['Bemerkung'] = $tokensNew[$i]['Id_Tokengruppe']['Bemerkung'];
					continue;	
				}
				
				if($token['Id_Tokengruppe'] && $token['Id_Tokengruppe']['Bemerkung'] == '' && $tokensNew[$i]['Id_Tokengruppe']['Bemerkung'] != ''){
					$sql = $db->prepare('
						UPDATE Tokengruppen tg 
						SET Bemerkung = %s 
						WHERE Id_Tokengruppe = (SELECT DISTINCT Id_Tokengruppe FROM Aeusserungen JOIN Tokens USING (Id_Aeusserung) WHERE Id_Aeusserung = %d and Tokens.Ebene_1 = %d and Tokens.Ebene_2 = %d and Tokens.Ebene_3 = %d)',
							$tokensNew[$i]['Id_Tokengruppe']['Bemerkung'], $next_record['Id_Aeusserung'], $token['Ebene_1'], $token['Ebene_2'], $token['Ebene_3']);
					echo $sql . '<br>';
					$db->query($sql);
					
					$tokensDB[$i]['Id_Tokengruppe']['Bemerkung'] = $tokensNew[$i]['Id_Tokengruppe']['Bemerkung'];
					$added = true;
				}
				else {
					$added = false;
				}
			}
		}

		$comp = va_deep_assoc_array_compare($tokensDB, $tokensNew);
		
		if($comp !== true){
			echo $index . ' rows ok.<br><br>';
			
			echo 'Id_Record: ' . $next_record['Id_Aeusserung'] . ' --- ' . $comp . '<br>';
			
			echo 'Tokenized:<br>';
			echo va_array_to_html_string($tokensNew, 0);
			echo '<br>';
			echo 'DB:<br>';
			echo va_array_to_html_string($tokensDB, 0);
			echo '<br>';
			$errorFound = true;
			return;
		}
		else {
			$db->query($db->prepare('UPDATE Aeusserungen SET Verifiziert_Am = NOW() WHERE Id_Aeusserung = %d', $next_record['Id_Aeusserung']));
		}
		
		$index++;
	}
	echo $index . ' records ok.<br />';
}

function va_tokenization_data_explicit ($data){

	foreach ($data['tokens'] as $index => $token){
		if(isset($token['MTyp'])){
			$data['tokens'][$index]['MTyp'] = va_tokenization_mtype_id_to_data($token['MTyp']);
		}
		
		if(isset($token['PTyp'])){
			$data['tokens'][$index]['PTyp'] = va_tokenization_mtype_id_to_data($token['PTyp']);
		}
		
		if(isset($token['Id_Tokengruppe'])){
			$data['tokens'][$index]['Id_Tokengruppe'] = $data['global']['groups'][intval(substr($token['Id_Tokengruppe'], 3))];
		}
	}
	return $data['tokens'];
}

function va_tokenization_get_token_data_from_db ($id_record){
	global $va_xxx;
	
	$tokens = $va_xxx->get_results($va_xxx->prepare('
		SELECT Token, Trennzeichen, Ebene_1, Ebene_2, Ebene_3, t.Bemerkung, Genus, GROUP_CONCAT(Id_Konzept) AS Konzepte, Id_phon_Typ AS PTyp, Id_morph_Typ AS MTyp, Id_Tokengruppe
		FROM 
			Tokens t
			JOIN Stimuli s USING (Id_Stimulus)
			LEFT JOIN VTBL_Token_Konzept USING (Id_Token) 
			LEFT JOIN VTBL_Token_phon_Typ v1 ON v1.Id_Token =  t.Id_Token AND v1.Quelle = s.Erhebung
			LEFT JOIN VTBL_Token_morph_Typ v2 ON v2.Id_Token =  t.Id_Token AND v2.Quelle = s.Erhebung
		WHERE Id_Aeusserung = %d
		GROUP BY t.Id_Token
		ORDER BY Ebene_1 ASC, Ebene_2 ASC, Ebene_3 ASC', $id_record), ARRAY_A);
	
	foreach ($tokens as $key => $token){
		if($tokens[$key]['Konzepte']){
			$tokens[$key]['Konzepte'] = array_map('intval', explode(',', $token['Konzepte']));
		}
		else {
			$tokens[$key]['Konzepte'] = [];
		}
		
		$token = $tokens[$key];
		
		$tokens[$key]['Ebene_1'] = intval($tokens[$key]['Ebene_1']);
		$tokens[$key]['Ebene_2'] = intval($tokens[$key]['Ebene_2']);
		$tokens[$key]['Ebene_3'] = intval($tokens[$key]['Ebene_3']);
		
		if($token['Id_Tokengruppe']){
			$tokens[$key]['Id_Tokengruppe'] = va_tokenization_token_group_id_to_data($token['Id_Tokengruppe']);
		}
		
		if($token['MTyp']){
			$tokens[$key]['MTyp'] = va_tokenization_mtype_id_to_data($token['MTyp']);
		}
		
		if($token['PTyp']){
			$tokens[$key]['PTyp'] = va_tokenization_ptype_id_to_data($token['PTyp']);
		}
	}
	
	return $tokens;
}

function va_tokenization_token_group_id_to_data ($id_group){
	global $va_xxx;
	
	$res = $va_xxx->get_row($va_xxx->prepare('
		SELECT tg.Genus, tg.Bemerkung, GROUP_CONCAT(DISTINCT Id_Konzept) AS Konzepte, Id_phon_Typ AS PTyp, Id_morph_Typ AS MTyp
		FROM
			Tokengruppen tg
			JOIN Tokens USING (Id_Tokengruppe)
			JOIN Stimuli s USING (Id_Stimulus)
			LEFT JOIN VTBL_Tokengruppe_phon_Typ v1 ON v1.Id_Tokengruppe =  tg.Id_Tokengruppe AND v1.Quelle = s.Erhebung
			LEFT JOIN VTBL_Tokengruppe_morph_Typ v2 ON v2.Id_Tokengruppe =  tg.Id_Tokengruppe AND v2.Quelle = s.Erhebung
			LEFT JOIN VTBL_Tokengruppe_Konzept v3 ON v3.Id_Tokengruppe = tg.Id_Tokengruppe
		WHERE tg.Id_Tokengruppe = %d
		GROUP BY tg.Id_Tokengruppe
		ORDER BY Ebene_1 ASC, Ebene_2 ASC, Ebene_3 ASC
		', $id_group), ARRAY_A);
	
	if($res['Konzepte']){
		$res['Konzepte'] = array_map('intval', explode(',', $res['Konzepte']));
	}
	else {
		$res['Konzepte'] = [];
	}
	
	return $res;
}

function va_tokenization_ptype_id_to_data ($id_type){
	global $va_xxx;
	
	return $va_xxx->get_row($va_xxx->prepare('SELECT Beta, Quelle FROM phon_Typen WHERE Id_phon_Typ = %d', $id_type), ARRAY_A);
}

function va_tokenization_mtype_id_to_data ($id_type){
	global $va_xxx;
	
	return $va_xxx->get_row($va_xxx->prepare('SELECT Orth, Genus, Quelle FROM morph_Typen WHERE Id_morph_Typ = %d', $id_type), ARRAY_A);
}
?>