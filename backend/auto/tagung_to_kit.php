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
	
	function va_kit_return_text ($id){
		if($id == "-1")
			return;
		
		ob_start();
			
		if (have_rows('tagung_kapitel', $id)){
			$main_index = 1;
				
			while (have_rows('tagung_kapitel', $id)){
				the_row();
				?>
				<h1>
					<?php 
					echo $main_index . '. ';
					the_sub_field('tagung_kapitel_titel', $id);
					?>
				</h1>
				<?php
				the_sub_field('tagung_kapitel_inhalt', $id);
				
				$sub_index = 1;
				while (have_rows('tagung_unterkapitel', $id)){
					the_row();
					?>
					<h2>
						<?php 
						echo $main_index . '.' . $sub_index . '. ';
						the_sub_field('tagung_unterkapitel_titel', $id);
						?>
					</h2>
					<?php
					//echo apply_filters( 'the_content', get_sub_field('tagung_unterkapitel_inhalt'));
					echo get_sub_field('tagung_unterkapitel_inhalt', $id);
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
			
			$eintrag_sql .= $wpdb->prepare('INSERT INTO kit.bib_eintraege (Titel, Ort, Jahr, Band, Enthalten_In, Seitenzahlen, Verlag, Link) VALUES (%s, %s, %s, %s, %s, %s, %s, %s);',
				get_sub_field('bibl_titel', $id),
				get_sub_field('bibl_ort', $id),
				get_sub_field('bibl_jahr', $id),
				get_sub_field('bibl_band', $id),
				get_sub_field('bibl_enthalten_in', $id),
				get_sub_field('bibl_seiten', $id),
				get_sub_field('bibl_verlag', $id),
				get_sub_field('bibl_link', $id)
			) . "\n";
			
			if($first){
				$eintrag_sql .= "SET @id = LAST_INSERT_ID();\nSET @id_str = CONCAT('IMPORT(', @id);\n";
				$first = false;
			}
			else
				$eintrag_sql .= "SET @id = LAST_INSERT_ID();\nSET @id_str = CONCAT(@id_str, ',', @id);\n";
			
			$i = 1;
			while (have_rows('bibl_autoren', $id)){
				the_row ();	
				
				$nn = get_sub_field('bibl_autor_nachname', $id);
				$vn =  get_sub_field('bibl_autor_vorname', $id);
				
				$sql = $wpdb->prepare('INSERT IGNORE INTO kit.bib_autoren (Nachname, Vorname) VALUES (%s, %s)', $nn, $vn);
				
				if(!in_array($sql, $autor_sql)){
					$autor_sql[] = $sql;
				}

				$eintrag_sql .= $wpdb->prepare('INSERT INTO kit.bib_eintrag_autor (Id_Eintrag, Id_Autor, Position, Herausgeber)
					SELECT @id, Id_Autor, %d, %d FROM kit.bib_autoren WHERE Nachname = %s AND Vorname = %s LIMIT 1',
					$i,
					get_sub_field('bilb_autoren_herausgeber', $id),
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