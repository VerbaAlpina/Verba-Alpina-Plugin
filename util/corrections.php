<?php
function va_correction_test_page() {
	//va_correction_gui (45034);
	//va_correction_gui(129845);
	//va_correction_gui(65822);
	va_correction_gui(27704);
}
function va_correction_gui($id_record) {
	global $va_xxx;
	$record = $va_xxx->get_row ($va_xxx->prepare ('
		SELECT Id_Aeusserung, Aeusserung, Klassifizierung, a.Bemerkung, Erhebung 
		FROM Aeusserungen a JOIN Stimuli USING (Id_Stimulus)
		WHERE Id_Aeusserung = %d AND Tokenisiert', $id_record), ARRAY_A);
	
	if(!$record){
		echo '<span style="background: red">Nicht tokenisiert!</span>';
		return;
	}
	
	$conceptsRecord = $va_xxx->get_col ($va_xxx->prepare ('
		SELECT Id_Konzept
		FROM VTBL_Aeusserung_Konzept
		WHERE Id_Aeusserung = %d', $id_record));
	
	
	$concepts = $va_xxx->get_results("SELECT Id_Konzept AS id, IF(Name_D != '', Name_D, Beschreibung_D) as text FROM Konzepte ORDER BY Text ASC", ARRAY_A);
	$mtypes = $va_xxx->get_results("SELECT Id_morph_Typ, Orth, Genus, Sprache, Wortart, Affix FROM morph_Typen WHERE Quelle = 'VA'", ARRAY_A);
	$mtypes = array_map(function ($e){
		return ['id' => $e['Id_morph_Typ'], 'text' => va_format_lex_type($e['Orth'], $e['Sprache'], $e['Wortart'], $e['Genus'], $e['Affix'])];
	}, $mtypes);
	
	wp_localize_script('toolsSkript', 'Concepts', $concepts);
	wp_localize_script('toolsSkript', 'MTypes', $mtypes);
	IM_Initializer::$instance->enqueue_select2_library();
	va_enqueue_tabs ();
	wp_enqueue_script("jquery-ui-draggable");
	wp_enqueue_script("jquery-ui-droppable");
	
	?>
<script type="text/javascript">

	jQuery(function (){
		var data = getConceptSearchDefaults();
		data["data"] = Concepts;
		data["multiple"] = true;
		jQuery(".conceptSelect").select2(data);
		jQuery("#corrConceptSelect").val([<?php echo implode(',', $conceptsRecord);?>]).trigger("change");
		jQuery("#correctionsTabs").tabs({disabled: [1,2], activate : function (event, ui){
			if(ui.newTab.index() == 0){
				jQuery("#correctionsTabs").tabs({disabled: [1,2]});
			}
		}, active : 0});

		jQuery("#tokenize_button").click(function (){
			jQuery("#tokenize_new").html('<img src="<?php echo VA_PLUGIN_URL . '/images/Loading.gif';?>" />');
			jQuery("#correctionsTabs").tabs({disabled: [2], active: 1});

			jQuery.post(ajax_object.ajaxurl, {
				"action" : "va",
				"namespace" : "util",
				"query" : "tokenizeRecord",
				"id" : <?php echo $id_record; ?>,
				"record" : jQuery("#correctionsRecordInput").val(),
				"extraData" : {
					"class" : jQuery("#correctionsClassInput").val(),
					"notes" : jQuery("#correctionsNotesInput").val(),
					"concepts" : jQuery("#corrConceptSelect").val()
				},
				"source" : "<?php echo htmlspecialchars($record['Erhebung']);?>"
			}, function (response){
				if(response.startsWith("ERR:")){
					jQuery("#tokenize_new").html(response.substring(4));
				}
				else {
					jQuery("#tokenize_new").html(response);
					setBlockedElements();
					
					jQuery(".corrField:not(.corrFieldFixd)").draggable({
						revert : "invalid",
						zIndex: 1000
					});
					jQuery("#corrTokenTable td").droppable({
						accept: function (element){
							var colNumber = jQuery(this).index();

							var tdUsed;
							if(element.hasClass("corrFieldConcept")){
								tdUsed = jQuery("#conceptRow td").eq(colNumber);
								return jQuery(tdUsed).data("type") == "concept" 
									&& tdUsed.find("div:not(.corrPlus)").length == 0 
									&& (element.data("gramm") == 0 || jQuery("#typeRow td").eq(colNumber).find("div:not(.corrPlus)").length == 0);
							}
							else if(element.hasClass("corrFieldType")){
								tdUsed =jQuery("#typeRow td").eq(colNumber);
								return jQuery(tdUsed).data("type") == "type" 
									&& tdUsed.find("div:not(.corrPlus)").length == 0 
									&& jQuery("#conceptRow td").eq(colNumber).find("div[data-gramm=1]").length == 0;
							}
							return false;
						},
						drop : function (event, ui){
							var colNumber = jQuery(this).index();
							
							var tdUsed;
							if(ui["draggable"].hasClass("corrFieldConcept")){
								tdUsed =jQuery("#conceptRow td").eq(colNumber);
							}
							else if(ui["draggable"].hasClass("corrFieldType")){
								tdUsed = jQuery("#typeRow td").eq(colNumber);
							}
							setTimeout(function(){
								tdUsed.find(".corrPlus").remove();
								
								ui["draggable"].appendTo(tdUsed);
								ui["draggable"].css("left", 0);
								ui["draggable"].css("top", 0);
								ui["draggable"].css("width", "");

								setBlockedElements();
							}, 0);
							
						},
						activeClass : "activeTarget"
					});
				}
			});
		});

		jQuery(document).on("click", ".corrFieldConcept span, .corrFieldType span", function (){
			jQuery(this).parent().remove();
			setBlockedElements();
		});
	});

	function setBlockedElements (){
		 jQuery("#conceptRow td:not(.fixed):not(:first)").each(function (){
			if(jQuery(this).children().length == 0){
				jQuery(this).append("<div class='corrField corrPlus'>+</div>");
			}
			 
			 var colNumber = jQuery(this).index();
			 var typeTd = jQuery("#typeRow td").eq(colNumber);
			 if (jQuery(this).find("div[data-gramm=1]").length > 0){
				 typeTd.addClass("blocked");
				 typeTd.find(".corrPlus").remove();
			 }
			 else {
				 typeTd.removeClass("blocked");
				 if(typeTd.children().length == 0){
					typeTd.append("<div class='corrField corrPlus'>+</div>");
				 }
			 }
		 });
	}
</script>	
	

<div id="correctionsTabs">
	<ul>
		<li><a href="#change_record">Äußerung ändern</a></li>
		<li><a href="#tokenize_new">Tokenisierung anpassen</a></li>
		<li><a href="#ready">Fertig</a></li>
	</ul>
	<div id="change_record">
		<table class="corrTable">
			<thead>
			</thead>
			<tbody>
				<tr>
					<td>Äußerung</td>
					<td><input type="text" autocomplete="off" class="recordBox" id="correctionsRecordInput"
						value="<?php echo htmlspecialchars($record['Aeusserung']); ?>" /></td>
				</tr>
				<tr>
					<td>Klassifizierung</td>
					<td><select autocomplete="off" id="correctionsClassInput">
							<option
								<?php if ($record['Klassifizierung'] == 'B') echo ' selected';?>>B</option>
							<option
								<?php if ($record['Klassifizierung'] == 'P') echo ' selected';?>>P</option>
							<option
								<?php if ($record['Klassifizierung'] == 'M') echo ' selected';?>>M</option>
						</select>
					</td>
				</tr>
				<tr>
					<td>Bemerkung</td>
					<td><input type="text" autocomplete="off" class="recordBox" id="correctionsNotesInput"
						value="<?php echo htmlspecialchars($record['Bemerkung']); ?>" />
					</td>
				</tr>
				<tr>
					<td>Konzepte</td>
					<td>
						<select class="conceptSelect" id="corrConceptSelect"></select>
					</td>
				</tr>
			</tbody>
		</table>
		
		<input type="button" class="button button-primary" value="Neu tokenisieren" id="tokenize_button" />
	</div>
	
	<div id="tokenize_new" style="overflow: auto;">
	</div>
	
	<div id="ready">
	</div>
</div>
<?php
}

function va_create_token_table ($data, $id){
	global $va_xxx;
		
	$tokens = [];
	$ipa = [];
	$original = [];
	$concepts = [];
	$genderA = [];
	$groupC = [];
	
	$len = count($data['tokens']);
	$currentGroup = NULL;
	$countGroup = 0;
	
	foreach ($data['tokens'] as $index => $token){
		$endOfGroup = $index == $len - 1 || $data['tokens'][$index + 1]['Ebene_1'] > $token['Ebene_1'] || $data['tokens'][$index + 1]['Ebene_2'] > $token['Ebene_2'];
		
		$tdStyles = [];
		if($endOfGroup){
			$tdStyles[] = 'border-right: 1px solid black';
		}
		
		$tokens[] = '<td style="' . implode(';', $tdStyles) . '"><div class="corrField corrFieldFixd corrFieldToken">' . $token['Token'] . '</div></td>';
		$ipa[] = $token['IPA']? '<td style="' . implode(';', $tdStyles) . '"><div class="corrField corrFieldFixd corrFieldToken">' . $token['IPA'] . '</div></td>' : '<td></td>';
		$original[] = $token['Original']? '<td style="' . implode(';', $tdStyles) . '"><div class="corrField corrFieldFixd corrFieldToken">' . $token['Original'] . '</div></td>' : '<td></td>';
		$genderSelect = '<td style="' . implode(';', $tdStyles) . '"><select>';
		
		$genders = ['', 'm', 'f', 'n'];
		foreach ($genders as $gender){
			$genderSelect .= '<option' . ($gender == $token['Genus']? ' selected': '') . ' value="' . $gender . '">' . $gender . '</option>';
		}
		$genderSelect .= '</select></td>';
		$genderA[] = $genderSelect;
		
		if($token['Id_Tokengruppe'] != $currentGroup && $countGroup > 0){
			$groupC[] = va_correction_get_concept_div($data['global']['groups'][substr($currentGroup, 3)]['Konzepte'], 'border-right: 1px solid black;', $countGroup - 1);

			$countGroup = 0;
		}

		if($token['Id_Tokengruppe'] === null){
			$groupC[] = '<td style="' . implode(';', $tdStyles) . '" class="blocked fixed"></td>';
		}
		else {
			$countGroup += 2;
		}
		$currentGroup = $token['Id_Tokengruppe'];
		
		$concepts[] = va_correction_get_concept_div($token['Konzepte'], implode(';', $tdStyles));
		
		if($index < $len - 1 && !$endOfGroup){
			if($token['Trennzeichen'] == ' ')
				$token['Trennzeichen'] = '␣ ';
			
			if($token['Trennzeichen_IPA'] == ' ')
				$token['Trennzeichen_IPA'] = '␣ ';
			
			if($token['Trennzeichen_Original'] == ' ')
				$token['Trennzeichen_Original'] = '␣ ';
			
			$tokens[] = '<td><div class="corrField corrFieldFixd corrFieldSep">' . $token['Trennzeichen'] . '</div></td>';
			$ipa[] = $token['Trennzeichen_IPA'] !== null? '<td><div class="corrField corrFieldFixd corrFieldSep">' . $token['Trennzeichen_IPA'] . '</div></td>' : '<td></td>';
			$original[] = $token['Trennzeichen_Original'] !== null? '<td><div class="corrField corrFieldFixd corrFieldSep">' . $token['Trennzeichen_Original'] . '</div></td>' : '<td></td>';
			$concepts[] = '<td class="blocked fixed"></td>';
			$genderA[] = '<td class="blocked fixed"></td>';
		}
	}
	
	if($countGroup > 0){
		$groupC[] = va_correction_get_concept_div($data['global']['groups'][substr($currentGroup, 3)]['Konzepte'], 'border-right: 1px solid black;', $countGroup - 1);
	}
	
	$mtypes = $va_xxx->get_results($va_xxx->prepare('
		SELECT Id_morph_Typ, Orth, m.Genus, Sprache, Wortart, Affix
		FROM Tokens t 
			LEFT JOIN (
				SELECT Id_morph_Typ, Id_Token, Orth, Genus, Sprache, Quelle, Wortart, Affix
				FROM VTBL_Token_morph_Typ JOIN morph_Typen USING (Id_Morph_Typ)
		) m ON m.Id_Token = t.Id_Token AND m.Quelle = "VA"
		WHERE Id_Aeusserung = %d
		ORDER BY Ebene_1 ASC, Ebene_2 ASC, Ebene_3 ASC', $id), ARRAY_A);
	
	$mtypedivs = [];
	
	if(count($mtypes) == count($data['tokens'])){
		foreach ($mtypes as $index => $mtype){
			$endOfGroup = $index == $len - 1 || $data['tokens'][$index + 1]['Ebene_1'] > $data['tokens'][$index]['Ebene_1'] || $data['tokens'][$index + 1]['Ebene_2'] > $data['tokens'][$index]['Ebene_2'];
			$tdStyles = [];
			if($endOfGroup){
				$tdStyles[] = 'border-right: 1px solid black';
			}
			
			$type = va_format_lex_type($mtype['Orth'], $mtype['Sprache'], $mtype['Wortart'], $mtype['Genus'], $mtype['Affix']);		
			if($type){
				$mtypedivs[] = '<td data-type="type" style="' . implode(';', $tdStyles) . '"><div class="corrField corrFieldType">' . $type . '<span>X</span></div></td>';
			}
			else {
				$mtypedivs[] = '<td data-type="type" style="' . implode(';', $tdStyles) . '"><div class="corrField corrPlus" data-type="type">+</div></td>';
			}
			
			if($index < $len - 1 && !$endOfGroup){
				$mtypedivs[] = '<td class="blocked fixed"></td>';
			}
		}
	}
	?>

<table id="corrTokenTable">
  <thead>
  </thead>
  <tbody>
  	<tr>
  		<td style="border-right: 1px solid black;">Token</td>
  		<?php echo implode('', $tokens); ?>
  	</tr>
  	<tr>
  		<td style="border-right: 1px solid black;">IPA</td>
  		<?php echo implode('', $ipa); ?>
  	</tr>
  	<tr>
  		<td style="border-right: 1px solid black;">Original</td>
  		<?php echo implode('', $original); ?>
  	</tr>
  	  	<tr>
  		<td style="border-right: 1px solid black;">Genus</td>
  		<?php echo implode('', $genderA); ?>
  	</tr>
  	<tr id="conceptRow">
  		<td style="border-right: 1px solid black;">Konzepte</td>
  		<?php echo implode('', $concepts); ?>
  	</tr>
  	<tr id="typeRow">
  		<td style="border-right: 1px solid black;">Morph. Typ</td>
  		<?php echo implode('', $mtypedivs); ?>
  	</tr>
  	<tr>
  		<td style="border-right: 1px solid black;">Konzept Gruppe</td>
		<?php echo implode('', $groupC); ?>
  	</tr>
  	  	<tr>
  		<td style="border-right: 1px solid black;">Morph. Typ Gruppe</td>

  	</tr>
  	  	</tr>
  	  	<tr>
  		<td style="border-right: 1px solid black;">Genus Gruppe</td>

  	</tr>
  </tbody>
</table>

	<?php
}

function va_correction_get_concept_div ($concepts, $style, $colspan = 1){
	global $va_xxx;
	
	$conceptsToken = [];
	if(!empty($concepts))
		$conceptsToken = $va_xxx->get_results($va_xxx->prepare('SELECT Id_Konzept, IF(Name_D != "", Name_D, Beschreibung_D) AS Konzept, Grammatikalisch FROM Konzepte WHERE Id_Konzept IN ' . im_key_placeholder_list($concepts), $concepts), ARRAY_A);
		
	if (count($conceptsToken) == 0){
		$cstring = '<div class="corrField corrPlus" data-type="concept">+</div>';
	}
	else {
		$ids = [];
		$cnames = [];
		$gramm = '0';
		
		foreach ($conceptsToken as $cToken){
			$ids[] = $cToken['Id_Konzept'];
			$cnames[] = $cToken['Konzept'];
			
			if($cToken['Grammatikalisch'] == '1'){
				$gramm = '1';
			}
		}
		
		$cstring = '<div class="corrField corrFieldConcept" data-gramm="' . $gramm . '" data-id="'
		. implode('+', $ids) . '">'
		. implode(' / ', $cnames) . '<span>X</span></div>';
	}
	
	
	return '<td colspan="' . $colspan . '" data-type="concept" style="' . $style . '">' .$cstring . '</td>';
}
?>