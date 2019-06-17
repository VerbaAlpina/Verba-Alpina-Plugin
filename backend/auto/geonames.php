<?php 
function va_geonames_page (){
	global $va_xxx;
	
	$num_without = $va_xxx->get_var('SELECT count(*) from orte WHERE Id_Kategorie = 62 AND Geonames IS NULL');
	$num_notfound = $va_xxx->get_var('SELECT count(*) from orte WHERE Id_Kategorie = 62 AND Geonames = 0');
	?>
	
	<script type="text/javascript">
	var max_at_once = 10;

	jQuery(function (){
		jQuery("#find_comms").click(function (){
			var num_comms = jQuery("#num_comms").val() * 1;
			var num_max = jQuery("#commTable tr:first td:nth-child(2)").text() * 1;
			if (num_comms > num_max){
				num_comms = num_max;
			}

			jQuery("#handled").val("0");
			jQuery("#full").val(num_comms);
			jQuery("#error").text("");
			
			load_ids(Math.min(max_at_once, num_comms));
		});
	});

	function load_ids (num){
		jQuery.post(ajaxurl, {
			"action" : "va",
			"namespace" : "util",
			"query" : "find_geoname_id",
			"count" : num
		}, function (response){
			try {
				var data = JSON.parse(response);
				updateTable(data[0], data[1]);
				var handled = jQuery("#handled").val() * 1;
				var total = jQuery("#full").val() * 1;
				handled += num;
				jQuery("#handled").val(handled);
	
				if (handled < total){
					load_ids(Math.min(max_at_once, total - handled));
				}
			}
			catch (e){
				jQuery("#error").text(response);
			}
		});
	}

	function updateTable (without, notfound){
		jQuery("#commTable tr:first td:nth-child(2)").text(without);
		jQuery("#commTable tr:nth-child(2) td:nth-child(2)").text(notfound);
	}
	
	</script>
	
	<br />
	<table id="commTable">
		<tr><td>Gemeinden ohne ID</td><td><?php echo $num_without;?></td></tr>
		<tr><td>Gemeinden ohne Resultat</td><td><?php echo $num_notfound;?></td></tr>
	</table>
	
	<br /><br />
	
	<input type="text" id="num_comms" value="100" />
	<input type="button" id="find_comms" class="button button-primary" value="Geonames Ids für Gemeinden finden" />
	
	<br />
	<br />
	<input readonly id="handled" type="text" value="0"> von <input readonly id="full" type="text" value="0"> bearbeitet
	
	<br />
	<br />
	<div id="error"></div>
	
	<?php
}

function va_contry_tag_to_iso3166 ($val){
	switch ($val){
		case 'ita': return ['it', 3];
		case 'fra': return ['fr', 4];
		default: throw new Exception('Unknown country tag: ' . $val);
	}
}

function va_load_geonames_ids ($limit){
	global $va_xxx;
	
	$communities = $va_xxx->get_results('SELECT Id_Ort, Orte.Name, Wert FROM Orte JOIN Orte_Tags USING (Id_Ort) WHERE Id_Kategorie = 62 AND Tag="LAND" AND Geonames IS NULL LIMIT ' . $limit, ARRAY_N);
	
	try {
		foreach ($communities as $community){
			list ($lang, $depth) = va_contry_tag_to_iso3166($community[2]);
			$api_url = 'http://api.geonames.org/search?username=fzacherl&name_equals=' . urlencode($community[1]) . '&country=' . $lang . '&featureCode=ADM' . $depth;
			
			$curl = curl_init($api_url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			$result = curl_exec($curl);
			curl_close($curl);
			$xml = simplexml_load_string($result);
			
			$exceeded = $xml->xpath('status');
			if (count($exceeded) != 0){
				throw new Exception('Geonames credits exceeded!');
			}
			
			$id_element = $xml->xpath('geoname/geonameId');
			
			if (!$id_element || count($id_element) > 1){
				$id = 0;
			}
			else {
				$id = $id_element[0]->__toString();
			}
			
			
			$va_xxx->update('Orte', ['Geonames' => $id], ['Id_Ort' => $community[0]]);
		}
		
		$num_without = $va_xxx->get_var('SELECT count(*) from orte WHERE Id_Kategorie = 62 AND Geonames IS NULL');
		$num_notfound = $va_xxx->get_var('SELECT count(*) from orte WHERE Id_Kategorie = 62 AND Geonames = 0');
		
		return [$num_without, $num_notfound];
		
	}
	catch (Exception $e){
		echo $e->getMessage();
		die;
	}
}
?>