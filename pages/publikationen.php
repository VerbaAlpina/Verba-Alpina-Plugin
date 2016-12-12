<?php
function wissPub (){
	global $va_xxx;
	global $Ue;
	
	$pub_thesis = $va_xxx->get_results("SELECT Autor, Titel, Jahr, Ort, Band, Enthalten_In, Seiten, Verlag, Download_URL FROM Bibliographie WHERE VA_Publikation = '1' AND Abschlussarbeit ORDER BY Jahr * 1 ASC", ARRAY_A);
	$pub_rest = $va_xxx->get_results("SELECT Autor, Titel, Jahr, Ort, Band, Enthalten_In, Seiten, Verlag, Download_URL FROM Bibliographie WHERE VA_Publikation = '1' AND NOT Abschlussarbeit ORDER BY Jahr * 1 ASC", ARRAY_A);
	?>
	
	<div class="entry-content">
		<h2>
			<?php echo $Ue['ABSCHLUSSARBEITEN']; ?>
		</h2>
		
		<br />
		
		<?php
		foreach ($pub_thesis as $p){
			echo '<li>';
			echo va_format_bibliography($p['Autor'], $p['Titel'], $p['Jahr'], $p['Ort'], $p['Download_URL'], $p['Band'], $p['Enthalten_In'], $p['Seiten'], $p['Verlag'], false);
			echo '</li>';
			echo '<br />';
		}
		?>
		
		
		<h2>
			<?php echo $Ue['WEITERE_PUB']; ?>
		</h2>	
	
		<br />
	
		<ul>
		<?php
		
			foreach ($pub_rest as $p){
				echo '<li>';
				echo va_format_bibliography($p['Autor'], $p['Titel'], $p['Jahr'], $p['Ort'], $p['Download_URL'], $p['Band'], $p['Enthalten_In'], $p['Seiten'], $p['Verlag'], false);
				echo '</li>';
				echo '<br />';
			}
			
			if(is_multisite())
				switch_to_blog(1);
			
			$va_posts = get_posts(array('category_name' => 'VA_Beitrag'));
			foreach ($va_posts as $post){
				echo '<li>';
				if(get_field('autoren', $post));{
					the_field('autoren', $post);
					echo ' (' . date('Y', strtotime($post->post_date)) . '): ';
				}
				echo $post->post_title;
				echo '&nbsp;<a href="' . get_permalink($post) . '">(Link)</a>';
				echo '</li>';
				echo '<br />';
			}
			
			if(is_multisite())
				restore_current_blog();
		?>
		</ul>
	</div>
	<?php
}

function intPub (){
	global $va_xxx;
	global $Ue;
	
	$vortr = $va_xxx->get_results('SELECT * FROM Vortraege ORDER BY Datum_Beginn DESC', ARRAY_A);
	
	?>
	<div class="entry-content">
		<table>
			<tr>
				<th><?php echo $Ue['DATUM'];?></th>
				<th><?php echo $Ue['VORTRAGENDE'];?></th>
				<th><?php echo $Ue['TITEL_VORTRAG'];?></th>
				<th><?php echo $Ue['VERANSTALTUNG'];?></th>
				<th><?php echo $Ue['ART_PRAESENTATION'];?></th>
				<th></th>
			</tr>
			<?php
				foreach ($vortr as $v){
					?>
					<tr>
						<td><?php echo formatDate($v['Datum_Beginn'], $v['Datum_Ende'], $Ue);?></td>
						<td><?php echo $v['Vortragender'];?></td>
						<td><?php echo $v['Titel'];?></td>
						<td><?php echo $v['Veranstaltung'];?></td>
						<td><?php echo $Ue[$v['Art']];?></td>
						<td>
							<?php if($v['Beschreibung1']) echo '<a href="' . $v['URL1'] . '">' . $Ue[$v['Beschreibung1']] . '</a>' ?>
							<?php if($v['Beschreibung2']) echo '<a href="' . $v['URL2'] . '">' . $Ue[$v['Beschreibung2']] . '</a>' ?>
						</td>
					</tr>
					<?php
				}
			?>
		</table>
	</div>
	<?php
}

function formatDate ($start, $end, &$Ue){
	$date_start = date('d.m.y', strtotime($start));
	$date_end = date('d.m.y', strtotime($end));
	
	if($date_start == $date_end)
		$res = $date_end;
	else {
		$start_day = substr($date_start, 0, 2);
		$end_day = substr($date_end, 0, 2);
		if((int) $start_day + 1 == (int) $end_day)
			$res = $start_day . './' . $end_day . substr($date_end, 2);
		else {
			$month_start = substr($date_start, 3, 2);
			$month_end = substr($date_end, 3, 2);
			if($month_start == $month_end)
				$res = $start_day . '-' . $end_day . substr($date_end, 2);
			else
				$res = $start_day . '.' . $month_start . '. - ' . $end_day . '.' . $month_end . substr($date_end, 5);
		}
	}
	
	$future = $start > date('Y-m-d');
	if($future)
		$res .= ' (' . $Ue['GEPLANT'] . ')';
	return $res;
}

function infoMat (){
	global $va_xxx;
	global $Ue;
	
	$args = array ('post_type' => 'attachment', 'posts_per_page' => -1, 'post_mime_type' => 'application/pdf');
	$posts = get_posts($args);

	?>
	
	<div class="entry-content">
		<h2>
			<?php echo $Ue['INFORMATIONSMATERIAL']; ?>
		</h2>	
	
		<br />
	
		<ul>
		<?php
		
			foreach ($posts as $post){
				if(has_term('informationsmaterial', 'media_category', $post) ){
					echo '<li>';
					echo '<a href="' . $post->guid . '">' . $va_xxx->get_var('SELECT Gegenstand FROM Medien WHERE Id_Medium = ' . getVA_ID($post->ID), 0, 0) . '</a>';
					echo '</li>';
					echo '<br />';
				}
			}
		?>
		</ul>
	</div>
	<?php
}
?>