<?php
function va_ajax_transcription (&$db){
	switch ($_POST['query']){
		
		case 'delete_locks':
			va_transcription_delete_locks($db);
		break;
			
		case 'update_informant':
			va_transcription_delete_locks($db);
			
			echo va_transcription_update_informant($db, $_POST['id_stimulus'], $_POST['mode'], $_POST['region']);
		break;
		
		case 'get_map_list':
			$sql = "SELECT Id_Stimulus, Erhebung, Karte, Nummer, left(Stimulus,50) as Stimulus
					FROM Stimuli
					WHERE Erhebung = '{$_POST['atlas']}'
					ORDER BY special_cast(karte)";
			
			$scans = va_transcription_list_scan_dir($_POST['atlas'] . '#');
			
			$result= $db->get_results($sql, ARRAY_A);
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
			echo $options;
		break;
		
		case 'get_new_row':
			echo va_transcription_get_table_row($_POST['index']);
		break;
	}
}

function va_transcription_list_scan_dir($atlas) {

	$scan_dir = get_home_path() . 'dokumente/scans/';
	$atlas = remove_accents($atlas);
	
	if ($handle = opendir($scan_dir)) {
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

	return $listing;
}

function va_transcription_update_informant (&$db, $id_stimulus, $mode, $region){
	global $admin;
	
	if($mode == 'first'){
		$modusWhere = 'a.Id_Aeusserung is null';
	}
	else if ($mode == 'correct')
		$modusWhere = 'a.Id_Aeusserung is not null';
	else {
		$modusWhere = "a.Aeusserung = '<problem>'";
	}
	
	$sql = $db->prepare("
	SELECT s.Erhebung, s.Karte, s.Nummer, s.Stimulus, i.Nummer as Informant_nummer, i.ortsname, s.Id_Stimulus, i.Id_Informant, a.Aeusserung, a.Id_Aeusserung, a.Klassifizierung, a.Ausdruck, a.Flexionsform, a.Erfasst_Von
	FROM `stimuli` s 
		join informanten i using (Erhebung) 
		left join aeusserungen a using (Id_Stimulus, Id_Informant)  
	WHERE 
		i.Alpenkonvention
		and Id_Stimulus = %d
		and $modusWhere
		and i.Nummer like %s
		and not exists (select * from Locks where Wert = CONCAT(s.Id_Stimulus, '|', i.Id_Informant))
	ORDER BY i.Position asc, Erfasst_am asc"
	, $id_stimulus, $region);

	$statements = $db->get_results($sql, ARRAY_A);
	
	//Use only statements with the first selected informant id
	$first_id = $statements[0]['Id_Informant'];
	foreach($statements as $index => $row){
		if($row['Id_Informant'] != $first_id){
			$break_index = $index;
			break;
		}
	}
	if($break_index)
		$results = array_slice($statements, 0, $break_index);
	else
		$results = $statements;

	 if($results[0]["Id_Stimulus"] && $results[0]["Id_Informant"]) {
		$sql="insert Locks (Tabelle, Wert, Gesperrt_Von, Zeit) 
				values ('Transkription', '".$results[0]["Id_Stimulus"]."|".$results[0]["Id_Informant"]."','".wp_get_current_user()->user_login."',now())";
		$db->query($sql);
		
		foreach ($results as $index => $row){
			if($mode == 'first' || $mode == 'extra' || $row['Aeusserung'] == '<vacat>' || $row['Aeusserung'] == '<problem>'){
				//Nur häufigstes Konzept
				$sql_concept = "SELECT Id_Konzept FROM Aeusserungen JOIN vtbl_aeusserung_konzept USING(Id_Aeusserung) WHERE Id_Stimulus = " . $row["Id_Stimulus"] . " GROUP BY Id_Konzept ORDER BY count(*) DESC LIMIT 1";
			}
			else {
				$sql_concept = "SELECT Id_Konzept FROM VTBL_Aeusserung_Konzept JOIN Aeusserungen USING(Id_Aeusserung) WHERE Id_Aeusserung = '" .$row["Id_Aeusserung"] . "'";
			}
			$conceptIds = $db->get_col($sql_concept);
			$results[$index]['Konzept_Ids'] = $conceptIds;
			
			$results[$index]['readonly'] = $mode == 'correct' && wp_get_current_user()->user_login !== $row['Erfasst_Von'] && $row['Erfasst_Von'] != '';
			ob_start();
			va_transcription_get_table_row($index, $mode == 'correct'? $row['Erfasst_Von'] : '', $results[$index]['readonly']);
			$results[$index]['html'] = ob_get_clean();
		}
		
		return json_encode($results);
	}
	
	$informant_exists = $db->get_var($db->prepare('SELECT Id_Informant FROM Informanten i JOIN Stimuli USING (Erhebung) WHERE i.Nummer like %s AND Id_Stimulus = %d', $region, $id_stimulus));
	if($informant_exists){
		if($mode == 'first'){
			if($region == '%'){
				return va_transcription_error_string(__('Everything transcribed!', 'verba-alpina'));
			}
			else {
				return va_transcription_error_string(__('Already transcribed!', 'verba-alpina'));
			}
		}
		else if($mode == 'correct'){
			return va_transcription_error_string(__('No transcription existent!', 'verba-alpina'));
		}
		else{
			return va_transcription_error_string(__('No more problems!', 'verba-alpina'));
		}
	}	
	else {
		return va_transcription_error_string(__('Informant number(s) not valid!', 'verba-alpina'));
	}
}

function va_transcription_error_string ($str){
	echo '<br><br><div style="color: red; font-size: 100%; font-style: bold;">' . $str . '</div><br>';
}

function va_transcription_delete_locks (&$db){
	$sql = "delete from Locks where Gesperrt_Von = '" . wp_get_current_user()->user_login . "' or hour(timediff(zeit,now())) > 0";
	$db->query($sql);
}

function va_transcription_get_table_row ($index, $author = '', $readonly = false){

	?>
<tr id="inputRow<?php echo $index; ?>">
	<td>
		<span class="spanNumber">
			<?php echo $index + 1;?>.) 
		</span>
	</td>
	
	<td>
		<input class="inputStatement" type="text" style="width: calc(60% - 8px)" />
		<span class="previewStatement" style="width: calc(40% - 8px); vertical-align: middle; line-height : 2; display:inline-block; text-overflow : ellipsis; overflow-x:hidden !important;"></span>
	</td>
	
	<td>
		<select class="classification">
			<option value="B"><?php _e('record', 'verba-alpina');?></option>
			<option value="P"><?php _e('phon. type', 'verba-alpina');?></option>
			<option value="M"><?php _e('morph. type', 'verba-alpina');?></option>
		</select>
	</td>

	<td>
		<select class="expression">
			<option value="nominal"><?php _e('nominal', 'verba-alpina');?></option>
			<option value="verbal"><?php _e('verbal', 'verba-alpina');?></option>
		</select>
	</td>
	
	<td>
		<span class="inflexionSpan" style="padding-left: 3px;">
			<input type="checkbox" class="inflexion" /><?php _e('Inflexion form', 'verba-alpina'); ?>
		</span>
	</td>
	
	<td>
		<select class="conceptList" data-placeholder="<?php _e('Choose Concept(s)', 'verba-alpina'); ?>" multiple style="width: 95%"></select>
		<img  style="vertical-align: middle;" src="<?php echo VA_PLUGIN_URL . '/images/Help.png';?>" id="helpIconConcepts" class="helpIcon" />
	</td>
	
	<td>
		<span class="authorSpan">
		<?php
			if($author){
				echo '<b>Erfasst&nbsp;von:&nbsp;</b>' . $author;
			}
			?>
		</span>
	</td>
	
	<td>
		<span class="deleteSpan">
			<?php 
			if($index > 0 && !$readonly){
				echo '<a class="remover" href="#">(' . __('Remove&nbsp;row', 'verba-alpina') . ')</a>'; 
			}
			?>
		</span>
	</td>
</tr>
<?php

}
?>