<?php
function ladeGlossar (){
	global $Ue;
	global $admin;
	global $va_mitarbeiter;
	global $va_current_db_name;
	global $lang;
	global $vadb;

	//Nutzerberechtigung (Projektmitarbeiter usw. können interne Einträge sehen)
	$intern = $admin || $va_mitarbeiter;
	
	$letterSet = isset($_GET['letter']);
	$tagSet = isset($_GET['tag']);
	$getAll = isset($_GET['all']);
	
	if(isset($_GET["mode"])){
		$mode = $_GET["mode"];
	}
	else {
		$mode = $tagSet? 'T': 'A';
	}
	
	?>
	<script type="text/javascript">
	jQuery(function() {
		addBiblioQTips(jQuery(".entry-content"));

		jQuery(".quote").each(function (){
			jQuery(this).qtip({
				"show" : "click",
				"hide" : "unfocus",
				"content" : {
					"text" : function (){
						var result = "<div class='divPlain'>" + jQuery(this).data("plain") + "</div><br />";
						result += "<input class='copyButton buttonPlain' style='display: block; margin: auto;' type='button' value='<?php echo $Ue['KOPIEREN']; ?>' /><br /><br />";
						result += "<div class='divBibtex'>" + jQuery(this).data("bibtex") + "</div><br />";
						result += "<input class='copyButton buttonBibtex' style='display: block; margin: auto;' type='button' value='<?php echo $Ue['KOPIEREN']; ?>' />";
						return result;
					}
				},
				"events" : {
					"visible": function (event, api){
						jQuery(event.target).find(".buttonPlain").data("content", jQuery(event.target).find(".divPlain").html());
						jQuery(event.target).find(".buttonBibtex").data("content", jQuery(event.target).find(".divBibtex").html());
					}
				}
			});
		});

		addCopyButtonSupport();

		jQuery("#sorting").val("<?php echo $mode;?>");
		
		jQuery("#sorting").change(function (){
			if(this.value == "T"){
				location.href = "<?php echo add_query_arg('mode','T', get_permalink());?>";
			}
			else if(this.value == "A"){
				location.href = "<?php echo add_query_arg('mode','A', get_permalink());?>";
			}
		});
	});
	</script> 
	
	<style type="text/css">
		.linkSpan {
			margin-bottom : 0.5em;
			display: inline-block;
		}
	</style>

	<?php
				
		echo $Ue['SORTIERUNG'];
		?>
		<select id="sorting">
			<option value="A"><?php echo $Ue['ALPHABETISCH']; ?></option>
			<option value="T"><?php echo $Ue['NACH_TAGS']; ?></option>
		</select>
		
		<br />
		<br />
		
		
		<a href="<?php echo add_query_arg('all', '1', get_permalink()); ?>"><?php echo $Ue['ALLE_EINTRAEGE']; ?></a>
		
		<br />
		<br />
		
		<?php
		
		//Buchstaben-Verweise
		$entryNames = getTitles($intern, $lang, $mode === 'T');
		$nameList = '<div id="entryList">';
		$currentIndex = 0;
		$currentTitle = 0;
		
		
		/*while($currentTitle < sizeof($entryNames) && !ctype_alpha($entryNames[$currentTitle][0][0])){
			$currentTitle++;	
		}*/
		

		if($tagSet || $mode === 'T'){
			$allTags = $vadb->get_results("
				SELECT DISTINCT IF(Id_Tag IS NULL, 0, Id_Tag), IF(Tag IS NULL, '(no Tag)', Tag) 
				FROM Glossar LEFT JOIN VTBL_Eintrag_Tag USING (Id_Eintrag) LEFT JOIN Tags USING (Id_Tag)
				ORDER BY Tag ASC", ARRAY_N);

			foreach ($allTags as $tag){
				$style = '';
				if(isset($_GET['tag']) && $tag[0] == $_GET['tag']){
					$style = 'style = "color : blue"';
				}
				$url = add_query_arg('tag', $tag[0], get_permalink());
				echo "<a href = \"$url\" $style>" . va_translate($tag[1], $Ue) . "</a>&nbsp;&nbsp;\n";
				$nameList .= '<h2 style="margin-bottom: 0.5em">' . va_translate($tag[1], $Ue) . '</h2>';
				while($currentTitle < count($entryNames) && strcasecmp($entryNames[$currentTitle][2], $tag[1]) === 0){
					if(!$tagSet){
						$url = add_query_arg('tag', $tag[0], get_permalink()) . '#' . $entryNames[$currentTitle][1];
						$estyle = va_get_glossary_link_style($entryNames[$currentTitle][1]);
						$nameList .= "<a style='$estyle' href=\"$url\">" . $entryNames[$currentTitle][0] . '</a><br />';
					}
					$currentTitle++;
				}
				$nameList .= '<br />';
				$currentIndex++;
			}
		}
		else {
			$allLetters = range('A','Z');
			foreach ($allLetters as $letter){
				if($currentTitle < sizeof($entryNames) && strcasecmp(va_only_latin_letters($entryNames[$currentTitle][0])[0],$letter) == 0){
					$style = '';
					if($letterSet && $letter == $_GET["letter"]){
						$style = 'style = "color : blue"';
					}
					$url = add_query_arg('letter', $letter, get_permalink());
					echo "<span class='linkSpan'><a href = \"$url\" $style> $letter</a></span>&nbsp;&nbsp;\n";
					while($currentTitle < sizeof($entryNames) && strcasecmp(va_only_latin_letters($entryNames[$currentTitle][0])[0],$letter) === 0){
						if(!$letterSet){
							$url = add_query_arg('letter', $letter, get_permalink()) . '#' . $entryNames[$currentTitle][1];
							$estyle = va_get_glossary_link_style($entryNames[$currentTitle][1]);
							$nameList .= "<a style='$estyle' href=\"$url\">" . $entryNames[$currentTitle][0] . '</a><br />';
						}
						$currentTitle++;
					}
					$nameList .= '<br />';
					$currentIndex++;
				}
				else
					echo "$letter&nbsp;\n";
			}
		}
		$nameList .= '</div>';
			
		echo "<br /><br /><br />";
		
		//Aktuelle Einträge
		

		if($letterSet || $tagSet || $getAll){
			if ($getAll){
				$entries = getAllEntries($intern, $lang, $Ue);
			}
			else if($letterSet)
				$entries = getEntriesForLetter($_GET['letter'], $intern, $lang, $Ue);
			else {
				$entries = getEntriesForTag($_GET['tag'], $intern, $lang, $Ue);
			}
			
			foreach ($entries as $e){
				echo "<header class=\"entry-header\">";
				echo "	<h1 class=\"entry-title va-title\">";
				echo '<span class="va-rel-link" id="' . $e[0] . '"></span>';
				$estyle = va_get_glossary_link_style($e[0]);
				echo "<span style='$estyle'>" . $e[1] . '</span>';
				if($intern && $va_current_db_name == 'va_xxx'){
					echo '&nbsp;<a href="' . get_admin_url(1) . '?page=glossar&entry=' . $e[0] . '" target="_BLANK" style="font-size: 50%">(' . $Ue['BEARBEITEN'] . ')</a>';
				}
				if($va_current_db_name != 'va_xxx'){
					$cite_text = va_create_glossary_citation($e[0], $Ue);
					$bibtex = va_create_glossary_bibtex($e[0], $Ue, true);
					echo '&nbsp;<span class="quote" data-plain="' . $cite_text . '" data-bibtex="' . $bibtex. '" style="font-size: 50%; cursor : pointer; color : grey;">(' . $Ue['ZITIEREN'] . ')</span>';
				}
				echo "	</h1>";			
				echo "</header>";
				
				parseSyntax($e[2], true, $intern, $mode) . '<br />';
						
				echo '<div class="entry-content">';
				echo "$e[2] <br />";
				
				echo va_add_glossary_authors($e[4], $e[5]);
				va_add_glossary_tags($e[3]);
				
				echo'</div><br /><br /><br />';
			}
		}
		else {
			echo $nameList;
		}
	}
	
	function va_get_glossary_link_style($id){
		if($id == 33) //Interne Syntax
			return;
		
		global $va_current_db_name;
		global $lang;
		global $admin;
		global $va_mitarbeiter;
		
		if(($admin || $va_mitarbeiter) && $va_current_db_name == 'va_xxx' && $lang == 'D'){
			global $va_xxx;
			$info = $va_xxx->get_row("SELECT Fertig, Erlaeuterung_D REGEXP '(<neu>|<mod>)' FROM Glossar WHERE Id_Eintrag = $id", ARRAY_N);
			if($info[0] == '0'){
				return 'color : red';
				
			}
			if($info[1] == '1'){
				return 'color : blue';
			
			}
		}
		return '';
	}
	
	function va_add_glossary_authors($authors, $translators){
		$res = '';
		if(count($authors) > 0){
			$res .= '<br />';
			$res .= '(auct. ' . implode(' | ', $authors);
			if(count($translators) > 0){
				$res .= ' - trad. ' . implode(' | ', $translators);
			}
			$res .= ')<br />';
		}
		return va_add_abrv($res);
	}
	
	function va_add_glossary_tags($tags){
		if(count($tags) > 0){
			echo '<br />Tags: ';
			foreach ($tags as $tag){
				$url = add_query_arg('tag', $tag[0], get_permalink());
				echo "<a href='$url'>$tag[1]</a> ";
			}
		}
	}
	
	function getAllEntries ($intern, $lang, &$Ue){
		global $vadb;

		$res = $vadb->get_results("
				select Id_Eintrag, Terminus_$lang, Erlaeuterung_$lang
				from glossar
				where" . ($intern? "" : " Intern = '0' and") . " Kategorie='Methodologie'
				order by Terminus_$lang asc", ARRAY_N);
	
		va_add_glossary_meta_information($res, $lang, $Ue);
		
		return $res;
	}
	
	//Einträge mit Anfangsbuchstaben $letter
		function getEntriesForLetter ($letter, $intern, $lang, &$Ue){
			global $vadb;
			
			$res = $vadb->get_results($vadb->prepare("
					select Id_Eintrag, Terminus_$lang, Erlaeuterung_$lang
					from glossar
					where" . ($intern? "" : " Intern = '0' and") . " substring(Terminus_$lang,1,1) = %s and Kategorie='Methodologie'
					order by Terminus_$lang asc", $letter)
					, ARRAY_N);

			va_add_glossary_meta_information($res, $lang, $Ue);

			return $res;
		}
		
		//Einträge mit Tag $tag
		function getEntriesForTag ($id_tag, $intern, $lang, &$Ue){
			global $vadb;
			
			if($id_tag == 0){
				$res = $vadb->get_results($vadb->prepare("
					select Id_Eintrag, Terminus_$lang, Erlaeuterung_$lang
				 	from glossar LEFT JOIN VTBL_Eintrag_Tag USING (Id_Eintrag)
				 	where" . ($intern? "" : " Intern = '0' and") . " Kategorie='Methodologie' and Id_Tag IS NULL and Terminus_$lang != ''
				 	order by Terminus_$lang asc", $id_tag)
				 	, ARRAY_N);
					
				foreach ($res as &$row){
					$row[] = array ();
				}	
			}
			else {
				$res = $vadb->get_results($vadb->prepare("
					select Id_Eintrag, Terminus_$lang, Erlaeuterung_$lang
				 	from glossar JOIN VTBL_Eintrag_Tag USING (Id_Eintrag)
				 	where" . ($intern? "" : " Intern = '0' and") . " Kategorie='Methodologie' and Id_Tag = %d and Terminus_$lang != ''
				 	order by Terminus_$lang asc", $id_tag)
				 	, ARRAY_N);
					
				va_add_glossary_meta_information($res, $lang, $Ue);
			}	
			return $res;
		}
		
		function va_add_glossary_meta_information (&$result, $lang, &$Ue = NULL){
			global $vadb;
			
			foreach ($result as &$row){
				if($row[0] == 61 /*Versionierung*/){
					$row[2] .= va_add_image_gallery();
				}
				
				$ctags = $vadb->get_results("SELECT Id_Tag, Tag FROM Tags JOIN VTBL_Eintrag_Tag USING (Id_Tag) WHERE Id_Eintrag = {$row[0]}", ARRAY_N);
				foreach ($ctags as &$ctag){
					$ctag[1] = va_translate($ctag[1], $Ue);
				}
				$row[] = $ctags;

				if(va_version_newer_than('va_161')){
					$row[] = $vadb->get_col("SELECT CONCAT(Vorname, ' ', Name) FROM Personen JOIN VTBL_Eintrag_Autor USING (Kuerzel) WHERE Id_Eintrag = {$row[0]} AND Aufgabe = 'auct'");
					$row[] = $vadb->get_col("SELECT CONCAT(Vorname, ' ', Name) FROM Personen JOIN VTBL_Eintrag_Autor USING (Kuerzel) WHERE Id_Eintrag = {$row[0]} AND Aufgabe = 'trad' AND Sprache = '$lang'");
				}
			}
		}

		//Titel der Einträge
		function getTitles ($intern, $lang, $tags = false){
			global $vadb;
			if($tags){
				return $vadb->get_results("
					select Terminus_$lang, Id_Eintrag, IF(Tag IS NULL, '(no Tag)', Tag) as Tag2 
				from glossar left join vtbl_eintrag_tag using (Id_Eintrag) left join tags using (Id_Tag) 
				where Terminus_$lang != '' AND Kategorie='Methodologie'" . ($intern? "" : " and Intern = '0'") . " 
				ORDER BY Tag2 ASC, Terminus_$lang ASC", ARRAY_N);
			}
			$titles = $vadb->get_results("select Terminus_$lang, Id_Eintrag from glossar where Terminus_$lang != '' AND Kategorie='Methodologie'" . ($intern? "" : " and Intern = '0'"), ARRAY_N);
			usort($titles, function ($row1, $row2){
				return strcasecmp(va_only_latin_letters($row1[0]), va_only_latin_letters($row2[0]));
			});
			return $titles;
		}
		
		function termino (){
			
			global $vadb;
			global $admin;
			global $va_mitarbeiter;
			global $Ue;
			
			$data = $vadb->get_results('SELECT Begriff_D, Begriff_F, Begriff_I, Begriff_R, Begriff_S FROM Terminologie' . (($admin || $va_mitarbeiter)? '': ' WHERE INTERN = 0') . ' ORDER BY Begriff_D ASC', ARRAY_N);
			
			?>
			<div class="entry-content">
				<div class="table-responsive">
					<table style="width:100%; "  class="easy-table easy-table-default tablesorter  ">
						<tr>
							<th><?php echo $Ue['BEGRIFF_D']; ?></th>
							<th><?php echo $Ue['BEGRIFF_F']; ?></th>
							<th><?php echo $Ue['BEGRIFF_I']; ?></th>
							<th><?php echo $Ue['BEGRIFF_R']; ?></th>
							<th><?php echo $Ue['BEGRIFF_S']; ?></th>
						</tr>
						<?php
						foreach ($data as $d){
							?>
							<tr>
								<td><?php echo $d[0]; ?></td>
								<td><?php echo $d[1]; ?></td>
								<td><?php echo $d[2]; ?></td>
								<td><?php echo $d[3]; ?></td>
								<td><?php echo $d[4]; ?></td>
							</tr>
							<?php
						}
						?>
					</table>
				</div>
			</div>
			<?php
			
		}
		
		function va_add_image_gallery (){
			global $va_xxx;
			global $Ue;
			$res = "\n\n<h3>" . $Ue['UEBERSCHRIFT_GALLERIE'] . '</h3>';
			
			$files = $va_xxx->get_results('SELECT Dateiname, Nummer, Size_Logo, Logo_Left, Logo_Top FROM Versionen JOIN Medien USING (Id_Medium) ORDER BY Nummer ASC', ARRAY_N);
			$size = 45;
			foreach ($files as $i => $file){
				$res .= '<div class="galleryContainer">';
				$res .= '<object type="image/svg+xml" class="galleryLogo" style="width: ' 
					. $file[2] * $size / 100 . '%; left: ' 
					. $file[3] * $size / 100 . '%; top: ' 
					. $file[4] . '%;" data="' . get_site_url(1) . '/wp-content/uploads/VA_logo.svg"></object>';
					$res .= '<img style="width: ' . $size . '%" src="' . $file[0] . '" /><p>' . va_format_version_number(va_get_string_or_empty($files, $i - 1)[1]) . '</p>';
				$res .= '</div>';
			}
			return $res;
		}
		
		function va_get_string_or_empty (&$arr, $key){
			if(array_key_exists($key, $arr))
				return $arr[$key];
			return '';
		}
?>
