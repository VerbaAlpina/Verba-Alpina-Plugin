<?php

function va_translation_page (){
	?>
	
	<script type="text/javascript">
		var all = false;
		var lang = '';
		
		jQuery(document).ready(function () {
			jQuery("#sprachauswahl").val("");
			jQuery("input[name='alle'][value='nein']").attr("checked","checked");
		
			jQuery("#sprachauswahl").change(function (){
				lang = this.value;
				changed();
			});
			
			jQuery("input[name='alle']").change(function (){
				all = (this.value == 'ja');
				changed();
			});
		});
		
		function changed (){
			if(lang == ''){
				jQuery("#inhalt").html("");
			}
			else {
				data = {
					"action" : "va",
					"namespace" : "translation",
					"query" : "get_list",
					"lang" : lang,
					"all" : all,
				}
				jQuery.post(ajaxurl, data, function (response){
					jQuery("#inhalt").html(response);
				});
			}
		}
		
		function saveValue (field, id){
			var data = {
				"action" : "va",
				"namespace" : "translation",
				"query" : "update",
				"lang" : lang,
				"key" : field,
				"value" : jQuery("[id='" + id + "']").val()
			};

			jQuery.post(ajaxurl, data, function (response){
				if(response == "success")
					jQuery("[id='" + id + "']").css('background-color', '#CCFFFF');
				else
					alert(response);
			});
		}
	</script>
	
	
	<h1>Übersetzung Oberfläche</h1>
	
	<br />
	<br />
	
	<select id="sprachauswahl">
		<option value="" selected>---Sprache wählen---</option>
		<option value="I">Italienisch</option>
		<option value="F">Französisch</option>
		<option value="R">Rätoromanisch</option>
		<option value="S">Slowenisch</option>
		<option value="E">Englisch</option>
		<option value="L">Ladinisch</option>
	</select>
	
	<br />
	<br />
	
	<input type="radio" name="alle" value="nein" checked /> Nur bisher nicht übersetzte Begriffe anzeigen
	<br />
	<input type="radio" name="alle" value="ja" /> Alle Begriffe anzeigen
	
	<br />
	<br />
	
	<div id="inhalt">
	
	</div>
	<?php
}

function va_echo_translation_list (){
	global $va_xxx;
	$entries = $va_xxx->get_results('SELECT Schluessel, Begriff_D, Kontext, Begriff_' . $_POST['lang'] . ' FROM Uebersetzungen WHERE ' . ($_POST['all'] == 'true'? '1' : 'Begriff_' . $_POST['lang'] . " = ''"), ARRAY_N);
	
	?>
	<table class="wp-list-table widefat fixed">
	<col width="10%">
	<col width="30%">
	<col width="30%">
	<col width="30%">
	<tr>
		<th>
			Kürzel
		</th>
		<th>
			Deutscher Begriff
		</th>
		<th>
			Übersetzter Begriff
		</th>
		<th>
			Kontext
		</th>
	</tr>
	<?php
	
	foreach ($entries as $e){
		$id = 'uber' . preg_replace('/\s|,|\.|-/', '', $e[0]);
	?>
		<tr>
			<td><?php echo $e[0]; ?></td>
			<td><?php echo $e[1]; ?></td>
			<td><textarea id="<?php echo $id; ?>" style="width: 100%" onChange="saveValue('<?php echo $e[0]; ?>', '<?php echo $id; ?>')"><?php echo $e[3]; ?></textarea></td>
			<td><?php echo $e[2]; ?></td>
		</tr>
	<?php
	}
	?>
	</table>
	<?php
	die();
}
?>