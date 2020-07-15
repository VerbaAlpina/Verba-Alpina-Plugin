<?php 
function va_codepage_page (){
	?>
	<script type="text/javascript">
	var apis;
	
	jQuery(function (){
		jQuery("#atlasSelect").change(function (){
			var val = jQuery(this).val();

			if(val == "0"){
				jQuery("#tableArea").empty();
				return;
			}
			
			jQuery.post (ajax_object.ajaxurl, {
				"action" : "va",
				"namespace" : "get_codepage",
				"atlas" : val,
				"dbname" : "va_xxx"
			}, function (response){
				if(apis){
					for (let i = 0; i < apis.length; i++){
						apis[i].destroy(true);
					}
				}
				jQuery("#tableArea").html(response);
				apis = addBiblioQTips(jQuery("#tableArea"));
			});
		});
	});
	</script>
	<?php
	
	global $va_xxx;
	global $Ue;
	
	$atlases = $va_xxx->get_col($va_xxx->prepare('SELECT DISTINCT Erhebung FROM Codepage_IPA WHERE Erhebung != "" AND Erhebung != %s ORDER BY Erhebung', 'BSA_alt'));
	$select = '<select id="atlasSelect" autocomplete="off"><option selected value="0">' . '--- ' . $Ue['QUELLENWAHL'] . ' ---' . '</option>';
	
	foreach ($atlases as $atlas){
		$select .= '<option value="' . $atlas . '">' . ($atlas === 'ALD-II'? 'ALD': $atlas) . '</option>';
	}
	
	$select .= '</select><br /><br />';
	
	echo $select;
	
	echo '<div id="tableArea" class="entry-content"></div>';
}

function va_get_codepage_data($atlas){
	global $va_xxx;
	global $Ue;
	
	$va_beta = $va_xxx->get_var($va_xxx->prepare('SELECT VA_Beta FROM Bibliographie WHERE Abkuerzung = %s', $atlas)) == '1';
	
	$originale = va_two_dim_to_assoc($va_xxx->get_results('SELECT Beta, Original FROM Codepage_Original', ARRAY_N));
	$ipas = $va_xxx->get_results($va_xxx->prepare('
		SELECT Beta, IPA 
		FROM Codepage_IPA 
		WHERE 
			Erhebung = %s AND 
			Art != "Trennzeichen" AND 
			Art != "Akzent" AND
			IPA != ""
		ORDER BY IPA ASC, Beta ASC', $atlas), ARRAY_A);
	
	$res = '';
	
	if (!$va_beta){
		$note = str_replace('%s', '[[Bibl:' . $atlas . ']]', $Ue['CODEPAGE_NICHT_BETA']);
		parseSyntax($note);
		$res .= '<div style="margin: 20px;">' . $note . '</div>';
	}
	
	$res .= '<table class="easy-table" style="font-family : arial unicode; table-layout: fixed;"><tr><th style="width: 45%;">' . $Ue['BETA_CODES'] . '</th>';
	if($va_beta)
		$res .= '<th style="width: 45%;">' . $Ue['ORIGINAL'] . '</th>';
	$res .= '<th style="width: 10%;">' . $Ue['IPA_ZEICHEN'] . '</th></tr>';
	
	$last_ipa = '';
	$beta_list = [];
	$original_list = [];
	$len = count($ipas);
	
	foreach ($ipas as $index => $ipa){
		$beta_list[] = $ipa['Beta'];
		
		$original = isset($originale[$ipa['Beta']])? $originale[$ipa['Beta']] : '';
		$original_list[] = $original? '<span style="position: relative;">' . $original . '</span>': '(?)';
		
		if($last_ipa == '')
			$last_ipa = $ipa['IPA'];
		
		if($index == $len - 1 || $ipas[$index + 1]['IPA'] != $last_ipa){
			$res .= 
				'<tr><td style="font-size: 200%; line-height: 2;">' . implode(', ', $beta_list) . 
				($va_beta? '</td><td style="font-size: 200%; line-height: 2;">' . implode(', ', $original_list) : '') . 
				'</td><td style="font-size: 200%; line-height: 2;">' . $last_ipa . '</td></tr>';
			$last_ipa = '';
			$beta_list = [];			
			$original_list = [];
		}
	}
	$res .= '</table>';
	
	return $res;
}
?>