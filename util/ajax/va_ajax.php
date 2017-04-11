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
			if(!$intern && !current_user_can('va_transcription_tool_write') && !current_user_can('va_typification_tool_write'))
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
		
		//Test stuff
		case 'test':
			switch ($_REQUEST['query']){
				case 'getPVA_BSA_Tokens':
					echo json_encode($db->get_col($db->prepare('SELECT lautschrift FROM PVA_BSA.Belege WHERE Lautschrift IS NOT NULL LIMIT %d, 1000', $_REQUEST['index'])));
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