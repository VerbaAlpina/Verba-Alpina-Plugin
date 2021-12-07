<?php
function va_tools_dibs_import (){
	global $va_xxx;

	//Import Informanten
	// insert into va_xxx.informanten (Erhebung, Nummer, Ortsname, Georeferenz, Alpenkonvention)
	// select 'DIBS', CONCAT(o.Landkreis, "-", o.Ort), o.ort, GeomFromText(CONCAT('POINT(', lon, ' ', lat, ')')),
	// ST_WITHIN(GeomFromText(CONCAT('POINT(', lon, ' ', lat, ')')), (select Geodaten from va_xxx.orte where ID_Kategorie = 1))
	// from orte o
	// where 
	// o.lat is not null and o.lon is not null and 
	// (select Id_VA_Informant from va_informanten v where v.Ort = o.Ort and v.landkreis = o.landkreis) is NULL;

	// call va_xxx.setCommunityIDs();

	// update va_informanten set Id_VA_Informant = 
	// (select Id_Informant from va_xxx.informanten where Erhebung = 'DIBS' and Nummer collate 'utf8_bin' = CONCAT(Landkreis, "-", Ort))
	// where Id_VA_Informant is null;
			
			
	try {	
		$sql = 'SELECT DISTINCT le.lemma, lautung, be.bedeutung, o2.ort, o2.landkreis, zusatz
			FROM pva_dibs.lemmata le 
				JOIN pva_dibs.lautungen la ON le.id = la.lemma
				JOIN pva_dibs.bedeutungen be ON le.id = be.lemma
				JOIN pva_dibs.bedeutungen_orte bo ON bo.bedeutung = be.id AND bo.ort = la.ort
				JOIN pva_dibs.orte o2 ON o2.id = bo.ort
			WHERE le.id IN (4803, 46217)
			ORDER BY le.lemma, o2.ort ASC';
				
		$data = $va_xxx->get_results($sql, ARRAY_A);
		
		echo '<table>';
		
		foreach ($data as $row){
			echo '<tr>';
			foreach ($row as $val){
				echo '<td>' . $val . '</td>';
			}
			echo '</tr>';
		}		
		
		echo '</table>';
		
		foreach($data as $row){
			$informants = va_tools_dibs_find_informants($row['ort'], $row['landkreis']);
			$meaning = va_tools_dibs_get_meaning($row['bedeutung']);
			$stimulus = va_tools_dibs_get_stimulus($row['lemma']);
			
			foreach ($informants as $id_inf){
				if (!$row['zusatz']){
					$row['zusatz'] = '';
				}
				
				// $adata = ['Id_Stimulus' => $stimulus, 'Id_Informant' => $id_inf, 'Aeusserung' => $row['lautung'], 'Erfasst_Von' => 'admin', 'Bemerkung' => $row['zusatz']];
				// $va_xxx->insert('aeusserungen', $adata);
				// $id_a = $va_xxx->insert_id;
				
				// $va_xxx->insert('vtbl_aeusserung_bedeutung', ['Id_Aeusserung' => $id_a, 'Id_Bedeutung' => $meaning]);
				
				// $va_xxx->select('pva_dibs');
				// $va_xxx->insert('va_import', $row);
				// $va_xxx->select('va_xxx');
			}
		}
		
	}
	catch (Exception $e){
		echo $e->getMessage();
	}
}

function va_tools_dibs_find_informants ($ort, $lk){
	global $va_xxx;
	
	//Simple location
	$id_inf = $va_xxx->get_var($va_xxx->prepare('SELECT id_va_informant FROM pva_dibs.va_informanten WHERE ort = %s and landkreis = %s', $ort, $lk));
	
	if ($id_inf){
		return [$id_inf];
	}
	
	//Region
	$informants = $va_xxx->get_col($va_xxx->prepare('
		SELECT id_va_informant 
		FROM pva_dibs.va_orte_regionen r JOIN pva_dibs.va_informanten o ON r.ort = o.ort AND r.landkreis = o.landkreis
		WHERE r.region = %s', $ort));
		
	if (!$informants){
		throw Exception('Region "' . $ort . '" not found!');
	}
	
	return $informants;
}

function va_tools_dibs_get_meaning ($str){
	global $va_xxx;
	
	$id = $va_xxx->get_var($va_xxx->prepare('SELECT id_bedeutung FROM bedeutungen WHERE bedeutung = %s AND sprache = "deu"', $str));
	
	if (!$id){
		$va_xxx->insert('bedeutungen', ['Sprache' => 'deu', 'Bedeutung' => $str]);
		$id = $va_xxx->insert_id;
	}
	
	return $id;
}

function va_tools_dibs_get_stimulus ($lemma){
	global $va_xxx;
	
	$id = $va_xxx->get_var($va_xxx->prepare('SELECT id_stimulus FROM Stimuli WHERE Erhebung = "DIBS" AND Karte = %s', $lemma));
	
	if (!$id){
		$va_xxx->insert('stimuli', ['Erhebung' => 'DIBS', 'Karte' => $lemma, 'Nummer' => 1, 'Stimulus' => 'Lemma "' . $lemma . '"']);
		$id = $va_xxx->insert_id;
	}
	
	return $id;
}