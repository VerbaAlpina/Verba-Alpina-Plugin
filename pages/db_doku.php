<?php

//TODO change doku path in tools.php

function va_show_db_doku (){

    ?>
    <script type="text/javascript">
	jQuery(function (){
		jQuery(".docu_type_info").accordion({
			collapsible: true,
			active: "none",
			heightStyle: "content"
		});
	});
	</script>
    <?php
    
	echo '<div class="entry-content">';

	if (isset($_REQUEST['table'])){
		echo va_get_table_docu($_REQUEST['table']);
	}
	else {
		list ($doku, $missing, $outdated_corr, $outdated_wrong, $doku_unconn) = va_check_table_state();
		echo htmlentities(json_encode([$doku, $missing, $outdated_corr, $outdated_wrong, $doku_unconn]));

		// foreach ($doku as $table_name => $tdata){
			// echo va_get_table_docu($table_name, $tdata);
		// }
	}
	
	echo '</div>';
}

function va_get_table_docu ($table_name, $table_data = NULL){
	
	//TODO Versionierung!!!!!
	global $va_xxx;
	
	$proc_path = 'https://github.com/VerbaAlpina/SQL/tree/master/procedures/';
	
	$prefixes = [
		'a_' => 'Tabellen mit dem Präfix "a_" werden automatisiert aus den Inhalten anderer Tabellen befüllt und enthalten die Ergebnisse aufwendiger Rechenoperationen.',
		'z_' => 'Tabellen mit dem Präfix "z_" sind Teil der [[Datenzugriffsschicht]] von VerbaAlpina. Sie haben eine persistente Strukur und werden aus den zugrundeliegenden (veränderlichen) Tabellen der Kategorie "Projektdaten" automatisiert befüllt. Im Gegensatz zu den Tabellen mit dem Präfix "vap_" dienen sie hauptsächlich dem maschinellen Zugriff.',
		'vap_' => 'Tabellen mit dem Präfix "vap_" sind Teil der [[Datenzugriffsschicht]] von VerbaAlpina. Ihre Inhalte enstsprechen denen der entsprechenden "z_"-Tabellen, die Daten werden allerdings lokalisiert und leichter menschenlesbar dargestellt.',
		'v_' => 'Mit dem Präfix "v_" werden Ansichten (Views) markiert. Diese sind keine eigentlichen Tabellen, weisen aber prinzipiell die selbe Struktur auf (vgl. [[https://dev.mysql.com/doc/refman/8.0/en/views.html]]).',
		'vtbl_' => 'Tabellen mit dem Präfix "vtbl_" sind sogenannte "Verknüpfungstabellen", die eine m:n Relation zwischen zwei Primärtabellen abbilden.'
	];
	
	$data_type = [
	    [
	        'types' => ['geometry', 'point'],
	        'text' => 'Dieser Datentyp speichert geometrische Daten in MySQL-Datenbanken. Die einfachste Möglichkeit diese manuell einzutragen, ist die Verwendung der [[GeomFromText|https://dev.mysql.com/doc/refman/5.7/en/gis-wkt-functions.html#function_geomfromtext]]-Funktion. Diese erlaubt die Eingabe der Daten im WKT-Format.<br /><br />'.
	        'Alle geographischen Koordinaten in VerbaAlpina werden in der Reihenfolge \<Längengrad\>, \<Breitengrad\> angegeben. Die Eingabe eines Punktes mit den Koordinaten Breitengrad 48.135876 und Längengrad 11.583720 könnte somit folgendermaßen über die [[phpMyAdmin|https://pma.gwi.uni-muenchen.de/index.php]]-Oberfläche angegeben werden:<br /><br>'.
	        '<ul><li>Auswahl von "GeomFromText" in der Spalte "Funktion"</li><li>Eingabe von <pre style="padding: 0px; margin: 0.5em;">POINT(11.583720 48.135876)</pre> in der Spalte "Wert"'
	    ]
	];
	
	$auto_update = [
		'trigger' => 'Die Daten in dieser Tabelle werden sofort bei einer Änderung der jeweils zugrundeliegenden Primärtabellen mit Hilfe eines [[Triggers|https://dev.mysql.com/doc/refman/8.0/en/triggers.html]] aktualisiert.',
		'daily' => 'Die Daten in dieser Tabelle werden (mindestens) einmal täglich aus den jeweils zugrundeliegenden Primärtabellen neu erstellt.'
	];
	
	if (!$table_data){
		try {
			$table_data = va_get_docu_data($table_name);
		}
		catch (Exception $e) {
			echo '<span style="color:red;">' . $e->getMessage() . '</span>';
			return;
		}
	}
	
	$content = '<h2' . ($table_data['veraltet']? ' style="color: red;"': '') . '>' . $table_name . '</h2>';
	
	if ($table_data['veraltet']){
		$content .= '<b>Diese Tabelle ist veraltet und existiert (in dieser Form) nicht mehr.</b>';
		return $content;
	}
	
	foreach ($prefixes as $prefix => $ptext){
		if (mb_strpos(mb_strtolower($table_name), mb_strtolower($prefix)) === 0){
			$content .= '<div><b>Vorbemerkung</b><br /><br />' . $ptext;
			
			if ($table_data['prozedur']){
				$content .= '<br /><br />Die Inhalte dieser Tabelle werden durch die folgende Prozedur erstellt: <a target="_BLANK" href="' . $proc_path . $table_data['prozedur'] . '.sql">' . mb_substr($table_data['prozedur'], mb_strpos($table_data['prozedur'], '/') + 1) . '</a>';
			}
			
			if ($table_data['auto_update']){
				$content .= '<br /><br />' . $auto_update[$table_data['auto_update']];
			}
			
			$content .= '</div><br /><br />';
			break;
		}
	}
	
	if ($table_data['beschreibung']){
		$content .= '<div><b>Beschreibung</b><br /><br />' . $table_data['beschreibung'] . '</div>';
	}
	else {
		$content .= '<div style="color: red;">Tabellenbeschreibung fehlt!</div>';
	}
	
	$content .= '<br /><br />';
	
	$content .= '<div><b>Spalten</b><br /><br />';
	
	$content .= '<div style="font-size: 0.8em; margin-bottom: 2em;">Der Name von Spalten, die Teil des Primärschlüssels sind wird fett dargestellt, die Namen von Fremdschlüsseln unterstrichen. Kursivierte Werte im Feld Datentyp bedeuten, dass der Wert optional (nullable) ist.</div>';
	
	$content .= '<table><tr><th>Spaltenname</th><th>Datentyp</th><th>Mögliche Werte</th><th>Beschreibung</th></tr>';
	foreach ($table_data['col_descriptions'] as $col_name => $col_data){
		if ($col_data['veraltet']){
			continue;
		}
		
		$col_name = mb_strtolower($col_name);
		
		foreach (str_split($col_data['schluessel']) as $key_part){
			if ($key_part == 'P'){
				$col_name = '<b>' . $col_name . '</b>';
			}
			
			if ($key_part == 'F'){
				$col_name = '<u>' . $col_name . '</u>';
			}
		}
		
		$ctype = $col_data['typ'];
		$ctype_str = $ctype;
		
		if ($col_data['optional']){
		    $ctype_str = '<i>' . $ctype_str . '</i>';
		}
		
		foreach ($data_type as $tdescription){
		    foreach ($tdescription['types'] as $dtype){
		        if ($dtype == $ctype){
		            $ctype_str = '<div class="docu_type_info" style="min-width: 15em;"><h3>' . $ctype_str . '</h3><div>' . $tdescription['text'] . '</div></div>';
		            break 2;
		        }
		    }
		}
		
		$content .= '<tr><td>' . $col_name . '</td><td>' . $ctype_str . '</td><td>' . ($col_data['werte']?: 'beliebig') . '</td><td>' . ($col_data['beschreibung']?: '<span style="color: red;">Spaltenbeschreibung fehlt!</span>') . '</td></tr>';
	}
	$content .= '</table>';

	if ($table_data['ausschnitt']){
		
		$cols_missing = false;
		
		if ($table_data['ausschnitt'] === 'DEFAULT'){
			$select_cols = [];
			foreach ($table_data['col_descriptions'] as $col_name => $col_data){
				if ($col_data['veraltet']){
					continue;
				}
				$select_cols[] = in_array($col_data['typ'], ['point', 'geometry'])? 'AsText(' . $col_name . ') AS ' . $col_name : $col_name;
			}
			$example_data = $va_xxx->get_results('SELECT ' . implode($select_cols, ',') . ' FROM ' . $table_name . ' ORDER BY RAND() LIMIT 10', ARRAY_A);
		}
		else if (strpos($table_data['ausschnitt'], 'DEFAULT-EXCEPT(') === 0){
			$excl_cols = explode(',', substr($table_data['ausschnitt'], 15, -1));
			$select_cols = [];
			foreach ($table_data['col_descriptions'] as $col_name => $col_data){
				if (!in_array($col_name, $excl_cols)){
					$select_cols[] = in_array($col_data['typ'], ['point', 'geometry'])? 'AsText(' . $col_name . ') AS ' . $col_name: $col_name;
				}
				else {
					$cols_missing = true;
				}
			}
			error_log('SELECT ' . implode($select_cols, ',') . ' FROM ' . $table_name . ' ORDER BY RAND() LIMIT 10');
			$example_data = $va_xxx->get_results('SELECT ' . implode($select_cols, ',') . ' FROM ' . $table_name . ' ORDER BY RAND() LIMIT 10', ARRAY_A);
		}
		else {
			$example_data = $va_xxx->get_results($table_data['ausschnitt'], ARRAY_A);
		}
		if (count ($example_data) > 0){
			$grey_found = false;
			$content .= '<div><b>Ausschnitt</b><br /><br />';
		
			if ($table_data['ausschnitt_beschreibung']){
				$content .= $table_data['ausschnitt_beschreibung'] . '<br /><br />';
			}
			$styles = [];
			
			$replacement = '<div style="overflow: auto; height: 300px"><table class="doku_example_table"><thead>';
			foreach (array_keys($example_data[0]) as $col_name) {
				$style = '';
				if (!array_key_exists($col_name, $table_data['col_descriptions'])){
					$style .= ' color: silver; text-transform: none;';
					$grey_found = true;
				}
				$styles[$col_name] = $style;
				
				$col_title = $col_name;
				$pos_dollar = strrpos($col_name, '$');
				if ($pos_dollar !== false){
					$col_title = substr($col_name, 0, $pos_dollar) . ' ([[Tabelle:' . substr($col_name, $pos_dollar + 1) . ']])';
				}
				
				$replacement .= '<th style="' . $style . 'position: sticky; top: 0; background: white;">' . htmlentities($col_title) . '</th>';
			}
			$replacement .= '</tr></thead><tbody>';

			foreach ($example_data as $row) {
				$replacement .= '<tr>';
				foreach ($row as $col_name => $value) {
					$replacement .= '<td style="' . $styles[$col_name] . '">' . htmlentities(addslashes($value)) . '</td>';
				}
				$replacement .= '</tr>';
			}
			$content .= $replacement . '</tbody></table></div>';
			
			if ($grey_found){
				$content .= '<br /<br />(Ausgegraute Spalten sind in der Tabelle selbst nicht enthalten. Sie kommen durch den [[Join|https://dev.mysql.com/doc/refman/8.0/en/join.html]] mit anderen Tabellen zustande und dienen dazu die Beispielsdaten der primären Tabelle besser verständlich zu machen.)';
			}
			
			if ($cols_missing){
				$content .= '<br /<br />(Eine oder mehrere Spalten dieser Tabelle werden nicht dargestellt.)';
			}
		}
		
		// global $va_current_db_name;
		// $content .= sth_parseSQL(['db' => $va_current_db_name, 'query' => str_replace('<br />', '', $table_data['ausschnitt']),  'login' => 'va_wordpress', 'height' => '300px']);
	}
	
	parseSyntax($content, true, true);
	return $content;
}

function va_get_docu_data ($table_name){
	global $va_xxx;
	
	$sql = 'SELECT id_tabelle, beschreibung, prozedur, auto_update, ausschnitt_beschreibung, ausschnitt, veraltet FROM doku_tabellen WHERE name = %s';
	$table_docu = $va_xxx->get_row($va_xxx->prepare($sql, $table_name), ARRAY_A);
	
	if (!$table_docu){
		throw new Exception('Die Tabelle "' . $table_name . '" existiert nicht!');
	}
	
	$sql = 'SELECT id_spalte, id_tabelle, name, typ, schluessel, optional, werte, beschreibung, veraltet FROM doku_spalten WHERE id_tabelle = ' . $table_docu['id_tabelle'];
	$cols_docu = $va_xxx->get_results($sql, ARRAY_A);
	
	$cdata_arr = [];
	foreach ($cols_docu as $col_docu){
		$cdata_arr[$col_docu['name']] = $col_docu;
	}
	
	
	$table_docu['col_descriptions'] = $cdata_arr;
	return $table_docu;
}

function va_check_table_state (){
	global $va_xxx;
	
	$sql = 'SELECT table_name FROM information_schema.tables WHERE table_schema = "va_xxx" ORDER BY table_name ASC';
	$tables_existing = $va_xxx->get_col($sql);
	
	$sql = 'SELECT id_tabelle, name, beschreibung, prozedur, auto_update ausschnitt_beschreibung, ausschnitt, veraltet FROM doku_tabellen';
	$tables_docu = $va_xxx->get_results($sql, ARRAY_A);
	$tables_docu_map = [];
	foreach ($tables_docu as $td){
		$sql = 'SELECT id_spalte, name, beschreibung, typ, schluessel, optional, werte, veraltet FROM doku_spalten WHERE Id_Tabelle = ' . $td['id_tabelle'];
		$td['cols'] = $va_xxx->get_results($sql, ARRAY_A);
		
		$tables_docu_map[$td['name']] = $td;
	}
	
	$sql = 'SELECT id_spalte, id_tabelle, name, typ, schluessel, optional, werte, beschreibung, veraltet FROM doku_spalten';
	$cols_docu = $va_xxx->get_results($sql, ARRAY_A);
	$cols_docu_map = [];
	foreach ($cols_docu as $cd){
		$cols_docu_map[$cd['id_tabelle'] . '-' . $cd['name']] = $cd;
	}
	
	$missing = [];
	$outdated_corr = [];
	$outdated_wrong = [];
	$doku = [];
	$doku_unconn = [];
	
	$table_langs = ['de' => 'deutsch', 'fr' => 'französisch', 'it' => 'italienisch', 'si' => 'slowenisch'];
	
	foreach ($tables_existing as $table_name){
		
		if (!array_key_exists($table_name, $tables_docu_map)){
			
			if (mb_strpos($table_name, 'vap_') === 0){
				$first_underscore = mb_strpos($table_name, '_', 4);
				$second_underscore = mb_strrpos($table_name, '_');
				$type = mb_substr($table_name, 4, $first_underscore - 4);
				$lang = mb_substr($table_name, $second_underscore + 1);
				$tdescr = 'Menschenlesbare Variante der Tabelle [[Tabelle:z_' . $type . ']] in ' . $table_langs[$lang] . 'er Sprache.';
			}
			else {
				$tdescr = '';
			}
			
			$data = ['name' => $table_name, 'beschreibung' => $tdescr, 'veraltet' => 0, 'prozedur' => null, 'auto_update' => null, 'ausschnitt_beschreibung' => null, 'ausschnitt' => null, 'veraltet' => false];
			$va_xxx->insert('doku_tabellen', $data);
			
			$data['id_tabelle'] = $va_xxx->insert_id;
		}
		else {
			$data = $tables_docu_map[$table_name];
		}

		if ($data['veraltet']){
			$outdated_wrong[] = ['table' => $table_name, 'col' => NULL];
		}
		else {
			$sql = 'SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY FROM `INFORMATION_SCHEMA`.`COLUMNS` WHERE `TABLE_SCHEMA`="va_xxx" AND `TABLE_NAME`="' . $table_name . '"';
			$cols_existing = $va_xxx->get_results($sql, ARRAY_A);
			
			$cdata_arr = [];
			foreach ($cols_existing as $col_data){
				$col_name = $col_data['COLUMN_NAME'];
				$col_key = $data['id_tabelle'] . '-' . $col_name;
				
				$sql = 'SELECT REFERENCED_TABLE_NAME, REFERENCED_TABLE_SCHEMA FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = "va_xxx" AND TABLE_NAME = "' . $table_name . '" AND COLUMN_NAME = "' . $col_name . '" AND REFERENCED_TABLE_NAME IS NOT NULL';
				$fkeys = $va_xxx->get_row($sql, ARRAY_A);
				
				if (!array_key_exists($col_key, $cols_docu_map)){
					
					$key_type = '';
					$optional = $col_data['IS_NULLABLE'] === 'YES';
					
					if ($col_data['COLUMN_KEY'] === 'PRI'){
						$key_type = 'P';
					}
					
					if ($fkeys){
						if ($fkeys['REFERENCED_TABLE_SCHEMA'] == 'va_xxx'){
							$ref_table_name = '[[Tabelle:' . $fkeys['REFERENCED_TABLE_NAME'] . ']]';
						}
						else {
							$ref_table_name = '`' . $fkeys['REFERENCED_TABLE_SCHEMA'] . '.' . $fkeys['REFERENCED_TABLE_NAME'] . '`';
						}
						$cdescr = 'Fremdschlüssel, der auf die Tabelle ' . $ref_table_name . ' verweist.';
						$key_type .= 'F';
					}
					else {
						$cdescr = '';
					}
					
					$col_type = $col_data['COLUMN_TYPE'];
					$values = '';
					
					if (mb_strpos($col_type, 'enum') === 0){
						$values = mb_substr($col_type, 5, -1);
						$col_type = 'enum';
					}
					
					$cdata = ['id_tabelle' => $data['id_tabelle'], 'name' => $col_name, 'typ' => $col_type, 'schluessel' => $key_type, 'optional' => $optional, 'werte' => $values, 'beschreibung' => $cdescr, 'veraltet' => 0];
					$va_xxx->insert('doku_spalten', $cdata);
				}
				else {
					$cdata = $cols_docu_map[$col_key];
					
					//Check if an update is needed
					$changes = [];
					$new_values = false; //Don't update values field, since it might be changed manually, except for enums
					$col_type = $col_data['COLUMN_TYPE'];
					
					if (mb_strpos($col_data['COLUMN_TYPE'], 'enum') === 0){
						$values = mb_substr($col_data['COLUMN_TYPE'], 5, -1);
						$col_type = 'enum';
					}
					
					if ($new_values !== false && $new_values != $cdata['werte']){
						$changes['werte'] = $new_values;
					}
					
					if ($col_type != $cdata['typ']){
						$changes['typ'] = $col_type;
					}
					
					$key_type = '';
					
					if ($col_data['COLUMN_KEY'] === 'PRI'){
						$key_type = 'P';
					}
					
					if ($fkeys){
						$key_type .= 'F';
					}
					
					if ($key_type != $cdata['schluessel']){
						$changes['schluessel'] = $key_type;
					}
					
					$optional = $col_data['IS_NULLABLE'] === 'YES'? 1: 0;
					if ($optional != $cdata['optional']){
						$changes['optional'] = $optional;
					}
					
					
					if ($changes){
						$va_xxx->update('doku_spalten', $changes, ['id_spalte' => $cdata['id_spalte']]);
						foreach ($changes as $changed_key => $change){
							$cdata[$changed_key] = $change;
						}
					}
				}
				
				if ($cdata['veraltet']){
					$outdated_wrong[] = ['table' => $table_name, 'col' => $col_name];
				}
				else {
					$cdata_arr[$col_name] = $cdata;
				}
			}
			
			$doku[$table_name] = ['beschreibung' => $data['beschreibung'], 'prozedur' => $data['prozedur'], 'auto_update' => $table_docu['auto_update'], 'ausschnitt_beschreibung' => $table_docu['ausschnitt_beschreibung'], 'ausschnitt' => $table_docu['ausschnitt'], 'col_descriptions' => $cdata_arr, 'veraltet' => $table_docu['veraltet']];
		}
	}
	
	foreach ($tables_docu_map as $tname => $tdata){
		if (!in_array($tname, $tables_existing)){
			if ($tdata['veraltet']){
				$outdated_corr[] = $tname;
			}
			else {
				$doku_unconn[] = ['table' => $tname, 'col' => NULL];
			}
		}
	}
	
		foreach ($cols_docu_map as $cname => $cdata){
		if (!in_array($cname, $cols_existing)){
			if (!$cdata['veraltet']){
				$doku_unconn[] = ['table' => $cdata['id_tabelle'], 'col' => $cname];
			}
		}
	}
	
	return [$doku, $missing, $outdated_corr, $outdated_wrong, $doku_unconn];
}