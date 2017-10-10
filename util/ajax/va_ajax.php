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
					va_save_comment($db, $_POST['id'], $_POST['content'], $_POST['authors'], $_POST['internal'], $_POST['ready']) &&
					(!isset($_POST['lang']) || va_save_comment_translation($db, $_POST['id'], $_POST['lang'], $_POST['translation'], $_POST['translators'])))
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
			
		//Util tools
		case 'util':
			//TODO maybe better user control
			if(!$intern && !current_user_can('va_transcription_tool_write') && !current_user_can('va_typification_tool_write') && !current_user_can('glossar'))
				break;
			
			switch ($_REQUEST['query']){
				case 'addLock':
					$db->query($db->prepare("DELETE FROM Locks where (Wert = %s AND Tabelle = %s AND Gesperrt_von = %s) or hour(timediff(Zeit,now())) > 0", $_REQUEST['value'], $_REQUEST['table'], wp_get_current_user()->user_login));
					if($db->insert('Locks', array('Tabelle' => $_REQUEST['table'], 'Gesperrt_von' => wp_get_current_user()->user_login, 'Wert' => $_REQUEST['value']))){
						echo 'success';
					}
					else {
						echo 'locked';
					}
				break;
					
				case 'removeLock':
					$db->query($db->prepare("DELETE FROM Locks where (Wert = %s AND Tabelle = %s AND Gesperrt_von = %s) or hour(timediff(Zeit,now())) > 0", $_REQUEST['value'], $_REQUEST['table'], wp_get_current_user()->user_login));
					echo 'success';
				break;
				
				case 'removeAllLocks':
					$db->query($db->prepare("DELETE FROM Locks where (Tabelle = %s AND Gesperrt_von = %s) or hour(timediff(Zeit,now())) > 0", $_REQUEST['table'], wp_get_current_user()->user_login));
					echo 'success';
				break;
			}
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
?>