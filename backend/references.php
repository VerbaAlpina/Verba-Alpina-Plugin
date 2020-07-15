<?php
function va_reference_page (){
	
	?>
	<script type="text/javascript">
	jQuery(function (){
		jQuery("#source_select").val("0");
		
		jQuery("#source_select, #num_lemmas, #num_chars, #without_cand").change(selectionChanged);
		
		function selectionChanged (){
			
			jQuery("select.other_ref").select2("destroy");
			jQuery("#res_area").html("");
			
			let source = jQuery("#source_select").val();
			if (source != "0"){
				jQuery.post(ajaxurl, {
					"action": "va",
					"namespace" : "util",
					"query" : "getTypeWithoutRef",
					"source" : source,
					"threshold": jQuery("#num_chars").val(),
					"num_lemmas": jQuery("#num_lemmas").val(),
					"show_no_candidates": jQuery("#without_cand").is(":checked")
				}, function (response){
					jQuery("#res_area").html(response);
					
					let select2Data = {
						ajax: {
							"url": ajaxurl,
							"dataType": 'json',
							"data" : function (params){
								return {
									"action" : "va",
									"namespace" : "util",
									"query" : "getLemmasForSelect",
									"source" : jQuery("#source_select").val(),
									"search" : params.term
								};
							},
							"processResults": function (data){
								return {"results": data};
							}
						},
						"minimumInputLength": 2
					};
					
					jQuery('select.other_ref').select2(select2Data);
				});
			}
		}
		
		jQuery(document).on("click", "#save_data", function (){
			
			let data = {};
			jQuery("#main_table tr:not(:first)").each(function (){
				let id_type = jQuery(this).find("td:first").data("id");
				let id_ref = jQuery(this).find("input[name=" + id_type + "]:checked").data("id-ref");
				if (id_ref == "select"){
					id_ref = jQuery(this).find(".other_ref").val();
				}
				data[id_type] = id_ref;
			});
			
			jQuery.post(ajaxurl, {
				"action": "va",
				"namespace" : "util",
				"query" : "saveReferences",
				"source" : jQuery("#source_select").val(),
				"data" : data
			}, function (response){
				if (response == "success"){
					selectionChanged();
				}
				else {
					jQuery("#res_area").html("Error: " + response);
				}
			});
		});
		
		jQuery(document).on("change", "select.other_ref", function (){
			jQuery(this).parent().find("input[type=radio].custom_radio").prop("checked", true);
		});
	});
	</script>
	<?php
	
	echo '<br /><br />';

	$sources = ['TLIO', 'DRG'];
	
	echo '<select autocomplete="off" id="source_select"><option value="0">Referenzwörterbuch auswählen</option>';
	foreach ($sources as $source){
		echo '<option value="' . $source . '">' . $source . '</option>';	
	}
	echo '</select>';
	
	echo '<br /><br />Suche Lemmata, die sich um maximal <input type="text" id="num_chars" value="1" style="width: 4em;" /> Zeichen unterscheiden.<br />';
	echo 'Zeige <input type="text" id="num_lemmas" value="1" style="width: 4em;" /> Lemmata gleichzeitig.<br />';
	echo '<input id="without_cand" type="checkbox" /> Zeige Typen ohne Lemma-Kandidaten<br /><br />';
	
	echo '<div id="res_area"></div>';
}

function va_references_show_candidates ($source, $num_chars, $num_lemmas, $show_empty){
	
	global $va_xxx;
	
	$filters = [
		'TLIO' => function ($str){ //Remove grammar info after lemma
			$str = trim($str);
			while (mb_substr($str, -1) == '.') {
				$index = mb_strlen($str) - 2;
				while (ctype_alpha(mb_substr($str, $index, 1)) || mb_substr($str, $index, 1) === '/'){
					$index--;
				}
				$str = trim(mb_substr($str, 0, $index + 1));
			}
			
			$str = trim(preg_replace('/\([^)]*\)/', '', $str));
			
			return $str;
		}
	];
	
	$langs = [
		'TLIO' => 'roa',
		'DRG' => 'roa'
	];

	$lang = $langs[$source];
	if (!$lang){
		echo 'Lang missing for ' . $source;
		return;
	}
	
	$sql = 'SELECT Id_morph_Typ, Orth, Genus, Wortart, Sprache, Affix FROM morph_typen m WHERE Sprache = %s AND Quelle = "VA" AND NOT EXISTS (SELECT * FROM VTBL_morph_Typ_Lemma vm JOIN Lemmata l USING (Id_Lemma) WHERE l.Quelle = %s AND vm.Id_morph_Typ = m.Id_morph_Typ) ORDER BY Orth COLLATE utf8_general_ci ASC';
	$types = $va_xxx->get_results($va_xxx->prepare($sql, $lang, $source), ARRAY_A);
	
	foreach ($types as $itype => $type){
		$types[$itype]['simplified'] = mb_strtolower(remove_accents($type['Orth']));
	}
	
	$sql = 'SELECT Id_lemma, subvocem, link FROM lemmata WHERE Quelle = %s AND NOT Text_Referenz ORDER BY subvocem ASC';
	$refs = $va_xxx->get_results($va_xxx->prepare($sql, $source), ARRAY_A);
	
	$refs_letters = [];
	foreach ($refs as $ref){
		if (isset($filters[$source])){
			$ref['simplified'] = mb_strtolower(remove_accents($filters[$source]($ref['subvocem'])));
		}
		else {
			$ref['simplified'] = mb_strtolower(remove_accents($ref['subvocem']));
		}
		
		$first = mb_substr($ref['simplified'], 0, 1);
		if (!isset($refs_letters[$first])){
			$refs_letters[$first] = [];
		}
		$refs_letters[$first][] = $ref;
	}
	
	echo '<table id="main_table" class="wp-list-table widefat striped"><tr><th style="text-align: left;">VA-Typ</th><th style="text-align: left;">' . $source . '-Lemmata</th></tr>';
	
	$count = 0;
	foreach ($types as $itype => $type){
		
		$candidates = [];
		$num_identical = 0;
		
		foreach (explode(' / ', $type['simplified']) as $type_str){
				
			$first = mb_substr($type_str, 0, 1);
			
			if (isset($refs_letters[$first])){
				foreach ($refs_letters[$first] as $iref => $ref){
					//if ($type_str == $ref['simplified']){
					$diff = abs(mb_strlen($type_str) - mb_strlen($ref['simplified']));
					if ($diff <= $num_chars && levenshtein($type_str, $ref['simplified']) <= $num_chars){
						
						foreach ($candidates as $candidate){
							if ($candidate[1] == $iref){
								continue 2; //Ref already there (possible for multiple variants separated with / )
							}
						}
						
						$identical = $type_str == $ref['simplified'];
						if ($identical){
							$num_identical ++;
						}
						$candidates[] = [$first, $iref, $identical];
					}
				}
			}
		}
		
		if ($show_empty || $candidates){
			
			usort($candidates, function ($arr){
				return $arr[2]? 0: 1;
			});
			
			echo '<tr style="line-height: 2em;">';
			echo '<td data-id="' . $type['Id_morph_Typ'] . '">' . va_format_lex_type ($type['Orth'], $type['Sprache'], $type['Wortart'], $type['Genus'], $type['Affix']) . '</td><td>';
			$found = false;
			foreach ($candidates as $arr){
				$ref = $refs_letters[$arr[0]][$arr[1]];
				echo '<span style="margin-right: 8px;">';
				if ($num_identical == 1 && $arr[2]){
					$found = true;
				}
				echo '<input type="radio" data-id-ref="' . $ref['Id_lemma'] . '" name="' . $type['Id_morph_Typ'] . '"' . ($num_identical == 1 && $arr[2]? ' checked': '') . ' /><a style="color: ' . ($arr[2]? 'green': 'orange') . ';" target="_BLANK" href="' . $ref['link'] . '">' . $ref['subvocem'] . '</a></span>';
			}
			echo '<span style="margin-right: 10px;"><input data-id-ref="vacat" type="radio" name="' . $type['Id_morph_Typ'] . '"' . (!$found? ' checked': '') . ' />nichts</span>';
			echo '<input class="custom_radio" data-id-ref="select" type="radio" name="' . $type['Id_morph_Typ'] . '" /> Lemma wählen: <select class="other_ref" data-id="' . $type['Id_morph_Typ'] . '" style="width: 150px;"></select>';

			echo '</td></tr>';
			$count ++;
			
			if ($count >= $num_lemmas){
				break;
			}
		}
	}
	
	echo '</table><br /><br />';
	
	if ($count == 0){
		echo 'Keine weiteren Typen gefunden.';
	}
	else {
		echo '<input type="button" class="button button-primary" value="Daten speichern" id="save_data" />';
	}
}

function va_references_save_data ($data, $source){
	global $va_xxx;
	foreach ($data as $type => $ref){
		if ($ref === 'vacat'){
			$id_pseudo = $va_xxx->get_var($va_xxx->prepare('SELECT Id_Lemma FROM Lemmata WHERE Quelle = %s AND subvocem = "<vacat>"', $source));
			if (!$id_pseudo){
				$va_xxx->insert('Lemmata', ['Quelle' => $source, 'subvocem' => '<vacat>', 'Angelegt_Von' => 'auto']);
				$id_pseudo = $va_xxx->insert_id;
			}
			$va_xxx->insert('VTBL_morph_Typ_Lemma', ['Id_morph_Typ' => $type, 'Id_Lemma' => $id_pseudo, 'Quelle' => 'VA', 'Angelegt_Von' => wp_get_current_user()->user_login]);
		}
		else {
			$va_xxx->insert('VTBL_morph_Typ_Lemma', ['Id_morph_Typ' => $type, 'Id_Lemma' => $ref, 'Quelle' => 'VA', 'Angelegt_Von' => wp_get_current_user()->user_login]);
		}
	}
	echo 'success';
}