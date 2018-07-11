<?php

//All AJAX calls of the VA plugin are delegated in this file
add_action('wp_ajax_va', 'va_ajax_handler');
add_action('wp_ajax_nopriv_va', 'va_ajax_handler');

include_once('va_ajax_typification.php');
include_once('va_ajax_transcription.php');
include_once('va_ajax_edit_glossary.php');
include_once('va_ajax_concept_tree.php');
include_once('va_ajax_overview.php');

function va_ajax_handler (){
	global $va_xxx;
	global $admin;
	global $va_mitarbeiter;
	
	$db = $va_xxx;
	
	if(isset($_REQUEST['dbname']))
		$db->select($_REQUEST['dbname']);
	else if (isset($_REQUEST['db'])){
		$db->select('va_' . $_REQUEST['db']);
	}
	
	$intern = $admin || $va_mitarbeiter;
	switch($_REQUEST['namespace']){
		
		//Typification tool
		case 'typification':
			if(!current_user_can('va_typification_tool_read'))
				break;
			
			va_ajax_typification($db);
			break;
		
		//Transcription tool
		case 'transcription':
			if(!current_user_can('va_transcription_tool_read'))
				break;
			
			va_ajax_transcription($db);
		break;
			
		//Edit glossary
		case 'edit_glossary':
			if(!current_user_can('glossar'))
				break;

			va_ajax_edit_glossary($db);
		break;
		
		//Edit comments
		case 'edit_comments':
			if(!current_user_can('im_edit_comments'))
				break;

			switch($_POST['query']){
				case 'updateList':
					echo va_filter_comments_for_editing($db, $_POST['filter']);
					break;
					
				case 'getEntry':
					$data_comm = va_get_comment_edit_data($db, $_POST['id']);

					if($data_comm === false)
						echo 'Locked';
					else
						echo json_encode($data_comm);
					break;
					
				case 'removeLock':
					va_remove_comment_lock($db, $_POST['id']);
					break;
					
				case 'saveComment':
					if(
					va_save_comment($db, $_POST['id'], stripslashes($_POST['content']), $_POST['authors'], $_POST['internal'], $_POST['ready']) &&
					(!isset($_POST['lang']) || va_save_comment_translation($db, $_POST['id'], $_POST['lang'], stripslashes($_POST['translation']), $_POST['translators'], $_POST['correctors'])))
						echo 'success';
					break;
					
				case 'getTranslation':
					echo json_encode(va_get_comment_translation_data($db, $_POST['id'], $_POST['lang']));
					break;
					
				case 'getNoCommentsList':
					echo va_get_missing_comments_options($db, $_POST['type']);
					break;
			}
		break;
		
		//Concept tree
		case 'concept_tree':
			if(!current_user_can('va_concept_tree_read'))
				break;
			
			va_ajax_concept_tree($db);
		break;
		
		//Overview page
		case 'overview':
			if(!current_user_can('va_see_progress_page'))
				break;
					
			va_ajax_overview($db);
			break;
		
		//Media
		case 'media':
			if(!$intern)
				break;
				
			switch ($_REQUEST['query']){
				case 'update':
					if($_REQUEST['field'] == 'Georeferenzierung'){
						if(is_array($_REQUEST['value'])){
							$stmt = $db->prepare('UPDATE Medien SET Georeferenzierung = POINT(%f, %f) WHERE Id_Medium = %d', $_REQUEST['value'][0], $_REQUEST['value'][1], $_REQUEST['id']);
						}
						else {
							$stmt = $db->prepare('UPDATE Medien SET Georeferenzierung = NULL WHERE Id_Medium = %d', $_REQUEST['id']);
						}
					}
					else if($_REQUEST['field'] == 'Abkuerzung_Bibliographie'){
						if($_REQUEST['value']){
							$stmt = $db->prepare('UPDATE Medien SET Abkuerzung_Bibliographie = %s WHERE Id_Medium = %d', $_REQUEST['value'], $_REQUEST['id']);
						}
						else {
							$stmt = $db->prepare('UPDATE Medien SET Abkuerzung_Bibliographie = NULL WHERE Id_Medium = %d', $_REQUEST['id']);
						}
					}
					else if ($_REQUEST['field'] == 'ADD_CONCEPT'){
						$stmt = $db->prepare('INSERT INTO VTBL_Medium_Konzept (Id_Medium, Id_Konzept) VALUES (%d, %d)', $_REQUEST['id'], $_REQUEST['value']);
					}
					else if ($_REQUEST['field'] == 'DELETE_CONCEPT'){
						$stmt = $db->prepare('DELETE FROM VTBL_Medium_Konzept WHERE Id_Medium = %d AND Id_Konzept = %d', $_REQUEST['id'], $_REQUEST['value']);
					}
					else if ($_REQUEST['field'] == 'ADD_TYPE'){
						$stmt = $db->prepare('INSERT INTO VTBL_Medium_Typ (Id_Medium, Id_morph_Typ) VALUES (%d, %d)', $_REQUEST['id'], $_REQUEST['value']);
					}
					else if ($_REQUEST['field'] == 'DELETE_TYPE'){
						$stmt = $db->prepare('DELETE FROM VTBL_Medium_Typ WHERE Id_Medium = %d AND Id_morph_Typ = %d', $_REQUEST['id'], $_REQUEST['value']);
					}
					else {
						//For security reasons you should probably check the value of 'field', but since this request is only fulfilled for VA co-workers,
						//this is omitted
						$stmt = $db->prepare('UPDATE Medien SET ' . $_REQUEST['field'] . ' = %s WHERE Id_Medium = %d', $_REQUEST['value'], $_REQUEST['id']);
					}
					$db->query($stmt);
					break;
			}
			break;
			
		//Translations
		case 'translation':
			if(!$intern || strlen($_REQUEST['lang']) > 1)
				break;
			
			switch ($_REQUEST['query']){
				case 'update':
					if($db->update('Uebersetzungen', 
						array(
							'Begriff_' . $_REQUEST['lang'] => $_REQUEST['value']
						),
						array(
							'Schluessel' => $_REQUEST['key']
						)) === false){
						echo $db->last_error;
					}
					else {
						echo 'success';
					}
					break;
					
				case "get_list":
					va_echo_translation_list();
					break;
			}
			break;
			
		//IPA conversion
		case 'ipa':
			if(!$intern)
				break;

			switch ($_REQUEST['query']){
				case 'get_tokens':
					$tokens = $va_xxx->get_col($va_xxx->prepare("
						SELECT distinct Token 
						FROM Tokens JOIN Stimuli USING (ID_Stimulus) LEFT JOIN VTBL_Token_Konzept USING (Id_Token) 
						WHERE Erhebung = %s" . ($_POST['all'] === 'true'? '' : " AND IPA = ''") . " AND Token != '' AND (Id_Konzept is null or Id_Konzept != 779)", $_POST['source']), 0);
					
					echo json_encode($tokens);
					break;
					
				case 'compute':
					$tokens = json_decode(stripslashes($_POST['data']));
					$missing_chars = array();
					$quelle = $_POST['source'];
					$transformations = '';
					$errors = '';
					
					$akzente = $va_xxx->get_results("SELECT Beta, IPA FROM Codepage_IPA WHERE Art = 'Akzent' AND Erhebung = '$quelle'", ARRAY_N);
					$vokale = $va_xxx->get_var("SELECT group_concat(DISTINCT SUBSTR(Beta, 1, 1) SEPARATOR '') FROM Codepage_IPA WHERE Art = 'Vokal' AND Erhebung = '$quelle'", 0, 0);
					$numComplete = 0;
					
					foreach ($tokens as $token){
						$complete = true;
						$result = '';
						$akzentExplizit = false;
						$indexLastVowel = false;
						
						foreach ($token as $index => $character) {
							foreach ($akzente as $akzent) {
								$ak_qu = preg_quote($akzent[0], '/');
								$character = preg_replace_callback('/([' . $vokale . '][^' . $ak_qu . 'a-zA-Z]*)' . $ak_qu . '/', function ($matches) use (&$result, $akzent, &$akzentExplizit){
									$result .= $akzent[1];
									$akzentExplizit = true;
									return $matches[1];
								}, $character);
							}
							
							$ipa = $va_xxx->get_var("SELECT IPA from Codepage_IPA WHERE Erhebung = '" . ($quelle == 'ALD-I'? 'ALD-II': $quelle) . "' AND Beta = '" . addcslashes($character, "\'") . "' AND IPA != ''");
							if($ipa){
								$result .= $ipa;
								
								if(strpos($vokale, $character[0]) !== false){
									$indexLastVowel = mb_strlen($result) - mb_strlen($ipa);
								}
							}
							else {
								if(!in_array($character, $missing_chars)){
									$missing_chars[] = $character;
									$errors .= "Eintrag \"$character\" fehlt fuer \"" . ($quelle == 'ALD-I'? 'ALD-II': $quelle) . "\"!\n";
								}
								$complete = false;
							}
						}
						
						//Akzent auf letzer Silbe, falls nicht gesetzt
						$addAccent = !$akzentExplizit && $indexLastVowel !== false && ($quelle === 'ALP' || $quelle === 'ALJA' || $quelle === 'ALL');
						
						if($addAccent){
							$result = mb_substr($result, 0, $indexLastVowel) . $akzente[0][1] . mb_substr($result, $indexLastVowel);
						}
						
						
						if($complete){
							$transformations .= implode('', $token) . ' -> ' . $result . ($addAccent? ' (Akzent hinzugefügt)' : '') . "\n";
							$va_xxx->query("UPDATE Tokens SET IPA = '" . addslashes($result) . "', Trennzeichen_IPA = (SELECT IPA FROM Codepage_IPA WHERE Art = 'Trennzeichen' AND Beta = Trennzeichen AND Erhebung = '$quelle')
						 WHERE EXISTS (SELECT * FROM Stimuli WHERE Stimuli.Id_Stimulus = Tokens.Id_Stimulus AND Erhebung = '$quelle') AND Token = '" . addslashes(implode('', $token)) . "'");
							$numComplete++;
						}
					}
					
					echo json_encode(array($transformations, $errors, $numComplete));
					break;
			}
			
			break;
			
		//Util tools
		case 'util':
			//TODO maybe better user control
			if(!$intern && !current_user_can('va_transcription_tool_write') && !current_user_can('va_typification_tool_write') && !current_user_can('glossar'))
				break;
			
			switch ($_REQUEST['query']){
				case 'addLock':
					echo va_check_lock($db, $_POST);
				break;
					
				case 'removeLock':
					$db->query($db->prepare("DELETE FROM Locks where (Wert = %s AND Tabelle = %s AND Gesperrt_von = %s) or hour(timediff(Zeit,now())) > 0", $_REQUEST['value'], $_REQUEST['table'], wp_get_current_user()->user_login));
					echo 'success';
				break;
				
				case 'removeAllLocks':
					$db->query($db->prepare("DELETE FROM Locks where (Tabelle = %s AND Gesperrt_von = %s) or hour(timediff(Zeit,now())) > 0", $_REQUEST['table'], wp_get_current_user()->user_login));
					echo 'success';
				break;
				
				case 'markTodo':
				    $db->update('Todos', ['Fertig' => $_POST['marked'] == '1'? current_time('mysql'): null], ['Id_Todo' => $_POST['id']]);
				    echo 'success';
			   break;
			   
				case 'addTodo':
				    $text = stripslashes($_POST['text']);
				    $insert_array = ['Todo' => $text, 'Kuerzel' => $_POST['owner'], 'Kontext' => $_POST['context']];
				    if($_POST['parent'] != -1){
				        $insert_array['Ueber'] = $_POST['parent'];
				    }
				    $db->insert('Todos', $insert_array);
				    
				    $options = [
				        'Id_Todo' => $db->insert_id,
				        'Todo' => $text,
				        'Ueber' =>  $_POST['parent'] == -1? null: $_POST['parent'],
				        'Fertig' => null,
				        'Blockiert' => false,
				    	'Kontext' => $_POST['context']
				    ];
				    
				    $res = ['row' => va_get_todo_row($options)];
				    
				    if ($_POST['parent'] == -1){
				        $res['option'] = va_get_todo_parent_option($options);
				        $res['context'] = $_POST['context'];
				    }
				    
				    echo json_encode($res);
			     break;
			     
				 case 'get_print_overlays':
				     $db->select('va_xxx');
				    echo json_encode($db->get_col('SELECT AsText(Polygone_Vereinfacht.Geodaten) FROM Orte JOIN Polygone_Vereinfacht USING (Id_Ort) WHERE Id_Kategorie = 63 AND Epsilon = 0.003'));
				 break;
				 
				 case 'checkTokens':
				 	va_check_tokens_call($db);
				 	break;
				 	
				 case 'check_tokenizer':
				 	va_tokenization_test_ajax($db);
				 	break;
			}
		break;
		
		case 'record_input':
			if(!$intern)
				break;
			
			switch ($_REQUEST['query']){
				case 'save':
					echo va_update_record(
						$_POST['id_stimulus'], 
						$_POST['id_informant'], 
						$_POST['id_aeusserung'], 
						stripslashes($_POST['value']), 
						stripslashes($_POST['notes']), 
						$_POST['id_konzept'], 
						$_POST['classification'],
						substr($_POST['lang'], 0, 1),
						(isset($_POST['returnType'])? $_POST['returnType'] : NULL)
					);
					break;
			}
			break;
			
		case 'get_codepage':
			echo va_get_codepage_data($_POST['atlas']);
		break;
		
		case 'admin_table':
			if(!$admin)
				break;
			
			switch($_POST['query']){
				case 'update':
					$content = stripslashes($_POST['content']);
					$va_xxx->update('admin', array ('Beschreibung' => $content), array ('Tabelle' => $_POST['table']));
					parseSyntax($content, true, true);
					echo $content;
					break;
					
				case 'select':
					echo $va_xxx->get_var($va_xxx->prepare('SELECT Beschreibung FROM admin WHERE Tabelle = %s', $_POST['table']));
					break;
			}
			break;
			
		case 'comments':
			switch($_POST['query']){
				case 'getDetails':
					global $Ue;
					$id = substr($_POST['id'], 1);
					$lang = strtoupper($_POST['lang']);
					
					$result = new IM_Result();
					
					switch ($_POST['id'][0]){
						case 'L':
							$_POST['filter']['subElementCategory'] = 1; //Concept
							$where_clause = $db->prepare("Id_Instance IN (SELECT DISTINCT Id_Instance FROM Z_Ling WHERE Type_Kind = 'L' AND Id_Type = %d)", $id);
							va_create_result_object($where_clause, $lang, $result, $Ue, $db);
						break;
					}
					
					$result->addComments($_POST['key'], $db);
	
					echo $result->createResultString();
					break;
			}
		break;
		
		case 'tokenize':
			if(!$intern)
				break;
				
			switch ($_POST['query']){
				case 'updateTable':
					echo va_records_for_stimulus($_POST['id_stimulus']);
				break;
				
				case 'tokenize':
					echo va_tokenize_records($_POST['id_stimulus'], $_POST['preview'] == 'true'? true: false); //in tokenize.php
					break;
			}
		break;
		
		case 'bsa_import':
			if(!$intern)
				break;

			switch ($_POST['query']){
				case 'getRecords':
					echo json_encode(va_get_bsa_records($_POST['stimulus'], $_POST['concept'], $db));
					break;
					
				case 'getOptions':
					echo va_bsa_get_options($_POST['filter'], $_POST['ignoreEmpty'], $db);
					break;
					
				case 'import':
					echo va_bsa_import_records($_POST['data'], $db);
					break;
			}
			break;
		
		case 'kml':
			echo pointListToKML($_POST['sql']);
		break;
		
		//Test stuff
		case 'test':
			switch ($_REQUEST['query']){
				case 'getPVA_BSA_Tokens':
					echo json_encode($db->get_col($db->prepare('SELECT lautschrift FROM PVA_BSA.Belege WHERE Lautschrift IS NOT NULL LIMIT %d, 1000', $_REQUEST['index'])));
					break;
					
				case 'tagung':
					va_kit_return_text($_POST['id']);
					break;
			}
			break;
		
		default:
			echo 'No namespace given!';
	}
	die;
}


/**
 * Returns a string with %d's for integer list
 */
function keyPlaceholderList ($arr){
	return '(' . implode(',', array_fill(0, count($arr), '%d')) . ')';
}

function va_check_lock (&$db, $data){
    ob_start();
    $db->query($db->prepare("DELETE FROM Locks where (Wert = %s AND Tabelle = %s AND Gesperrt_von = %s) or hour(timediff(Zeit,now())) > 0", $data['value'], $data['table'], wp_get_current_user()->user_login));
    if($db->insert('Locks', array('Tabelle' => $data['table'], 'Gesperrt_von' => wp_get_current_user()->user_login, 'Wert' => $data['value']))){
        $res = 'success';
    }
    else {
        $res = 'locked';
    }
    ob_end_clean();
    return $res;
}
?>