<?php
function showDBDescription (){
	?>

	<script type="text/javascript">
	jQuery(function (){
		jQuery("#tabDiv").tabs();
	});
	
	function editText (name){
		var textElement = jQuery("#text" + name);
		var text = textElement.text();
		var box = document.createElement("textarea");
		box.rows = 10;
		box.style.width = "100%";
		box.addEventListener("input", fieldInput);
		box.addEventListener("blur", saveChanges);
		box.appendChild(document.createTextNode(text));
		textElement.html(box);
		
	}
	
	function fieldInput (){
		jQuery(this).addClass("elementChanged");
	}
	
	function saveChanges (){
		alert(9);
	}
	</script>

	<?php
	global $va_xxx;
	global $admin;
	
	$tables = $va_xxx->get_results('SELECT * FROM admin ORDER BY Art ASC, Tabelle ASC', ARRAY_A);
	$kinds = $va_xxx->get_col("SELECT DISTINCT Art FROM admin WHERE Art != '' ORDER BY Art ASC", 0);
	$currentKind = '';
	?>
	
	<br />
	
	<div id="tabDiv" class="entry-content">
		<ul>
			<li>
				<a href="#tabEmpty">(Ohne Kategorie)</a>
			</li>
			
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
		
		<div id="tabEmpty">
		<?php
			foreach ($tables as $table){
				if($table['Art'] !== $currentKind){
					$currentKind = $table['Art'];
					echo '</div>';
					echo '<div id="tab' . str_replace(' ', '_', $table['Art']) . '">';
				}
				echo '<h3>' . ucfirst($table['Tabelle']) . ($admin? "&nbsp<a href='javascript:editText(\"" . $table['Tabelle'] . "\");' style='font-size: 70%; color : blue'>(Bearbeiten)</a>" : '') . '</h3>';
				parseSyntax($table['Beschreibung'], true, true);
				echo '<div id="text' . $table['Tabelle'] . '">' .  $table['Beschreibung'] . '</div>';
			}
		?>
		</div>
	</div>
	<?php
	}
?>