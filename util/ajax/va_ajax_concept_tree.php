<?php
function va_ajax_concept_tree (&$db){
	switch ($_POST['query']){
		case 'update_node':
			if(!current_user_can('va_concept_tree_write'))
				break;
				
			if($db->query($db->prepare('UPDATE Ueberkonzepte SET Id_Ueberkonzept = %d WHERE ID_Konzept = %d', $_POST['superconcept'], $_POST['concept'])) !== false){
				echo 'success';
			}
			break;
			
		case 'show_tree':
			$db->query('CALL buildConceptCount()');
			echo showTree($_POST['main_category'], $_POST['category']);
			break;
			
		case 'get_concept_info':
			echo json_encode($db->get_results($db->prepare('
					SELECT Name_D, Beschreibung_D, Relevanz, Kategorie, Hauptkategorie, Kommentar_Intern 
					FROM Konzepte WHERE Id_Konzept = %d', $_POST['concept']), ARRAY_N));
			break;
	}
}
?>