<?php
function va_original_page (){
    ?>
    
    <script type="text/javascript">
	jQuery(function (){
		
		changeSelect();
		
		jQuery("#typeSel").change(changeSelect);
		
		jQuery("#comp_orig").click(function (){
			
			if (jQuery("#typeSel").val() == "db"){
				jQuery.post(ajaxurl, {
					"action" : "va",
					"namespace" : "original",
					"query" : "compute"
				}, handleResponse);
			}
			else {
				jQuery.post(ajaxurl, {
					"action" : "va",
					"namespace" : "original",
					"query" : "computeText",
					"records": jQuery("#input_area").val().split("\n")
				}, handleResponse);
			}
		});
	});
	
	function changeSelect (){
		if (jQuery("#typeSel").val() == "db"){
			jQuery("#input_area").toggle(false);
		}
		else {
			jQuery("#input_area").toggle(true);
		}
	}
	
	function handleResponse (response){
		let data = JSON.parse(response);
		let html = "<table style='border: 1px solid black;'>";
		for (let i = 0; i < data[0].length; i++){
			html += "<tr><td style='border: 1px solid black;'>" + data[0][i][0] + "</td><td style='min-width: 50px; border: 1px solid black;'>" + data[0][i][1] + "</td></tr>";
		}
		html += "</table><br /><br />";
		
		for (let i = 0; i < data[1].length; i++){
			html += data[1][i] + "<br />";
		}

		jQuery("#oresult").html(html);
	}
	</script>
    
    <h1>Beta -> Original</h1>
    
	<select id="typeSel">
		<option value="db">In Datenbank</option>
		<option value="text">Textfeld</option>
	</select>
    <input type="button" class="button button-primary" id="comp_orig" value="Originaltranskription berechnen">
	
	<br />
	
	<textarea style="width: 800px; height: 400px;" id="input_area"></textarea>
    
    <br /><br />
    
    <div id="oresult" style="width: 40%"></div>
    <?php
}