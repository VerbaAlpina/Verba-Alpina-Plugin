<?php
function kml_transform () {
?>

<script type="text/javascript">
	function kml (){
		var data = {'action' : 'va',
					'namespace' : 'kml',
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