<?php
	add_action('wp_ajax_va_protocols', 'ajax_protocols_va');

	function ajax_protocols_va (){
		global $va_xxx;
		
		switch ($_POST['query']){
			case 'get':
				echo showProtocol($va_xxx->get_row('SELECT * FROM Protokolle WHERE Id_Protokoll = ' . $_POST['id'], ARRAY_A), $_POST['mode'] === 'edit');
				break;
				
			case 'search':
				echo showSearchPage(searchResults($_POST['string']), $_POST['string']);
				break;
				
			case 'new':
				$time = $va_xxx->get_row('SELECT 
												DATE_ADD(Datum,  INTERVAL 7 DAY) as Datum,
												Beginn,
												next_person (DATE_ADD(Datum,  INTERVAL 7 DAY), Protokollant) as Protokollant
											FROM Protokolle WHERE Id_Protokoll = (SELECT max(Id_Protokoll) FROM Protokolle)', ARRAY_A, 0);
				$va_xxx->insert('Protokolle', $time, array('%s', '%s'));
				$id_new = $va_xxx->insert_id;
				$va_xxx->query("INSERT INTO VTBL_Protokolle_Teilnehmer (Id_Protokoll, Person, Anwesend, Kommentar) 
									SELECT " . $id_new . ", Kuerzel, 0, IF(Art = 'hk', 'Teilnahme nicht obligat.', '') 
									FROM Personen JOIN Stellen USING (Kuerzel) 
									WHERE Art in ('hk', 'prom', 'postdoc', 'leitung') AND Startdatum < '" . $time['Datum'] . "' AND (Enddatum IS NULL OR Enddatum > '" . $time['Datum'] . "')");
				echo json_encode(array($id_new, $time['Datum']));
				break;
				
				
			case 'update':
				$_POST['value'] = stripslashes($_POST['value']);
				if($_POST['id'] == "protocolDate"){
					$result = $va_xxx->update('Protokolle', array('Datum' => $_POST['value']), array('Id_Protokoll' => $_POST['pid']), array ('%s'));
				}
				else if ($_POST['id'] == "protocolHoursStart"){
					$result = $va_xxx->query($va_xxx->prepare('UPDATE Protokolle SET Beginn = MAKETIME(%d, IF(MINUTE(Beginn) IS NULL, 0, MINUTE(Beginn)), 0) WHERE ID_Protokoll = %d', $_POST['value'], $_POST['pid']));
				}
				else if ($_POST['id'] == "protocolMinutesStart"){
					$result = $va_xxx->query($va_xxx->prepare('UPDATE Protokolle SET Beginn = MAKETIME(IF(HOUR(Beginn) IS NULL, 0, HOUR(Beginn)), %d, 0) WHERE ID_Protokoll = %d', $_POST['value'], $_POST['pid']));
				}
				else if ($_POST['id'] == "protocolHoursEnd"){
					$result = $va_xxx->query($va_xxx->prepare('UPDATE Protokolle SET Ende = MAKETIME(%d, IF(MINUTE(Ende) IS NULL, 0, MINUTE(Ende)), 0) WHERE ID_Protokoll = %d', $_POST['value'], $_POST['pid']));
				}
				else if ($_POST['id'] == "protocolMinutesEnd"){
					$result = $va_xxx->query($va_xxx->prepare('UPDATE Protokolle SET Ende = MAKETIME(IF(HOUR(ENDE) IS NULL, 0, HOUR(Ende)), %d, 0) WHERE ID_Protokoll = %d', $_POST['value'], $_POST['pid']));
				}
				else if($_POST['id'] == "protocolWriter"){
					$result = $va_xxx->update('Protokolle', array('Protokollant' => $_POST['value']), array('Id_Protokoll' => $_POST['pid']), array ('%s'));
				}
				else if(substr($_POST['id'], 0, 16) == "protocolAttended"){
					$person = substr($_POST['id'], 16);
					$result = $va_xxx->update('VTBL_Protokolle_Teilnehmer', array('Anwesend' => $_POST['value'] === 'true'), array('Id_Protokoll' => $_POST['pid'], 'Person' => $person), array ('%s'));
				}
				else if(substr($_POST['id'], 0, 15) == "protocolComment"){
					$person = substr($_POST['id'], 15);
					$result = $va_xxx->update('VTBL_Protokolle_Teilnehmer', array('Kommentar' => $_POST['value']), array('Id_Protokoll' => $_POST['pid'], 'Person' => $person), array ('%s'));
				}
				else if(substr($_POST['id'], 0, 13) == "protocolTitle"){
					$number = substr($_POST['id'], 13);
					$result = $va_xxx->query($va_xxx->prepare('INSERT INTO Protokolle_TOPs (Id_Protokoll, Nummer, Titel) VALUES (%d, %d, %s) ON DUPLICATE KEY UPDATE Titel = %s', $_POST['pid'], $number, $_POST['value'], $_POST['value']));
				}
				else if(substr($_POST['id'], 0, 12) == "protocolText"){
					$number = substr($_POST['id'], 12);
					$result = $va_xxx->query($va_xxx->prepare('INSERT INTO Protokolle_TOPs (Id_Protokoll, Nummer, Inhalt) VALUES (%d, %d, %s) ON DUPLICATE KEY UPDATE Inhalt = %s', $_POST['pid'], $number, $_POST['value'], $_POST['value']));
				}
				else {
					$result = false;
				}
				if($result === false){
					echo 'error';
				}
				else {
					echo 'success';
				}
				break;
				
			case 'deleteTop':
				$va_xxx->delete('Protokolle_TOPs', array('Id_Protokoll' => $_POST['pid'], 'Nummer' => $_POST['number']));
				if($result === false){
					echo 'error';
				}
				else {
					echo 'success';
				}
				break; 
		}
		die;
	}

	function protokolle (){
		global $va_xxx;
		$protokolle = $va_xxx->get_results('SELECT * FROM Protokolle ORDER BY DATUM DESC', ARRAY_A);

		if($_GET['mode']){
			$edit = $_GET['mode'] === 'edit';
		}
		
		?>
		
		<script type="text/javascript">
			var url = "<?php echo get_permalink();?>";

			jQuery(function (){
				
				bindChangeListeners();
				jQuery(".dateField").datepicker({"dateFormat": "dd.mm.yy"});
				
				setInterval(function (){
					if(jQuery(":focus").is("textarea, input") && jQuery(":focus").hasClass("elementChanged")){
						jQuery(":focus").change();
					};
				}, 5000);				
							
				History.Adapter.bind(window,'statechange',function(){
					if(History.getState()["data"]["id"]){
						var mode = History.getState()["data"]["mode"];
						jQuery.post(ajax_object.ajaxurl, {
							"action" : "va_protocols",
							"query" : "get",
							"id" : History.getState()["data"]["id"],
							"mode" : mode
						}, function (response){
							jQuery("#protocolArea").html(response);
							jQuery("#modeList").val(mode);
							jQuery(".dateField").datepicker({"dateFormat": "dd.mm.yy"});
							bindChangeListeners();
							addBiblioQTips(jQuery("#protocolTextArea"));
						});
					}
        			else if (History.getState()["data"]["search"]){
        				jQuery.post(ajax_object.ajaxurl, {
							"action" : "va_protocols",
							"query" : "search",
							"string" : History.getState()["data"]["search"]
						},
						function (response){
							jQuery("#protocolArea").html(response);
						});
        			}
    			});
				
				<?php
				if($_GET['protocol'])
					echo 'jQuery("#protocolList").val("' . $_GET['protocol'] . '");';
				?>
				
				<?php
				if($_GET['search'])
					echo 'jQuery("#searchBox").val("' . urldecode($_GET['search']) . '");';
				?>
				
				jQuery("#protocolList").change(function (){
					var date = jQuery.datepicker.parseDate('yy-mm-dd', jQuery(this).find(":selected").text().substring(0, 10));
					date.setHours(23, 59, 59);
					var mode = date > new Date()? "edit" : "read";
					selectProtocol(this.value, mode);
				});
				
				jQuery("#searchBox").keypress(function (event){
					if(event.which == 13) { //Enter
						History.pushState({"search" : this.value}, "", url + "&search=" + encodeURIComponent(this.value));
					}
				});

				addBiblioQTips(jQuery("#protocolTextArea"));
			});
			
			function bindChangeListeners (){
				jQuery(".protocolEditField").unbind("input");
				jQuery(".protocolEditField").bind("input", function (){
					jQuery(this).addClass("elementChanged");
				});
				jQuery(".protocolEditField").unbind("change");
				jQuery(".protocolEditField").on("change", changed);
			}
			
			function changed (){		
				var object = this;
				var value = this.value;
				var number = jQuery("#protocolNumber").text().trim();
				
				if(this.type == "checkbox"){
					jQuery(this).next().addClass("changesSent");
					value = this["checked"];
				}
				else if (jQuery(this).is("input, textarea") && !jQuery(this).is(".hasDatepicker")){
					if(jQuery(this).hasClass("elementChanged")){
						jQuery(this).addClass("changesSent");
						jQuery(this).removeClass("elementChanged");
					}
					else {
						return;
					}
				}
				else {
					jQuery(this).addClass("changesSent");
				}
				
				if(this.id == "protocolDate"){
					value = jQuery.datepicker.formatDate('yy-mm-dd', jQuery.datepicker.parseDate('dd.mm.yy', this.value));
					jQuery("#protocolList option[value='" + number + "']").text(value + " (" + number + ")");
				}

				jQuery.post(ajax_object.ajaxurl, {
					"action" : "va_protocols",
					"query" : "update",
					"pid" : number,
					"id" : this.id,
					"value" : value
				}, function (response){
					if(response != "success"){
						alert(response);
					}
					else {
						unColor(object);
					}
				});
			}
			
			function unColor(object){
				if(object.type == "checkbox"){
					jQuery(object).next().removeClass("changesSent")
				}
				else {
					jQuery(object).removeClass("changesSent")
				}
			}
			
			function selectProtocol (id, mode){
				jQuery("#protocolList").val(id);
				History.pushState({"id" : id, "mode" : mode}, "", url + "&protocol=" + id + "&mode=" + mode);
			}
			
			function addTop (){
				var title = prompt("Titel:");
				var number;
				if(jQuery("#protocolTextArea div").length == 0){
					number = 1;
				}
				else {
					number = jQuery("#protocolTextArea div:last-child h4 input").prop("id").substring(13) * 1 + 1;
				}
				var div = jQuery("<div  id='protocolTOP" + number + "' tabindex='-1' />");
				div.append("<h4>" + number + ") <input type='text' class='protocolEditField' size='62' maxlength='500' value='"+ title + "' id='protocolTitle" + number + "' />&nbsp;<a href='javaScript:deleteTop(" + number + ");'>Löschen</a></h4>");
				div.append("<textarea id='protocolText" + number + "' class='protocolEditField' cols='75' rows='10'></textarea>");
				div.find("input:first").addClass("elementChanged");
				jQuery("#protocolTextArea").append(div);
				bindChangeListeners();
				jQuery("#protocolTitle" + number).trigger("change");
				div.focus();
			}
			
			function newProtocol (){
				jQuery.post(ajax_object.ajaxurl, {
					"action" : "va_protocols",
					"query" : "new"
				}, function (response){
					data = JSON.parse(response);
					jQuery("#protocolList").prepend(jQuery("<option>", {
						value: data[0],
						text: data[1] + " (" + data[0] + ")"
					}));
					selectProtocol(data[0], "edit");
				});
			}
			
			function deleteTop (number){
				jQuery.post(ajax_object.ajaxurl, {
					"action" : "va_protocols",
					"query" : "deleteTop",
					"pid" : jQuery("#protocolNumber").text().trim(),
					"number" : number
				}, function (response){
					if(response == "success"){
						jQuery("#protocolTOP" + number).remove();
					}
					else {
						alert(response);
					}
				});
			}
			
			function changeMode (val){
				var pid = jQuery("#protocolNumber").text().trim();
				History.pushState({"id" : pid, "mode" : val}, "", url + "&protocol=" + pid + "&mode=" + val);
			}
			
		</script>
		
		
		<div class="entry-content">
			
			<div id="protocolSelection" style="border-style: solid;	border-width: 1px; padding: 3px;">
				Protokoll: 
				<select id="protocolList">
					<?php
					foreach ($protokolle as $protokoll){
						echo '<option value="' . $protokoll['Id_Protokoll'] . '">' . $protokoll['Datum'] . ' (' . $protokoll['Id_Protokoll'] . ')' . '</option>';
					}
					?>
				</select>

				Suche: 
				<input id="searchBox" type="text" />
				
				<input type="button" style="float : right" class="button button-primary" value="Neues Protokoll" onClick="newProtocol();" />
				
				<br />
				
				<div>
					Modus:&nbsp;&nbsp;&nbsp;
					<select id="modeList" onChange="changeMode(this.value);">
						<option value="read">Lesen</option>
						<option value="edit">Bearbeiten</option>
					</select>
				</div>
			</div>
			
			<br />
			
			
			<div id ="protocolArea">
				<?php
				if($_GET['protocol']){
					foreach ($protokolle as $protokoll){
						if($protokoll['Id_Protokoll'] === $_GET['protocol']){
							if(!isset($edit)){
								$endOfDay = new DateTime($protokoll['Datum']);
								$endOfDay->setTime(23,59,59);
								$edit = new DateTime() < $endOfDay;
							}
							showProtocol($protokoll, $edit);
							break;
						}
					}
					
				}
				else if($_GET['search']){
					showSearchPage(searchResults(urldecode($_GET['search'])), urldecode($_GET['search']));
				}
				else {
					if(!isset($edit)){
						$endOfDay = new DateTime($protokolle[0]['Datum']);
						$endOfDay->setTime(23,59,59);
						$edit = new DateTime() < $endOfDay;
					}
					showProtocol($protokolle[0], $edit);
				}
				?>
			</div>
		</div>
		
		<script type="text/javascript">
			jQuery("#modeList").val("<?php echo $edit? 'edit': 'read';?>");
		</script>
		
		<?php
	}
	
	function showProtocol ($row, $editMode){
		global $va_xxx;
		?>
		
		<table>
			<tr>
				<td>
					Protokoll_Nr.
				</td>
				<td id="protocolNumber">
					<?php echo $row['Id_Protokoll'];?>
				</td>
			</tr>
			<tr>
				<td>
					Datum
				</td>
				<td>
					<?php 
					if($editMode){
						echo inputField(10, 10, 'protocolDate', date('d.m.Y', strtotime($row['Datum'])), 'dateField');
					}
					else
					{
						echo date('d.m.Y', strtotime($row['Datum']));
					}
					?>
				</td>
			</tr>
			<tr>
				<td>
					Beginn
				</td>
				<td>
					<?php 
					if($editMode){
						echo inputField(2, 2, 'protocolHoursStart', date('G', strtotime($row['Beginn']))) . ' : ' . inputField(2, 2, 'protocolMinutesStart', date('i', strtotime($row['Beginn'])));
					}
					else
					{
						echo date('G:i', strtotime($row['Beginn']));
					}
					?> Uhr
				</td>
			</tr>
			<tr>
				<td>
					Ende
				</td>
				<td>
					<?php 
					if($editMode){
						echo inputField(2, 2, 'protocolHoursEnd', date('G', strtotime($row['Ende']))) . ' : ' . inputField(2, 2, 'protocolMinutesEnd', date('i', strtotime($row['Ende'])));
					}
					else
					{
						echo date('G:i', strtotime($row['Ende']));
					}?> Uhr
				</td>
			</tr>
			<tr>
				<td>
					Protokollant
				</td>
				<td>
					<?php
					if($editMode){
						echo '<select id="protocolWriter" class="protocolEditField">';
						$persons = $va_xxx->get_col("SELECT Kuerzel FROM Personen JOIN Stellen USING (Kuerzel) WHERE Startdatum < '" . $row['Datum'] . "' AND (Enddatum > '" . $row['Datum'] . "' OR Enddatum IS NULL)", 0);
						foreach ($persons as $person){
							echo '<option value="' . $person . '"' . ($person == $row['Protokollant']? ' selected': '') . '>' . $person . '</option>';
						}
						echo '</select>';
					}
					else
					{
						echo ktop($row['Protokollant']);
					}
					?>
				</td>
			</tr>
		</table>
		
		<br />
		<br />
		
		<h3>
			Teilnehmer
		</h3>
		
		<?php 
		$tt = $va_xxx->get_results("SELECT * FROM VTBL_Protokolle_Teilnehmer WHERE Id_Protokoll = " . $row['Id_Protokoll'], ARRAY_A);
		foreach ($tt as $t){
			if($t['Anwesend']){
				echo '<input type="checkbox" id="protocolAttended' . $t['Person'] . '" class="protocolEditField" checked ' . ($editMode? '': 'disabled') . ' />';
			}
			else {
				echo '<input type="checkbox" style="background: red" id="protocolAttended' . $t['Person'] . '" class="protocolEditField" unchecked ' . ($editMode? '': 'disabled') . ' />';
			}
			echo ' <label>' . ktop($t['Person']) . '</label>';
			if($editMode){
				echo ' (' . inputField(30, 200, 'protocolComment' . $t['Person'], $t['Kommentar']) . ')';
			}
			else if($t['Kommentar']){
				echo ' (' . $t['Kommentar'] . ')';
			}
			echo '<br />';
		}
		?>
		
		<br />
		<br />
		
		<?php 
		$tops = $va_xxx->get_results("SELECT * FROM Protokolle_TOPs WHERE Id_Protokoll = '" . $row['Id_Protokoll']. "' ORDER BY Nummer ASC", ARRAY_A);
		
		if(!$editMode) {
		?>
		<h3>
			Tagesordnung
		</h3>
		
		<?php

		foreach ($tops as $top){
			echo $top['Nummer'] . ') <a href="#' . $top['Nummer'] . '">' . $top['Titel'] . '</a>';
			echo '<br />';
		}
		
		?>
		
		<br />
		
		<?php
		}
		?>
		
		<h3>
			Protokoll
			<?php 
			if($editMode){
				echo '&nbsp;<a href="javaScript:addTop();">(TOP hinzufügen)</a>';
			}
			?>
		</h3>

		<div id="protocolTextArea">
	
			<?php
	
			foreach ($tops as $top){
				echo getTop($top['Nummer'], $top['Titel'], $top['Inhalt'], $editMode);
			}
			
			?>
		</div>
			<?php
			
			if($editMode){
				echo '<a href="javaScript:addTop();">(TOP hinzufügen)</a>';
			}
	}

	function getTop ($number, $title, $content, $editMode){
		$result = '<div id="protocolTOP' . $number . '">';
		$result .= '<a name="' . $number . '"></a>';
		$result .= '<h4>' . $number . ') ';
		if($editMode){
			$result .= inputField(62, 500, 'protocolTitle' . $number, $title);
			$result .= '&nbsp;<a href="javaScript:deleteTop(' . $number . ');">Löschen</a>';
		}
		else {
			$result .= $title;
		}
		
		$result .= '</h4>';
		
		if($editMode){
			$result .= '<textarea id="protocolText' . $number . '" class="protocolEditField" cols="75" rows="10">';
			$result .= $content;
			$result .= '</textarea>';
		}
		else {
			parseSyntax($content, true, true);
			$result .= $content;
		}
		return $result . '</div>';	
	}

	function inputField ($length, $maxlength, $id, $val, $class = ''){
		return "<input type='text' class='protocolEditField $class' size='$length' maxlength='$maxlength' value='$val' id='$id' />";
	}

	function showSearchPage ($protocols, $searchWords){
		
		echo '<h2>Suchergebnisse<h2>';		
		
		$firstWord =  array_shift(explode(' ', $searchWords));
			
		foreach ($protocols as $protocol){
			echo '<h3><a href="' . get_permalink(1760) . '&protocol=' . $protocol['Id_Protokoll'] . '"> Protokoll ' . $protocol['Id_Protokoll'] . ' (' . $protocol['Datum'] . ')</a></h3>';

			$indexSearchWord = mb_stripos($protocol['Titel'], $firstWord);
			if($indexSearchWord !== false){
				echo '[...] ' . mb_substr($protocol['Titel'], 0, $indexSearchWord) 
					. '<font color="red">' . $firstWord . '</font>' 
					. mb_substr($protocol['Titel'], $indexSearchWord + strlen($firstWord)) . ' [...]';
			}
			else {
				$indexSearchWord = mb_stripos($protocol['Inhalt'], $firstWord);
				if($indexSearchWord !== false){
					echo '[...] ' . mb_substr($protocol['Inhalt'], max(0, $indexSearchWord - 50), $indexSearchWord - max(0, $indexSearchWord - 50)) 
						. '<font color="red">' . $firstWord . '</font>' 
						. mb_substr($protocol['Inhalt'], $indexSearchWord + strlen($firstWord), min(strlen($protocol['Inhalt']) - $indexSearchWord, 50)) . ' [...]';
				}
				else {
					echo '[...]';
				}
			}
			
			echo '<br />';  
		}
	}

	function ktop ($k){
		global $va_xxx;
		return $va_xxx->get_var("SELECT CONCAT(Vorname, ' ', Name) FROM Personen WHERE Kuerzel = '" . $k . "'", 0, 0);
	}
	
	function searchResults ($searchString){
		global $va_xxx;
		return $va_xxx->get_results("
			SELECT Id_Protokoll, Titel, Inhalt, Datum
			FROM Protokolle_TOPs JOIN Protokolle USING (Id_Protokoll)
			WHERE MATCH(Titel, Inhalt) AGAINST ('" . '+' . str_replace(' ', '* +', $searchString) . "*' IN BOOLEAN MODE)" , ARRAY_A);
	}
?>