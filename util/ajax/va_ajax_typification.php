<?php
function va_ajax_typification (&$db){
	switch ($_POST['query']){
	    case 'getTokenList':
	        if (!is_numeric($_POST['id'])){
	            if (mb_substr($_POST['id'], 0, 1) === 'D'){
	                $res = $db->get_results($db->prepare('CALL getRecords(%d, %d, %d, %d, %s);', 90322, $_POST['all'], $_POST['allC'], $_POST['allA'], mb_substr($_POST['id'], 1)));
	            }
	            else {
	                $res = $db->get_results($db->prepare('CALL getRecords(%d, %d, %d, %d, %s);', 90322, $_POST['all'], $_POST['allC'], $_POST['allA'], $_POST['id']));
	            }
	            $problems = $db->get_col($db->prepare('SELECT Ids FROM tprobleme WHERE Id_Stimulus =%d AND Geloest < 2', 90322));
	        }
	        else {
	           $res = $db->get_results($db->prepare('CALL getRecords(%d, %d, %d, %d, %s);', $_POST['id'], $_POST['all'], $_POST['allC'], $_POST['allA'], ''));
	           $problems = $db->get_col($db->prepare('SELECT Ids FROM tprobleme WHERE Id_Stimulus =%d AND Geloest < 2', $_POST['id']));
	        }
	        
	        foreach ($res as $index => $row){
	            $res[$index]->Problem = in_array($row->TokenIds, $problems);
	        }
	        
	        echo json_encode($res);
	        
	        break;
			
		case 'getStimulusList':
		    echo va_typif_get_stimulus_list($db, $_POST['atlas'], $_POST['all'] == 0, $_POST['allC'] == 0, $_POST['allA'] == 0);
		    break;
			
		case 'removeTypification':
			if(!current_user_can('va_typification_tool_write'))
				break;
				
			$description = json_decode(stripslashes($_POST['description']));
			$tids = $description->idlist;
			
			if($description->kind === 'G' || $description->kind === 'K' || $description->kind === 'GP'){
				$db->query($db->prepare("DELETE VTBL_Tokengruppe_morph_Typ FROM VTBL_Tokengruppe_morph_Typ JOIN morph_Typen m USING (Id_morph_Typ) WHERE m.Quelle = 'VA' AND Id_Tokengruppe IN " . keyPlaceholderList($tids), $tids));
			}
			else {
				$db->query($db->prepare("DELETE VTBL_Token_morph_Typ FROM VTBL_Token_morph_Typ JOIN morph_Typen m USING (Id_morph_Typ) WHERE m.Quelle = 'VA' AND Id_Token IN " . keyPlaceholderList($tids), $tids));
			}
			echo 'success';
			break;
				
		case 'removeConcept':
			if(!current_user_can('va_typification_tool_write'))
				break;
				
			$description = json_decode(stripslashes($_POST['description']));
			$tids = $description->idlist;
			
			if (empty($tids)){
				error_log(json_encode($_POST)); //TODO remove if bug is fixed
			}
				
			$placeholder_list = keyPlaceholderList($tids);
			array_push($tids, $_POST['concept']);
			if($description->kind === 'G' || $description->kind === 'K' || $description->kind === 'GP'){
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
			    va_add_typification($db, $description->idlist, $description->kind, $_POST['newTypeId']);
			}
			echo 'success';
			break;
				
		case 'addConcept':
			if(!current_user_can('va_typification_tool_write'))
				break;
				
			$results = array();
			$descriptions = json_decode(stripslashes($_POST['descriptionList']));
			foreach ($descriptions as $description){
			    $tids = $description->idlist;
				
				if(!isset($_REQUEST['allowMultipleConcepts'])){
					if($description->kind === 'G' || $description->kind === 'K' || $description->kind === 'GP'){
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
					if($description->kind === 'G' || $description->kind === 'K' || $description->kind === 'GP'){
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
				$db->hide_errors();
				$_POST['type']['Angelegt_Von'] = wp_get_current_user()->user_login;
				if(!$db->insert('morph_Typen', $_POST['type'])){
					$last_err = $db->last_error;
					if(substr($last_err, 0, 9) === 'Duplicate'){
						echo 'Fehler: Es gibt bereits einen identischen Typ!';
					}
					else {
						echo 'Fehler: ' . $last_err;
					}
					return;
				}
				
				$db->show_errors();
				
				$mtype_id = $db->insert_id;
			}
			
			//Connect base types
			$db->delete('VTBL_morph_Basistyp', array('Id_morph_Typ' => $mtype_id));
			if(!empty($_POST['btypes'])){
				foreach ($_POST['btypes'] as $index => $btype){
					$db->insert('VTBL_morph_Basistyp', array('Id_morph_Typ' => $mtype_id, 'Id_Basistyp' => $btype, 'Quelle' => 'VA',
							'Angelegt_Von' => wp_get_current_user()->user_login, 'Unsicher' => $_POST['unsures'][$index]));
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
			
		case 'saveBaseType':
			if(!current_user_can('va_typification_tool_write'))
				break;

			//Store type information
			if(isset($_POST['id'])){
				$btype_id = $_POST['id'];
				
				$db->update('Basistypen', $_POST['type'], array('Id_Basistyp' => $btype_id));
			}
			else {
				$db->hide_errors();
				
				$_POST['type']['Angelegt_Von'] = wp_get_current_user()->user_login;
				if(!$db->insert('Basistypen', $_POST['type'])){
					$last_err = $db->last_error;
					if(substr($last_err, 0, 9) === 'Duplicate'){
						echo 'Fehler: Es gibt bereits einen Basistyp mit dem gleichen Namen!';
					}
					else {
						echo 'Fehler: ' . $last_err;
					}
					return;
				}
				
				$db->show_errors();
				
				$btype_id = $db->insert_id;
			}
			
			//Connect references
			$refs_added = false;
			$db->delete('VTBL_Basistyp_Lemma', array('Id_Basistyp' => $btype_id));
			if(!empty($_POST['refs'])){
				foreach ($_POST['refs'] as $ref){
					$db->insert('VTBL_Basistyp_Lemma', array('Id_Basistyp' => $btype_id, 'Id_Lemma' => $ref, 'Angelegt_Von' => wp_get_current_user()->user_login));
				}
				$refs_added = true;
			}
			
			$result = array('Id' => $btype_id, 'Name' =>  $_POST['type']['Orth'], 'Refs' => $refs_added, 'Sprache' => $_POST['type']['Sprache']);
			echo json_encode($result);
			break;
				
		case 'getMorphTypeDetails':
			$typ_info = $db->get_row($db->prepare("SELECT * FROM morph_Typen WHERE Id_morph_Typ = %d", $_POST['id']));
			$parts = $db->get_col($db->prepare("SELECT Id_Bestandteil FROM VTBL_morph_Typ_Bestandteile WHERE Id_morph_Typ = %d AND Id_Bestandteil != %d", $_POST['id'], $_POST['id']));
			$refs = $db->get_col($db->prepare("SELECT Id_Lemma FROM VTBL_morph_Typ_Lemma WHERE Id_morph_Typ = %d", $_POST['id']));
			$btypes = $db->get_results($db->prepare("SELECT Id_Basistyp, Unsicher FROM VTBL_morph_Basistyp WHERE Id_morph_Typ = %d", $_POST['id']), ARRAY_N);
			echo json_encode(array('type' => $typ_info, 'parts' => $parts, 'refs' => $refs, 'btypes' => $btypes));
			break;
			
		case 'getBaseTypeDetails':
			$typ_info = $db->get_row($db->prepare("SELECT * FROM Basistypen WHERE Id_Basistyp = %d", $_POST['id']));
			$refs = $db->get_col($db->prepare("SELECT Id_Lemma FROM VTBL_Basistyp_Lemma WHERE Id_Basistyp = %d", $_POST['id']));
			echo json_encode(array('type' => $typ_info, 'refs' => $refs));
			break;
			
		case 'checkFileExists':
			$file_loc = substr($_POST['file'], 0, strpos($_POST['file'], '#')) . '/' . $_POST['file'];
			$file = get_home_path() . 'dokumente/scans/' . $file_loc;
			echo file_exists($file)? $file_loc: 'no';
			break;
			
		case 'getReferencesBase':
			if (isset($_POST['ids'])){
				$where = 'Id_Lemma IN (' . implode(',', array_filter($_POST['ids'], 'is_numeric')) . ')';
			}
			else if (isset($_POST['search'])){
				$search_strs = explode(' ', $_POST['search']);
				$where = implode(' AND ', array_map(function ($e){ return '(Quelle LIKE "%' . esc_sql($_POST['search']) . '%" OR IF(Subvocem REGEXP "^[¹²³⁴⁵⁶⁷⁸⁹]", SUBSTR(Subvocem,2), Subvocem) COLLATE utf8_unicode_ci LIKE "' .  esc_sql($_POST['search']) . '%")';}, $search_strs));
			}
			else {
				echo '[]';
				break;
			}
			
			$results = $db->get_results('SELECT Id_Lemma as id, CONCAT(Quelle, ": ", Subvocem) as text FROM Lemmata_Basistypen WHERE ' . $where . ' ORDER BY Quelle ASC, Subvocem COLLATE utf8_unicode_ci ASC', ARRAY_A);
			
			echo json_encode($results);
			break;
			
		case 'getReferencesMorph':
			if (isset($_POST['ids'])){
				$where = 'Id_Lemma IN (' . implode(',', array_filter($_POST['ids'], 'is_numeric')) . ')';
			}
			else if (isset($_POST['search'])){
				$search_strs = explode(' ', $_POST['search']);
				$where = implode(' AND ', array_map(function ($e){ return '(Quelle LIKE "%' . esc_sql($e) . '%" OR IF(Subvocem REGEXP "^[¹²³⁴⁵⁶⁷⁸⁹]", SUBSTR(Subvocem,2), Subvocem) COLLATE utf8_unicode_ci LIKE "' .  esc_sql($e) . '%")';}, $search_strs));
			}
			else {
				echo '[]';
				break;
			}
			
			$results = $db->get_results('SELECT Id_Lemma as id, CONCAT(Quelle, ": ", IF(Text_Referenz, "s.v. ", ""), Subvocem) as text FROM Lemmata WHERE ' . $where . ' ORDER BY Quelle ASC, Subvocem COLLATE utf8_unicode_ci ASC', ARRAY_A);
			
			echo json_encode($results);
			break;
			
		case 'getPartsMorph':
		    $results = $db->get_results('SELECT Id_morph_Typ as id, lex_unique(Orth, Sprache, Genus) as text FROM morph_Typen WHERE Id_morph_Typ IN (' . implode(',', array_filter($_POST['ids'], 'is_numeric')) . ') ORDER BY Orth', ARRAY_A);
		    
		    echo json_encode($results);
		    break;
		    
		case 'add_problem':
		    if(!current_user_can('va_typification_tool_write'))
		        break;
		    
		    $db->insert('tprobleme', [
                'Id_Stimulus' => $_REQUEST['id_stimulus'],
                'Beleg' => $_REQUEST['record'],
                'Art' => $_REQUEST['kind'],
                'Ids' => implode(',', $_REQUEST['ids'])
		    ]);
		    
		    add_problem_comment($db, $db->insert_id);
		    
		    echo 'success';
		    break;
		    
		case 'getProblemList':
		    $problems = $db->get_results($db->prepare('SELECT Id_Problem, Beleg, count(*) as NumKommentare, Geloest FROM tprobleme JOIN tprobleme_kommentare USING (Id_Problem) WHERE Geloest < 2 AND Id_Stimulus = %d GROUP BY Id_Problem', $_REQUEST['id_stimulus']), ARRAY_A);
		    echo '<option value=""></option>';
		    foreach ($problems as $problem){
		        $style = '';
		        if ($problem['Geloest'] > 0){
		            $style .= 'background: LightGreen;';
		        }
		        else if ($problem['NumKommentare'] > 1){
		            $style .= 'background: yellow;';
		        }
		        echo '<option style="' . $style . '" value="' . $problem['Id_Problem'] . '">' . $problem['Beleg'] . '</option>';
		    }
		    break;
		    
		case 'resolve_problem':
		    if(!current_user_can('va_typification_tool_write'))
		        break;
		    
		    if (isset($_REQUEST['comment'])){
                add_problem_comment($db, $_REQUEST['id_problem']);
		    }
		    if ($_REQUEST['id_type']){
		        list($ids, $kind) = $db->get_row($db->prepare('SELECT Ids, Art FROM tprobleme WHERE id_problem = %d', $_REQUEST['id_problem']), ARRAY_N);
		        va_add_typification($db, explode(',', $ids), $kind, $_POST['id_type']);
		        $db->update('tprobleme', ['Geloest' => 2], ['Id_Problem' => $_REQUEST['id_problem']]);
		        echo 'Solved';
		    }
		    else {
		        $db->update('tprobleme', ['Geloest' => 1], ['Id_Problem' => $_REQUEST['id_problem']]);
		        va_get_tproblem_details($db);
		    }
		    break;
		
		case 'add_problem_comment':
		    if(!current_user_can('va_typification_tool_write'))
		        break;
		    
		    add_problem_comment($db, $_REQUEST['id_problem']);
		    va_get_tproblem_details($db);
		    break;
		    
		case 'getProblemDetails':
		    va_get_tproblem_details($db);
		    break;
		    
		case 'update_problem_comment':
		    if(!current_user_can('va_typification_tool_write'))
		        break;
		    
		    va_update_problem_comment($db, $_REQUEST['id_comment'], $_REQUEST['comment']);
		    va_get_tproblem_details($db);
		    break;
	}
}

function add_problem_comment ($db, $id_problem){
    $cpos = $db->get_var($db->prepare('SELECT Max(Position) FROM tprobleme_kommentare WHERE id_problem = %d', $id_problem));
	if ($cpos === null){
		$cpos = -1;
	}
    
    $db->insert('tprobleme_kommentare', [
        'Id_Problem' => $id_problem,
        'Kommentar' => stripslashes($_REQUEST['comment']),
        'Angelegt_Von' => wp_get_current_user()->user_login,
        'Id_morph_Typ' => $_REQUEST['id_type']?: null,
        'morph_Typ_Text' => $_REQUEST['type_text'],
        'Position' => $cpos + 1
    ]);
    
    $id_comm = $db->insert_id;
    
    if (isset($_REQUEST['refs']) && $_REQUEST['refs']){
        foreach ($_REQUEST['refs'] as $ref){
            $db->insert('tprobleme_referenzen', [
                'Id_Kommentar' => $id_comm,
                'Kommentar' => $ref['text'],
                'Id_Lemma' => $ref['id']?: null
            ]);
        }
    }
}

function va_get_tproblem_details ($db){
    $problem_data = $db->get_row($db->prepare('SELECT Ids, Art, Beleg, Geloest FROM tprobleme WHERE Id_Problem = %d', $_REQUEST['id_problem']), ARRAY_A);
    $id_list = explode(',', $problem_data['Ids']);
    
    if($problem_data['Art'] === 'G' || $problem_data['Art'] === 'K' || $problem_data['Art'] === 'GP'){
        $data = $db->get_row('
                    SELECT
                        GROUP_CONCAT(DISTINCT IF(Name_D != "" AND Name_D != Beschreibung_D, CONCAT(Name_D, " (", Beschreibung_D, ")"), Beschreibung_D)) as Konzepte,
                        GROUP_CONCAT(DISTINCT Bemerkung) as Bemerkungen
                    FROM Tokengruppen
                        LEFT JOIN VTBL_Tokengruppe_Konzept USING (Id_Tokengruppe)
                        LEFT JOIN Konzepte USING (Id_Konzept)
                    WHERE Id_Tokengruppe IN (' . implode(',', $id_list) . ')
                ', ARRAY_A);
    }
    else {
        $data = $db->get_row('
                    SELECT
                        GROUP_CONCAT(DISTINCT IF(Name_D != "" AND Name_D != Beschreibung_D, CONCAT(Name_D, " (", Beschreibung_D, ")"), Beschreibung_D)) as Konzepte,
                        GROUP_CONCAT(DISTINCT Bemerkung) as Bemerkungen
                    FROM Tokens
                        LEFT JOIN VTBL_Token_Konzept USING (Id_Token)
                        LEFT JOIN Konzepte USING (Id_Konzept)
                    WHERE Id_Token IN (' . implode(',', $id_list) . ')
                ', ARRAY_A);
    }
    
    echo '<table class="widefat fixed"><tr><th>Beleg</th><th>Konzepte</th><th>Bemerkungen</th></tr><tr><td style="font-family: doulosSIL; font-size: 1.5em;">' . $problem_data['Beleg'] . '</td><td>' . $data['Konzepte'] . '</td><td>' . $data['Bemerkungen'] . '</td></tr></table>';
    
    echo '<br /><br /><br />';
    
    $type_props = [];
    
    $comments = $db->get_results($db->prepare('
        SELECT Id_Kommentar, Kommentar, Position, Version, k.Angelegt_Von, k.Angelegt_Am, lex_unique(Orth, Sprache, Genus) AS morph_Typ, Id_morph_Typ, morph_Typ_Text 
        FROM tprobleme_kommentare k LEFT JOIN morph_Typen USING (Id_morph_Typ) 
        WHERE Id_Problem = %d
        ORDER BY Position ASC, Version ASC', $_REQUEST['id_problem']), ARRAY_A);
    foreach ($comments as $ic => $comment){
        
        
        if ($comment['Version'] == 1){
            $versions = [];
            if ($problem_data['Geloest'] && $ic == count($comments) - 1){
                $bgcolor = 'lightgreen';
            }
            else {
                $bgcolor = 'white';
            }
            
            echo '<div class="commentContainer" style="border: 1px solid #c3c4c7; box-shadow: 0 1px 1px rgba(0,0,0,.04); background: ' . $bgcolor . '; padding: 20px; margin-bottom: 10px;">';
        }

        echo '<div style="display:none" class="comment_version_text" data-version="' . $comment['Version'] . '">' . nl2br($comment['Kommentar']) . '</div>';
        $versions[$comment['Version']] = ['timestamp' => $comment['Angelegt_Am'], 'creator' => $comment['Angelegt_Von']];
        
        if ($ic == count($comments) - 1 || $comments[$ic + 1]['Position'] != $comment['Position']){
            if ($comment['Angelegt_Von'] == wp_get_current_user()->user_login){
                echo '<span style="float: right; cursor: pointer;" class="edit_tp_comment dashicons dashicons-edit" data-id="' . $comment['Id_Kommentar'] . '" ></span>';
            }
            echo '<div class="commentContent" data-version="' . $comment["Version"] . '">' . nl2br($comment['Kommentar']) . '</div><br /><br />';
            if ($comment['morph_Typ'] || $comment['morph_Typ_Text']){
                echo '<b>Typvorschlag</b>: ';
                if ($comment['morph_Typ']){
                    echo $comment['morph_Typ'] . ' ';
                    $type_props[] = [$comment['Id_morph_Typ'], $comment['morph_Typ']];
                }
                
                if ($comment['morph_Typ_Text']){
                    echo $comment['morph_Typ_Text'];
                    if (!$comment['morph_Typ']){
                        $type_props[] = [-1, $comment['morph_Typ_Text']];
                    }
                }
            }
            
            $refs = $db->get_results($db->prepare('SELECT Kommentar, Quelle, Subvocem, Bibl_Verweis, Link FROM tprobleme_referenzen LEFT JOIN lemmata USING (Id_Lemma) WHERE Id_Kommentar = %d', $comment['Id_Kommentar']), ARRAY_A);
            if ($refs){
                echo '<br /><br /><b>Referenzen</b>:<br />';
                foreach ($refs as $ref){
                    if ($ref['Quelle']){
                        echo $ref['Quelle'] . ': ' . $ref['Subvocem'];
                        if ($ref['Bibl_Verweis']){
                            echo ', ' . $ref['Bibl_Verweis'];
                        }
                        if ($ref['Link']){
                            echo ' <a target="_BLANK" href="' . $ref['Link'] . '">Link</a>';
                        }
                        
                        if ($ref['Kommentar']){
                            echo ' (' . $ref['Kommentar'] . ')';
                        }
                    }
                    else {
                        echo $ref['Kommentar'];
                    }
                    
                    echo '<br />';
                }
            }
            
            echo '<br /><br /><div style="overflow: hidden;"><span style="float: right">';
            if ($comment['Version'] == 1){
                echo $comment['Angelegt_Von'] . ' (' . date('d.m.Y H:i', strtotime($comment['Angelegt_Am'])) . ')';
            }
            else {
                echo '<select class="comment_version_select">';
                foreach ($versions as $vnum => $vdata){
                    echo '<option' . ($vnum == count($versions)? ' selected': '') . ' value="' . $vnum . '">Version ' . $vnum . ': ' . $vdata['creator'] . ' (' . date('d.m.Y H:i', strtotime($vdata['timestamp'])) . ')' . '</option>';
                }
                echo '</select>';
            }
            echo '<span></div>';
            
            echo '</div>';
        }
    }
    
    echo '<br /><br />';
    
    echo '<div id="reactionDiv"><ul>';
    if ($problem_data['Geloest']){
        echo '<li><a href="#resolve">Typisierung anlegen</a></li></ul>';
    }
    else {
        echo '<li><a href="#comment">Kommentar hinzufügen</a></li><li><a href="#resolve">Problem klären</a></li></ul>';
    }
    
    
    
    if ($problem_data['Geloest']){
        va_tproblem_add_resolve_div(true);
    }
    else {
        va_tproblem_add_comment_div();
        va_tproblem_add_resolve_div(false, $type_props);
    }

    echo '</div>';
}

function va_tproblem_add_comment_div (){
    ?>
    <div id="comment" style="padding: 5px;">
        <textarea id="problemComment" cols="100" rows="10"></textarea>
        <br />
        <b>Typvorschlag:</b> Bestehender Typ <select class="selectExisting" style="margin-bottom: 5px; min-width: 300px;"><option value=""></option></select> und/oder Freitext 
        <input id="problemNewType" type="text" style="min-width: 300px;" /><br />
    
       	<br /><table id="problemRefTable"></table><input type="button" value="<?php _e('Add reference', 'verba-alpina'); ?>" class="button button-secondary problemRefButton" />
       	
       	<br ><br />
       	
        <div style="overflow: hidden;"><input type="button" style="float: right;" id="commentAddButton" value="Kommentar erstellen" class="button button-primary" />
       	</div>
   	</div>
   	<?php
}

function va_tproblem_add_resolve_div ($resolved, $type_props = null){
    echo '<div id="resolve" style="padding: 5px;">';

    if ($resolved){
        $type_text = 'Typ auswählen';
        $stext = 'Typisieren';
    }
    else {
        echo '<textarea id="resolveComment" cols="100" rows="10"></textarea><br />';
        
        if (count($type_props) > 0){
            echo '<b>Typvorschlag verwenden</b>: <select id="resolveTypeProp">';
            echo '<option value=""></option>';
            foreach ($type_props as $tpdata){
                $style = '';
                if ($tpdata[0] != -1){
                    $style .= 'background: LightGreen;';
                }
                echo '<option style="' . $style . '" value="' . $tpdata[0] . '">' . $tpdata[1] . '</option>';
            }
            echo '</select>';
            echo va_get_info_symbol('Grün markierte Vorschläge markieren bestehende Typen. Beim Klären des Problems wird der Beleg direkt typisiert.');
            echo '<br />';
            
            echo '<br />oder<br />';
        }
        $type_text = 'Anderen bestehenden Typ verwenden';
        $stext = 'Problem klären';
    }

    echo '<div style="margin-top: 20px;"><b>' . $type_text . '</b>: <select class="selectExisting" style="min-width: 300px;"><option value=""></option></select><input type="button" class="button button-primary" id="newVAType" value="Neuen Typ erstellen" /></div><br />';
    
    if (!$resolved){
        echo 'oder<br /><br />';
        echo '<b>Typ-Orthographie angeben</b>: <input id="onlyOrth" type="text" value="" />';
        echo va_get_info_symbol('Wird nur eine Typ-Orthographie angegeben, wird nur das Problem als gelöst markiert, der eigentliche Typ muss noch nachträglich erstellt werden.');
    }
    echo '<div style="overflow: hidden;"><input type="button" style="float: right;" id="problemResolveButton" value="' . $stext . '" class="button button-primary" /></div></div>';
    
    
    //                 echo '<b>Typ:</b> <select id="commentTypeSelect" style="margin-bottom: 5px; min-width: 300px;"><option value=""></option></select><input id="problemNewType" type="text" style="min-width: 300px;" /><br />';
    //                 echo '<br /><table id="problemRefTable"></table><input type="button" value="' . __('Add reference', 'verba-alpina') . '" class="button button-secondary problemRefButton" /><br ><br />';
    //                 echo '<div style="overflow: hidden;"><input type="button" style="float: right;" id="commentAddButton" value="Kommentar erstellen" class="button button-primary" /></div></div>';
}

function va_add_typification ($db, $tids, $kind, $id_type){
    if($kind === 'G' || $kind === 'K' || $kind === 'GP'){
        $db->query("DELETE VTBL_Tokengruppe_morph_Typ FROM VTBL_Tokengruppe_morph_Typ JOIN morph_Typen m WHERE m.Quelle = 'VA' AND Id_Tokengruppe IN (" . implode(',', $tids) . ')');
        $db->query($db->prepare("
			INSERT INTO VTBL_Tokengruppe_morph_Typ (Id_Tokengruppe, Id_morph_Typ, Angelegt_Von, Angelegt_Am)
			SELECT Id_Tokengruppe, %d, %s, NOW()
			FROM Tokengruppen WHERE Id_Tokengruppe IN (" . implode(',', $tids) . ')', $id_type, wp_get_current_user()->user_login));
    }
    else {
        $db->query("DELETE VTBL_Token_morph_Typ FROM VTBL_Token_morph_Typ JOIN morph_Typen m USING (Id_morph_Typ) WHERE m.Quelle = 'VA' AND Id_Token IN (" . implode(',', $tids) . ')');
        $db->query($db->prepare("
				INSERT INTO VTBL_Token_morph_Typ (Id_Token, Id_morph_Typ, Angelegt_Von, Angelegt_Am)
				SELECT Id_Token, %d, %s, NOW()
				FROM Tokens WHERE Id_Token IN (" . implode(',', $tids) . ')', $id_type, wp_get_current_user()->user_login));
    }
}

function va_update_problem_comment ($db, $id_comment, $comment){
    list($position, $version, $id_problem, $id_mtype, $mttext) = $db->get_row($db->prepare('SELECT Position, Version, Id_Problem, Id_morph_Typ, morph_Typ_Text FROM tprobleme_kommentare WHERE Id_Kommentar = %d', $id_comment), ARRAY_N);
    $db->insert('tprobleme_kommentare', [
        'Id_Problem' => $id_problem,
        'Kommentar' => $comment,
        'Angelegt_Von' => wp_get_current_user()->user_login,
        'Id_morph_Typ' => $id_mtype,
        'morph_Typ_Text' => $mttext,
        'Position' => $position,
        'Version' => $version + 1
    ]);
    
    $id_comm = $db->insert_id;
    
    $refs = $db->get_results($db->prepare('SELECT Kommentar, Id_Lemma FROM tprobleme_referenzen WHERE Id_Kommentar = %d', $id_comment), ARRAY_A);
    foreach ($refs as $ref){
        $ref['Id_Kommentar'] = $id_comm;
        $db->insert('tprobleme_referenzen', $ref);
    }
}
?>