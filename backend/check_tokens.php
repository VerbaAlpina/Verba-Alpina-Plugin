<?php
function va_check_tokens (){
	?>
	<script type="text/javascript">
		var stop = true;
	
		jQuery(function (){
			jQuery("#va_check_tokens_button").click(function (){
				jQuery("#va_check_tokens_result").html("");
				jQuery("#va_check_tokens_errors").html("");
				stop = false;
				
				checkCall ();
			});

			jQuery("#va_stop_check_tokens_button").click(function (){
				stop = true;
			});
		});

		function checkCall (){
			jQuery.post(ajaxurl, {
				"action" : "va",
				"namespace" : "util",
				"query" : "checkTokens",
				"number" : 500
			}, function (response){
				response = JSON.parse(response);
				jQuery("#va_check_tokens_result").html(jQuery("#va_check_tokens_result").html() + response[0] + " records handled. " + response[1] + " errors.<br />");
				jQuery("#va_check_tokens_errors").html(jQuery("#va_check_tokens_errors").html() + response[2]);

				if (response[0] * 1 > response[1] * 1 && !stop){
					checkCall();
				}
			});
		}
	</script>
	
	<input type="button" id="va_check_tokens_button" class="button button-primary" value="Äußerungen prüfen" style="margin-top: 30px;" />
	<input type="button" id="va_stop_check_tokens_button" class="button button-primary" value="Stop" style="margin-top: 30px;" />
	
	<br />
	<br />
	
	<div>
		<div id="va_check_tokens_result" style="width: 500px; height: 800px; overflow: scroll; float: left;"></div>
		<div id="va_check_tokens_errors" style="width: 500px; height: 800px; overflow: scroll; float: left;"></div>
	</div>
	
	<?php
}

function va_check_tokens_call (&$db){

	$errors = '';
	$num_errors = 0;
	
	$unconnected_tokens = $db->get_results('SELECT Id_Token, Id_Aeusserung FROM Tokens JOIN Aeusserungen USING (ID_Aeusserung) WHERE Not Tokenisiert', ARRAY_N);
	foreach ($unconnected_tokens as $utoken){
		$errors .= "Token with Id " . $utoken[0] . " is connected with a record (" . $utoken[1] . ") that is not marked as tokenized!<br />";
	}
	
	$records = $db->get_results('
		SELECT Id_Aeusserung, Aeusserung, Erhebung
		FROM Aeusserungen a join Stimuli USING (Id_Stimulus)
		WHERE Tokenisiert AND Klassifizierung = "B" AND (Verifiziert_Am IS NULL OR Verifiziert_Am < Geaendert_Am OR Verifiziert_Am < (SELECT max(Geaendert_Am) FROM Tokens t WHERE t.Id_Aeusserung = a.Id_Aeusserung))
		LIMIT ' . $_POST['number'], ARRAY_A);
	
	foreach ($records as $record){
		$tokens = $db->get_results('SELECT Token, Ebene_1, Ebene_2, Ebene_3, Id_Aeusserung, Genus, Trennzeichen FROM Tokens WHERE Id_Aeusserung = ' . $record['Id_Aeusserung'] . ' ORDER BY Ebene_1, Ebene_2, Ebene_3', ARRAY_A);

		$res = va_check_record_via_tokens($record['Aeusserung'], $record['Id_Aeusserung'], $record['Erhebung'], $tokens, $db);
		
		if($res !== true){
			$errors .= $res . '<br />' . "\n";
			$num_errors++;
		}
		
	}
	
	echo json_encode([count($records), $num_errors, $errors]);
}

function va_check_record_via_tokens ($record, $id_record, $source, $tokens, &$db){

	$grey_parts = [];
	
	$matches = [];
	
	if($source != 'ALD-I' && $source != 'ALD-II'){
		preg_match_all('/<[^><]*>/', $record, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $match){
			if($match[1] > 1 && ($record[$match[1] - 1] != '\\' || $record[$match[1] - 2] != '\\')){
				$grey_parts = va_add_interval($grey_parts, [$match[1], $match[1] + strlen($match[0])]);
			}
		}
	}
	
	if ($source == 'CROWD'){
		preg_match_all('/\(.*\)/U', $record, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $match){
			$grey_parts = va_add_interval($grey_parts, [$match[1], $match[1] + strlen($match[0])]);
		}
		
		preg_match_all('/ \(/', $record, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $match){
			$grey_parts = va_add_interval($grey_parts, [$match[1], $match[1] + 1]);
		}
		
		preg_match_all('/\) /', $record, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $match){
			$grey_parts = va_add_interval($grey_parts,  [$match[1] + 1, $match[1] + 2]);
		}
	}
	
	if (substr($record, -1) == ' '){
		$grey_parts = va_add_interval($grey_parts, [strlen($record)  - 1, strlen($record)]);
	}
	
	if (substr($record, 0, 1) == ' '){
		$grey_parts = va_add_interval($grey_parts, [0, 1]);
	}
	
	preg_match_all('/ ([;<])/', $record, $matches, PREG_OFFSET_CAPTURE);
	foreach ($matches[0] as $match){
		$grey_parts = va_add_interval($grey_parts, [$match[1], $match[1] + 1]);
	}
	
	if($source != 'BSA'){
		preg_match_all('/ ,/', $record, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $match){
			$grey_parts = va_add_interval($grey_parts, [$match[1], $match[1] + 1]);
		}
	}
	
	preg_match_all('/([;>]) /', $record, $matches, PREG_OFFSET_CAPTURE);
	foreach ($matches[0] as $match){
		$grey_parts = va_add_interval($grey_parts,  [$match[1] + 1, $match[1] + 2]);
	}
	
	if($source != 'BSA'){
		preg_match_all('/, /', $record, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $match){
			$grey_parts = va_add_interval($grey_parts,  [$match[1] + 1, $match[1] + 2]);
		}
	}
	
	try {
		$record_stripped = va_strip_intervals($record, $grey_parts);
		$record_stripped = str_replace('\\\\;', ';', $record_stripped);
		$record_stripped = str_replace('\\\\,', ',', $record_stripped);
		$record_stripped = str_replace('\\\\>', '>', $record_stripped);
		$record_stripped = str_replace('\\\\<', '<', $record_stripped);
		
		$record_reconstructed = va_reconstruct_record_from_tokens($tokens);
		
		if($record_stripped == $record_reconstructed){
			//return 'Record "' . $record . '" valid.';
			$db->query('UPDATE Aeusserungen SET Verifiziert_Am = NOW() WHERE Id_Aeusserung = ' . $id_record);
			return true;
		}
		else {
			return '<span style="background: red;">Error, reconstructed record ' . $id_record . ' does not equal stored record</span><br/>' . 
				va_add_marking_spans($record, $grey_parts, 'style="background: grey"') . '<br />' . 
				htmlentities($record_stripped) . '<br/>' . 
				htmlentities($record_reconstructed);
		}
	}
	catch (Exception $e){
		return $e->getMessage();
	}
}

function va_interval_tests (){
	$tests = [
		[[[5,6], [9,11]], [3,4], [[3,4], [5,6], [9,11]]],
		[[[5,6], [9,11]], [3,5], [[3,6], [9,11]]],
		[[[5,6], [9,11]], [12,15], [[5,6], [9,11], [12,15]]],
		[[[5,6], [9,11]], [11,13], [[5,6], [9,13]]],
		[[[5,6], [9,11]], [7,8], [[5,6], [7,8], [9,11]]],
		[[[5,6], [9,11]], [6,10], [[5,11]]],
		[[[5,6], [9,11]], [3,7], [[3,7], [9,11]]],
		[[[5,6], [9,11]], [7,9], [[5,6], [7,11]]],
		[[[5,6], [9,11]], [7,99], [[5,6], [7,99]]],
		[[], [7,99], [[7,99]]],
		[[[9,24],[40,45],[46,83]], [24,25], [[9,25],[40,45],[46,83]]]
	];
	
	foreach ($tests as $test){
		$res = va_add_interval($test[0], $test[1]);
		if ($res == $test[2]){
			echo json_encode($test[0]) . ' + ' . json_encode($test[1]) . ' passed<br />';
		}
		else {
			echo json_encode($test[0]) . ' + ' . json_encode($test[1]) . ' soll: ' . json_encode($test[2]) . ' ist: ' . json_encode($res) . '<br />';
		}
	}
}

?>