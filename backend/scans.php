<?php
//Scans auflisten

//PHP Datenbank-Operationen
function getSources () {
	global $va_xxx;
	return $va_xxx->get_results("select distinct Abkuerzung from Bibliographie where VA_Erfassung = '1'", ARRAY_N);
}

function getPages ($source) {
	global $va_xxx;
	return $va_xxx->get_results("select distinct Karte, Erhebung from Stimuli where Erhebung like '$source' order by Karte * 1", ARRAY_N);
}


//Scans


function scans () {
	$scan_dir = get_home_path() . 'dokumente/scans/';
	
	$sources = getSources();
	
	if(isset($_POST['Refresh'])){
		unset($_POST['Refresh']);
		session_unset();
	}
	
	if(!isset($_SESSION['Overview'])){
		$_SESSION['Overview'] = array ();
		$pages = getPages('%');
		
		foreach($sources as $s){
				$_SESSION['Overview']['CStimuli'][$s[0]] = 0;
				$_SESSION['Overview']['CPages'][$s[0]] = 0;
				$_SESSION['Overview']['CEA'][$s[0]] = 0;
				$_SESSION['Overview']['CZA'][$s[0]] = 0;
		}
				
		foreach ($pages as $p){
			$_SESSION['Overview']['CStimuli'][$p[1]]++;
			if(!file_exists($scan_dir . $p[1] . '#' . $p[0] . '.pdf')){
				$_SESSION['Overview']['CZA'][$p[1]]++;
			}
		}
		
		$files = scandir($scan_dir);
		foreach($files as $f){
			$pos = strpos($f, "#");
			if($pos){
				$abkuerzung = substr($f, 0, $pos);
				if(!isset($_SESSION['Overview']['CPages'][$abkuerzung])){
					$_SESSION['Overview']['CPages'][$abkuerzung] = 0;
				}
				$_SESSION['Overview']['CPages'][$abkuerzung]++;
				$seite = substr($f, $pos + 1, strpos($f, ".") - $pos -1);
				$found = false;
				foreach ($pages as $p){
					if($p[0] == $seite && $p[1] == $abkuerzung){
						$found = true;
						break;
					}
				}
				if(!$found)
					$_SESSION['Overview']['CEA'][$abkuerzung]++;
			}
		}
	}
	?>
	
	<div id="Uebersicht">
		<h1>Übersicht</h1>
		
		<br />
		<br />
		
		<table border="1">
			<tr>
				<th>Quelle</th>
				<th>Anzahl Stimuli</th>
				<th>Anzahl Scans</th>
				<th>Scans ohne Stimulus-Eintrag</th>
				<th>Stimulus-Einträge ohne Scan</th>
			</tr>
			<?php
				foreach ($sources as $s){
					if($_SESSION['Overview']['CStimuli'][$s[0]] > 0 || $_SESSION['Overview']['CPages'][$s[0]] > 0){
						echo "<tr>";
						echo "<td>" . $s[0] . "</td>";
						echo "<td>" . $_SESSION['Overview']['CStimuli'][$s[0]] . "</td>";
						echo "<td>" . $_SESSION['Overview']['CPages'][$s[0]] . "</td>";
						echo "<td>" . $_SESSION['Overview']['CEA'][$s[0]] . "</td>";
						echo "<td>" . $_SESSION['Overview']['CZA'][$s[0]] . "</td>";
						echo "</tr>";
					}
				}
			?>
		</table>
		
		<br />
		
		<form name="reload" method="POST">
			<input class="button button-primary" type="submit" value="Übersicht aktualisieren" name="Refresh"/>
		</form>
	</div>
	
	<br />
	
	<h1> Vorhandene Scans </h1>
	
	<br />
	<table>
		<tr>
			<td>
				<h4> Quelle </h4>
			</td>
			<td>
				<form name="scanList" method="GET">
					<select name="source" id="scanL" onChange="this.form.submit()">
						<option><?php echo DEFAULT_SELECT; ?></option>
			<?php
				$sources = getSources();
				$curr_entry = '';
				if(isset($_GET['source']))
					$curr_entry = $_GET['source'];
				foreach ($sources as $s){
					echo "<option" . (strcmp($curr_entry,$s[0])? "" : " selected") . ">$s[0]</option>\n";
				}
			?>
					</select>
					<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
				</form>
			</td>
			<td>
				<form name="methodList" method="GET">
					<select name="method" id="methodL" onChange="this.form.submit()">
						<option value="scans" <?php echo (!isset($_GET['method']) || $_GET['method'] == 'scans'? 'selected': '') ?>>Fehlende Scans</option>
						<option value="stimuli" <?php echo (isset($_GET['method']) && $_GET['method'] == 'stimuli'? 'selected': '') ?>>Fehlende Stimuli</option>
					</select>
					<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
					<input type="hidden" name="source" value="<?php echo $_GET['source']; ?>" />
				</form>
			</td>
		</tr>
	</table>
	<br />
	
	<?php
	
	if(!isset($_GET['method'])){
		$_GET['method'] = 'scans';
	}
	
	if(isset($_GET['source'])){
		$source = $_GET['source'];
		?>
		<form name="show" method="GET">
			<input name="show" value="All" <?php echo (isset($_GET['show']) && $_GET['show'] == 'All')? 'checked="checked"' : '' ?>  type="radio" onChange="this.form.submit()"> <span>Alle anzeigen</span><br />
			<input name="show" value="Missing" <?php echo (!isset($_GET['show']) || $_GET['show'] == 'Missing')? 'checked="checked"' : '' ?> type="radio" onChange="this.form.submit()"> <span>Nur fehlende</span><br />
			<input type="hidden" name="page" value="<?php echo $_GET['page']; ?>" />
			<input type="hidden" name="source" value="<?php echo $_GET['source']; ?>" />
			<input type="hidden" name="method" value="<?php echo $_GET['method']; ?>" />
		</form>
		<br />
		<table border="1">
			<tr>
				<th>Seite</th>
				<th><?php echo ($_GET['method'] == 'scans'? 'Scan Vorhanden': 'Stimulus Vorhanden') ?> </th>
			<tr>
		<?php
		if(!isset($_GET['show'])){
		$_GET['show'] = 'Missing';
		}
		
		$pages = getPages($source);
		
		if($_GET['method'] == 'scans'){
			foreach ($pages as $p){
				$file_ex = file_exists($scan_dir . $source . '#' . $p[0] . '.pdf');
				if($file_ex && isset($_GET['show']) && $_GET['show'] == 'Missing')
					continue;
				echo "<tr><td> $p[0] </td>";
				echo $file_ex? '<td style="color: GREEN">Ja': '<td style="color: RED">Nein' . '</td>';
				echo '</tr>';
			}
		}
		else {
			$files = glob($scan_dir . $source . "*");
			if($files){
				foreach($files as $f){
					$pos = strpos($f, "#");
					if($pos){
						$found = false;
						foreach($pages as $p){
							$abkuerzung = substr($f, 0, $pos);
							$seite = substr($f, $pos + 1, strpos($f, ".") - $pos -1);
							if($p[0] == $seite){
								$found = true;
								break;
							}
						}
						if($found && isset($_GET['show']) && $_GET['show'] == 'Missing')
							continue;
						echo "<tr><td> $f </td>";
						echo $found? '<td style="color: GREEN">Ja': '<td style="color: RED">Nein' . '</td>';
						echo '</tr>';
					}
				}
			}
		}
		?>
		</table>
		<?php
	}
}

?>