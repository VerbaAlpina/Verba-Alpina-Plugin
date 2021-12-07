<?php
function va_show_team () {
	global $va_xxx;
	global $Ue;

?>

<script type="text/javascript">
	jQuery(function (){
		addBiblioQTips(jQuery(".entry-content"));
	});
</script>

<div class="entry-content">
	<div class="table-responsive">
		<table style="width:100%; "  class="easy-table easy-table-default tablesorter  ">
			<thead>
				<tr>
					<th data-sort="string"><?php echo $Ue['NACHNAME'];?>, <?php echo $Ue['VORNAME'];?></th>
					<th data-sort="string"><?php echo $Ue['FUNKTION'];?></th>
					<th data-sort="string"><?php echo $Ue['TELEFON'];?></th>
					<th data-sort="string">E-Mail</th>
				</tr>
			</thead>
			<tbody>
<?php
	$personen = $va_xxx->get_results("
		SELECT Name, Vorname, Telefon, EMail, Link, Art, Fachgebiet, Kuerzel, orcid
		FROM Personen JOIN Stellen USING (Kuerzel)
		WHERE Startdatum < NOW() AND (Enddatum IS NULL OR Enddatum > NOW()) 
		ORDER BY Name ASC", ARRAY_A);
	foreach ($personen as $person){

		?>

				<tr>
					<td>
						<?php 	if($person['Link'] == '')
									echo $person['Name'] . ', ' . $person['Vorname'] /*." (".$person['Kuerzel'].")"*/ ;
								else
									echo "<a href='" . va_translate_url($person['Link']) . "'>".$person['Name'] . ', ' .$person['Vorname'] /*." (".$person['Kuerzel'].")*/. "</a>";
						echo va_orcid_link($person['orcid']);
						?>
					</td>
					<td><?php echo ucfirst(va_translate($person['Art'], $Ue)) . ($person['Fachgebiet']? ' (' . va_translate($person['Fachgebiet'], $Ue) . ')' : ''); ?></td>
					<td><?php echo $person['Telefon']?></td>
					<td><a href="mailto:<?php echo $person['EMail']?>" class="g-link-mail" title="<?php echo $Ue['EMAIL_AN'];?> <?php echo $person['EMail']?>"><?php echo $person['EMail']?></a></td>
				</tr>
		<?php
	}
?>
			<tbody>
		</table>
		
		<br />
		
		<h1><?php echo $Ue['EHEMALIGE_MITAREBITER'];?></h1> <?php
		$former = $va_xxx->get_results("
						SELECT Name, Vorname, CONCAT(DATE_FORMAT(Startdatum, '%d.%m.%Y'), ' - ', DATE_FORMAT(Enddatum, '%d.%m.%Y')) AS Zeitraum, Art, Fachgebiet
						FROM Personen JOIN Stellen USING (Kuerzel)
						WHERE Enddatum < NOW() AND Art != 'prak'
						ORDER BY Name ASC, Vorname ASC", ARRAY_A);
			?>
			<table style="width:100%; "  class="easy-table easy-table-default tablesorter">
				<thead>
					<tr>
						<th data-sort="string"><?php echo $Ue['NACHNAME'];?>, <?php echo $Ue['VORNAME'];?></th>
						<th data-sort="string"><?php echo $Ue['ZEITRAUM']; ?></th>
						<th data-sort="string"><?php echo $Ue['FUNKTION'];?></th>
					</tr>
				</thead>
				<tbody>
			<?php
			foreach ($former as $person){
				?>
						<tr>
							<td><?php echo $person['Name'] . ', ' . $person['Vorname'];?></td>
							<td><?php echo $person['Zeitraum'];?></td>
							<td><?php echo ucfirst(va_translate($person['Art'], $Ue)) . ($person['Fachgebiet']? ' (' . va_translate($person['Fachgebiet'], $Ue) . ')' : ''); ?></td>
						</tr>
				<?php
			}
			?>
			</tbody>
			</table>
			
			<br />
			
		<h4><?php echo $Ue['EXTERNE_MITARBEITER'];?></h4> <?php
		$externs = $va_xxx->get_results("
						SELECT Name, Vorname, group_concat(CONCAT(Aufgabe, '$$$', Detail) SEPARATOR '###') AS Aufgabenbereiche 
						FROM Personen JOIN VTBL_Person_Aufgabe USING (Kuerzel) 
						WHERE NOT EXISTS (SELECT * FROM Stellen WHERE Stellen.Kuerzel = Personen.Kuerzel AND Enddatum > NOW()) 
						GROUP BY Kuerzel
						ORDER BY Name, Vorname", ARRAY_A);
		?>
		<table style="width:100%; "  class="easy-table easy-table-default tablesorter  ">
			<thead>
				<tr>
					<th data-sort="string"><?php echo $Ue['NACHNAME'];?>, <?php echo $Ue['VORNAME'];?></th>
					<th data-sort="string"><?php echo $Ue['AUFGABENBEREICHE'];?></th>
				</tr>
			</thead>
			<tbody>
		<?php
		foreach ($externs as $person){
			?>
					<tr>
						<td><?php echo $person['Name'] . ', ' . $person['Vorname'];?></td>
						<td>
						<?php 
							$task_list = explode('###', $person['Aufgabenbereiche']);
							foreach ($task_list as &$task){
								$sub_list = explode('$$$', $task);
								$sub_list = array_map(function ($str) use (&$Ue){
									return va_translate($str, $Ue);
								}, $sub_list);
								$task = $sub_list[0] . ($sub_list[1]? ' (' . $sub_list[1] . ')': '');
							}
							echo va_add_abrv(implode(', ', $task_list));
							?>
						</td>
					</tr>
			<?php
		}
		?>
		</tbody>
		</table>
		
		<br />
			
			<h4><?php echo $Ue['PRAKTIKANTEN']; ?></h4>
			
			<table style="width:100%; "  class="easy-table easy-table-default tablesorter  ">
			<thead>
				<tr>
					<th data-sort="string"><?php echo $Ue['NACHNAME'];?>, <?php echo $Ue['VORNAME'];?></th>
					<th data-sort="string"><?php echo $Ue['ZEITRAUM']; ?></th>
				</tr>
			</thead>
			<tbody>
	<?php
		$prakt = $va_xxx->get_results("SELECT Name, Vorname, CONCAT(DATE_FORMAT(Startdatum, '%d.%m.%Y'), ' - ', DATE_FORMAT(Enddatum, '%d.%m.%y')) AS Zeitraum, Email
						FROM Personen JOIN Stellen USING (Kuerzel)
						WHERE Enddatum < NOW() AND Art = 'prak'
						ORDER BY Startdatum ASC", ARRAY_A);
		$last_name = '';
		foreach ($prakt as $person){
			?>
					<tr>
						<td><?php echo $person['Name'] . ', ' . $person['Vorname'];?></td>
						<td><?php echo $person['Zeitraum'];?></td>
					</tr>
			<?php
		}
	?>
			<tbody>
		</table>	
	</div>
</div>
<?php
}

function partnerAnzeigen () {

	global $va_xxx;
	global $Ue;

	$media_path = get_home_url() . '/wp-content/uploads/';
?>


<script type="text/javascript">
	jQuery(function (){

		 jQuery(".clickable-row").click(function() {
		 	var link = jQuery(this).data("href");
		 	if(link!="")window.location = jQuery(this).data("href");
		 });

	});
</script>


<div class="entry-content">
	<h3><?php echo $Ue['KOOPERATIONSPARTNER'];?></h3>
	<p><?php echo $Ue['PRAEAMBEL_KOOP'];?></p>
	<div class="table-responsive">
		<table style="width:100%; "  class="easy-table easy-table-default ">
			<tbody>
<?php
	$partner = $va_xxx->get_results("SELECT Name, Link,Logo FROM Kooperationspartner WHERE Status='fix' ORDER BY Name ASC", ARRAY_N);
	foreach ($partner as $pp){

		if($pp[1]!="")echo '<tr class="clickable-row" data-href="'.$pp[1].'">';
		else echo '<tr>';
		?>
					<td style="vertical-align:middle"> <?php
					 $path =  $media_path.$pp[2];
					 $arr = explode('uploads',  $path);
					 $path_test = $arr[1];

					 if($path_test != "/")
						 {
						 	echo '<img style="width: 85px; vertical-align: middle;" src="'.$path.'">';
						 } 
					 ?>
					 	
					 </td>
					<td style="vertical-align:middle"> <?php echo $pp[0] ?></td>
				</tr>
		<?php
	}
?>
			<tbody>
		</table>
		<h3><?php echo $Ue['BEIRAT'];?></h3>
		<table style="width:100%; "  class="easy-table easy-table-default ">
			<tbody>
<?php
	$beirat = $va_xxx->get_results("SELECT Name, Link FROM Kooperationspartner WHERE Status='Beirat' ORDER BY Name ASC", ARRAY_N);
	foreach ($beirat as $bb){
		?>
				<tr>
					<td> <?php echo $bb[0] ?></td>
				</tr>
		<?php
	}
?>
			<tbody>
		</table>
	</div>
</div>
<?php
}
?>