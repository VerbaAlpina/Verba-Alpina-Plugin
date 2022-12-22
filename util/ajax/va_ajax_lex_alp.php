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
	
	case 'get_all_municipalities':
		global $Ue;
		$res = $vadb->get_results('SELECT CONCAT("A", Id_Ort) AS Id, Name AS Title_Html FROM orte WHERE Id_Kategorie = 62 AND Alpenkonvention ORDER BY Name ASC', ARRAY_A);
		$res = array_map(function ($e) use ($Ue){
			$e['Title_Html'] = '<span class="name" title="' . htmlentities($e['Title_Html']) . '">' . htmlentities($e['Title_Html']) . '</span> - <span class="type">' . $Ue['GEMEINDE'] . '</span>';		
			return $e;
		}, $res);
		
		echo json_encode($res);
	break;

	case 'get_all_methodology':
		global $Ue;
		global $lang;
		
		$intern = current_user_can('va_glossary_edit');
		$titles = va_get_methodology_titles($intern, $lang);

		$res = array_map(function ($e) use ($Ue){
		    list($estyle, $eclass, $eimgs) = va_get_glossary_link_style($e['Id_Eintrag']);
		    
		    $html = $eimgs . '<span style="' . $estyle . '" class="name '. $eclass .'" title="' . htmlentities($e['Name']) . '">'
		        . htmlentities($e['Name']) . '</span> - <span class="type">' . $Ue['METHODOLOGIE'] . '</span>';
		    
		    $entry_data = [
		        'Id' => 'M' . $e['Id_Eintrag'],
		        'Title_Html' => $html,
		        'Tags' => $e['Tags']
		    ];

			
		    return $entry_data;
		}, $titles);
		
		echo json_encode($res);
	break;

	case 'get_secondary_data':

		 $id = $_POST['id'];
	     $type = $_POST['type'];
	     $p_type = $_POST['parent_type'];
	     $p_id = $_POST['parent_id'];
		 echo json_encode(get_secondary_data($id,$type,$p_id,$p_type));
	break;

	case 'get_concept_images':

		 $id = $_POST['id'];
		 echo json_encode(get_images_to_concept($id));

	break;


    case 'get_back_table':

         $id = $_POST['id'];
         $selftype = $_POST['selftype'];
         $othertype = $_POST['othertype'];

         echo json_encode(getTableInfo($id,$selftype,$othertype));

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
	
	case 'get_search_results_mun':
		$sql = 'SELECT CONCAT("A", Id_Ort) FROM Orte WHERE Id_Kategorie = 62 AND Alpenkonvention AND Name LIKE "%' . esc_sql($_POST['search_val']) . '%"';
		echo json_encode($vadb->get_col($sql));
		break;

	case 'get_search_results_meth':
		global $lang;
		$sql = 'SELECT CONCAT("M", Id_Eintrag) AS Id FROM glossar WHERE TERMINUS_'.esc_sql($lang).' LIKE"%'.esc_sql($_POST['search_val']) . '%" OR ERLAEUTERUNG_'.esc_sql($lang). ' LIKE "%' . esc_sql($_POST['search_val']) . '%"'.' ORDER BY TERMINUS_'.esc_sql($lang) .' ASC';

		echo json_encode($vadb->get_col($sql));
	 break;	
		
	case 'get_filter_results':
	    global $lang;
		
		if (preg_match('/[CBL][1-9][0-9]*/', $_POST['search_val'])){
			if ($vadb->get_var($vadb->prepare('SELECT Id FROM a_lex_titles WHERE Id = %s', $_POST['search_val']))){
				echo json_encode([$_POST['search_val']]);
			}
			else {
				echo json_encode([]);
			}
			break;
		}
	    
	    $search_vals = array_map(function ($str){return ' AND (Title LIKE "%' . esc_sql($str) . '%" OR Entity LIKE "%' . esc_sql($str) . '%")';}, explode(' ', $_POST['search_val']));
	    $sql = 'SELECT t.Id FROM a_lex_titles t WHERE lang = "' . esc_sql($lang) . '" ' . implode(' ', $search_vals) . ' ORDER BY Sort_Number DESC';
	    
	    echo json_encode($vadb->get_col($sql));
	break;
	
	case 'filter_municipalities_results':
		if (preg_match('/A[1-9][0-9]*/', $_POST['search_val'])){
			if ($vadb->get_var($vadb->prepare('SELECT Id_Ort FROM orte WHERE Alpenkonvention AND Id_Kategorie = 62 AND Id_Ort = %s', substr($_POST['search_val'], 1)))){
				echo json_encode([$_POST['search_val']]);
			}
			else {
				echo json_encode([]);
			}
			break;
		}
		
		$sql = 'SELECT CONCAT("A", Id_Ort) FROM Orte WHERE Alpenkonvention AND Id_Kategorie = 62 AND Name LIKE "%' . esc_sql($_POST['search_val']) . '%" ORDER BY Name ASC';
		echo json_encode($vadb->get_col($sql));
	break;

	case 'filter_methodology_results':

	   global $lang;

	    $search_vals = array_map(function ($str) use ($lang) {
	    	return 'AND TERMINUS_'.esc_sql($lang).' LIKE "%' . esc_sql($str) . '%"';}
	    	, explode(' ', $_POST['search_val']));


	     $search_vals[0] = substr_replace($search_vals[0],"",0,3); //remove first "AND"


	    $sql = 'SELECT CONCAT("M",Id_Eintrag) FROM glossar  WHERE ' . implode(' ', $search_vals) . ' ORDER BY TERMINUS_'.esc_sql($lang).' DESC';
	    
	    echo json_encode($vadb->get_col($sql));

	break;
	
	case 'get_images':
	    if (substr($_POST['id'], 0, 1) === 'C'){
	       $sql = 'SELECT Dateiname FROM VTBL_Medium_Konzept JOIN Medien USING (Id_Medium) WHERE Id_Konzept = %d AND NOT Konzeptillustration';
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
    global $lang;
    
    $type = substr($id, 0, 1);

    $db_id = substr($id, 1);
    $db_lang = strtoupper($langdb[0]);

    if ($type == 'M'){
        $glossary_entry = va_get_glossary_entry($db_id, $db_lang, true, "Methodologie", $Ue);
        va_add_glossary_meta_information($glossary_entry, $lang, $Ue);
        $text = va_get_glossary_html($glossary_entry[0], true);
        $info_str = false;
    }
    else {
        $text =  va_get_comment_text($id, $langdb, $admin || $va_mitarbeiter);
        $info_str = va_get_comment_auto_part($id, $langdb, $admin || $va_mitarbeiter);
    }
	
	

    $image_found = false;
    
    $id_classes = implode(' ', array_map(function ($id) use ($type){return 'lex_article_'. $type . $id;}, explode('+', substr($id, 1))));

    if ($type == 'M'){
        $id_classes .= " meth";
    }
    
    $html ='<div id="detailview_'.$id.'" class="lex_article type_'.$type.' ' . $id_classes . '">';

    if ($type != 'M'){
        $html.='<div class="flipbutton arrow type_'.$type.'"> <i class="fas fa-database"></i> <span class="text">' . $Ue['LEX_DATA'] . '</span> <span class="arrow"></span> </div>';
    }
    
    $html.= '<div class="front">';

    if ($type=='C'){
    	 
	    	 $c_id = ltrim($id, 'C');

	    	 $images = $vadb->get_results($vadb->prepare("
	    	 	SELECT Id_Medium
	    	 	FROM vtbl_medium_konzept
	    	 	WHERE Id_Konzept = %s AND NOT Konzeptillustration", $c_id), ARRAY_N);

	    	 //'+$images[0]+'

	    	  if(sizeof($images)>0){
	    	      
	    	      $image_id = $images[0][0];
	   		  		$image_found = true;
	   		  

	   		  		$url_arr = $vadb->get_results($vadb->prepare("
					SELECT Dateiname
					FROM Medien
					WHERE Id_Medium = %s ",$image_id), ARRAY_N);

					$url = $url_arr[0][0];

					$html.= '<div class="front_image" style="background-image:url('.$url.')">';
	   		  		$html.= '</div>';
	   		  	
	   		  }	

    }
    
    $html.= '<div class="f_content">';
    
    $html.='<div class="head">';
    
    $html.= '<div class="lex_button_container">';
	
    if ($type != "M"){
        $html.= '<a class="lex_edit" title="'.$Ue['KARTE_VISUALISIEREN'].'" target="_BLANK" href="' . va_get_map_link($id) . '"><button class="actionbtn"><i class="fas fa-map-marked-alt"></i></button></a>';
    }
    
    if($va_current_db_name != 'va_xxx' && $text){
        if ($type == 'M'){
            $citation = va_create_glossary_citation($db_id, $Ue);
            $cit_bibtex = va_create_glossary_bibtex($db_id, $Ue, true);
        }
        else {
            $citation = va_create_comment_citation($id, $Ue);
            $cit_bibtex = va_create_comment_bibtex($id, $Ue, true);
        } 

        if ($citation){
            if ($type != "M"){
                $html .= '<span class="sep"></span>';
            }
			$html.= '<a data-plain="'. $citation.'" data-bibtex="'. $cit_bibtex.'" class="lex_edit quote" title="'.$Ue['ZITIEREN'].'"><button class="actionbtn"><i class="fas fa-quote-right"></i></button></a>';
        }
    }
    
    
    if($va_current_db_name != 'va_xxx' && $text){
        global $lang;
        
		$html .= '<span class="sep"></span>';
		$lang_iso = va_lang_to_iso($lang);
        $html.= '<a class="lex_edit" title="Download" target="_BLANK" href="https://www.verba-alpina.gwi.uni-muenchen.de/?api=1&action=getText&id=' . $id . '_' . $lang_iso . '&version=' . substr($va_current_db_name, 3) . '"><button class="actionbtn"><i class="fas fa-file-download"></i></button></a>';
    }
    
    
    if(($admin || $va_mitarbeiter) && $va_current_db_name == 'va_xxx' && $text){

        if ($type != "M"){
            $html .= '<span class="sep"></span>';
        }
		

		if($type == 'M'){
		    $href = get_admin_url(1).'admin.php?page=va&entry='. $db_id;
		}
		else {
		    $href = get_admin_url(1) . 'admin.php?page=edit_comments&comment_id=' . $id;
		}

        $html .= '<a class="lex_edit" title="'.$Ue['BEARBEITEN'].'" target="_BLANK" href="' . $href . '"><button class="actionbtn"><i class="fas fa-pencil-alt"></i></button></a>';
    }

    
// 	if($type!="M" || $admin || $va_mitarbeiter) 

// 	if($type=="M" && $va_current_db_name != 'va_xxx') $html .= '<span class="sep"></span>';

    $html .= '<span class="sep"></span>';
	if ($type == 'M'){
		$link = va_get_glossary_doi_link(substr($va_current_db_name, 3, 3), substr($id, 1));
	}
	else {
		$link = va_get_comments_doi_link(substr($va_current_db_name, 3, 3), $id);
	}
    $html .= '<a class="lex_edit" href="'. $link .'" title="' . $Ue['LINK_TO_ENTRY'] . '"><button class="actionbtn"><i class="fas fa-link"></i></button></a>';


    if($type=='C'){

	  if($image_found){
	  		 $html .= '<span class="sep"></span>';
	  		 $html .= '<button class="actionbtn lex_image_btn"><i class="fas fa-images"></i></button>';
	  }	

    }

    $html .= '<span class="sep"></span>';
    $html .= '<a class="lex_close lex_edit" title="' . $Ue['CS_close_modal'] . '"><button class="actionbtn"><i class="fas fa-times"></i></button></a>';
    
    $html.='</div>';
    
    $identifier_element = '';
    foreach (explode('+', substr($id, 1)) as $sid){
        $identifier_element .= '<span class="lex_id" title="Identifier">' . substr($id, 0, 1) . $sid. '</span>';
    }
    
    
    $html.='<div class="lex_head_container">';
    $html.=  va_get_comment_title($id, $langdb);
    $html.= '<span class="sep"></span>';
    $html.=  $identifier_element;

	if($type == 'M') {
	    $html .= va_get_glossary_lang_icons($db_id);
	}	

    $html.='</div>';
    
    
    if ($type=='C'){
        $html .= va_get_comment_description($id, $langdb);
    }
    
    $html.='</div>';
    
    $html.='<div class="content">';


    
    if ($info_str){
        $html .= $info_str;
    }
	
	if ($text){
	    $html .= $text;
	}

 
   $html.='</div>';
    
    $html.='<div class="lexfooter">';
    
    if(($admin || $va_mitarbeiter) && $type != 'A' && $type != 'M'){
        $kid = $id;
        $pos_plus = strpos($id, '+');
        if ($pos_plus !== false){
            $kid = substr($kid, 0, $pos_plus);
        }
        $html .= '<span class="lex_comment_id">   [[Kommentar:' . $kid . ']]</span>';
    }

//     if($type == 'M'){
//         if(va_version_newer_than('va_171')){
                
//             $auth = $vadb->get_results($vadb->prepare("
// 								SELECT Vorname, Name, Affiliation
// 								FROM VTBL_Kommentar_Autor JOIN Personen USING (Kuerzel)
// 								WHERE Id_Kommentar = %s AND Aufgabe = 'auct'", $id), ARRAY_N);
//             $trad = $vadb->get_results($vadb->prepare("
// 								SELECT Vorname, Name, Affiliation
// 								FROM VTBL_Kommentar_Autor JOIN Personen USING (Kuerzel)
// 								WHERE Id_Kommentar = %s AND Aufgabe = 'trad' AND Sprache = %s", $id, $langdb), ARRAY_N);
            
//             $html.= '<div>' . va_add_glossary_authors($auth, $trad) . '</div>';
//         }
//     }
    
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
    
    if($type != "M"){
    
		    $html.='<div class="back">';
		    
		    $html.='<div class="b_content">';
		    
		    
		    
		    $html.='<div class="head">';
		    
		    $html.= '<div class="lex_button_container bback">';
			
			$html.= '<a class="lex_edit" title="'.$Ue['KARTE_VISUALISIEREN'].'" target="_BLANK" href="' . va_get_map_link($id) . '"><button class="actionbtn"><i class="fas fa-map-marked-alt"></i></button></a>';
		    
		    if ($va_current_db_name != 'va_xxx'){
				if ($type == 'A'){
					$has_data = $vadb->get_var($vadb->prepare('SELECT count(*) FROM z_ling WHERE Id_Community = %d', substr($id, 1)));
				}
				else {
					$has_data = true;
				}
				
				if ($has_data && $type!= "M"){
					$html.= '<span class="sep"></span>';
					$html.= '<a class="lex_edit" title="Download" target="_BLANK" href="https://www.verba-alpina.gwi.uni-muenchen.de/?api=1&action=getRecord&id=' . $id . '&version=' . substr($va_current_db_name, 3) . '&format=xml&empty=0"><button class="actionbtn"><i class="fas fa-file-download"></i></button></a>';
				}
		    }
		    
		    
			$html.= '<span class="sep"></span>';
		    $html .= '<a class="lex_close lex_edit" title="Schließen"><button class="actionbtn"><i class="fas fa-times"></i></button></a>';
		    
		    $html.='</div>';
		    
		    $html.='<div class="lex_head_container">';
		    $html.=  va_get_comment_title($id, $langdb);
		    $html.= '<span class="sep"></span>';
		    $html.=  $identifier_element;
		    $html.='</div>';
		    
		    $html.='</div>';
		    
		    $html.='<div class="back_body">';
		    
		    
		    $html.= getBackContent($type,$id);
		    
		    
		    $html.='</div>';
		    
		    
		    
		    $html.='</div>';
		    
		    $html.='</div>';
    
    }
    
    $html.='</div>';
    return $html;
}

function getBackContent($type, $id){

	global $Ue;
	$html = '';
			if($type=='C') {

							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> ' . $Ue['MORPH_TYP_PLURAL'] . '</div>';

								$html.='<div class="hiddenbackcontent"></div>';

							$html.='</div>';


							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> ' . $Ue['BASISTYP_PLURAL'] . '</div>';


								$html.='<div class="hiddenbackcontent"></div>';


							$html.='</div>';
						
			}

			if($type=='L') {

							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> ' . $Ue['KONZEPT_PLURAL'] . '</div>';

								$html.='<div class="hiddenbackcontent"></div>';


							$html.='</div>';

							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> ' . $Ue['Gemeinden'] . '</div>';

								$html.='<div class="hiddenbackcontent"></div>';
			
							$html.='</div>';
						
			}


			if($type=='B') {


							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> ' . $Ue['MORPH_TYP_PLURAL'] . '</div>';

								$html.='<div class="hiddenbackcontent"></div>';
			

							$html.='</div>';

							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> ' . $Ue['Gemeinden'] . '</div>';


								$html.='<div class="hiddenbackcontent"></div>';
			

							$html.='</div>';	

							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> ' . $Ue['KONZEPT_PLURAL'] . '</div>';


								$html.='<div class="hiddenbackcontent"></div>';
					
							$html.='</div>';								
			}
			if($type=='A') {


							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> ' . $Ue['MORPH_TYP_PLURAL'] . '</div>';

								$html.='<div class="hiddenbackcontent"></div>';
			

							$html.='</div>';

							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> ' . $Ue['BASISTYP_PLURAL'] . '</div>';


								$html.='<div class="hiddenbackcontent"></div>';
			

							$html.='</div>';	

							$html.='<div class="sub_head">';
							$html.='<div class="sub_head_content"><i class="fas fa-angle-right"></i></i> ' . $Ue['KONZEPT_PLURAL'] . '</div>';


								$html.='<div class="hiddenbackcontent"></div>';
					
							$html.='</div>';								
			}

return $html;
}

function getTableInfo($id,$owntype,$othertype){

$html = '';
$html .='<table class="backtable" style="width:100%" type="'.$othertype.'">';
$html .='<thead>';

$id_trim = ltrim($id, $id[0]); 

list($info, $extra) = get_table_data($othertype,[$owntype => [intval($id_trim)]]);

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

$html .= $extra;

return $html;


}


function get_secondary_data($id,$type,$p_id,$p_type){

    $info = get_table_data('I',[$type => [intval($id)], $p_type => array_map('intval', explode('+', $p_id))]);
	return $info;

}




function get_images_to_concept(){

	     global $vadb;

	     $id = $_POST['id'];

	     $c_id = ltrim($id, 'C');

    	 $images = $vadb->get_results($vadb->prepare("
    	 	SELECT Id_Medium
    	 	FROM vtbl_medium_konzept
    	 	WHERE Id_Konzept = %s AND NOT Konzeptillustration", $c_id), ARRAY_N);

    	$urls = array();

	    foreach ($images as $image){

	    		$image_id = $image[0];

				$url_arr = $vadb->get_results($vadb->prepare("
				SELECT Dateiname
				FROM Medien
				WHERE Id_Medium = %s ",$image_id), ARRAY_N);

			    foreach ($url_arr as $value){
			    	array_push($urls, $value[0]);
			    }
	    }

   		return $urls; 	
}


function get_table_data ($type, $ids){
    
    global $vadb;
    global $lang;
    
    $key_col = 'VAID';
    
    $bib_data = [];
    $sti_data = [];
    $inf_data = [];
    
    $isolangs = va_get_iso_map($lang, $vadb);
    $source_types = va_get_bib_type_map($vadb);
    
    
    switch ($type){
        case 'I': //instances
            $select = [
                'External_Id AS VAID', 
                'IF(Instance = "", (SELECT CONCAT("Typ ", Type) FROM z_ling z2 WHERE z2.Id_Instance = z_ling.Id_Instance AND z2.Source_Typing != "VA" LIMIT 1), Instance) AS Instance',
                'Gender',
                'Instance_Source', 
                'Community_Name', 
                'IF(Name != "", Name, Description) AS Concept',
                'Id_Stimulus',
                'Id_Informant',
                '(SELECT Stimulus FROM Stimuli s WHERE s.Id_Stimulus = z_ling.Id_Stimulus) AS Stimulus'
            ];
            $format = [
                'Instance_Source' => function ($source_string, $row) use (&$bib_data, &$sti_data, &$inf_data, $isolangs, $source_types){
                    return va_format_source_from_db($row, $bib_data, $sti_data, $inf_data, $source_types, $isolangs);
                    //$sdata = explode('#', $source_string);
                    //return $sdata[0] . ' ' . $sdata[1] . '#' . $sdata[2] . $sdata[3] . ' (' . $sdata[4] . ')';
                },
                'Community_Name' => function ($name) {global $lang; return va_translate_extra_ling_name($name, $lang);},
                'Concept' => function ($des) {global $Ue; if ($des === null) return $Ue['KEIN_KONZEPT']; return $des;},
                'Gender' => function ($gen){return $gen?: '';},
                'Instance' => function ($instance){
                    global $Ue;
                    $pos_hashes = mb_strpos($instance, '###');
                    if ($pos_hashes){
                        return mb_substr($instance, 0, $pos_hashes) . '<br /><span style="font-size: 80%">(' . $Ue['BELEG_TEIL'] . ' ' . mb_substr($instance, $pos_hashes + 3) . ')</span>';
                    }
                    return $instance;
                }
            ];
            $where = '(lang IS NULL OR lang = "' . $lang . '")';
            $join = ' LEFT JOIN z_concepts USING (Id_Concept)';
            $combine = [];
            $order = ['Instance_Source', 'Community_Name', 'Instance'];
            $ignore_fields = ['Id_Stimulus', 'Id_Informant', 'Stimulus'];
            break;
        case 'L': //morph-lex types
            $select = [
                'IF(Source_Typing = "VA" OR Source_Typing IS NULL, CONCAT("L", Id_Type), NULL) AS VAID',
                'IF(Source_Typing = "VA" OR Source_Typing IS NULL, Type, NULL) AS Type',
                'IF(Source_Typing = "VA" OR Source_Typing IS NULL, Type_Lang, NULL) AS Type_Lang',
                'IF(Source_Typing = "VA" OR Source_Typing IS NULL, POS, NULL) AS POS',
                'IF(Source_Typing = "VA" OR Source_Typing IS NULL, Gender, NULL) AS Gender',
                'IF(Source_Typing = "VA" OR Source_Typing IS NULL, Type_Reference, NULL) AS Type_Reference',
            ];
            $format = [
                'Type' => function ($type) {global $Ue; if ($type === null) return $Ue['NICHT_TYPISIERT']; return $type;}
            ];
            $where = '(Id_Type IS NULL OR (Type_Kind = "L" AND Source_Typing = "VA") OR (Id_Type is not null and not exists(select * from z_ling z2 where z_ling.Id_Instance = z2.Id_Instance and Source_Typing = "VA")))';
            $join = '';
            $combine = ['Type_Reference' => function ($ref){return va_add_references('', $ref);}];
            $order = ['Type', 'Type_Lang', 'POS', 'Gender'];
            $ignore_fields = [];
            break;
        case 'B': //base types
            $select = ['CONCAT("B", Id_Base_Type) AS VAID', 'Base_Type', 'Base_Type_Lang', 'Base_Type_Unsure', 'Base_Type_Reference'];
            $format = [
                'Base_Type' => function ($type) {global $Ue; if ($type === null) return $Ue['NICHT_TYPISIERT']; return $type;},
                'Num_Instances' => function ($num, $row, $sql_data, $ids) use ($key_col){
                    //Compute the count of instances separately since there are several special case (e.g. only source typification, some lex. have base types some not, etc.)
                    if (!$row[$key_col]){
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
            $ignore_fields = [];
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
			$ignore_fields = [];
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
            $ignore_fields = [];
            break;
        default:
            throw new Exception('Unknown type: ' . $type);
    }
    
    $having = '';
    
    foreach (array_keys($ids) as $stype){
        switch ($stype){
            case 'C':
                if ($ids[$stype]){
                    $concepts = $vadb->get_col($vadb->prepare('SELECT Id_Konzept FROM A_Ueberkonzepte_Erweitert WHERE Id_Ueberkonzept = %d', $ids[$stype][0]));
                    $concepts[] = $ids[$stype][0];
                    $where .= $vadb->prepare(' AND Id_Concept IN ' . im_key_placeholder_list($concepts), $concepts);
                    $ids[$stype] = null;
                }
                else {
                    $where .= ' AND Id_Concept IS NULL';
                }
                break;
            case 'L':
                if ($ids[$stype] && $ids[$stype][0]){ //If not typed there is an array with the value 0
                    $where .= $vadb->prepare(' AND Id_Type IN ' . im_key_placeholder_list($ids[$stype]) . ' AND Source_Typing = "VA" AND Type_Kind = "L"', $ids[$stype]);
                    $ids[$stype] = null;
                }
                else {
                    $ids[$stype] = [];
                    $where .= ' AND Id_Type IS NULL';
                }
                break;
            case 'B':
                if ($ids[$stype]){
                    $ids[$stype] = $ids[$stype][0];
                }
                $where .= $ids[$stype]? ' AND Id_Base_Type = %d': '';
                $having = $ids[$stype]? '': ' SUM(Id_Base_Type) IS NULL';
                break;
            case 'A':
                $ids[$stype] = $ids[$stype][0];
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
            return mb_substr($col, 0, $pos_as);//mb_substr($col, $pos_as + 4);
        }
        return $col;
    }, $select));
    
    $sql = 'SELECT ' . $select_clause . ' FROM z_ling' . $join . ' WHERE ' . $where . ' AND Alpine_Convention GROUP BY ' . $group_clause . ($having? ' HAVING' . $having: '') . ' ORDER BY VAID';
    
    if ($ids){
        //va_query_log($vadb->prepare($sql, array_values($ids)));
        $rows = $vadb->get_results($vadb->prepare($sql, array_values($ids)), ARRAY_A);
    }
    else {
        //va_query_log($sql);
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
                else if (!in_array($col, $ignore_fields)){
                    if (isset($format[$col])){
                        $val = $format[$col]($val, $row, $sql_data, $ids);
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
    
    $extra = '<div id="mouseOverBib" style="display: none;">';
    foreach ($bib_data as $content){
        $extra .= $content;
    }
    
    $extra .= '</div><div id="mouseOverSti" style="display: none;">';
    foreach ($sti_data as $content){
        $extra .= $content;
    }
    
    $extra .= '</div><div id="mouseOverInf" style="display: none;">';
    foreach ($inf_data as $content){
        $extra .= $content;
    }
    
    return [$res, $extra];
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