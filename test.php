<?php


add_action('wp_ajax_test_token_data_bsa', 'test_token_bsa');
add_action('wp_ajax_test_token_ipa', 'test_token_ipa');
add_action('wp_ajax_test_token_original', 'test_token_original');
add_action('wp_ajax_test_codepage_original', 'test_codepage_original');
add_action('wp_ajax_test_codepage_original2', 'test_codepage_original2');

function test_codepage_original2 (){
	global $va_xxx;
	
	$transl = json_decode(stripslashes($_POST['data']));
	
	/*$va_xxx->set_charset($va_xxx->dbh, 'utf8mb4');
	$va_xxx->query('SET NAMES utf8mb4');*/
	
	foreach ($transl as $t){
		$uni = '';
		$hex = preg_replace('/x([0-9a-fA-F][0-9a-fA-F][0-9a-fA-F][0-9a-fA-F]+)/u', '&#x$1;', $t[1]);
		
		if (strpos($t[0], 'u1') === false){
			$uni = mb_convert_encoding($hex,'UTF-8', 'HTML-ENTITIES');
		}
		
		$va_xxx->query("UPDATE Codepage_Original SET Original = '" . addslashes($uni) . "', Hex_Original = '" . addslashes($hex) . "' WHERE Beta = '" . addslashes($t[0]) . "'");
		echo $t[0] . ' -> ' . $hex . " | " . $uni . "\n";
	}
	
	die;
}

function test_codepage_original (){
	global $va_xxx;
	
	$tokens = $va_xxx->get_results("SELECT Beta FROM Codepage_Original WHERE Original regexp '^$' AND Hex_Original regexp '^$'", ARRAY_N);
	
	echo json_encode($tokens);
	
	die;
}

function fixHexUnicode($string)
{
    $json = json_encode($string);
    $json = str_replace('\\\\u',  '\\u', $json);
    $string  = json_decode($json);
 
    return $string;
}

/*
 * $res = $va_xxx->get_results("SELECT Erhebung, Beta, IPA, Hex_IPA from codepage_ipa WHERE IPA like '%˺%'", ARRAY_N);
	
	foreach ($res as $re) {
		foreach ($re as $r) {
			echo $r . ' ';
		}
		$va_xxx->query("UPDATE codepage_ipa set IPA = '" . str_replace('˺', fixHexUnicode("\u0306"), $re[2]) . "' WHERE Erhebung = '" . $re[0] . "' AND Beta = '" . $re[1] . "'");
		echo str_replace('˺', fixHexUnicode("\u0306"), $re[2]);
		echo "\n";
	}*/

function test_token_ipa (){
	global $va_xxx;
	
	$tokens = json_decode(stripslashes($_POST['data']));
	$missing_chars = array();

	foreach ($tokens as $token){
		$quelle = $token[0];
		$akzente = $va_xxx->get_results("SELECT Beta, IPA FROM Codepage_IPA WHERE Art = 'Akzent' AND Erhebung = '$quelle'", ARRAY_N);
		$vokale = $va_xxx->get_var("SELECT group_concat(DISTINCT SUBSTR(Beta, 1, 1) SEPARATOR '') FROM Codepage_IPA WHERE Art = 'Vokal' AND Erhebung = '$quelle'", 0, 0);
		$complete = true;		
		$result = '';

		foreach ($token[1] as $index => $character) {
			foreach ($akzente as $akzent) {
				$ak_qu = preg_quote($akzent[0], '/');
				$character = preg_replace_callback('/([' . $vokale . '][^' . $ak_qu . 'a-zA-Z]*)' . $ak_qu . '/', function ($matches) use (&$result, $akzent){
					$result .= $akzent[1];
					return $matches[1];
				}, $character);
			}
			
			$ipa = $va_xxx->get_results("SELECT IPA from Codepage_IPA WHERE Erhebung = '" . $quelle . "' AND Beta = '" . addcslashes($character, "\'") . "' AND IPA != ''", ARRAY_N);
			if($ipa[0][0]){
				$result .= $ipa[0][0];
			}
			else {
				if(!in_array($character, $missing_chars)){
					$missing_chars[] = $character;
					echo "Eintrag \"$character\" fehlt fuer \"$quelle\"!\n";
				}
				$complete = false;
			}
			
		}
		if($complete){
			echo implode('', $token[1]) . ' -> ' . $result . "\n";
			$va_xxx->query("UPDATE Tokens SET IPA = '" . addslashes($result) . "', Trennzeichen_IPA = (SELECT IPA FROM Codepage_IPA WHERE Art = 'Trennzeichen' AND Beta = Trennzeichen AND Erhebung = '$quelle')
			 WHERE EXISTS (SELECT * FROM Stimuli WHERE Stimuli.Id_Stimulus = Tokens.Id_Stimulus AND Erhebung = '$quelle') AND Token = '" . addslashes(implode('', $token[1])) . "'");
		}
	}

	die;
}

function test_token_original (){
	global $va_xxx;
	
	$tokens = json_decode(stripslashes($_POST['data']));

	foreach ($tokens as $token){
		$complete = true;
		$quelle = $token[0];
		$result = '';
		
		$has_span = false;
		
		foreach ($token[1] as $index => $character) {
			
			$original = $va_xxx->get_var("SELECT IF(Original REGEXP '^$', Hex_Original, Original) from Codepage_Original WHERE Beta = '" . addslashes($character) . "'");
			if($original){
				if(strpos($original, "<span style='position: absolute;") !== false){
					$result .= '<span style="position: relative">' . $original . '</span>';
					$has_span = true;
				}
				else {
					$result .= $original;
				}
			}
			else {
				echo "Eintrag \"$character\" fehlt!\n";
				$complete = false;
			}
			
		}
		if($complete){
			
			if ($has_span){
				$result = '<span style="position : relative">' . $result . '</span>'; //Add this to make the absolute spans work
			}
			
			echo implode('', $token[1]) . ' -> ' . $result . "\n";
			$va_xxx->query("UPDATE Tokens SET Original = '" . addslashes($result) . "', Trennzeichen_Original = (SELECT Original FROM Codepage_Original WHERE Beta = Trennzeichen)
			WHERE EXISTS (SELECT * FROM Stimuli WHERE Stimuli.Id_Stimulus = Tokens.Id_Stimulus AND Erhebung = '$quelle') AND Token = '" . addslashes(implode('', $token[1])) . "'");
		}
	}
	
	die;
}

function test_token_bsa (){
	global $va_xxx;
	
	$tokens = json_decode(stripslashes($_POST['data']));
	$missing_chars = array();

	foreach ($tokens as $token){
		$complete = true;
		$quelle = $token[0];
		$result = '';
		
		if($token[1] === '0'){
			$va_xxx->query("UPDATE Aeusserungen SET Aeusserung = '<vacat>', Bemerkung = 'BayDat-Transkription(0)' WHERE EXISTS (SELECT * FROM Stimuli WHERE Stimuli.Id_Stimulus = Aeusserungen.Id_Stimulus AND Erhebung = '$quelle') AND Aeusserung = '0'");
		}
		else {
			foreach ($token[1] as $index => $character) {
				$original = $va_xxx->get_results("SELECT betacode from Codepage_BayDat WHERE baydat = '" . addslashes($character) . "'", ARRAY_N);
				if($original[0][0]){
					$result .= $original[0][0];
				}
				else {
					if(!in_array($character, $missing_chars)){
						$missing_chars[] = $character;
						echo "Eintrag \"$character\" fehlt fuer \"$quelle\"!\n";
					}
					$complete = false;
				}
				
			}
			if($complete){
				echo implode('', $token[1]) . ' -> ' . $result . "\n";
				$va_xxx->query("UPDATE Aeusserungen SET Aeusserung = '" . addslashes($result) . "', Bemerkung = CONCAT(Bemerkung, 'BayDat-Transkription(' , '" . addslashes(implode('', $token[1])) . "' , ')')
				WHERE EXISTS (SELECT * FROM Stimuli WHERE Stimuli.Id_Stimulus = Aeusserungen.Id_Stimulus AND Erhebung = '$quelle') AND Aeusserung = '" . addslashes(implode('', $token[1])) . "'");
			}
		}
	}
	
	die;
}

function test_wiki (){
	va_create_wiki_link_list();
}

function test (){

?>
<script type="text/javascript" src="<?php echo VA_PLUGIN_URL; ?>/lib/peg-0.10.0.min.js"></script>
<script type="text/javascript">
	var parser, parser2, parserBSA;
	jQuery(function() {
		parser = peg.generate(jQuery("#grammar").val());
		parserBSA = peg.generate(jQuery("#grammarBSA").val())
		parser2 = peg.generate(jQuery("#grammar2").val());
	});

	function tokensPruefen() {
		jQuery.post(ajaxurl, {
			"action" : "token_ops",
			"stage" : "getTokens",
			"type" : "ipa"
		}, function(response) {
			var list = [];
			tokens = JSON.parse(response);
			jQuery("#ges").val(tokens.length);
			jQuery("#akt").val("0");
			jQuery("#result").val("");
			for (var i = 0, j = tokens.length; i < j; i++) {
				try {
					var tokenL = parser.parse(tokens[i][0]);
					list.push([tokens[i][1], tokenL]);
				} catch (err) {
					jQuery("#result").val(jQuery("#result").val() + "Token: " + tokens[i] + " ungültig!  (" + err + ")\n");
				}
				jQuery("#akt").val(jQuery("#akt").val() * 1 + 1);
			};
			jQuery.post(ajaxurl, {
				"action" : "test_token_ipa",
				"data" : JSON.stringify(list)
			}, function(response) {
				jQuery("#result").val(jQuery("#result").val() + response);
			});
		});
	}
	
	function tokensOriginal() {
		jQuery.post(ajaxurl, {
			"action" : "token_ops",
			"stage" : "getTokens",
			"type" : "original"
		}, function(response) {
			var list = [];
			tokens = JSON.parse(response);
			jQuery("#ges").val(tokens.length);
			jQuery("#akt").val("0");
			jQuery("#result").val("");
			for (var i = 0, j = tokens.length; i < j; i++) {
				try {
					var tokenL = parser.parse(tokens[i][0]);
					list.push([tokens[i][1], tokenL]);
				} catch (err) {
					jQuery("#result").val(jQuery("#result").val() + "Token: " + tokens[i] + " ungültig!  (" + err + ")\n");
				}
				jQuery("#akt").val(jQuery("#akt").val() * 1 + 1);
			};
			jQuery.post(ajaxurl, {
				"action" : "test_token_original",
				"data" : JSON.stringify(list)
			}, function(response) {
				jQuery("#result").val(jQuery("#result").val() + response);
			});
		});
	}
	
	function tokensBSA() {
		jQuery.post(ajaxurl, {
			"action" : "token_ops",
			"stage" : "getTokens",
			"type" : "bsa"
		}, function(response) {
			var list = [];
			tokens = JSON.parse(response);
			jQuery("#ges").val(tokens.length);
			jQuery("#akt").val("0");
			jQuery("#result").val("");
			for (var i = 0, j = tokens.length; i < j; i++) {
				try {
					var tokenL = parserBSA.parse(tokens[i][0]);
					list.push([tokens[i][1], tokenL]);
				} catch (err) {
					jQuery("#result").val(jQuery("#result").val() + "Äußerung: " + tokens[i] + " ungültig!  (" + err + ")\n");
				}
				jQuery("#akt").val(jQuery("#akt").val() * 1 + 1);
			};
			jQuery.post(ajaxurl, {
				"action" : "test_token_data_bsa",
				"data" : JSON.stringify(list)
			}, function(response) {
				jQuery("#result").val(jQuery("#result").val() + response);
			});
		});
	}
	
	function codepageOriginal (){
		jQuery("#result").val("");
		jQuery.post(ajaxurl, {
			"action" : "test_codepage_original"
		}, function(response) {
			var list = [];
			betas = JSON.parse(response);

			for (var i = 0, j = betas.length; i < j; i++) {
				try {
					var betaL = parser2.parse(betas[i][0]);
					list.push([betas[i][0], betaL]);
				} catch (err) {
					jQuery("#result").val(jQuery("#result").val() + "Beta: <<" + betas[i] + ">> ungültig!  (" + err + ")\n");
				}
			};
			jQuery.post(ajaxurl, {
				"action" : "test_codepage_original2",
				"data" : JSON.stringify(list)
			}, function(response) {
				jQuery("#result").val(jQuery("#result").val() + response);
			});
		});
	}

	var completeList = new Set();
	function combBSA (index){
		jQuery.post(ajaxurl, {
			"action" : "va",
			"namespace" : "test",
			"query" : "getPVA_BSA_Tokens",
			"index" : index
		}, function(response) {
			var result = JSON.parse(response);
			if(result.length > 0){
				for (var i = 0; i < result.length; i++){
					try {
						var tokenL = parserBSA.parse(result[i]);
						for (var j = 0; j < tokenL.length; j++){
							if(!completeList.has(tokenL[j])){
								completeList.add(tokenL[j]);
								jQuery("#result").val(jQuery("#result").val() + tokenL[j] + "\n");
							}
						}
					} catch (err) {
						jQuery("#errors").val(jQuery("#errors").val() + "Äußerung: " + result[i] + " ungültig!  (" + err + ")\n");
					}
				}
				combBSA(index + 1000);
			}
		});
	}
</script>

<br />

<input type="text" id="akt" value="" />
von
<input type="text" id="ges" value="" />

<div style="display : block">
	<textarea id="grammar"><?php echo file_get_contents(plugin_dir_path(__FILE__) . '/backend/auto/grammatik_transkr.txt'); ?></textarea>
</div>

<div style="display : block">
	<textarea id="grammar2"><?php echo file_get_contents(plugin_dir_path(__FILE__) . '/backend/auto/grammatik_original.txt'); ?></textarea>
</div>

<div style="display : block">
	<textarea id="grammarBSA"><?php echo file_get_contents(plugin_dir_path(__FILE__) . '/backend/auto/grammatik_bsa.txt'); ?></textarea>
</div>

<br />
<input type="button" value="Tokens IPA" onClick="tokensPruefen()" />
<input type="button" value="Tokens Original" onClick="tokensOriginal()" />
<input type="button" value="Äußerungen BSA" onClick="tokensBSA()" />
<input type="button" value="Codepage Original" onClick="codepageOriginal()" />
<input type="button" value="Kombination PVA_BSA" onClick="jQuery('#result').val('');jQuery('#errors').val('');combBSA(0)" />
<br />
<textarea rows="20" cols="100" id="result"></textarea>
<textarea rows="20" cols="100" id="errors"></textarea>

<br />
<br />
<br />

<?php

}

function communityMulti (){
	global $va_xxx;
	
	$comms = $va_xxx->get_results("SELECT Name, AsText(Geodaten) FROM Orte3 where Kategorie = 'Gemeinden' AND Alpenkonvention LIMIT 4000, 10000", ARRAY_N);
	
	$result = "<?xml version='1.0' encoding='UTF-8'?>\n\n<kml xmlns='http://www.opengis.net/kml/2.2'>\n<Document>\n";
	
	foreach($comms as $c){
		$result .= "<Placemark>\n<Name>" . $c[0] . "</Name>\n<Description></Description>\n<Polygon>\n<outerBoundaryIs>\n<LinearRing>\n<coordinates>\n";
		$coords = str_replace('))', ',', str_replace('POLYGON((', '', $c[1]));
		$firstInner = strpos($coords, '),(');
		if($firstInner !== false){
			$coords = substr_replace($coords, ",</coordinates>\n</LinearRing>\n</outerBoundaryIs>\n<innerBoundaryIs>\n<LinearRing>\n<coordinates>\n", $firstInner, 3);
			$coords = str_replace('),(', ",</coordinates>\n</LinearRing>\n</innerBoundaryIs>\n<innerBoundaryIs>\n<LinearRing>\n<coordinates>\n", $coords);
		}
		$coords = preg_replace('/(.*) (.*),/U', "$1,$2,0\n", $coords);
		
		if($firstInner !== false){
			$result .= $coords . "</coordinates>\n</LinearRing>\n</innerBoundaryIs>\n</Polygon>\n</Placemark>\n";
		}
		else {
			$result .= $coords . "</coordinates>\n</LinearRing>\n</outerBoundaryIs>\n</Polygon>\n</Placemark>\n";
		}
		
	}
	$result .= "</Document>\n</kml>";
	return $result;
}

function va_create_wiki_link_list (){
	global $va_xxx;
	
	$communities = $va_xxx->get_results("
		SELECT Orte.Id_Ort, Name, Wert 
		FROM Orte JOIN Orte_Tags USING (Id_Ort) LEFT JOIN Orte_Urls ou ON ou.Id_Ort = Orte.Id_Ort and Typ = 'WIKI_D' 
		WHERE Id_Kategorie = 62 AND Alpenkonvention AND Tag = 'LAND' and Beschreibung != 'Water body' AND Url IS NULL 
		limit 100", ARRAY_A);
	
	echo '<table>';
	
	foreach ($communities as $community){
		echo '<tr>';
		echo '<td>' . $community['Name'] . '</td>';
		echo '<td>' . $community['Wert'] . '</td>';
		
		$comm_name = str_replace(' ', '_', $community['Name']);
		$link = 'https://de.wikipedia.org/wiki/' . $comm_name;

		$headers = get_headers($link);
		if($headers[0] == 'HTTP/1.1 200 OK'){
			echo va_test_save_url($community['Id_Ort'], $link);
		}
		else {
			// Try other cases for each word:
			$parts = explode('_', $comm_name);
			$count = count($parts);
			$num_combinations = pow(2, $count);
			$found = false;
			for ($i = 0; $i < $num_combinations; $i++){
				$b = sprintf("%0"  .$count . "b", $i);
				$newNames = [];
				for ($j = 0; $j < $count; $j++){
					if($b[$j] == 1){
						$newNames[] = ucfirst($parts[$j]);
					}
					else {
						$newNames[] = lcfirst($parts[$j]);
					}
				}
				$new_link = 'https://de.wikipedia.org/wiki/' . implode($newNames, '_');
				$headers = get_headers($new_link);
				
				if($headers[0] == 'HTTP/1.1 200 OK'){
					echo va_test_save_url($community['Id_Ort'], $new_link);
					$found = true;
					break;
				}	
			}
			
			if(!$found)
				echo '<td style="color: red">' . $link . '</td>';
		}
		
		echo '</tr>';
	}
	
	echo '</table>';
}

function va_test_save_url ($id, $url){
	global $va_xxx;
	
	$va_xxx->insert('Orte_Urls', array('Id_Ort' => $id, 'Typ' => 'WIKI_D', 'Url' => $url));
	return '<td>' . $url. '</td>';
}
?>