<?php
	add_action('wp_ajax_va_protocols', 'ajax_protocols_va');

	function ajax_protocols_va (){
		global $va_xxx;
		
		switch ($_POST['query']){
			case 'get':
			    $edit = false;
			    if(isset($_POST['mode']) && $_POST['mode'] === 'edit'){
			        $edit = true;
			    }
			    
			    echo showProtocol($va_xxx->get_row('SELECT * FROM Protokolle WHERE Id_Protokoll = ' . $_POST['id'], ARRAY_A), $edit);
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
				
				$default_link = 'https://lmu-munich.zoom.us/j/95275082216?pwd=TTlnbDdqY3l4N21LMnVxdVZ1d2pWQT09';
				if ($default_link){
					$time['link'] = $default_link;
				}
											
				$va_xxx->insert('Protokolle', $time);
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
				else if($_POST['id'] == "protocolLink"){
					$result = $va_xxx->update('Protokolle', array('Link' => $_POST['value']), array('Id_Protokoll' => $_POST['pid']), array ('%s'));
				}
				else if(substr($_POST['id'], 0, 16) == "protocolAttended"){
					$person = substr($_POST['id'], 16);
					$result = $va_xxx->update('VTBL_Protokolle_Teilnehmer', array('Anwesend' => $_POST['value'] === 'true'), array('Id_Protokoll' => $_POST['pid'], 'Person' => $person), array ('%s'));
					$va_xxx->update('Protokolle', ['Geaendert' => current_time('mysql')], ['Id_Protokoll' => $_POST['pid']]);
				}
				else if(substr($_POST['id'], 0, 15) == "protocolComment"){
					$person = substr($_POST['id'], 15);
					$result = $va_xxx->update('VTBL_Protokolle_Teilnehmer', array('Kommentar' => $_POST['value']), array('Id_Protokoll' => $_POST['pid'], 'Person' => $person), array ('%s'));
					$va_xxx->update('Protokolle', ['Geaendert' => current_time('mysql')], ['Id_Protokoll' => $_POST['pid']]);
				}
				else if(substr($_POST['id'], 0, 13) == "protocolTitle"){
					$number = substr($_POST['id'], 13);
					$result = $va_xxx->query($va_xxx->prepare('INSERT INTO Protokolle_TOPs (Id_Protokoll, Nummer, Titel) VALUES (%d, %d, %s) ON DUPLICATE KEY UPDATE Titel = %s', $_POST['pid'], $number, $_POST['value'], $_POST['value']));
					$va_xxx->update('Protokolle', ['Geaendert' => current_time('mysql')], ['Id_Protokoll' => $_POST['pid']]);
				}
				else if(substr($_POST['id'], 0, 12) == "protocolText"){
					$number = substr($_POST['id'], 12);
					$result = $va_xxx->query($va_xxx->prepare('INSERT INTO Protokolle_TOPs (Id_Protokoll, Nummer, Inhalt) VALUES (%d, %d, %s) ON DUPLICATE KEY UPDATE Inhalt = %s', $_POST['pid'], $number, $_POST['value'], $_POST['value']));
					$va_xxx->update('Protokolle', ['Geaendert' => current_time('mysql')], ['Id_Protokoll' => $_POST['pid']]);
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
				
			case 'add_top':
			    $new_top_number = $va_xxx->get_var($va_xxx->prepare('SELECT max(Nummer) FROM Protokolle_TOPs WHERE Id_Protokoll = %d', $_POST['pid'])) + 1;
			    
			    $text = stripslashes($_POST['text']);
			    $title = stripslashes($_POST['title']);
			    
			    $va_xxx->insert('Protokolle_TOPs', ['Id_Protokoll' => $_POST['pid'], 'Nummer' => $new_top_number, 'Titel' => $title, 'Inhalt' => $text]);
			    echo getTop ($new_top_number, $title, $text, $_POST['edit'] == 'true');
            break;
            
			case 'add_participant':
				$va_xxx->insert('VTBL_Protokolle_Teilnehmer', [
					'Id_Protokoll' => $_POST['protocol'],
					'Person' => $_POST['person'],
					'Anwesend' => 0,
					'Kommentar' => '']);
				
				echo 'success';
				break;
		}
		die;
	}

	function protokolle (){
		global $va_xxx;
		$protokolle = $va_xxx->get_results('SELECT * FROM Protokolle ORDER BY DATUM DESC', ARRAY_A);

		if(isset($_GET['mode'])){
		    $edit = $_GET['mode'] === 'edit';
		}
		
		$user_key = get_user_meta(get_current_user_id(), 'va_kuerzel', true);
		?>
		
		<script type="text/javascript">
			var url = "<?php echo get_permalink();?>";
		
			jQuery(function (){

				jQuery("#protocol_insert_top_confirm_button").on("click", addTopConfirmed);

				jQuery(document).on("va_todos_new", function (event, params){
					if(params["parent"] == -1){
						jQuery(".va_todo_parent_input").append(params["option"]);
					}
				});
				
				bindChangeListeners();
				jQuery(".dateField").datepicker({"dateFormat": "dd.mm.yy"});

				setInterval(function (){
					if(jQuery(":focus").is("textarea, input") && jQuery(":focus").hasClass("elementChanged")){
						jQuery(":focus").change();
					}

					if(jQuery("#modeList").val() == "read" && checkTime()){
						updateProtocol(jQuery("#protocolList").val(), false);
					}
				}, 5000);

				History.Adapter.bind(window,'statechange', function (){
					if(History.getState()["data"]["id"]){
						updateProtocol(History.getState()["data"]["id"], History.getState()["data"]["mode"] == "edit");
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
				if(isset($_GET['protocol']))
					echo 'jQuery("#protocolList").val("' . $_GET['protocol'] . '");';
				?>
				
				<?php
				if(isset($_GET['search']))
					echo 'jQuery("#searchBox").val("' . urldecode($_GET['search']) . '");';
				?>
				
				jQuery("#protocolList").change(function (){
					selectProtocol(this.value);
				});
				
				jQuery("#searchBox").keypress(function (event){
					if(event.which == 13) { //Enter
						History.pushState({"search" : this.value}, "", url + ((url.indexOf("?") === -1)? "?search=" : "&search=") + encodeURIComponent(this.value));
					}
				});

				<?php 
				if(isset($_GET['protocol']) && isset($edit) && $edit && va_check_lock($va_xxx, ['table' => 'Protokolle', 'value' => $_GET['protocol']]) != 'success'){
				    ?>
				    alert("Protokoll wird gerade bearbeitet!");
				    <?php
				    unset($edit);
				}
				?>

				<?php 
				if ($user_key == 'FZ'){
				    ?>
				    todoButtons();
				    <?php   
				}
				?>

				jQuery("#modeList").val("<?php echo (isset($edit) && $edit)? 'edit': 'read';?>");

				addBiblioQTips(jQuery("#protocolTextArea"));

				window.onunload = function (){
					removeLock ("Protokolle", null, null, "va_xxx", true);
				};

				jQuery(document).on("click", "#protocolAddParticipant", function (){
					var person = jQuery("#addParticipantSelect").val();
					var data = {
						"action" : "va_protocols",
						"query" : "add_participant",
						"protocol" : jQuery("#protocolList").val(),
						"person" : person
					};

					jQuery.post(ajax_object.ajaxurl, data, function (response){
						if(response == "success"){
							updateProtocol(jQuery("#protocolList").val(), jQuery("#modeList").val() == "edit");
							jQuery("#addParticipantSelect option[value=" + person  + "]").remove();
						}
						else {
							alert("Error");
						}
					});
				});
			});

			function checkTime (){
				var now = new Date();
				var date = jQuery.datepicker.parseDate("dd.mm.yy", jQuery("#protocol_date_td").text().trim());
				var endStr = jQuery("#protocol_end_td").text().trim();
				endStr = endStr.substring(0, endStr.indexOf(" "));
				var end;
				if(endStr == ""){
					end = [23, 59];
				}
				else {
					end =  endStr.split(":");
				}
				date.setHours(end[0], end[1], 0);
				
				if (now > date)
					return false;
				
				var startStr = jQuery("#protocol_start_td").text().trim();
				var start = startStr.substring(0, startStr.indexOf(" ")).split(":");
				date.setHours(start[0], start[1], 0);

				return now > date;
			}

			function updateProtocol (id, edit){
				var data = {
					"action" : "va_protocols",
					"query" : "get",
					"id" : id
				};
				if(edit){
					data["mode"] = "edit";
				}						
				
				jQuery.post(ajax_object.ajaxurl, data, function (response){
					try {
						jQuery("#protocolArea").html(response);
						jQuery("#modeList").val(data["mode"]? data["mode"]: "read");
						jQuery(".dateField").datepicker({"dateFormat": "dd.mm.yy"});
						bindChangeListeners();
						addBiblioQTips(jQuery("#protocolTextArea"));
					}
					catch (e){
						alert(response);
					}
				});
    			
			}
			
			function bindChangeListeners (){
				jQuery(".protocolEditField").off("input");
				jQuery(".protocolEditField").on("input", function (){
					if (jQuery(this).is("input, textarea") && !jQuery(this).is(".hasDatepicker")){
						jQuery(this).addClass("elementChanged");
					}
				});
				jQuery(".protocolEditField").off("change");
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
				console.log(object);
				if(object.type == "checkbox"){
					jQuery(object).next().removeClass("changesSent");
				}
				else {
					jQuery(object).removeClass("changesSent");
				}
			}
			
			function selectProtocol (id){
				jQuery("#protocolList").val(id);
				History.pushState({"id" : id}, "", getUrl(id));
			}

			function getUrl(id, mode){
				var res;
				if (url.indexOf("?") === -1){
					res = url + "?protocol=" + id;
				}
				else {
					res = url + "&protocol=" + id;
				}

				if(mode){
					res += "&mode=" + mode;
				}

				return res;
			}

			function addTop (edit){
				jQuery("#protocol_top_title_input").val("");
				jQuery("#protocol_top_text_input").val("");
				jQuery("#protocol_top_mode").val(edit? "1": "0");
				
				jQuery("#protocolNewTopDiv").dialog({
					"width" : 600,
					"height" : 400,
					"title" : "TOP hinzufügen"
				});
			}
			
			function addTopConfirmed (){
				jQuery("#protocolNewTopDiv").dialog("close");
				
				var title = jQuery("#protocol_top_title_input").val();
				var text = jQuery("#protocol_top_text_input").val();

				var edit = jQuery("#protocol_top_mode").val() == "1";
				
				jQuery.post(ajax_object.ajaxurl, {
					"action" : "va_protocols",
					"query" : "add_top",
					"title" : title,
					"text" : text,
					"pid" : jQuery("#protocolNumber").text().trim(),
					"edit" : edit
				}, function (response){
					var div = jQuery(response);
					jQuery("#protocolTextArea").append(div);					
					div.focus();
					
					if(edit){
						bindChangeListeners();
					}
				});
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
					selectProtocol(data[0]);
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
				if(val == "edit"){
					addLock ("Protokolle", pid , function (response){
						if(response == "success"){
							History.pushState({"id" : pid, "mode" : val}, "", getUrl(pid, val));
						}
						else {
							alert("Protokoll wird gerade bearbeitet!");
							jQuery("#modeList").val("read");
						}
					}, "va_xxx");
				}
				else {
					removeLock ("Protokolle", pid, null, "va_xxx");
					History.pushState({"id" : pid, "mode" : val}, "", getUrl(pid, val));
				}
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
				
				<?php
				if(current_user_can('va_transcripts_write')){
				?>
				<div>
					Modus:&nbsp;&nbsp;&nbsp;
					<select id="modeList" onChange="changeMode(this.value);">
						<option value="read">Lesen</option>
						<option value="edit">Bearbeiten</option>
					</select>
				</div>
				<?php
				}
				?>
			</div>
			
			<br />
			
			
			<div id ="protocolArea">
				<?php
				if(isset($_GET['protocol'])){
					foreach ($protokolle as $protokoll){
						if($protokoll['Id_Protokoll'] === $_GET['protocol']){
							if(!isset($edit)){
								$edit = false;
							}
							showProtocol($protokoll, $edit);
							break;
						}
					}
					
				}
				else if(isset($_GET['search'])){
					showSearchPage(searchResults(urldecode($_GET['search'])), urldecode($_GET['search']));
				}
				else {
					if(!isset($edit)){
						$edit = false;
					}
					showProtocol($protokolle[0], $edit);
				}
				?>
			</div>
		</div>
		
		<div style="display: none" id="protocolNewTopDiv">
			Titel:<input style="width: 400px; margin-left: 5px;" type="text" id="protocol_top_title_input" />
			<br />
			<br />
			Inhalt:
			<br />
			<textarea style="margin-top: 15px; width: 100%; height: 200px;" id="protocol_top_text_input"></textarea>
			<br />
			<br />
			<input type="button" value="Hinzufügen" id="protocol_insert_top_confirm_button" />
			<input type="hidden" id="protocol_top_mode" />
		</div>
		
		<?php
		
		if($user_key == 'FZ'){
            ?>
            <div style="position: fixed; top: 200px; right: 100px">
            	<?php echo va_get_todo_button('FZ');?>
            </div>
            <?php
		}
		    
	}
	
	function va_new_tops_possible (&$protocol){
	    $startTime = new DateTime($protocol['Datum']);
	    $timeArray = explode(':', $protocol['Beginn']);
	    $startTime->setTime($timeArray[0], $timeArray[1], $timeArray[2]);
	    return new DateTime() < $startTime;
	}
	
	function showProtocol ($row, $editMode){
		global $va_xxx;
		
		//Ignore edit mode if you only have reading access
		if(!current_user_can('va_transcripts_write')){
			$editMode = false;
		}
		
		$newTopsPossible = va_new_tops_possible($row);
		
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
				<td id="protocol_date_td">
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
				<td id="protocol_start_td">
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
				<td id="protocol_end_td">
					<?php 
					if($editMode){
					    if ($row['Ende'] === null){
					        echo inputField(2, 2, 'protocolHoursEnd', '') . ' : ' . inputField(2, 2, 'protocolMinutesEnd', '');
					    }
					    else {
						  echo inputField(2, 2, 'protocolHoursEnd', date('G', strtotime($row['Ende']))) . ' : ' . inputField(2, 2, 'protocolMinutesEnd', date('i', strtotime($row['Ende'])));
					    }
					}
					else
					{
					    echo $row['Ende'] === null? '': date('G:i', strtotime($row['Ende'])) . ' Uhr';
					}?>
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
				<?php
				if($editMode){
					echo '<tr><td>Link</td><td>' . inputField (62, 500, 'protocolLink', $row['link']) . '</td>';
				}
				else if (isset($row['link'])){
					echo '<tr><td>Link</td><td><a href="' . $row['link'] . '">' . $row['link'] . '</a></td>';
				}
				?>
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
		
		if ($editMode){
			echo '<br /><br />';
			echo '<select id="addParticipantSelect">';
			$persons = $va_xxx->get_results("SELECT Kuerzel, CONCAT(Vorname, ' ', Name) FROM Personen p WHERE NOT EXISTS (SELECT * FROM VTBL_Protokolle_Teilnehmer v WHERE v.Person = p.Kuerzel AND Id_Protokoll = " . $row['Id_Protokoll'] . ") ORDER BY Kuerzel", ARRAY_N);
			foreach ($persons as $person){
				echo '<option value="' . $person[0] . '">' . $person[1] . '</option>';
			}
			echo '</select>';
			echo '<input type="button" id="protocolAddParticipant" value="Teilnehmer hinzufügen" />';
		}
		?>
		
		<br />
		<br />
		
		<?php 
		$tops = $va_xxx->get_results("SELECT * FROM Protokolle_TOPs WHERE Id_Protokoll = '" . $row['Id_Protokoll']. "' ORDER BY Nummer ASC", ARRAY_A);
		
		if(!$editMode && !$newTopsPossible) {
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
			if($editMode || $newTopsPossible){
				echo '&nbsp;<a href="javaScript:addTop(' . ($editMode? 'true': 'false') . ');">(TOP hinzufügen)</a>';
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
				echo '<a href="javaScript:addTop(true);">(TOP hinzufügen)</a>';
			}
	}

	function getTop ($number, $title, $content, $editMode){
		$result = '<div id="protocolTOP' . $number . '" data-number="' . $number . '">';
		$result .= '<a name="' . $number . '"></a>';
		$result .= '<h4 style="font-weight: bold; font-size: 120%;">' . $number . ') ';
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
		
		echo '<h2>Suchergebnisse</h2>';		
		$searchWordList = explode(' ', trim($searchWords));
		$firstWord =  array_shift($searchWordList);
		
		$wordStart = $searchWords[strlen($searchWords) - 1] != ' '; 
			
		foreach ($protocols as $protocol){
			$protocolLink = get_page_link(get_page_by_title('Protokolle'));
			
			echo '<h3><a href="' . add_query_arg('protocol', $protocol['Id_Protokoll'], $protocolLink) . '"> Protokoll ' . $protocol['Id_Protokoll'] . ' (' . $protocol['Datum'] . ')</a></h3>';

			$title = remove_accents($protocol['Titel']);
			$indexSearchWord = findSeachWordInText($title, $firstWord, $wordStart);
			
			if($indexSearchWord !== false){
				echo '[...] ' . mb_substr($title, 0, $indexSearchWord) 
					. '<font color="red">' . $firstWord . '</font>' 
					. mb_substr($title, $indexSearchWord + strlen($firstWord)) . ' [...]';
			}
			else {
				$text = remove_accents($protocol['Inhalt']);
				$indexSearchWord = findSeachWordInText($text, $firstWord, $wordStart);
				if($indexSearchWord !== false){
					echo '[...] ' . mb_substr($text, max(0, $indexSearchWord - 50), $indexSearchWord - max(0, $indexSearchWord - 50)) 
						. '<font color="red">' . $firstWord . '</font>' 
						. mb_substr($text, $indexSearchWord + strlen($firstWord), min(strlen($text) - $indexSearchWord, 50)) . ' [...]';
				}
				else {
					echo '[...]';
				}
			}
			
			echo '<br />';  
		}
	}
	
	function findSeachWordInText ($text, $searchWord, $wordStart){
		
		$indexSearchWord = mb_stripos($text, $searchWord);
		$indexNextChar = $indexSearchWord + strlen($searchWord) + 1;
		
		while(!$wordStart && $indexSearchWord !== false && $indexNextChar< strlen($text) && ctype_alnum(mb_substr($text, $indexNextChar - 1, 1))){
			$indexSearchWord = mb_stripos($text, $searchWord, $indexNextChar);
			$indexNextChar = $indexSearchWord + strlen($searchWord) + 1;
		}
		
		return $indexSearchWord;
	}

	function ktop ($k){
		global $va_xxx;
		return $va_xxx->get_var("SELECT CONCAT(Vorname, ' ', Name) FROM Personen WHERE Kuerzel = '" . $k . "'", 0, 0);
	}
	
	function searchResults ($searchString){
		global $va_xxx;

		$addAsterix = $searchString[strlen($searchString) - 1] != ' ';
		
		$searchString = '+' . preg_replace('/([^ ]) ([^ ])/', '$1\* \+$2', $searchString) . ($addAsterix? '*' : '');
		
		return $va_xxx->get_results("
			SELECT Id_Protokoll, Titel, Inhalt, Datum
			FROM Protokolle_TOPs JOIN Protokolle USING (Id_Protokoll)
			WHERE MATCH(Titel, Inhalt) AGAINST (' . $searchString . ' IN BOOLEAN MODE)
			ORDER BY Datum DESC" , ARRAY_A);
	}
?>