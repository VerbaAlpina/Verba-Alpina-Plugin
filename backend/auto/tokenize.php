<?php
function tokenizeAeusserungen($id_Stimulus, $vorschau) {
	global $va_xxx;

	$sql = "SELECT Aeusserungen.*,Erhebung from Aeusserungen JOIN Stimuli USING (Id_Stimulus) WHERE Tokenisiert = 0 AND Aeusserung != '<vacat>' AND Aeusserung != '<problem>' AND Aeusserung NOT REGEXP '^<.*>$' AND Id_Stimulus " . $id_Stimulus;
	$results = $va_xxx -> get_results($sql, ARRAY_A);

	$artikel_db = $va_xxx -> get_results('SELECT Artikel, Genus FROM Artikel', ARRAY_N);
	$artikel = array_combine(array_column($artikel_db, 0), array_column($artikel_db, 1));
	$sonderzeichen = array_column($va_xxx -> get_results('SELECT Zeichen FROM Sonderzeichen', ARRAY_N), 0);

	$addedTypesM = array();
	$addedTypesP = array();
	
	if (count($results) > 0) {
		
		//Tokenisierung
		$j = 0;
		foreach ($results as $row) {
			$tokenizedFlagSet = false;
			$commentsInTagBrackets = $row['Erhebung'] != 'ALD-I' && $row['Erhebung'] != 'ALD-II';
			unset($row['Erhebung']);
		
			if($commentsInTagBrackets){
				if (preg_match("/<.*>/U", $row["Aeusserung"])) {
					preg_match_all("/.*(<[^<]*>).*/U", $row["Aeusserung"], $parts, PREG_PATTERN_ORDER);
					$partsOrig[1] = $parts[1];
					$parts[1] = preg_replace("/[ 	]+/", '§§§', $parts[1]);
					$parts[1] = preg_replace("/,/", '###', $parts[1]);
					$parts[1] = preg_replace("/;/", '~~~', $parts[1]);
					$row["Aeusserung"] = str_replace($partsOrig[1], $parts[1], $row["Aeusserung"]);
				}
				//Leerzeichen vor Tagklammern entfernen => Wort + Bemerkungen sind ein Token
				$row["Aeusserung"] = preg_replace("/[ 	]+</", "<", $row["Aeusserung"]);
			}
			$row["Aeusserung"] = preg_replace("/[ 	]+/", " ", $row["Aeusserung"]);
			// entfernung von ueberfluessigen leerzeichen

			$ebene_1 = split(";", trim($row["Aeusserung"]));

			unset($row["Aeusserung"]);
			unset($row["Tokenisiert"]);
			
			$klass = $row["Klassifizierung"];			
			unset($row["Klassifizierung"]);

			$bemerkungA = $row['Bemerkung'];
			
			foreach ($ebene_1 as $k => $v) {
				$ebene_2 = split(",", trim($v));
				$kk = 0;
				do {
					$vv = $ebene_2[$kk];
			
					$str_bemerkung = '';
					if($commentsInTagBrackets){
						$vv = preg_replace_callback('/([^<]*)(<([^<]+)>)+[ 	]*/U', function($treffer) use (&$str_bemerkung) {
							$str_bemerkung .= $treffer[3] . ' ';
							return $treffer[1];
						}, addslashes($vv));
						
						$str_bemerkung = str_replace('§§§', ' ', $str_bemerkung);
						$str_bemerkung = str_replace('###', ',', $str_bemerkung);
						$str_bemerkung = str_replace('~~~', ';', $str_bemerkung);
					}
					
					//Dupliziere Belege mit mehrfacher Genus-Information
					$matches = array();
					$preg_str = '/(?:^|[ .,;])([mfn])(?:$|[ .,;])/';
					preg_match_all($preg_str, $str_bemerkung, $matches);
					$num_gen = count($matches[1]);
					if($num_gen > 1){
						$bemerkungOhneGenus = preg_replace($preg_str,'', $str_bemerkung);
						
						//Update Bemerkung
						$str_bemerkung = $matches[1][0] . '. ' . $bemerkungOhneGenus;
												
						//Füge zusätzliche Werte hinzu
						for ($gi = 1; $gi < $num_gen; $gi++){
							$bemerkungNeu = $matches[1][$gi] . '. ' . $bemerkungOhneGenus;
							$bemerkungNeu = str_replace(' ', '§§§', $bemerkungNeu);
							$bemerkungNeu = str_replace(',', '###', $bemerkungNeu);
							$bemerkungNeu = str_replace(';', '~~~', $bemerkungNeu);
							array_splice($ebene_2, $kk + $gi, 0, array($vv . '<' . $bemerkungNeu . '>'));
						}
					}
					
					//Erstelle Bemerkungs-Spalte
					if (strlen($str_bemerkung) > 0) {
							$row['Bemerkung'] = ($bemerkungA == ''? '': $bemerkungA . ' ') . substr($str_bemerkung, 0, strlen($str_bemerkung) - 1);
					} else {
						$row['Bemerkung'] = $bemerkungA;
					}
					
					$current_gender = '';
					
					if($klass == 'M'){
						$mtokens = explode(' ', trim($vv));
						if(count($mtokens) == 1){
							$komp_typ[$j] = false;
						}
						else {
							$first_word = trim($mtokens[0]);
							if(count($mtokens) == 2 && array_key_exists(stripslashes($first_word), $artikel)){
								//Transformiere z.B. "Der Tennen" nach "Tennen, m."
								$komp_typ[$j] = false;
								if($artikel[stripslashes($first_word)] != 'x'){
									$current_gender = $artikel[stripslashes($first_word)];
								}
								$row['Bemerkung'] .= ' ' . trim($vv) . ' -> ';
								$vv = trim($mtokens[1]);
							}
							else {
								$komp_typ[$j] = true;
							}
						}
					}
					
					$row['Bemerkung'] = addslashes($row['Bemerkung']);
					
					if($komp_typ[$j]){ //nicht tokenisieren, es wird ein leerer Beleg erstellt, dem ein Kompositions-Typ zugewiesen wird
						$ebene_3 = array(trim($vv));
					}
					else { //sonst tokenisieren
						$ebene_3 = split(" ", trim($vv));
					}
					
					$length_tokengruppe = sizeof($ebene_3);
					
					//Durchsuche Bemerkung nach Genus-Information
					
					if(preg_match('/(^|[ .,;])m($|[ .,;])/', $row['Bemerkung'])){
						$current_gender = 'm';
					}
					if(preg_match('/(^|[ .,;])f($|[ .,;])/', $row['Bemerkung'])){
						$current_gender = 'f';
					}
					if(preg_match('/(^|[ .,;])n($|[ .,;])/', $row['Bemerkung'])){
						$current_gender = 'n';
					}
					
					foreach ($ebene_3 as $kkk => $vvv) {
						
						$row['Ebene_1'] = $k + 1;
						$row['Ebene_2'] = $kk + 1;
						$row['Ebene_3'] = $kkk + 1;
						$row['Token'] = $vvv;

						//Erstelle Typzuweisungen bzw. neue Typen für typisierte Belege
						if($klass != 'B'){
							
							//Entferne doppelte Backslashes (sollten eigentlich beim Transkribieren von Typen gar nicht benutzt werden)
							$row['Token'] = str_replace('\\', '', $row['Token']);
							
							//Ersetze im Beta-Code transkribierte Umlaute in morph. Typen
							if($klass == 'M'){
								$row['Token'] = str_replace('u:', 'ü', $row['Token']);
								$row['Token'] = str_replace('o:', 'ö', $row['Token']);
								$row['Token'] = str_replace('a:', 'ä', $row['Token']);
							}
							
							$quelle = $va_xxx->get_var("SELECT Erhebung FROM Stimuli WHERE Id_Stimulus = " . $row['Id_Stimulus'], 0, 0);
							if($klass == 'M'){
								if(!in_array(array($row['Token'], $current_gender), $addedTypesM)){
									if($komp_typ[$j]){ //Kompositionstyp
										$typ = $va_xxx->get_var("SELECT Orth FROM morph_Typen WHERE Orth = '" . $row['Token'] . "' AND Genus = '$current_gender'", 0, 0);
										if(!$typ){
											$addedTypesM[] = array($row['Token'], $current_gender);
											$insertsT[$j] = "INSERT INTO morph_Typen (Orth, Genus, Quelle, Kommentar_Intern) VALUES('" . $row['Token'] . "', '" . $current_gender . "', '$quelle', '" . $row['Bemerkung'] . "');\n";
										}
										else
											$insertsT[$j] = '';
									}
									else { //Einfacher morph. Typ
										$typ = $va_xxx->get_var("SELECT Orth FROM morph_Typen WHERE Orth = '" . $row['Token'] . "' AND Genus = '$current_gender'", 0, 0);
										if(!$typ){
											$addedTypesM[] = array($row['Token'], $current_gender);
											$insertsT[$j] = "INSERT INTO morph_Typen (Orth, Genus, Quelle, Kommentar_Intern) VALUES('" . $row['Token'] . "', '" . $current_gender . "', '$quelle', '" . $row['Bemerkung'] . "');\n";
										}
										else
											$insertsT[$j] = '';
									}
								}
								else
									$insertsT[$j] = '';
									
								if($komp_typ[$j]){
									$insertsV[$j] = "INSERT INTO VTBL_Tokengruppe_morph_Typ (Id_Tokengruppe, Id_morph_Typ, Quelle, Angelegt_Von, Angelegt_Am) SELECT %%%, Id_morph_Typ, '$quelle', NULL, NOW() FROM morph_Typen WHERE Orth = '" . $row['Token'] . "' AND Genus = '$current_gender' LIMIT 1;\n";
								}
								else {
									$insertsV[$j] = "INSERT INTO VTBL_Token_morph_Typ (Id_Token, Id_morph_Typ, Quelle, Angelegt_Von, Angelegt_Am) SELECT %%%, Id_morph_Typ, '$quelle', NULL, NOW() FROM morph_Typen WHERE Orth = '" . $row['Token'] . "' AND Genus = '$current_gender' LIMIT 1;\n";
								}
							}
							else if($klass == 'P'){
								if(!in_array(array($row['Token'], $current_gender), $addedTypesP)){
									$typ = $va_xxx->get_var("SELECT Beta FROM phon_Typen WHERE Beta = '" . $row['Token'] . "' AND Genus = '$current_gender'", 0, 0);
									if(!$typ){
										$addedTypesP[] = array($row['Token'], $current_gender);
										$insertsT[$j] = "INSERT INTO phon_Typen (Beta, Quelle, Genus, Kommentar_Intern) VALUES('" . $row['Token'] . "', '$quelle', '" . $current_gender . "', '" . $row['Bemerkung'] . "');\n";
									}
									else
										$insertsT[$j] = '';
								}
								else
									$insertsT[$j] = '';
								$insertsV[$j] = "INSERT INTO VTBL_Token_phon_Typ (Id_Token, Id_phon_Typ, Quelle, Angelegt_Von, Angelegt_Am) SELECT %%%, Id_phon_Typ, '$quelle', NULL, NOW() FROM phon_Typen WHERE Beta = '" . $row['Token'] . "' AND Genus = '$current_gender' LIMIT 1;\n";
							}
							
							if(!$row['Bemerkung'])
								$row['Bemerkung'] = $quelle . '-Typ ' . $row['Token'];
							else
								$row['Bemerkung'] .= ' ' . $quelle . '-Typ ' . $row['Token'];
							$row['Token'] = '';
						}
						else {
							$insertsT[$j] = '';
							$insertsV[$j] = '';
						}
						
						//Tokengruppen Information
						$row['Id_Tokengruppe'] = '%%%'; //Platzhalter für eventuell benötigte Tokengruppe
						
						if(sizeof($ebene_3) > 1 && $row["Ebene_3"] != sizeof($ebene_3)){ //Mehrere Tokens, aber das aktuelle ist nicht das letzte
							if($row['Token'][strlen($row['Token']) - 1] == '{'){
								//Letztes Zeichen { => das Token muss durch { } vom nächsten Token getrennt sein
								$row['Trennzeichen'] = '{ }';
								$row['Token'] = substr($row['Token'], 0, strlen($row['Token']) - 1);
							}
							else {
								$row['Trennzeichen'] = ' ';
							}
						}
						else {
							$row['Trennzeichen'] = 'NULL';
						}
						if($row["Ebene_3"] == sizeof($ebene_3) && $row['Token'][0] == '}'){
							$row['Token'] = substr($row['Token'], 1);
						}
						
						//Setzte Tokenisiert-Flag
						if(!$tokenizedFlagSet){
							$updates[$j] .= 'UPDATE Aeusserungen SET Tokenisiert = 1 WHERE ID_Aeusserung = ' . $row['Id_Aeusserung'] . ";\n";
							$tokenizedFlagSet = true;
						}
						
						
						//Konzeptzuweisung
						if (sizeof($ebene_3) == 1) {
							if(!$komp_typ[$j]){ //Nur ein Token => Konzept dem Token zuweisen
								$insertsKonz[$j] .= 'INSERT INTO VTBL_Token_Konzept (SELECT %%%, Id_Konzept FROM VTBL_Aeusserung_Konzept WHERE ID_Aeusserung =  ' . $row['Id_Aeusserung'] . ");\n";
								$row['Genus'] = $current_gender;
							}
						} else {
							if (in_array(stripslashes($vvv), $sonderzeichen)) { //Aktuelles Token ist Sonderzeichen => 779 zuweisen
								$insertsKonz[$j] .= "INSERT INTO VTBL_Token_Konzept VALUES (%%%, 779);\n";
								$length_tokengruppe--; //Sonderzeichen werden nicht für die Tokengruppe gezählt
							}
							else if (array_key_exists(stripslashes($vvv), $artikel)) { //Aktuelles Token ist Artikel => 699 zuweisen
								$insertsKonz[$j] .= "INSERT INTO VTBL_Token_Konzept VALUES (%%%, 699);\n";
								$length_tokengruppe--; //Artikel werden nicht für die Tokengruppe gezählt
								if($artikel[stripslashes($vvv)] != 'x'  && $current_gender == '' && $row['Ebene_3'] == 1)
									$current_gender = $artikel[stripslashes($vvv)]; //Falls es Genus-Information in der Bemerkung und durch den Artikel gibt, wird die aus der Bemerkung verwendet
							}
							else if ((sizeof($ebene_3) == 2 || (sizeof($ebene_3) == 3 && in_array(stripslashes($ebene_3[2]), $sonderzeichen))) && array_key_exists(stripslashes($ebene_3[0]), $artikel) && $row["Ebene_3"] == 2) { //Einzelnes Token nach Artikel bzw. Token von Artikel und Sonderzeichen eingeschlossen => Token das Konzept zuweisen
								$insertsKonz[$j] .= 'INSERT INTO VTBL_Token_Konzept (SELECT %%%, Id_Konzept FROM VTBL_Aeusserung_Konzept WHERE ID_Aeusserung =  ' . $row['Id_Aeusserung'] . ");\n";
								$row['Genus'] = $current_gender;
							} else {
								$insertsKonz[$j] .= '';
							}
						}
						
						//Einfügen des/der Tokens
						$inserts[$j] = "insert into tokens (" . implode(",", array_keys($row)) . ") values \n\t(" . implode(",", array_map(function($v) {
							if($v == 'NULL' || $v == '%%%')
								return $v;
							else
								return "'" . $v . "'";
						}, array_values($row))) . ");\n";

						$j++;
					}

					//Tokengruppe erstellen?
					for ($n = sizeof($ebene_3); $n > 0 ; $n--) {
						//Abzüglich Sonderzeichen und Artikel mehr als ein Token oder kompTyp? => Tokengruppe erstellen
						if($length_tokengruppe > 1 || $komp_typ[$j-1]) {
							if($n == sizeof($ebene_3)){
								$insertsGroup[$j - $n] = "INSERT INTO Tokengruppen (Genus, Bemerkung) VALUES ('" . $current_gender . "', '" . $str_bemerkung . "')\n";
								$insertsTGK[$j - $n] = 'INSERT INTO VTBL_Tokengruppe_Konzept (SELECT %%%, Id_Konzept FROM VTBL_Aeusserung_Konzept WHERE ID_Aeusserung =  ' . $row['Id_Aeusserung'] . ");\n";
							}
							else {
								$insertsGroup[$j - $n] = 'R'; //Platzhalter
								$insertsTGK[$j - $n] = '';
							}
						}
						else {
							$insertsGroup[$j - $n] = '';
							$insertsTGK[$j - $n] = '';
						}
					}
					
					$kk++;	
				}
				while ($kk < count($ebene_2));
			}
		}
		
		//Ausgabe und Ausführung der SQL-Befehle
		$ret = '';
		for ($i = 0; $i < $j; $i++) {
			if(!$vorschau && $insertsGroup[$i] && $insertsGroup[$i] != 'R'){
				$va_xxx->query($insertsGroup[$i]);
				$group_id = $va_xxx -> insert_id;
			}
				
			if($insertsGroup[$i]){
				$mi = str_replace('%%%', $group_id, $inserts[$i]);
			}
			else {
				$mi = str_replace('%%%', 'NULL', $inserts[$i]);
			}
			
			if (!$vorschau){
				$va_xxx -> query($mi);
			}
			
			if ($insertsKonz[$i] != '')
				//TODO mehrere Belege
				$uk = str_replace('%%%', $va_xxx -> insert_id, $insertsKonz[$i]);
			else
				$uk = '';
				
			if($insertsV[$i] != '')
				if($komp_typ[$i])
					$uv = str_replace('%%%', $group_id, $insertsV[$i]);
				else
					//TODO mehrere Belege
					$uv = str_replace('%%%', $va_xxx -> insert_id, $insertsV[$i]);
			else
				$uv = '';
			
			if($insertsTGK[$i] != '')
				$utg = str_replace('%%%', $group_id, $insertsTGK[$i]);
			else
				$utg = '';
			
			if (!$vorschau) {
				$va_xxx->query($insertsT[$i]);
				$va_xxx -> query($updates[$i]);
				if ($uk != '')
					$va_xxx -> query($uk);
				if ($uv != '')
					$va_xxx -> query($uv);
				if ($utg != '')
					$va_xxx -> query($utg);
			}
			$ret .= htmlentities(($insertsGroup[$i] == 'R'? '' : $insertsGroup[$i]) . $mi . $insertsT[$i] . $uv . $updates[$i] . $uk . $utg . "\n");
		}
	}
	return '<pre>' . $ret . '</pre>';
}

function anzahlAeusserungen($id_Stimulus) {
	global $va_xxx;

	$sql = "select count(*), (select count(*) from Aeusserungen WHERE Aeusserung != '<vacat>' AND Aeusserung != '<problem>' AND Id_Stimulus " . $id_Stimulus . ") from Aeusserungen where Tokenisiert = 0 AND Aeusserung != '<vacat>' AND Aeusserung != '<problem>'  AND Aeusserung NOT REGEXP '^<.*>$' AND Id_Stimulus " . $id_Stimulus;
	$result = $va_xxx -> get_results($sql, ARRAY_N);
	return '<table border="2">
		<tr>
			<th>Äußerungen gesamt</th>
			<th>Nicht tokenisiert</th>
		</tr>
		<tr>
			<td>' . $result[0][1] . '</td>
			<td>' . $result[0][0] . '</td>
		</tr>
	</table>';
}
?>