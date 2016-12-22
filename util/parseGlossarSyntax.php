<?php
	function parseSyntax(&$value, $replaceNewlines = false, $intern = false, $mode = 'A'){
		global $media_path;
		global $map_path;
		global $comment_path;
		global $lang;
		
		$media_path = get_site_url(1) . '/wp-content/uploads/';
		$map_path = va_get_map_link();
		$comment_path = va_get_comments_link();
		
	
		if($replaceNewlines){
			$value = nl2br($value);
			//Re-convert new lines after tags to newlines
			$value = preg_replace('#><br />#', '>', $value);
		}
	
		// " - " -> " – "
		$value = preg_replace('/ - /', ' – ', $value);
		
		//Aufzählungen
		$value = preg_replace('/[\n\r]?\*.+([\n|\r]\*.+)+/','<ul>$0</ul>',$value);
		$value = preg_replace('/[\n\r]\*(?!\*) *(.+)(([\n\r]\*{2,}.+)+)/','<li>$1<ul>$2</ul></li>',$value);
		$num_repl = 1;
		$len = 2;
		while($num_repl > 0){
			$value = preg_replace('/[\n\r]\*{' . $len . '}(?!\*) *(.+)(([\n\r]\*{' . ($len + 1) . ',}.+)+)/','<li>$1<ul>$2</ul></li>',$value, -1, $num_repl);
			$len++;
		}
		
		//Nummerierte Aufzählungen
		$value = preg_replace('/[\n\r]?#.+([\n|\r]#.+)+/','<ol>$0</ol>',$value);
		$value = preg_replace('/[\n\r]#(?!#) *(.+)(([\n\r]#{2,}.+)+)/','<li>$1<ol>$2</ol></li>',$value);
		$num_repl = 1;
		$len = 2;
		while($num_repl > 0){
			$value = preg_replace('/[\n\r]#{' . $len . '}(?!#) *(.+)(([\n\r]#{' . ($len + 1) . ',}.+)+)/','<li>$1<ol>$2</ol></li>',$value, -1, $num_repl);
			$len++;
		}
		
		$value = preg_replace('/^[#\*]+ *(.+)$/m','<li>$1</li>',$value);
		
		//Escape-Zeichen
		$value = preg_replace('/\\\\\\\\/', '\\\\', $value);
		$value = preg_replace('/\\\\\*/', '*', $value);
		$value = preg_replace('/\\\\#/', '#', $value);
		$value = preg_replace('/\\\\</', '&lt;', $value);
		$value = preg_replace('/\\\\>/', '&gt;', $value);
	
		//Bilder
		
		$value = preg_replace('/(\A|[^-])\[\[Bild:([^\|]*)\]\]/U',"$1<br /><img src=\"$media_path$2\"><br />",$value);
		
		$value = preg_replace_callback('/([^-])\[\[Bild:(.*)\|(.*)\]\]/U',
			function($treffer) {
				global $media_path;
				if(strpos($treffer[3], ':')){
					$parts = explode(':', $treffer[3]);
					if(strcasecmp($parts[0], 'Breite')){
						return "$treffer[1]<br /><img src=\"$media_path$treffer[2]\" width=\"$parts[1]px\"><br />";
					}
					else if(strcasecmp($parts[0], 'Höhe')){
						return "$treffer[1]<br /><img src=\"$media_path$treffer[2]\" height=\"$parts[1]px\"><br />";
					}
					else {
						return "$treffer[1]<br /><img src=\"$media_path$treffer[2]\"><br />";
					}
				}
				else {
					return "$treffer[1]<br /><img src=\"$media_path$treffer[2]\"  width=\"$treffer[3]\"><br />";
				}	
			}
			,$value);
		
		//Themenkarte
		$value = preg_replace_callback('/(\A|[^-])\[\[(([^\]]*)\|)?Karte:(.*)\]\]/U',
			function($treffer) {
				global $map_path;
				if($treffer[2] == ''){
					return "$treffer[1]<a target='_BLANK' href=\"" . $map_path . "&tk=" . getThemenkarteId($treffer[4]) . "\">$treffer[4]</a>";
				}
				else {
					return "$treffer[1]<a target='_BLANK' href=\"" . $map_path . "&tk=" . getThemenkarteId($treffer[4]) . "\">$treffer[3]</a>";
				}
			}
			,$value);
			
		//Kommentare	
		$value = preg_replace_callback('/(\A|[^-])\[\[(([^\]]*)\|)?Kommentar:(.)(.*)\]\]/U',
			function($treffer) {
				global $comment_path;
				$prefix = $treffer[4];
				$id = $treffer[5];
				if($treffer[2] == ''){
					$name = getCommentHeadline($prefix.$id, $lang);			
					return "$treffer[1]<a target='_BLANK' href='$comment_path&prefix=$prefix#$prefix$id'>$name</a>";
				}
				else {
					return "$treffer[1]<a target='_BLANK' href='$comment_path&prefix=$prefix#$prefix$id'>$treffer[3]</a>";
				}
			}
			,$value);
			
			
		
		parseBiblio ($value);
		
		//Ergänzungen/Änderungen
		$value = preg_replace_callback('#(<|\[\[)neu(>|\]\])#', function ($treffer) use ($intern){
			if($intern)
				return '<div style="background-color: #dffde1">';
			else
				return '';
		}, $value);
	
		$value = preg_replace_callback('#(<|\[\[)mod(>|\]\])#', function ($treffer) use ($intern){
			if($intern)
				return '<div style="background-color: #e6f7ff">';
			else
				return '';
		}, $value);
		$value = preg_replace_callback('#((<|\[\[)/neu(>|\]\]))|((<|\[\[)/mod(>|\]\]))#', function ($treffer) use ($intern){
			if($intern)
				return '</div>';
			else
				return '';
		}, $value);
		
		//SQL
		$value = preg_replace_callback('/([^-])\[\[SQL:(.*)((?:\|(?:db|width|height)=.*)*)\]\]/U',	function($treffer) {
			$atts['db'] = 'va_xxx';
			$atts['query'] = $treffer[2];
			$atts['login'] = 'va_wordpress';
			if(count($treffer) > 3){
				$params = explode('|', $treffer[3]);
				foreach($params as $p){
					$pair = explode('=', $p);
					if($pair[0])
						$atts[$pair[0]] = $pair[1];
				}
			}
			return $treffer[1] . sth_parseSQL($atts);
		}, $value);
		
		//Lokale UND globale Links
		$value = preg_replace_callback('/(\A|[^-])\[\[(.*)\]\]/U',
			function ($treffer) use ($mode) {
				global $media_path;
				global $lang;
				global $vadb;
				$parts = explode('|', $treffer[2]);
				
				$beschreibung = $parts[0];

				//Link ohne Beschreibung
				if(count($parts) == 1){
					$letter = $parts[0][0];
					$eintrag = $parts[0];
				}
				
				else {
					$image = stristr($parts[1], "Bild:");
					//Link auf Bild
					if($image){
						return "$treffer[1]<a href=\"" . $media_path . substr($parts[1], 5) . "\">$beschreibung</a>";
					}
					//Link mit Beschreibung
					else {
						$letter = $parts[1][0];
						$eintrag = $parts[1];
					}
				}
				
				if(strpos($eintrag,"http") === 0)
					return "$treffer[1]<a href=\"$eintrag\" target=\"_BLANK\">$beschreibung</a>";
				
				$url = va_get_glossary_link();
				if($mode === 'A'){
					$id = $vadb->get_var("SELECT Id_Eintrag FROM Glossar WHERE Terminus_$lang = '" . addslashes($eintrag) . "'");
					$url = add_query_arg('letter', $letter, $url) . '#' . $id;
				}
				else {
					$tags = $vadb->get_row("SELECT Id_Eintrag, Id_Tag FROM Glossar LEFT JOIN VTBL_Eintrag_Tag USING (Id_Eintrag) WHERE Terminus_$lang = '" . addslashes($eintrag) ."'", ARRAY_N);
					$url = add_query_arg('tag', $tags[1]? $tags[1] : 0, $url) . '#' . $tags[0];
				}
				return "$treffer[1]<a href=\"" . $url . "\">$beschreibung</a>";
			},
			$value);
			
		//Vorgestelltes Minus (Escape-Zeichen) entfernen
		$value = preg_replace('/-\[/','[',$value);
	}
	
	function createBiblHTML ($abk, $code, $descr = null){
		if($descr == null)
			$descr = $abk;
		
		return "<span class='bibl' data-bibl='$code' style='text-decoration: underline; cursor: pointer;'>$descr</span>";
	}
	
	function addBiblDIV ($code, $content, &$codesBibl){
		if(!array_key_exists($code, $codesBibl)){
			$codesBibl[$code] = "<div id='$code' style='display: none;'>
					$content
				</div>";
		}
	}
	
	function getThemenkarteId($name){
		global $vadb;
		return $vadb->get_var($vadb->prepare("SELECT Id_Syn_Map FROM im_syn_maps WHERE Name = %s", $name));
	}
	
	function getCommentHeadline($id, $lang){
		global $vadb;
		return $vadb->get_var($vadb->prepare("SELECT getEntryName(%s, %s)", $id, $lang));
	}
	
	function parseBiblio (&$text){
		$codesBibl = array ();
		
		$text = preg_replace_callback('/([^-])\[\[(([^\[]*)\|)?Bibl:([^\[]*)\]\]/',
			function ($treffer) use (&$codesBibl){
				global $va_xxx;
				$b = $va_xxx->get_results("SELECT Autor, Titel, Ort, Jahr, Download_URL, Band, Enthalten_In, Seiten, Verlag FROM Bibliographie WHERE Abkuerzung = '$treffer[4]'", 'ARRAY_N');
				$code = preg_replace('/\s+/', '', $treffer[4]);
				$code = str_replace('/','',$code);
				$code = str_replace('.','',$code);
				$abk = $treffer[3]? $treffer[3] : null;
				addbiblDIV($code, ((sizeof($b) == 0)? 'Eintrag nicht gefunden': va_format_bibliography($b[0][0], $b[0][1], $b[0][3], $b[0][2], $b[0][4], $b[0][5], $b[0][6], $b[0][7], $b[0][8])), $codesBibl);
				return $treffer[1] . createBiblHTML($treffer[4], $code, $abk);
			}
			
		,$text);
		
		$text .= implode('', $codesBibl);
	}
?>