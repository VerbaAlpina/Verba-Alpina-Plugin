<?php
function va_cs_emails (){
	global $va_xxx;
	
	?>
	<br />
	<h3>Liste aller registrierten Crowdsourcing-Nutzer</h3>
	
	<table class="widefat fixed striped" style="width: 80%">
		<thead>
			<tr>
				<th>Benutzername</th>
				<th>Registrierungsdatum</th>
				<th>EMail</th>
			</tr>
		</thead>
		<tbody>
			<?php 
			$informants = $va_xxx->get_col("SELECT DISTINCT Nummer FROM Informanten WHERE Erhebung = 'CROWD' AND Nummer NOT LIKE 'anonymousCrowder%'");
			foreach ($informants as $informant){
				$user = get_user_by('slug', $informant);
				if($user !== false){
					echo '<tr>';
					echo '<td>' . $informant . '</td>';
					echo '<td>' . $user->user_registered . '</td>';
					echo '<td>' . $user->user_email . '</td>';
					echo '</tr>';
				}
			}
			?>
		</tbody>
	</table>

	<?php 
}