<?php
function va_record_input_for_concept ($id_stimulus, $id_informant, $classification, $show_notes){
	global $record_input_shortcode;
	$record_input_shortcode = true;
	
	global $lang;
	
	$res = va_get_concept_input_list($id_stimulus, $id_informant, $show_notes);
	
	ob_start();
	
	?>
	<script type="text/javascript">
		var lang = "<?php echo $lang;?>";
		var showNotes = <?php echo $show_notes? 'true' : 'false' ?>;

		jQuery(function (){
			jQuery("#va_record_input_concepts").select2({
				sorter : function (results, nase, bah){
					var term = jQuery(".select2-search__field").val();
					if(!term)
						term = "";
					term = term.toUpperCase();
					
					if(term){
						results.sort(function (a, b){
							var t1 = a.text.toUpperCase();
							var t2 = b.text.toUpperCase();

							//Saerch string possibly not found because of e.g. umlauts
							var i1 = t1.indexOf(term);
							if(i1 == -1)
								i1 = Number.MAX_VALUE;
							var i2 = t2.indexOf(term);
							if (i2 == -1)
								i2 = Number.MAX_VALUE;
							var diff = i1 - i2;

							if(diff == 0){
								var col1 = t1.indexOf(":");
								if(col1 == -1)
									col1 = t1.length;
								var col2 = t2.indexOf(":");
								if (col2 == -1)
									col2 = t2.length;
								var diffCol = col1 - col2;

								if(diffCol == 0){
									return t1.localeCompare(t2);
								}
								else {
									return diffCol;
								}
							}
							else {
								return diff;
							}
						});
					}
					return results;
				},
				templateResult: function (state){
					colString = "";
					if(state.element && jQuery(state.element).hasClass("red")){
						var colString = " style='background-color: red;'";
					}

					return jQuery("<span" + colString + ">" + state.text + "</span>");
				}
			});
			
			jQuery(document).on("input", ".recordInput, .va_record_input_notes", function (){
				jQuery(this).addClass("elementChanged");
			});

			jQuery(document).on("change", ".recordInput, .va_record_input_notes", function (){
				var row = jQuery(this).closest("tr");

				var cell = jQuery(this);
				var notes, recordCell;
				if(cell.hasClass("recordInput")){
					notes = row.find("textarea.va_record_input_notes").val();
					recordCell = cell;
				}
				else {
					recordCell = row.find("input.recordInput");
					notes = cell.val();
				}
				var record = recordCell.val();
				
				
				jQuery.post(ajax_object.ajaxurl, {
					"action" : "va",
					"namespace": "record_input",
					"query" : "save",
					"value" : record,
					"notes" : notes,
					"id_stimulus" : <?php echo $id_stimulus;?>,
					"id_informant" : <?php echo $id_informant;?>,
					"id_aeusserung" : recordCell.data("id")? recordCell.data("id"): "NEW",
					"id_konzept" : row.data("id-concept"),
					"classification" : "<?php echo $classification? $classification: 'B';?>",
					"lang" : "<?php echo $lang;?>"
				}, function (response){
					if(response.startsWith("success")){
						if (response == "successNO"){
							recordCell.data("id", null);
						}
						else {
							recordCell.data("id", response.substring(7));
						}
						cell.removeClass("elementChanged");
						if(cell.hasClass("recordInput")){
							if (record == ""){
								row.find("textarea.va_record_input_notes").prop("readonly", true);
							}
							else {
								row.find("textarea.va_record_input_notes").removeProp("readonly");
							}
						}
					}
				});
			});

			jQuery("#newConcept").click(function (){
				showTableEntryDialog('newConceptDialog', function (data){
					var classRed = false;
					var text;
					if(data["Name_" + lang]){
						text = data["Name_" + lang];
					}
					else if(data["Beschreibung_" + lang]){
						text = data["Beschreibung_" + lang];
					}
					else if(data["Name_D"]){
						text = data["Name_D"];
						classRed = lang != "D";
					}
					else{
						text = data["Beschreibung_D"];
						classRed = lang != "D";
					}

					jQuery("#va_record_input_concepts").append("<option value='" + data["id"] + "'" + (classRed? " class='red'": "") + ">" + text + "</option>")
						.val(data["id"])
						.trigger("change");
				});
			});

			jQuery("#va_record_input_add_button").click(function (){
				jQuery.post(ajax_object.ajaxurl, {
					"action" : "va",
					"namespace": "record_input",
					"query" : "save",
					"value" : jQuery("#va_record_input_new_value_field").val(),
					"notes" : jQuery("#va_record_input_new_notes_field").val(),
					"id_stimulus" : <?php echo $id_stimulus;?>,
					"id_informant" : <?php echo $id_informant;?>,
					"id_aeusserung" : "NEW",
					"id_konzept" : jQuery("#va_record_input_concepts").val(),
					"classification" : "<?php echo $classification? $classification: 'B';?>",
					"lang" : "<?php echo $lang;?>",
					"returnType" : "<?php echo $show_notes? '1' : '0';?>"
				}, function (response){
					if (response == "successNO"){
						alert("Kein Beleg!");
					}
					else {
						jQuery("#recordInputTable").append(response);
						jQuery("#va_record_input_concepts option:selected").remove();
						jQuery("#va_record_input_concepts").trigger("change");
						jQuery("#va_record_input_new_value_field").val("");
						jQuery("#va_record_input_new_notes_field").val("");
					}
				});
			});
		});
	</script>
	
	<style type="text/css">
	   input[readonly] {
	       background: lightgrey;
	   }
	   textarea:-moz-read-only {
			background-color: lightgrey;
		}
		
		textarea:read-only {
			background-color: lightgrey;
		}
	</style>
	<?php 
	
	$res .= ob_get_contents();
	ob_end_clean();
	
	return $res;
}

function va_get_concept_input_list($id_stimulus, $id_informant, $show_notes){
	global $va_xxx;
	global $lang;

	$concepts = $va_xxx->get_results($va_xxx->prepare("
	SELECT Name_$lang AS Name, Beschreibung_$lang AS Beschreibung, Name_D, Beschreibung_D, Aeusserung, Id_Konzept, Id_Aeusserung, IF(Id_Token IS NULL, 0, 1) AS Readonly, Bemerkung
	FROM Konzepte JOIN (SELECT Id_Aeusserung, Aeusserung, Aeusserungen.Bemerkung, Id_Token, Id_Konzept FROM VTBL_Aeusserung_Konzept JOIN Aeusserungen USING(Id_Aeusserung) LEFT JOIN Tokens USING (Id_Aeusserung) WHERE Aeusserungen.Id_Stimulus = %d AND Aeusserungen.Id_Informant = %d) k
	USING (Id_Konzept)
	ORDER BY Basiskonzept DESC, IF(Name_$lang != '', Name_$lang, IF(Beschreibung_$lang != '', Beschreibung_$lang, IF(Name_D != '', Name_D, Beschreibung_D))) ASC
	LIMIT 200", $id_stimulus, $id_informant), ARRAY_A);
	
	$res = '';
	
	$res .= '<div style="position: fixed; left: 5px; top: 50px;"><input type="button" class="button button-primary" id="newConcept" value="Konzept hinzufügen" /></div>';
	
	ob_start();
	va_echo_new_concept_fields('newConceptDialog', [new IM_Field_Information('Name_I', 'V', false), new IM_Field_Information('Beschreibung_I', 'V', false)]);
	$res .= ob_get_contents();
	ob_end_clean();
	
	$res .= '<table id="recordInputTable">';
	foreach ($concepts as $concept){
		$res .= va_create_record_input_row($concept, $show_notes);
	}
	$res .='</table>';
	
	$res .= '<br /><br />';
	$res .= '<h2>Neuer Eintrag</h2>';
	$res .= '<br />';
	$res .= '<input id="va_record_input_new_value_field" type="text" style="width: 29%; margin-right: 1%;" />';
	$res .= '<select id="va_record_input_concepts" style="width: 70%">';
	$conceptsVA = $va_xxx->get_results($va_xxx->prepare("
		SELECT 
			Id_Konzept, 
			IF(
				Beschreibung_$lang != '', 
				IF (Name_$lang != '' AND Name_$lang != Beschreibung_$lang, CONCAT(Name_$lang, ': ', Beschreibung_$lang), Beschreibung_$lang), 
				IF (Name_D != '' AND Name_D != Beschreibung_D, CONCAT(Name_D, ': ', Beschreibung_D), Beschreibung_D)
			) as Name,
			Name_$lang != '' OR Beschreibung_$lang != '' AS Uebersetzung
		FROM Konzepte k
		WHERE NOT Grammatikalisch AND NOT Pseudo AND NOT EXISTS (
			SELECT * FROM Aeusserungen JOIN VTBL_Aeusserung_Konzept v USING (Id_Aeusserung) WHERE v.Id_Konzept = k.Id_Konzept AND Id_Stimulus = %d AND Id_Informant = %d
		)
		ORDER BY Name ASC", $id_stimulus, $id_informant), ARRAY_A);
	
	foreach ($conceptsVA as $vac) {
		$res.= '<option' . ($vac['Uebersetzung']? '' : ' class="red"') . ' value="' . $vac['Id_Konzept'] . '">' . $vac['Name'] . '</option>';
	}
	$res .= '</select>';
	
	if($show_notes){
		$res .= '<textarea id="va_record_input_new_notes_field" autocomplete="off" style="width: 94%; margin-left: 3%; margin-top: 20px; height: 180px;"></textarea>';
	}
	
	$res .= '<br /><br />';
	
	$res .= '<input type="button" id="va_record_input_add_button" value="Eintragen" class="button button-primary" />';
	
	return $res;
}

function va_create_record_input_row ($concept, $show_notes){
	$res ='<tr data-id-concept="' . $concept['Id_Konzept'] . '">';
	$res .='<td><input autocomplete="off" style="min-width: 300px;"'
		. ($concept['Readonly'] == '1'? ' readonly': '')
		. ' class="recordInput" data-id="' . $concept['Id_Aeusserung']
		. '" type="text" value="' . $concept['Aeusserung']
		. '" /></td><td>' . ($concept['Beschreibung'] || $concept['Name']? '<b>' . ($concept['Name']? $concept['Name'] . ': ': '') . '</b>' . $concept['Beschreibung']: '<span style="color: red;"><b>' . ($concept['Name_D']? $concept['Name_D'] . ': ': '') . '</b>' . $concept['Beschreibung_D'] . '</span>') . '</td>';
	if($show_notes){
		$res .= '<td><textarea class="va_record_input_notes" autocomplete="off"' . ($concept['Readonly'] == '1' || !$concept['Aeusserung']? ' readonly': '')  . '>' .
								$concept['Bemerkung'] . '</textarea></td>';
	}
	$res .='</tr>';
	
	return $res;
}

function va_update_record ($id_stimulus, $id_informant, $id_record, $record, $notes, $id_concept, $classification, $lang, $returnType){
	global $va_xxx;
	
	if($record == '' && $id_record){
	    $va_xxx->delete('VTBL_Aeusserung_Konzept', ['Id_Aeusserung' => $id_record]);
		$va_xxx->delete('Aeusserungen', ['Id_Aeusserung' => $id_record]);
		return 'successNO';
	}
	
	if($id_record == 'NEW'){
		if($va_xxx->insert('Aeusserungen', 
		    ['Id_Stimulus' => $id_stimulus, 
		     'Id_Informant' => $id_informant, 
		     'Aeusserung' => $record,
		     'Bemerkung' => $notes,
		     'Erfasst_Von' => wp_get_current_user()->user_login,
		     'Klassifizierung' => $classification
		    ])){
			
		     $id_a = $va_xxx->insert_id;
		     if($va_xxx->insert('VTBL_Aeusserung_Konzept',
		         ['Id_Aeusserung' => $id_a,
		         'Id_Konzept' => $id_concept])){
		    
		         if($returnType == NULL){
		         	return 'success' . $id_a;
		         }
		         else {
		         	global $va_xxx;
		         	$concept = $va_xxx->get_row($va_xxx->prepare("
						SELECT Id_Konzept, Name_$lang AS Name, Beschreibung_$lang AS Beschreibung, Name_D, Beschreibung_D
						FROM Konzepte WHERE Id_Konzept = %d", $id_concept), ARRAY_A);
		         			         	
		         	$concept['Aeusserung'] = $record;
		         	$concept['Id_Aeusserung'] = $id_a;
		         	$concept['Readonly'] = 0;
		         	$concept['Bemerkung'] = $notes;

		         	return va_create_record_input_row($concept, $returnType == '1');
		         }
		     }
		}
		return 'error';
	}
	
	if($va_xxx->update('Aeusserungen', ['Aeusserung' => $record, 'Erfasst_Von' => wp_get_current_user()->user_login, 'Bemerkung' => $notes], ['Id_Aeusserung' => $id_record])){
	    $va_xxx->delete('VTBL_Aeusserung_Konzept', ['Id_Aeusserung' => $id_record]);
	    if($va_xxx->insert('VTBL_Aeusserung_Konzept',
	        ['Id_Aeusserung' => $id_record,
	            'Id_Konzept' => $id_concept])){
		  return 'success' . $id_record;
	    }
	}
	return 'error';
}
?>