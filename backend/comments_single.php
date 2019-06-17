<?php
function va_single_comments_page (){
	
	?>
	<script type="text/javascript">
	var changed = false;
	var currentId = null;
	var currentText = null;
	
	jQuery(function (){
		jQuery("#comment_field").val("");
		
		jQuery("#location_select").select2({
		  ajax: {
		    url: ajaxurl,
		    dataType: "json",
		    data: function (params){
			    return {
					action: "va",
					namespace: "util",
					query: "search_locations",
					search: params.term
			    };
		    },
		    placeholder: "Ort suchen",
		    delay: 300,
		    method: "POST",
		    processResults: function (data, params) {
				var results_by_categories = {}; 
	
				// group results by categories
				for(var key in data['results']){
			    	var item = data['results'][key];
					var desc = item['description'];
					if(!results_by_categories[desc]){
			     		results_by_categories[desc] = [];
			  		}
	
					results_by_categories[desc].push(item);
			      }
	
			     var res_objs = [];
	
			      for(var key in results_by_categories){
			      	  var res_obj = {};
			      	  var obj = results_by_categories[key];
			      	  res_obj['text'] = key;
			      	  res_obj['children'] = obj;
			      	  res_objs.push(res_obj);
			      }
	
			      return {
			        "results": res_objs
			      };
			    }
			  },
			  minimumInputLength: 3
		});

		jQuery("#location_select").on('select2:select', function (e) {
			if (!currentId || !changed || confirm("Die Änderungen am aktuellen Eintrag wurden noch nicht gespeichert! Fortsetzen?")){
				var data = e['params']['data'];
				currentId = data["id"];	
				currentText = data["text"];		
				jQuery.post(ajaxurl, {
					action: "va",
					namespace: "util",
					query: "get_location_description",
					id: currentId
				}, function (response){
					jQuery("#comment_field").val(response);
					changed = false;
				});
			}
			else {
				var newOption = new Option(currentText, currentId, true, true);
				jQuery('#location_select').append(newOption).trigger('change');
			}
		});

		jQuery("#comment_field").change(function (){
			changed = true;
		});

		jQuery(window).on('beforeunload', function(){
			if(changed)
				return "Die Änderungen wurden noch nicht in die Datenbank übertragen!";
		 });

		 jQuery("#saveButton").click(function (){
			 jQuery.post(ajaxurl, {
				action: "va",
				namespace: "util",
				query: "save_location_description",
				id: currentId,
				content: jQuery("#comment_field").val()
			}, function (response){
				if (response == "success"){
					changed = false;
					alert("Eintrag gespeichert!");
				}
			});
		 });
	});
	</script>
	
	
	<br />
	<select id="location_select" style="width: 400px;"></select>
	
	<br /><br />
	
	<textarea rows=15 cols=100 id="comment_field"></textarea>
	<br />
	<input type="button" id="saveButton" class="button button-primary" value="Speichern" />
	
	<?php 
}