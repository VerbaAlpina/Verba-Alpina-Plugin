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
				<td>Praefix</td>
				<td><input name="Praefix" type="text" /></td>
			</tr>
			<tr>
				<td>Infix</td>
				<td><input name="Infix" type="text" /></td>
			</tr>
						<tr>
				<td>Suffix</td>
				<td><input name="Suffix" type="text" /></td>
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
	<select id="auswahlBestandteile" multiple="multiple" style="min-width: 400px;">
		<?php
// 		$parts = $db->get_results("SELECT Id_morph_Typ, lex_unique(Orth, Sprache, Genus) FROM morph_Typen WHERE Quelle = 'VA'", ARRAY_N);
// 		foreach ($parts as $part){
// 			echo "<option value='{$part[0]}'>{$part[1]}</option>";
// 		}
		?>
	</select>
	
	<h2>Zugeordnete Referenzen</h2>
	<select id="auswahlReferenz" multiple="multiple" style="min-width: 400px;"></select>
	
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

	echo createBaseTypeOverlay($db, $dbname);

	echo im_table_entry_box('NeueReferenzFuerZuweisung', new IM_Row_Information('Lemmata', array(
		new IM_Field_Information('Quelle', 'F WHERE Referenzwoerterbuch', true),
		new IM_Field_Information('Subvocem', 'V', true),
		new IM_Field_Information('Genera', 'S', false),
		new IM_Field_Information('Text_Referenz', 'B', true, false, 0, false, false, va_get_info_symbol('Gibt an, dass die Referenz sich nicht auf das im Feld Subvocem genannte Lemma bezieht, sondern auf eine im entsprechenden Eintragstext genannte Form.')),
		new IM_Field_Information('Bibl_Verweis', 'V', false),
		new IM_Field_Information('Link', 'V', false),
		new IM_Field_Information('Kommentar_Intern', 'T', false)
	), 'Angelegt_Von'), $dbname);
	
	va_echo_new_concept_fields('NeuesKonzept');
}

function createProblemOverlay (&$db, $dbname){
    ?>
    <div id="VAProblemOverlay" title="<?php _e('Problem description', 'verba-alpina'); ?>" style="display : none">
    	<input type="hidden" id="problemDescId" />
    	<table>
    		<tr>
    			<td style="font-weight: bold; padding-right: 20px;"><?php _e('Stimulus', 'verba-alpina'); ?></td>
    			<td><input type="text" disabled id="problemStimulus" style="min-width: 400px;" /></td>
    		</tr>
    		<tr>
    			<td style="font-weight: bold; padding-right: 20px;"><?php _e('Attestation', 'verba-alpina'); ?></td>
    			<td><input type="text" disabled id="problemRecord" style="min-width: 400px;" /></td>
    		</tr>
    		<tr>
    			<td style="font-weight: bold; padding-right: 20px;"><?php _e('Comment', 'verba-alpina'); ?></td>
    			<td><textarea rows="6" cols="50"  id="problemComment"></textarea></td>
    		</tr>
    		<tr>
    			<td style="font-weight: bold; padding-right: 20px;"><?php _e('Type proposal', 'verba-alpina'); ?></td>
    			<td style="padding-top: 30px;"><?php _e('Existing type', 'verba-alpina'); ?>: <select id="problemType" style="min-width: 300px;"></select><br /><br />
    			<?php _e('Or new type', 'verba-alpina');?> <input type="text" id="problemNewType" style="min-width: 300px;" /></td>
    		</tr>
    		<tr>
    			<td style="font-weight: bold; padding-right: 20px"><?php _e('References', 'verba-alpina'); ?></td>
    			<td style="padding-top: 30px;"><table id="problemRefTable"></table></td>
    		</tr>
    	</table>
    	<input type="button" value="<?php _e('Add reference', 'verba-alpina'); ?>" class="button button-secondary problemRefButton" />
    	 <br />
    	 <br />
    	 <br />
    	 <input type="button" id="problemConfirm" style="float: right;" value="<?php _e('Create problem description', 'verba-alpina'); ?>" class="button button-primary" />
    </div>
    <?php   
}

function createBaseTypeOverlay (&$db, $dbname, $edit = false){
	ob_start();
	?>
	<div id="VABasetypeOverlay" title="Basistyp" style="display : none">
		<form name="eingabeBasistyp">
			<h2>Typ-Information</h2>
			<table>
				<tr>
					<td>Orth:</td>
					<td><input name="Orth" type="text" /></td>
				</tr>
				<tr>
					<td>Sprache:</td>
					<td>
						<select id="auswahlLangBasetype" name="Sprache">
						<?php
						$langs = $db->get_results("SELECT Abkuerzung, Bezeichnung_D FROM Sprachen WHERE Basistyp_Sprache", ARRAY_A);
						foreach ($langs as $lang){
							echo "<option value='{$lang['Abkuerzung']}'>{$lang['Bezeichnung_D']}</option>";
						}
						?>
						</select>
					</td>
				</tr>
				<tr>
					<td>Alpenwort:</td>
					<td><input name="Alpenwort" type="checkbox" /></td>
				</tr>
				<tr>
					<td>Kommentar_Intern:</td>
					<td><textarea name="Kommentar_Intern" /></textarea></td>	
				</tr>
			</table>
		
			<h2>Zugeordnete Referenzen</h2>
			<select id="auswahlReferenzBasetype" multiple="multiple" style="min-width: 400px;"></select>
			
			<input type="button" class="button button-primary" id="newBasetypeReferenceButton" value="Neue Referenz anlegen">
		</form>
		
		<br />
		<br />
		<br />
		
		<input type="button" class="button button-primary" id="newBTypeButton" value="<?php echo ($edit? 'Ändern' : 'Einfügen'); ?>" />
	</div>
	<?php
	
	echo im_table_entry_box('NeueReferenzFuerBasistyp', new IM_Row_Information('Lemmata_Basistypen', array(
			new IM_Field_Information('Quelle', 'F WHERE Referenz_Basistyp', true),
			new IM_Field_Information('Subvocem', 'V', true),
			new IM_Field_Information('Text_Referenz', 'B', true, false, 0, false, false, va_get_info_symbol('Gibt an, dass die Referenz sich nicht auf das im Feld Subvocem genannte Lemma bezieht, sondern auf eine im entsprechenden Eintragstext genannte Form.')),
			new IM_Field_Information('Bibl_Verweis', 'V', false),
			new IM_Field_Information('Link', 'V', false),
			new IM_Field_Information('Kommentar_Intern', 'T', false)
	), 'Angelegt_Von'), $dbname);
	
	return ob_get_clean();
}
?>