<?php
function va_cs_emails (){
	global $va_xxx;
	
	?>
	<script type="text/javascript">
	(function ($){
		$(function (){
			 $("#cstable").tablesorter(); 
		});
	})(jQuery);
	</script>
	
	<br />
	<h3>Liste aller registrierten Crowdsourcing-Nutzer</h3>
	
	<table class="widefat fixed striped" id="cstable" style="width: 80%" >
		<thead>
			<tr>
				<th class="sortable">Benutzername</th>
				<th class="sortable">Registrierungsdatum</th>
				<th class="sortable">Letzte Aktivität</th>
				<th class="sortable">EMail</th>
				<th class="sortable">Nationalität</th>
				<th class="sortable">Anzahl Belege</th>
			</tr>
		</thead>
		<tbody>
			<?php 
			$informants = $va_xxx->get_results("
				SELECT DISTINCT Nummer, Ortsname, Wert, 
					(SELECT max(Erfasst_Am) FROM Aeusserungen a WHERE a.Id_Informant = i.Id_Informant) AS last, 
					(select count(*) from Aeusserungen a WHERE a.Id_Informant = i.Id_Informant) as count
				FROM Informanten i
					JOIN orte o on Id_Gemeinde = Id_Ort 
					JOIN Orte_Tags ot ON ot.Id_Ort = o.Id_Ort AND ot.Tag = 'LAND'
				WHERE Erhebung = 'CROWD' AND Nummer NOT LIKE 'anonymousCrowder%'
				GROUP BY Nummer, Ortsname
				ORDER BY (SELECT min(Erfasst_Am) FROM Aeusserungen a WHERE a.Id_Informant = i.Id_Informant) ASC 
			", ARRAY_A);
			foreach ($informants as $informant){
				$user = get_user_by('login', $informant['Nummer']);
				if($user !== false){
					echo '<tr>';
					echo '<td>' . $informant['Nummer'] . ' (' . $informant['Ortsname'] . ')' . '</td>';
					echo '<td>' . $user->user_registered . '</td>';
					echo '<td>' . $informant['last'] . '</td>';
					echo '<td>' . $user->user_email . '</td>';
					echo '<td>' .  $informant['Wert']. '</td>';
					echo '<td>' .  $informant['count']. '</td>';
					echo '</tr>';
				}
			}
			?>
		</tbody>
	</table>

	<?php 
}