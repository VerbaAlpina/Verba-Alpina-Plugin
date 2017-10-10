<?php
function createTypeOverlay (&$db, $dbname){
?>

<style type="text/css">
	#auswahlBestandteile_chosen .chosen-drop, #auswahlReferenz_chosen .chosen-drop, #auswahlBasistyp_chosen .chosen-drop {
		border-bottom: 0;
		border-top: 1px solid #aaa;
		top: auto;
		bottom: 40px;
	}
</style>

<div id="VATypeOverlay" title="Morpho-Lexikalischer Typ" style="display : none">
	<form name="eingabeMorphTyp">
		<h2>Typ-Information</h2>
		<table>
			<tr>
				<td>Orth</td>
				<td><input name="Orth" type="text" /></td>
			</tr>
			<tr>
				<td>Sprache</td>
				<td><?php echo im_enum_select('morph_Typen', 'Sprache', 'Sprache', '---', false, '', NULL, $dbname);?></td>
			</tr>
			<tr>
				<td>Wortart</td>
				<td><?php echo im_enum_select('morph_Typen', 'Wortart', 'Wortart', '---', true, '', NULL, $dbname);?></td>
			</tr>
			<tr>
				<td>Affix</td>
				<td><input name="Affix" type="text" /></td>
			</tr>
			<tr>
				<td>Genus</td>
				<td><?php echo im_enum_select('morph_Typen', 'Genus', 'Genus', '---', false, '', NULL, $dbname);?></td>
			</tr>
			<tr>
				<td>Kommentar_Intern</td>
				<td><textarea  name="Kommentar_Intern"></textarea></td>
			</tr>
		</table>
	</form>
		
	<h2>Bestandteile</h2>
	<select id="auswahlBestandteile" multiple="multiple">
		<?php
		$parts = $db->get_results("SELECT Id_morph_Typ, lex_unique(Orth, Sprache, Genus) FROM morph_Typen WHERE Quelle = 'VA'", ARRAY_N);
		foreach ($parts as $part){
			echo "<option value='{$part[0]}'>{$part[1]}</option>";
		}
		?>
	</select>
	
	<h2>Zugeordnete Referenzen</h2>
	<select id="auswahlReferenz" multiple="multiple">
		<?php
		$lemmas = $db->get_results('SELECT * FROM Lemmata', ARRAY_A);
		foreach ($lemmas as $lemma){
			$genus_info = ' (' . str_replace('+', ',', $lemma['Genera']) . ')';
			echo "<option value='{$lemma['Id_Lemma']}'>{$lemma['Quelle']}: {$lemma['Subvocem']}" . ($genus_info != ' ()'? $genus_info : '') . "</option>";
		}
		?>
	</select>
	
	<input type="button" class="button button-primary" id="newReferenceButton" value="Neue Referenz anlegen">
	
	<h2>Zugeordnete Basistypen</h2>
	<select id="auswahlBasistyp">
		<?php
		$btypes = $db->get_results("SELECT Id_Basistyp, Orth FROM Basistypen WHERE Quelle = 'VA'", ARRAY_N);
		foreach ($btypes as $btype){
			echo "<option value='{$btype[0]}'>{$btype[1]}</option>";
		}
		?>
	</select>

	<input type="button" class="button button-primary" id="newBaseTypeButton" value="Neuen Basistyp anlegen" />
	
	<table id="baseTypeTable">
		<tbody>
			
		</tbody>
	</table>
	
	<br />
	<br />
	<br />
	
	<input type="button" class="button button-primary" id="newMTypeButton" value="Bestätigen" />
	<input type="hidden" id="saveCaller" />
</div>

<?php

	echo im_table_entry_box('NeuerBTypFuerZuweisung', new IM_Row_Information('Basistypen', array(
		new IM_Field_Information('Orth', 'V', true),
		new IM_Field_Information('Alpenwort', 'B', false),
		new IM_Field_Information('Kommentar_Intern', 'T', false)
	), 'Angelegt_Von'), $dbname);

	echo im_table_entry_box('NeueReferenzFuerZuweisung', new IM_Row_Information('Lemmata', array(
		new IM_Field_Information('Quelle', 'F WHERE Referenzwoerterbuch', true),
		new IM_Field_Information('Subvocem', 'V', true),
		new IM_Field_Information('Genera', 'S', false),
		new IM_Field_Information('Bibl_Verweis', 'V', false),
		new IM_Field_Information('Link', 'V', false),
		new IM_Field_Information('Kommentar_Intern', 'T', false)
	), 'Angelegt_Von'), $dbname);
	
	echo im_table_entry_box('NeuesKonzept', new IM_Row_Information('Konzepte', array(
		new IM_Field_Information('Name_D', 'V', false),
		new IM_Field_Information('Beschreibung_D', 'V', true),
		new IM_Field_Information('Kategorie', 'E', true, true),
		new IM_Field_Information('Hauptkategorie', 'E', true, true),
		new IM_Field_Information('Relevanz', 'B', false, true, true),
		new IM_Field_Information('Pseudo', 'B', false, true),
		new IM_Field_Information('Grammatikalisch', 'B', false, true)
	), 'Angelegt_Von'), $dbname);
}
?>