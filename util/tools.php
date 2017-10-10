<?php

//Action Handler
add_action('wp_ajax_token_ops', 'token_ops'); //TODO integrate into va_ajax

$mapPage = get_page_by_title('KARTE');
global $va_map_url;
if($mapPage != null){
	$va_map_url = get_page_link($mapPage);
}

function va_get_glossary_link ($id = null){

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
	
	return $link;
}

function va_get_map_link ($element = null){
	global $va_map_url;
	$result = '';
	
	if($va_map_url){
		$result = $va_map_url;
		if($element != null){
			$result = add_query_arg('single', $element, $result);
		}
	}
	return $result;
}

function va_get_comments_link (){
	$commentsPage = get_page_by_title('KOMMENTARE');
	if($commentsPage != null){
		return get_page_link($commentsPage);
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
		$res .= ($author == ''? '': ': ') . $title;
		if($loc != '')
			$res .= ', ' . $loc;
		if($in != '')
			$res .= ', in ' . $in;
		if($band != '')
			$res .= ', vol. ' . $band;
		if($seiten != '')
			$res .= ', ' . $seiten;
		if($verlag != '')
			$res .= ', ' . $verlag;
		if($link != '')
			if($link_abgesetzt)
				$res .= "\n<br /><br />\n<a href='$link'>Link</a>";
			else
				$res .= "\n(<a href='$link'>Link</a>)";
		return $res;
}

function va_format_base_type ($str, $uncertain = '0'){
	global $Ue;
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
	return '<a href="' . va_get_glossary_link($id) . '" target="_blank"><i class="helpsymbol fa fa-question-circle-o" style="vertical-align: middle;" title="' . $Ue['HILFE'] . '" ></i></a>';
}

function va_get_mouseover_help ($text, &$Ue, &$db, $lang, $id_glossary = NULL){
	$res = '<i class="helpsymbol fa fa-question-circle-o va_mo_help" style="vertical-align: middle;"></i>';
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

function va_format_lex_type ($orth, $lang, $word_class, $gender, $affix, &$Ue = NULL){
	if($Ue){
		if(isset($Ue['ABK_' . $lang])){
			$lang = $Ue['ABK_' . $lang];
		}
	}
	
	if($lang && $gender)
		 $result = $orth . ' (' . $lang . '.' . json_decode('"\u00A0"') . $gender . '.)';
	else if ($lang)
		$result = $orth . ' (' . $lang . '.)';
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

function token_ops (){
	global $va_xxx;
	
	if($_POST['stage'] == 'getTokens'){
		switch ($_POST['type']){
			case 'ipa':
				$tokens = $va_xxx->get_col("SELECT distinct Token FROM Tokens JOIN Stimuli USING (ID_Stimulus) WHERE Erhebung = '" . $_POST['source'] . "'" . ($_POST['all'] === 'true'? '' : " AND IPA = ''") . " AND Token != '' AND NOT EXISTS (SELECT * FROM Sonderzeichen WHERE Zeichen = Token)", 0);
				break;
				
			case 'original':
				$tokens = $va_xxx->get_results("SELECT distinct Token, Erhebung FROM Tokens JOIN Stimuli USING (ID_Stimulus) WHERE Erhebung != 'ALD-II' AND Erhebung != 'ALD-I' AND Erhebung != 'ALTR' AND Erhebung != 'Clapie' AND Erhebung != 'APV' AND Erhebung != 'BSA' AND Erhebung != 'WBOE' AND Original = '' AND Token != ''", ARRAY_N);
				break;
			
			case 'bsa':
				$tokens = $va_xxx->get_results("SELECT distinct Aeusserung, Erhebung FROM Aeusserungen JOIN Stimuli USING (ID_Stimulus) WHERE Erhebung = 'BSA' AND Bemerkung NOT like '%BayDat-Transkription%'", ARRAY_N);
				break;
			
			default:
				$tokens = array();
		}
		echo json_encode($tokens);
	}
	
	if($_POST['stage'] == 'compute'){
		global $va_xxx;
		switch ($_POST['type']){
			case 'ipa':
				$tokens = json_decode(stripslashes($_POST['data']));
				$missing_chars = array();
				$quelle = $_POST['source'];
				$transformations = '';
				$errors = '';
				
				$akzente = $va_xxx->get_results("SELECT Beta, IPA FROM Codepage_IPA WHERE Art = 'Akzent' AND Erhebung = '$quelle'", ARRAY_N);
				$vokale = $va_xxx->get_var("SELECT group_concat(DISTINCT SUBSTR(Beta, 1, 1) SEPARATOR '') FROM Codepage_IPA WHERE Art = 'Vokal' AND Erhebung = '$quelle'", 0, 0);
				$numComplete = 0;
				
				foreach ($tokens as $token){
					$complete = true;		
					$result = '';
					$akzentExplizit = false;
					$indexLastVowel = false;			
			
					foreach ($token as $index => $character) {
						foreach ($akzente as $akzent) {
							$ak_qu = preg_quote($akzent[0], '/');
							$character = preg_replace_callback('/([' . $vokale . '][^' . $ak_qu . 'a-zA-Z]*)' . $ak_qu . '/', function ($matches) use (&$result, $akzent, &$akzentExplizit){
								$result .= $akzent[1];
								$akzentExplizit = true;
								return $matches[1];
							}, $character);
						}
						
						
						$ipa = $va_xxx->get_results("SELECT IPA from Codepage_IPA WHERE Erhebung = '" . $quelle . "' AND Beta = '" . addcslashes($character, "\'") . "' AND IPA != ''", ARRAY_N);
						if($ipa[0][0]){
							$result .= $ipa[0][0];
							
							if(strpos($vokale, $character[0]) !== false){
								$indexLastVowel = mb_strlen($result) - mb_strlen($ipa[0][0]);
							}
						}
						else {
							if(!in_array($character, $missing_chars)){
								$missing_chars[] = $character;
								$errors .= "Eintrag \"$character\" fehlt fuer \"$quelle\"!\n";
							}
							$complete = false;
						}
						
						
						
					}
					
					//Akzent auf letzer Silbe, falls nicht gesetzt
					$addAccent = !$akzentExplizit && $indexLastVowel !== false && ($quelle === 'ALP' || $quelle === 'ALJA' || $quelle === 'ALL');
					
					if($addAccent){
						$result = mb_substr($result, 0, $indexLastVowel) . $akzente[0][1] . mb_substr($result, $indexLastVowel);
					}
					
					
					if($complete){
						$transformations .= implode('', $token) . ' -> ' . $result . ($addAccent? ' (Akzent hinzugefÃ¼gt)' : '') . "\n";
						$va_xxx->query("UPDATE Tokens SET IPA = '" . addslashes($result) . "', Trennzeichen_IPA = (SELECT IPA FROM Codepage_IPA WHERE Art = 'Trennzeichen' AND Beta = Trennzeichen AND Erhebung = '$quelle')
						 WHERE EXISTS (SELECT * FROM Stimuli WHERE Stimuli.Id_Stimulus = Tokens.Id_Stimulus AND Erhebung = '$quelle') AND Token = '" . addslashes(implode('', $token)) . "'");
						$numComplete++;
					}
				}
				echo json_encode(array($transformations, $errors, $numComplete));
			break;
		}
	}
	
	die;
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
	$title = $vadb->get_var("SELECT Terminus_$lang FROM Glossar WHERE Id_Eintrag = $id");
 	$link = va_get_glossary_link();
 	$link = add_query_arg('db', substr($va_current_db_name, 3), $link);
 	$link = add_query_arg('letter', remove_accents(substr($title, 0, 1)), $link);
 	$link .= '#' . $id;
	
 	return	implode(' / ', $authors) . ': s.v. “' . $title . '”, in: VA-' . substr(get_locale(), 0, 2) . ' ' .
 			substr($va_current_db_name, 3, 2) . '/' . substr($va_current_db_name, 5) . ', ' . $Ue['METHODOLOGIE'] . ', ' . $link;
 }
 
function va_remove_special_chars ($str){
 	return preg_replace('/[^a-zA-Z0-9]/', '', remove_accents($str));
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
 	$title = va_sub_translate($vadb->get_var("SELECT getEntryName('$id', '$lang')"), $Ue);

 	$link = va_get_comments_link();
 	$link = add_query_arg('db', substr($va_current_db_name, 3), $link);
 	$link .= '#' . $id;
 
 	return	implode(' / ', $authors) . ': s.v. “' . $title . '”, in: VA-' . substr(get_locale(), 0, 2) . ' ' .
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
?>