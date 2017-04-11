<?php

//Action Handler
add_action('wp_ajax_dbtranskr', 'transkrDB');



function transkrDB (){
	global $va_xxx;

	if(!current_user_can('va_transcription_tool_read'))
		break;
	
	switch ($_POST['type']) {
		case 'changeModus':
		case 'changeRegion':
		case 'selectKarte':
			$sql = "delete from datenerfassung_locks where gesperrt_von = '" . wp_get_current_user()->user_login . "' or hour(timediff(zeit,now())) > 0";
			$va_xxx->query($sql);
			
			echo updateInformant($_POST['Id_Stimulus'], $_POST['Modus'], $_POST['Region'], $_POST['Eintrag']);
		break;
		
		case 'updateTranscription':
			
			if(!current_user_can('va_transcription_tool_write'))
				break;
		
			if($_REQUEST["Aeusserung"] == "" || $_REQUEST["Id_Stimulus"] == "" && $_REQUEST["Id_Informant"] == ""){
				errorString('Kein Wert eingetragen!');
			}
			else{
				if($_REQUEST["Modus"] == 'extra' || $_REQUEST["Modus"] == 'first'){
				
					if(!$va_xxx->insert('Aeusserungen', 
						array(
						'Id_Stimulus' => intval($_REQUEST["Id_Stimulus"]),
						'Id_informant' => intval($_REQUEST["Id_Informant"]),
						'Aeusserung' => stripslashes($_REQUEST["Aeusserung"]),
						'Erfasst_von' => wp_get_current_user()->user_login,
						'Version' => 1,
						'Klassifizierung' => $_REQUEST["Klasse"]),

						array('%d','%d', '%s', '%s', '%d', '%s')))
						break;
					$aid = $va_xxx->insert_id;
				}
				else {
					//Update
					$va_xxx->update('Aeusserungen', array (
							'Aeusserung' => $_REQUEST["Aeusserung"],
							'Klassifizierung' => $_REQUEST["Klasse"],
						),
						array (
							'Id_Aeusserung' => $_REQUEST["Id_Aeusserung"],
						)
					);
					
					//Lösche eventuell vorhandene ältere Werte in VTBL_Aeusserung_Konzept
					$va_xxx->delete ('VTBL_Aeusserung_Konzept', array('Id_Aeusserung' => $_REQUEST["Id_Aeusserung"]));
					
					$aid = $_REQUEST["Id_Aeusserung"];
				}
				
				//Füge Werte zu VTBL_Aeusserung_Konzept und VTBL_Stimulus_Konzept hinzu
				if($_REQUEST["Aeusserung"] != '<vacat>' && $_REQUEST["Aeusserung"] != '<problem>'){
					$vsk = "INSERT IGNORE INTO VTBL_Stimulus_Konzept VALUES ";
					$vak = "INSERT INTO VTBL_Aeusserung_Konzept VALUES ";
					foreach($_REQUEST["Konzept_IDs"] as $id){
						$vsk .= '(' . $_REQUEST["Id_Stimulus"] . ', ' . $id . '),';
						$vak .= '(' . $aid . ', ' . $id . '),';
					}
					$vsk = substr($vsk, 0, -1);
					$vak = substr($vak, 0, -1);
					$va_xxx->query($vsk);
					$va_xxx->query($vak);
				}
				
				$sql="delete from datenerfassung_locks where gesperrt_von = '". wp_get_current_user()->user_login ."' or hour(timediff(zeit,now())) > 0";
				if(!$va_xxx->query($sql))
					break;

				echo updateInformant($_POST['Id_Stimulus'], $_POST['Modus'], $_POST['Region'], $_POST['Eintrag']);
				//$report = "<p>".$sql0." - gespeichert</p>";
			}
		break;
		
		case 'changeAtlas':
			echo getKartenliste($_POST['atlas']);
		break;
	
		default:
			errorString('Ungültiger AJAX-Aufruf!');
	}
	
	die();
}

function errorString ($str){
	echo '<br><br><div style="color: red; font-size: 100%; font-style: bold;">' . $str . '</div><br>';
}

function updateInformant ($id_stimulus, $modus, $region, $offset){
	global $va_xxx;
	global $admin;
	
	if($modus == 'first')
		$modusWhere = 'a.Id_Aeusserung is null';
	else if ($modus == 'correct')
		if($admin)
			$modusWhere = 'a.Id_Aeusserung is not null';
		else
			$modusWhere = "a.Id_Aeusserung is not null and Erfasst_von = '" . wp_get_current_user()->user_login . "'";
	else if ($modus == 'extra')
		$modusWhere = 'a.Id_Aeusserung is not null';
	else
		$modusWhere = "a.Aeusserung = '<problem>'";
	
	
	$sql = $va_xxx->prepare("
	SELECT s.Erhebung, s.Karte, s.Nummer, s.Stimulus, i.Nummer as Informant_nummer, i.ortsname, s.Id_Stimulus, i.Id_Informant, a.Aeusserung, a.Id_Aeusserung, a.Klassifizierung
	FROM `stimuli` s 
		join informanten i using (Erhebung) 
		left join aeusserungen a using (Id_Stimulus, Id_Informant) 
		left join datenerfassung_locks l using (Id_Stimulus, Id_Informant) 
	WHERE 
		i.Alpenkonvention
		and Id_Stimulus = %d
		and $modusWhere
		and i.Nummer like %s
		and l.Id_Stimulus is null
	ORDER BY informant_cast(i.Nummer) asc, Erfasst_am asc
	LIMIT %d,1", $id_stimulus, $region, $offset);
	 
	$row = $va_xxx->get_results($sql, ARRAY_A);

	 if($row[0]["Id_Stimulus"] != "" && $row[0]["Id_Informant"] != "") {
		$sql="insert into datenerfassung_locks (Id_Stimulus, Id_informant,gesperrt_von,zeit) 
				values (".$row[0]["Id_Stimulus"].",".$row[0]["Id_Informant"].",'".wp_get_current_user()->user_login."',now())";
		$va_xxx->query($sql);
		
		if($modus == 'first' || $modus == 'extra' || $row[0]['Aeusserung'] == '<vacat>' || $row[0]['Aeusserung'] == '<problem>'){
			//Nur häufigstes Konzept
			$sql_konzept = "SELECT Id_Konzept FROM Aeusserungen JOIN vtbl_aeusserung_konzept USING(Id_Aeusserung) WHERE Id_Stimulus = " . $row[0]["Id_Stimulus"] . " GROUP BY Id_Konzept ORDER BY count(*) DESC LIMIT 1";
			//$sql_konzept = "SELECT Id_Konzept FROM VTBL_Stimulus_Konzept JOIN Stimuli USING(Id_Stimulus) WHERE Id_Stimulus = '" . $row[0]["Id_Stimulus"] . "'";
		}
		else {	
			$sql_konzept = "SELECT Id_Konzept FROM VTBL_Aeusserung_Konzept JOIN Aeusserungen USING(Id_Aeusserung) WHERE Id_Aeusserung = '" .$row[0]["Id_Aeusserung"] . "'";
		}
		$konzeptIds = $va_xxx->get_results($sql_konzept, ARRAY_N);
		$a = array();
		foreach ($konzeptIds as $id){
			$a[] = $id[0];
		}
		
		$result = array('Erhebung' => $row[0]["Erhebung"], 'Karte' => $row[0]["Karte"], 'Stimulus' => $row[0]["Stimulus"], 'Informant_Nr' => $row[0]["Informant_nummer"], 'Ortsname' => $row[0]["ortsname"], 
		'Id_Stimulus' => $row[0]["Id_Stimulus"], 'Id_Informant' => $row[0]["Id_Informant"], 'Konzept_IDs' => $a, 'Aeusserung' => $row[0]['Aeusserung'], 'Id_Aeusserung' => $row[0]['Id_Aeusserung'], 'Klassifizierung' => $row[0]['Klassifizierung']);
		return json_encode($result);
	}
	return errorString(__('No value!', 'verba-alpina'));
}

function eingabe ($folder){
	
$d_url = home_url('/dokumente/', 'https');

$hilfeTranskription = file_get_contents(plugin_dir_path( __FILE__ ) . "Hilfe_tr.html");

$hilfeModus="Bitte wählen Sie, ob Sie Daten erfassen möchten, die noch gar nicht erfasst wurden (Ersterfassung), oder ob Sie bereits erfasste Belege (NUR SELBST ERFASSTE) korrigieren möchten (Korrektur) oder die bestehenden Probleme bearbeiten wollen.";

$hilfeKonzept = "Wählen Sie das Konzept / die Konzepte aus, die dieser Äußerung zugeordnert ist. In den meisten Fällen hängen die Konzepte nur vom jeweiligen Stimulus ab, auf manchen Karten (z.B. AIS#1191_1) sind sie allerdings auch vom Informanten abhängig. Es werden prinzpiell alle Konzepte angezeigt, die einem Stimulus zugeordnet sind. Nicht zutreffende Konzepte können über dieses Feld entfernt werden, fehlende Konzepte ausgewählt werden.";

$hilfeProblem = "Dieser Button überspringt den aktuellen Informanten und markiert ihn speziell als problematisch. Später können die Problemfälle über den Modus Probleme im rechten Auswahlmenü eingetragen werden.";

	$can_write = current_user_can('va_transcription_tool_write');
	
	$result = getKartenStub()."
	  
  <div style=\"float:right; display:inline;\">
	 <input name=\"Region\" id=\"Region\" placeholder=\"" . __('Informant number(s)', 'verba-alpina') . "\" style=\"background-color:#ffffff; \" onchange=\"tr_region = (this.value == ''? '%': this.value); ajax_info('changeRegion');\"  style=\"display:inline;\">
	 <img src=\"".$folder."info.png\" onmouseover=\"Tip('".trim(preg_replace('/\n/','<br />',$hilfeTranskription))."', CLICKSTICKY, true, CLOSEBTN, true, WIDTH, 750, OFFSETY, -20, TITLE, 'Hilfe')\" onmouseout=\"UnTip()\" height=\"15px\">
	 <input name=\"Eintrag_Nummer\" id=\"Eintrag\" placeholder=\"" . __('Entry number', 'verba-alpina') . "\" style=\"background-color:#ffffff; \" onchange=\"tr_eintrag = (this.value == ''? '1': this.value); ajax_info('changeRegion');\"  style=\"display:inline;\">
  </div>
	
  <div style=\"float:right; display:inline;\">
	 <select class=\"noChosen\" name=\"Modus\" id=\"Modus\" style=\"background-color:#ffffff; \" onchange=\"tr_modus = this.value; ajax_info('changeRegion');\">
		<option value=\"first\" style=\"\">" . __('Initial recording', 'verba-alpina') . "</option>
		<option value=\"correct\" style=\"\">" . __('Correction', 'verba-alpina') . "</option>
		<option value=\"extra\" style=\"\">" . __('Additional', 'verba-alpina') . "</option>
		<option value=\"problems\">" . __('Problems', 'verba-alpina') ." </option>
	 </select>
	<img src=\"".$folder."info.png\" onmouseover=\"Tip('".trim(preg_replace('/\n/','<br />',$hilfeModus))."', CLICKSTICKY, true, CLOSEBTN, true, WIDTH, 750, OFFSETY, -20, TITLE, 'Hilfe')\" onmouseout=\"UnTip()\" height=\"15px\">
  </div>

  <div class=\"hidden_coll\" id=\"fehler\"></div>
  
  <div class=\"informant_details hidden_c\" id=\"input_fields\">
		<div id=\"Informant_Info\">
			<span class=\"informant_fields\">
				  - 
			</span> - " . __('Informant no.', 'verba-alpina') . " 
			<span class=\"informant_fields\">
				
			</span> ()
		</div>
 
		" . __('Transcription', 'verba-alpina') . " <input id=\"inputAeusserung\" name=\"aeusserung\" size=\"50\" type=\"text\" /> 
		<input type=\"button\" value=\"" . __('Insert', 'verba-alpina') . "\" onClick=\"writeAeusserung(getElementById('inputAeusserung').value)\" " . ($can_write? '' : ' disabled') . " />
		<input type=\"button\" value=\"<vacat>\"  onClick=\"writeAeusserung('<vacat>')\" " . ($can_write? '' : ' disabled') . " />
		<input type=\"button\" value=\"" . __('Problem', 'verba-alpina') . "\"  onClick=\"writeAeusserung('<problem>')\" " . ($can_write? '' : ' disabled') . " />
		<img src=\"".$folder."info.png\" onmouseover=\"Tip('".trim(preg_replace('/\n/','<br />',$hilfeProblem))."', CLICKSTICKY, true, CLOSEBTN, true, WIDTH, 750, OFFSETY, -20, TITLE, 'Hilfe')\" onmouseout=\"UnTip()\" height=\"15px\">
		
		<select class=\"noChosen\" id=\"Klasse\">
			<option value=\"B\">" .  __('record', 'verba-alpina') . "</option>
			<option value=\"P\">" . __('phonetic type', 'verba-alpina') . "</option>
			<option value=\"M\">" . __('morphological type', 'verba-alpina') . "</option>
		</select>
		
		<div style=\"display:inline;\">
	 <br />" . __('Assigned concepts', 'verba-alpina') . " <img src=\"".$folder."info.png\" onmouseover=\"Tip('".trim(preg_replace('/\n/','<br />',$hilfeKonzept))."', CLICKSTICKY, true, CLOSEBTN, true, WIDTH, 750, OFFSETY, -20, TITLE, 'Hilfe')\" onmouseout=\"UnTip()\" height=\"15px\">" 
	 . im_table_select('Konzepte', 'Id_Konzept', array('Name_D', 'Beschreibung_D'), 'konzepteL', array (
	 	'placeholder' => __('Choose Concept(s)', 'verba-alpina'),
	 	'width' => '98%',
	 	'multiple_values' => true,
	 	'new_values_info' => new IM_Row_Information('Konzepte', array(
	 			new IM_Field_Information('Name_D', 'V', false),
	 			new IM_Field_Information('Beschreibung_D', 'V', true),
	 			new IM_Field_Information('Kategorie', 'E', true),
	 			new IM_Field_Information('Hauptkategorie', 'E', true)
	 	), 'Angelegt_Von')
	 )) .
	 "
  </div>
	 </div>
	 
	 <script type=\"text/javascript\">
		var url = '" . $d_url . "';
	 </script>
	 <script src=\"". $folder ."wz_tooltip.js\" type=\"text/javascript\"></script>
	 <script src=\"". $folder ."transkr.js\" type=\"text/javascript\"></script>
	 ";
	 
	return $result;
}


function getKartenStub() {
  global $va_xxx;

	$sql = 'SELECT DISTINCT Erhebung FROM Stimuli';
		
	$atlanten = $va_xxx->get_results($sql, ARRAY_N);
	
	$auswahl = '<select class="noChosen" id="atlasAuswahl" onChange="atlasChanged (this.value)"><option value="-1">' . __('Choose atlas', 'verba-alpina') . '</option>';
	
	foreach($atlanten as $atlas){
		$auswahl .= "<option value='$atlas[0]'>$atlas[0]</option>";
	}
	
	$auswahl .= '</select>';
	
	$auswahl .="
      <select class=\"noChosen\" name=\"Karte\" id=\"Karte\" style=\"background-color:#fe7266; max-width:30%; display : none\" onchange=\"mapChanged (this.value)\">
      <option value=\"\">" . __('Chose map', 'verba-alpina') . "</option></select>
	";
   return $auswahl;
}

function getKartenliste ($atlas){
	
	global $va_xxx;
	$scan_dir = get_home_path() . 'dokumente/scans/';
	
	$sql = "SELECT Id_Stimulus, Erhebung, Karte, Nummer, left(stimulus,50) as Stimulus
			FROM Stimuli
			WHERE Erhebung = '$atlas'
			ORDER BY special_cast(karte)";
	
	$scans = listdir($scan_dir, $atlas . '#');
	
	$result= $va_xxx->get_results($sql, ARRAY_A);
	foreach($result as $row) {
		$scan = $scans[$row['Karte']];
		if($scan) {
			$backgroundcolor="#80FF80";
			$value = $row['Id_Stimulus'];
		}
		else {
			$backgroundcolor="#fe7266";
			$value = "None";
		}
		$nameKarte = $row['Erhebung'] . '#' . $row['Karte'] . '_' . $row['Nummer'] . ' (' . $row['Stimulus'] . ')';
		$options .= "<option value=\"". ($value == 'None'? 'None' : $value . '|' . $scan) . "\" style=\"background-color:".$backgroundcolor."\">" .  $nameKarte . "</option>\n";
	}
	return $options;
}

function listdir($dir, $atlas) {

	$atlas = remove_accents($atlas);
	
	if ($handle = opendir($dir)) {
		while (false !== ($file = readdir($handle))) {
			
			if ($file != "." && $file != ".." && mb_strpos($file, $atlas) === 0) {
				$pos_hash = mb_strpos($file, '#');
				$pos_dot = mb_strpos($file, '.pdf');
				$map = mb_substr($file, $pos_hash + 1, $pos_dot - $pos_hash - 1); 

				if(mb_strpos($map, '-') !== false){
					$numbers = explode('-', $map);
					if(ctype_digit($numbers[0]) && ctype_digit($numbers[1])){
						$start = (int) $numbers[0];
						$end = (int) $numbers[1];
						for ($i = $start; $i <= $end; $i++){
							$listing[$i] = $file;
						}
					}
					else {
						$listing[$map] = $file;
					}
				}
				else {
					$listing[$map] = $file;
				}
			}
		}
		closedir($handle);
	}
	//sort($listing);
	return $listing;
}

?>