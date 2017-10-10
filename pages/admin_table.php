<?php
function va_show_DB_description (){
	?>

	<script type="text/javascript">
	jQuery(function (){
		jQuery("#tabDiv").tabs();
		addBiblioQTips(jQuery("#tabDiv"));
	});
	
	function editText (name){

		jQuery.post(ajax_object.ajaxurl, {
			"action" : "va",
			"namespace" : "admin_table",
			"query" : "select",
			"table" : name
		}, function (response){
			var textElement = jQuery("#text" + name);
			var box = document.createElement("textarea");
			box.rows = 10;
			box.style.width = "100%";
			box.dataset.tableName = name;
			box.addEventListener("input", fieldInput);
			box.addEventListener("blur", saveChanges);
			box.appendChild(document.createTextNode(response));
			textElement.html(box);
			box.focus();
		});
	}
	
	function fieldInput (){
		jQuery(this).addClass("elementChanged");
	}
	
	function saveChanges (){
		var name = jQuery(this).data("tableName");
		var content = jQuery(this).val();

		var thisObject = this;
		
		jQuery.post(ajax_object.ajaxurl, {
			"action" : "va",
			"namespace" : "admin_table",
			"query" : "update",
			"table" : name,
			"content" : content
		}, function (response){
			jQuery("#text" + name).html(response);
			addBiblioQTips(jQuery("#text" + name));
		});
	}
	</script>

	<?php
	global $va_xxx;
	global $admin;
	global $va_mitarbeiter;
	
	if($admin || $va_mitarbeiter){
		$tables = $va_xxx->get_results('SELECT * FROM admin ORDER BY Art ASC, Tabelle ASC', ARRAY_A);
		$kinds = $va_xxx->get_col("SELECT DISTINCT Art FROM admin WHERE Art != '' ORDER BY Art ASC", 0);
	}
	else {
		$tables = $va_xxx->get_results("SELECT * FROM admin WHERE Tabelle in ('z_ling', 'z_geo') ORDER BY Art ASC, Tabelle ASC", ARRAY_A);
		$kinds = array ('Schnittstelle');
	}
	
		
	
	$currentKind = '';
	?>
	
	<br />
	
	<div id="tabDiv" class="entry-content">
		<ul>
			<?php 
			if($admin || $va_mitarbeiter){
			?>
			<li>
				<a href="#tabEmpty">(Ohne Kategorie)</a>
			</li>
			<?php 
			}
			?>
			
			<?php
			foreach($kinds as $kind){
			?>
			<li>
				<a href="#tab<?php echo str_replace(' ', '_', $kind);?>"><?php echo $kind;?></a>
			</li>
			<?php
			}
			?>
			
		</ul>
		
		<?php 
			if($admin || $va_mitarbeiter){
		?>
		<div id="tabEmpty" style="margin-bottom: 5em;">
		<?php
			}
			else {
				echo '<div>';	
			}
			foreach ($tables as $table){
				if($table['Art'] !== $currentKind){
					$currentKind = $table['Art'];
					echo '</div>';
					echo '<div id="tab' . str_replace(' ', '_', $table['Art']) . '" style="margin-bottom: 5em;">';
				}
				echo '<h3>' . ucfirst($table['Tabelle']) . ($admin? "&nbsp<a href='javascript:editText(\"" . $table['Tabelle'] . "\");' style='font-size: 70%; color : blue'>(Bearbeiten)</a>" : '') . '</h3>';
				parseSyntax($table['Beschreibung'], true, true);
				echo '<div id="text' . $table['Tabelle'] . '">' .  $table['Beschreibung'] . '</div>';
				echo '<hr />';
			}
		?>
		</div>
	</div>
	<?php
	}
?>