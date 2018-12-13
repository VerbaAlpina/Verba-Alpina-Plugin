<?php

 //Globale Variablen
 define('DEFAULT_SELECT', '--- Auswahl ---');

//Glossar

function glossar (){
	
	global $va_xxx;

	echo im_table_entry_box ('addGlossaryEntry', new IM_Row_Information('Glossar', array (
			new IM_Field_Information('Terminus_D', 'V', true),
			new IM_Field_Information('Kategorie', 'E', false),
			new IM_Field_Information('Intern', 'B', false)
	)));

	if (isset($_GET['entry'])){
		$curr_entry = $_GET['entry'];
	}
	else {
		$curr_entry = 0;
	}
	
	$selectionTags = $va_xxx->get_col($va_xxx->prepare('SELECT Id_Tag FROM VTBL_Eintrag_Tag WHERE Id_Eintrag = %d', $curr_entry));
	$selectionAuthors = $va_xxx->get_col($va_xxx->prepare("SELECT Kuerzel FROM VTBL_Eintrag_Autor WHERE Id_Eintrag = %d AND Aufgabe = 'auct'", $curr_entry));
	$selectionReady = $va_xxx->get_var($va_xxx->prepare("SELECT Fertig FROM Glossar WHERE Id_Eintrag = %d", $curr_entry));
	if(!$selectionReady){
		$selectionReady = 0; //For no selection
	}
	$selectionInternal = $va_xxx->get_var($va_xxx->prepare("SELECT Intern FROM Glossar WHERE Id_Eintrag = %d", $curr_entry));
	if(!$selectionInternal){
		$selectionInternal = 0; //For no selection
	}
	
	?>
	
	<script type='text/javascript'>
	
	var aenderungD = false;
	var aenderungUe = false;
	var currEntry = 0;
	var currTransl = "<?php echo DEFAULT_SELECT; ?>";
	var glossaryUrl = "<?php echo va_get_glossary_link(); ?>";
	
	jQuery(document).ready(function (){

		jQuery("select:not(.im_enum_select)").chosen({"allow_single_deselect" : true});
		
		History.Adapter.bind(window,'statechange',function(){
			changeEntry(History.getState()["data"]["entry"]);
		});
		
		jQuery("#tagList").val([<?php echo implode(',', $selectionTags) ?>]).trigger("chosen:updated");
		jQuery("#authorList").val([<?php echo implode(',', va_surround($selectionAuthors, '"')) ?>]).trigger("chosen:updated");
		jQuery("#ready").prop("checked", <?php echo $selectionReady;?>);
		jQuery("#internal").prop("checked", <?php echo $selectionInternal;?>);
		
		jQuery("#tagList").change(function (){
			aenderungD = true;
		});
		jQuery("#authorList").change(function (){
			aenderungD = true;
		});
		jQuery("#ready").change(function (){
			aenderungD = true;
		});
		jQuery("#internal").change(function (){
			aenderungD = true;
		});
		jQuery("#translatorList").change(function (){
			aenderungUe = true;
		});
		jQuery("#correctorTList").change(function (){
			aenderungUe = true;
		});
		
		jQuery("#translationL").val("<?php echo DEFAULT_SELECT; ?>");
		jQuery("#translationL").trigger("chosen:updated");
		jQuery("#translationList").chosen();
		hideTranslation();
		adjustToScreen();
		currEntry = "<?php echo $curr_entry;?>";
		lockG(currEntry, true, function (response){
			if(response != "success"){
				locked();
			}
		});
	
		jQuery("#entryFilter").change(function (){
			jQuery("#entryL").val(0).trigger("chosen:updated");
			entryLChanged();
			updateSelect();
		});
		updateSelect(function (){
			jQuery("#entryL").val("<?php echo $curr_entry;?>").trigger("chosen:updated");
		});
	});
	
	
	jQuery(window).on('beforeunload', function(){
		if(aenderungD || aenderungUe)
			return 'Die Änderungen wurden noch nicht in die Datenbank übertragen!';
		//return '';
	 });
	  
	jQuery(window).on('unload', function(){
         lockG("", false, null, false);
	});
	
	//jQuery(window).resize(adjustToScreen);
	
	function updateSelect (callback){
		jQuery.post(ajaxurl, {
			"action" : "va",
			"namespace" : "edit_glossary",
			"query" : "updateList",
			"filter" : jQuery("#entryFilter").val()
			}, function (response) {
				jQuery("#entryL").html(response).trigger("chosen:updated");
				if(callback)
					callback();
		});
	}
	
	function changeEntry (id){
		if(id == undefined)
			id = 0;
		
		jQuery("#description").val("");
		jQuery("#tr_description").val("");
		jQuery("#tr_terminus").val("");
		jQuery("#translatorList").val([]).trigger("chosen:updated");
		jQuery("#correctorTList").val([]).trigger("chosen:updated");
		jQuery("#tagList").val([]).trigger("chosen:updated");
		jQuery("#authorList").val([]).trigger("chosen:updated");
		jQuery("#ready").prop("checked", false);
		jQuery("#internal").prop("checked", false);
		
		lockG(currEntry, false, function (response){
			if(response == "success"){
				lockG(id, true, function(response){
					if(response == "success"){
						currEntry = id;
						aenderungD = false;
						aenderungUe = false;
						jQuery('#entryL').val(id).trigger("chosen:updated");
						if(id == 0){
							jQuery("#wp-admin-bar-show_glossary_entry a").prop("href", glossaryUrl);
							document.title = "Glossar";
							jQuery("#translationL").val("<?php echo DEFAULT_SELECT; ?>").trigger("chosen:updated");
							hideTranslation();
						}
						else {
							document.title = "Glossar | " + jQuery("#entryL option:selected").text();
							var data = {'action' : 'va',
										'namespace' : 'edit_glossary',
										'query' : 'changeEntry',
										'id' : id
							};
							var l = jQuery('#translationL').val();
							if(l != "<?php echo DEFAULT_SELECT; ?>"){
								data['language'] = l;
							}
							
							jQuery.post(ajaxurl, data, function (response) {
								var t = JSON.parse(response);
								jQuery("#description").val(t.erlaeuterung_d);
								jQuery("#tr_description").val(t.erlaeuterung);
								jQuery("#tr_terminus").val(t.terminus);
								jQuery("#authorList").val(t.autoren).trigger("chosen:updated");
								jQuery("#tagList").val(t.tags).trigger("chosen:updated");
								jQuery("#ready").prop("checked", t.Fertig * 1);
								jQuery("#internal").prop("checked", t.Intern * 1);
								jQuery("#translatorList").val(t.uebersetzer).trigger("chosen:updated");
								jQuery("#correctorTList").val(t.korrekturleser).trigger("chosen:updated");
								jQuery("#wp-admin-bar-show_glossary_entry a").prop("href", t.url);
							});
						}
					}
					else {
						locked();
					}
				});
			}
			else {
				alert("NN" + response);
			}
		});
	}
	
	function locked (){
		alert("Dieser Eintrag wird gerade von einem anderen Benutzer bearbeitet!");
		jQuery('#entryL').val(0).trigger("chosen:updated");
		jQuery('#description').val("");
		hideTranslation();
		currEntry = 0;
		jQuery("#wp-admin-bar-show_glossary_entry a").prop("href", glossaryUrl);
		History.pushState({}, '', '?page=glossar');
		
		aenderungD = false;
		aenderungUe = false;
	}

	function updateEntryD (){
		if(currEntry == 0){
			alert("Kein Eintrag gewählt!");
			return;
		}
		
		var data = {'action' : 'va',
					'namespace' : 'edit_glossary',
					'query' : 'updateEntry',
					'id' : currEntry,
					'content' : jQuery("#description").val(),
					'tags' : jQuery("#tagList").val(),
					'authors' : jQuery("#authorList").val(),
					'ready' : jQuery("#ready").prop("checked")? "1" : "0",
					'internal' : jQuery("#internal").prop("checked")? "1" : "0"
		};
		
		var l = jQuery("#translationL").val();
		if(l != "<?php echo DEFAULT_SELECT; ?>"){
			data['language'] = l;
			data['terminus'] = document.getElementById("tr_terminus").value;
			data['erlaeuterung'] = document.getElementById("tr_description").value;
			data['translators'] = jQuery("#translatorList").val();
			data['correctors'] = jQuery("#correctorTList").val();
		}
		
		jQuery.post(ajaxurl, data, function (response) {
			if (response === '1') {
				aenderungD = false;
				aenderungUe = false;
				alert('Eintrag geschrieben!');
			}
			else {
				alert(response);
			}
		});
	}
	
	function newEntryD(){
		if((aenderungD || aenderungUe) && !confirm("Die Änderungen am aktuellen Eintrag wurden noch nicht gespeichert! Fortsetzen?")){
			return;
		}
		
		showTableEntryDialog("addGlossaryEntry", "reload");
	}
	
	function changeTextField (uebers){
		if(currEntry != 0){
			if(uebers)
			{
				aenderungUe = true
			}
			else {
				aenderungD = true;
			}
		}
	}
	
	function lockG (entry, add, callback, async){
		if(async == undefined)
			async = true;
		
		if(entry == "0"){
			if(callback != null)
				callback("success");
			return;
		}
		
		if(add){
			var data = {'action' : 'va',
						'namespace' : 'util',
						'query' : 'addLock',
						'table' : 'Glossar',
						'value' : entry,
			};
		}
		else {
			var data = {'action' : 'va',
						'namespace' : 'util',
						'query' : 'removeAllLocks',
						'table' : 'Glossar'
			};
		}
		if(async)
			jQuery.post(ajaxurl, data, callback);
		else {
			jQuery.ajax({
				type: "POST",
				data: data,
				succes: callback,
				url: ajaxurl,
				async: false
			});
		}
	}
	
	function adjustToScreen (){
		if(jQuery.browser.chrome) //Chrome erlaubt das manuelle Ändern der Textfeldgröße nicht mehr, nachdem man einmal die Größe per Skript geändert hat...
			return;
		var height = document.getElementById('wpfooter').offsetTop;
		var sheight = document.getElementById('topStuff').offsetHeight;
		var theight = document.getElementById('divT').offsetHeight;
		
		var h = (height - sheight - theight) / 2;
		document.getElementById('description').style.height =  h + "px";
		document.getElementById('tr_description').style.height =  h + "px";
	}
	
	function changeLanguage (newLang){
		
		if(aenderungUe && !confirm("Die Änderungen am aktuellen Eintrag wurden noch nicht gespeichert! Trotzdem wechseln?")){
			jQuery("#translationL").val(currTransl);
			jQuery("#translationL").trigger("chosen:updated");
			return;
		}

		currTransl = newLang;
		
		var tr_d = jQuery('#tr_description');
		var tr_t = jQuery('#tr_terminus');
		var divt = jQuery('#it');
		
		if(newLang == "<?php echo DEFAULT_SELECT; ?>"){
			hideTranslation();
			aenderungUe = false;
		}
		else {
			if(currEntry == "<?php echo DEFAULT_SELECT; ?>"){
				jQuery("#translationL").val("<?php echo DEFAULT_SELECT; ?>");
				jQuery("#translationL").trigger("chosen:updated");
			}
			else {
				var data = {'action' : 'va',
							'namespace' : 'edit_glossary',
							'query' : 'getTranslation',
							'id' : currEntry,
							'language' : newLang,
				};
				jQuery.post(ajaxurl, data, function (response){
					var t = JSON.parse(response);
					tr_d.val(t.description);
					tr_t.val(t.terminus);
					jQuery("#translatorList").val(t.uebersetzer);
					jQuery("#correctorTList").val(t.korrekturleser);
					showTranslation();
					aenderungUe = false;
				});
			}
		}
	}
	
	function entryLChanged (){
		var entry = jQuery("#entryL").val();
		
		if((aenderungD || aenderungUe) && !confirm("Die Änderungen am aktuellen Eintrag wurden noch nicht gespeichert! Trotzdem wechseln?")){
			jQuery("#entryL").val(currEntry);
			jQuery("#entryL").trigger("chosen:updated");
			return;
		}
		
		if(entry == 0){
			History.pushState({}, '', '?page=glossar');
		}
		else {
			History.pushState({'entry' : entry}, '', '?page=glossar&entry=' + entry);
		}
	}

	function hideTranslation(){
		jQuery("#tr_description").val("").hide();
		jQuery("#tr_terminus").val("");
		jQuery("#it").hide();
		jQuery("#translatorList").val([]).chosen("destroy").hide();
		jQuery("#translatorLabel").hide();
		jQuery("#correctorTList").val([]).chosen("destroy").hide();
		jQuery("#correctorTLabel").hide();
	}

	function showTranslation (){
		jQuery("#tr_description").show();
		jQuery("#it").show();
		if(jQuery("#translatorList_chosen").is(":visible")){
			jQuery("#translatorList").trigger("chosen:updated");
		}
		else {
			jQuery("#translatorList").show().chosen();
		}
		jQuery("#translatorLabel").show();
		if(jQuery("#correctorTList_chosen").is(":visible")){
			jQuery("#correctorTList").trigger("chosen:updated");
		}
		else {
			jQuery("#correctorTList").show().chosen();
		}
		jQuery("#correctorTLabel").show();
	}

	</script>
	
	<div id="topStuff" style="padding: 0">
	<h1> Texteingabe Glossar </h1>
	
	<br />
	
	<table>
		<tr>
			<td>
			<select name="entry" id="entryL" onChange="entryLChanged();">
				<option>------------------------------------------------------</option>
			</select>
		</td>
			<td>
				<input type="button" class="button button-primary" value="In Datenbank &uuml;bertragen" onClick="updateEntryD()" />
			</td>
			
			<td>
				<input type="button" class="button button-primary" value="Neuen Eintrag anlegen" onClick="newEntryD()" />
			</td>
			<td>
				<span style="margin-left: 10px">Filter:</span>
				<select id="entryFilter">
					<option value="NONE">
						keiner
					</option>
					<option value="MISSING_F">
						fehlende Übersetzungen F
					</option>
					<option value="MISSING_I">
						fehlende Übersetzungen I
					</option>
					<option value="MISSING_S">
						fehlende Übersetzungen S
					</option>
					<option value="MISSING_R">
						fehlende Übersetzungen R
					</option>
					<option value="MISSING_L">
						fehlende Übersetzungen L
					</option>
					<option value="MISSING_E">
						Übersetzung nicht korrekturgelesen E
					</option>
					<option value="NCORRECT_F">
						Übersetzung nicht korrekturgelesen F
					</option>
					<option value="NCORRECT_I">
						Übersetzung nicht korrekturgelesen I
					</option>
					<option value="NCORRECT_S">
						Übersetzung nicht korrekturgelesen S
					</option>
					<option value="NCORRECT_R">
						Übersetzung nicht korrekturgelesen R
					</option>
					<option value="NCORRECT_L">
						Übersetzung nicht korrekturgelesen L
					</option>
					<option value="NCORRECT_E">
						Übersetzung nicht korrekturgelesen E
					</option>
				</select>
			</td>
		</tr>
	</table>
	</div>
	
	<textarea id="description" <?php if (!current_user_can('va_glossary_edit')) echo ' readonly'; ?> style="width:98%;" onChange="changeTextField(false)" autocomplete="off"><?php
		if($curr_entry !== 0){
			echo $va_xxx->get_var($va_xxx->prepare("SELECT Erlaeuterung_D FROM Glossar WHERE Id_Eintrag = %d", $curr_entry));
		}
		?></textarea>
	
	<br />
	
	Autoren:
	<select <?php if (!current_user_can('va_glossary_edit')) echo ' disabled'; ?> id="authorList" multiple="multiple" style="width: 300pt">
		<?php 
		$authors = $va_xxx->get_results("SELECT DISTINCT Kuerzel, Vorname, Name FROM Personen LEFT JOIN Stellen USING(Kuerzel) WHERE Art is null or Art != 'prak'", ARRAY_A);
		foreach ($authors as $author){
			echo "<option value='{$author['Kuerzel']}'>" . $author['Vorname'] . ' ' . $author['Name'] . '</option>';
		}
		?>
	</select>
	
	Tags:
	<select <?php if (!current_user_can('va_glossary_edit')) echo ' disabled'; ?> id="tagList" multiple="multiple" style="width: 500pt">
		<?php 
			$tags = $va_xxx->get_results('SELECT Id_Tag, Tag FROM Tags', ARRAY_A);
			foreach ($tags as $tag){
				echo "<option value='{$tag['Id_Tag']}'>{$tag['Tag']}</option>";
			}
		?>
	</select>
	
	<input <?php if (!current_user_can('va_glossary_edit')) echo ' disabled'; ?> type="checkbox" id="ready" /> Fertig
	
	<input <?php if (!current_user_can('va_glossary_edit')) echo ' disabled'; ?> type="checkbox" id="internal" /> Intern
	
	<br />
	<br />
	
	Übersetzung:
	<div style="display:inline" id="divT">
		<select name="translation" id="translationL" onChange="changeLanguage(this.value);" style="display:inline">
			<option><?php echo DEFAULT_SELECT; ?></option>
			<option value="I">Italienisch</option>
			<option value="F">Französisch</option>
			<option value="R">Rätoromanisch</option>
			<option value="S">Slowenisch</option>
			<option value="E">Englisch</option>
			<option value="L">Ladinisch</option>
		</select>
		<div id="it" style="display: inline;">
			Terminus: <input id="tr_terminus" type="text" onChange="changeTextField(true)" />
		</div>
	</div>
	<textarea id="tr_description" style="width:98%; display:inline" onChange="changeTextField(true)"></textarea>
	
	<span id="translatorLabel">Übersetzer:</span>
	<select id="translatorList" class="noChosen" multiple="multiple" style="width: 300pt">
		<?php
		foreach ($authors as $author){
			echo "<option value='{$author['Kuerzel']}'>" . $author['Vorname'] . ' ' . $author['Name'] . '</option>';
		}
		?>
	</select>
		<span id="correctorTLabel">Korrekturleser:</span>
	
	<select id="correctorTList" class="noChosen" multiple="multiple" style="width: 300pt">
		<?php 
		foreach ($authors as $author){
			echo "<option value='{$author['Kuerzel']}'>" . $author['Vorname'] . ' ' . $author['Name'] . '</option>';
		}
		?>
	</select>
	<?php
}
?>