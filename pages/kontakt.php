<?php
	function kontaktSeite (){
	global $Ue;
	?>

	<div class="entry-content">
		<h1><?php echo $Ue['KONTAKT']; ?></h1>

		<h2><?php echo $Ue['ANSCHRIFT']; ?></h2>
		<p>
			Ludwig-Maximilians-Universität München<br />
			- VerbaAlpina -<br />
			Hauspostfach 152<br/>
			Geschwister-Scholl-Platz 1<br />80539 München</p>
		
		<h2><?php echo $Ue['PROJEKTRAUM']; ?></h2>
		<p>
			Schellingstrasse 33, <?php echo $Ue['RUECKGEBAEUDE']; ?>, <?php echo $Ue['RAUM']; ?> 4005<br />
			80799 München
		</p>

		
		<h2><?php echo $Ue['MVV']; ?></h2>
			<?php echo $Ue['UBAHN']; ?>
		<p></p>

		<h2><?php echo $Ue['ANSPRECHPARTNER']; ?></h2>
		
		<?php 
			global $va_xxx;
			$daten = $va_xxx->get_results("SELECT Telefon, Email, Link, Titel, CONCAT(Vorname, ' ', Name) FROM Personen WHERE Kontaktseite IS NOT NULL ORDER BY Kontaktseite ASC", ARRAY_N);
			
			foreach ($daten as $datum){
				?>
				<h3>
				<?php
				if ($datum[2]){
					echo '<a href="' . va_translate_url($datum[2]) .'">' . $datum[3] . ' ' . $datum[4] .'</a>';
				}
				else {
					echo $datum[3] . ' ' . $datum[4];
				}
				?>
				</h3>
				<p>
					<?php echo $Ue['TELEFON']; ?>: <?php echo $datum[0]; ?><br />
					Mail: <a href="mailto:<?php echo $datum[1]; ?>"><?php echo $datum[1]; ?></a>
				</p>
				<?php
			}
		?>
	</div>
	<?php	
	}
?>