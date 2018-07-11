<?php

//TODO add read mode (wordpress capabilities)

function va_transcription () {
	$d_url = home_url('/dokumente/', 'https');
	
	$trules_image_path = plugins_url('/images/', __FILE__);
	
	$helpProblem = 'Dieser Button überspringt den aktuellen Informanten und markiert ihn speziell als problematisch. Später können die Problemfälle über den Modus Probleme im rechten Auswahlmenü eingetragen werden.';
	
	$helpVacat = 'Markiert in der Datenbank, dass es für diesen Informanten keine Belege gibt.';
	
	$helpScans = 'Zu grün markierten Stimuli sind Scans vorhanden, zu rot markierten Stimuli fehlen die entsprechenden Scans.';
	
	$folder = VA_PLUGIN_URL . '/backend/transcription/';
	
	global $va_xxx;
	$va_xxx->select('va_playground');//TODO Remove!!
	
	$base_chars = $va_xxx->get_results("SELECT * FROM Transkriptionsregeln WHERE Typ = 'Basiszeichen' ORDER BY Beta", ARRAY_A);
	$diacritics = $va_xxx->get_results("SELECT * FROM Transkriptionsregeln WHERE Typ = 'Diakritika' ORDER BY Gruppe ASC, Beta ASC", ARRAY_A);
	$special_chars = $va_xxx->get_results("SELECT * FROM Transkriptionsregeln WHERE Typ = 'Spezielle Zeichen'", ARRAY_A);
	$spaces = $va_xxx->get_results("SELECT * FROM Transkriptionsregeln WHERE Typ = 'Leerzeichen'", ARRAY_A);
	
	wp_enqueue_script('va_transcription_script', $folder . 'transcription.js', [], false, true);
	wp_enqueue_style('va_transcription_style', $folder . 'transcription.css');
	
	//Concepts for js
	$concepts = $va_xxx->get_results("SELECT Id_Konzept AS id, IF(Name_D != '', Name_D, Beschreibung_D) as text FROM Konzepte ORDER BY Text ASC", ARRAY_A);
	wp_localize_script('va_transcription_script', 'Concepts', $concepts);

	//Original codepage for js:
	$chars = $va_xxx->get_results("
        SELECT Beta, IF(Original regexp '^$', Hex_Original, Original) 
        FROM Codepage_Original 
        WHERE Original not regexp '^$' OR Hex_Original not regexp '^$'", ARRAY_N);
    
    $char_assoc = [];
    
    foreach ($chars as $char){
        $char_assoc[$char[0]] = $char[1];
    }
    wp_localize_script('va_transcription_script', 'Codepage', $char_assoc);

    ?>
	<script type="text/javascript">
		var url = "<?php echo $d_url;?>";
		var grammarText = <?php echo json_encode(va_build_grammar_for_original());?>;
	</script>
	
	<div id="iframeScanDiv">
		<iframe src="about:blank" id="iframeScan"></iframe>
	</div>
	<div id="iframeCodepageDiv">
		<h1>Basiszeichen</h1>
		
		<table>
			<thead>
				<tr>
					<th>Zeichen</th>
					<th>Beschreibung</th>
					<th>Beta-Code</th>
					<th>Kommentar</th>
				</tr>
			</thead>
			<tbody>
				<?php 
				foreach ($base_chars as $char){
					if ($char['Darstellung']){
						$img = $char['Darstellung'];
					}
					else {
						$img = '<img src="' . $trules_image_path . $char['Beta'] . '" />';
					}
					
					echo '<tr><td class="imageTranscriptionRule"><div>' . $img . '</div></td><td>' . $char['Beschreibung'] . '</td><td class="betaTranscriptionRule">' . $char['Beta'] . '</td><td>' . $char['Kommentar'] . '</td></tr>';
				}
				?>
			</tbody>
		</table>
		
				<h1>Diakritika</h1>
		
		<table>
			<thead>
				<tr>
					<th>Zeichen</th>
					<th>Beschreibung</th>
					<th>Beta-Code</th>
					<th>Kommentar</th>
					<th>Beispiel</th>
				</tr>
			</thead>
			<tbody>
				<?php 
				foreach ($diacritics as $char){
					if ($char['Darstellung']){
						$img = '<span style="position: relative; vertical-align: middle;">' . $char['Darstellung'] . '</span>';
					}
					else {
						$img = '<img src="' . $trules_image_path . $char['Beta'] . '" />';
					}
					
					echo '<tr><td class="imageTranscriptionRule"><div>' . $img . '</div></td><td>' . $char['Beschreibung'] . '</td><td class="betaTranscriptionRule">' . $char['Beta'] . '</td><td>' . $char['Kommentar'] . '</td><td>' . $char['Beta_Beispiel'] . '</td></tr>';
				}
				?>
			</tbody>
		</table>
	</div>
	<div id="enterTranscription">
	<?php
	$sql = 'SELECT DISTINCT Erhebung FROM Stimuli';
	$atlases = $va_xxx->get_col($sql);
	?>
		<select id="atlasSelection">
			<option value="-1"><?php _e('Choose atlas', 'verba-alpina');?></option>
			<?php
			foreach($atlases as $atlas){
				echo "<option value='$atlas'>$atlas</option>";
			}
			?>
		</select>
		
		<div id="mapSelectionDiv">
			<select id="mapSelection">
			</select>
			<?php echo va_get_info_symbol($helpScans);?>
		</div>
		
		<div id="informant_info" class="hidden_c" style="display: inline;">
			<span class="informant_fields"></span> - <?php _e('Informant no.', 'verba-alpina');?>
			<span class="informant_fields"></span>
		</div>
	
		<div style="float:right; display:inline;">
			 <input id="region" placeholder="<?php _e('Informant number(s)', 'verba-alpina');?>" style="background-color : #ffffff; display : inline;" />
			 <img  style="vertical-align: middle;" src="<?php echo VA_PLUGIN_URL . '/images/Help.png';?>" id="helpIconInformants" class="helpIcon" />
		</div>
		
		<div style="float:right; display:inline;">
	 		<select id="mode" style="background-color:#ffffff;">
				<option value="first"><?php _e('Initial recording', 'verba-alpina');?></option>
				<option value="correct"><?php _e('Correction', 'verba-alpina');?></option>
				<option value="problems"><?php _e('Problems', 'verba-alpina');?> </option>
		 	</select>
			<img  style="vertical-align: middle;" src="<?php echo VA_PLUGIN_URL . '/images/Help.png';?>" id="helpIconMode" class="helpIcon" />
		</div>
			
		<div class="hidden_coll" id="error"></div>
			
		<div class="informant_details hidden_c" id="input_fields">
			<h3 style="display: inline"><?php _e('Transcription', 'verba-alpina');?></h3>
			<a href="#" id="addRow" style="display: inline">(<?php _e('+ Add row', 'verba-alpina'); ?>)</a>
			
			<table id="inputTable"></table>
		
			<br />
			
			
			<input type="button" value="<?php _e('Insert', 'verba-alpina');?>" />
			
			<input type="button" value="vacat" />
			<?php echo va_get_info_symbol($helpVacat);?>
			
			<input type="button" value="<?php _e('Problem', 'verba-alpina');?>" />
			<?php echo va_get_info_symbol($helpProblem);?>
		
			<input type="button" id="addConcept" value="<?php _e('Create new concept', 'verba-alpina');?>" style="float: right" />
		</div>

		<div id="helpInformants" class="entry-content" style="display: none">
			Zur Auswahl von Erhebungspunkten stehen folgende Möglichkeiten zur Verfügung:
			<ul style="list-style : disc; padding-left : 1em;">
				<li>Angabe exakt eines Informanten (Beispiel: 252)</li>
				<li>
					Verwendung von Wildcards: % steht für eine beliebige Anzahl beliebiger Zeichen, _ steht für genau ein beliebiges Zeichen
					<ul style="list-style : circle; padding-left : 2em;">
						<li>8%  -> alle Punkte, die mit einer 8 beginnen (81,801,899)</li>
						<li>8_  -> alle Punkte bestehend aus zwei Ziffern, von denen die erste eine 8 ist (80,81,...)</li>
						<li>8__ -> alle Punkte bestehend aus drei Ziffern, von denen die erste eine 8 ist (800,801,...)</li>
						<li>87_ -> alle Punkte bestehend aus drei Ziffern, von denen die erste eine 8 und die zweite eine 7 ist (870,871,...)</li>
						<li>% -> alle Punkte</li>
					</ul>
				</li>
			</ul>
			ACHTUNG: *Nur* im Fall der Ersterfassung ist der Einsatz von Wildcards bei der Auswahl der Informantennummern sinnvoll. 
			Im Korrektur-Modus ist jeweils eine konkrete Informantennummer einzugeben.
		</div>
		
		<div id="helpMode" style="display: none">
			Es gibt folgende Modi:
			<ul style="list-style : disc; padding-left : 1em;">
				<li><b>Ersterfassung</b> Neue Daten erfassen</li>
				<li><b>Korrektur</b> Bestehende Daten korrigieren bzw. weitere Belege für einen bereits bearbeiteten Informanten hinzufügen</li>
				<li><b>Probleme</b> Bestehende Probleme bearbeiten</li>
			</ul>
		</div>
		
		<div id="helpConcepts" style="display: none">
			Auswahl des Konzepts / der Konzepte, die dieser Äußerung zugeordnet ist. In den meisten Fällen hängen die Konzepte nur vom jeweiligen Stimulus ab, 
			auf manchen Karten (z.B. AIS#1191_1) sind sie allerdings auch vom Informanten abhängig. Standardmäßig wird das Konzept angezeigt, das bisher am 
			häufigsten zugeordnet wurde. Nicht zutreffende Konzepte können über dieses Feld entfernt werden, fehlende Konzepte ausgewählt werden.
		</div>
	</div>
<?php
	va_echo_new_concept_fields('newConceptDialog');
}
?>