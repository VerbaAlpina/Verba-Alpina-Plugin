<?php
function va_cs_emails (){
	global $va_xxx;
	
	?>
	<script type="text/javascript">
		jQuery(function (){
			 jQuery("#cstable").tablesorter();
			 
			 jQuery(".email_invalid_checkbox").click(function (){
				var val = jQuery(this).is(":checked");
				var row = jQuery(this).closest("tr");
				jQuery.post(ajaxurl, {
					"action" : "va",
					"namespace" : "util",
					"query" : "user_email_outdated",
					"id_user" : row.data("user-id"),
					"val" : val
				}, function (response){
					if (response === 'failure'){
						alert("Setzen der Meta-Daten fehlgeschlagen");
					}
					else {
						rows = jQuery("#cstable tr[data-user-id=" + row.data("user-id") + "]");
						if (response == "true"){
							rows.each(function () {
								jQuery(this).css("background", "mistyrose");
								jQuery(this).find(".email_invalid_checkbox").prop("checked", true);
								jQuery(this).find("span.outdated_label").text("Ungültig ");
							});
						}
						else {
							rows.each(function () {
								jQuery(this).css("background", "");
								jQuery(this).find(".email_invalid_checkbox").prop("checked", false);
								jQuery(this).find("span.outdated_label").text("");
							});
						}
					}
				});
			 });
		});
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
				<th class="sortable">Email ungültig</th>
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

			foreach ($informants as $index => $informant){
				$user = get_user_by('login', $informant['Nummer']);
				if($user !== false){
					$email_outdated = get_user_meta($user->ID, 'email_outdated', true);
					$informants[$index]['email'] = $user->user_email;
					$informants[$index]['registered'] = $user->user_registered;
					$informants[$index]['user_id'] = $user->ID;
					$informants[$index]['email_outdated'] = $email_outdated == 'true'? true: false;
				}
			}

			foreach ($informants as $informant){
			    if(isset($informant['registered']) && $informant['registered']){
					$style = '';
					if ($informant['email_outdated']){
						$style = 'background: mistyrose;';
					}
					
					$res = '<tr style="' . $style . '" data-user-id="' . $informant['user_id'] . '">';
					$res .= '<td>' . $informant['Nummer'] . ' (' . $informant['Ortsname'] . ')' . '</td>';
					$res .= '<td>' . $informant['registered'] . '</td>';
					$res .= '<td>' . $informant['last'] . '</td>';
					$res .= '<td>' . $informant['email'] . '</td>';
					$res .= '<td>' .  $informant['Wert']. '</td>';
					$res .= '<td>' .  $informant['count']. '</td>';
					$res .= '<td><span class="outdated_label">' . ($informant['email_outdated']? 'Ungültig ': '') . '</span><input type="checkbox" ' . ($informant['email_outdated']? 'checked ': '') . 'class="email_invalid_checkbox" autocomplete="off"></input></td>';
					$res .= '</tr>';
					
					echo $res;
				}
			}
			?>
		</tbody>
	</table>

	<?php 
}