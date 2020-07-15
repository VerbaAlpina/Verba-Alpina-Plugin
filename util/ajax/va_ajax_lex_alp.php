<?php
function va_ajax_lex_alp ($db){
    global $vadb;
   
	switch ($_POST['query']){

	case 'get_text_content':
        $html = '';
    
        $loc = get_locale();
        $langdb = explode("_", $loc)[0];
        $ids = $_POST['id'];
    
        $length = count($ids);
    	for ($i = 0; $i < $length; $i++) {
            $html .= va_lex_alp_get_text_content($ids[$i], $langdb);
    	}
    
    	echo $html;

	break;

	case 'get_all_articles':
		echo json_encode(va_get_lex_alp_header_data());
	break;

	case 'get_secondary_data':

		 $id = $_POST['id'];
	     $type = $_POST['type'];
	     $p_type = $_POST['parent_type'];
	     $p_id = $_POST['parent_id'];
		 echo json_encode(get_secondary_data($id,$type,$p_id,$p_type));
	break;

	case 'get_search_results':
	    global $lang;
	    
// 	    $s_string = '+' . str_replace(' ', ' +', $_POST['search_val']);

// 		$sql = '
//             SELECT Id
//             FROM im_comments
//             WHERE SUBSTRING(Language, 1, 1) = %s AND (MATCH (Comment) AGAINST (%s IN BOOLEAN MODE))
//             UNION
//             SELECT Id
//             FROM a_lex_titles
//             WHERE lang = %s AND (MATCH (Title) AGAINST (%s IN BOOLEAN MODE))';

//  	echo json_encode($vadb->get_col($vadb->prepare($sql, $lang, $s_string, $lang, $s_string)));
		
	    $search_vals_title = array_map(function ($str){return ' AND Title LIKE "%' . esc_sql($str) . '%"';}, explode(' ', $_POST['search_val']));
	    $search_vals_title[0] = mb_substr($search_vals_title[0], 5);
	    $search_vals_content = array_map(function ($str){return ' AND Comment LIKE "%' . esc_sql($str) . '%"';}, explode(' ', $_POST['search_val']));
	    $search_vals_content[0] = mb_substr($search_vals_content[0], 5);
		
		$sql = 'SELECT t.Id
            FROM a_lex_titles t
            	LEFT JOIN im_comments i ON t.Id = i.Id AND t.lang = SUBSTRING(i.`Language`, 1, 1)
            WHERE lang = "' . esc_sql($lang) . '" AND ((' . implode(' ', $search_vals_title) . ') OR (' . implode(' ', $search_vals_content) . '))
            ORDER BY Sort_Number ASC';
		
		echo json_encode($vadb->get_col($sql));
	break;
		
	case 'get_filter_results':
	    global $lang;
	    
	    $search_vals = array_map(function ($str){return ' AND (Title LIKE "%' . esc_sql($str) . '%" OR Entity LIKE "%' . esc_sql($str) . '%")';}, explode(' ', $_POST['search_val']));
	    $sql = 'SELECT t.Id FROM a_lex_titles t WHERE lang = "' . esc_sql($lang) . '" ' . implode(' ', $search_vals) . ' ORDER BY Sort_Number DESC';
	    
	    echo json_encode($vadb->get_col($sql));
	break;
	
	case 'get_images':
	    if (substr($_POST['id'], 0, 1) === 'C'){
	       $sql = 'SELECT Dateiname FROM VTBL_Medium_Konzept JOIN Medien USING (Id_Medium) WHERE Id_Konzept = %d';
	       echo json_encode($vadb->get_col($vadb->prepare($sql, substr($_POST['id'], 1))));
	    }
	    else {
	        echo [];
	    }
	    
	    break;
	    
	case 'save_state':
	    $data = $_REQUEST['data'];
	    
	    $db->select('va_xxx');
	    $res = $db->insert('lex_saves', ['version' => $_REQUEST['version_number'], 'scroll_pos' => $data['scrollPos'], 'highlighted' => $data['highlightString']]);
        $id = $db->insert_id;
	    
        if (!$res || !$id){
	        echo 'Error';
	        die;
        }
        
        foreach ($data['articles'] as $article){
            $state = 'front_min';
            if (isset($article['frontOpen']) && $article['frontOpen']){
                $state = 'front_max';
            }
            else if (isset($article['backOpen']) && $article['backOpen']){
                $state = 'back';
            }
            
            $sub_data = null;
            if (isset($article['openSubs'])){
                $sub_data = json_encode($article['openSubs']);
            }
            
            
            $res = $db->insert('lex_saves_entries', ['id_lex_save' => $id, 'id_entry' => $article['id'], 'state' => $state, 'open_subs' => $sub_data]);
            
            if (!$res){
                echo 'Error';
                die;
            }
        }
	    
        echo $id;
	    break;
	    
	case 'load_state':
	    $db->select('va_xxx');
	    $res = $db->get_row($db->prepare('SELECT version, scroll_pos, highlighted FROM lex_saves WHERE id_lex_save = %d', $_REQUEST['id']), ARRAY_A);
	    if (!$res){
	        echo 'INVALID_STATE';
	        die;
	    }
	    
	    $res['articles'] = [];
	    
	    $articles = $db->get_results($db->prepare('SELECT id_entry, state, open_subs FROM lex_saves_entries WHERE id_lex_save = %d', $_REQUEST['id']), ARRAY_A);
	    foreach ($articles as $article){
	        $article_data = ['id'=> $article['id_entry']];
	        
	        if ($article['state'] === 'front_max'){
	            $article_data['frontOpen'] = true;
	        }
	        
	        if ($article['state'] === 'back'){
	            $article_data['backOpen'] = true;
	        }
	        
	        if ($article['open_subs']){
	            $article_data['openSubs'] = json_decode($article['open_subs']);
	        }
	        
	        
	        $res['articles'][] = $article_data;
	    }
	    
	    echo json_encode($res);
	    break;
	}
}

function va_lex_alp_get_text_content ($id, $langdb){
    global $admin;
    global $va_mitarbeiter;
    global $Ue;
    global $va_current_db_name;
    global $vadb;
    
    $type = substr($id, 0, 1);
    $text =  va_get_comment_text($id, $langdb, $admin || $va_mitarbeiter);
    
    
    $html ='<div id="detailview_'.$id.'" class="lex_article type_'.$type.'">';
    
    $html.='<div class="flipbutton arrow type_'.$type.'"> <i class="fas fa-database"></i> <span class="text">Daten</span> <span class="arrow"></span> </div>';
    
    $html.= '<div class="front">';
    
    $html.= '<div class="f_content">';
    
    $html.='<div class="head">';
    
    $html.= '<div class="lex_button_container">';
    
    $html.= '<a class="lex_edit" title="'.$Ue['KARTE_VISUALISIEREN'].'" target="_BLANK" href="' . va_get_map_link($id) . '"><button class="actionbtn"><i class="fas fa-map-marked-alt"></i></button></a>';
    
    
    if($va_current_db_name != 'va_xxx'){
        $citation = va_create_comment_citation($id, $Ue);

        if ($citation){
            $html.= '<span class="sep"></span>';
            $html.= '<a data-quote="'. htmlspecialchars($citation).'" class="lex_edit lex_quote" title="'.$Ue['ZITIEREN'].'"><button class="actionbtn"><i class="fas fa-quote-right"></i></button></a>';
        }
    }
    
    
    if($text && $va_current_db_name != 'va_xxx'){
        $html.= '<span class="sep"></span>';
        $html.= '<a class="lex_edit" title="Download" target="_BLANK" href="https://www.verba-alpina.gwi.uni-muenchen.de/?api=1&action=getText&id=' . $id . '&lang=' . $langdb . '&version=' . substr($va_current_db_name, 3) . '"><button class="actionbtn"><i class="fas fa-file-download"></i></button></a>';
    }
    
    
    if(($admin || $va_mitarbeiter) && $va_current_db_name == 'va_xxx'){
        $html.= '<span class="sep"></span>';
        $html .= '<a class="lex_edit" title="'.$Ue['BEARBEITEN'].'" target="_BLANK" href="' . get_admin_url(1) . 'admin.php?page=edit_comments&comment_id=' . $id . '"><button class="actionbtn"><i class="fas fa-pencil-alt"></i></button></a>';
    }
    
    // $html .= '<span class="sep"></span>';
    // $html .= '<a class="lex_flip lex_edit" title="Data"><button class="actionbtn"><i class="fas fa-redo"></i></button></a>';

    //TODO: IMPLEMENT IMAGE BUTTON IN JS

    // if ($type === 'C'){
    //     $img = $vadb->get_var($vadb->prepare('SELECT Id_Medium FROM VTBL_Medium_Konzept WHERE Id_Konzept = %d', substr($id, 1)));
    //     if ($img){
    //         $html .= '<span class="sep"></span>';
    //         $html .= '<a class="lex_flip lex_edit" title="Images"><button class="actionbtn"><i class="fas fa-images"></i></button></a>';
    //     }
    // }

    $html .= '<span class="sep"></span>';
    $html .= '<a class="lex_close lex_edit" title="Schließen"><button class="actionbtn"><i class="fas fa-times"></i></button></a>';
    
    $html.='</div>';
    
    
    $html.='<div class="lex_head_container">';
    $html.=  va_get_comment_title($id, $langdb);
    $html.= '<span class="sep"></span>';
    $html.=  '<span class="lex_id" title="Identifier">' .$id. '</span>';
    $html.='</div>';
    
    
    if($type=='C') $html.= va_get_comment_description($id, $langdb);
    
    $html.='</div>';
    
    $html.='<div class="content">';
    
    if($text) $html.= $text;
    
    $html.='</div>';
    
    $html.='<div class="lexfooter">';
    
    if($admin || $va_mitarbeiter){
        $html .= '<span class="lex_comment_id">   [[Kommentar:' . $id . ']]</span>';
    }
    
    if(va_version_newer_than('va_171')){
        
        $auth = $vadb->get_results($vadb->prepare("
									SELECT Vorname, Name, Affiliation
									FROM VTBL_Kommentar_Autor JOIN Personen USING (Kuerzel)
									WHERE Id_Kommentar = %s AND Aufgabe = 'auct'", $id), ARRAY_N);
        $trad = $vadb->get_results($vadb->prepare("
									SELECT Vorname, Name, Affiliation
									FROM VTBL_Kommentar_Autor JOIN Personen USING (Kuerzel)
									WHERE Id_Kommentar = %s AND Aufgabe = 'trad' AND Sprache = %s", $id, $langdb), ARRAY_N);
    }
    
    if(va_version_newer_than('va_171') && $text){
        $html.= '<div>' . va_add_glossary_authors($auth, $trad) . '</div>';
    }
    
    if(($va_mitarbeiter || $admin) && $va_current_db_name === 'va_xxx'){
        $html.= ' <b>' . $vadb->get_var($vadb->prepare('SELECT Approved FROM im_comments WHERE Id = %s AND Language = %s', $id, 'de')) . '</b>';
    }
    
    
    if ($type === 'C'){
        $qid = $vadb->get_var($vadb->prepare('SELECT QID FROM Konzepte WHERE Id_Konzept = %s', substr($id, 1)));
        if ($qid){
            $html.=  '<div class="lex_wiki"> (' . $Ue['SIEHE'] . ' <a target="_BLANK" href="https://www.wikidata.org/wiki/Q' . $qid . '"> Wikidata Q' . $qid . '</a>)</div>';
        }
    }
    
    
    $html.='</div>'; //footer
    
    $html.='</div>'; //f_content
    
    
    $html.='</div>'; //front
    
    
    
    $html.='<div class="back">';
    
    $html.='<div class="b_content">';
    
    
    
    $html.='<div class="head">';
    
    $html.= '<div class="lex_button_container bback">';
    
    $html.= '<a class="lex_edit" title="'.$Ue['KARTE_VISUALISIEREN'].'" target="_BLANK" href="' . va_get_map_link($id) . '"><button class="actionbtn"><i class="fas fa-map-marked-alt"></i></button></a>';
    
    if ($va_current_db_name != 'va_xxx'){
        $html.= '<span class="sep"></span>';
        $html.= '<a class="lex_edit" title="Download" target="_BLANK" href="https://www.verba-alpina.gwi.uni-muenchen.de/?api=1&action=getRecord&id=' . $id . '&version=' . substr($va_current_db_name, 3) . '&format=xml&empty=0"><button class="actionbtn"><i class="fas fa-file-download"></i></button></a>';
    }
    
    
    $html .= '<span class="sep"></span>';
    $html .= '<a class="lex_close lex_edit" title="Schließen"><button class="actionbtn"><i class="fas fa-times"></i></button></a>';
    
    $html.='</div>';
    
    $html.='<div class="lex_head_container">';
    $html.=  va_get_comment_title($id, $langdb);
    $html.= '<span class="sep"></span>';
    $html.=  '<span class="lex_id" title="Identifier">' .$id. '</span>';
    $html.='</div>';
    
    $html.='</div>';
    
    $html.='<div class="back_body">';
    
    
    $html.= getBackContent($type,$id);
    
    
    $html.='</div>';
    
    
    
    $html.='</div>';
    
    $html.='</div>';
    
    
    
    $html.='</div>';
    return $html;
}

function getBackContent($type, $id){


	$html = '';
			if($type=='C') {

							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> Morpholexikalische Typen</div>';

								$html.='<div class="hiddenbackcontent">';
											$html.= getTableInfo($id,"C","L");
								$html.='</div>';

							$html.='</div>';


							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> Basistypen</div>';


								$html.='<div class="hiddenbackcontent">';
											$html.= getTableInfo($id,"C","B");
								$html.='</div>';


							$html.='</div>';
						
			}

			if($type=='L') {

							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> Konzepte</div>';

								$html.='<div class="hiddenbackcontent">';
											$html.= getTableInfo($id,"L","C");
								$html.='</div>';

							$html.='</div>';

							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> Orte</div>';

								$html.='<div class="hiddenbackcontent">';
											$html.= getTableInfo($id,"L","A");
								$html.='</div>';

							$html.='</div>';
						
			}


			if($type=='B') {


							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> Morpholexikalische Typen</div>';

								$html.='<div class="hiddenbackcontent">';
											$html.= getTableInfo($id,"B","L");
								$html.='</div>';

							$html.='</div>';

							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> Orte</div>';


								$html.='<div class="hiddenbackcontent">';
											$html.= getTableInfo($id,"B","A");
								$html.='</div>';


							$html.='</div>';	

							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> Konzepte</div>';


								$html.='<div class="hiddenbackcontent">';
											$html.= getTableInfo($id,"B","C");
								$html.='</div>';

							$html.='</div>';								
			}

return $html;
}

function getTableInfo($id,$owntype,$othertype){

$html = '';
$html .='<table class="backtable" style="width:100%" type="'.$othertype.'">';
$html .='<thead>';

$id_trim = ltrim($id, $id[0]); 

$info = get_table_data($othertype,[$owntype => intval($id_trim)]);

	if(count($info)==0) return;

	$first_row = $info[0];
	$keys = array_keys($first_row);

	$html.='<tr>';
	foreach ($keys as &$value) {
	   			 $html .= '<th>';
				 $html .= $value;
				 $html .= '</th>';
	 }
	 $html.='</tr>';

	 $html .='</thead>';
     $html .='<tbody>';

	for ($i = 0; $i < count($info); $i++){
		$row = $info[$i];

			$html .= '<tr id="'.$row[array_keys($row)[0]].'">';

			$rc = 0;

			foreach ($row as &$value) {
   			 $html .= '<td>';
   			 	if($rc==0){
   			 		$html.= '<span class="list_marker type_'.$owntype.'"></span>';
   			 	}
			 $html .= $value;
			 $html .= '</td>';
			 $rc++;
			}

			$html .= '</tr>';
	}

 	$html .='</tbody>';
$html.='</table>';


return $html;


}


function get_secondary_data($id,$type,$p_id,$p_type){

	$info = get_table_data('I',[$type => intval($id), $p_type => intval($p_id)]);
	return $info;

}


function get_table_data ($type, $ids){
    
    global $vadb;
    global $lang;
	global $Ue;
    
    switch ($type){
        case 'I': //instances
            $select = [
                'External_Id AS VAID', 
                'IF(Instance = "", (SELECT CONCAT("Typ ", Type) FROM z_ling z2 WHERE z2.Id_Instance = z_ling.Id_Instance AND z2.Source_Typing != "VA" LIMIT 1), Instance) AS Instance', 
                'Instance_Source', 
                'Community_Name', 
                'IF(Name != "", Name, Description) AS Concept'
            ];
            $format = [
                'Instance_Source' => function ($source_string){
                    $sdata = explode('#', $source_string);
                 return $sdata[0] . ' ' . $sdata[1] . '#' . $sdata[2] . $sdata[3] . ' (' . $sdata[4] . ')';
                },
                'Community_Name' => function ($name) {global $lang; return va_translate_extra_ling_name($name, $lang);},
                'Concept' => function ($des) {global $Ue; if ($des === null) return $Ue['KEIN_KONZEPT']; return $des;}
            ];
            $where = '(lang IS NULL OR lang = "' . $lang . '")';
            $join = ' LEFT JOIN z_concepts USING (Id_Concept)';
            $combine = [];
            $order = ['Instance_Source', 'Community_Name', 'Instance'];
            break;
        case 'L': //morph-lex types
            $select = ['CONCAT("L", Id_Type) AS VAID', 'Type', 'Type_Lang', 'POS', 'Gender', 'Type_Reference'];
            $format = [
                'Type' => function ($type) {global $Ue; if ($type === null) return $Ue['NICHT_TYPISIERT']; return $type;}
            ];
            $where = '(Id_Type IS NULL OR (Type_Kind = "L" AND Source_Typing = "VA"))';
            $join = '';
            $combine = ['Type_Reference' => function ($ref){return va_add_references('', $ref);}];
            $order = ['Type', 'Type_Lang', 'POS', 'Gender'];
            break;
        case 'B': //base types
            $select = ['CONCAT("B", Id_Base_Type) AS VAID', 'Base_Type', 'Base_Type_Lang', 'Base_Type_Unsure', 'Base_Type_Reference'];
            $format = [
                'Base_Type' => function ($type) {global $Ue; if ($type === null) return $Ue['NICHT_TYPISIERT']; return $type;},
                'Num_Instances' => function ($num, $id_btype, $sql_data, $ids){
                    //Compute the count of instances separately since there are several special case (e.g. only source typification, some lex. have base types some not, etc.)
                    if (!$id_btype){
                        $sql = 
                            'select count(*) from
                            (SELECT Id_Instance 
                            FROM z_ling
                            WHERE ' . $sql_data['where'] . ' AND Alpine_Convention 
                            GROUP BY Id_Instance
                            having sum(Id_Base_Type) is null) z;';
                        
                        global $vadb;
                        
                        if ($ids){
                            return $vadb->get_var($vadb->prepare($sql, array_values($ids)));
                        }
                        else {
                            return $vadb->get_var($sql);
                        }
                    }
                    return $num;
                }
            ];
            $where = '1';
            $join = '';
            $combine = [
                'Base_Type_Reference' => function ($refs){return va_add_references('', $refs);}, 
                'Base_Type_Unsure' => function($vals){return array_reduce($vals, function ($c, $i){return $c && $i;});}
            ];
            $order = ['Base_Type', 'Base_Type_Lang'];
            break;
            
        case 'A': //communities
            $select = ['CONCAT("A", Id_Community) AS VAID', 'Community_Name', 'Geonames_Id AS Geonames'];
            $format = [
                'Community_Name' => function ($name) {global $lang; return va_translate_extra_ling_name($name, $lang);}
            ];
            $where = '1';
            $join = '';
            $combine = [];
            $order = ['Community_Name'];
            break;
        case 'C': //Concepts
            $select = ['CONCAT("C", Id_Concept) AS VAID', 'Description', 'Name', 'QID'];
            $format = [
                'Description' => function ($des) {global $Ue; if ($des === null) return $Ue['KEIN_KONZEPT']; return $des;}
            ];
            $where = '(lang IS NULL OR lang = "' . $lang . '")';
            $join = ' LEFT JOIN z_concepts USING (Id_Concept)';
            $combine = [];
            $order = ['Description'];
            break;
        default:
            throw new Exception('Unknown type: ' . $type);
    }
    
    $key_col = 'VAID';
    $having = '';
    
    foreach (array_keys($ids) as $stype){
        switch ($stype){
            case 'C':
                if ($ids[$stype]){
                    $concepts = $vadb->get_col($vadb->prepare('SELECT Id_Konzept FROM A_Ueberkonzepte_Erweitert WHERE Id_Ueberkonzept = %d', $ids[$stype]));
                    $concepts[] = $ids[$stype];
                    $where .= $vadb->prepare(' AND Id_Concept IN ' . im_key_placeholder_list($concepts), $concepts);
                    $ids[$stype] = null;
                }
                else {
                    $where .= ' AND Id_Concept IS NULL';
                }
                break;
            case 'L':
                $where .= $ids[$stype]? ' AND Id_Type = %d AND Source_Typing = "VA" AND Type_Kind = "L"': ' AND Id_Type IS NULL';
                break;
            case 'B':
                $where .= $ids[$stype]? ' AND Id_Base_Type = %d': '';
                $having = $ids[$stype]? '': ' SUM(Id_Base_Type) IS NULL';
                break;
            case 'A':
                $where .= ' AND Id_Community = %d';
                break;
            default:
                throw new Exception('Unknown type: ' . $stype);
        }
        
        if (!$ids[$stype]){
            unset($ids[$stype]);
        }
    }

    $select_clause = implode(',', $select);
    if ($type !== 'I'){
        $select_clause .= ', COUNT(DISTINCT Id_Instance) AS Num_Instances';
    }
    $group_clause = implode(',', array_map(function ($col){
        $pos_as = mb_strpos($col, ' AS ');
        if ($pos_as !== false){
            return mb_substr($col, $pos_as + 4);
        }
        return $col;
    }, $select));
    
    $sql = 'SELECT ' . $select_clause . ' FROM z_ling' . $join . ' WHERE ' . $where . ' AND Alpine_Convention GROUP BY ' . $group_clause . ($having? ' HAVING' . $having: '') . ' ORDER BY VAID';
    
    //va_query_log($vadb->prepare($sql, array_values($ids)));
    if ($ids){
        $rows = $vadb->get_results($vadb->prepare($sql, array_values($ids)), ARRAY_A);
    }
    else {
        $rows = $vadb->get_results($sql, ARRAY_A);
    }

    $res = [];
    $keys_handled = [];
    
    $sql_data = ['select' => $select, 'join' => $join, 'where' => $where, 'having' => $having]; 
    
    $i = 0;
    while ($i < count($rows)){
        $row = $rows[$i];
        
        if (!in_array($row[$key_col], $keys_handled)){
            $keys_handled[] = $row[$key_col];
            $res_row = [];
            
            foreach ($row as $col => $val){
                if (isset($combine[$col])){
                    $res_row[va_translate_lex_col($col)] = [];
                }
                else {
                    if (isset($format[$col])){
                        $val = $format[$col]($val, $row[$key_col], $sql_data, $ids);
                    }
                    
                    $res_row[va_translate_lex_col($col)] = $val;
                }
            }
            
            while ($i < count($rows) && $rows[$i][$key_col] == $row[$key_col]){
                foreach (array_keys($combine) as $ccol){
                    if ($rows[$i][$ccol] !== null){
                        $res_row[va_translate_lex_col($ccol)][] = $rows[$i][$ccol];
                    }
                }
                $i++;
            }
            
            foreach ($combine as $ccol => $fun){
				$ccol = va_translate_lex_col($ccol);
                $res_row[$ccol] = $fun($res_row[$ccol]);
            }
            $res[] = $res_row;
        }
    }
    
    $key_col = va_translate_lex_col($key_col);
    foreach ($order as $i => $col){
        $order[$i] = va_translate_lex_col($col);
    }
    
    usort($res, function($row_1, $row_2) use ($order, $key_col){
        foreach ($order as $ocol){
            
            $name_1 = $row_1[$key_col]? va_remove_special_chars($row_1[$ocol]): $row_1[$ocol]; //Don't simplify string for null values
            $name_2 = $row_2[$key_col]? va_remove_special_chars($row_2[$ocol]): $row_2[$ocol];
            
            $val = strcasecmp($name_1, $name_2);
            if ($val){
                return $val;
            }
        }
        return 0;
    });
    
    return $res;
}

function va_translate_lex_col ($str){
	
	if ($str === 'VAID'){
		return 'VA-ID';
	}
	
	global $Ue;
	
	$translations = [
		'Instance' => 'BELEG',
		'Instance_Source' => 'QUELLE',
		'Community_Name' => 'GEMEINDE',
		'Concept' =>  'KONZEPT',
		'Type' => 'MORPH_TYP_ABKUERZUNG', 
		'Type_Lang' => 'SPRACHE',
		'POS' => 'WORTART',
		'Gender' => 'GENUS',
		'Type_Reference' => 'TYP_REFERENZEN',
		'Base_Type' => 'BASISTYP',
		'Base_Type_Lang' => 'SPRACHE',
		'Base_Type_Unsure' => 'TYPISIERUNG_UNSICHER',
		'Base_Type_Reference' => 'TYP_REFERENZEN',
		'Description' => 'DESCRIPTION',
		'Name' => 'NAME',
		'Num_Instances' => 'ANZAHL_BELEGE'
	];
	
	if (isset($translations[$str])){
		return $Ue[$translations[$str]];
	}
	
	return $str;
}

?>