<?php
function va_ajax_overview (&$db){
	switch ($_REQUEST['query']){
		case 'transcription':
			va_overview_build_transcription();
			break;
		
		case 'stimuli':
			va_overview_build_stimuli_concepts();
			break;
			
		case 'atlases':
			va_overview_build_atlases_concepts();
			break;
			
		case 'typification':
		    va_overview_build_typification();
		    break;
	}
}

function va_overview_build_atlases_concepts (){
	global $vadb;
	?>
	<table class="matrix">
			<thead>
				<tr>
					<th><div>Konzept</div></th>
					<?php 
					$atlanten = $vadb->get_col('SELECT DISTINCT Erhebung FROM
													(SELECT Erhebung FROM Stimuli s WHERE EXISTS (SELECT * FROM Aeusserungen a WHERE a.Id_Stimulus = s.Id_Stimulus)) e', 0);
					foreach ($atlanten as $atlas){
						echo '<th><div><span>' . $atlas . '</span></div></th>';
					}
					?>						
				</tr>
			</thead>
			
			<tbody>
			
			<?php
			/*
				 SELECT
					IF(Name_D = '', Beschreibung_D, Name_D) AS Konzept,
					Id_Aeusserung,
					Erhebung
				FROM
					((SELECT * FROM A_ueberkonzepte_erweitert)
						UNION ALL 
					(SELECT Id_Konzept, Id_Konzept as Id_Ueberkonzept FROM Konzepte)) u 
					JOIN Konzepte k ON u.Id_Ueberkonzept = k.Id_Konzept
					JOIN Ueberkonzepte ue ON k.Id_Konzept = ue.Id_Konzept
					JOIN VTBL_Aeusserung_Konzept v ON u.Id_Konzept = v.Id_Konzept
					JOIN Aeusserungen USING (Id_Aeusserung)
					JOIN Stimuli USING (Id_Stimulus)
				WHERE 
					Relevanz
					AND (Name_D != '' OR ue.Id_Ueberkonzept = 707)
					AND (Aeusserung IS NULL OR (Aeusserung != '<vacat>' AND Aeusserung != '<problem>'))
				GROUP BY u.Id_Ueberkonzept, Id_Aeusserung, Id_Stimulus
				ORDER BY IF(Name_D = '', Beschreibung_D, Name_D)
			 */
			$belege = $vadb->get_results("
				SELECT
					IF(Name_D = '', Beschreibung_D, Name_D) AS Konzept,
					Id_Aeusserung,
					Erhebung,
					Basiskonzept
				FROM
					((SELECT * FROM A_ueberkonzepte_erweitert)
						UNION ALL 
					(SELECT Id_Konzept, Id_Konzept as Id_Ueberkonzept FROM Konzepte)) u 
					JOIN Konzepte k ON u.Id_Ueberkonzept = k.Id_Konzept
					JOIN VTBL_Aeusserung_Konzept v ON u.Id_Konzept = v.Id_Konzept
					JOIN Aeusserungen USING (Id_Aeusserung)
					JOIN Stimuli USING (Id_Stimulus)
					LEFT JOIN A_Konzept_Tiefen a ON a.Id_Konzept = k.Id_Konzept
				WHERE 
					(Aeusserung IS NULL OR (Aeusserung != '<vacat>' AND Aeusserung != '<problem>')) AND k.RELEVANZ AND k.va_phase = '2'
				GROUP BY u.Id_Ueberkonzept, Id_Aeusserung, Id_Stimulus
				ORDER BY Basiskonzept DESC, IF(Basiskonzept, Konzept, IF(Tiefe IS NULL, 99, Tiefe)) ASC, Konzept ASC
			", ARRAY_A);
			
			$matrix = array();
			$vorschlag = array();
			
			foreach ($belege as $beleg){
				$matrix[$beleg['Konzept']] = array();
				foreach ($atlanten as $atlas){
					$matrix[$beleg['Konzept']][$atlas] = 0;
				}
				$vorschlag[$beleg['Konzept']] = $beleg['Basiskonzept'];
			}
			
			foreach ($belege as $beleg){
				$matrix[$beleg['Konzept']][$beleg['Erhebung']] ++;
			}
			
			
			foreach ($matrix as $key => $row){
				if($vorschlag[$key])
					echo '<tr style="background: green; color : white">';
				else
					echo '<tr>';
				echo '<td><div>';
				echo $key;
				echo '</div></td>';
				foreach ($row as $val){
					echo '<td><div>';
					echo ($val > 0? 'X': '');
					echo '</div></td>';
				}
				echo '</tr>';
			}
			?>
			</tbody>
		</table>
	<?php
}

function va_overview_build_stimuli_concepts (){
	global $vadb;
	
	$konzepte = $vadb->get_results("
	SELECT
		k.Id_Konzept,
		IF(Name_D = '', Beschreibung_D, NAme_D) as Konzept,
		GROUP_CONCAT(DISTINCT CONCAT(Erhebung, '#', Karte, '_', Nummer) SEPARATOR ', ') as Stimuli,
		count(DISTINCT Id_Aeusserung) as Aeusserungen,
		count(DISTINCT IF(Tokenisiert, Id_Aeusserung, NULL)) as Tokenisiert
	FROM
		((SELECT * FROM A_ueberkonzepte_erweitert)
			UNION ALL
		(SELECT Id_Konzept, Id_Konzept as Id_Ueberkonzept FROM Konzepte)) u
		JOIN Konzepte k ON u.Id_Ueberkonzept = k.Id_Konzept
		JOIN Ueberkonzepte ue ON k.Id_Konzept = ue.Id_Konzept
		LEFT JOIN VTBL_Aeusserung_Konzept v ON u.Id_Konzept = v.Id_Konzept
		LEFT JOIN Aeusserungen USING (Id_Aeusserung)
		LEFT JOIN Stimuli USING (Id_Stimulus)
	WHERE
		Relevanz
		AND (Name_D != '' OR ue.Id_Ueberkonzept = 707)
		AND (Aeusserung IS NULL OR (Aeusserung != '<vacat>' AND Aeusserung != '<problem>'))
	GROUP BY u.Id_Ueberkonzept
	ORDER BY IF(Name_D = '', Beschreibung_D, Name_D)
	", ARRAY_A);
	?>
	<table class="easy-table easy-table-default">
		<tr>
			<th>Konzept</th>
			<th>Stimuli</th>
			<th>Äußerungen</th>
			<th>Tokenisiert</th>
			<th>Tokens</th>
		</tr>
		<?php
		foreach ($konzepte as $konzept){
			echo '<tr>';
			echo '<td>' . $konzept['Konzept'] . '</td>';
			echo '<td>' . $konzept['Stimuli'] . '</td>';
			echo '<td>' . $konzept['Aeusserungen'] . '</td>';
			echo '<td>' . $konzept['Tokenisiert'] . '</td>';
			
			$subConcepts = $vadb->get_col('SELECT Id_Konzept FROM A_Ueberkonzepte_Erweitert WHERE Id_Ueberkonzept = ' . $konzept['Id_Konzept'], 0);
			$subConcepts[] = $konzept['Id_Konzept'];
			
			$tokens = $vadb->get_var('
				SELECT count(*)
				FROM 
					Tokens
					JOIN VTBL_Token_Konzept v USING (Id_Token)
				WHERE 
					Id_Konzept IN (' . implode(',', $subConcepts) . ')'
				, 0, 0);
				
			$tokengruppen = $vadb->get_var('
				SELECT count(*)
				FROM 
					Tokengruppen
					JOIN VTBL_Tokengruppe_Konzept v USING (Id_Tokengruppe)
				WHERE 
					Id_Konzept IN (' . implode(',', $subConcepts) . ')'
				, 0, 0);
				
			echo '<td>' . ($tokens + $tokengruppen) . '</td>';
			
			echo '</tr>';
		}
		?>
	</table>
	<?php
}

function va_overview_build_transcription (){
	global $vadb;
	$stimuli = $vadb->get_results("
	SELECT
		Id_Stimulus,
		Erhebung,
		concat(Karte, '_', Nummer) as Karte,
		Stimulus,
		(SELECT count(DISTINCT Id_Informant) FROM Aeusserungen WHERE Id_Stimulus = Stimuli.Id_Stimulus AND (SELECT Alpenkonvention FROM Informanten WHERE Id_Informant = Aeusserungen.Id_Informant)) AS Aeusserungen,
		(SELECT count(*) FROM Informanten WHERE Erhebung = Stimuli.Erhebung AND Alpenkonvention) as Informanten,
		(SELECT count(*) FROM Aeusserungen WHERE Id_Stimulus = Stimuli.Id_Stimulus AND Aeusserung = '<problem>') AS Probleme
	FROM Stimuli
	WHERE VA_Phase = '2'
	", ARRAY_A);
	
	?>
	<table class="easy-table easy-table-default">
		<thead>
			<tr>
				<td>Id</td>
				<td>Erhebung</td>
				<td>Karte</td>
				<td>Stimulus</td>
				<td>Transkribiert</td>
				<td>Probleme</td>
			</tr>
		</thead>
	<?php
		
	foreach ($stimuli as $stimulus) {
		if($stimulus['Informanten'] == 0)
			continue;
	
		$style = '';
		if($stimulus['Aeusserungen'] === $stimulus['Informanten']){
			if($stimulus['Probleme'] == 0){
				$style .= 'background: #00FF00;';
			}
			else {
				$style .= 'background: #FFFF00;';
			}
		}
		else if($stimulus['Aeusserungen'] != 0){
			$style .= 'background: #00FFFF;';
		}

		echo '<tr style="' . $style . '">';
		echo '<td>' . $stimulus['Id_Stimulus'] . '</td>';
		echo '<td>' . $stimulus['Erhebung'] . '</td>';
		echo '<td>' . $stimulus['Karte'] . '</td>';
		echo '<td>' . $stimulus['Stimulus'] . '</td>';
		echo '<td>' . $stimulus['Aeusserungen'] . '/' . $stimulus['Informanten'] . '</td>';
		echo '<td>' . $stimulus['Probleme'] . '</td>';
		echo '</tr>';
	}
	?>
	</table>
	<?php
}

function va_overview_build_typification (){
    global $vadb;
    
    $sql = 'SELECT 
                Erhebung, 
                SUM(IF(Id_morph_Typ IS NOT NULL, 1, 0)) AS Typisiert,
                SUM(IF(Id_morph_Typ IS NULL AND (Relevanz = 0 OR Grammatikalisch > 0 OR Not Alpenkonvention), 1, 0)) AS Nicht_Notwendig,
                COUNT(*) AS Summe
            FROM
                (SELECT t.Id_Token, s.Erhebung, m.Id_morph_Typ, SUM(Relevanz) as Relevanz, SUM(Grammatikalisch) as Grammatikalisch, Alpenkonvention
                FROM Tokens t
                    JOIN Stimuli s USING (Id_Stimulus)
                    JOIN Informanten USING (Id_Informant)
                    LEFT JOIN VTBL_Token_morph_Typ vm USING (Id_Token)
                    LEFT JOIN morph_Typen m ON m.Quelle = "VA" AND m.Id_morph_Typ = vm.Id_morph_Typ
                    LEFT JOIN VTBL_Token_Konzept USING (Id_Token)
                    LEFT JOIN Konzepte USING (Id_Konzept)
                WHERE s.Erhebung != "Test"
                GROUP BY Id_Token) tt
            GROUP BY Erhebung
            ORDER BY COUNT(*) DESC';
    
    $res = $vadb->get_results($sql, ARRAY_A);
    
    ?>
	<table class="easy-table easy-table-default tablesorter tablesorter-default">
		<thead>
			<tr>
				<th class="sortable">Erhebung</th>
				<th class="sortable">Tokens</th>
				<th class="sortable">Typisiert</th>
				<th class="sortable">Nicht Notwendig<sup><a href="#fn1">1</a></sup></th>
				<th class="sortable">Nicht Typisiert</th>
				<th class="sortable">Anteil Typisiert</th>
			</tr>
		</thead>
	<?php
    
	foreach ($res as $row){
	    $rest = $row['Summe'] - $row['Typisiert'] - $row['Nicht_Notwendig'];
	    echo '<tr><td>' . $row['Erhebung'] . '</td><td>' . $row['Summe'] . '</td><td>' . $row['Typisiert'] . '</td><td>' . $row['Nicht_Notwendig'] . '</td><td>' .
	   	    $rest . '</td><td>' . round($row['Typisiert'] / ($row['Typisiert'] + $rest) * 100) . '%</td></tr>';
	}
	
	?>
	</table>
	
	<div id="fn1">
	1: "Nicht notwendig" bedeutet in diesem Fall das Token ist mit einem nicht relevanten Konzept verknüpft oder als grammatikalisch markiert oder nicht innerhalb der Alpenkonvention.
	</div>
	<?php
}
?>