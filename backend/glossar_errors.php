<?php
function search_glossary_errors() {
	global $va_xxx;

	$entries = $va_xxx -> get_results('SELECT * FROM Glossar', ARRAY_A);
	//TODO kommentare und posts/seiten

	//Get all image URLs
	$media_path = get_home_path() . '/wp-content/uploads/';

	echo '<br /><br /><br />';

	foreach ($entries as $entry) {
		foreach ($entry as $name => $field) {
			if (strpos($name, 'Erlaeuterung') === false)
				continue;

			$matches = array();
			preg_match_all('/\[\[(?:((?:[^|\]])*)\|)?([^\]]*)\]\]/', $field, $matches);

			$index = -1;
			foreach ($matches[2] as $link) {
				$index ++;
				
				if(preg_match('/^[0-9]*%$/', $link) ||
					strpos($link, 'Breite:') === 0 ||
					strpos($link, 'Höhe:') === 0){
					$link = $matches[1][$index];
				}
				
				//Images
				if (strpos($link, 'Bild:') === 0) {
					$path = $media_path . substr($link, 5);
					if (!file_exists($path)) {
						echo $entry['Terminus_D'] . ' --- ' . $name . ' --- IMAGE NOT FOUND: ' . substr($link, 5) . '<br />';
					}
				}

				//Maps
				else if (strpos($link, 'Karte:') === 0) { //TODO use im_syn_maps
// 					if (empty($va_xxx -> get_var($va_xxx->prepare("SELECT Id_Themenkarte FROM Themenkarten WHERE Name = %s", substr($link, 6))))) {
// 						echo $entry['Terminus_D'] . ' --- ' . $name . ' --- SYNOPTIC MAP NOT FOUND: ' . substr($link, 6) . '<br />';
// 					}
				}

				//TODO Kommentare (oder nur anzeigen wenn existent?)
				
				else if (strpos($link, 'SQL:') === 0){
					//SKIP
				}
				
				else if (strpos($link, 'Bibl:') === 0) {
					if (empty($va_xxx -> get_var($va_xxx->prepare("SELECT Abkuerzung FROM Bibliographie WHERE Abkuerzung = %s", substr($link, 5))))) {
						echo $entry['Terminus_D'] . ' --- ' . $name . ' --- BIBLIOGRAPHY ENTRY NOT FOUND: ' . substr($link, 5) . '<br />';
					}
				} else {
					//Links
					if (strpos($link, "http") === 0) {
						/*if(!isDomainAvailible($link)){
							echo $entry['Terminus_D'] . ' --- ' . $name . ' --- LINK NOT AVAILABLE: ' . $link . '<br />';
						}*/
					}
					else {
						$lang = substr($name, strpos($name, '_') + 1);
						if (empty($va_xxx -> get_var($va_xxx->prepare("SELECT Id_Eintrag FROM Glossar WHERE Terminus_$lang = %s", $link)))) {
							echo $entry['Terminus_D'] . ' --- ' . $name . ' --- LOCAL LINK NOT AVAILABLE: ' . $link . '<br />';
						}
					}
				}
			}
		}
	}
}

function isDomainAvailible($domain) {
	//check, if a valid url is provided
	if (!filter_var($domain, FILTER_VALIDATE_URL)) {
		return false;
	}

	//initialize curl
	$curlInit = curl_init($domain);
	curl_setopt($curlInit, CURLOPT_CONNECTTIMEOUT, 10);
	curl_setopt($curlInit, CURLOPT_HEADER, true);
	curl_setopt($curlInit, CURLOPT_NOBODY, true);
	curl_setopt($curlInit, CURLOPT_RETURNTRANSFER, true);

	//get answer
	$response = curl_exec($curlInit);

	curl_close($curlInit);

	if ($response)
		return true;

	return false;
}
?>