<?php 

function create_va_map (){

$t = microtime(true);$times[] = array('Start: ', date("h:i:s") . sprintf(" %06d",($t - floor($t)) * 1000000));

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

$sql_extra = '
		SELECT Id_Category, Category_Level_1, Category_Level_2, Category_Level_3, Category_Level_4, Category_Level_5, Category_Name, GROUP_CONCAT(DISTINCT Tags) 
		FROM Z_Geo
		GROUP BY Id_Category
		ORDER BY Category_Level_1, Category_Level_2, Category_Level_3, Category_Level_4, Category_Level_5';
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

wp_localize_script ('im_map_script', 'Ue', $Ue);
wp_localize_script ('im_map_script', 'Concepts', $concepts_JS);
wp_localize_script ('im_map_script', 'ELing', $eling_JS);

$t = microtime(true);$times[] = array('Concept-Transl: ', date("h:i:s") . sprintf(" %06d",($t - floor($t)) * 1000000));


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
							<?php
							//Phonetic types
							echo im_table_select('Z_Ling', array('Id_Type'), array('Type'), 'phonTypeSelect', array(
									'placeholder' => $Ue['PHON_TYP'],
									'width' => '240pt', 
									'filter' => "Type_Kind = 'P' AND Source_Typing = 'VA'"
								));
							echo va_get_glossary_help(58, $Ue);
							
							$t = microtime(true);$times[] = array('Phon: ', date("h:i:s") . sprintf(" %06d",($t - floor($t)) * 1000000));
							
							//Morphologic types
							echo im_table_select('Z_Ling', array('Id_Type'), array('Type', 'Type_Lang', 'POS', 'Gender', 'Affix'), 'morphTypeSelect', array(
									'list_format_function' => array('va_format_lex_type', &$Ue),
									'placeholder' => $Ue['MORPH_TYP'],
									'width' => '240pt',
									'filter' => "Type_Kind != 'P' AND Source_Typing = 'VA'"
								));
							echo va_get_glossary_help(58, $Ue);
							
							$t = microtime(true);$times[] = array('Morph: ', date("h:i:s") . sprintf(" %06d",($t - floor($t)) * 1000000));
							
							//Base types
							echo im_table_select('Z_Ling', array('Id_Base_Type'), array('Base_Type'), 'baseTypeSelect', array(
									'list_format_function' => 'va_format_base_type',
									'placeholder' => $Ue['BASISTYP'],
									'width' => '240pt'
								));
							echo va_get_glossary_help(58, $Ue);
							
							$t = microtime(true);$times[] = array('Base: ', date("h:i:s") . sprintf(" %06d",($t - floor($t)) * 1000000));
							
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
									echo im_hierarchical_select('conceptSelect', $Ue['KONZEPT'], $kats, $params, array ('style' => 'width : 240pt'));
								?>
								</div>
									<div style="display: table-cell; vertical-align: middle;">
										<div style="margin-left: 3pt;">
										<?php
											echo va_get_glossary_help(37, $Ue);
										?>
									</div>
								</div>
							</div>
							<hr style="height:5pt; visibility:hidden; margin : 0 0" />
							
							<?php
							
							$t = microtime(true);$times[] = array('Concepts: ', date("h:i:s") . sprintf(" %06d",($t - floor($t)) * 1000000));
							
							//Extra-linguistic
							foreach($extra_cats as &$row){
								for($i = 1; $i < 6; $i++){
									if(isset($Ue[$row[$i]]))
										$row[$i] = $Ue[$row[$i]];
								}
							}
							
							
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
											echo va_get_glossary_help(3, $Ue);
										?>
									</div>
								</div>
							</div>
							
							<hr style="height:5pt; visibility:hidden; margin : 0 0" />
							
							<?php
							$t = microtime(true);$times[] = array('Extra_Ling: ', date("h:i:s") . sprintf(" %06d",($t - floor($t)) * 1000000));
							
							//Informants
							echo im_table_select('Informanten', 'Erhebung', array('Erhebung'), 'informantSelect', array(
									'placeholder' => $Ue['INFORMANTEN'],
									'width' => '240pt'
								));
							echo va_get_glossary_help(30, $Ue);
							
							$t = microtime(true);$times[] = array('Informants: ', date("h:i:s") . sprintf(" %06d",($t - floor($t)) * 1000000));
							
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
							echo im_create_synoptic_map_div('220pt',  $va_current_db_name !== 'va_xxx');
							echo va_get_glossary_help(56, $Ue); ?>
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
$t = microtime(true);$times[] = array('Rest: ', date("h:i:s") . sprintf(" %06d",($t - floor($t)) * 1000000));

im_create_filter_popup_html();
im_create_comment_popup_html();
im_create_save_map_popup_html();
im_create_ajax_nonce_field ();
im_create_debug_area();

$t = microtime(true);$times[] = array('End: ', date("h:i:s") . sprintf(" %06d",($t - floor($t)) * 1000000));

//TODO comment time measurements
/*echo '<table>';
foreach ($times as $time){
	echo '<tr><td>' . $time[0] . '</td><td>' . $time[1] . '</td></tr>';
}
echo '</table>';*/
}
?>