<?php 
	function va_kit_transform (){
		$posts = get_posts(array (
			'post_type' => 'tagung'
		));
		
		?>
		<script type="text/javascript">
		jQuery(function () {
			jQuery("#postSelect").val("-1");
		});
		
		function getFormatedText(id){
			jQuery.post(ajaxurl, {
				"action" : "va",
				"namespace" : "test",
				"query" : "tagung",
				"id" : id
			}, function (response){
				var response = JSON.parse(response);

				jQuery("#tagung_content").val(response[0]);
				jQuery("#bib_sql").val(response[1]);
			});
		}
		</script>
		
		<select id="postSelect" onChange="getFormatedText(this.value)">
			<option value="-1" selected>---</option>
			<?php 
			foreach ($posts as $post){
				echo '<option value="' . $post->ID . '">' . $post->post_title . '</option>';	
			}
			?>
		</select>
		
		<textarea id="tagung_content" cols="200" rows="20"></textarea>
		<textarea id="bib_sql" cols="200" rows="20"></textarea>
		<?php
	}
	
	function replace_footnotes ($text){
		return preg_replace('/ \(\(([^)]*)\)\)/', '[note]$1[/note]', $text);
	}
	
	function va_kit_return_text ($id){
		if($id == "-1")
			return;
		
		ob_start();
	
		if (have_rows('tagung_kapitel', $id)){
			$main_index = 1;
				
			while (have_rows('tagung_kapitel', $id)){
				the_row();
				?><h1><?php 
					echo $main_index . '. ';
					echo replace_footnotes(get_sub_field('tagung_kapitel_titel', false));
					?></h1>
				<?php
				echo replace_footnotes(get_sub_field('tagung_kapitel_inhalt', false));
				
				$sub_index = 1;
				while (have_rows('tagung_unterkapitel', $id, false)){
					the_row();
					?>
					<h2><?php 
						echo $main_index . '.' . $sub_index . '. ';
						echo replace_footnotes(get_sub_field('tagung_unterkapitel_titel', false));
						?></h2><?php
					//echo apply_filters( 'the_content', get_sub_field('tagung_unterkapitel_inhalt'));
					echo replace_footnotes(get_sub_field('tagung_unterkapitel_inhalt', false));
					$sub_index++;
				}
			$main_index++;
			}
		}
		
		$text = ob_get_contents();
		ob_end_clean();
		
		$autor_sql = array();
		$eintrag_sql = '';
		$first = true;
		global $wpdb;
		
		while (have_rows('bibliographie', $id)){
			the_row();
			
			$names = [];
			while (have_rows('bibl_autoren', $id)){
				the_row ();
				
				$names[] = [get_sub_field('bibl_autor_nachname', $id), get_sub_field('bibl_autor_vorname', $id), get_sub_field('bilb_autoren_herausgeber', $id)];
			}
			
			if (count($names) > 2){
				$abk = $names[0][0] . ' et al. ' . get_sub_field('bibl_jahr', $id);	
			}
			else {
				$abk = implode('/', array_map(function ($e) {return $e[0];}, $names)) . ' ' . get_sub_field('bibl_jahr', $id);
			}
			
			$eintrag_sql .= $wpdb->prepare('INSERT INTO kit.bib_eintraege (Abkuerzung, Titel, Ort, Jahr, Band, Enthalten_In, Seitenzahlen, Verlag, Link, Nummer, Institution, URN, DOI, Bemerkung, Bibtex) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s);',
				$abk,
				get_sub_field('bibl_titel', $id),
				get_sub_field('bibl_ort', $id),
				get_sub_field('bibl_jahr', $id),
				get_sub_field('bibl_band', $id),
				get_sub_field('bibl_enthalten_in', $id),
				get_sub_field('bibl_seiten', $id),
				get_sub_field('bibl_verlag', $id),
				get_sub_field('bibl_link', $id),
				'',
				'',
				'',
				'',
				'',
				''
			) . "\n";
			
			if($first){
				$eintrag_sql .= "SET @id = LAST_INSERT_ID();\nSET @id_str = CONCAT('IMPORT(', @id);\n";
				$first = false;
			}
			else
				$eintrag_sql .= "SET @id = LAST_INSERT_ID();\nSET @id_str = CONCAT(@id_str, ',', @id);\n";
			
			$i = 1;
			foreach ($names as $name){
				
				$nn = $name[0];
				$vn =  $name[1];
				
				$sql = $wpdb->prepare('INSERT IGNORE INTO kit.bib_autoren (Nachname, Vorname) VALUES (%s, %s)', $nn, $vn);
				
				if(!in_array($sql, $autor_sql)){
					$autor_sql[] = $sql;
				}

				$eintrag_sql .= $wpdb->prepare('INSERT INTO kit.bib_eintrag_autor (Id_Eintrag, Id_Autor, Position, Herausgeber)
					SELECT @id, Id_Autor, %d, %d FROM kit.bib_autoren WHERE Nachname = %s AND Vorname = %s LIMIT 1',
					$i,
					$name[2],
					$nn,
					$vn) . ";\n";
				
				$i++;
			}
		}
		
		$eintrag_sql .= "SET @id_str = CONCAT(@id_str, ')');\n";
		$eintrag_sql .= "INSERT INTO kit.bib_import_helper (Import, Titel) values (@id_str, '" . get_post($id)->post_title . "');";
		
		echo json_encode(array ($text, implode(";\n", $autor_sql) . ";\n" . $eintrag_sql));
	}
?>