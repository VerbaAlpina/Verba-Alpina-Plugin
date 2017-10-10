<?php
function va_import_bsa_page (){
	global $va_xxx;
	
	?>
	<style type="text/css">
		.noData {
			background-color: red;
		}
		.partData {
			background-color: yellow;
		}
		.allData {
			background-color: green;
			color: white;
		}
	</style>
	
	<script type="text/javascript">

	var currentStimulus;
	var noEmptyRecords = true;
	var currentRow;
	
	jQuery(function (){
		jQuery("#stimSelection").val("0").select2({
			templateResult: function (data, container) {
				if(data.element){
					jQuery(container).addClass(jQuery(data.element).attr("class"));
				}
				return data.text;
			}
		});

		jQuery("#stimFilter").val("0").change(function (){
			jQuery("#recordTable tbody").empty();
			jQuery("#recordTable").toggle(false);
			jQuery("#infoText").empty();
			jQuery("#importButton").toggle(false);
			
			jQuery.post(ajaxurl, {
				"action": "va",
				"namespace" : "bsa_import",
				"query" : "getOptions",
				"filter" : this.value,
				"ignoreEmpty": noEmptyRecords
			}, function (response){
				jQuery("#stimSelection").html(response).val("0").trigger("change.select2");
			});
		});

		jQuery("#stimSelection").change(function (){
			jQuery("#recordTable tbody").empty();
			jQuery("#recordTable").toggle(false);
			jQuery("#infoText").empty();
			jQuery("#importButton").toggle(false);
			
			currentStimulus = this.value;

			if(currentStimulus == "0"){
				jQuery("#conceptSelection").chosen("destroy").toggle(false);
			}
			else {
				if(jQuery("#conceptSelection").data("chosen") === undefined){
					jQuery("#conceptSelection").val("0").toggle(true).chosen();
				}
				else {
					jQuery("#conceptSelection").val("0").trigger("chosen:updated");
				}
				
			}
		});

		jQuery("#conceptSelection").change(function (){
			jQuery("#recordTable tbody").empty();
			jQuery("#infoText").empty();
				
			if(this.value != "0"){
				jQuery.post(ajaxurl, {
					"action": "va",
					"namespace" : "bsa_import",
					"query" : "getRecords",
					"stimulus" : currentStimulus,
					"concept" : this.value
				}, function (response){
					var data = JSON.parse(response);
					jQuery("#infoText").html(data[1]);
					jQuery("#recordTable tbody").html(data[0]);
					jQuery("#recordTable").toggle(true);
					jQuery("#importButton").toggle(true);
				});
			}
		});

		jQuery("#noRecordsCheckbox").prop("checked", true).change(function (){
			noEmptyRecords = jQuery(this).is(":checked");
			jQuery("#stimFilter").trigger("change");
		});

		jQuery("#singleConceptSelection").change(function (){
			var td = jQuery("#record" + currentRow).children().eq(1);
			td.data("concept", this.value);
			td.find("span").text(jQuery(this).find("option[value=" + this.value + "]").text());

			jQuery("#singleConceptDiv").dialog("close");
		});

		jQuery(".infoSymbol").qtip();

		jQuery("#newConcept").click(function (){
			showTableEntryDialog('newConceptDialog', function (data){
				jQuery('#singleConceptSelection').append("<option value='" + data["id"] + "'>" + data["Beschreibung_D"] + "</option>").trigger("chosen:updated");
				jQuery('#conceptSelection').append("<option value='" + data["id"] + "'>" + data["Beschreibung_D"] + "</option>").trigger("chosen:updated");
			}, selectModes.Chosen);
		});

		jQuery("#importButton").click(function (){
			var data = [];
			var rows = jQuery("#recordTable tbody").children().each(function (){
				if( jQuery(this).children().eq(3).find("input").is(":checked")){
					var point = {
						"aeusserung" : jQuery(this).children().eq(0).find("input").val(),
						"id_stimulus" : currentStimulus,
						"informant" : jQuery(this).children().eq(6).text(),
						"id_konzept" :  jQuery(this).children().eq(1).data("concept"),
						"genus" : jQuery(this).children().eq(2).text(),
						"bemerkung" : jQuery(this).children().eq(5).text(),
						"bsa_id" : 	jQuery(this).data("bsa")
					};
					data.push(point);
				}
			});
			jQuery.post(ajaxurl, {
				"action": "va",
				"namespace" : "bsa_import",
				"query" : "import",
				"data" : data
			}, function (response){
				alert(response);
				location.reload();
			});
		});
		
	});

	function changeConcept(rowNumber){
		currentRow = rowNumber;
		
		jQuery("#singleConceptDiv").dialog({
			"width" : "80%",
			"height": 400,
			"open" : function (){
				jQuery("#singleConceptSelection").val("0").chosen();
			},
			"modal" : true
		});
	}
	</script>
	
	<br />
	<br />
	
	Stimuli filtern:
	<select id="stimFilter">
		<option value="0">Kein Filter</option>
		<option value="I">Bereits importiert</option>
	</select>
	
	<input type="checkbox" id="noRecordsCheckbox" />Stimuli ohne Belege im VA-Gebiet ausblenden

	
	<br />
	<br />
	
	<select id="stimSelection">
		<?php echo va_bsa_get_options('0', true, $va_xxx);?>
	</select>
	
	<?php echo va_get_info_symbol('Farbcodierung: <br /><ul><li>Rot: Keine Belege im VA-Gebiet</li><li>Grün: Alle Belege importiert</ul><ul>Gelb: Belege teilweise importiert</ul><ul>Weiß: Keine Belege importiert</ul>')?>
	
	<br />
	<br />
	
	
	<select id="conceptSelection" style="width: 70%; display: none;">
		<option value="0">VA-Konzept wählen</option>
		<?php 
			$concepts = $va_xxx->get_results("SELECT Id_Konzept, Name_D, Beschreibung_D FROM Konzepte WHERE Relevanz AND NOT Grammatikalisch ORDER BY IF(Name_D != '', Name_D, Beschreibung_D)", ARRAY_A);
			foreach ($concepts as $concept){
				echo '<option value="' . $concept['Id_Konzept'] . '">' . ($concept['Name_D']? $concept['Name_D']: $concept['Beschreibung_D']) . '</option>';	
			}
		?>
	</select>
	
	<input type="button" class="button button-primary" value="Neues Konzept anlegen" id="newConcept" />
	
	<br />
	<br />
	
	<span id="infoText" style="color: green"></span>
	
	<br />
	<br />
	
	<table id="recordTable" class="widefat striped" style="display : none">
		<thead>
			<tr>
				<th>Lautschrift</th>
				<th>VA-Konzept</th>
				<th>Genus</th>
				<th>Import?</th>
				<th>Lemma bzw. Bedeutung</th>
				<th>Kommentar</th>
				<th>Informant</th>
			<tr>
		</thead>
		<tbody></tbody>
	</table>
	
	<br />
	<br />
	
	<input style="display: none" id="importButton" type="button" class="button button-primary" value="Belege importieren" />
	
	<div id="singleConceptDiv" style="display: none">
		<select id="singleConceptSelection" style="width: 90%">
			<option value="0">VA-Konzept wählen</option>
			<?php 
				foreach ($concepts as $concept){
					echo '<option value="' . $concept['Id_Konzept'] . '">' . ($concept['Name_D']? $concept['Name_D']: $concept['Beschreibung_D']) . '</option>';	
				}
			?>
		</select>
	</div>

	<?php
	
	echo im_table_entry_box('newConceptDialog', new IM_Row_Information('Konzepte', array(
			new IM_Field_Information('Name_D', 'V', false),
			new IM_Field_Information('Beschreibung_D', 'V', true),
			new IM_Field_Information('Kategorie', 'E', true, true),
			new IM_Field_Information('Hauptkategorie', 'E', true, true),
			new IM_Field_Information('Relevanz', 'B', false, true, true),
			new IM_Field_Information('Pseudo', 'B', false, true),
			new IM_Field_Information('Grammatikalisch', 'B', false, true)
	), 'Angelegt_Von'));
}

function va_get_bsa_records($id, $concept, &$db){
	$result = '';
	
	$atlases = $db->get_col("SELECT DISTINCT SUBSTR(Nummer, 1, 3) FROM Informanten WHERE Erhebung = 'BSA'");
	$qnum = $db->get_var($db->prepare('SELECT Karte FROM Stimuli WHERE Id_Stimulus = %d', $id));
	
	$count_ready = 0;
	$count_new = 0;
	
	foreach ($atlases as $atlas){
		$question = $db->get_var($db->prepare('SELECT ' . $atlas . ' FROM PVA_BSA.fragen WHERE Fragenr = %s', $qnum));
		
		$records = $db->get_results($db->prepare('
			SELECT Id, Lautschrift, Grammatik, Bedeutung, Lemma, GP_Kom, Expl_Kom, VA_ID, Ortssigle
			FROM PVA_BSA.belege JOIN Informanten ON Nummer = Ortssigle
			WHERE SUBSTR(Ortssigle, 1, 3) = %s AND Frage_Nr = %s AND Lautschrift IS NOT NULL', $atlas, $question), ARRAY_A);
		
		foreach ($records as $key => $record){
			if(!$record['VA_ID']){
				$markRecord = preg_match('/ /', $record['Lemma']) || $record['Bedeutung'];
				
				$concept_name = $db->get_var($db->prepare("SELECT IF(Name_D != '', Name_D, Beschreibung_D) FROM Konzepte WHERE Id_Konzept = %s", $concept));
				
				$result .= '<tr data-bsa="' . $record['Id'] . '" id="record' . $key . '"' . ($markRecord? ' style="background: yellow"': '') . '>';
				$result .= '<td><input type="text" style="width: 100%;" value="' . str_replace(' ', '', $record['Lautschrift']) . '"></input></td>';
				$result .= '<td data-concept="' . $concept . '"><span>' . $concept_name . '</span> <a href="javascript:changeConcept(' . $key . ')">(Ändern)</a></td>';
				$result .= '<td>' . va_bsa_get_gender($record['Grammatik']) . '</td>';
				$result .= '<td><input type="checkbox" checked /></td>';
				$result .= '<td>' . implodeIgnore(array('Lemma' => $record['Lemma'],  'Bedeutung' => $record['Bedeutung']), true) . '</td>';
				$result .= '<td>' . implodeIgnore(array($record['GP_Kom'],  $record['Expl_Kom'], $record['Grammatik'])) . '</td>';
				$result .= '<td>' . $record['Ortssigle'] . '</td>';
				$result .= '</tr>';
				
				$count_new++;
			}
			else {
				$count_ready++;
			}
		}
	}
	
	$db->query($db->prepare('
		INSERT INTO pva_bsa.va_import_info VALUES (Id_Stimulus, Importiert, Fehlend), 
		(%d, %d, %d) ON DUPLICATE KEY UPDATE Importiert = %d, Fehlend = %d', $id, $count_ready, $count_new, $count_ready, $count_new));
	
	return array($result, $count_new . ' neue Belege, ' . $count_ready . ' Belege bereits importiert.');
}

function implodeIgnore($arr, $add_key = false){
	$res_array = array();
	
	foreach ($arr as $key => $val){
		if($val != ''){
			$cval = $val ;
			if($add_key){
				$cval .= ' (' . $key . ')';
			}
			$res_array[] = $cval;
		}
	}
	
	return implode(' / ', $res_array);
}

function va_bsa_get_option_style ($imp, $miss){
	if($imp == 0 && $miss == 0){
		return 'class = "noData"';
	}
	else if($miss == 0){
		return 'class = "allData"';
	}
	else if($imp > 0){
		return 'class = "partData"';
	}
	return '';
}

function va_bsa_get_options ($filter, $ignoreEmpty, &$db){
	$result = '';
	if($filter == 'I'){
		$stquery = "SELECT Id_Stimulus, Stimulus, Importiert, Fehlend FROM Stimuli LEFT JOIN PVA_BSA.va_import_info USING (Id_Stimulus) WHERE Erhebung = 'BSA' AND Importiert > 0";
	}
	else {
		$stquery = "SELECT Id_Stimulus, Stimulus, Importiert, Fehlend FROM Stimuli LEFT JOIN PVA_BSA.va_import_info USING (Id_Stimulus) WHERE Erhebung = 'BSA'";
	}
	
	if($ignoreEmpty == 'true'){
		$stquery .= ' AND Importiert + Fehlend > 0';
	}
	
	$bsa_stim = $db->get_results($stquery, ARRAY_N);
	
	$result .= '<option value="0">Stimulus wählen</option>';	
	foreach ($bsa_stim as $stim){
		$result .= '<option ' . va_bsa_get_option_style($stim[2], $stim[3]) . ' value="' . $stim[0] . '">' . $stim[0] . ' : ' . $stim[1] . '</option>';
	}
	
	return $result;
}

function va_bsa_get_gender ($grammar){
	$genders = array('M','F','N');
	$parts = array_map('trim', explode(',', $grammar));
	
	$resArray = array();
	foreach ($genders as $gender){
		if(in_array($gender, $parts)){
			$resArray[] = strtolower($gender);
		}
	}
	return implode(',', $resArray);
}

function va_bsa_import_records ($data, &$db){
	$num_inserted = 0;
	foreach ($data as $point){
		$id_inf = $db->get_var($db->prepare('SELECT Id_Informant FROM Informanten WHERE Erhebung = %s and Nummer = %s', 'BSA', $point['informant']));
		$auess = $point['aeusserung'];
		if($point['genus'] != ''){
			$auess .= ' <' . $point['genus'] . '>';
		}
		
		if($db->insert(
				'Aeusserungen',
				array ('Aeusserung' => $auess, 'Id_Stimulus' => $point['id_stimulus'], 'Id_Informant' => $id_inf, 'Bemerkung' => $point['bemerkung'], 'Erfasst_Von' => wp_get_current_user()->user_login),
				array('%s', '%d', '%d', '%s'))){
			$id_aeuss = $db->insert_id;
			$db->insert(
				'VTBL_Aeusserung_Konzept',
				array ('Id_Aeusserung' => $id_aeuss, 'Id_Konzept' => $point['id_konzept']),
				array ('%d', '%d')
			);
			$db->select('PVA_BSA');
			$db->update(
				'belege',
				array('VA_ID' => $id_aeuss),
				array('Id' => $point['bsa_id']),
				array ('%d'),
				array ('%d')
			);
			$db->select('Va_xxx');
			$num_inserted++;
		}
	}
	$db->select('PVA_BSA');
	$db->query($db->prepare('UPDATE PVA_BSA.va_import_info 
			SET Importiert = Importiert + ' . $num_inserted . ', Fehlend = Fehlend - ' . $num_inserted . ' 
			WHERE Id_Stimulus = %d', $point['id_stimulus']));
	$db->select('Va_xxx');
	
	return $num_inserted . ' Äußerungen eingefügt, ' . (count($data) - $num_inserted) . ' Fehler.';
}
?>