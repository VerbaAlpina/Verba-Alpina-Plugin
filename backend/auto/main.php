<?php

//Action Handler
add_action('wp_ajax_va_auto_ops', 'va_auto_ajax_ops');

include_once("tokenize.php");

function va_auto_ajax_ops (){
	switch ($_POST['type']){
		case 'tokenize':
			echo tokenizeAeusserungen($_POST['id_stimulus'], $_POST['vorschau'] == 'true'? true: false); //in tokenize.php
			break;
			
		case 'ipa':
			echo ipa(); //in ipa.php
			break;
			
		case 'updateAT':
			echo anzahlAeusserungen($_POST['id_stimulus']);
			break;
			
		case 'kml':
			echo pointListToKML($_POST['sql']);
		break;
			
		default:
			echo "Error";
	}
	die();
}



function va_auto (){
	
/*for ($i = 59; $i <= 70; $i++){
	$data = get_userdata($i);
	var_dump($data);
	//$data->add_cap('typification');
}*/
	
/*$names = array (array("amitschke", "anja.mitschke@gmx.de"),
array("huzunkaya", "huemeyra.uzunkaya@stud-mail.uni-wuerzburg.de"),
array("jfliessbach", "jan.fliessbach@gmail.com"),
array("sissel", "s.issel-dombert@uni-kassel.de"),
array("jreinhard", "janina.reinhardt@uni-konstanz.de"),
array("ckoch", "christian.koch.romanistik@uni-due.de"),
array("cwidera", "carmen.widera@uni-konstanz.de"),
array("tprohl", "tanja.prohl@uni-bamberg.de"),
array("kkaiser", "katharina.kaiser@uni-konstanz.de"),
array("lgaudino", "Livia.Gaudino-Fallegger@romanistik.uni-giessen.de"),
array("gseymer", "gesine.seymer@tu-dresden.de"),
array("rhesselbach", "robert.hesselbach@uni-wuerzburg.de"));

*/

/*$names = array (array('pgschwendner', 'Patricia.Gschwendner@gmx.de'));

foreach ($names as $name){
	$id = wp_create_user($name[0], 'workshop2016', $name[1]);
	$data = get_userdata($id);
	$data->add_cap('transcription');
	$data->add_cap('typification');
}*/

?>

<script type='text/javascript'>
	var vorschau;

	function tokenize (){
		var data = {'action' : 'va_auto_ops',
					'type' : 'tokenize',
					'id_stimulus' : jQuery("#id_stimFeld").val(),
					'vorschau' : vorschau,
		};
		jQuery.post(ajaxurl, data, function (response) {
			document.getElementById("Test").innerHTML = response;
			updateATabelle(jQuery("#id_stimFeld").val());
		});
	}
	
	function ipa (){
		var data = {'action' : 'va_auto_ops',
					'type' : 'ipa'
		};
		jQuery.post(ajaxurl, data, function (response) {
			document.getElementById("Test").innerHTML = response;
		});
	}
	
	
	function updateATabelle (ids){
		var data = {'action' : 'va_auto_ops',
					'type' : 'updateAT',
					'id_stimulus' : ids,
		};
		jQuery.post(ajaxurl, data, function (response) {
			jQuery('#atabelle').html(response);
		});
		
	}
	jQuery("document").ready(function (){
		jQuery("#id_stimFeld").val("");
		vorschau = jQuery("#vorschauID").prop("checked");
	});

</script>

<h1>Automatische Datenbankoperationen</h1>

<br />
<br />
<br />
<br />

<input class="button button-primary" type="button" value="Äußerungen tokenisieren" onClick="tokenize()"/>
Id_Stimulus <input id="id_stimFeld" type="text" onChange="updateATabelle(this.value);"/>
<input type="radio" name="modus" checked onChange="vorschau = !this.checked;"> Datenbank
<input type="radio" name="modus" id="vorschauID" onChange="vorschau = this.checked;"> Vorschau

<br />
<br />

<div id="atabelle">
	<?php echo anzahlAeusserungen(''); ?>
</div>


<br />
<br />



<div id="Test">

</div>

<?php
}

function kml_transform () {
?>

<script type="text/javascript">
	function kml (){
		var data = {'action' : 'va_auto_ops',
					'type' : 'kml',
					'sql' : jQuery("#sqlArea").val(),
		};
		jQuery.post(ajaxurl, data, function (response) {
			document.getElementById("Test").innerHTML = "<textarea rows='40' cols='500'>" + response + "</textarea>";
		});
	}
</script>

<br />
<br />

<input class="button button-primary" type="button" value="Punktliste -> KML" onClick="kml ()"/>

<br />
<br />
<textarea rows="15" cols="100" id="sqlArea">
SELECT CONCAT(X(Georeferenz), ',', Y(Georeferenz)), Nummer, Ortsname 
FROM Informanten WHERE Erhebung = 'AIS'
</textarea>
<br />
<br />

<div id="Test">

</div>

<?php
}
?>