<?php

add_filter('attachment_fields_to_edit', 'add_more_fields', 10, 2); //Felder für Mediathek
add_action( 'edit_attachment', 'save_media_fields'); //Speichern der Mediathek-Felder
add_action( 'print_media_templates', 'css_adapt' );
add_action( 'admin_footer', 'css_adapt2' );
add_action('wp_ajax_createCostHtml', 'createCostHtml');
add_action('wp_ajax_saveTax', 'saveTaxonomiesManually');


function createCostHtml (){
	$ret = array();
	$ret[] = im_table_select('Konzepte', 'Id_Konzept', array('Name_D', 'Beschreibung_D'), 'id_konzepte', array (
			'placeholder' => 'Konzept(e) auswählen',
			'multiple_values' => true,
			'width' => '190pt',
			'new_values_info' => new IM_Row_Information('Konzepte', array (
				new IM_Field_Information('Name_D', 'V', false),
	 			new IM_Field_Information('Beschreibung_D', 'V', true),
	 			new IM_Field_Information('Kategorie', 'E', true),
	 			new IM_Field_Information('Hauptkategorie', 'E', true)
	 		), 'Angelegt_Von')
		)
	);
	$ret[] = im_table_select('morph_Typen', 'Id_morph_Typ', array('Orth'), 'id_typen', array (
			'placeholder' => 'Typ(en) auswählen',
			'multiple_values' => true,
			'width' => '190pt',
			'new_values_info' => new IM_Row_Information('morph_Typen', array (
					new IM_Field_Information('Orth', 'V', true),
					new IM_Field_Information('Wortart', 'E', false),
					new IM_Field_Information('Affix', 'V', false),
					new IM_Field_Information('Genus', 'E', false),
					new IM_Field_Information('Numerus', 'E', false)
			), 'Angelegt_Von')
		)
	);
	$ret[] = im_table_select('Bibliographie', 'Abkuerzung', array('Abkuerzung'), 'id_biblio', array(
		'placeholder' => 'Abkürzung auswählen',
		'new_values_info' => new IM_Row_Information('Bibliographie', array(
				new IM_Field_Information('Abkuerzung', 'V', true),
				new IM_Field_Information('Autor', 'V', false),
				new IM_Field_Information('Titel', 'V', false),
				new IM_Field_Information('Ort', 'V', false),
				new IM_Field_Information('Jahr', 'V', false),
				new IM_Field_Information('Band', 'V', false),
				new IM_Field_Information('Enthalten_In', 'V', false),
				new IM_Field_Information('Seiten', 'V', false),
				new IM_Field_Information('Verlag', 'V', false),
				new IM_Field_Information('Download_Url', 'V', false),
				new IM_Field_Information('Download_Datum', 'V', false),
				new IM_Field_Information('Kontaktadresse', 'V', false),
				new IM_Field_Information('VA_Erfassung', 'B', false),
				new IM_Field_Information('VA_Publikation', 'B', false),
			)
		),
		'width' => '190pt'
		)	
	);
	echo json_encode($ret);
	die;
}

function saveTaxonomiesManually (){
	if($_REQUEST['selected'] == "true"){
		echo json_encode(wp_set_object_terms( $_REQUEST['id'], array(intval($_REQUEST['num'])), 'media_category', true ));
	}
	else {
		$old_terms = wp_get_object_terms($_REQUEST['id'], 'media_category', array('fields' => 'ids'));
		$key = array_search(intval($_REQUEST['num']), $old_terms);
		unset($old_terms[$key]);
		echo json_encode(wp_set_object_terms( $_REQUEST['id'], $old_terms, 'media_category', false ));
	}
	die;
}

function css_adapt (){
	?>
	<style>
		media-sidebar .setting, .attachment-details .setting { display : none} /*Hide wordpress default fields*/
    </style>
	<?php
}

function css_adapt2 (){
	if(get_current_screen()->id == 'attachment'){
	
		IM_Initializer::$instance->enqueue_gui_elements();
	?>
	<script type="text/javascript">
		jQuery(document).ready(function ($) {
			$('select').not(".noChosen").chosen({allow_single_deselect: true});
			
			$(".wp_attachment_details").css("display","none");
			$('#attachment-nav').appendTo($('h2:first'));
			
			setStartValues();
		});
				
		<?php
			global $post;
			$id = getVA_ID($post->ID);
		?>

		var id = <?php echo $id;?>;
		function setStartValues (){
			//Create html code
			
			data = {
				'action' : 'createCostHtml',
			};
			
			jQuery.post(ajaxurl, data, function (response){
				var arr = JSON.parse(response);


				jQuery("input:not(:checkbox)").not("#title").on("change", function (){changed(this)});
				jQuery("select").on("change", function (){changed(this)});
				jQuery("textarea").on("change", function (){changed(this)});
				
				appendInputElement(arr[1], '#morphDiv');
				jQuery('#id_typen').chosen({allow_single_deselect: true});
				
				//Set start values and onChange functions
				var b = jQuery("#id_typen");
				b.val(JSON.parse(jQuery('#morphDiv').attr('data-str')));
				b.trigger("chosen:updated");
				lastSelVal = b.val();
				
				b.on("change", function (){
					var v = jQuery(this).val();
					if(v != null && v[0] == 0)
						return;

					if(lengthNull(v) > lengthNull(lastSelVal)){
						var diff = jQuery(v).not(lastSelVal).get();
						if(!isNaN(diff[0]))
							updateValue(id, "ADD_TYPE", diff[0]);
					}
					else {
						var diff = jQuery(lastSelVal).not(v).get();
						if(!isNaN(diff[0]))
							updateValue(id, "DELETE_TYPE", diff[0]);
					}
					lastSelVal = v;
				});
				
				//Speichere Kategorien manuell (TODO bessere Lösung)
				jQuery("input[type=checkbox]").on("change", function (){
					data = {
						'action' : 'saveTax',
						'num' : this.value,
						'id' : '<?php echo $post->ID; ?>',
						'selected' : this.checked,
					};
			
				jQuery.post(ajaxurl, data, function (response){});
				});

				appendInputElement(arr[0], '#konzeptDiv');
				jQuery('#id_konzepte').chosen({allow_single_deselect: true});
				var b = jQuery("#id_konzepte");
				b.val(JSON.parse(jQuery('#konzeptDiv').attr('data-str')));
				b.trigger("chosen:updated");
				lastSelValKonz = b.val();
				
				b.on("change", function (){
					var v = jQuery(this).val();
					if(v != null && v[0] == 0)
						return;
					
					if(lengthNull(v) > lengthNull(lastSelValKonz)){
						var diff = jQuery(v).not(lastSelValKonz).get();
						if(!isNaN(diff[0]))
							updateValue(id, "ADD_CONCEPT", diff[0]);
					}
					else {
						var diff = jQuery(lastSelValKonz).not(v).get();
						if(!isNaN(diff[0]))
							updateValue(id, "DELETE_CONCEPT", diff[0]);
					}
					lastSelValKonz = v;
				});


				appendInputElement(arr[2], '#biblioDiv');
				jQuery('#id_biblio').chosen({allow_single_deselect: true});
				
				var b = jQuery("#id_biblio");
				b.val(jQuery('#biblioDiv').attr('data-str'));
				b.trigger("chosen:updated");
				
				b.on("change", function (){
					var v = jQuery(this).val();
					if(v != "###NEW###"){
						if(v == "-1"){
							updateValue(id, "Abkuerzung_Bibliographie", null);
						}
						else {
							updateValue(id, "Abkuerzung_Bibliographie", v);
						}
					}
				});
				
				
				
				function changed (element){
					var start = element.name.lastIndexOf("[") + 1;
					var name = element.name.substr(start, element.name.length-start-1);
					if(name == "konzepte" || name == "morph_Typen")
						return;
					if(name == "Breitengrad" || name == "Laengengrad"){
						br = jQuery("#attachments-<?php echo get_the_ID();?>-Breitengrad").val();
						la = jQuery("#attachments-<?php echo get_the_ID();?>-Laengengrad").val();
						
						if(br.match(/[0-9]*,[0-9]*/)){
							br = br.replace(",",".");
						}
						if(la.match(/[0-9]*,[0-9]*/)){
							la = la.replace(",",".");
						}
						if(la == '')
							la = '0';
						else if (br == '')
							br = '0';
						
						if(br.match(/[0-9]+(\.[0-9]*)?/) && la.match(/[0-9]+(\.[0-9]*)?/)){
							updateValue(<?php echo getVA_ID(get_the_ID()); ?>, "Georeferenzierung", [br,la]);
						}
						else {
							alert("Georeferenzierung konnte nicht gespeichert werden!");
							updateValue(<?php echo getVA_ID(get_the_ID()); ?>, "Georeferenzierung", null);
						}
					}
					else if (name == "Datum_Aufnahme" || name == "Datum_Download"){
						var datum = element.value;
						if(datum.match(/^[0-9][0-9][0-9][0-9]$/)){
							datum = datum + "-00-00";
							element.value = datum;
						}
						else if (datum.match(/^[0-9][0-9][0-9][0-9]-[0-9][0-9]$/)){
							datum = datum + "-00";
							element.value = datum;
						}
						if(datum.match(/^[0-9][0-9][0-9][0-9]-[0-9][0-9]-[0-9][0-9]$/)){
							updateValue(<?php echo getVA_ID(get_the_ID()); ?>, name, datum);
						}
						else {
							alert("Ungültiges Datum in Feld " + name);
						}
					}
					else {
						updateValue(<?php echo getVA_ID(get_the_ID()); ?>, name, element.value);
					}
				}

				addNewEnumValueScript();
				addNewValueScript ('#id_typen', "reload", selectModes.Chosen);
				addNewValueScript ('#id_biblio', "reload", selectModes.Chosen);
				addNewValueScript ('#id_konzepte', "reload", selectModes.Chosen);
			});
		}

		function updateValue(id, field, value) {
			ajaxData = {
				"action" : "va",
				"namespace" : "media",
				"query" : "update",
				"id" : id,
				"field" : field,
				"value" : value
			};
			jQuery.post(ajaxurl, ajaxData, null);
		}

		/*
		* This functions removes the html code for the popup window and appends it to the wpcontent div
		* The rest is append to the elment with th given selector.
		* This is need since html does not allow nested forms and the media fields are already contained in a form.
		*/
		function appendInputElement(htmlString, elementSelector){
			var newElement = jQuery.parseHTML(htmlString);
			var inputWindow = newElement[0];
			jQuery("#wpcontent").append(inputWindow);
			newElement.splice(0,1);
			jQuery(elementSelector).html(newElement);
		}
	
	</script>
	
	<?php
	
	global $wpdb;
	$id_prev = $wpdb->get_var("SELECT ID FROM wp_posts WHERE post_type='attachment' AND ID > $post->ID ORDER BY ID ASC LIMIT 1", 0, 0);
	$id_next = $wpdb->get_var("SELECT ID FROM wp_posts WHERE post_type='attachment' AND ID < $post->ID ORDER BY ID DESC LIMIT 1", 0, 0);
	?>
	
	<div id="attachment-nav" style="margin-left: 20pt; display:inline-block;">
		<?php if ($id_prev != null) { ?>
		<a href="<?php echo get_edit_post_link($id_prev);?>" class="add-new-h2">Vorheriges Medium</a>
		<?php } if ($id_next != null) { ?>
		<a href="<?php echo get_edit_post_link($id_next);?>" class="add-new-h2">Nächstes Medium</a>
		<?php } ?>
	</div>
	
	<?php
	}
}

//TODO Einträge löschen, wenn die Bilder gelöscht werden

function add_more_fields ($form_fields, $post){
	global $va_xxx;
	
	//TODO abgleichen, ob eigenes oder fremdes Bild
	if(current_user_can('edit_others_posts')){
	
		$id = getVA_ID($post->ID);
		
		$typen = $va_xxx->get_results("SELECT Id_morph_Typ FROM VTBL_Medium_Typ WHERE Id_Medium = $id", ARRAY_N);
		if(sizeof($typen) == 0){
			$typ_str = '[]';
		}
		else {
			$typ_str = '[';
			foreach ($typen as $typ){
				$typ_str .= '"' . $typ[0] . '", '; 
			}
			$typ_str = substr($typ_str, 0, -2).']';
		}
		
		$konzepte = $va_xxx->get_results("SELECT Id_Konzept FROM VTBL_Medium_Konzept WHERE Id_Medium = $id", ARRAY_N);
		if(sizeof($konzepte) == 0){
			$konz_str = '[]';
		}
		else {
			$konz_str = '[';
			foreach ($konzepte as $konz){
				$konz_str .= '"' . $konz[0] . '", '; 
			}
			$konz_str = substr($konz_str, 0, -2).']';
		}
		
		$values = $va_xxx->get_row("SELECT Gegenstand, Ursprung, Fotograf, Datum_Aufnahme, Datum_Download, Copyright, Lizenz, Rechteinhaber, Kontaktadresse, Bemerkungen, Genauigkeit_Geo, X(Georeferenzierung) as XG, Y(Georeferenzierung) as YG, Abkuerzung_Bibliographie FROM Medien WHERE Id_Medium = $id", ARRAY_A);
		
		$form_fields['Gegenstand'] = array(
			'label' => 'Gegenstand',
			'input' => 'text',
			'value' => $values['Gegenstand'],
		);
		
		$form_fields['Ursprung'] = array(
			'label' => 'Ursprung',
			'input' => 'text',
			'value' => $values['Ursprung'],
		);
		
		$form_fields['Fotograf'] = array(
			'label' => 'Fotograf',
			'input' => 'text',
			'value' => $values['Fotograf'],
		);
		
		$form_fields['Datum_Aufnahme'] = array(
			'label' => 'Datum der Aufnahme',
			'input' => 'text',
			'value' => $values['Datum_Aufnahme'],
		);
		
		$form_fields['Datum_Download'] = array(
			'label' => 'Datum des Downloads',
			'input' => 'text',
			'value' => $values['Datum_Download'],
		);

		
		$form_fields['Copyright'] = array(
			'label' => 'Copyright',
			'input' => 'html',
			'html' => im_enum_select('Medien', 'Copyright', 'attachments[' . $post->ID . '][Copyright]', $values['Copyright'])
		);
		
		$form_fields['Lizenz'] = array(
			'label' => 'Lizenz',
			'input' => 'html',
			'html' => im_enum_select('Medien','Lizenz','attachments[' . $post->ID . '][Lizenz]', $values['Lizenz'], true),
		);
		
		$form_fields['Rechteinhaber'] = array(
			'label' => 'Rechteinhaber',
			'input' => 'text',
			'value' => $values['Rechteinhaber'],
		);
		
		$form_fields['Kontaktadresse'] = array(
			'label' => 'Kontaktadresse',
			'input' => 'text',
			'value' => $values['Kontaktadresse'],
		);
		
		$form_fields['Bemerkungen'] = array(
			'label' => 'Bemerkungen',
			'input' => 'textarea',
			'value' => $values['Bemerkungen'],
		);
		
		$form_fields['Breitengrad'] = array(
			'label' => 'Breitengrad',
			'input' => 'text',
			'value' => $values['XG'],
		);
		
		$form_fields['Laengengrad'] = array(
			'label' => 'Längengrad',
			'input' => 'text',
			'value' => $values['YG'],
		);
		
		$form_fields['Genauigkeit_Geo'] = array(
			'label' => 'Genauigkeit Georeferenzierung',
			'input' => 'html',
			'html' => im_enum_select('Medien','Genauigkeit_Geo', 'attachments[' . $post->ID . '][Genauigkeit_Geo]', $values['Genauigkeit_Geo']),
		);
		
		IM_Initializer::$instance->enqueue_chosen_library();
		IM_Initializer::$instance->enqueue_gui_elements();
		
		$form_fields['morph_Typen'] = array(
			'label' => 'morph. Typen',
			'input' => 'html',
			'html' => '<div id="morphDiv" data-str="' . str_replace('"', '&quot;', $typ_str) . '">(Bitte über weitere Details bearbeiten)</div>',
		);
		
		$form_fields['Konzepte'] = array(
			'label' => 'Konzepte',
			'input' => 'html',
			'html' => '<div id="konzeptDiv" data-str="' . str_replace('"', '&quot;', $konz_str) . '">(Bitte über weitere Details bearbeiten)</div>',
		);
		
		$name_bibl = 'attachments[' . $post->ID . '][Abkuerzung_Bibliographie]';
		
		
		$form_fields['Abkuerzung_Bibliographie'] = array(
			'label' => 'Abkürzung Bibliographie',
			'input' => 'html',
			'html' => '<div id="biblioDiv" data-str="' . $values['Abkuerzung_Bibliographie'] . '">(Bitte über weitere Details bearbeiten)</div>',
		);
	}
			
	return $form_fields;
}



function save_media_fields ($attachment_id){
	global $va_xxx;
	$id = getVA_ID($attachment_id);
	
	saveTo($id, $attachment_id, 'Gegenstand');
	saveTo($id, $attachment_id, 'Ursprung');
	saveTo($id, $attachment_id, 'Fotograf');
	saveTo($id, $attachment_id, 'Datum_Aufnahme');
	saveTo($id, $attachment_id, 'Datum_Download');
	saveTo($id, $attachment_id, 'Copyright');
	saveTo($id, $attachment_id, 'Lizenz');
	saveTo($id, $attachment_id, 'Rechteinhaber');
	saveTo($id, $attachment_id, 'Kontaktadresse');
	saveTo($id, $attachment_id, 'Bemerkungen');
	saveTo($id, $attachment_id, 'Genauigkeit_Geo');
	
	
	if ( isset( $_REQUEST['attachments'][$attachment_id]['Abkuerzung_Bibliographie'] ) ) {
		$val = $_REQUEST['attachments'][$attachment_id]['Abkuerzung_Bibliographie'];
		if($val == '-1')
			$va_xxx->query("UPDATE Medien SET Abkuerzung_Bibliographie = NULL WHERE Id_Medium = $id");
		else
			$va_xxx->query("UPDATE Medien SET Abkuerzung_Bibliographie = '$val' WHERE Id_Medium = $id");
    }
	
	if ( isset( $_REQUEST['attachments'][$attachment_id]['Breitengrad'] ) && isset( $_REQUEST['attachments'][$attachment_id]['Laengengrad'] ) ) {
		saveGeo($_REQUEST['attachments'][$attachment_id]['Breitengrad'], $_REQUEST['attachments'][$attachment_id]['Laengengrad'], $id);
    }
}

function saveTo($id, $attachment_id, $col){
	global $va_xxx;
	if ( isset( $_REQUEST['attachments'][$attachment_id][$col] ) ) {
		$val = $_REQUEST['attachments'][$attachment_id][$col];
		$va_xxx->query("UPDATE Medien SET $col = '$val' WHERE Id_Medium = $id");
    }
}

function saveGeo ($br, $la, $id){
	global $va_xxx;
	
	if(preg_match('/[0-9]*,[0-9]*/', $br)){
		$br = str_replace(',','.',$br);
	}
		
	if(preg_match('/[0-9]*,[0-9]*/', $la)){
		$la = str_replace(',','.',$la);
	}
		
	if($la == '')
		$la = '0';
	else if ($br == '')
		$br = '0';
		
	if(preg_match('/[0-9]+(\.[0-9]*)?/', $br) && preg_match('/[0-9]+(\.[0-9]*)?/', $la)){
		$va_xxx->query("UPDATE Medien SET Georeferenzierung = POINT($br, $la) WHERE Id_Medium = $id");
	}
	else {
		//TODO eventl. Fehlermeldung
		$va_xxx->query("UPDATE Medien SET Georeferenzierung = NULL WHERE Id_Medium = $id");
	}
}

function getVA_ID ($wp_id){
	global $va_xxx;
	$url = set_url_scheme(wp_get_attachment_url($wp_id), 'https');
	select: 
	$va_id = $va_xxx->get_results("SELECT Id_Medium FROM Medien WHERE Dateiname = '$url'", ARRAY_N);
	//TODO evtl. für lokale Versionen verhindern
	//Neuen Eintrag anlegen:
	if(count($va_id) == 0){
		$va_xxx->query("INSERT INTO `va_xxx`.`medien` (Dateiname, Genauigkeit_Geo) VALUES ('$url', 'nicht spezifiziert')");
		goto select;
	}
	return $va_id[0][0];
}
?>