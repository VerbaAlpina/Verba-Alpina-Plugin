<?php
function va_ajax_typification (&$db){
	switch ($_POST['query']){
		case 'getTokenList':
			echo json_encode($db->get_results($db->prepare('CALL getRecords(%d, %d, %d);', $_POST['id'], $_POST['all'], $_POST['allC'])));
			break;
			
		case 'removeTypification':
			if(!current_user_can('va_typification_tool_write'))
				break;
			
			$description = json_decode(stripslashes($_POST['description']));
			$tids = getTokenIds($db, $description);
			if($description->kind === 'G' || $description->kind === 'K'){
				$db->query($db->prepare("DELETE FROM VTBL_Tokengruppe_morph_Typ WHERE Quelle = 'VA' AND Id_Tokengruppe IN " . keyPlaceholderList($tids), $tids));
			}
			else {
				$db->query($db->prepare("DELETE FROM VTBL_Token_morph_Typ WHERE Quelle = 'VA' AND Id_Token IN " . keyPlaceholderList($tids), $tids));
			}
			echo 'success';
		break;
		
		case 'removeConcept':
			if(!current_user_can('va_typification_tool_write'))
				break;
			
			$description = json_decode(stripslashes($_POST['description']));
			$tids = getTokenIds($db, $description);
			$placeholder_list = keyPlaceholderList($tids);
			array_push($tids, $_POST['concept']);
			if($description->kind === 'G' || $description->kind === 'K'){
				$db->query($db->prepare("DELETE FROM VTBL_Tokengruppe_Konzept WHERE Id_Tokengruppe IN " . $placeholder_list . " AND Id_Konzept = %d", $tids));
			}
			else {
				$db->query($db->prepare("DELETE FROM VTBL_Token_Konzept WHERE Id_Token IN " . $placeholder_list . " AND Id_Konzept = %d", $tids));
			}
			echo 'success';
			break;
		
		case 'addTypification':
			if(!current_user_can('va_typification_tool_write'))
				break;
			
			$descriptions = json_decode(stripslashes($_POST['descriptionList']));
			foreach ($descriptions as $description){
				$tids = getTokenIds($db, $description);
				if($description->kind === 'G' || $description->kind === 'K'){
					$db->query("DELETE FROM VTBL_Tokengruppe_morph_Typ WHERE Quelle = 'VA' AND Id_Tokengruppe IN (" . implode(',', $tids) . ')');
					$db->query($db->prepare("
						INSERT INTO VTBL_Tokengruppe_morph_Typ (Id_Tokengruppe, Id_morph_Typ, Quelle, Angelegt_Von, Angelegt_Am) 
						SELECT Id_Tokengruppe, %d, 'VA', %s, NOW() 
						FROM Tokengruppen WHERE Id_Tokengruppe IN (" . implode(',', $tids) . ')', $_POST['newTypeId'], wp_get_current_user()->user_login));
				}
				else {
					$db->query("DELETE FROM VTBL_Token_morph_Typ WHERE Quelle = 'VA' AND Id_Token IN (" . implode(',', $tids) . ')');
					$db->query($db->prepare("
						INSERT INTO VTBL_Token_morph_Typ (Id_Token, Id_morph_Typ, Quelle, Angelegt_Von, Angelegt_Am) 
						SELECT Id_Token, %d, 'VA', %s, NOW() 
						FROM Tokens WHERE Id_Token IN (" . implode(',', $tids) . ')', $_POST['newTypeId'], wp_get_current_user()->user_login));
				}
			}
			echo 'success';
		break; 
		
		case 'addConcept':
			if(!current_user_can('va_typification_tool_write'))
				break;
			
			$results = array();
			$descriptions = json_decode(stripslashes($_POST['descriptionList']));
			foreach ($descriptions as $description){
				$tids = getTokenIds($db, $description);
				
				if(!isset($_REQUEST['allowMultipleConcepts'])){
					if($description->kind === 'G' || $description->kind === 'K'){
						$olds = $db->get_col($db->prepare('
							SELECT DISTINCT Id_Konzept FROM VTBL_Tokengruppe_Konzept
							WHERE Id_Tokengruppe IN (' . implode(',', $tids) . ')
							AND Id_Konzept != %d', $_POST['newConceptId']), 0);
					}
					else {
						$olds = $db->get_col($db->prepare('
							SELECT DISTINCT Id_Konzept FROM VTBL_Token_Konzept
							WHERE Id_Token IN (' . implode(',', $tids) . ')
							AND Id_Konzept != %d', $_POST['newConceptId']), 0);
					}
				}
				
				if(!isset($_REQUEST['allowMultipleConcepts']) && !empty($olds)){
					$name = $db->get_var('SELECT Beschreibung_D FROM Konzepte WHERE Id_Konzept IN (' . implode(',', $olds) . ')', 0, 0);
					$results[] = $name;
				}
				else {
					if($description->kind === 'G' || $description->kind === 'K'){
						$db->query($db->prepare('INSERT IGNORE INTO VTBL_Tokengruppe_Konzept (Id_Tokengruppe, Id_Konzept) SELECT Id_Tokengruppe, %d FROM Tokengruppen 
							WHERE Id_Tokengruppe IN (' . implode(',', $tids) . ')', $_POST['newConceptId']));
					}
					else {
						$db->query($db->prepare('INSERT IGNORE INTO VTBL_Token_Konzept (Id_Token, Id_Konzept) SELECT Id_Token, %d FROM Tokens 
							WHERE Id_Token IN (' . implode(',', $tids) . ')', $_POST['newConceptId']));
					}
					$results[] = 'success';
				}
			}
			echo json_encode($results);
		break;
		
		case 'saveMorphType':
			if(!current_user_can('va_typification_tool_write'))
				break;
		
			//Store type information
			if(isset($_POST['id'])){
				$mtype_id = $_POST['id'];
				
				$db->update('morph_Typen', $_POST['type'], array('Id_morph_Typ' => $mtype_id));
			}
			else {
				$_POST['type']['Angelegt_Von'] = wp_get_current_user()->user_login;
				$db->insert('morph_Typen', $_POST['type']);
				
				$mtype_id = $db->insert_id;
			}
			
			//Connect base types
			$db->delete('VTBL_morph_Basistyp', array('Id_morph_Typ' => $mtype_id));
			if(!empty($_POST['btypes'])){
				foreach ($_POST['btypes'] as $btype){
					$db->insert('VTBL_morph_Basistyp', array('Id_morph_Typ' => $mtype_id, 'Id_Basistyp' => $btype, 'Quelle' => 'VA', 
						'Angelegt_Von' => wp_get_current_user()->user_login));
				}
			}
			
			//Connect references
			$db->delete('VTBL_morph_Typ_Lemma', array('Id_morph_Typ' => $mtype_id));
			if(!empty($_POST['refs'])){
				foreach ($_POST['refs'] as $ref){
					$db->insert('VTBL_morph_Typ_Lemma', array('Id_morph_Typ' => $mtype_id, 'Id_Lemma' => $ref, 'Quelle' => 'VA', 
						'Angelegt_Von' => wp_get_current_user()->user_login));
				}
			}
			
			//Connect components
			$db->delete('VTBL_morph_Typ_Bestandteile', array('Id_morph_Typ' => $mtype_id));
			if(empty($_POST['parts'])){
				//If there are no components, the type itself is its only component
				$db->insert('VTBL_morph_Typ_Bestandteile', array('Id_morph_Typ' => $mtype_id, 'Id_Bestandteil' => $mtype_id));
			}
			else {
				foreach ($_POST['parts'] as $part){
					$db->insert('VTBL_morph_Typ_Bestandteile', array('Id_morph_Typ' => $mtype_id, 'Id_Bestandteil' => $part));
				}
			}
			$result = array('Id' => $mtype_id, 'Name' => $db->get_var("SELECT lex_unique(Orth, Sprache, Genus) FROM morph_Typen WHERE Id_morph_Typ = $mtype_id"));
			echo json_encode($result);
		break;
		
		case 'getMorphTypeDetails':
			$typ_info = $db->get_row($db->prepare("SELECT * FROM morph_Typen WHERE Id_morph_Typ = %d", $_POST['id']));
			$parts = $db->get_col($db->prepare("SELECT Id_Bestandteil FROM VTBL_morph_Typ_Bestandteile WHERE Id_morph_Typ = %d AND Id_Bestandteil != %d", $_POST['id'], $_POST['id']));
			$refs = $db->get_col($db->prepare("SELECT Id_Lemma FROM VTBL_morph_Typ_Lemma WHERE Id_morph_Typ = %d", $_POST['id']));
			$btypes = $db->get_col($db->prepare("SELECT Id_Basistyp FROM VTBL_morph_Basistyp WHERE Id_morph_Typ = %d", $_POST['id']));
			echo json_encode(array('type' => $typ_info, 'parts' => $parts, 'refs' => $refs, 'btypes' => $btypes));
		break;
	}
}


function getTokenIds (&$db, $description){
	$concept_list = implode(',', $description->concepts);
	switch($description->kind){
		case 'T':
			$sql = $db->prepare("
				SELECT tk.Id_Token 
				FROM 
					(SELECT Id_Token, Token, Genus, Id_Stimulus, GROUP_CONCAT(Id_Konzept ORDER BY Id_Konzept) AS Konzepte
						FROM Tokens
						LEFT JOIN VTBL_Token_Konzept USING (Id_Token)
						LEFT JOIN Konzepte USING (Id_Konzept)
						WHERE (Relevanz IS NULL OR Relevanz)
						GROUP BY Id_Token) AS tk
					LEFT JOIN VTBL_Token_morph_Typ vt ON (tk.Id_Token = vt.Id_Token AND vt.Quelle = 'VA')
					WHERE
						Token = %s 
						AND Genus = %s 
						AND Id_Stimulus = %d
					", $description->token, $description->gender, $description->id_stimulus);
			break;
		case 'G':
			$sql = $db->prepare("
				SELECT tk.Id_Tokengruppe 
				FROM 
					(SELECT Id_Tokengruppe, Tokengruppe, Genus, Id_Stimulus, GROUP_CONCAT(Id_Konzept ORDER BY Id_Konzept) as Konzepte
						FROM V_Tokengruppen t 
						LEFT JOIN VTBL_Tokengruppe_Konzept USING (Id_Tokengruppe)
						LEFT JOIN Konzepte USING (Id_Konzept)
						WHERE (Relevanz IS NULL OR Relevanz)
						GROUP BY Id_Tokengruppe) AS tk
					LEFT JOIN VTBL_Tokengruppe_morph_Typ vt ON (tk.Id_Tokengruppe = vt.Id_Tokengruppe AND vt.Quelle = 'VA')
				WHERE
					Tokengruppe = %s 
					AND Genus = %s 
					AND Id_Stimulus = %d
				", $description->token, $description->gender, $description->id_stimulus);
			break;
		case 'P':
			$sql = $db->prepare("
				SELECT tk.Id_Token 
				FROM 
					(SELECT Id_Token, Token, Genus, Id_Stimulus, GROUP_CONCAT(Id_Konzept ORDER BY Id_Konzept) AS Konzepte
						FROM Tokens
						LEFT JOIN VTBL_Token_Konzept USING (Id_Token)
						LEFT JOIN Konzepte USING (Id_Konzept)
						WHERE (Relevanz IS NULL OR Relevanz)
						GROUP BY Id_Token) AS tk
					LEFT JOIN VTBL_Token_morph_Typ vt ON (tk.Id_Token = vt.Id_Token AND vt.Quelle = 'VA')
				WHERE 
					Token = '' 
					AND EXISTS (
						SELECT *
						FROM VTBL_Token_phon_Typ v 
						WHERE 
							v.Id_Token = tk.Id_Token 
							AND v.Quelle = %s 
							AND Id_phon_Typ = %d) 
					AND Genus = %s 
					AND Id_Stimulus = %d
				", $description->source, $description->id_type, $description->gender, $description->id_stimulus);
			break;
		case 'M':
			$sql = $db->prepare("
				SELECT tk.Id_Token 
				FROM 
					(SELECT Id_Token, Token, Genus, Id_Stimulus, GROUP_CONCAT(Id_Konzept ORDER BY Id_Konzept) AS Konzepte
						FROM Tokens
						LEFT JOIN VTBL_Token_Konzept USING (Id_Token)
						LEFT JOIN Konzepte USING (Id_Konzept)
						WHERE (Relevanz IS NULL OR Relevanz)
						GROUP BY Id_Token) AS tk
					LEFT JOIN VTBL_Token_morph_Typ vt ON (tk.Id_Token = vt.Id_Token AND vt.Quelle = 'VA')
				WHERE 
					Token = '' 
					AND EXISTS (
						SELECT * 
						FROM VTBL_Token_morph_Typ v 
						WHERE 
							v.Id_Token = tk.Id_Token 
							AND v.Quelle = %s 
							AND Id_morph_Typ = %d) 
					AND Genus = %s 
					AND Id_Stimulus = %d
				", $description->source, $description->id_type, $description->gender, $description->id_stimulus);
			break;
		case 'K':
			$sql = $db->prepare("
				SELECT tk.Id_Tokengruppe 
				FROM 
					(SELECT Id_Tokengruppe, Tokengruppe, Genus, Id_Stimulus, GROUP_CONCAT(Id_Konzept ORDER BY Id_Konzept) as Konzepte
						FROM V_Tokengruppen t 
						LEFT JOIN VTBL_Tokengruppe_Konzept USING (Id_Tokengruppe)
						LEFT JOIN Konzepte USING (Id_Konzept)
						WHERE (Relevanz IS NULL OR Relevanz)
						GROUP BY Id_Tokengruppe) AS tk
					LEFT JOIN VTBL_Tokengruppe_morph_Typ vt ON (tk.Id_Tokengruppe = vt.Id_Tokengruppe AND vt.Quelle = 'VA')
				WHERE
					Tokengruppe = '' 
					AND EXISTS (
						SELECT *
						FROM VTBL_Tokengruppe_morph_Typ vq
						WHERE 
							vq.Id_Tokengruppe = tk.Id_Tokengruppe 
							AND vq.Quelle = %s
							AND vq.Id_morph_Typ = %d) 
					AND Genus = %s 
					AND Id_Stimulus = %d
				", $description->source, $description->id_type, $description->gender, $description->id_stimulus);
			break;
	}
	
	if($concept_list == ''){
		$app = ' AND Konzepte IS NULL';
	}
	else {
		$app = $db->prepare(' AND Konzepte = %s', $concept_list);
	}

	if($description->id_vatype){
		$app .= $db->prepare(" AND vt.Id_morph_Typ = %d", $description->id_vatype);
	}
	else {
		$app .= ' AND vt.Id_morph_Typ IS NULL';
	}
	$sql .= $app;

	return $db->get_col($sql, 0);
}
?>