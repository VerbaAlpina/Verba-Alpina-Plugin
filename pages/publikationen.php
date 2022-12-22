<?php
function wissPub (){
	global $vadb;
	global $Ue;
	
	$pub_thesis = $vadb->get_results("SELECT Autor, Titel, Jahr, Ort, Band, Enthalten_In, Seiten, Verlag, Download_URL FROM Bibliographie WHERE VA_Publikation = '1' AND Abschlussarbeit ORDER BY Jahr * 1 DESC, Abkuerzung ASC", ARRAY_A);
	$pub_rest = $vadb->get_results("SELECT Autor, Titel, Jahr, Ort, Band, Enthalten_In, Seiten, Verlag, Download_URL FROM Bibliographie WHERE VA_Publikation = '1' AND NOT Abschlussarbeit ORDER BY Jahr * 1 DESC, Abkuerzung ASC", ARRAY_A);
	?>
	
	<div class="entry-content">
		
		<a href="#Publikationen"><?php echo $Ue['PUBLIKATIONEN_PERS'];?></a><br />
		<a href="#Abschlussarbeiten"><?php echo $Ue['ABSCHLUSSARBEITEN'];?></a>
		
		<br />
		<br />
		<br />
		<br />
		
		<h2><?php echo $Ue['PUBLIKATIONEN_PERS'];?></h2>
		
		<ul id="Publikationen">
		<?php
		
			foreach ($pub_rest as $p){
				echo '<li>';
				$link = va_replace_by_doi_url($p['Download_URL']);
				
				echo va_format_bibliography($p['Autor'], $p['Titel'], $p['Jahr'], $p['Ort'], $link, $p['Band'], $p['Enthalten_In'], $p['Seiten'], $p['Verlag'], false);
				echo '</li>';
				echo '<br />';
			}
			
			/*if(is_multisite())
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
				restore_current_blog();*/
		?>
		</ul>
		
		<h2>
			<?php if(count($pub_thesis) > 0) echo $Ue['ABSCHLUSSARBEITEN']; ?>
		</h2>
		
		<br />
		
		<ul id="Abschlussarbeiten">
		<?php
		foreach ($pub_thesis as $p){
			echo '<li>';
			echo va_format_bibliography($p['Autor'], $p['Titel'], $p['Jahr'], $p['Ort'], $p['Download_URL'], $p['Band'], $p['Enthalten_In'], $p['Seiten'], $p['Verlag'], false);
			echo '</li>';
			echo '<br />';
		}
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
							<?php if($v['Beschreibung3']) echo '<a href="' . $v['URL3'] . '">' . $Ue[$v['Beschreibung3']] . '</a>' ?>
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
				$res = $start_day . '.-' . $end_day . substr($date_end, 2);
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
	
	if(function_exists('switch_to_blog'))
		switch_to_blog(1);
	
	$args = array ('post_type' => 'attachment', 'posts_per_page' => -1, 'post_mime_type' => 'application/pdf');
	$posts = get_posts($args);
	?>

	<div class="entry-sub-head" id="pm"><?php echo $Ue['PRESS_MATERIAL'];?></div>

	<div class="press_container">
		
		<div style="margin-bottom: 10px;">

			<div class="entry-small_head_indent" ><a href="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/VerbaAlpina_Pressetext_deutsch_VA3.pdf"><?php echo $Ue['PRESSTEXT_DE'];?></a></div>
				<div class="entry-small_head_indent" ><a href="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/VerbaAlpina_Pressetext_italienisch_VA3.pdf"><?php echo $Ue['PRESSTEXT_ITA'];?></a></div>
					<div class="entry-small_head_indent" ><a href="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/VerbaAlpina_Pressetext_franzoesisch_VA3.pdf"><?php echo $Ue['PRESSTEXT_FR'];?></a></div>

	    </div>				


		<div class="entry-small_head_indent" ><a href="https://www.youtube.com/watch?v=hxbtXzxa5LY">Crowdsourcing Video:</a></div>

		<a href="https://www.youtube.com/watch?v=hxbtXzxa5LY"><img class="cs_video_image" src="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/play_movie-1024x575.png"></a>




		<div class="entry-small_head_indent"> <?php echo $Ue['BILDMATERIAL'];?>:</div>

		<div class="image_table">

			       <div class="thumb_img_table">
					    <a href="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/karte_screenshot_presse_3.jpg">
						    <div class="thumb_img_container" style="background: url(https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/karte_screenshot_presse_3.jpg) 0% 0% / cover no-repeat;">
						    </div>
					    </a>
				    </div>


			        <div class="thumb_img_table">
					    <a href="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/karte_screenshot_presse.jpg">
						    <div class="thumb_img_container" style="background: url(https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/karte_screenshot_presse.jpg) 0% 0% / cover no-repeat;">
						    </div>
					    </a>
				    </div>

				        <div class="thumb_img_table">
					    <a href="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/karte_screenshot_presse_2.jpg">
						    <div class="thumb_img_container" style="background: url(https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/karte_screenshot_presse_2.jpg) 0% 0% / cover no-repeat;">
						    </div>
					    </a>
				    </div>


				    <div class="thumb_img_table">
					    <a href="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/karte_schematisch.jpg">
						    <div class="thumb_img_container" style="background: url(https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/karte_schematisch.jpg) 0% 0% / cover no-repeat;">
						    </div>
					    </a>
				    </div>

		

				        <div class="thumb_img_table">
					    <a href="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/VA_LOGO_presse.png">
						    <div class="thumb_img_container" style="background: url(https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/VA_LOGO_presse_dreiecke.jpg) 0% 0% / cover no-repeat;">
						    </div>
					    </a>
				    </div>
		
			        <div class="thumb_img_table">
					    <a href="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/gs_2169_edited_better_smaller.jpg">
						    <div class="thumb_img_container" style="background: url(https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/graserntegs_2169.png) 0% 0% / cover no-repeat;">
						    </div>
					    </a>
				    </div>




				    <div class="thumb_img_table">
					    <a href="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/gs_2522.jpg">
						    <div class="thumb_img_container" style="background: url(https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/schuerzetaetigkeit-zur-extraktiongs_2522.png) 0% 0% / cover no-repeat;">
						    </div>
					    </a>
				    </div>

				        <div class="thumb_img_table">
					    <a href="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/gs_2106.jpg">
						    <div class="thumb_img_container" style="background: url(https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/getreideerntegs_2106.png) 0% 0% / cover no-repeat;">
						    </div>
					    </a>
				    </div>

				        <div class="thumb_img_table">
					    <a href="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/gs_2179.jpg">
						    <div class="thumb_img_container" style="background: url(https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/transport-von-heu-mit-seilgs_2179.png) 0% 0% / cover no-repeat;">
						    </div>
					    </a>
				    </div>

				        <div class="thumb_img_table">
					    <a href="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/gs_2108.jpg">
						    <div class="thumb_img_container" style="background: url(https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/getreideerntesensesichelgs_2108.png) 0% 0% / cover no-repeat;">
						    </div>
					    </a>
				    </div>
		
			
		</div>


		<div class="entry-small_head_indent">Screenshots Crowdsourcing:</div>

		<div class="image_table">

			<?php 
				for ($c = 1; $c < 4; $c++) {
				    echo '<div class="thumb_img_table"><a href="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/cs_'.$c.'.jpg">
				    <div class="thumb_img_container" style="background: url(https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/cs_'.$c.'.jpg) center center / cover no-repeat;">
				    </div>
				    </a>
				    </div>';
				} 

				   echo '<div class="thumb_img_table"><a href="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/cs_4a.jpg"><div class="thumb_img_container" style="background: url(https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/cs_4a.jpg) center center / cover no-repeat;">
				   </div>
				   </a>
				   </div>';


					for ($c = 5; $c < 16; $c++) {
					    echo '<div class="thumb_img_table"><a href="https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/cs_'.$c.'.jpg">
					    <div class="thumb_img_container" style="background: url(https://www.verba-alpina.gwi.uni-muenchen.de/wp-content/uploads/cs_'.$c.'.jpg) center center / cover no-repeat;">	    
					    </div>
					    </a>
					    </div>';
					} 
				?>
					
		</div>

	</div>	


    <div class="entry-sub-head" id="im"><?php echo $Ue['INFO_MATERIAL'];?></div>
	
	<div class="entry-content">

		<ul>
		<?php
		
			foreach ($posts as $post){
				if(has_term('informationsmaterial', 'media_category', $post) ){
					echo '<li class="entry-small_head_indent">';
					echo '<a href="' . $post->guid . '">' . va_translate($va_xxx->get_var('SELECT Gegenstand FROM Medien WHERE Id_Medium = ' . getVA_ID($post->ID), 0, 0), $Ue) . '</a>';
					echo '</li>';		
				}
			}
		?>
		</ul>
	</div>
	
	
	<div class="entry-sub-head" id="kv"><?php echo $Ue['KOOPERATIONSVEREINBARUNGEN'];?></div>
	<div class="entry-content">
		<ul>
		<?php
		
			foreach ($posts as $post){
				if(has_term('kooperationsvereinbarung', 'media_category', $post) ){
					echo '<li class="entry-small_head_indent">';
					echo '<a href="' . $post->guid . '">' . va_translate($va_xxx->get_var('SELECT Gegenstand FROM Medien WHERE Id_Medium = ' . getVA_ID($post->ID), 0, 0), $Ue) . '</a>';
					echo '</li>';		
				}
			}
		?>
		</ul>
	</div>
	
	<?php
	if(function_exists('switch_to_blog'))
		restore_current_blog();
}

function va_echo_page (){
	global $va_xxx;
	global $Ue;
	

	$res = '<div class="entry-content"><ul>';
	
	$entries = $va_xxx->get_results('SELECT Datum, Medium, Link, tot, Kategorie FROM Echo ORDER BY Kategorie ASC, Datum DESC', ARRAY_A);
	$medien = false;
	$wiss = false;
	
	foreach ($entries as $entry){
		if (!$medien && $entry['Kategorie'] == 'Medien'){
			$medien = true;
			$res .= '<h3>' . $Ue['ECHO'] . '</h3>';
		}
		
		if (!$wiss && $entry['Kategorie'] == 'Forschung'){
			$wiss = true;
			$res .= '<h3>' . $Ue['ECHO_WISSENSCHAFTLICH'] . '</h3>';
		}
		
		if ($entry['tot']){
			$res.= '<li>' . date('d.m.Y', strtotime($entry['Datum'])) . ' - ' . '<a style="color: red" href="' . $entry['Link'] . '" target="_BLANK">' . $entry['Medium'] . '</a> (' . str_replace('%s', date('d.m.Y', strtotime($entry['tot'])), $Ue['LINK_TOT']) . ')</li>';
		}
		else {
			$res.= '<li>' . date('d.m.Y', strtotime($entry['Datum'])) . ' - ' . '<a href="' . $entry['Link'] . '" target="_BLANK">' . $entry['Medium'] . '</a></li>';
		}
	}
	
	$res .= '</ul></div><br /><br /><br />';
	
	return $res;
}
?>