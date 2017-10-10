<?php 

function va_edit_comments_page (){
	global $va_xxx;
	
	$authors = $va_xxx->get_results("SELECT DISTINCT Kuerzel, Vorname, Name FROM Personen LEFT JOIN Stellen USING(Kuerzel) WHERE Art is null or Art != 'prak'", ARRAY_A);
	
	?>
	<div id="topStuff" style="padding: 0">
		<h1>Texteingabe Kommentare</h1>
		<br />
		<select id="commSelection" style="width: 400px">
			<?php echo va_filter_comments_for_editing($va_xxx, 'None');?>
		</select>
		<input type="button" class="button button-primary" id="commSaveButton" value="In Datenbank übertragen" />
		<input type="button" class="button button-primary" id="commNewButton" value="Neuen Kommentar anlegen" />
		
		Filter: 
		<select id="commFilter">
			<option value="None">
				keiner
			</option>
			<option value="MISSING_fr">
				fehlende Übersetzungen F
			</option>
			<option value="MISSING_it">
				fehlende Übersetzungen I
			</option>
			<option value="MISSING_sl">
				fehlende Übersetzungen S
			</option>
			<option value="MISSING_rg">
				fehlende Übersetzungen R
			</option>
			<option value="MISSING_ld">
				fehlende Übersetzungen L
			</option>
			<option value="MISSING_en">
				fehlende Übersetzungen E
			</option>
		</select>
	</div>
	
	<textarea id="commDescription" style="width : 98%;"></textarea>
	
	<br />
	Autoren:
	<select id="commAuthorList" multiple="multiple" style="width: 300pt">
		<?php 
		foreach ($authors as $author){
			echo "<option value='{$author['Kuerzel']}'>" . $author['Vorname'] . ' ' . $author['Name'] . '</option>';
		}
		?>
	</select>
	
	<input type="checkbox" id="commT" /> T
	<input type="checkbox" id="commS" /> S
	<input type="checkbox" id="commInternal" /> Intern
	
	<br />
	<br />
	
	Übersetzung:
	<div style="display:inline" id="divT">
		<select name="translation" id="commTranslationSelect" style="display:inline">
			<option value="None"><?php echo DEFAULT_SELECT; ?></option>
			<option value="it">Italienisch</option>
			<option value="fr">Französisch</option>
			<option value="rg">Rätoromanisch</option>
			<option value="sl">Slowenisch</option>
			<option value="en">Englisch</option>
			<option value="ld">Ladinisch</option>
		</select>
	</div>
	
	<div id="commTranslationArea">
		<textarea id="commTranslation" style="width:98%; display:inline"></textarea>
		
		<span>Übersetzer:</span>
		<select id="commTranslatorList" multiple="multiple" style="width: 300pt">
			<?php 
			foreach ($authors as $author){
				echo "<option value='{$author['Kuerzel']}'>" . $author['Vorname'] . ' ' . $author['Name'] . '</option>';
			}
			?>
		</select>
	</div>	
	
	<div id="newCommentPopup" style="display: none; position: relative;">
		<input type="radio" name="commentType" value="C" class="newCommentTypeOption" /> Konzept
		<br />
		<input type="radio" name="commentType" value="L" class="newCommentTypeOption" /> morphologischer Typ
		<br />
		<input type="radio" name="commentType" value="B" class="newCommentTypeOption" /> Basistyp
		<br />
		<br />
		<select id="newCommentList" class="noChosen" style="display: none; width: 700px;"></select>
		<input type="button" value="Anlegen" style="position: absolute; bottom: 10px; right: 10px; display: none;" id="createCommentButton" class="button button-primary" />
	</div>
	
	<script type="text/javascript">
	var currentComment = "None";
	var currentTranslation = "None";
	
	var changeD = false;
	var changeT = false;
	
	jQuery(function (){
		adjustToScreen();

		jQuery("select:not(.im_enum_select):not(.noChosen)").chosen({"allow_single_deselect" : true});

		jQuery("#commFilter").val("None").trigger("chosen:updated");
		jQuery("#commTranslationSelect").val("None").trigger("chosen:updated");
		jQuery("#commTranslation").val("").trigger("chosen:updated");
		jQuery("#commSelection").val("None");

		jQuery("#commTranslationArea").toggle(false);

		jQuery("#commFilter").change(filterComments);
		jQuery("#commSelection").change(commentSelectionChanged);
		jQuery("#commDescription, #commAuthorList, #commInternal, #commT, #commS").change(function (){
			changeD = true;
		});
		jQuery("#commTranslation, #commTranslatorList").change(function (){
			changeT = true;
		});
		jQuery("#commSaveButton").click(saveComment);
		jQuery("#commTranslationSelect").change(switchTranslation);

		jQuery("#commNewButton").click(newComment);
		jQuery("#createCommentButton").click(createComment);
		jQuery(".newCommentTypeOption").click(selectCommentType);
		
		jQuery(window).on('beforeunload', function(){
			if(changeD || changeD)
				return 'Die Änderungen wurden noch nicht in die Datenbank übertragen!';
		 });
		  
		jQuery(window).on('unload', function(){
	         removeLock(currentComment);
		});

		window.onpopstate = function (event){
			var newComment;
			if(event.state == null || event.state.comment === undefined)
				newComment = "None";
			else
				newComment = event.state.comment;
			
			jQuery("#commSelection").val(newComment).trigger("chosen:updated");
			commentSelectionChanged(false);
		}

		setCommentFields();
		
		<?php 
		if(isset($_REQUEST['comment_id'])){
			?>
			jQuery("#commSelection").val("<?php echo $_REQUEST['comment_id'];?>").trigger("chosen:updated");
			commentSelectionChanged();
			<?php
		}
		?>
		
		
	});
	
	function adjustToScreen (){
		if(jQuery.browser.chrome) //Chrome erlaubt das manuelle Ändern der Textfeldgröße nicht mehr, nachdem man einmal die Größe per Skript geändert hat...
			return;
		var height = document.getElementById('wpfooter').offsetTop;
		var sheight = document.getElementById('topStuff').offsetHeight;
		var theight = document.getElementById('divT').offsetHeight;
		
		var h = (height - sheight - theight) / 2;
		document.getElementById('commDescription').style.height =  h + "px";
		document.getElementById('commTranslation').style.height =  h + "px";
	}

	function commentSelectionChanged (setHistory){
		if(setHistory === undefined)
			setHistory = true;
		
		if(currentComment != "None" && (changeD || changeT)){
			var con = confirm("Die Änderungen am aktuellen Eintrag wurden noch nicht gespeichert! Trotzdem wechseln?");

			if(!con){
				jQuery("#commSelection").val(currentComment).trigger("chosen:updated");
				return false;
			}
		}

		changeD = false;
		changeT = false;

		var newComment = jQuery("#commSelection").val();

		if(newComment == "None"){
			if(setHistory)
				history.pushState({"comment" : "None"}, "", "?page=edit_comments");
			setCommentFields();
			setTranslationFields();
			removeLock(currentComment);
			currentComment = "None";
		}
		else{
			jQuery.post(ajaxurl, {
				"action" : "va",
				"namespace" : "edit_comments",
				"query" : "getEntry",
				"id" : newComment
				}, function (response) {
					if(response == "Locked"){
						alert("Dieser Eintrag wird gerade von einem anderen Benutzer bearbeitet!");
						jQuery("#commSelection").val(currentComment).trigger("chosen:updated");
						return false;
					}
					else {
						if(setHistory)
							history.pushState({"comment" : newComment}, "", "?page=edit_comments&comment_id=" + newComment);
						var data = JSON.parse(response);

						if(currentComment != "None"){
							removeLock(currentComment);
						}

						currentComment = newComment;
						setCommentFields(data["Comment"], (data["Authors"] == null? []: data["Authors"].split(",")), data["Ready"], data["Internal"]);
						setTranslationFields();
						jQuery("#commTranslationSelect").val("None").trigger("chosen:updated");
						jQuery("#commTranslationArea").toggle(false);
					}
			});
		}
		
		return true;
	}

	function removeLock (id){
		jQuery.post(ajaxurl, {
			"action" : "va",
			"namespace" : "edit_comments",
			"query" : "removeLock",
			"id" : id
			}, null);
	}

	function setCommentFields(content, authors, ready, internal){
		if(content === undefined)
			content = "";

		if(authors === undefined)
			authors = [];

		if(ready === undefined)
			ready = "";

		if(internal === undefined)
			internal = "0";
		
		jQuery("#commDescription").val(content);
		jQuery("#commAuthorList").val(authors).trigger("chosen:updated");
		jQuery("#commInternal").prop("checked", internal == "1");

		jQuery("#commT").prop("checked", ready.indexOf("T") !== -1);
		jQuery("#commS").prop("checked", ready.indexOf("S") !== -1);
	}

	function filterComments (){
		jQuery("#commSelection").val("None");
		if(!commentSelectionChanged()){
			return;
		}
		
		jQuery.post(ajaxurl, {
			"action" : "va",
			"namespace" : "edit_comments",
			"query" : "updateList",
			"filter" : jQuery("#commFilter").val()
			}, function (response) {
				jQuery("#commSelection").html(response).trigger("chosen:updated");
		});
	}

	function saveComment (){
		if(currentComment == "None")
			return;

		var data = {
			"action" : "va",
			"namespace" : "edit_comments",
			"query" : "saveComment",
			"id" : currentComment,
			"content" : jQuery("#commDescription").val(),
			"authors" : jQuery("#commAuthorList").val(),
			"internal" : jQuery("#commInternal").is(":checked")? "1" : "0",
			"ready" : (jQuery("#commT").is(":checked")? "T" : "") + (jQuery("#commS").is(":checked")? "S" : "")
		};

		if(currentTranslation != "None"){
			data["lang"] = currentTranslation;
			data["translation"] = jQuery("#commTranslation").val();
			data["translators"] = jQuery("#commTranslatorList").val();
		}
		
		jQuery.post(ajaxurl, data, function (response) {
				if(response == "success"){
					alert("Kommentar gespeichert");
					changeD = false;
					changeT = false;
				}
				else {
					alert("Fehler: " + response);
				}
		});
	}

	function switchTranslation (){
		if(changeT && currentTranslation != "None"){
			if(!confirm("Die Änderungen am aktuellen Eintrag wurden noch nicht gespeichert! Trotzdem wechseln?")){
				jQuery("#commTranslationSelect").val(currentTranslation).trigger("chosen:updated");
				return;
			}
		}

		changeT = false;
		currentTranslation = jQuery("#commTranslationSelect").val();

		
		if(currentTranslation == "None"){
			jQuery("#commTranslationArea").toggle(false);
			setTranslationFields();
		}
		else {
			jQuery("#commTranslationArea").toggle(true);
			
			jQuery.post(ajaxurl, {
				"action" : "va",
				"namespace" : "edit_comments",
				"query" : "getTranslation",
				"id" : currentComment,
				"lang" : currentTranslation
				}, function (response) {

				var data = JSON.parse(response);
				setTranslationFields(data["Translation"], (data["Translators"] == null? []: data["Translators"].split(",")));
			});
		}

		
	}

	function setTranslationFields(content, translators){
		if(content === undefined)
			content = "";

		if(translators === undefined)
			translators = [];
		
		jQuery("#commTranslation").val(content);
		jQuery("#commTranslatorList").val(translators).trigger("chosen:updated");
	}

	function newComment (){
		if(currentComment != "None" && (changeD || changeT)){
			var con = confirm("Die Änderungen am aktuellen Eintrag wurden noch nicht gespeichert! Trotzdem wechseln?");

			if(!con){
				return;
			}
		}
		
		jQuery("#newCommentPopup").dialog({
			"title" : "Neuen Kommentar anlegen",
			"width" : 800,
			"height" : 600
		});

		jQuery("#newCommentPopup input[name=commentType]").prop("checked", false);
	}

	function selectCommentType (){
		var type = jQuery(this).val();
		jQuery.post(ajaxurl, {
			"action" : "va",
			"namespace" : "edit_comments",
			"query" : "getNoCommentsList",
			"type" : type
			}, function (response) {
			jQuery("#newCommentList").chosen("destroy").html(response).toggle(true).chosen({"allow_single_deselect" : true});
			jQuery("#createCommentButton").toggle(true);
		});
	}

	function createComment (){
		var newId = jQuery("#newCommentList").val();
		var name = jQuery("#newCommentList option:selected").text();

		switch (newId[0]){
			case "C":
				name = "Konzept " + name;
				break;
			case "L":
				name = "Morphologischer Typ " + name;
			 	break;
			case "B":
				name = "Basistyp " + name;
				break;
		}

		changeD = false;
		changeT = false;

		jQuery("#commSelection").append("<option value='" + newId + "'>" + name + "</option>");
		jQuery("#commSelection").val(newId).trigger("chosen:updated");
		currentComment = newId;
		setCommentFields();

		jQuery("#commTranslationSelect").val("None").trigger("chosen:updated");
		currentTranslation = "None"
		jQuery("#commTranslationArea").toggle(false);
		setTranslationFields();
		
		jQuery("#newCommentPopup").dialog("close");
	}
	</script>
	
	<?php
}

function va_filter_comments_for_editing (&$db, $filter){
	
	$res = '<option value="None">' . DEFAULT_SELECT . '</option>';
	
	if(strpos($filter, 'MISSING_') === 0){
		$sql_filter = " AND (Approved = 'TS' OR Approved = 'T') AND NOT EXISTS(SELECT * FROM im_comments i2 WHERE i1.Id = i2.Id AND i2.Language = '" . substr($filter, 8, 2) . "')";
	}
	else {
		$sql_filter = '';
	}
	
	$comments = $db->get_results("
		SELECT i1.Id, CONCAT(getCategoryName(SUBSTR(i1.Id, 1, 1), 'D'), ' ', getEntryName(i1.Id, 'D')) AS Name
		FROM im_comments i1
		WHERE i1.Language = 'De' AND SUBSTR(i1.Id, 1, 1) IN ('B','L','C')" . $sql_filter, ARRAY_A);
	
	foreach($comments as $comment){
		$res .= '<option value="' . $comment['Id'] . '">' . $comment['Name'] . '</option>';	
	}
	return $res;
}

function va_get_comment_edit_data(&$db, $id){
	$locked =  $db->get_var($db->prepare("SELECT NOW() - Locked FROM im_comments WHERE Id = %s AND Language = 'de'", $id));
	
	if($locked != NULL && $locked < 3600){
		return false;
	}
	
	$db->query($db->prepare("UPDATE im_comments SET Locked = NOW() WHERE Id = %s AND Language = 'de'", $id));

	return $db->get_row($db->prepare("
		SELECT Comment, GROUP_CONCAT(Kuerzel) AS Authors, Approved AS Ready, Internal
		FROM im_comments LEFT JOIN VTBL_Kommentar_Autor ON Id_Kommentar = Id AND Aufgabe = 'auct'
		WHERE Id = %s AND Language = 'de'
		GROUP BY Id", $id), ARRAY_A);
}

function va_remove_comment_lock (&$db, $id){
	$db->query($db->prepare("UPDATE im_comments SET Locked = NULL WHERE Id = %s AND Language = 'de'", $id));
}

function va_save_comment(&$db, $id, $content, $authors, $internal, $ready){
	$comment_exists = $db->get_var($db->prepare('SELECT Id FROM im_comments WHERE Id = %s', $id));
	
	if($comment_exists == NULL){
		$db->insert('im_comments', array (
			'Id' => $id,
			'Source' => 'VA',
			'Author' => wp_get_current_user()->user_login,
			'Language' => 'de',
			'Comment' => $content,
			'Approved' => $ready,
			'Internal' => $internal
		), array('%s', '%s', '%s', '%s', '%s', '%s', '%d'));
	}
	else {
		$db->update('im_comments', array(
				'Comment' => stripslashes($content),
				'Approved' => $ready,
				'Internal' => $internal
		), array (
				'Id' => $id,
				'Language' => 'de'
		), array (
				'%s', '%s', '%d'
		), array (
				'%s', '%s'
		));
	}

	
	$db->delete('VTBL_Kommentar_Autor', array('Id_Kommentar' => $id, 'Sprache' => 'D'), array ('%s', '%s'));
	
	if($authors != ''){
		foreach ($authors as $author){
			$db->insert('VTBL_Kommentar_Autor', array('Id_Kommentar' => $id, 'Kuerzel' => $author, 'Aufgabe' => 'auct', 'Sprache' => 'D'), array('%s','%s','%s','%s'));
		}
	}
	
	return true;
}

function va_save_comment_translation (&$db, $id, $lang, $translation, $translators){
	$db->delete('VTBL_Kommentar_Autor', array('Id_Kommentar' => $id, 'Sprache' => substr($lang, 0, 1)), array ('%s', '%s'));
	
	if($translation == ''){
		$db->delete('im_comments', array('Id' => $id, 'Language' => $lang), array ('%s', '%s'));
	}
	else {
		$exists = $db->get_var($db->prepare('SELECT Id FROM im_comments WHERE Id = %s AND Language = %s', $id, $lang));
		if($exists){
			$db->update('im_comments', array('Comment' => $translation), array('Id' => $id, 'Language' => $lang), array ('%s'), array ('%s', '%s'));
		}
		else {
			$db->insert('im_comments', array('Id' => $id, 'Comment' => $translation, 'Language' => $lang, 'Source' => 'VA'));
		}
		if($translators != ''){
			foreach ($translators as $translator){
				error_log($translator);
				$db->insert('VTBL_Kommentar_Autor', array('Id_Kommentar' => $id, 'Kuerzel' => $translator, 'Aufgabe' => 'trad', 'Sprache' => substr($lang, 0, 1)), array('%s','%s','%s','%s'));
			}
		}
	}
	return true;
}

function va_get_comment_translation_data (&$db, $id, $lang){
	return $db->get_row($db->prepare('
		SELECT Comment AS Translation, GROUP_CONCAT(Kuerzel) AS Translators 
		FROM im_comments LEFT JOIN VTBL_Kommentar_Autor ON Id = Id_Kommentar AND SUBSTR(Language, 1, 1) = Sprache
		WHERE Id = %s AND Language = %s', $id, $lang), ARRAY_A);
}

function va_get_missing_comments_options (&$db, $type){
	$list = array();
	switch ($type){
		case 'C':
			$list = $db->get_results("SELECT Id_Konzept AS Id, IF(Name_D = '' OR Name_D IS NULL OR Name_D = Beschreibung_D, Beschreibung_D, CONCAT(Name_D, ' (', Beschreibung_D, ')')) AS Wert
				FROM Konzepte LEFT JOIN im_comments ON CONCAT('C', Id_Konzept) = Id
				WHERE Relevanz AND Comment IS NULL
				ORDER BY Wert ASC", ARRAY_A);
			break;
			
		case 'L':
			$list = $db->get_results("SELECT Id_morph_Typ AS Id, Orth, Sprache, Wortart, Genus, Affix
			FROM morph_Typen LEFT JOIN im_comments ON CONCAT('L', Id_morph_Typ) = Id
			WHERE Quelle = 'VA' AND Comment IS NULL", ARRAY_A);
			
			$list = array_map(function ($e){
				return array ('Id' => $e['Id'], 'Wert' => va_format_lex_type($e['Orth'], $e['Sprache'], $e['Wortart'], $e['Genus'], $e['Affix']));
			}, $list);
			uasort($list, function ($e1, $e2){
				return strcasecmp($e1['Wert'], $e2['Wert']);
			});
			
			break;
				
		case 'B':
			$list = $db->get_results("SELECT Id_Basistyp AS Id, Orth AS Wert
		FROM Basistypen LEFT JOIN im_comments ON CONCAT('B', Id_Basistyp) = Id
		WHERE Comment IS NULL
		ORDER BY Wert ASC", ARRAY_A);
			break;
	}
	$res = '';
	
	foreach ($list as $entry){
		$res .= '<option value="' . $type . $entry['Id'] . '">' . $entry['Wert'] . '</option>';
	}
	return $res;
}
?>