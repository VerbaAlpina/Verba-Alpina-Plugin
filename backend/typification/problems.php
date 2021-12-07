<?php 
function lex_problems (){
    global $va_xxx;
    ?>
    
    <script type="text/javascript">
	var DATA = {"dbname": "va_xxx"};
    
	function callbackProblemInfo (response){
		if (response === "Solved"){
			jQuery("#problemDetails").html("");
			jQuery("#stimulusSelect").trigger("change"); //reload token list
			scroll(0,0);
		}
		else {
    		jQuery("#problemDetails").html(response);
    		jQuery("#problemDetails #reactionDiv").tabs({
    			active: 0,
    			activate: function(event, ui) {
    				select2ForMorph(ui.newPanel);
    			},
    			create : function (event, ui){
    				console.log(ui);
    				select2ForMorph(ui.panel);
    			},
    			beforeActivate: function (event, ui){
    				jQuery(ui.oldPanel).find(".selectExisting").select2("destroy");
    			}
    		});
    
    
    		jQuery("#commentAddButton").click(function (){
    
    			if (!jQuery("#comment #problemComment").val()){
    				alert("Bitte Kommentar eingeben!");
    				return;
    			}
    			
    			var refs = [];
    			
    			jQuery("#comment #problemRefTable tr").each(function (){
    				let newRef = {"id" : jQuery(this).find("td:first select").val(), "text" : jQuery(this).find("td:nth-child(2) input").val()};
    				refs.push(newRef);
    			});
    			
    			jQuery.post(ajaxurl, {
    				"action" : "va",
    				"namespace" : "typification",
    				"query" : "add_problem_comment",
    				"id_problem" : jQuery("#recordSelect").val(),
    				"comment" : jQuery("#comment #problemComment").val(),
    				"id_type" : jQuery("#comment .selectExisting").val(),
    				"type_text" : jQuery("#comment #problemNewType").val(),
    				"refs" : refs
    			}, callbackProblemInfo);
    		});
		}
	}
    
	jQuery(function (){
		jQuery("#stimulusSelect").change(function (){
			if (jQuery(this).val()){
    			jQuery.post(ajaxurl, {
    				"action": "va",
    				"namespace": "typification",
    				"query" : "getProblemList",
    				"id_stimulus" : jQuery(this).val()
    			}, function (response){
					jQuery("#recordSelect").html(response);
					jQuery("#recordSelectSpan").toggle(true);
    			});
			}
			else {
				jQuery("#recordSelectSpan").toggle(false);
			}
			jQuery("#problemDetails").html("");
		});

		jQuery("#recordSelect").change(function (){
			if (jQuery(this).val()){
    			jQuery.post(ajaxurl, {
    				"action": "va",
    				"namespace": "typification",
    				"query" : "getProblemDetails",
    				"id_problem" : jQuery(this).val()
    			}, callbackProblemInfo);
			}
			else {
				jQuery("#problemDetails").html("");
			}
		});

		jQuery(document).on("click", ".problemRefButton", function (){
			let newRow = jQuery("<tr><td><select style='min-width: 200px;'></select></td><td><input type='text' style='min-width: 200px;' /></td><td><span style='cursor: pointer;' class='problemRemoveRef dashicons dashicons-no-alt'></span></td></tr>");
			jQuery("#problemRefTable").append(newRow);
			newRow.find("select").select2({
				"ajax" : {
					"type" : "POST",
					"url" : ajaxurl,
					"dataType": "json",
					"data" : function (params){
						return {
							"action" : "va",
							"namespace" : "typification",
							"query" : "getReferencesMorph",
							"search" : params.term
						};
					},
					"processResults": function (data){
						return {"results": data};
					},
					"delay": 250
				},
				"minimumInputLength" : 2
			});
		});

		jQuery(document).on("click", ".problemRemoveRef", function (){
			jQuery(this).closest("tr").find("select").select2("destroy");
			jQuery(this).closest("tr").remove();
		});

		addListenersForCreateLexType();

		jQuery(document).on("change", "#resolveTypeProp", function (){
			jQuery("#resolve .selectExisting").val("").trigger("change.select2");
			jQuery("#resolve #onlyOrth").val("");
		});

		jQuery(document).on("change", "#resolve .selectExisting", function (){
			jQuery("#resolveTypeProp").val("");
			jQuery("#resolve #onlyOrth").val("");
		});

		jQuery(document).on("input", "#resolve #onlyOrth", function (){
			jQuery("#resolve .selectExisting").val("").trigger("change.select2");
			jQuery("#resolveTypeProp").val("");
		});

		jQuery(document).on("click", "#problemResolveButton", function (){
			var id_type = null;
			var type_text = null;
			
			if (jQuery("#resolveTypeProp").val()){
				id_type = jQuery("#resolveTypeProp").val();
				if (id_type == -1){
					id_type = null;
					type_text = jQuery("#resolveTypeProp option:selected").text();
				}
			}
			else if (jQuery("#resolve .selectExisting").val()){
				id_type = jQuery("#resolve .selectExisting").val();
			}
			else {
				type_text = jQuery("#onlyOrth").val();
			}

			if (!id_type && !type_text){
				alert("Typ angeben!");
				return;
			}
			
			jQuery.post(ajaxurl, {
				"action" : "va",
				"namespace" : "typification",
				"query" : "resolve_problem",
				"id_problem" : jQuery("#recordSelect").val(),
				"comment" : jQuery("#resolveComment").val(),
				"id_type" : id_type,
				"type_text" : type_text
			}, callbackProblemInfo);
		});
	});

	function select2ForMorph (container){
		jQuery(container).find(".selectExisting").val("").select2({
			ajax: {
				"url": ajaxurl,
				"type" : "POST",
				"dataType": "json",
				"data": function (params) {
					var query = {
						"action" : "va",
						"namespace" : "util",
						"query" : "getMorphTypesForSelect",
						"search": params.term,
						"page": params.page || 1
					}
		
		      		return query;
				}
			}
		});
	}

	function saveMorphType (){
		var data = getMorphTypeData();
		
		if(data.type.Orth == ""){
			alert(TRANSLATIONS.ERROR_ORTH);
			return;
		}

		jQuery.post(ajaxurl, data, function (response){
			try {
				if(response.startsWith("Fehler")){
					alert(response);
					return;
				}
				
				var typeInfo = JSON.parse(response);
				closeMorphDialog();
			}
			catch (e) {
				alert(e + "(" + response + ")");
			}
		});
	}
	</script>
    
    <br /><br />
    <h1>Probleme Typisierung</h1>
    <br /><br />
	
	<?php
	$slist = $va_xxx->get_results("
        SELECT DISTINCT Id_Stimulus, CONCAT(Erhebung, ': ', Karte, '_', Nummer, ': ', REPLACE(Stimulus, '\"', '')) as TStimulus 
        FROM tprobleme JOIN stimuli USING (Id_Stimulus)
        WHERE Geloest < 2", ARRAY_A);
    
	if ($slist){
		echo 'Stimulus: <select id="stimulusSelect" autocomplete="off"><option value=""></option>';
    
		foreach ($slist as $stimulus){
			echo '<option value="' . $stimulus['Id_Stimulus'] . '">' . $stimulus['TStimulus'] . '</option>';
		}
		
		echo '</select>';
	}
	else {
		echo 'Aktuell keine Probleme.';
	}
    ?>
    <span id="recordSelectSpan" style="display: none;">
    	Beleg: <select id="recordSelect" style="font-family: doulosSIL;"></select>
    	<?php echo va_get_info_symbol('Unbearbeitete Probleme sind weiß hinterlegt, bereits kommentierte gelb. Probleme, die als gelöst markiert wurden, bei denen die eigentliche Typisierung aber noch fehlt, sind grün markiert.');?>
    </span>
    
    <br />
    <br />
    
    <div id="problemDetails"></div>
    <?php
    
    createTypeOverlay($va_xxx, null);
}