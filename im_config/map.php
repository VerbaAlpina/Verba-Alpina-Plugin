<?php 

function create_va_map (){

global $lang;
global $Ue;
global $admin;
global $va_mitarbeiter;
global $va_current_db_name;


$concepts = IM_Initializer::$instance->database->get_results("
								SELECT 
									Id_Konzept,
									Name_$lang AS Name,
									Beschreibung_$lang AS Beschreibung, 
									Kategorie, 
									Name_$lang != '' OR Id_Ueberkonzept = 707 AS Anzeige,
									Anzahl_Allein AS Anzahl_Belege,
									Dateiname
								from
									A_Anzahl_Konzept_Belege
									JOIN Konzepte USING (Id_Konzept)
								order by IF(Name_$lang = '', Beschreibung_$lang, Name_$lang)", ARRAY_A);

$concepts_JS = array();				
foreach ($concepts as $concept){
	$children = IM_Initializer::$instance->database->get_col('SELECT Id_Konzept FROM A_Anzahl_Konzept_Belege WHERE Id_Ueberkonzept = ' . $concept['Id_Konzept']);
	$concepts_JS[$concept['Id_Konzept']] = array ($concept['Name'], $concept['Beschreibung'], $concept['Anzahl_Belege'], $children, $concept['Dateiname']);
}

$sql_extra = "
		SELECT Id_Category, Category_Level_1, Category_Level_2, Category_Level_3, Category_Level_4, Category_Level_5, Category_Name, 
			GROUP_CONCAT(DISTINCT Tags),
			GeometryType(Geo_data) != 'POLYGON' AND GeometryType(Geo_data) != 'MULTIPOLYGON'
		FROM Z_Geo
		GROUP BY Id_Category
		ORDER BY Category_Level_1, Category_Level_2, Category_Level_3, Category_Level_4, Category_Level_5";
$extra_cats = IM_Initializer::$instance->database->get_results($sql_extra, ARRAY_N);
$eling_JS = array();
foreach ($extra_cats as $ecat){
	$te = va_sub_translate($ecat[6], $Ue);
	$eling_JS[$ecat[0]][0] = $te;
	if($ecat[7]){
		$tagValuePairs = explode(',', $ecat[7]);
		$tagList = array();
		foreach ($tagValuePairs as $tfc){
			$tagValuePair = json_decode($tfc);
			foreach ($tagValuePair as $key => $value){
				if(!isset($tagList[$key])){
					$tagList[$key] = array();
				}
				array_push($tagList[$key], $value);
			}
		}
		$eling_JS[$ecat[0]][1] = $tagList;
	}
	else {
		$eling_JS[$ecat[0]][1] = NULL;
	}
}

$tagValues = IM_Initializer::$instance->database->get_col('SELECT DISTINCT Wert FROM Orte_Tags');

//TODO Better solutation! Don't abuse getElementName!!!
$tagNames = IM_Initializer::$instance->database->get_col('SELECT DISTINCT Tag FROM Orte_Tags');
$tagValues = array_merge($tagValues, $tagNames);

wp_localize_script ('im_map_script', 'Ue', $Ue);
wp_localize_script ('im_map_script', 'Concepts', $concepts_JS);
wp_localize_script ('im_map_script', 'ELing', $eling_JS);
wp_localize_script ('im_map_script', 'TagValues', $tagValues);

?>
<div id="<?php echo im_main_div_class();?>">
	<table id="mainTable" style="width: 100%; height: 100%">
		<tr style="width: 100%; height: 100%">
			<td>
				<table id="leftTable" style="width: 260pt; height: 100%; vertical-align: top;">	
					<tr id="trSelectionBar" style="height: 226px;">
						<td>
							<h2 style="width: 240pt; text-align: center; color: #515151">
								<?php echo $Ue['KARTOGRAPHISCH'];?>
								<?php echo va_get_glossary_help(34, $Ue); ?>
							</h2>
							<br />
							
							<h6 class="VA_Map_Subhead"><?php echo $Ue['SPRACHDATEN']; ?></h6>
							<?php
							
							//Base types
							echo im_table_select('Z_Ling', array('Id_Base_Type'), array('Base_Type'), 'baseTypeSelect', array(
									'list_format_function' => array('va_format_base_type', &$Ue),
									'placeholder' => $Ue['BASISTYP_PLURAL'],
									'width' => '240pt'
							));
							echo va_get_mouseover_help($Ue['HILFE_BASISTYP'], $Ue, IM_Initializer::$instance->database, $lang, 58);
							
							//Morphologic types
							echo im_table_select('Z_Ling', array('Id_Type'), array('Type', 'Type_Lang', 'POS', 'Gender', 'Affix'), 'morphTypeSelect', array(
									'list_format_function' => array('va_format_lex_type', &$Ue),
									'placeholder' => $Ue['MORPH_TYP_PLURAL'],
									'width' => '240pt',
									'filter' => "Type_Kind != 'P' AND Source_Typing = 'VA'"
								));
							echo va_get_mouseover_help($Ue['HILFE_MORPH'], $Ue, IM_Initializer::$instance->database, $lang, 58);
							
							//Phonetic types
							echo im_table_select('Z_Ling', array('Id_Type'), array('Type'), 'phonTypeSelect', array(
									'placeholder' => $Ue['PHON_TYP_PLURAL'],
									'width' => '240pt',
									'filter' => "Type_Kind = 'P' AND Source_Typing = 'VA'"
							));
							echo va_get_mouseover_help($Ue['HILFE_PHON'], $Ue, IM_Initializer::$instance->database, $lang, 58);
							
							?>

							<hr style="height:5pt; visibility:hidden; margin : 0 0" />
							
							<?php
							//Concepts
							$ueKat = function ($word) use (&$Ue){
								if($word == ''){
									return $Ue['KEINE_DOMAENE'];
								}	
								if(isset($Ue[$word]))
									return $Ue['DOMAENE'] . ' ' . $Ue[$word];
								return $word;
							};
							
							$kats = array();
							$params = array();
							
							foreach ($concepts as $index => $concept){
								if($concept['Anzeige']){	
									$kats[$index] = array($concept['Id_Konzept'], $ueKat($concept['Kategorie']));
									$hasName = $concept['Name'] != '' && $concept['Name'] != $concept['Beschreibung'];
									if($hasName){
										$kats[$index][] = $concept['Name'];
									}		
									else {
										$kats[$index][] = $concept['Beschreibung'];
									}
									if($hasName || $concept['Dateiname']){
										$params[$index] = array ('class' => 'conceptTooltip');
									}
								}
							}	
							uasort($kats, function ($a, $b){
								$katComp = strcmp($a[1], $b[1]);
								if($katComp == 0){
									return strcmp($a[2], $b[2]);
								}
								return $katComp;
							});
							
							?>
							<div style="display: table;">
								<div style="display: table-cell; vertical-align: middle;">
								<?php
									echo im_hierarchical_select('conceptSelect', $Ue['KONZEPT_PLURAL'], $kats, $params, array ('style' => 'width : 240pt'));
								?>
								</div>
									<div style="display: table-cell; vertical-align: middle;">
										<div style="margin-left: 3pt;">
										<?php
										echo va_get_mouseover_help($Ue['HILFE_KONZEPT'], $Ue, IM_Initializer::$instance->database, $lang, 37);
										?>
									</div>
								</div>
							</div>
							<hr style="height:5pt; visibility:hidden; margin : 0 0" />
							
							<h6 class="VA_Map_Subhead"><?php echo $Ue['PERIPHERIE']; ?></h6>
							<?php
							
							//Informants
							echo im_table_select('Informanten', 'Erhebung', array('Erhebung'), 'informantSelect', array(
									'placeholder' => $Ue['INFORMANTEN'],
									'width' => '240pt'
								));
							echo va_get_mouseover_help($Ue['HILFE_INFORMANTEN'], $Ue, IM_Initializer::$instance->database, $lang, 30);
							
							//Extra-linguistic
							foreach($extra_cats as &$row){
								for($i = 1; $i < 6; $i++){
									if(isset($Ue[$row[$i]]))
										$row[$i] = $Ue[$row[$i]];
								}
							}
								
							$extra_cats = array_filter($extra_cats, function ($e){
								return $e[8] == '1';
							});
									
									
								?>
							<div style="display: table;">
								<div style="display: table-cell; vertical-align: middle;">
								<?php
									echo im_hierarchical_select('extraLingSelect', $Ue['AUSSERSPR'], $extra_cats, array (), array ('style' => 'width : 240pt'));
								?>
								</div>
									<div style="display: table-cell; vertical-align: middle;">
										<div style="margin-left: 3pt;">
										<?php
										echo va_get_mouseover_help($Ue['HILFE_AUSSERSPR'], $Ue, IM_Initializer::$instance->database, $lang, 3);
										?>
									</div>
								</div>
							</div>
							
							<?php
							
							//Areas
							echo im_table_select('Z_Geo', 'Id_Category', array('Category_Name'), 'polygonSelect', array(
									'placeholder' => $Ue['POLYGONE'],
									'width' => '240pt',
									'list_format_function' => array('va_sub_translate', &$Ue),
									'filter' => "GeometryType(Geo_data) = 'POLYGON' OR GeometryType(Geo_data) = 'MULTIPOLYGON'"
							));
							
							echo va_get_mouseover_help($Ue['HILFE_FLAECHEN'], $Ue, IM_Initializer::$instance->database, $lang, 88);
							
							?>
							
							<hr style="height:12pt; visibility:hidden; margin : 0 0" />
						</td>
					</tr>
					
					<tr>
						<td>
							<?php im_create_legend(); ?>
						</td>
					</tr>
					
					<tr style="height: 85px">
						<td>
							<?php 
							echo im_create_synoptic_map_div('220pt',  $va_current_db_name !== 'va_xxx', va_get_glossary_help(56, $Ue));
							?>
							<br />
							<br />
						</td>
					</tr>
				</table>
			</td>
			<td style="width: 100%; height: 100%">
				<?php im_create_map(0); ?>
			</td>
		</tr>
	</table>
</div>

<?php 

im_create_filter_popup_html();
im_create_comment_popup_html();
im_create_save_map_popup_html();
im_create_ajax_nonce_field ();
im_create_debug_area();

}
?>