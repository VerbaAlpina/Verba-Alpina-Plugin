<?php
function comment_list (){
	
	global $vadb;
	global $lang;
	global $Ue;
	global $admin;
	global $va_mitarbeiter;
	global $va_current_db_name;
	
	?>
	
	
	
	<script type="text/javascript">
	var qtipApis;
	
	jQuery(function () {
		qtipApis = addBiblioQTips(jQuery(".entry-content"));

		jQuery(".quote").each(function (){
			jQuery(this).qtip({
				"show" : "click",
				"hide" : "unfocus",
				"content" : {
					"text" : "<div>" + jQuery(this).prop("title") 
					+ "</div><br /><input class='copyButton' style='display: block; margin: auto;' type='button' data-content='" 
					+ jQuery(this).prop("title") + "' value='<?php echo $Ue['KOPIEREN']; ?>' />"
				}
			});
		});

		addCopyButtonSupport();

		jQuery("#seachComments").keyup(function (event){
			if(event.key == "Enter"){
				changeContent(jQuery(this).val());
			}
		}).change(function (){
			changeContent(jQuery(this).val());
		}).val("");

	});

	function changeContent (val){
		for (let i = 0; i < qtipApis.length; i++){
			qtipApis[i].destroy(true);
		}
		removeMarkers(jQuery(".va-entry"));

		if(val.length > 0){
			filterContent(val);
			//TODO qtips
		}
		else {
			jQuery(".va-entry").toggle(true);
		}
		qtipApis = addBiblioQTips(jQuery(".entry-content"));
	}
		

	function filterContent(searchString){
		for (let i = 0; i < qtipApis.length; i++){
			qtipApis[i].destroy(true);
		}
		
		jQuery(".va-title .title-string").each(function (element){
			var entryDiv = jQuery(this).closest(".va-entry");
			var title = jQuery(this).text();
			var contentDiv = entryDiv.find(".va-content");
			var content = contentDiv.html();
			
			var indexesTitle = getOccurances(title, searchString);
			var indexesContent = getOccurances(content, searchString);
			
			if(indexesTitle.length == 0 && indexesContent.length == 0){
				entryDiv.toggle(false);
			}
			else {
				entryDiv.toggle(true);
				jQuery(this).html(markIndexes(title, indexesTitle, searchString));
				contentDiv.html(markIndexes(content, indexesContent, searchString));
			}
		});
	}
	</script>
	
	<span  style="float: right;"><input type="text" id="seachComments" placeholder="<?php _e('Search');?>"></input></span>
	
	<?php
	
	echo '<div class="entry-content">';
	
	if(va_version_newer_than('va_171'))
		$comments = $vadb->get_results("SELECT Comment, Id, Language FROM im_comments WHERE substr(Id,1,1) IN ('B','L','C') and substr(Language,1,1) = '$lang' and not Internal", ARRAY_A);
	else
		$comments = $vadb->get_results("SELECT Comment, Id, Language FROM im_comments WHERE substr(Id,1,1) IN ('B','L','C') and substr(Language,1,1) = '$lang'", ARRAY_A);
	
	foreach ($comments as $index => $comment){
		$letter = substr($comment['Id'], 0, 1);
		$key = substr($comment['Id'], 1);

		switch ($letter){
			case 'B':
				$comments[$index]['Title'] = '<em>' . $vadb->get_var($vadb->prepare('SELECT Orth FROM Basistypen WHERE Id_Basistyp = %d', $key)) . '</em> - ' . $Ue['BASISTYP'];
				break;
		
			case 'L':
				$comments[$index]['Title'] = '<em>' . $vadb->get_var($vadb->prepare('SELECT Orth FROM morph_Typen WHERE Id_morph_Typ = %d', $key)) . '</em> - ' . $Ue['MORPH_TYP'];
				break;
		
			case 'C':
				$comments[$index]['Title'] = $vadb->get_var($vadb->prepare("SELECT IF(Name_$lang != '', Name_$lang, Beschreibung_$lang) FROM Konzepte WHERE Id_Konzept = %d", $key)) . ' - ' . $Ue['KONZEPT'];
				break;
		}
	}
	
	uasort($comments, function ($e1, $e2){
		$str1 = preg_replace('/[^a-zA-Z]/', '', str_replace('<em>', '', remove_accents($e1['Title'])));
		$str2 = preg_replace('/[^a-zA-Z]/', '', str_replace('<em>', '', remove_accents($e2['Title'])));
		return strcasecmp($str1, $str2);
	});
	
	foreach ($comments as $comment){
		
		if(va_version_newer_than('va_171')){
			$auth = $vadb->get_col($vadb->prepare("
				SELECT CONCAT(Vorname, ' ', Name)
				FROM VTBL_Kommentar_Autor JOIN Personen USING (Kuerzel)
				WHERE Id_Kommentar = %s AND Aufgabe = 'auct'", $comment['Id']));
			$trad = $vadb->get_col($vadb->prepare("
				SELECT CONCAT(Vorname, ' ', Name) 
				FROM VTBL_Kommentar_Autor JOIN Personen USING (Kuerzel)
				WHERE Id_Kommentar = %s AND Aufgabe = 'trad' AND Sprache = %s", $comment['Id'], $lang));
		}
		
		$app = '';
		if($admin || $va_mitarbeiter){
			$app .= '<span style="font-size: 70%">   [[Kommentar:' . $comment['Id'] . ']]</span>';
		}
		if($va_current_db_name != 'va_xxx'){
			$citation = va_create_comment_citation($comment['Id'], $Ue);
			if($citation)
				$app .= '&nbsp;<span class="quote" title="' . $citation . '" style="font-size: 50%; cursor : pointer; color : grey; font-weight: normal;">(' . $Ue['ZITIEREN'] . ')</span>';
		}
		$app .= '&nbsp;<a style="font-size: 50%; cursor : pointer; color : grey; font-weight: normal; text-decoration: none;" target="_BLANK" href="' . va_get_map_link($comment['Id']) . '">(Auf Karte visualisieren)</a>';
		
		if(($admin || $va_mitarbeiter) && $va_current_db_name == 'va_xxx'){
			$app .= '&nbsp;<a style="font-size: 50%; cursor : pointer; color : grey; font-weight: normal; text-decoration: none;" target="_BLANK" href="' . get_admin_url(1) . 'admin.php?page=edit_comments&comment_id=' . $comment['Id'] . '">(' . $Ue['BEARBEITEN'] . ')</a>';
		}
		
		$pre = '<span class="va-rel-link" id="' . $comment['Id'] . '"></span>';
		
		echo '<div class="va-entry"><header class="entry-header" style="margin-bottom: 1rem; margin-top: 5rem;"><h1 class="va-title">' . $pre . '<span class="title-string">' . $comment['Title'] . '</span>' . $app . '</h1></header>';
		
		parseSyntax($comment['Comment'], true, $admin || $va_mitarbeiter);
		echo '<div class="va-content">' . $comment['Comment'];
		if(va_version_newer_than('va_171')){
			echo '<div>' . va_add_glossary_authors($auth, $trad) . '</div>';
		}
		global $va_current_db_name;
		if(($va_mitarbeiter || $admin) && $va_current_db_name === 'va_xxx'){
			echo ' <b>' . $vadb->get_var($vadb->prepare('SELECT Approved FROM im_comments WHERE Id = %s AND Language = %s', $comment['Id'], 'de')) . '</b>';	
		}
		echo '</div></div>';
	}
	echo '</div>';
}
?>