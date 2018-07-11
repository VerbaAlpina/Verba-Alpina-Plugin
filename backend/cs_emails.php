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
	
	<table class="widefat fixed striped" id="cstable" style="width: 80%" class="tablesorter">
		<thead>
			<tr>
				<th>Benutzername</th>
				<th>Registrierungsdatum</th>
				<th>EMail</th>
				<th>Nationalität</th>
			</tr>
		</thead>
		<tbody>
			<?php 
			$informants = $va_xxx->get_results("SELECT DISTINCT Nummer, Wert FROM Informanten join orte o on Id_Gemeinde = Id_Ort join Orte_Tags ot ON ot.Id_Ort = o.Id_Ort AND ot.Tag = 'LAND'  WHERE Erhebung = 'CROWD' AND Nummer NOT LIKE 'anonymousCrowder%'", ARRAY_A);
			foreach ($informants as $informant){
				$user = get_user_by('login', $informant['Nummer']);
				if($user !== false){
					echo '<tr>';
					echo '<td>' . $informant['Nummer'] . '</td>';
					echo '<td>' . $user->user_registered . '</td>';
					echo '<td>' . $user->user_email . '</td>';
					echo '<td>' .  $informant['Wert']. '</td>';
					echo '</tr>';
				}
			}
			?>
		</tbody>
	</table>

	<?php 
}