<?php
function parseSyntax(&$value, $replaceNewlines = false, $intern = false, $mode = 'A') {
	set_error_handler('va_syntax_warning_handler', E_WARNING);
	
	try {
		global $media_path;
		global $map_path;
		global $comment_path;
		global $lang;
		global $general_beta_parser;
		
		$media_path = get_site_url (1) . '/wp-content/uploads/';
		$map_path = va_get_map_link ();
		$comment_path = va_get_comments_link ();
		$dbdoku_path = va_get_dbdoku_link();

		if ($replaceNewlines) {
			$value = nl2br ($value);
			// Re-convert new lines after tags to newlines
			$value = preg_replace ('#><br />#', '>', $value);
		}
		
		//Beta code
		$value = preg_replace_callback('/(?<!-)#1([^#]*)##/', function ($match){
		    $beta_parser = va_parse_syntax_get_beta_parser();
		    
			$parse_res = $beta_parser->convert_to_original($match[1]);
			if ($parse_res['string'])
				return '<span style="font-family: arial unicode;">' . $parse_res['string'] . '</span>';
			return '<span style="background: red;">' . $match[1] . '</span>';
		}, $value);
		
		// " - " -> " – "
		$value = preg_replace ('/ - /', ' – ', $value);
		
		// Aufzählungen
		$value = va_parse_list_syntax('*', 'ul', 'li', $value);
		$value = va_parse_list_syntax('#', 'ol', 'li', $value);
		
		
		// Escape-Zeichen
		$value = preg_replace ('/\\\\\\\\/', '\\\\', $value);
		$value = preg_replace ('/\\\\\*/', '*', $value);
		$value = preg_replace ('/\\\\#/', '#', $value);
		$value = preg_replace ('/\\\\</', '&lt;', $value);
		$value = preg_replace ('/\\\\>/', '&gt;', $value);
		
		// Kein wp_texturize in auskommentieren [[..]] Befehlen
		$value = preg_replace ('/(-\[\[.*\]\])/U', '<code>$1</code>', $value);
		
		// Bilder
		$value = preg_replace ('/(?<!-)\[\[Bild:([^\|]*)\]\]/U', "<br /><img src=\"$media_path$1\" /><br />", $value);
		
		$value = preg_replace_callback ('/(?<!-)\[\[Bild:(.*)\|(.*)\]\]/U', function ($treffer) {
			global $media_path;
			if (strpos ($treffer[2], ':')) {
				$parts = explode (':', $treffer[2]);
				if (strcasecmp ($parts[0], 'Breite')) {
					return "<br /><img src=\"$media_path$treffer[1]\" width=\"$parts[1]px\" /><br />";
				} else if (strcasecmp ($parts[0], 'Höhe')) {
					return "<br /><img src=\"$media_path$treffer[1]\" height=\"$parts[1]px\" /><br />";
				} else {
					return "<br /><img src=\"$media_path$treffer[1]\"><br />";
				}
			} else {
				return "<br /><img src=\"$media_path$treffer[1]\"  width=\"$treffer[2]\" /><br />";
			}
		}, $value);
		
		// Themenkarte
		$value = preg_replace_callback ('/(?<!-)\[\[(?:([^\]]*)\|)?Karte:([^|]*)(\|Popup)?\]\]/U', function ($treffer) {
			global $map_path;
			
			$map = $treffer[2];
			if (! $treffer[1]) {
				$label = $treffer[2];
			} else {
				$label = $treffer[1];
			}
			
			$murl = $map_path;
			if (! is_numeric ($map)) {
				$map = getThemenkarteId ($map);
			}
			
			global $vadb;
			$options = $vadb->get_var ($vadb->prepare ('SELECT Options FROM IM_Syn_Maps WHERE Id_Syn_Map = %d', $map));
			$options = json_decode ($options, true);
			$dbset = $options['tdb'];
			global $va_next_db_name;
			if ($dbset == $va_next_db_name){ //The synoptic map stores a future version
				$dbset = 'xxx';
			}
			$murl = add_query_arg ('db', $dbset, $murl);
			
			if (isset($treffer[3])) { //PHP omits the last element if it is empty...
				return "<a target='_BLANK' href=\"" . add_query_arg ('tk', $map, $murl) . "\" onclick=\"window.open(this.href,this.target,'width=1024,height=768'); return false;\">$label</a>";
			} else {
				return "<a target='_BLANK' href=\"" . add_query_arg ('tk', $map, $murl) . "\">$label</a>";
			}
		}, $value);
		
		$is_lex = false;
		global $post;
		if (($post && $post->post_title === 'LexAlp') || (isset($_REQUEST['namespace']) && $_REQUEST['namespace'] === 'lex_alp')) {
			$is_lex = true;
		}

		// Kommentare
		$value = preg_replace_callback ('/(?<!-)\[\[(([^\]]*)\|)?Kommentar:(.)(.*)\]\]/U', function ($treffer) use ($is_lex, $lang) {
			global $comment_path;
			$prefix = $treffer[3];
			$id = $treffer[4];
			
			$link = $comment_path . '#' . $prefix . $id;
			if ($is_lex) {
				$start = '<a';
				$link = 'javascript:localLink("' . $prefix . $id . '")';
			} else {
				$start = "<a target='_BLANK'";
			}
			
			if ($treffer[1] == '') {
				$name = getCommentHeadline ($prefix . $id, $lang);
				return "$start href='$link'>$name</a>";
			} else {
				return "$start href='$link'>$treffer[2]</a>";
			}
		}, $value);
		
		parseBiblio ($value);
		
		// Ergänzungen/Änderungen
		$value = preg_replace_callback ('#(<|\[\[)neu(?: fertig="[IFSREL]*")?(>|\]\])#', function ($treffer) use ($intern) {
			if ($intern)
				return '<div style="background-color: #dffde1; display: inline-block;">';
			else
				return '';
		}, $value);
		
		$value = preg_replace_callback ('#(<|\[\[)mod(?: fertig="[IFSREL]*")?(>|\]\])#', function ($treffer) use ($intern) {
			if ($intern)
				return '<div style="background-color: #e6f7ff; display: inline-block;">';
			else
				return '';
		}, $value);
		
		$value = preg_replace_callback ('#(<|\[\[)anm(>|\]\])#', function ($treffer) use ($intern) {
			if ($intern)
				return '<div style="background-color: #fafad2; display: inline-block;">';
			else
				return '';
		}, $value);
		
		$value = preg_replace_callback ('#((<|\[\[)/neu(>|\]\]))|((<|\[\[)/mod(>|\]\]))|((<|\[\[)/anm(>|\]\]))#', function ($treffer) use ($intern) {
			if ($intern)
				return '</div>';
			else
				return '';
		}, $value);
		
		// SQL
		$value = preg_replace_callback ('/(?<!-)\[\[SQL:(.*)((?:\|(?:db|width|height|id)=.*)*)\]\]/sU', function ($treffer) {
			global $va_current_db_name;
			$atts = [];
			$atts['db'] = $va_current_db_name;
			$atts['query'] = str_replace('<br />', '', $treffer[1]);
			$atts['login'] = 'va_wordpress';
			if (count ($treffer) > 2) {
				$params = explode ('|', $treffer[2]);
				foreach ( $params as $p ) {
					$pair = explode ('=', $p);
					if ($pair[0]) {
						$atts[$pair[0]] = $pair[1];
					}
				}
			}
			
			if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'va_protocols' && isset($_REQUEST['query']) && ($_REQUEST['query'] == 'get' || $_REQUEST['query'] == 'update')){
				//Reloading protocol
				$atts['lazy'] = false;
			}
			
			return sth_parseSQL ($atts);
		}, $value);
		
		// Seiten
		$value = preg_replace_callback ('/(?<!-)\[\[(([^\]]*)\|)?Seite:(.*)\]\]/U', function ($treffer) {
			global $Ue;
			
			$fragment = false;
			$pos_fragment = mb_strpos($treffer[3], '#');
			if ($pos_fragment){
				$fragment = mb_substr($treffer[3], $pos_fragment + 1);
				$treffer[3] = mb_substr($treffer[3], 0, $pos_fragment);
			}
			
			$dtext = NULL;
			if ($treffer[3] == 'START'){
				$link = get_home_url();
			}
			else if ($treffer[3] == 'EXPORT'){
				$link = get_home_url() . '/export';
			}			
			else if ($treffer[3] == 'REGISTER'){
				$link = get_home_url() . '/wp-login.php?action=register';
			}
			else if ($treffer[3] == 'CSTOOL'){
				$link = get_home_url() . '/en/?page_id=1741';
			}
			else {
				$page = get_page_by_title ($treffer[3]);
				if (!$page) {
					return 'PAGE NOT FOUND!';
				}
				$link = get_page_link ($page->ID);
				$dtext = $Ue[$treffer[3]];
			}

			if ($fragment){
				$link .= '#' . $fragment;
			}

			if ($treffer[1] == '') {
				return "<a href='" . $link . "'>" . ($dtext ?: $link) . "</a>";
			} else {
				return "<a href='" . $link . "'>" . $treffer[2] . "</a>";
			}
		}, $value);
		
		// Tabellen
		$value = preg_replace_callback ('/(?<!-)\[\[(([^\]]*)\|)?Tabelle:(.*)\]\]/U', function ($treffer) use ($dbdoku_path) {
			$link = $dbdoku_path . '&table=' . urlencode($treffer[3]);
			
			if ($treffer[1] == '') {
				return "<a href='" . $link . "'>" . $treffer[3] . "</a>";
			} else {
				return "<a href='" . $link . "'>" . $treffer[2] . "</a>";
			}
		}, $value);
		
		// Lokale UND globale Links
		$value = preg_replace_callback ('/(?<!-)\[\[(?!(?:Abk:|Konst:))(.*)\]\]/U', function ($treffer) use ($mode, $intern) {
			global $media_path;
			global $lang;
			global $vadb;
			$parts = explode ('|', $treffer[1]);
			
			$beschreibung = $parts[0];
			
			// Link ohne Beschreibung
			if (count ($parts) == 1) {
				if (mb_strpos($beschreibung, 'va/') === 0){
					$beschreibung = get_home_url() . mb_substr($beschreibung, 2);
				}
				$eintrag = $beschreibung;
			} 
			else {
				$image = stristr ($parts[1], "Bild:");
				// Link auf Bild
				if ($image) {
					return "<a href=\"" . $media_path . substr ($parts[1], 5) . "\">$beschreibung</a>";
				}			// Link mit Beschreibung
				else {
					$eintrag = $parts[1];
					if (mb_strpos($eintrag, 'va/') === 0){
						$eintrag = get_home_url() . mb_substr($eintrag, 2);
					}
				}
			}
			
			$eintrag = trim ($eintrag);
			
			if ($eintrag == '')
				return '';
			
			if (strpos ($eintrag, "http") === 0)
				return "<a href=\"$eintrag\" target=\"_BLANK\">$beschreibung</a>";
			
			$url = va_get_glossary_link ();
			if ($mode === 'A') {
				$ent = $vadb->get_row ("SELECT Id_Eintrag, Intern, Fertig FROM Glossar WHERE Terminus_$lang = '" . addslashes ($eintrag) . "'", ARRAY_A);
				$url = add_query_arg ('letter', $eintrag[0], $url) . '#' . $ent['Id_Eintrag'];
			} else {
				$ent = $vadb->get_row ("SELECT Id_Eintrag, Id_Tag, Intern, Fertig FROM Glossar LEFT JOIN VTBL_Eintrag_Tag USING (Id_Eintrag) WHERE Terminus_$lang = '" . addslashes ($eintrag) . "'", ARRAY_A);
				$url = add_query_arg ('tag', $ent['Id_Tag'] ? $ent['Id_Tag'] : 0, $url) . '#' . $ent['Id_Eintrag'];
			}
			
			if ($ent['Intern']){
				if ($intern){
					return "<a style='background: LemonChiffon' href=\"" . $url . "\">$beschreibung</a>";
				}
				else {
					return $beschreibung;
				}
			}
			else if (!$ent['Fertig']){
				if ($intern){
					return "<a style='background: #ffe6e6' href=\"" . $url . "\">$beschreibung</a>";
				}
				else {
					return $beschreibung;
				}
			}
			
			return "<a href=\"" . $url . "\">$beschreibung</a>";
		}, $value);
		
		//Abkürzungen
		$value = va_add_abrv($value);
		
		//Explizite Abkürzungen
		$value = preg_replace_callback('/(?<!-)\[\[Abk:([^|]*)\|([^\]]*)\]\]/', function ($treffer){
			return '<span class="sabr" title="' . htmlspecialchars($treffer[2]) . '">' . $treffer[1] . '</span>';
		}, $value);
			
		//Ausnahmen bei Abkürzungen
		$value = preg_replace('/(?<!-)\[\[Konst:([^\]]*)\]\]/', '$1', $value);
		
		// Vorgestelltes Minus (Escape-Zeichen) entfernen
		$value = preg_replace ('/-\[/', '[', $value);
	}
	catch (ErrorException $e){
		$value = '<span style="color: red">' . $e->getMessage() . '</span>';
	}
	
	restore_error_handler();
}
function va_create_bibl_html($abk, $descr = null) {
	if ($descr == null)
		$descr = $abk;
	
	$code = preg_replace ('/\s+/', '', $abk);
	$code = str_replace ('/', '', $code);
	$code = str_replace ('.', '', $code);
	$code = mb_strtolower($code);
	
	return [
		$code,
		"<span class='bibl' data-bibl='$code'>$descr</span>"
	];
}

function va_add_bibl_div($code, $content, &$codesBibl) {
	if (! array_key_exists ($code, $codesBibl)) {
		$codesBibl[$code] = "<div id='$code' style='display: none;'>
			$content
			</div>";
	}
}

function getThemenkarteId($name) {
	global $vadb;
	return $vadb->get_var ($vadb->prepare ("SELECT Id_Syn_Map FROM im_syn_maps WHERE Name = %s", $name));
}

function getCommentHeadline($id, $lang) {
	global $vadb;
	return $vadb->get_var ($vadb->prepare ("SELECT getEntryName(%s, %s)", $id, $lang));
}

function parseBiblio(&$text) {
	$codesBibl = array();
	
	$text = preg_replace_callback ('/([^-])\[\[(([^\[]*)\|)?Bibl:([^\[]*)\]\]/', function ($treffer) use (&$codesBibl) {
		global $vadb;
		$b = $vadb->get_results ("SELECT Autor, Titel, Ort, Jahr, Download_URL, Band, Enthalten_In, Seiten, Verlag FROM Bibliographie WHERE Abkuerzung = '$treffer[4]'", 'ARRAY_N');
		$abk = $treffer[3] ? $treffer[3] : null;
		list ($code, $html) = va_create_bibl_html ($treffer[4], $abk);
		va_add_bibl_div ($code, ((sizeof ($b) == 0) ? 'Eintrag nicht gefunden' : va_format_bibliography ($b[0][0], $b[0][1], $b[0][3], $b[0][2], $b[0][4], $b[0][5], $b[0][6], $b[0][7], $b[0][8])), $codesBibl);
		return $treffer[1] . $html;
	}, 
	$text);
	
	$text .= implode ('', $codesBibl);
}

function va_add_abrv($value) {
	if (va_version_newer_than ('va_172')) {
		$abr_list = [];
		
		global $vadb;
		global $lang;
		$abks = $vadb->get_results ("SELECT Abkuerzung, Bedeutung FROM Abkuerzungen WHERE Sprache = '$lang' OR Sprache = 'ALL'", ARRAY_A);
		
		$isolangs = $vadb->get_results("
			SELECT CONCAT(Abkuerzung, '.') AS Abkuerzung, CONCAT(Bezeichnung_$lang, IF(ISO639 = '', '', CONCAT(' (ISO 639-', ISO639, ')'))) AS Bedeutung 
			FROM Sprachen 
			WHERE Bezeichnung_$lang != '' AND (ISO639 = '3' OR ISO639 = '5' OR ISO639 = '')", ARRAY_A);
		
		$abk_map = [];
		
		foreach ($abks as $abk){
			$abk_map[$abk['Abkuerzung']] = $abk['Bedeutung'];
		}
		
		foreach ($isolangs as $isolang){
			$abk_map[$isolang['Abkuerzung']] = $isolang['Bedeutung'];
			$abk_map[ucfirst($isolang['Abkuerzung'])] = $isolang['Bedeutung'];
		}
			
		$value = va_replace_in_text ('/(?<![\pL])(?<!Konst:)(' . implode('|', array_map(function ($abk){
			return preg_quote($abk);
		}, array_keys($abk_map))) . ')(?![\pL])/u', function ($treffer) use (&$abk_map, &$abr_list) {
		    $cleaned_abr = str_replace (' ', 'SPACE', str_replace ('.', 'DOT', $treffer[1]));
			if (! isset ($abr_list[$treffer[1]])) {
			    $abr_desc = $abk_map[$treffer[1]];
			    parseSyntax($abr_desc);
			    
				$abr_list[$treffer[1]] = '<div id="ABR_' . $cleaned_abr . '" style="display: none;">' . $abr_desc . '</div>';
			}
			return '<span class="vaabr" data-vaabr="' . $cleaned_abr . '">' . $treffer[1] . '</span>';
		}, $value);
		
		return $value . implode ('', $abr_list);
	}
	return $value;
}

function va_syntax_warning_handler ($errno, $errstr) {
	throw new ErrorException($errstr);
}

function va_parse_list_syntax($char, $main, $sub, $value){
	$char_quoted = preg_quote($char);
	
	//Add list tags
	$num_repl = 1;
	$len = 1;
	while ($num_repl > 0) {
		$value = preg_replace (
			'/^' . $char_quoted . '{' . $len . ',}.+([\n\r]' . $char_quoted . '.+)*/m',
			"<" . $main . ">\n$0\n</" . $main . ">" . ($len > 1? '</' . $sub . '>' : ''),
			$value, - 1, $num_repl);
		$len++;
	}
	
	//Add element tags to all elements that are not followed by a sub-list
	$value = preg_replace(
		'@^' . $char_quoted . '+(.+)(?=[\n\r](' . $char_quoted . '|</' . $main . '>))@m',
		'<' . $sub . '>$1</' . $sub . '>' , $value);
	
	//Add opening tag to elements followed by sublist (closing element is added to the end of the sub-list in the first part)
	$value = preg_replace(
		'/^' . $char_quoted . '+(.+)/m',
		'<' . $sub . '>$1' , $value);
	
	return $value;
}

global $va_parse_syntax_beta_parser;
$va_parse_syntax_beta_parser = NULL;

function va_parse_syntax_get_beta_parser (){
    global $va_parse_syntax_beta_parser;
    
    if (!$va_parse_syntax_beta_parser){
        $va_parse_syntax_beta_parser = va_get_general_beta_parser();
    }
    
    return $va_parse_syntax_beta_parser;
}
?>