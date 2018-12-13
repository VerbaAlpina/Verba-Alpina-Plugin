<?php
function search_glossary_errors() {
	echo '<br /><br /><input type="button" id="check_ex" value="Externe Links überprüfen" /><div id="externalLinksDiv"></div>';

	?>
	<script type="text/javascript">
	var running = false;
	jQuery(function (){
		jQuery("#check_ex").click(function (){
			if (!running){
				running = true;
				jQuery.post(ajaxurl, {
					"action" : "va",
					"namespace" : "util",
					"query" : "get_external_links"
				}, function (response){
					var links = JSON.parse(response);
	
					jQuery("#externalLinksDiv").html("<span id='curNum'>0</span> / " + links.length + " Links checked.<br />");

					var count = 0;
					for (let i = 0; i < links.length; i++){
						jQuery.post(ajaxurl, {
							"action" : "va",
							"namespace" : "util",
							"query" : "check_external_link",
							"link" : links[i][1]
						}, function (response){
							if (response == "1"){
								if(links[i][1].indexOf("verba-alpina.gwi.uni-muenchen.de") != -1){
									jQuery("#externalLinksDiv").append("<span>" + links[i][0] + " --- Global link to VerbaAlpina page (should be replaced by local link): " + links[i][1] + "</span><br />");
								}
							}
							else {
								jQuery("#externalLinksDiv").append("<span style='color: red'>" + links[i][0] + " --- Link invalid: " + links[i][1] + "</span><br />");
							}
	
							jQuery("#curNum").text(++count);
							if (count == links.length){
								running = false;
							}
						});
					}
				});
			}
		});
	});
	</script>
	<?php
	
	echo '<h2>Lokale Probleme</h2>';
	
	echo '<br />';
	
	va_glossary_error_search(true);
}

function va_glossary_error_search ($echo){
	global $va_xxx;
	
	$entries = $va_xxx -> get_results('SELECT * FROM Glossar', ARRAY_A);
	
	//Get all image URLs
	$media_path = get_home_path() . '/wp-content/uploads/';
	
	$pages = va_get_menu_items();
	$pages = array_merge($pages, ['CODEPAGE', 'EXPORT', 'START', 'Datenbank-Dokumentation']);

	$ignoreList = ['Konst'];
	$elinks = [];
	
	
	foreach ($entries as $entry) {
		foreach ($entry as $name => $field) {
			if (strpos($name, 'Erlaeuterung') === false){
				continue;
			}
				
			va_check_single_entry($echo, $entry['Terminus_D'], substr($name, strpos($name, '_') + 1), $field, $elinks, $pages, $ignoreList, $media_path, $entry['Intern']);
		}
	}
	
	$comments = $va_xxx->get_results('SELECT id, substring(language, 1, 1) as language, comment, Internal FROM im_comments', ARRAY_A);
	foreach ($comments as $comment){
		va_check_single_entry($echo, va_get_comment_title($comment['id'], 'D'), $comment['language'], $comment['comment'], $elinks, $pages, $ignoreList, $media_path, $comment['Internal']);
	}
	
	return $elinks;
}

function va_check_single_entry ($echo, $name, $lang, $field, &$elinks, $pages, $ignoreList, $media_path, $intern){
	global $va_xxx;
	
	$matches = array();
	preg_match_all('/(?<!-)\[\[(?:((?:[^|\]])*)\|)?([^\]]*)\]\]/', $field, $matches);
	
	$index = -1;
	foreach ($matches[2] as $link) {
		$index ++;
		
		if(preg_match('/^[0-9]*%$/', $link) ||
				strpos($link, 'Breite:') === 0 ||
				strpos($link, 'Höhe:') === 0 ||
				strpos($link, 'width=') === 0 ||
				strpos($link, 'height=') === 0 ||
				strpos($link, 'id=') === 0 ||
				strpos($link, 'db=') === 0){
					$link = $matches[1][$index];
		}
		
		if (strpos($matches[1][$index], 'Abk:') === 0){
			continue;
		}
		
		$link = str_replace('|Popup', '', $link);
		
		$link = trim($link);
		
		//Images
		if (strpos($link, 'Bild:') === 0) {
			$path = $media_path . substr($link, 5);
			if ($echo && !file_exists($path)) {
				echo $lang . ' --- ' . $name . ' --- IMAGE NOT FOUND: ' . substr($link, 5) . '<br />';
			}
		}
		
		//Pages
		else if (strpos($link, 'Seite:') === 0) {
			$pname = substr($link, 6);
			if ($echo && !in_array($pname, $pages)){
				echo $lang . ' --- ' . $name . ' --- PAGE NOT FOUND: ' . $pname . '<br />';
			}
		}
		
		//Maps
		else if (strpos($link, 'Karte:') === 0) {
			$map = substr($link, 6);
			if (is_numeric($map)){
				if ($echo && empty($va_xxx -> get_var($va_xxx->prepare("SELECT Id_Syn_Map FROM im_syn_maps WHERE Id_syn_map = %d", $map)))) {
					echo $lang . ' --- ' . $name . ' --- SYNOPTIC MAP NOT FOUND ID: ' . substr($link, 6) . '<br />';
				}
			}
			else {
				if ($echo && empty($va_xxx -> get_var($va_xxx->prepare("SELECT Id_Syn_Map FROM im_syn_maps WHERE Name = %s", $map)))) {
					echo $lang . ' --- ' . $name . ' --- SYNOPTIC MAP NOT FOUND NAME: ' . substr($link, 6) . '<br />';
				}
			}
		}
		
		//Lexicon
		else if (strpos($link, 'Kommentar:') === 0) {
			$id = substr($link, 10);
			$res = $va_xxx -> get_row($va_xxx->prepare("SELECT Id, Internal FROM im_comments WHERE Id = %s AND SUBSTRING(Language, 1, 1) = %s", $id, $lang), ARRAY_A);
			if ($echo && empty($res)) {
				echo $lang . ' --- ' . $name . ' --- COMMENT LINK NOT FOUND: ' . $id . '<br />';
			}
			else if ($echo && !$intern && $res['Internal']){
				echo $lang . ' --- ' . $name . ' --- LINK TO INTERNAL COMMENT: ' . $link . '<br />';
			}
		}
		
		else if (strpos($link, 'SQL:') === 0){
			//SKIP
		}
		
		else if (strpos($link, 'Bibl:') === 0) {
			if ($echo && empty($va_xxx -> get_var($va_xxx->prepare("SELECT Abkuerzung FROM Bibliographie WHERE Abkuerzung = %s", substr($link, 5))))) {
				echo $lang . ' --- ' . $name . ' --- BIBLIOGRAPHY ENTRY NOT FOUND: ' . substr($link, 5) . '<br />';
			}
		} else {
			foreach ($ignoreList as $ignore){
				if (strpos($link, $ignore . ':') === 0){
					continue 2;
				}
			}
			
			//Links
			if (strpos($link, "http") === 0 || strpos($link, "www") === 0) {
				$elinks[] = [$lang . ' --- ' . $name, $link];
			}
			else {
				$loclink = $va_xxx -> get_row($va_xxx->prepare("SELECT Id_Eintrag, Intern FROM Glossar WHERE Terminus_$lang = %s", $link), ARRAY_A);
				if ($echo && empty($loclink)) {
					echo $lang . ' --- ' . $name . ' --- LOCAL LINK NOT AVAILABLE: ' . $link . '<br />';
				}
				else if ($echo && !$intern && $loclink['Intern']){
					echo $lang . ' --- ' . $name . ' --- LOCAL LINK TO INTERNAL ENTRY: ' . $link . '<br />';
				}
			}
		}
	}
}

function va_get_external_links (){
	$links = va_glossary_error_search(false);
	$map = [];
	
	$links = array_filter($links, function ($e) use (&$map) {
		if (in_array($e[1], $map)){
			return false;
		}
		$map[] = $e[1];
		return true;
	});
	
	
	echo json_encode(array_values($links));
}

function va_check_external_link ($link){
	echo isDomainAvailible($link);
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