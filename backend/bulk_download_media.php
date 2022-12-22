<?php

 function va_bulk_download_media (){

	?>

	<script type="text/javascript">

	jQuery(function () {

	  jQuery('#download_start').on('click',function(){

	  	var url_list = jQuery('#text_area_list').val();
	  	var split_list = url_list.split(/\r?\n/);

	  	 for(key in split_list){
	  	 	 var list_entry = split_list[key];
	  	 	 var list_entry_split = list_entry.split(" ");
	  	 	 split_list[key]=list_entry_split[0];

	  	 	 if(split_list[key].length==0)delete split_list[key];
	  	 }

	  	 split_list = split_list.filter((a) => a);

	  	 var url_string = "";

	  	 for(key in split_list){

	  	 	url_string+= split_list[key];
	  	 	url_string+= "\n";
	  
	  	 }

	  	 var num = split_list.length;


   		jQuery.post(ajax_object.ajaxurl, {
					"action": 'va', 
					"namespace" : "bulk_download",
					"query" : url_string
				}, function (response){

					jQuery('#text_area_list').val("Versuchte "+num+" Bilder einzufügen und zu verknüpfen.");

				   	setTimeout(function() {
				   		jQuery('#text_area_list').val("");
				   	}, 2000);
		});




		})



	})

	</script>	

	<?php

	$html = '
	<h2> Eingefügte Links werden in die Mediathek heruntergeladen: </h2>
	<textarea placeholder=" URL und id in diesem Schema:&#10 1;https://th.bing.com/th/id/R.01edd89649de4a6f8389a36fdb86d3ca?rik=OF3v9qNwvv4F9Q&pid=ImgRaw&r=0 (03.06.2022)&#10 oder: &#10 1;https://th.bing.com/th/id/R.01edd89649de4a6f8389a36fdb86d3ca?rik=OF3v9qNwvv4F9Q&pid=ImgRaw&r=0" id="text_area_list" style="min-height:350px; margin-top:10px; margin-bottom:10px; width:85%;"></textarea> 
	<div><button id="download_start">Download</button></div>';	


	echo $html;

  }




?>