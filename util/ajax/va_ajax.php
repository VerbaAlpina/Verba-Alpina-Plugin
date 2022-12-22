<?php

//All AJAX calls of the VA plugin are delegated in this file
add_action('wp_ajax_va', 'va_ajax_handler');
add_action('wp_ajax_nopriv_va', 'va_ajax_handler');

include_once('va_ajax_typification.php');
include_once('va_ajax_edit_glossary.php');
include_once('va_ajax_concept_tree.php');
include_once('va_ajax_lex_alp.php');
include_once('va_ajax_versions.php');
include_once('va_ajax_overview.php');
include_once('va_ajax_bulk_download.php');

function va_ajax_handler (){
	global $va_xxx;
	global $admin;
	global $va_mitarbeiter;
	
	$page_count = 20; //Used for pagination of select2 ajax calls
	
	$db = $va_xxx; //TODO Warum hiern va_xxx und nicht vadb???? Macht Probleme, z.B. in va_get_external_id_for_stimulus oder auch in va_version_summary
	
	if(isset($_REQUEST['dbname']))
		$db->select($_REQUEST['dbname']);
	else if (isset($_REQUEST['db'])){
		$db->select('va_' . $_REQUEST['db']);
	}
	
	$intern = $admin || $va_mitarbeiter;

	switch($_REQUEST['namespace']){
	    case 'token_ops':
	        if($_POST['stage'] == 'getTokens'){
	            switch ($_POST['type']){
	                case 'original':
	                    $tokens = $db->get_results("
					SELECT distinct Token, Erhebung FROM Tokens LEFT JOIN VTBL_Token_Konzept USING (Id_Token) LEFT JOIN Konzepte USING (Id_Konzept) JOIN Stimuli USING (ID_Stimulus) JOIN Bibliographie ON Erhebung = Abkuerzung
					WHERE VA_Beta
						AND Original = '' AND Token != '' AND (Id_Konzept is null or Id_Konzept != 779)", ARRAY_N);
	                    break;
	                    
	                case 'bsa':
	                    $tokens = $db->get_results("SELECT distinct Aeusserung, Erhebung FROM Aeusserungen JOIN Stimuli USING (ID_Stimulus) WHERE Erhebung = 'BSA' AND Bemerkung NOT like '%BayDat-Transkription%'", ARRAY_N);
	                    break;
	                    
	                default:
	                    $tokens = array();
	            }
	            echo json_encode($tokens);
	       }
	       break;
	    
		//Typification tool
		case 'typification':
			if(!current_user_can('va_typification_tool_read'))
				break;
			
			va_ajax_typification($db);
			break;
			
		//Edit glossary
		case 'edit_glossary':
			if(!current_user_can('va_glossary'))
				break;

			va_ajax_edit_glossary($db);
		break;
		
		//Lexicon Alpinum
		case 'lex_alp':
			va_ajax_lex_alp($db);
		break;

		//VA Versions Page
		case 'va_versions':
			va_ajax_versions($db);
		break;
		
		//Edit comments
		case 'edit_comments':
			if(!current_user_can('va_glossary'))
				break;

			switch($_POST['query']){
				case 'updateList':
					echo va_filter_comments_for_editing($db, $_POST['filter']);
					break;
					
				case 'getEntry':
					$data_comm = va_get_comment_edit_data($db, $_POST['id']);

					if($data_comm === false)
						echo 'Locked';
					else {
						$data_comm['url'] = va_get_comments_link($_POST['id']);
						echo json_encode($data_comm);
					}
					break;
					
				case 'removeLock':
					va_remove_comment_lock($db, $_POST['id']);
					break;
					
				case 'saveComment':
					if(!current_user_can('va_glossary_translate') && !current_user_can('va_glossary_edit')){
						break;
					}
					
					$success = true;
					if(current_user_can('va_glossary_edit')){
						$success = va_save_comment($db, $_POST['id'], stripslashes($_POST['content']), $_POST['authors'], $_POST['internal'], $_POST['ready']);
					}
					
					if(isset($_POST['lang'])){
						$success = $success && va_save_comment_translation($db, $_POST['id'], $_POST['lang'], stripslashes($_POST['translation']), $_POST['translators'], $_POST['correctors']);
					}
					
					if($success)
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

			//Bulk Download
		case 'bulk_download':
			 va_ajax_bulk_download($db);
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
							'Begriff_' . $_REQUEST['lang'] => stripslashes($_REQUEST['value'])
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
				case 'compute':
					try {
						$quelle = va_filter_source_for_ipa($_POST['source']);
						
						$parser = new VA_BetaParser($quelle);
						
						$tokens = $_POST['data'];
						
						$transformations = '';
						$errors = '';
						$numComplete = 0;
						
						$warnings = [];
						

						foreach ($tokens as $token){
							$token = stripslashes($token);
							list($chars, $valid) = $parser->split_chars($token);
							
							if(!$valid){
								$errors .= 'Record not valid: ' . $token . ' -> ' . html_entity_decode($chars) . "\n";
							}
							else {
								$res = $parser->convert_to_ipa($chars);
								if($res['string']){
									$transformations .= $token . ' -> ' . $res['string'] . "\n";
									$va_xxx->query("UPDATE Tokens SET IPA = '" . addslashes($res['string']) . "', Trennzeichen_IPA = (SELECT IPA FROM Codepage_IPA WHERE Art = 'Trennzeichen' AND Beta = Trennzeichen AND Erhebung = '$quelle') WHERE EXISTS (SELECT * FROM Stimuli WHERE Stimuli.Id_Stimulus = Tokens.Id_Stimulus AND Erhebung = '$quelle') AND Token = '" . addslashes($token) . "'");
									$numComplete++;
								}
								else {
									foreach ($res['output'] as $missing){
										if (!in_array($missing[1], $warnings)){
											$warnings[] = $missing[1];
										}
									}
								}
								
							}								
						}
						
						$errors .= implode("\n", $warnings) . "\n";

						echo json_encode([$transformations, $errors, $numComplete, true]);
					}
					catch (Exception $e){
						echo json_encode(['', $e->getMessage(), 0, false]);
					}
				break;
				
				case 'get_tokens':
					$tokens = $va_xxx->get_col($va_xxx->prepare("
						SELECT distinct Token 
						FROM Tokens JOIN Stimuli USING (ID_Stimulus) LEFT JOIN VTBL_Token_Konzept USING (Id_Token) 
						WHERE Erhebung = %s" . ($_POST['all'] === 'true'? '' : " AND IPA = ''") . " AND Token != '' AND (Id_Konzept is null or Id_Konzept != 779)", $_POST['source']), 0);
					
					echo json_encode($tokens);
					break;
					
				// case 'compute':
					// $tokens = json_decode(stripslashes($_POST['data']));
					// $missing_chars = array();
					// $quelle = $_POST['source'];
					// $transformations = '';
					// $errors = '';
					
					// $akzente = $va_xxx->get_results("SELECT Beta, IPA FROM Codepage_IPA WHERE Art = 'Akzent' AND Erhebung = '$quelle'", ARRAY_N);
					// $vokale = $va_xxx->get_var("SELECT group_concat(DISTINCT SUBSTR(Beta, 1, 1) SEPARATOR '') FROM Codepage_IPA WHERE Art = 'Vokal' AND Erhebung = '$quelle'", 0, 0);
					// $numComplete = 0;
					
					// foreach ($tokens as $token){
						// $complete = true;
						// $result = '';
						// $akzentExplizit = false;
						// $indexLastVowel = false;
						
						// foreach ($token as $character) {
							// foreach ($akzente as $akzent) {
								// $ak_qu = preg_quote($akzent[0], '/');
								// $character = preg_replace_callback('/([' . $vokale . '][^' . $ak_qu . 'a-zA-Z]*)' . $ak_qu . '/', 
									// function ($matches) use (&$result, $akzent, &$akzentExplizit){
										// $result .= $akzent[1];
										// $akzentExplizit = true;
										// return $matches[1];
								// }, $character);
							// }
							
							// $ipa = $va_xxx->get_var("SELECT IPA from Codepage_IPA WHERE Erhebung = '" . ($quelle == 'ALD-I'? 'ALD-II': $quelle) . "' AND Beta = '" . addcslashes($character, "\'") . "' AND IPA != ''");
							// if($ipa){
								// $result .= $ipa;
								
								// if(strpos($vokale, $character[0]) !== false){
									// $indexLastVowel = mb_strlen($result) - mb_strlen($ipa);
								// }
							// }
							// else {
								// if(!in_array($character, $missing_chars)){
									// $missing_chars[] = $character;
									// $errors .= "Eintrag \"$character\" fehlt fuer \"" . ($quelle == 'ALD-I'? 'ALD-II': $quelle) . "\"!\n";
								// }
								// $complete = false;
							// }
						// }
						
						//Akzent auf letzer Silbe, falls nicht gesetzt
						// $addAccent = !$akzentExplizit && $indexLastVowel !== false && ($quelle === 'ALP' || $quelle === 'ALJA' || $quelle === 'ALL');
						
						// if($addAccent){
							// $result = mb_substr($result, 0, $indexLastVowel) . $akzente[0][1] . mb_substr($result, $indexLastVowel);
						// }
						
						
						// if($complete){
							// $transformations .= implode('', $token) . ' -> ' . $result . ($addAccent? ' (Akzent hinzugefügt)' : '') . "\n";
							// $va_xxx->query("UPDATE Tokens SET IPA = '" . addslashes($result) . "', Trennzeichen_IPA = (SELECT IPA FROM Codepage_IPA WHERE Art = 'Trennzeichen' AND Beta = Trennzeichen AND Erhebung = '$quelle')
						 // WHERE EXISTS (SELECT * FROM Stimuli WHERE Stimuli.Id_Stimulus = Tokens.Id_Stimulus AND Erhebung = '$quelle') AND Token = '" . addslashes(implode('', $token)) . "'");
							// $numComplete++;
						// }
					// }
					
					// echo json_encode(array($transformations, $errors, $numComplete));
					// break;
			}
			
			break;
			
		case 'drg':
		    if(!$intern)
		        break;

	        switch ($_REQUEST['query']){
	            case 'get_letter_data':
	                echo va_drg_get_letter_data($_POST['letter']);
					break;
	            case 'set_irrelevant':
	                echo va_drg_set_irrelevant($_POST['id']);
					break;
				case 'set_concept':
					echo va_drg_set_concept($_POST['id_lemma'], $_POST['id_concept']);
					break;
					
				case 'remove_concept':
					echo va_drg_remove_concept($_POST['id_lemma'], $_POST['id_concept']);
					break;
					
				case 'import':
					echo va_drg_import_data();
					break;
	        }
	        break;
		        
		case 'original':
		    if(!$intern)
		        break;
		        
        switch ($_REQUEST['query']){
            case 'compute':
                $tokens = $va_xxx->get_col(
					"SELECT distinct Token
					FROM Tokens JOIN Stimuli USING (Id_Stimulus) JOIN bibliographie ON Erhebung = Abkuerzung
					WHERE Token != '' AND Original = '' AND VA_Beta
                    ORDER BY Token ASC");
                
                $reps = [];
                $errors = [];
                
                $parser = va_get_general_beta_parser();
                
                foreach ($tokens as $token){
                    $res = $parser->convert_to_original($token);
                    if ($res['string']){
						
						//Replace u1 since wordpress db connection does not work with mb4 characters for some reason
						$db_string = str_replace('𝑢', '###u1###', $res['string']);
						
                        $va_xxx->query($va_xxx->prepare('UPDATE Tokens JOIN Stimuli USING (Id_Stimulus) JOIN bibliographie ON Erhebung = Abkuerzung SET Original = %s WHERE Token = %s AND Original = "" AND VA_Beta', $db_string, $token));
                        $reps[] = [htmlentities($token), $res['string']];
                    }
                    else {
                        foreach ($res['output'] as $part){
                            $msg = htmlentities($part[1]);
                            if (!in_array($msg, $errors)){
                                $errors[] = htmlentities($msg);
                            }
							$reps[] = [htmlentities($token), ''];
                        }
                    }
                }
				
				$va_xxx->query('CALL replace_u1()');
                
                echo json_encode([$reps, $errors]);
                break;
				
				case 'computeText':
				
					$reps = [];
					$errors = [];
				
					$parser = va_get_general_beta_parser();
					foreach ($_POST['records'] as $record){
						$record = html_entity_decode(stripslashes($record));
						$res = $parser->convert_to_original($record);
						
						if ($res['string']){
							$reps[] = [htmlentities($record), $res['string']];
						}
						else {
							foreach ($res['output'] as $part){
								$msg = htmlentities($part[1]);
								if (!in_array($msg, $errors)){
									$errors[] = htmlentities($msg);
								}
								$reps[] = [htmlentities($record), ''];
							}
						}
					}
					echo json_encode([$reps, $errors]);
				break;
        }
		break;
			
		//Util tools
		case 'util':
		
			//No user restriction
			switch ($_REQUEST['query']){
				case 'get_print_overlays':
					$db->select('va_xxx');
					echo json_encode($db->get_col('SELECT AsText(Polygone_Vereinfacht.Geodaten) FROM Orte JOIN Polygone_Vereinfacht USING (Id_Ort) WHERE Id_Kategorie = 63 AND Epsilon = 0.003'));
					break 2;
					
				case 'get_community_names':
					$comms = [];
					foreach (explode(', ', $_POST['points']) as $point){
						$comms[] = $db->get_var($db->prepare('SELECT Name FROM Orte WHERE Id_Kategorie = 62 AND ST_WITHIN(GeomFromText(%s), Geodaten)', $point));
					}
				 	echo implode(', ', $comms);
				 	break 2;
					
										
				case 'get_community_name':
				 	echo $db->get_var($db->prepare('SELECT Name FROM Orte WHERE Id_Kategorie = 62 AND ST_WITHIN(GeomFromText(%s), Geodaten)', $_POST['point']));
				 	break 2;
			}
			
			//TODO maybe better user control
			if(!$intern && !current_user_can('va_transcription_tool_write') && !current_user_can('va_typification_tool_write') && !current_user_can('va_glossary'))
				break;

			switch ($_REQUEST['query']){
				case 'user_email_outdated':
					if(update_user_meta($_POST['id_user'], 'email_outdated', $_POST['val']) || !$val){
						echo $_POST['val'];
					}
					else {
						echo 'failure';
					}
				break;
				
				case 'check_external_link':
					va_check_external_link($_POST['link']);
				break;
				
				case 'get_external_links':
					va_get_external_links();
				break;
				
				case 'get_glossary_link':
					echo va_get_glossary_link($_POST['id']);
				break;
				
				case 'get_comments_link':
					echo va_get_comments_link($_POST['id']);
				break;
				
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
			   
			   case 'getTypeWithoutRef':
					va_references_show_candidates($_POST['source'], $_POST['threshold'], $_POST['num_lemmas'], $_POST['show_no_candidates'] === 'true');
				break;
				
				case 'saveReferences':
					va_references_save_data($_POST['data'], $_POST['source']);
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
				 
				 case 'checkTokens':
				 	va_check_tokens_call($db);
				 	break;
				 	
				 case 'check_tokenizer':
				 	va_tokenization_test_ajax($db);
				 	break;
				 	
				 case 'tokenizeRecord':
				 	$tokenizer = va_create_tokenizer($_POST['source']);
				 	try {
				 		$_POST['extraData']['notes'] = stripslashes($_POST['extraData']['notes']);
				 		echo va_create_token_table($tokenizer->tokenize(stripslashes($_POST['record']), $_POST['extraData']), $_POST['id']);
				 	}
				 	catch (Exception $e){
				 		echo 'ERR:' . $e;
				 	}
				 	break;

				 case 'search_locations':
				 	echo json_encode(search_va_locations($_POST['search']));
				 	break;
				 	
				 case 'get_location_description':
				 	echo $va_xxx->get_var($va_xxx->prepare('SELECT Beschreibung FROM Orte WHERE Id_Ort = %d', $_POST['id']));
				 	break;
				 	
				 case 'save_location_description':
				 	if($va_xxx->update('Orte', ['Beschreibung' => $_POST['content']], ['Id_Ort' => $_POST['id']]) !== false){
				 		echo 'success';
				 	}
				 	break;
				 	
				 case 'find_geoname_id':
				 	echo json_encode(va_load_geonames_ids($_POST['count']));
				 	break;
					
				case 'get_informant_list':
					va_get_informant_geo_data(stripslashes($_POST['source']), $_POST['regions']);
					break;
					
				case 'getConceptsForSelect':
				    $start = ($_REQUEST['page'] - 1) * $page_count;
					$where = '';
					
					if (isset($_REQUEST['ignore']) && $_REQUEST['ignore']){
						$where .= 'Id_Konzept NOT IN (' . implode(',', $_REQUEST['ignore']) . ') AND ';
					}

					if (isset($_REQUEST['ignore_gram']) && $_REQUEST['ignore_gram']){
					    $where .= 'NOT Grammatikalisch AND ';
					}

					
					$search = $_REQUEST['search']?? '';
				
					$res = $db->get_results('SELECT Id_Konzept as id, IF(Name_D is not null and Name_d != "" AND Name_D != Beschreibung_D, CONCAT(Name_D, " (", Beschreibung_D, ")"), Beschreibung_D) as text FROM Konzepte WHERE ' . $where . '(Name_D LIKE "%' . esc_sql($search) . '%" OR Beschreibung_D LIKE "%' . esc_sql($search) . '%") ORDER BY text ASC', ARRAY_A);
					
					va_sort_for_select($search, $res);
					
					echo json_encode([
					    'results' => array_slice($res, $start, $page_count),
					    'pagination'=> [
					        'more' => $start + $page_count < count($res)
					    ]
					]);
					//echo json_encode($results);
				break;
					
				case 'getLemmasForSelect':
				    if (!isset($_REQUEST['search'])){
				        $_REQUEST['search'] = '';
				    }
				    
					$search = va_remove_special_chars($_REQUEST['search']);
					$sql = 'SELECT Id_lemma as id, subvocem as text FROM lemmata WHERE NOT Text_Referenz AND Quelle = %s AND subvocem COLLATE utf8_general_ci LIKE "%' . esc_sql($search) . '%" ORDER BY subvocem ASC';
					$refs = $db->get_results($va_xxx->prepare($sql, $_REQUEST['source']), ARRAY_A);
					
					echo json_encode($refs);
					break;
					
				case 'getMorphTypesForSelect':
				    if (!isset($_REQUEST['search'])){
				        $_REQUEST['search'] = '';
				    }
				    
				    $start = ($_REQUEST['page'] - 1) * $page_count;
				    $search = va_remove_accents($_REQUEST['search']);
				    $sql = 'SELECT Id_morph_Typ as id, lex_unique(Orth, Sprache, Genus) as text FROM morph_Typen WHERE Quelle = "VA" AND Orth COLLATE "utf8_general_ci" LIKE "%' . esc_sql($search) . '%" ORDER BY Orth collate "utf8_general_ci"';
				    $res = $db->get_results($sql, ARRAY_A);
					
					va_sort_for_select($search, $res, function ($x){return va_remove_accents($x);});
					
				    echo json_encode([
				        'results' => array_slice($res, $start, $page_count),
				        'pagination'=> [
				            'more' => $start + $page_count < count($res)
				        ]
				    ]);
				    break;
				    
				case 'getBaseTypesForSelect':
				    $start = ($_REQUEST['page'] - 1) * $page_count;
				    $search = va_remove_accents($_REQUEST['search']);
				    $sql = 'SELECT Id_Basistyp as id, CONCAT(Orth, IF(Sprache != "", CONCAT(" (", Sprache, ")"), "")) as text FROM Basistypen WHERE Quelle = "VA" AND Orth COLLATE "utf8_general_ci" LIKE "%' . esc_sql($search) . '%" ORDER BY Orth';
				    $res = $db->get_results($sql, ARRAY_A);
					
					va_sort_for_select($search, $res, function ($x){
						if (mb_strpos($x, 0, 1) == '*'){
							$x = mb_substr($x, 1);
						}
						return va_remove_accents($x);
					});
					
				    echo json_encode([
				        'results' => array_slice($res, $start, $page_count),
				        'pagination'=> [
				            'more' => $start + $page_count < count($res)
				        ]
				    ]);
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
					echo va_tokenization_info_for_stimulus($_POST['stimulus']);
				break;
				
				case 'tokenize':
					echo va_tokenize_for_stimulus($_POST['stimulus'], $_POST['preview']);
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
			
		case 'questionnaire':
				switch ($_POST['query']){
					case 'nextPage':
						global $wpdb;
						if ($_POST['save'] == 'true'){
							foreach ($_POST['answers'] as $answer){
							    $sub_questions = false;
								if (is_array($answer['answer'])){
								    if(array_keys($answer['answer']) === range(0, count($answer['answer']) - 1)){ //Sequential
								        $answer['answer'] = implode('###', $answer['answer']);
								    }
								    else {
								        $sub_questions = true;
								    }
								}
								
								if ($sub_questions){
								    $letter = 'a';
								    foreach ($answer['answer'] as $sub_text => $sub_answer_text){
								        $sub_answer = $answer;
								        $sub_answer['answer'] = $sub_answer_text;
								        $sub_answer['spec'] = $sub_text;
								        
								        $sub_answer['sub_question'] = $letter ++;
								        
								        va_quest_insert_answer($wpdb, $sub_answer);
								    }
								}
								else {
								    va_quest_insert_answer($wpdb, $answer);
								}
							}
						}
						va_questionnaire_sub_page($_POST['page'] + 1, $_POST['post']);
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

function va_quest_insert_answer (&$db, $answer){
    $answer['answer'] = stripslashes($answer['answer']);
    $answer['question_text'] = strip_tags($db->get_var($db->prepare('SELECT meta_value FROM wp_postmeta WHERE post_id = %d AND meta_key = %s',
    $answer['post_id'], ('fb_seite_' . $answer['page'] . '_fb_frage_' . $answer['question'] . '_fb_uberschrift'))));
    
    if (isset($answer['spec'])){
        $answer['question_text'] .= ' (' . $answer['spec'] . ')';
        unset($answer['spec']);
    }
    
    if (!isset($answer['sub_question'])){
        $answer['sub_question'] = '';
    }
    
    $db->insert('questionnaire_results', $answer);
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

function va_sort_for_select ($search, &$dbres, $extraFun = false){
	if (mb_strlen($search) > 1){
		$search = mb_strtolower($search);
		if ($extraFun){
			$search = $extraFun($search);
		}
		
		usort($dbres, function ($e1, $e2) use ($search, $extraFun){
			$t1 = mb_strtolower($e1['text']);
			$t2 = mb_strtolower($e2['text']);
			
			if ($extraFun){
				$t1 = $extraFun($t1);
				$t2 = $extraFun($t2);
			}
			
			$pos1 = mb_strpos($t1, $search);
			$pos2 = mb_strpos($t2, $search);
			
			if ($pos1 === 0 && $pos2 !== 0){
				return -1;
			}
			
			if ($pos2 === 0 && $pos1 !== 0){
				return 1;
			}
			
			return strcmp($t1, $t2);
		});
	}
}
?>