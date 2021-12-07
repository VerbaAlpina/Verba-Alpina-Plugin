<?php

function va_zooniverse_replacements ($str){
	$str = str_replace(' ;', ';', $str);
	$str = str_replace(' ,', ',', $str);
	$str = str_replace(', ', ',', $str);
	$str = str_replace('; ', ';', $str);
	$str = str_replace(' <', '<', $str);
	$str = str_replace('<p>', '<p.>', $str);
	$str = str_replace('{)/}', ')/', $str);
	$str = str_replace('  ', ' ', $str);
	
	return $str;
}

function va_zooniverse_results (){
	
	echo '<script>function showDetails (num){jQuery(".details").toggle(false); jQuery("#details" + num).toggle(true);}</script>';
	echo '<style>del {background:lightsalmon; text-decoration: none;} ins {background: lightgreen}</style>';
	
	$perc_use = 50;
	
	echo '<div class="entry-content">';
	
	global $va_xxx;
	$va_xxx->select('va_temp');
	
	$map_sects = $va_xxx->get_col('SELECT distinct subject_id FROM zooniverse_correct');
	
	echo '<table><thead><tr><th>Ausschnitt</th><th>Ohne Belegnummer</th><th>Belege vollständig</th><th>Belege nutzermarkiert</th><th>transkribiert</th><th>korrekt</th><th>Ergebnis</th><th></th></thead><tbody>';
	
	$dtables = [];
	foreach ($map_sects as $num_sect => $sect){
		$solutions = $va_xxx->get_results('SELECT number, transcription, url FROM zooniverse_correct WHERE subject_id = "' . $sect . '"', ARRAY_A);
		$data = $va_xxx->get_results('SELECT num_box, map_number, transcription FROM zooniverse_2021_08_10 WHERE subject_id = "' . $sect . '"', ARRAY_A);
		$data = array_map(function ($e){$e['transcription'] = va_zooniverse_replacements($e['transcription']); return $e;}, $data);
		$num_users = $va_xxx->get_var('SELECT count(DISTINCT user_id) FROM zooniverse_2021_08_10 WHERE subject_id = "' . $sect . '" AND map_number != ""');
		
		$correct = [];
		foreach ($solutions as $sol){
			$correct[$sol['number']] = $sol['transcription'];
		}
		
		$missing = 0;
		$num = 0;
		$transcribed = 0;
		$corr_transcribed = 0;
		$user_data = [];
		$user_transcrs = [];

		foreach ($data as $row){
			if ($row['map_number']){
				if (!isset($user_data[$row['map_number']])){
					$user_data[$row['map_number']] = [];
				}
				
				$arr = [];
				
				if ($row['transcription']){
					$transcribed++;
					
					if (!isset($user_data[$row['map_number']][$row['transcription']])){
						$exists = array_key_exists($row['map_number'], $correct);
						$ct = $exists? $row['transcription'] == $correct[$row['map_number']]: false;
						
						if ($ct){
							$corr_transcribed++;
						}
						
						$user_data[$row['map_number']][$row['transcription']] = ['correct' => $ct, 'count' => 1, 'solution' => $correct[$row['map_number']]];
					}
					else {
						if ($user_data[$row['map_number']][$row['transcription']]['correct']){
							$corr_transcribed++;
						}
						$user_data[$row['map_number']][$row['transcription']]['count']++;
					}
				}
				else {
					if (!isset($user_data[$row['map_number']]['KEINE TRANSKRIPTION'])){
						$user_data[$row['map_number']]['KEINE TRANSKRIPTION'] = ['correct' => false, 'count' => 0];
					}
					$user_data[$row['map_number']]['KEINE TRANSKRIPTION']['count']++;
				}
			}
			else {
				$missing ++;
			}
			
			$num++;
		}
		
		$detail_table = '<img src="' . $solutions[0]['url'] . '"><table><thead><tr><th>Nummer</th><th>vollständig</th><th>Variante</th><th>Unterschied</th><th>Anzahl</th></thead><tbody>';
		
		$last_num = null;
		$bgcolors = ['white', 'lightgrey'];
		$bgindex = 1;
		
		$num_corr = 0;
		$num_unsure = 0;
		$num_wrong = 0;
		
		$nums_missing = array_keys($correct);
		
		foreach ($user_data as $udnum => $urow){

			uasort($urow, function ($e1, $e2){return $e1['count'] > $e2['count']? -1: 1;});
			
			$sum = 0;
			foreach ($urow as $udata){
				$sum += $udata['count'];
			}
			
			if ($sum / $num_users * 100 > $perc_use){
				
				if (($imnum = array_search($udnum, $nums_missing)) !== false) {
					unset($nums_missing[$imnum]);
				}
				
				$without_empty = [];
				foreach ($urow as $ukey => $uval){
					if ($ukey != 'KEINE TRANSKRIPTION'){
						$without_empty[$ukey] = $uval;
					}
				}
				
				if (count($without_empty) > 0){
					$next_index = 1;
					$last_index = 0;
					$akeys = array_keys($without_empty);
					while ($next_index < count($without_empty) && $without_empty[$akeys[$next_index]]['count'] == $without_empty[$akeys[0]]['count']){
						$next_index++;
					}
					
					if ($next_index === 1){
						if ($without_empty[$akeys[0]]['correct']){
							$num_corr++;
						}
						else {
							$num_wrong++;
						}
					}
					else {
						$corr_found = false;
						for ($i = 0; $i < $next_index; $i++){
							if ($without_empty[$akeys[$i]]['correct']){
								$corr_found = true;
								break;
							}
						}
						error_log($corr_found);
						if ($corr_found){
							$num_unsure++;
						}
						else {
							$num_wrong++;
						}
					}
					
				}
				
			}
			
			$bgindex = ($bgindex + 1) % count($bgcolors);
			
			$detail_table .= '<tr style="background: ' . $bgcolors[$bgindex] . '"><td>' . $udnum . '</td><td>' . (array_key_exists($udnum, $correct)? 'Ja': 'Nein') . '</td>';
			$first = true;
			if (count($urow) === 0){
				$detail_table .= '<td></td><td></td>';
			}
			else {
				foreach ($urow as $transcr => $udata){
					if ($first){
						$first = false;
					}
					else {
						$detail_table .= '</tr><tr style="background: ' . $bgcolors[$bgindex] . '"><td></td><td></td>';
					}
					
					$detail_table .= '<td style="background: ' . ($udata['correct']? 'lightgreen': 'lightsalmon') . ';">' . htmlentities($transcr) . '</td><td>' . (!$udata['correct'] && isset($udata['solution'])? htmlDiff(htmlentities($transcr), htmlentities($udata['solution'])) :  '') . '</td><td>' . $udata['count'] . '</td>';
				}
			}
			
			$detail_table .= '</tr>';
		}
		
		$num_wrong += count($nums_missing);
		
		$detail_table .= '</tbody></table>';
		$dtables[$num_sect] = $detail_table;
		
		
		echo '<tr><td>' . $sect . '</td><td>' . $missing . ' / ' . $num . '</td><td>' . count($correct) . '</td><td>' . count($user_data) . '</td><td>' . $transcribed . ' / ' . ($num - $missing) . '</td><td>' . $corr_transcribed . ' / ' . $transcribed . '</td><td><span style="color: green"><b>' . $num_corr . '</b></span> / <span style="color: orange"><b>' . $num_unsure . '</b></span> / <span style="color: red"><b>' . $num_wrong . '</b></span></td><td><a href="javascript:showDetails(' . $num_sect . ')">Details</a></td></tr>';
	}
	echo '</tbody></table>';

	echo '<b>Ohne Belegnummer</b>: Gibt an, bei wie vielen Nutzereingaben die Belegnummer fehlt (nur Rahmen, keine Angabe der Nummer).<br />';
	echo '<b>Belege vollständig</b>: Gibt an, wie viele vollständige (nicht abgeschnittene) Belege es in diesem Kartenausschnitt gibt.<br />';
	echo '<b>Belege nutzermarkiert</b>: Gibt an, wie viele unterschiedliche Belege von Nutzern markiert wurden.<br />';
	echo '<b>Transkribiert</b>: Gibt an, wie viele der Belege mit Belegnummern auch transkribiert wurden.<br />';
	echo '<b>Korrekt</b>: Gibt den Anteil an korrekten Transkriptionen an. Hierbei werden Transkriptionen zu abgeschnitten Belegen immer als falsch gewertet.<br />';
	echo '<b>Ergebnis</b>: Gibt an wie viele Belege richtig / unsicher / falsch sind. Belege werden dabei nur gezählt, wenn mindestens ' . $perc_use . '% der Nutzer sie markiert und mit Nummer versehen haben (das heißt nicht, dass sie transkribiert wurden). Belege bei denen die richtige Variante die höchste Anzahl Transkriptionen hat werden als richtig markiert. Belege, bei es keine eindeutige Variante mit maximaler Anzahl gibt, aber die richtige Variante enthalten ist, werden also unsicher markiert. Alle anderen werden als als falsch markiert.<br />';
	
	echo '<br /><br /><h1>Details</h1>';
	foreach ($dtables as $key => $dtable){
		echo '<div class="details" id="details' . $key . '" style="display: none;">' . $dtable . '</div>';
	}
	
	echo '</div>';
}