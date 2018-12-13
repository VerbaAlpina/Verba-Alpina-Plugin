<?php
function va_ajax_edit_glossary ($db){
	switch ($_POST['query']){
		case 'changeEntry':
			$transl = '';
			if(isset($_POST['language'])){
				$lang = $_POST['language'];
				if(strlen($lang) !== 1){
					break;
				}
				$transl = ", Erlaeuterung_$lang AS erlaeuterung, Terminus_$lang AS terminus";
				
				$translators = $db->get_col($db->prepare('SELECT Kuerzel FROM VTBL_Eintrag_Autor WHERE Id_Eintrag = %d AND Sprache = %s AND Aufgabe = %s', $_POST['id'], $lang, 'trad'));
				$correctors = $db->get_col($db->prepare('SELECT Kuerzel FROM VTBL_Eintrag_Autor WHERE Id_Eintrag = %d AND Sprache = %s AND Aufgabe = %s', $_POST['id'], $lang, 'corr'));
			}
			$result = $db->get_row($db->prepare("SELECT Erlaeuterung_D as erlaeuterung_d, Fertig, Intern" . $transl . " FROM Glossar WHERE Id_Eintrag = %d", $_POST['id']), ARRAY_A);
			
			$tags = $db->get_col($db->prepare('SELECT Id_Tag FROM VTBL_Eintrag_Tag WHERE Id_Eintrag = %d', $_POST['id']));
			$result['tags'] = $tags;
			
			$authors = $db->get_col($db->prepare('SELECT Kuerzel FROM VTBL_Eintrag_Autor WHERE Id_Eintrag = %d AND Aufgabe = %s', $_POST['id'], 'auct'));
			$result['autoren'] = $authors;
			
			if(isset($translators)){
				$result['uebersetzer'] = $translators;
				$result['korrekturleser'] = $correctors;
			}
			
			$result['url'] = va_get_glossary_link($_POST['id']);
			
			echo json_encode($result);
			break;
			
		case 'updateEntry':
			if(!current_user_can('va_glossary_translate') && !current_user_can('va_glossary_edit')){ //TODO finer control
				break;
			}
			
			if(isset($_POST['language'])){
				$lang = $_POST['language'];
				if(strlen($lang) !== 1){
					break;
				}
				
				$query = $db->prepare("UPDATE Glossar set Erlaeuterung_D = %s, Fertig = %d, Intern = %s, Erlaeuterung_$lang = %s, Terminus_$lang = %s where Id_Eintrag = %d", stripslashes($_POST['content']), $_POST['ready'], $_POST['internal'], stripslashes($_POST['erlaeuterung']), stripslashes($_POST['terminus']), $_POST['id']);
				
				$db->delete('VTBL_Eintrag_Autor', array('Id_Eintrag' => $_POST['id'], 'Aufgabe' => 'trad', Sprache => $lang), array ('%d', '%s', '%s'));
				if($_POST['translators']){
					foreach ($_POST['translators'] as $translator){
						$db->insert('VTBL_Eintrag_Autor', array ('Id_Eintrag' => $_POST['id'], 'Kuerzel' => $translator, 'Aufgabe' => 'trad', 'Sprache' => $lang), array ('%d', '%s', '%s', '%s'));
					}
				}
				
				$db->delete('VTBL_Eintrag_Autor', array('Id_Eintrag' => $_POST['id'], 'Aufgabe' => 'corr', Sprache => $lang), array ('%d', '%s', '%s'));
				if($_POST['correctors']){
					foreach ($_POST['correctors'] as $corrector){
						$db->insert('VTBL_Eintrag_Autor', array ('Id_Eintrag' => $_POST['id'], 'Kuerzel' => $corrector, 'Aufgabe' => 'corr', 'Sprache' => $lang), array ('%d', '%s', '%s', '%s'));
					}
				}
			}
			else {
				$query = $db->prepare('UPDATE Glossar set Erlaeuterung_D = %s, Fertig = %d, Intern = %s where Id_Eintrag = %d', stripslashes($_POST['content']), $_POST['ready'], $_POST['internal'], $_POST['id']);
			}
			
			$db->query($query);
			
			$db->delete('VTBL_Eintrag_Tag', array('Id_Eintrag' => $_POST['id']), array ('%d'));
			if($_POST['tags']){
				foreach ($_POST['tags'] as $tag){
					$db->insert('VTBL_Eintrag_Tag', array ('Id_Eintrag' => $_POST['id'], 'Id_Tag' => $tag), array ('%d', '%d'));
				}
			}
			
			$db->delete('VTBL_Eintrag_Autor', array('Id_Eintrag' => $_POST['id'], 'Aufgabe' => 'auct'), array ('%d', '%s'));
			if($_POST['authors']){
				foreach ($_POST['authors'] as $author){
					$db->insert('VTBL_Eintrag_Autor', array ('Id_Eintrag' => $_POST['id'], 'Kuerzel' => $author, 'Aufgabe' => 'auct', 'Sprache' => 'D'), array ('%d', '%s', '%s', '%s'));
				}
			}
			
			echo '1';
			break;
			
		case 'getTranslation':
			$lang = $_POST['language'];
			if(strlen($lang) !== 1){
				break;
			}
			$query = $db->prepare("SELECT Terminus_$lang AS terminus, Erlaeuterung_$lang AS description FROM Glossar WHERE Id_Eintrag = %d", $_POST['id']);
			$result = $db->get_row($query, ARRAY_A);
			
			$result['uebersetzer'] = $db->get_col($db->prepare('SELECT Kuerzel FROM VTBL_Eintrag_Autor WHERE Id_Eintrag = %d AND Sprache = %s AND Aufgabe = %s', $_POST['id'], $lang, 'trad'));
			$result['korrekturleser'] = $db->get_col($db->prepare('SELECT Kuerzel FROM VTBL_Eintrag_Autor WHERE Id_Eintrag = %d AND Sprache = %s AND Aufgabe = %s', $_POST['id'], $lang, 'corr'));
			
			echo json_encode($result);
			break;
			
		case 'updateList':
		
			if(strpos($_POST['filter'], 'MISSING_') === 0){
				$entries = $db->get_results('select Id_Eintrag, Terminus_D from glossar WHERE Erlaeuterung_' . substr($_POST['filter'], 8, 1) . " = '' AND Intern = '0' AND Fertig AND Kategorie = 'Methodologie'", ARRAY_A);
			}
			else if(strpos($_POST['filter'], 'NCORRECT_') === 0){
				$entries = $db->get_results("select g.Id_Eintrag, Terminus_D from glossar g left join vtbl_eintrag_autor v on v.Id_Eintrag = g.Id_Eintrag and Aufgabe = 'corr' WHERE Erlaeuterung_" . substr($_POST['filter'], 9, 1) . " != '' AND Intern = '0' AND Fertig AND Kategorie = 'Methodologie' and Kuerzel is null", ARRAY_A);
			}
			else {
				$entries = $db->get_results('select Id_Eintrag, Terminus_D from glossar', ARRAY_A);
			}
			
			$res = '<option value="0">' . DEFAULT_SELECT . '</option>';
			
			foreach ($entries as $e){
				$res .= "<option value='{$e['Id_Eintrag']}'>{$e['Terminus_D']}</option>\n";
			}
			echo $res;
			break;
	}
}
?>