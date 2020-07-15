<?php
function va_lex_scripts (){
    IM_Initializer::$instance->enqueue_chosen_library();
    IM_Initializer::$instance->enqueue_select2_library();
    IM_Initializer::$instance->enqueue_gui_elements();
    wp_enqueue_script('lex_typifiy_script', plugins_url('/lex.js?v=1', __FILE__));
    wp_enqueue_script('typifiy_script', plugins_url('/util.js', __FILE__));
    
    wp_localize_script('lex_typifiy_script', 'DATA', [
        'scanUrl' => home_url('/dokumente/scans/', 'https'),
        'loadingUrl' => VA_PLUGIN_URL . '/images/Loading.gif',
        'writeMode' => current_user_can('va_typification_tool_write')? 'true' : 'false'
    ]);
    
    wp_localize_script('lex_typifiy_script', 'TRANSLATIONS', [
        'CORRECT' => __('Correct Attestation','verba-alpina'),
        'ATTESTATION' => __('Attestation','verba-alpina'),
        'CHOOSE_TYPE' => __('Choose a type!','verba-alpina'),
        'CHOOSE_CONCEPT' => __('Choose a concept!','verba-alpina'),
        'NO_RECORDS' => __('No attestations selected!','verba-alpina'),
        'INVALID_SELECTION' => __('Invalid selection!','verba-alpina'),
        'CONCEPT' => __('Concept','verba-alpina'),
        'NO_CONCEPT_ASSIGNED' => __('The following attestations could not be processed, since they are already connected to a different concept', 'verba-alpina'),
        'ERROR_ORTH' => __('The field "Orth" must not be empty!','verba-alpina'),
    ]);
}

function va_typif_get_stimulus_list (&$db, $atlas, $morph_flag, $concept_flag, $alpine_flag){
   
	$app = '';
	$join = '';
	$join_groups = '';
	if ($morph_flag){
		$app .= ' AND m.Id_morph_Typ IS NULL';
		$join .= ' LEFT JOIN VTBL_Token_morph_typ v USING (Id_Token) LEFT JOIN morph_Typen m ON v.Id_morph_Typ = m.Id_morph_Typ AND Quelle = "VA"';
		$join_groups .= ' LEFT JOIN VTBL_Tokengruppe_morph_typ v USING (Id_Tokengruppe) LEFT JOIN morph_Typen m ON v.Id_morph_Typ = m.Id_morph_Typ AND Quelle = "VA"';
	}
	if ($concept_flag){
		$app .= ' AND Id_Konzept IS NULL';
	}
	if ($alpine_flag){
		$app .= ' AND Alpenkonvention';
		$join .= ' JOIN Informanten i USING (Id_Informant)';
		$join_groups .= ' JOIN Informanten i USING (Id_Informant)';
	}

	if ($atlas == 'CROWD'){
		
		if (!$alpine_flag){
			$join .= ' JOIN Informanten i USING (Id_Informant)';
			$join_groups .= ' JOIN Informanten i USING (Id_Informant)';
		}
		
        $stimuli_pre = $db->get_results("
			SELECT DISTINCT 
				Sprache AS Id_Stimulus, 
				Sprache AS TStimulus,
				'' AS Karte 
			FROM Informanten 
			ORDER BY Sprache ASC", ARRAY_A);
			
		$stimuli = [];
		foreach ($stimuli_pre as $stimulus){
			$sql = '
				SELECT (SELECT count(*) 
				FROM Tokens LEFT JOIN VTBL_Token_Konzept USING (Id_Token) LEFT JOIN Konzepte USING (Id_Konzept)' . $join . '
				WHERE i.Sprache = "' . $stimulus['Id_Stimulus'] . '" AND Id_Stimulus IN (SELECT ID_Stimulus FROM Stimuli WHERE Erhebung = "CROWD") AND (Grammatikalisch IS NULL OR NOT Grammatikalisch)' . $app .')
				+
				(SELECT count(*) 
				FROM Tokengruppen LEFT JOIN VTBL_Tokengruppe_Konzept USING (Id_Tokengruppe) LEFT JOIN Konzepte USING (Id_Konzept) JOIN Tokens USING(Id_Tokengruppe)' . $join_groups . '
				WHERE i.Sprache = "' . $stimulus['Id_Stimulus'] . '" AND Id_Stimulus IN (SELECT ID_Stimulus FROM Stimuli WHERE Erhebung = "CROWD") AND (Grammatikalisch IS NULL OR NOT Grammatikalisch)' . $app .')';
				
			$count = $db->get_var($sql);
			
			if ($count > 0){
				$stimuli[] = $stimulus;
			}
		}
    } 
    else {
       $query =$db->prepare("
        SELECT
            Id_Stimulus,
            CONCAT(Karte, '_', Nummer, ': ', REPLACE(Stimulus, '\"', '')) as TStimulus,
            Karte
        FROM Stimuli s
        WHERE Erhebung = %s
        AND ((
                SELECT count(*) 
                FROM Tokens t LEFT JOIN VTBL_Token_Konzept USING (Id_Token) LEFT JOIN Konzepte USING (Id_Konzept)" . $join . " 
                WHERE t.Id_Stimulus = s.Id_Stimulus AND (Grammatikalisch IS NULL OR NOT Grammatikalisch)" . $app . ")
            + (
                SELECT count(DISTINCT Id_Tokengruppe) 
                FROM Tokengruppen LEFT JOIN VTBL_Tokengruppe_Konzept USING (Id_Tokengruppe) LEFT JOIN Konzepte USING (Id_Konzept) JOIN Tokens t USING (Id_Tokengruppe)" . $join_groups . "
                WHERE t.Id_Stimulus = s.Id_Stimulus AND (Grammatikalisch IS NULL OR NOT Grammatikalisch)" . $app . ")) > 0"
           , $atlas);
        
        $stimuli = $db->get_results($query, ARRAY_A);
    }
    
    $res = '';
    
    $res .= '<select class="stimulusList">';
    $res .= '<option value="">' . __('Choose Stimulus', 'verba-alpina') . '</option>';
    foreach ($stimuli as $stimulus){
        $res .= '<option value="' . $stimulus['Id_Stimulus'] . '" data-file="' . $atlas . '#' . $stimulus['Karte'] . '.pdf">' . $stimulus['TStimulus'] . '</option>';
    }
    $res .= '</select>';
    
    return $res;
}

function lex_typification (){

global $va_xxx;
$db = $va_xxx;

$dbname = NULL;

if($dbname != NULL){
    $va_xxx->select($dbname);
}

$can_write = current_user_can('va_typification_tool_write');

$att_info = __('Attestation without typification are formatted bold. Attestations without concept are in italics. Attestations linked with irrelevant concepts are shown with a grey background.', 'verba-alpina');
$select_info = __('If "Ctrl" is pressed multiple attestations can be selected, if "Shift" is pressed other attestations that are orthographically identical get selected, too.', 'verba-alpina');

?>

<script type="text/javascript">
DATA.dbname = <?php echo $dbname? '"' . $dbname . '"': 'undefined'; ?>;
</script>

<style>
	.chosen-container {
		max-width : 300pt;
	}
</style>

<table style="width : 100%; height: 100%">
	<tr>
		<td style="width: 50%">
			<h1><?php _e('Typification', 'verba-alpina');?></h1>
			
			<br />
			
			<h3><?php _e('Attestations', 'verba-alpina');?></h3>
				
			<select id="filterAtlas" class="chosenSelect">
				<option value=""><?php _e('Choose Atlas', 'verba-alpina');?></option>
				<?php
				$atlanten = $db -> get_col('SELECT DISTINCT Erhebung FROM Stimuli JOIN Tokens USING(Id_Stimulus) ORDER BY Erhebung ASC', 0);
				foreach ($atlanten as $atlas) {
					echo '<option value="' . str_replace(' ', '_', $atlas) . '">' . $atlas . '</option>';
				}
				?>
			</select>
			
			<span id="stimulusListDiv"></span>
			<br />
			<br />
			
			<input type="checkbox" id="AllorNot" checked="checked" /> <?php _e('Show only attestations without morph.-lex. typification', 'verba-alpina');?>
			<br />
			<input type="checkbox" id="AllorNotConcept" /> <?php _e('Show only attestations without concept', 'verba-alpina');?>
			<br />
			<input type="checkbox" id="AllorNotAlpes" checked="checked" /> <?php _e('Show only attestations within the Alpine convention', 'verba-alpina');?>
			
			<br />
			<br />
			
			<div style="min-height: 20px" class="tokenInfo">
				<span style="color: red; font-size: 90%;">(<?php echo $select_info; ?>)</span>
				<br />
				<br />
				<?php _e('Attestations', 'verba-alpina');?>
					<select id="tokenAuswahlLex" multiple="multiple" style="width: 400pt">
					</select>
					<?php echo va_get_info_symbol($att_info);?>
					<input type="button" id="emptySelection" value="<?php _e('Clear selection', 'verba-alpina');?>" class="button button-primary" style="margin-left: 50px;" />
			</div>
			<img src="<?php echo VA_PLUGIN_URL . '/images/Loading.gif' ?>" style="display: none" id="tokensLoading" />
			
			<br />
			<br />
			
			<table id="recordSummary" class="widefat fixed striped tokenInfo">
				<tr>
					<th><?php _e('Attestation', 'verba-alpina');?></th>
					<th><?php _e('Informants', 'verba-alpina');?></th>
					<th><?php _e('Remarks', 'verba-alpina');?></th>
					<th><?php _e('Concept(s)', 'verba-alpina');?></th>
					<th><?php _e('Morph.-lex type', 'verba-alpina');?></th>
					<th></th>
				</tr>
			</table>
			
			<br />
			<br />
			
			<h3><?php _e('Assign morph.-lex. type', 'verba-alpina');?></h3>
			
			<select id="morphTypenAuswahl" class="chosenSelect" data-placeholder="<?php _e('Choose type', 'verba-alpina');?>">
				<?php
				$typenVA = $db->get_results("SELECT Id_morph_Typ, lex_unique(Orth, Sprache, Genus) as Orth FROM morph_Typen WHERE Quelle = 'VA' ORDER BY Orth ASC", ARRAY_A);
		
				foreach ($typenVA as $vat) {
					echo '<option value="' . $vat['Id_morph_Typ'] . '">' . $vat['Orth'] . '</option>';
				}
				?>
			</select>
			<input id="assignVA" type="button" class="button button-primary assignButton" value="<?php _e('Assign type', 'verba-alpina');?>" <?php if(!$can_write) echo ' disabled';?> />
			<input id="newVAType" type="button" class="button button-primary" value="<?php _e('Create new type', 'verba-alpina');?>" <?php if(!$can_write) echo ' disabled';?> />
			<input id="editVAType" type="button" class="button button-primary" value="<?php _e('Edit type', 'verba-alpina');?>" <?php if(!$can_write) echo ' disabled';?> />
			
			<br />
			<br />
			
			<h3><?php _e('Assign concept', 'verba-alpina');?></h3>
			
			<select id="konzeptAuswahl" class="chosenSelect" data-placeholder="<?php _e('Choose concept', 'verba-alpina');?>">
				<?php
				$conceptsVA = $db->get_results("SELECT Id_Konzept, IF(Name_D != '', Name_D, Beschreibung_D) as Name FROM Konzepte WHERE NOT Grammatikalisch ORDER BY Name ASC", ARRAY_A);
		
				foreach ($conceptsVA as $vac) {
					echo '<option value="' . $vac['Id_Konzept'] . '">' . $vac['Name'] . '</option>';
				}
				?>
			</select>
			<input id="assignConcept" type="button" class="button button-primary conceptButton" value="<?php _e('Assign concept', 'verba-alpina');?>" <?php if(!$can_write) echo ' disabled';?> />
			<input id="newConcept" type="button" class="button button-primary" value="<?php _e('Create new concept', 'verba-alpina');?>" <?php if(!$can_write) echo ' disabled';?> />
			
			<br />
			<br />
			
			<div>
				<h3><?php _e('Typification not necessary', 'verba-alpina');?></h3>
			
				<?php _e('Tokens are', 'verba-alpina');?>
				<select id="keinTypAuswahl" class="chosenSelect">
					<?php
					$konzeptNamen = $db -> get_results('SELECT Id_Konzept, Beschreibung_D FROM Konzepte WHERE Grammatikalisch', ARRAY_A);
			
					foreach ($konzeptNamen as $name) {
						echo '<option value="' . $name['Id_Konzept'] . '">' . $name['Beschreibung_D'] . '</option>';
					}
					?>
				</select>
				<input type="button" class="button button-primary conceptButton" id="noTypeButton" value="<?php _e('Confirm', 'verba-alpina');?>" <?php if(!$can_write) echo ' disabled';?>>
			</div>
			
		</td>
		
		<td style="width: 50%;">
			<iframe src="about:blank" style="width : 100%; height: 600pt;" id="pdfFrame">
				
			</iframe>
		</td>
	</tr>
</table>

<?php
createTypeOverlay($db, $dbname);

}
?>