<?php

function va_query_log($query){
	error_log(preg_replace('/\s+/', ' ', $query));
}

function va_get_glossary_link ($id = null, $lang = null){

	if (!$lang){
		global $lang;
	}
	$blog_id = va_blog_id_from_lang($lang);
	
	$post_table = 'wp_' . ($blog_id > 1? $blog_id . '_' : '') . 'posts';
	$sql = "SELECT ID FROM $post_table WHERE post_title = 'METHODOLOGIE'";
	global $wpdb;
	$page_id = $wpdb->get_var($sql);

	//$glossarPage = get_page_by_title('METHODOLOGIE');
	if($page_id){
		//$link = get_page_link($page_id);
		$link = va_add_query_vars(add_query_arg('page_id', $page_id, get_home_url($blog_id)), $page_id);
	}
	else {
		return '';
	}
	
	if($id){
		global $vadb;
		$res = $vadb->get_var('SELECT Terminus_' . $lang . ' FROM Glossar WHERE Id_Eintrag = ' . $id);
		
		$link = add_query_arg('letter', remove_accents($res[0]), $link) . '#' . $id;
	}
	
	return $link;
}

function va_blog_id_from_lang ($lang){
	switch ($lang){
		case 'F':
			return 5;
		case 'I':
			return 3;
		case 'S':
			return 6;
		case 'R':
			return 7;
		case 'E':
			return 8;
		default:
			return 1;
	}
}

function va_get_glossary_doi_link ($version = false, $id = null){
	$glossarPage = get_page_by_title('METHODOLOGIE');
	
	if($glossarPage != null){
		$link = va_get_doi_base_link();
		$params = ['page_id=' . $glossarPage->ID];
		$fragment = false;
		
		if($version !== false){
			$params[] = 'db=' . $version;
		}
	}
	else {
		return '';
	}
	
	if($id){
		global $lang;
		global $vadb;
		$res = $vadb->get_var('SELECT Terminus_' . $lang . ' FROM Glossar WHERE Id_Eintrag = ' . $id);
		
		$params[] = 'letter=' . remove_accents($res[0]);
		$fragment = $id;
	}
	
	$append = '?' . implode('&', $params) . ($fragment !== false? '#' . $fragment: '');
	return $link . urlencode($append);
}

function va_get_post_doi_link ($version = false, $id){
	
	$link = va_get_doi_base_link();
	$params = ['p=' . $id];
	
	if($version !== false){
		$params[] = 'db=' . $version;
	}
	
	$append = '?' . implode('&', $params);
	return $link . urlencode($append);
}

function va_get_comments_doi_link ($version = false, $id = null){
	$commentsPage = get_page_by_title('LexAlp');
	
	if($commentsPage != null){
		$link = va_get_doi_base_link();
		$params = ['page_id=' . $commentsPage->ID];
		$fragment = false;
		
		if($version !== false){
			$params[] = 'db=' . $version;
		}
	}
	else {
		return '';
	}
	
	if($id){
		$fragment = $id;
	}
	
	$append = '?' . implode('&', $params) . ($fragment !== false? '#' . $fragment: '');
	return $link . urlencode($append);
}

function va_get_comments_link ($id = null){
    $commentsPage = get_page_by_title('LexAlp');
    if($commentsPage != null){
        return get_page_link($commentsPage) . ($id ? '#' . $id : '');
    }
    return '';
}


function va_get_doi_base_link ($only_german = false){
	$res = 'https://doi.org/10.5282/verba-alpina?urlappend=';
	
	if (!$only_german){
		$blog_url = get_home_url();
		$matches = NULL;
		if (preg_match('#[.]*/([a-z]{2})$#', $blog_url, $matches)){
			$res .= urlencode('/' . $matches[1]);
		}
	}
	
	return $res;
}

function va_get_glossary_link_and_title ($id = null){

	$glossarPage = get_page_by_title('METHODOLOGIE');
	if($glossarPage != null){
		$link = get_page_link($glossarPage);
	}
	else {
		return '';
	}
	
	if($id){
		global $lang;
		global $vadb;
		$res = $vadb->get_var('SELECT Terminus_' . $lang . ' FROM Glossar WHERE Id_Eintrag = ' . $id);
		
		$link = add_query_arg('letter', $res[0], $link) . '#' . $id;
	}
	
	return [$link, $res];
}

function va_get_map_link ($element = null){
	$result = '';
	
	if(defined('VA_MAP_URL')){
		$result = VA_MAP_URL;
		if($element != null){
			$result = add_query_arg('single', $element, $result);
		}
	}
	return $result;
}

function va_get_dbdoku_link (){
	$dokuPage = get_page_by_title('DBDokuNeu'); //TODO change
	if($dokuPage != null){
		return get_page_link($dokuPage);
	}
	return '';
}

function va_surround ($str_arr, $q){
	foreach($str_arr as $key => $str) {
		$str_arr[$key] = $q . $str . $q;
	}
	return $str_arr;
}


function va_format_bibliography ($author, $title, $year, $loc, $link, $band, $in, $seiten, $verlag, $link_abgesetzt = true){
		$res =  $author;
		if($year != '')
			$res .= ' (' . $year . ')';
		$res .= (($author == '' && $year == '')? '': ': ') . $title;
		if($loc != '')
			$res .= ', ' . $loc;
		if($in != '')
			$res .= ', in ' . $in;
		if($band != '')
			$res .= ', vol. ' . $band;
		if($verlag != '')
			$res .= ', ' . $verlag;
		if($seiten != '')
			$res .= ', ' . $seiten;
		if($link != '')
			if($link_abgesetzt)
				$res .= "\n<br /><br />\n<a href='" . str_replace("'", '%27', $link) . "'>Link</a>";
			else
				$res .= "\n(<a href='$link'>Link</a>)";
		return $res;
}

function va_format_base_type ($str, $lang, $uncertain = '0', $Ue = null){
	
    if ($lang){
		$str .= ' (' . $lang . ')';
    }
	
	if (!$Ue){
	   global $Ue;
	}
	
	if(mb_strpos($str, '*') !== false){
		return $str . ' (* = ' . $Ue['REKONSTRUIERT'] . ')';
	}
	if($uncertain === '1'){
		$str = '(?) ' . $str;
	}
	
	return $str;
}
//TODO use icons from plugin everywhere and delete icons folder in va
function va_get_glossary_help ($id, &$Ue){
	return '<a href="' . va_get_glossary_link($id) . '" target="_blank"><i class="helpsymbol far fa-question-circle" style="vertical-align: middle;" title="' . $Ue['HILFE'] . '" ></i></a>';
}

function va_get_mouseover_help ($text, &$Ue, &$db, $lang, $id_glossary = NULL){
	$res = '<i class="helpsymbol far fa-question-circle va_mo_help" style="vertical-align: middle;"></i>';
	$res .= '<div style="display : none;">' . nl2br($text); 
	if($id_glossary != NULL){
		$entry_name = $db->get_var('SELECT Terminus_' . $lang . ' FROM Glossar WHERE Id_Eintrag = ' . $id_glossary);
		$res .= '<br /><br />' . '<a href="' . va_get_glossary_link($id_glossary) . '" target="_blank">(' . $Ue['SIEHE'] . ' ' . $entry_name . ')</a>';
	}
	return $res . '</div>';
}

//TODO use plugin icons
/**
 * Use jQuery('.infoSymbol').qtip(); to show qtips.
 */
function va_get_info_symbol ($info_text){
	return '<img  src="' . VA_PLUGIN_URL . '/images/Help.png" style="vertical-align: middle;" title="' . $info_text . '" class="infoSymbol" />';
}


/**
 * Replaces urls that contain page_id=<german_page_id> with a valid url in the respective language
 */
function va_translate_url ($url){
	if(get_current_blog_id() != 1){
		$url = preg_replace_callback('/(?:page_id=)([0-9]*)/', function ($matches){
			$elements_linked = mlp_get_linked_elements(intval($matches[1]), '', 1);
			return 'page_id=' . $elements_linked[get_current_blog_id()];
		}, $url);
		return preg_replace('/http:\/\/www.verba-alpina.gwi.uni-muenchen.de\//', get_home_url(), $url);
	}
	return $url;
}

function va_format_lex_type ($orth, $lang, $word_class, $gender, $affix, $add_qtip_spans = false){

	if ($add_qtip_spans){
		$lang = '<span class="iso" data-iso="' . $lang . '">' . $lang . '</span>';
	}
	
	if($lang && $gender)
		 $result = $orth . ' (' . $lang . json_decode('"\u00A0"') . $gender . '.)';
	else if ($lang)
		$result = $orth . ' (' . $lang . ')';
	else if ($gender)
		$result = $orth . ' (' . $gender . '.)';
	else
		$result = $orth;
	return $result;
}

function va_format_version_number ($version){
	if($version == '')
		return '';
	
	return substr($version, 0, 2) . '/' . substr($version, 2);
}

function va_sub_translate ($str, &$Ue){
	return preg_replace_callback('/Ue\[(.*)\]/U', function ($matches) use (&$Ue){
		if(isset($Ue[$matches[1]])){
			return $Ue[$matches[1]];
		}
		return $matches[1];
	}, $str); 
 }
 
 function va_translate($term, &$Ue){
 	if(isset($Ue[$term])){
 		return $Ue[$term];
 	}
 	return $term;
 }
 
function va_create_glossary_citation ($id, &$Ue){
 	global $vadb;
 	global $lang;
 	global $va_current_db_name;
 	
 	$authors = $vadb->get_col("SELECT CONCAT(Name, ', ', SUBSTR(Vorname, 1, 1), '.') FROM VTBL_Eintrag_Autor JOIN Personen USING (Kuerzel) WHERE Aufgabe = 'auct' AND Id_Eintrag = $id ORDER BY Name ASC, Vorname ASC");
	$data = $vadb->get_row("SELECT Terminus_$lang, angelegt_$lang, geaendert_$lang FROM Glossar WHERE Id_Eintrag = $id", ARRAY_N);
	$link = va_get_glossary_doi_link(substr($va_current_db_name, 3, 3), $id);

	$vyear = substr($va_current_db_name, 3, 2);
	$vnumber = substr($va_current_db_name, 5);
	
	$res = implode(' / ', $authors) . ': s.v. “' . $data[0] . '”, in: VerbaAlpina-' . substr(get_locale(), 0, 2) . ' '
 	 . $vyear . '/' . $vnumber;
	
	$pub_data = [];
	if ($data[1] != $vyear . $vnumber){
		$pub_data[] = $Ue['ERSTELLT'] . ': ' . substr($data[1], 0, 2) . '/' . substr($data[1], 2);
	}
 	
 	if ($data[2] != $vyear . $vnumber){
 		$pub_data[] = $Ue['LETZTE_AENDERUNG'] . ': ' . substr($data[2], 0, 2) . '/' . substr($data[2], 2);
 	}
 	
 	if ($pub_data){
 		$res .= ' (';
 		foreach ($pub_data as $i => $pd){
 			if ($i > 0){
 				$pd = lcfirst($pd);
 				$res .= ', ';
 			}
 			$res .= $pd;
 		}
 		$res .= ')';
 	}
	
	$res .= ', ' . $Ue['METHODOLOGIE'] . ', ' . $link;
	
 	return $res;
 }
 
function va_create_glossary_bibtex ($id, &$Ue, $html = false){
	global $vadb;
	global $lang;
	global $va_current_db_name;
	
	$authors = $vadb->get_results("SELECT Name, Vorname FROM VTBL_Eintrag_Autor JOIN Personen USING (Kuerzel) WHERE Aufgabe = 'auct' AND Id_Eintrag = $id ORDER BY Name ASC, Vorname ASC", ARRAY_A);
	$data = $vadb->get_row("SELECT Terminus_$lang, geaendert_$lang FROM Glossar WHERE Id_Eintrag = $id", ARRAY_N);
	$year = '20' . substr($data[1], 0, 2);
	$shortcode = implode('', array_map(function ($e) {return strtolower(remove_accents($e['Name']));}, $authors)) 
		. $year 
		. va_shortcode_title_part($data[0]);
	$link = va_get_glossary_doi_link(substr($va_current_db_name, 3, 3), $id);
	
	$tab = $html? '&nbsp;&nbsp;&nbsp;': "\t";
	$newline = $html? '<br />' : "\n";
 	
	$res = '@incollection{' . $shortcode . ',' . $newline .
	$tab . 'author={' . implode(' and ', array_map(function ($e) {return $e['Name'] . ', ' . $e['Vorname'];}, $authors)) . '},' . $newline .
	$tab . 'year={' . $year . '},' . $newline .
	$tab . 'title={' . $data[0] . '},' . $newline .
	$tab . 'publisher={VerbaAlpina-' . substr(get_locale(), 0, 2) . ' ' . va_format_version_number(substr($va_current_db_name, 3)) . '},' . $newline .
	$tab . 'booktitle={'. $Ue['METHODOLOGIE']. '},' . $newline .
	$tab . 'url={' . $link. '}' . $newline . '}';
	
	if($html)
		$res = htmlentities($res);
	
	return $res;
 }
 
 function va_create_post_citation ($id, &$Ue){
 	global $va_current_db_name;
 	
 	$authors = get_field('autoren');
 	if ($authors){
 		$authors = array_map('trim', explode(',', $authors));
 	}
 	else {
 		$authors = [];
 	}
 	
 	$link = va_get_post_doi_link(substr($va_current_db_name, 3, 3), get_the_ID());
 	
 	$vyear = substr($va_current_db_name, 3, 2);
 	$vnumber = substr($va_current_db_name, 5);
 	
 	$res = implode(' / ', $authors) . ': “' . get_the_title($id) . '”, in: VerbaAlpina-' . substr(get_locale(), 0, 2) . ' '
 			. $vyear . '/' . $vnumber;
 	
 	global $wpdb;
 	global $va_xxx;
 	$first = $wpdb->get_var($wpdb->prepare('SELECT post_date FROM ' . va_get_post_table() . ' WHERE ID = %d', get_the_ID()));
 	$first_version = $va_xxx->get_var($va_xxx->prepare('SELECT Nummer FROM Versionen WHERE Erstellt_Am > %s ORDER BY Erstellt_Am ASC', $first));
 	$last = $wpdb->get_var($wpdb->prepare('SELECT post_date FROM ' . va_get_post_table() . ' WHERE ID = %d', $id));
 	$last_version = $va_xxx->get_var($va_xxx->prepare('SELECT Nummer FROM Versionen WHERE Erstellt_Am > %s ORDER BY Erstellt_Am ASC', $last));
 			
 	$pub_data = [];
 	if ($first_version != $vyear . $vnumber){
 		$pub_data[] = $Ue['ERSTELLT'] . ': ' . substr($first_version, 0, 2) . '/' . substr($first_version, 2);
 	}
 			
 	if ($last_version != $vyear . $vnumber){
 		$pub_data[] = $Ue['LETZTE_AENDERUNG']. ': ' . substr($last_version, 0, 2) . '/' . substr($last_version, 2);
 	}
 			
 	if ($pub_data){
 		$res .= ' (';
 		foreach ($pub_data as $i => $pd){
 			if ($i > 0){
 				$pd = lcfirst($pd);
 				$res .= ', ';
 			}
 			$res .= $pd;
 		}
 		$res .= ')';
 	}
 			
 	$res .= ', ' . $link;
 			
 	return $res;
 }
 
 function va_create_post_bibtex ($id, &$Ue, $html = false){
 	global $va_current_db_name;
 	
 	$authors = get_field('autoren');
 	if ($authors){
 		$authors = array_map('trim', explode(',', $authors));
 	}
 	else {
 		$authors = [];
 	}
 	
 	$link = va_get_post_doi_link(substr($va_current_db_name, 3, 3), get_the_ID());
 	$title = get_the_title($id);
 	
 	global $wpdb;
 	global $va_xxx;
 	$last = $wpdb->get_var($wpdb->prepare('SELECT post_date FROM ' . va_get_post_table() . ' WHERE ID = %d', $id));
 	$last_version = $va_xxx->get_var($va_xxx->prepare('SELECT Nummer FROM Versionen WHERE Erstellt_Am > %s ORDER BY Erstellt_Am ASC', $last));
 	
 	$year = '20' . substr($last_version, 0, 2);
 	$shortcode = implode('', array_map(function ($e) {return strtolower(remove_accents(mb_substr($e, mb_strpos($e, ' ') + 1)));}, $authors))
 	. $year
 	. va_shortcode_title_part($title);
 	$link = va_get_post_doi_link(substr($va_current_db_name, 3, 3), get_the_ID());
 	
 	$tab = $html? '&nbsp;&nbsp;&nbsp;': "\t";
 	$newline = $html? '<br />' : "\n";
 	
 	$res = '@article{' . $shortcode . ',' . $newline .
 	$tab . 'author={' . implode(' and ', $authors) . '},' . $newline .
 	$tab . 'year={' . $year . '},' . $newline .
 	$tab . 'title={' . $title . '},' . $newline .
 	$tab . 'publisher={VerbaAlpina-' . substr(get_locale(), 0, 2) . ' ' . va_format_version_number(substr($va_current_db_name, 3)) . '},' . $newline .
 	$tab . 'url={' . $link. '}' . $newline . '}';
 	
 	if($html)
 		$res = htmlentities($res);
 		
 		return $res;
 }
 
 function va_shortcode_title_part ($title){
 	return str_replace(' ', '', mb_substr(mb_strtolower(va_remove_special_chars($title)), 0, 15));
 }
 
function va_remove_special_chars ($str){
 	return preg_replace('/[^a-zA-Z0-9]/u', '', remove_accents($str));
}
 
function va_create_comment_citation ($id, &$Ue){
 	global $vadb;
 	global $lang;
 	global $va_current_db_name;
 	if(va_version_newer_than('va_171')){
 		$authors = $vadb->get_col("SELECT CONCAT(Name, ', ', SUBSTR(Vorname, 1, 1), '.') FROM VTBL_Kommentar_Autor JOIN Personen USING (Kuerzel) WHERE Aufgabe = 'auct' AND Id_Kommentar = '$id' ORDER BY Name ASC, Vorname ASC");
 	}
 	else {
 		$content = $vadb->get_var("SELECT Comment FROM im_comments WHERE Id = '$id'");
 		$pos_auct = mb_strpos($content, '(auct. ');
 		if($pos_auct === false)
 			return false;
 		
 		$authorStr = mb_substr($content, $pos_auct + 7, mb_strpos($content, ')', $pos_auct) - $pos_auct - 7);
 		$authorsL = mb_split('|', $authorStr);
 		
 		$authors = array();
 		foreach ($authorsL as $author){
 			$names = mb_split(' ', $author);
 			$authors[] = $names[count($names) - 1] . ', ' . $names[0][0] . '.';
 		}
 	}
 	
 	if (!$authors){
 	    return false;
 	}
 	
 	$title = va_sub_translate($vadb->get_var("SELECT getEntryName('$id', '$lang')"), $Ue);

 	$link = va_get_comments_doi_link(substr($va_current_db_name, 3, 3), $id);
 
 	return	implode(' / ', $authors) . ': s.v. “' . $title . '”, in: VerbaAlpina-' . substr(get_locale(), 0, 2) . ' ' .
 			substr($va_current_db_name, 3, 2) . '/' . substr($va_current_db_name, 5) . ', Lexicon alpinum, ' . $link;
}
 
 function va_version_newer_than ($version){
 	global $va_current_db_name;
 	
 	if($version == 'va_xxx')
 		return false;
 	if($va_current_db_name == 'va_xxx')
 		return true;
 	
 	$num_curr = substr($va_current_db_name, 3);
 	$num_newer = substr($version, 3);

 	return $num_curr > $num_newer;
 }
 
 function va_get_post_version_id ($post_id){
	global $va_current_db_name;
 	if ($va_current_db_name === 'va_xxx'){
 		return $post_id;
 	}
 	else {
 		global $wpdb;
 		global $va_xxx;
 		$version_created = $va_xxx->get_var($va_xxx->prepare('SELECT Erstellt_Am FROM Versionen WHERE Nummer = %s', substr($va_current_db_name, 3)));

 		$last_rev_in_version = $wpdb->get_var($wpdb->prepare('SELECT ID FROM ' . va_get_post_table() . ' WHERE post_parent = %d and post_type ="revision" and post_date <= %s ORDER BY post_date DESC LIMIT 1', $post_id, $version_created));
 		return $last_rev_in_version;
 	}
 }
 
 function va_get_post_table (){
 	$blog_id = get_current_blog_id();
 	
 	if ($blog_id === 1){
 		return 'wp_posts';
 	}
 	else {
 		return 'wp_' . $blog_id . '_posts';
 	}
 }
 
 //Php ??
 function dq ($array, $val){
 	if (isset($array[$val]) && $array[$val])
 		return $array[$val];
 	return null;
 }
 
 function va_only_latin_letters($str){
 	return preg_replace('/[^a-zA-Z]/', '', remove_accents($str));
 }
 
 function va_two_dim_to_assoc($two_dim){
 	$assoc = [];
 	foreach ($two_dim as $val){
 		$assoc[$val[0]] = $val[1];
 	}
 	return $assoc;
 }
 
 function va_echo_new_concept_fields ($name, $extra_fields = NULL){
	$fields = array(
			new IM_Field_Information('Name_D', 'V', false),
			new IM_Field_Information('Beschreibung_D', 'V', true),
			new IM_Field_Information('Id_Kategorie AS Kategorie', 'F{CONCAT(Hauptkategorie, "/", Kategorie)}', true),
			new IM_Field_Information('Taxonomie', 'V', false),
			new IM_Field_Information('QID', 'N', false, false, NULL, false, true),
			new IM_Field_Information('Kommentar_Intern', 'V', false),
			new IM_Field_Information('Relevanz', 'B', false, true, true),
			new IM_Field_Information('Pseudo', 'B', false, true),
			new IM_Field_Information('Grammatikalisch', 'B', false, true),
			new IM_Field_Information('VA_Phase', 'E', false)
	);
	
	if($extra_fields){
		foreach ($extra_fields as $extra){
			$fields[] = $extra;
		}
	}
	
	echo im_table_entry_box ($name, new IM_Row_Information('Konzepte', $fields, 'Angelegt_Von'));
}

function va_add_interval ($intervals, $new_interval){
	
	if(empty($intervals)){
		return [$new_interval];
	}
	
	$len = count($intervals);
	
	//New interval at the beginning
	if ($new_interval[1] < $intervals[0][0]){
		array_unshift($intervals, $new_interval);
		return $intervals;
	}
	
	//New interval at the end
	if ($new_interval[0] > $intervals[$len - 1][1]){
		$intervals[] = $new_interval;
		return $intervals;
	}
	
	$startInterval = NULL;
	//Find starting interval
	foreach ($intervals as $index => $interval){
		if($new_interval[0] >= $interval[0] && $new_interval[0] <= $interval[1]){
			$startInterval = [$index, true];
			break;
		}
		
		if($new_interval[0] < $interval[0]){
			$startInterval = [$index, false];
			break;
		}
	}
	
	//Find ending interval
	$endInterval = NULL;
	foreach ($intervals as $index => $interval){
		if($new_interval[1] >= $interval[0] && $new_interval[1] <= $interval[1]){
			$endInterval =  [$index, true];
			break;
		}
		
		if ($index == $len - 1 || $intervals[$index + 1][0] > $new_interval[1]){
			$endInterval = [$index, false];
			break;
		}
	}
	
	if(!$startInterval[1] && !$endInterval[1]){
		array_splice($intervals, max($endInterval[0], $startInterval[0]), $endInterval[0] - $startInterval[0] + 1, [$new_interval]);
	}
	else if ($startInterval[1]){
		if($endInterval[1]){
			array_splice($intervals, $startInterval[0], $endInterval[0] - $startInterval[0] + 1, [[$intervals[$startInterval[0]][0], $intervals[$endInterval[0]][1]]]);
		}
		else {
			array_splice($intervals, $startInterval[0], $endInterval[0] - $startInterval[0] + 1, [[$intervals[$startInterval[0]][0], $new_interval[1]]]);
		}
	}
	else {
		array_splice($intervals, $startInterval[0], $endInterval[0] - $startInterval[0] + 1, [[$new_interval[0], $intervals[$endInterval[0]][1]]]);
	}
	return $intervals;
	
}

function va_add_marking_spans ($text, $intervals, $span_attributes = 'style="background: yellow"'){
	if(count($intervals) == 0){
		return htmlentities($text);
	}
	
	$offset = 0;
	$marked_text = $text;

	foreach ($intervals as $index => $interval){
		$pre = '<span ' . $span_attributes . '>';
		$post = '</span>';
		$marked = substr($marked_text, $interval[0] + $offset, $interval[1] - $interval[0]);
		$middle = htmlentities($marked);
		
		$start = substr($marked_text, 0, $interval[0] + $offset);
		$end = substr($marked_text, $interval[1] + $offset);
		
		if ($index == count($intervals) - 1){
			$end = htmlentities($end);
		}
		
		if ($index == 0){
			$startEnt = htmlentities($start);
			$offset += strlen($startEnt) - strlen($start);
			$start = $startEnt;
		}
		
		$marked_text =	$start . $pre . $middle . $post . $end;
		$offset += strlen($pre) + strlen($post) + (strlen($middle) - strlen($marked));
	}
	
	return $marked_text;
}

function va_strip_intervals ($text, $intervals){

	$offset = 0;
	$stripped_text = $text;
	foreach ($intervals as $interval){
		$stripped_text =
		substr($stripped_text, 0, $interval[0] - $offset) . substr($stripped_text, $interval[1] - $offset);
		$offset += $interval[1] - $interval[0];
	}

	return $stripped_text;
}

function va_reconstruct_record_from_tokens ($tokens){
	
	$curr_1 = 0;
	$curr_2 = 0;
	$curr_3 = 0;
	$cur_token = '';
	$cur_gender = 'xxx';
	
	$res = '';
	
	foreach ($tokens as $index => $token){
		if(intval($token['Ebene_1']) === $curr_1 + 1 && intval($token['Ebene_2']) === 1 && intval($token['Ebene_3']) === 1){
			if($res == ''){
				$res = $token['Token'];
			}
			else {
				$res .= ';' . $token['Token'];
			}
		}
		else if (intval($token['Ebene_1']) === $curr_1 && intval($token['Ebene_2']) === $curr_2 + 1 && intval($token['Ebene_3']) === 1){
			if($cur_token != $token['Token'] || $cur_gender == $token['Genus']){ //Double tokens for different genders!
				$res .= ',' . $token['Token'];
			}
		}
		else if (intval($token['Ebene_1']) === $curr_1 && intval($token['Ebene_2']) === $curr_2 && intval($token['Ebene_3']) === $curr_3 + 1){
			$space = ' ';
			if($tokens[$index-1]['Trennzeichen']){
				$space = $tokens[$index-1]['Trennzeichen'];
			}
			
			$res .= $space . $token['Token'];
		}
		else {
			throw new Exception('Invalid token indexes: [' . $token['Ebene_1'] . ',' . $token['Ebene_2'] . ',' . $token['Ebene_3'] . '] after [' .
				$curr_1 . ',' . $curr_2 . ',' . $curr_3 . '] for record ' . $token['Id_Aeusserung'] . '!');
		}
		
		$curr_1 = intval($token['Ebene_1']);
		$curr_2 = intval($token['Ebene_2']);
		$curr_3 = intval($token['Ebene_3']);
		$cur_token = $token['Token'];
		$cur_gender = $token['Genus'];
	}
	
	return $res;
}

function va_deep_assoc_array_compare ($arr1, $arr2){
	
	foreach ($arr2 as $key => $val){
		if(!array_key_exists($key, $arr1)){
			return 'Key "' . $key . '" does not exist in array 1!';
		}
	}
	
	foreach ($arr1 as $key => $val){
		if(!array_key_exists($key, $arr2)){
			return 'Key "' . $key . '" does not exist in array 2!';
		}
		
		if(is_array($val)){
			if(is_array($arr2[$key])){
				$rec = va_deep_assoc_array_compare($val, $arr2[$key]);
				if($rec !== true){
					return 'Key "' . $key . '" sub-array not equal: ' . $rec;
				}
			}
			else {
				return 'Key "' . $key . '" is array in array 1, but no array in array 2!';
			}
		}
		else {
			if($val !== $arr2[$key]){
				return 'Key "' . $key . '" has value "' . $val . '" in array 1 and value "' . $arr2[$key] . '" in array 2!';
			}
		}
	}
	return true;
}

function va_array_to_html_string ($arr, $showLevel = 1, $recLevel = 0){
	if(empty($arr)){
		return '[]';
	}
	
	$assoc = count(array_filter(array_keys($arr), 'is_string')) > 0;
	
	$vals = [];
	
	foreach ($arr as $key => $val){
		if (is_array($val)){
			$vals[] = ($assoc? '"' . $key . '" => ': '') . va_array_to_html_string($val, $showLevel, $recLevel + 1);
		}
		else {
			$vals[] = ($assoc? '"' . $key . '" => ': '') . ($val === null? 'NULL' : (is_string($val)? ('"' . htmlentities($val) . '"') : htmlentities($val)));
		}
	}
	
	return '[' . ($recLevel > $showLevel? '' : '<br />') . implode(($recLevel > $showLevel? ', ': '<br />'), $vals) . ($recLevel > $showLevel? '' : '<br />') . ']' . ($recLevel > 0? '' : '<br />');
}

function va_get_comment_text ($id, $lang, $internal, $db = false){
    if (!$db){
        global $vadb;
        $db = $vadb;
    }
	
	if (true /*!va_version_newer_than('va_181')*/){ //TODO change
		$content = $db->get_var($db->prepare('SELECT comment FROM im_comments WHERE Id = %s AND Language = %s', $id, $lang));
		
		$content = trim($content);
		
		if (!$content){
			return '';
		}
		
		parseSyntax($content, true, $internal);
		
		return $content;
	}

	$date_cache = $db->get_var($db->prepare('SELECT geaendert FROM a_text_cache WHERE Typ = "kommentar" AND Id = %s AND Sprache = %s', $id, $lang));
	$date_comment = $db->get_var($db->prepare('SELECT changed FROM im_comments WHERE Id = %s AND Language = %s', $id, $lang));
	
	if ($date_cache && $date_cache > $date_comment){
		return $db->get_var($db->prepare('SELECT Inhalt FROM a_text_cache WHERE Typ = "kommentar" AND Id = %s AND Sprache = %s', $id, $lang));
	}
	else {
		$content = $db->get_var($db->prepare('SELECT comment FROM im_comments WHERE Id = %s AND Language = %s', $id, $lang));
		
		parseSyntax($content, true, $internal);
		
		$db->query($db->prepare("REPLACE INTO a_text_cache (Typ, Id, Sprache, Intern, Inhalt) VALUES ('kommentar', %s, %s, %d, %s)",
				$id, $lang, ($internal? 1: 0), $content));
		
		return $content;
	}
}

function va_concept_compare ($t1, $t2, $search){
	$diff = mb_stripos ($t1, $search) - mb_stripos ($t2, $search);
	if($diff == 0){
		return strcmp($t1, $t2);
	}
	else {
		return $diff;
	}
}

//Preg replace with ignoring html tags
function va_replace_in_text ($pattern, $callback, $subject){
	if ($subject == '')
		return '';
	
	$subject = preg_replace('/<\!\[CDATA\[([^\]]*)\]\]>/', '$1', $subject);
	
	$doc = new IvoPetkov\HTML5DOMDocument();
	$doc->loadHTML($subject);
	
	$node = va_replace_in_single_html_node($doc, $pattern, $callback);
	return substr($doc->saveHTML($node), 28, -14);
}

function va_replace_in_single_html_node (DOMNode $node, $pattern, $callback){

	if ($node->nodeType === XML_TEXT_NODE){
		$newNode = $node->ownerDocument->createDocumentFragment();
		$newText = preg_replace_callback($pattern, $callback, $node->nodeValue);
		$newNode->appendXML(str_replace('&', '&amp;', $newText));
		return $newNode;
	}
	else {
		if ($node->hasChildNodes()){
			$replacements = [];
			foreach ($node->childNodes as $child){
				$newChild = va_replace_in_single_html_node($child, $pattern, $callback);
				$replacements[] = [$child, $newChild];
			}
	
			foreach ($replacements as $rep){
				$node->replaceChild($rep[1], $rep[0]);
			}
		}
			
		return $node;
	}
}

function va_get_comment_title ($id, $lang, $from_db = true, &$Ue = NULL, $db = NULL){
	
    $lang = substr($lang, 0, 1);
    
    if ($from_db){
        global $vadb;
        
        $sql = 'select Title_Html from a_lex_titles alt where id = %s AND lang = %s';
        return $vadb->get_var($vadb->prepare($sql, $id, $lang));
    }
    
	$letter = substr($id, 0, 1);
	$key = substr($id, 1);
	
	switch ($letter){
		case 'B':
		    $type_data = $db->get_row($db->prepare('SELECT Orth, Sprache FROM Basistypen WHERE Id_Basistyp = %d', $key), ARRAY_A);
		    $title = va_format_base_type('<em>' . $type_data['Orth'] . '</em>', $type_data['Sprache'], '0', $Ue);
			return '<span class="name">' . $title . '</span> - ' . '<span class="type">'.$Ue['BASISTYP'].'</span>';
			break;
			
		case 'L':
		    $type_data = $db->get_row($db->prepare('SELECT Orth, Sprache, Wortart, Genus, Affix FROM morph_Typen WHERE Id_morph_Typ = %d', $key), ARRAY_A);
		    $title = va_format_lex_type('<em>' . $type_data['Orth'] . '</em>', $type_data['Sprache'], $type_data['Wortart'], $type_data['Genus'], $type_data['Affix']);
		    return '<span class="name">' . $title . '</span> - ' . '<span class="type">'.$Ue['MORPH_TYP'].'</span>';
			break;
			
		case 'C':
			$nameData = $db->get_row($db->prepare("SELECT Name_$lang, Beschreibung_$lang, Name_D, Beschreibung_D FROM Konzepte WHERE Id_Konzept = %d", $key), ARRAY_A);
			
			$title = '';
			if (!$nameData["Beschreibung_$lang"]){
			    $title = '<img class="noTranslationImg" title="Translation missing" src="' . WP_CONTENT_URL . '/themes/verba-alpina/images/svg_flags/germany_svg_round.png" /> ';
			    $name = $nameData['Name_D'];
			    $desc = $nameData['Beschreibung_D'];
			}
			else {
			    $name = $nameData["Name_$lang"];
			    $desc = $nameData["Beschreibung_$lang"];
			}
			    
			if ($name && $name != $desc){
				$title .= '<span class="name">' .$name . '</span>'. ' - ' . '<span class="type">'.$Ue['KONZEPT'].'</span>';
			}
			else {
				$title .= '<span class="name">'. $desc . ' - </span><span class="type">'.$Ue['KONZEPT'].'</span>';
			}
			return $title;
			break;
	}
}

function va_get_comment_description($id, $lang){

global $vadb;

	$key = substr($id, 1);
	$lang = substr($lang, 0, 1);
	$descData = $vadb->get_row($vadb->prepare("SELECT Beschreibung_$lang FROM Konzepte WHERE Id_Konzept = %d", $key), ARRAY_A);

return '<div class="desc"> ('. $descData["Beschreibung_$lang"] . ')</div>';

}



function va_get_general_beta_parser (){
	global $general_beta_parser;
	
	if (!$general_beta_parser){
		$general_beta_parser = new VA_BetaParser('AIS');
	}
	
	return $general_beta_parser;
}

function va_count_words($str){
	return str_word_count(remove_accents(strip_tags($str)));
}

function search_va_locations ($search){
	global $Ue;
	global $lang;
	
	$db = IM_Initializer::$instance->database;
	$query = 'SELECT
            Id_Geo AS id,
            Name AS text,
            Category_Name AS description
        FROM Z_Geo
        WHERE Name LIKE "%'.$search.'%" AND Name NOT LIKE "%Ue[%"
        GROUP BY Name
        ORDER BY description ASC, text ASC';
	
	$names = $db->get_results($query);
	
	foreach ($names as $index => $name){
		$names[$index]->description = va_sub_translate($names[$index]->description, $Ue);
		$names[$index]->text = va_translate_extra_ling_name($names[$index]->text, $lang);
	}
	
	return ['results' => $names];
}

function va_translate_extra_ling_name ($name, $lang){
	//Check potential name translations:
	$name_list = explode('###', $name);
	$oname = $name_list[0];
	unset($name_list[0]);
	foreach ($name_list as $curr_oname){
		if($curr_oname[0] === $lang){
			$oname = mb_substr($curr_oname, 2);
			break;
		}
	}
	return $oname;
}

function va_orcid_link ($orcid){
	if (!$orcid)
		return '';
	
	return '<a target="_BLANK" href="https://orcid.org/' . $orcid . '"><img class="orcid" src="' . VA_PLUGIN_URL . '/images/orcid.svg" /></a>';
}

function va_version_list ($params){
	global $va_xxx;
	
	$versions = $va_xxx->get_col('SELECT Nummer FROM versionen WHERE Website ORDER BY Nummer DESC');
	
	$format = 'simple';
	
	if (isset($params['format'])){
		$format = $params['format'];
	}
	
	switch ($format){
		case 'simple':
			return implode(', ', $versions);
		default:
			return 'Invalid format';
	}
}

function va_add_references ($str, $ref_data){
    
    foreach ($ref_data as $ref){
        $data = explode('|', $ref);
        if($data[0] !== 'VA'){
            if($data[3]){
                $str .= '<a title="' . $data[0] . ': ' . ($data[4]? 's.v. ': '') . $data[1] . ' ' . $data[2] . '" href="' . $data[3] . '" target="_BLANK" class="encyLink">' . substr($data[0], 0, 1) . '</a>';
            }
            else {
                $str .= '<span title="' . $data[0] . ': ' . ($data[4]? 's.v. ': '') . $data[1] . ' ' . $data[2] . '" class="encyLink">' . substr($data[0], 0, 1) . '</span>';
            }
        }
    }
    return $str;
}

function va_get_db_creds ($login_data){
    
    $dbuser = $login_data[0];
    $dbpassw = $login_data[1];
    $is_external = get_option('va_external');
    $dbhost = $is_external? 'localhost:3311': 'gwi-sql.gwi.uni-muenchen.de:3311';
    
    return [$dbuser, $dbpassw, $dbhost];
}

function va_get_lex_alp_header_data ($from_db = true, &$db = NULL){

    if ($from_db){
        global $lang;
        global $vadb;
        
        $sql = 'select Id, Title_Html, QID from a_lex_titles alt where lang = %s order by Sort_Number ASC';
        return $vadb->get_results($vadb->prepare($sql, $lang), ARRAY_A);
    }
    
    $insert_data = [];
    
    foreach (va_get_lang_array() as $lang){
        $Ue = va_get_translations($db, $lang);
        
        $concepts = $db->get_results('
            SELECT DISTINCT CONCAT("C", Id_Konzept) AS Id, IF(QID != 0 AND QID IS NOT NULL, QID, NULL) AS QID 
            FROM A_Anzahl_Konzept_Belege JOIN Konzepte USING (Id_Konzept) 
            WHERE Id_Konzept != 2195 AND Id_Konzept != 2197 AND Anzahl_Allein_AK > 0 AND Relevanz AND (Name_' . $lang . ' != "" OR Id_Ueberkonzept = 707)', ARRAY_A);
        $mtypes = $db->get_results('SELECT DISTINCT CONCAT("L", Id_Type) AS Id, NULL AS QID FROM z_ling WHERE Id_Type != 6977 AND Type_Kind = "L" AND Source_Typing = "VA"', ARRAY_A);
        $btypes = $db->get_results('SELECT DISTINCT CONCAT("B", Id_Base_Type) AS Id, NULL AS QID FROM z_ling WHERE Id_Base_Type IS NOT NULL', ARRAY_A);
        
        $comment_data = array_merge($concepts, $mtypes, $btypes);
        
        foreach ($comment_data as $index => $comment){
            $comment_data[$index]['Title_Html'] = va_get_comment_title($comment['Id'], $lang, false, $Ue, $db);
            $title_raw = strip_tags($comment_data[$index]['Title_Html']);
            $parts = explode(' - ', $title_raw);
            $comment_data[$index]['Title'] = $parts[0];
            $comment_data[$index]['Entity'] = $parts[1];
        }
        
        usort($comment_data, function ($e1, $e2){
            $str1 = preg_replace('/[^a-zA-Z]/', '', str_replace('<em>', '', remove_accents($e1['Title'])));
            $str2 = preg_replace('/[^a-zA-Z]/', '', str_replace('<em>', '', remove_accents($e2['Title'])));
            $strcmp = strcasecmp($str1, $str2);
            
            if ($strcmp === 0){
                return strcasecmp($e1['Entity'], $e1['Entity']);
            }
            return $strcmp;
        });
          
        foreach ($comment_data as $index => $comment){
            $insert_data[] = array_merge($comment, ['Sort_Number' => $index, 'Lang' => $lang]);
        }
    }
    
    return $insert_data;
}

function va_insert_multiple (&$db, $table, $data, $param_str, $packet_size = 500){
    
    $field_str = '';
    foreach (array_keys($data[0]) as $key){
        $field_str .= ',`' . $key . '`';
    }
    
    $sql_prefix = 'INSERT INTO `' . $table . '` (' . mb_substr($field_str, 1) . ') VALUES ';
    
    $num = ceil(count($data) / $packet_size);
    
    for ($i = 0; $i < $num; $i++){
        $sql = $sql_prefix;
        
        for ($j = 0; $j < $packet_size; $j++){
            
            $index = $i * $packet_size + $j;
            if ($index >= count($data)){
                break;
            }
            
            if ($j > 0){
                $sql .= ',';
            }
            $sql .= $db->prepare('(' . $param_str .')', $data[$index]);
        }
        
        $db->query($sql);
    }
}

function va_get_lang_array (){
    return ['D','E','F','I','L','R','S'];
}

function va_get_translations (&$db, $lang){
    $transl = 'Begriff_' . $lang;
    
    $res = $db->get_results("SELECT Schluessel, IF($transl = '', CONCAT(Begriff_D, '(!!!)'), $transl) FROM Uebersetzungen" , ARRAY_N);
    
    $Ue = array();
    foreach ($res as $r){
        $Ue[$r[0]] = $r[1];
    }
    return $Ue;
}
?>