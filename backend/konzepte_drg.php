<?php
function va_drg_concepts (){
    
    global $va_xxx;
    
    ?>
    <script type="text/javascript">
		jQuery(function (){
			jQuery("#selLetter").val("0");

    		jQuery("#selLetter").change(function (){
    			let val = jQuery(this).val();
    			if (val != "0"){
        			jQuery.post(ajaxurl, {
        				"action" : "va",
        				"namespace" : "drg",
        				"query" : "get_letter_data",
        				"letter" : jQuery(this).val()
        			}, function (response){
        				jQuery("#divTable").html(response);
        			});
    			}
				else {
					jQuery("#divTable").html("");
				}
    		});
			
			jQuery("#newConcept").click(function (){
				showTableEntryDialog('newConceptDialog');
			});
			
			jQuery("#importData").click(function (){
				
				jQuery("#importRes").html("<img src='<?php echo VA_PLUGIN_URL . '/images/Loading.gif';?>' />");
				
				jQuery.post(ajaxurl, {
        				"action" : "va",
        				"namespace" : "drg",
        				"query" : "import"
        			}, function (response){
        				jQuery("#importRes").html(response);
        			});
			});
		});
    
		jQuery(document).on("click", ".ircb", function (){
			let row = jQuery(this).parents("tr");

			row.css("background", "lightcoral");
			
			jQuery.post(ajaxurl, {
				"action" : "va",
				"namespace" : "drg",
				"query" : "set_irrelevant",
				"id" : row.data("id")
			}, function (response){
				if (response === "success"){
					row.remove();
				}
			});
		});
		
		jQuery(document).on("click", ".konzeptButton", function () {
			
			jQuery("select.konzeptSelect").select2("destroy").replaceWith('<input type="button" class="button button-primary konzeptButton" value="Konzept(e) hinzufügen" />');
			
			jQuery(this).replaceWith("<select style='min-width: 400px;' class='konzeptSelect'></select>");
			
			let select2Data = {
				ajax: {
					"url": ajaxurl,
					"dataType": 'json',
					"data" : function (params){
						let already = jQuery(this).parent().find("span.chosen-like-button").map(
							function(){
								return jQuery(this).data('id');
							}).get();
						
						return {
							"action" : "va",
							"namespace" : "util",
							"query" : "getConceptsForSelect",
							"search" : params.term,
							"ignore" : already,
							"page": params.page || 1
						};
					}
				}
			};
			
			select2Data = Object.assign(select2Data, getConceptSearchDefaults());
			jQuery('.konzeptSelect').select2(select2Data);
		});
		
		jQuery(document).on("change", ".konzeptSelect", function () {

			let data = jQuery(this).select2("data");
			let that = this;

			if (data[0] && data[0].id){
				jQuery.post(ajaxurl, {
					"action" : "va",
					"namespace" : "drg",
					"query" : "set_concept",
					"id_concept" : data[0].id,
					"id_lemma" : jQuery(this).closest("tr").data("id")
				}, function (response){
					if (response === "success"){
						jQuery(that).parent().prepend("<span class='chosen-like-button chosen-like-button-del' data-id='" + data[0].id + "'>" + data[0].text + "<a class='removeConcept' /></span>");
					}
				});
				
				jQuery(this).val(null).trigger("change");
			}
		});
		
		jQuery(document).on("click", ".removeConcept", function (){
			
			let that = this;
			
			jQuery.post(ajaxurl, {
				"action" : "va",
				"namespace" : "drg",
				"query" : "remove_concept",
				"id_concept" : jQuery(this).closest("span").data("id"),
				"id_lemma" : jQuery(this).closest("tr").data("id")
			}, function (response){
				if (response === "success"){
					jQuery(that).closest("span").remove();
				}
			});
		});
    </script>
    
	<br /><br />
	
	<?php
	//if (isDevTester()){
		echo '<input type="button" id="importData" class="button button-primary" value="Re-Import" /><br /><div id="importRes"></div><br /><br />';
	//}
	?>
	
    <select id="selLetter">
    	<option value="0">Buchstabe wählen</option>
    	<?php 
    	$letters = $va_xxx->get_col('SELECT DISTINCT SUBSTRING(lemma, 1, 1) FROM pva_drg.lemmata WHERE SUBSTRING(lemma, 1, 1) REGEXP "[A-Z]"');
    	foreach ($letters as $letter){
    	   echo '<option value="' . $letter . '">' . $letter . '</option>';
    	}
    	?>
    </select>
	
	<input type="button" class="button button-primary" id="newConcept" value="Konzept anlegen" />
	<?php va_echo_new_concept_fields('newConceptDialog'); ?>
    
    <div id="divTable"></div>
    <?php
}

function va_drg_get_letter_data ($letter){
    
    global $va_xxx;
    $data = $va_xxx->get_results('SELECT id_lemma, lemmata.lemma, bedeutung, group_concat(DISTINCT Id_Konzept) as konzepte, Id_Stimulus FROM pva_drg.lemmata left JOIN pva_drg.vtbl_lemma_konzept USING (id_lemma) JOIN pva_drg.phon_formen USING (id_lemma) WHERE NOT irrelevant AND lemmata.lemma LIKE "' . $letter . '%" OR lemmata.lemma LIKE "(' . $letter . '%" GROUP BY id_lemma', ARRAY_A);
    
	$concept_names = va_two_dim_to_assoc($va_xxx->get_results('SELECT Id_Konzept, IF(Name_D is not null and Name_d != "" AND Name_D != Beschreibung_D, CONCAT(Name_D, " (", Beschreibung_D, ")"), Beschreibung_D) from Konzepte', ARRAY_N));
	
    $res = '<table style="border-collapse: collapse"><tr><th>Irrelevant</th><th>Lemma</th><th style="max-width: 300px;">Bedeutung(en)</th><th>Konzept(e)</th></tr>';
    foreach ($data as $row){
        $res .= '<tr style="border-bottom: 1px solid black;" data-id="' . $row['id_lemma'] . '"><td><input class="ircb" type="checkbox" /></td><td>' . $row['lemma'] . '</td><td style="max-width: 300px;">' . $row['bedeutung'] . '</td><td>';
		
		if ($row['konzepte']){
			foreach (explode(',', $row['konzepte']) as $id_concept){
				$res .= "<span class='chosen-like-button chosen-like-button-del' data-id='" . $id_concept . "'>" . $concept_names[$id_concept];
				if (!$row['Id_Stimulus']){
					$res .= "<a class='removeConcept' />";
				}
				
				$res .= "</span>";
			}
		}
		
		if (!$row['Id_Stimulus']){
			$res .= '<input type="button" class="button button-primary konzeptButton" value="Konzept(e) hinzufügen" />';
		}
		
		$res .= '</td></tr>';
    }
    
    return $res;
}

function va_drg_set_irrelevant ($id){
    global $va_xxx;
    
    $va_xxx->select('pva_drg');
    if ($va_xxx->update('lemmata', ['irrelevant' => 1], ['id_lemma' => $id])){
        return 'success';
    }
}

function va_drg_set_concept ($id_lemma, $id_concept){
	global $va_xxx;
    
    $va_xxx->select('pva_drg');
    if ($va_xxx->insert('vtbl_lemma_konzept', ['id_lemma' => $id_lemma, 'id_konzept' => $id_concept])){
        return 'success';
    }
}

function va_drg_remove_concept ($id_lemma, $id_concept){
	global $va_xxx;
    
    $va_xxx->select('pva_drg');
    if ($va_xxx->delete('vtbl_lemma_konzept', ['id_lemma' => $id_lemma, 'id_konzept' => $id_concept])){
        return 'success';
    }
}

function va_drg_import_data (){
	global $va_xxx;
	
	$res = $va_xxx->get_results('
		SELECT id_form, form, vfi.id_informant, spor, lemmata.id_stimulus, lemmata.lemma, Nummer, lemmata.id_lemma, keine_pruefung, id_aeusserung
		FROM pva_drg.phon_formen 
			JOIN pva_drg.lemmata USING (id_lemma) 
			JOIN pva_drg.vtbl_formen_informanten vfi USING (id_form)
			LEFT JOIN va_xxx.aeusserungen USING (id_aeusserung)
		WHERE (Id_Aeusserung IS NULL OR geaendert > erfasst_am)
			AND EXISTS (SELECT * FROM pva_drg.vtbl_lemma_konzept vlk WHERE vlk.id_lemma = lemmata.id_lemma AND vlk.id_konzept != 7706)
			AND (keine_pruefung OR NOT (uebersprungen IS NOT NULL AND uebersprungen = geaendert))
		ORDER BY id_form ASC 
		LIMIT 5000', ARRAY_A);
	
	$new_stimuli = [];
	$skipped_forms = [];
	$skipped = 0;
	
	try {
		foreach ($res as $row){
			
			if (in_array($row['id_form'], $skipped_forms)){
				$skipped++;
				continue;
			}
			
			if ($row['keine_pruefung']){
				$record = $row['form'];
			}
			else {
				try {
					$record = va_drg_format_form($row['form']);
				}
				catch (Exception $e){
					$va_xxx->select('pva_drg');
					$va_xxx->update('phon_formen', ['uebersprungen' => current_time('mysql'), 'geaendert' => current_time('mysql')], ['id_form' => $row['id_form']]);
					$va_xxx->select('va_xxx');
					
					$skipped_forms[] = $row['id_form'];
					$skipped++;
					continue;
				}
			}
			
			$concepts = $va_xxx->get_col('SELECT Id_Konzept FROM pva_drg.vtbl_lemma_konzept WHERE id_konzept != 7706 AND id_lemma = ' . $row['id_lemma']);
			
			if ($row['id_stimulus']){
				$stimulus = $row['id_stimulus'];
			}
			else {
				if (array_key_exists($row['id_lemma'], $new_stimuli)){
					$stimulus = $new_stimuli[$row['id_lemma']];
				}
				else {
					$va_xxx->insert('Stimuli', ['Erhebung' => 'DRG', 'Karte' => $row['Nummer'], 'Nummer' => 1, 'Stimulus' => 'Lemma "' . $row['lemma'] . '"']);
					
					$stimulus = $va_xxx->insert_id;
					
					$new_stimuli[$row['id_lemma']] = $stimulus;
					$va_xxx->select('pva_drg');
					$va_xxx->update('lemmata', ['id_stimulus' => $stimulus], ['id_lemma' => $row['id_lemma']]);
					$va_xxx->select('va_xxx');
				}
			}
			
			if ($row['id_aeusserung']){
				$va_xxx->query('DELETE FROM VTBL_Token_morph_Typ WHERE Id_Token IN (SELECT Id_Token FROM Tokens WHERE Id_Aeusserung = ' . $row['id_aeusserung'] . ')');
				$va_xxx->query('DELETE FROM VTBL_Token_Konzept WHERE Id_Token IN (SELECT Id_Token FROM Tokens WHERE Id_Aeusserung = ' . $row['id_aeusserung'] . ')');
				$va_xxx->query('DELETE FROM Tokens WHERE Id_Aeusserung = ' . $row['id_aeusserung']);
				$va_xxx->query('DELETE FROM VTBL_Aeusserung_Konzept WHERE Id_Aeusserung = ' . $row['id_aeusserung']);	
				
				$va_xxx->update('Aeusserungen', [
					'Id_Informant' => $row['id_informant'],
					'Aeusserung' => $record,
					'Bemerkung' => $row['spor']? 'spor.': '',
					'Tokenisiert' => 0,
					'Erfasst_am' => current_time('mysql') //Geaendert_am kann nicht verwendet werden, weil es auch bei der Tokenisierung gesetzt wird
				], ['id_aeusserung' => $row['id_aeusserung']]);
				
				foreach ($concepts as $concept){
					$va_xxx->insert('vtbl_aeusserung_konzept', ['id_aeusserung' => $row['id_aeusserung'], 'id_konzept' => $concept]);
				}
			}
			else {

				$va_xxx->insert('Aeusserungen', [
					'Id_Stimulus' => $stimulus, 
					'Id_Informant' => $row['id_informant'], 
					'Aeusserung' => $record, 
					'Erfasst_Von' => 'admin', 
					'Version' => 1, 
					'Klassifizierung' => 'B',
					'Bemerkung' => $row['spor']? 'spor.': ''
				]);
				$id_record = $va_xxx->insert_id;
				
			
				foreach ($concepts as $concept){
					$va_xxx->insert('vtbl_aeusserung_konzept', ['id_aeusserung' => $id_record, 'id_konzept' => $concept]);
					
					$va_xxx->select('pva_drg');
					$va_xxx->update('vtbl_formen_informanten', ['id_aeusserung' => $id_record], ['id_form' => $row['id_form'], 'id_informant' => $row['id_informant']]);
					$va_xxx->select('va_xxx');
				}
			}
		}
		
		$sql_rest = 'SELECT count(*)
		FROM pva_drg.phon_formen 
			JOIN pva_drg.lemmata USING (id_lemma) 
			JOIN pva_drg.vtbl_formen_informanten USING (id_form) 
		WHERE Id_Aeusserung IS NULL
			AND EXISTS (SELECT * FROM pva_drg.vtbl_lemma_konzept vlk WHERE vlk.id_lemma = lemmata.id_lemma AND vlk.id_konzept != 7706)';
			
		$rest = $va_xxx->get_var($sql_rest);
		
		return (count($res) - $skipped) . ' Datensätze importiert. ' . $skipped . ' übersprungen. ' . $rest . ' weitere vorhanden.';
	}
	catch (Exception $e){
		return $e->getMessage();
	}
}

function va_drg_format_form ($form){

	$parts = array_map('trim', explode(';', $form));
	
	// Try to resolve brackets
	$changed_parts = [];
	foreach ($parts as $part){
		if (mb_strpos($part, '(') !== false || mb_strpos($part, ')') !== false){
			$num_opening = substr_count($part, '(');
			$num_closing = substr_count($part, ')');
			if ($num_opening != $num_closing){
				throw new Exception('Brackets not balanced: ' . $form);
			}
			
			if (substr_count($part, ' ') === 0 && $num_opening === 1){
				$first_op = mb_strpos($part, '(');
				$first_cl = mb_strpos($part, ')');
				
				$changed_parts[] = mb_substr($part, 0, $first_op) . mb_substr($part, $first_op + 1, $first_cl - $first_op - 1) . mb_substr($part, $first_cl + 1);
				$changed_parts[] = mb_substr($part, 0, $first_op) . mb_substr($part, $first_cl + 1);
			}
			else {
				throw new Exception('Brackets could not be resolved: ' . $form);
			}
			
		}
		else {
			$changed_parts[] = $part;
		}
	}
	
	$parts = $changed_parts;
	
	
	//Check characters
	$new_parts = [];
	foreach ($parts as $part){
		$tokens = explode(' ', $part);

		foreach ($tokens as $index => $token){
		
			if ($index > 0 && mb_substr($tokens[$index - 1], 0, 1) === mb_substr($token, 0, 1)){
				throw new Exception('Words start with same letter: ' . $form);
			}
			
			if (!preg_match("/^[;̄špẹrdíαęl'bákać̆mntǘīsǭọvǫācejúhíoučéọ̈zųŋüñgǫ́ǖūǵ̯əłf̤ḁ̀ôḗ̇įèEžạąǧCêxqDIäPBॱχyGìùwṓûMTâụòǜɱ̀ǩRěǟēĭFṛLōὰNSWė̮î]*$/", $token)){
				throw new Exception('Special characters: ' . $form);
			}
		}
		
		$new_parts[] = implode(' ', $tokens);
	}
	
	//Create result
	$res = implode('; ', $new_parts);
	
	if ($res != $form){
		//throw new Exception($form . ' to ' . $res);
	}
	
	return $res;
	
	
	
}