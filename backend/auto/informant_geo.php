<?php
function va_informant_geo_page (){
	
	global $va_xxx;
	?>
	
	<script type="text/javascript">
	jQuery(function (){
		jQuery("#search_informants").click(function (){
			
			jQuery.post(ajaxurl, {
				action : "va",
				namespace : "util",
				query : "get_informant_list",
				source : jQuery("#source").val(),
				regions : jQuery("#regions").val()
			}, function (response){
				jQuery("#informants").html(response);
			});
		});
		
		jQuery("#regions").select2();
	});
	</script>
	
	<br />
	
	<select id="source">
	<?php
	$sources = $va_xxx->get_col('SELECT DISTINCT Erhebung FROM Informanten WHERE Georeferenz IS NULL OR Georeferenz = ""');
	foreach ($sources as $source){
		echo '<option value="' . htmlspecialchars($source) . '">' . htmlentities($source) . '</option>';
	}
	?>
	</select>
	<input type="button" class="button button-primary" value="Informanten suchen" id="search_informants" />
	
	
	<br />
	<br />
	
	Innerhalb von: <select id="regions" multiple>
	<?php
	$regions = $va_xxx->get_results('SELECT Name, Geonames FROM Orte WHERE Id_Kategorie in (60, 63) AND Geonames IS NOT NULL AND Geonames > 0 ORDER BY Name ASC', ARRAY_A);
	foreach ($regions as $region){
		echo '<option value="' . $region['Geonames'] . '">' . htmlentities($region['Name']) . '</option>';
	}
	?>
	</select>
	<div id="informants"></div>
	<?php
}

function va_get_informant_geo_data ($source, $regions){
	global $va_xxx;
	
	$informants = $va_xxx->get_results($va_xxx->prepare('SELECT Id_Informant, Ortsname FROM informanten WHERE (Georeferenz = "" OR Georeferenz IS NULL) AND Erhebung = %s and Ortsname != ""', $source), ARRAY_A);
	
	if ($regions){
		$api_urls = array_map(function ($e){
			$url = 'http://api.geonames.org/get?username=fzacherl&geonameId=' . $e;
			
			$curl = curl_init($url);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			$result = curl_exec($curl);
			curl_close($curl);
			$xml = simplexml_load_string($result);
			
			$fcode = $xml->xpath('//fcode')[0]->__toString();
			if ($fcode == 'PCLI'){
				$param = 'countryCode';
			}
			else {
				$param = 'adminCode' . substr($fcode, 3, 1);
			}
			
			$pcode = $xml->xpath('//' . $param)[0]->__toString();
			
			return 'http://api.geonames.org/search?username=fzacherl&name_equals=%%%&fcode=PPL&' . $param . '=' . $pcode;
		}, $regions);
	}
	else {
		$api_urls = ['http://api.geonames.org/search?username=fzacherl&name_equals=%%%&fcode=PPL'];
	}
	
	echo '<table>';
	foreach ($informants as $informant){
		$geo_data = [];
		
		$gd = false;
		
		foreach ($api_urls as $aurl){
			$aurl = str_replace('%%%', urlencode($informant['Ortsname']), $aurl);
			
			$curl = curl_init($aurl);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			$result = curl_exec($curl);
			curl_close($curl);
			$xml = simplexml_load_string($result);
			
			$lat = $xml->xpath('//lat');
			$lng = $xml->xpath('//lng');
			
			if (count($lat) == 1 && count($lng) == 1){
				$geo_data[] = [$lat[0]->__toString(), $lng[0]->__toString()];
			}
			else if (count($lat) > 1){
				$gd = 'Multiple found';
				break;
			}
		}
		
		if (!$gd){
			if (count($geo_data) == 1){
				$gd = $geo_data[0][0] . ', ' . $geo_data[0][1];
				$va_xxx->query($va_xxx->prepare('UPDATE Informanten SET Georeferenz = GeomFromText("POINT(' . $geo_data[0][1] . ' ' . $geo_data[0][0] . ')") WHERE Id_Informant = %d', $informant['Id_Informant']));
			}
			else if (count($geo_data) == 0){
				$gd = 'None found';
			}
			else {
				$gd = 'Multiple found';
			}
		}
		echo '<tr><td>' . $informant['Ortsname'] . '</td><td>' . $gd . '</td></tr>';
	}
	
	echo '</table>';
}