<?php
function va_version_gallery(){
	
	global $vadb;
	global $lang;
	global $Ue;
	global $admin;
	global $va_mitarbeiter;
	global $va_current_db_name;


?>
	
<script src="https://d3js.org/d3.v4.js"></script>
	
<script type="text/javascript">

	jQuery(document).ready(function () {

			 var loading = false;


			jQuery('body').addClass('va_version_gallery');
			var res  = getPreviousVAVersions(ajax_object.max_db);

			for(var i=0;i<res.length;i++){
				
					var temp = getFlipTemplate();
					if (res[i].first == 15){
						var url = "<?php echo get_site_url(1);?>/wp-content/uploads/DSC_02793.jpg";
					}
					else {
						var url =  "<?php echo get_site_url(1);?>/wp-content/uploads/"+res[i].first+"_"+res[i].last+"_titel.jpg"; 
					}
					var img = jQuery('<div class="img_container"></div>').css("background-image",'url("'+url+'")');
					temp.find('.b_content').append('<div class="b_content_img"></div>');
					temp.find('.b_content_img').css("background-image",'url("'+url+'")');
					temp.find('.content').append(img);
					if (res[i].first == 15){
						var vtext = "<?php echo $Ue['VOR_ERSTER_VERSION'];?>";
					}
					else {
						var vtext = "<?php echo $Ue['VERSION'];?> " + res[i].first+'/'+res[i].last;
					}
					temp.find('.content').append('<div class="version_div">'+vtext+'</div>');
					jQuery('.va_versions').append(temp)
					temp.height(200);

				   if(res[i].last==1 && res[i].first != 15){
					  	jQuery('.va_versions').append('<div class="year">20'+res[i].first+'<span class="line"></span></div>');  		
				    }
				   else if(res[i].last==2){
				   		jQuery('.va_versions').append('<div class="year"><span class="line inset"></span></div>');  
				   }

				   temp.attr('version',res[i].first.toString()+res[i].last.toString());

				   var ue_array = <?php echo json_encode($Ue); ?>;
				   var img_desc_text = "VA_TITELBILD_"+res[i].first+res[i].last;
				   var current_desc = ue_array[img_desc_text];

			
				   var infobutton_img = jQuery('<div class="info_div langbtn"><img src="<?php echo $url?>/wp-content/themes/verba-alpina/images/VA_Infobutton.png"></img></div>');
				   var imgbutton_img = jQuery('<div class="info_div langbtn pic"> <a href="'+url+'"><img src="<?php echo $url?>/wp-content/themes/verba-alpina/images/VA_Imagebutton.png"></img></a></div>');

				
				   temp.find('.content .img_container').append(infobutton_img);
				   temp.find('.content .img_container').append(imgbutton_img);

					infobutton_img.qtip({
						content : {
							text : current_desc
						},
						style : {
							classes : 'qtip-blue'
						}
					});
				
			}

			jQuery('.lex_article').each(function(i,el){


					var that = jQuery(this);
					jQuery(this).flip({axis: 'x', trigger: 'manual', speed:450});	
					that.find('.back').show();

					var el = that[0];
					var f_cont = that.find('.f_content');
					var el_cont = f_cont[0];
					var front  = that.find('.front');

					var version = jQuery(this).attr('version');
				

					var total_height = el.offsetHeight;

					that.height(total_height);
					front.height(total_height);

					that.attr('original_height',total_height);
					that.css('max-height','initial');
					that.css('max-height','initial');
					front.css('max-height','initial');


					jQuery(this).find('.coverImageVersion,.flipbutton').on('click',function(){
									
					if(loading) return;				

		  	 	  	that.toggleClass('flipped');
				  //  that.addClass('toggle_scale');
				  //  setTimeout(function() {that.removeClass('toggle_scale')}, 200);	

				  	 that.find('.flipbutton').hide();

					 

						  that.one('flip:done',function(){

						  		setTimeout(function() {
						  			that.find('.flipbutton').fadeIn('fast');	
								  	  if(that.hasClass('flipped')){
							
										  	 	that.find('.flipbutton').find('.text').text('<?php echo $Ue['LEX_BACK']; ?>');
										  	 	that.find('.flipbutton').find('i').removeClass('fa-info').addClass('fa-angle-left');
										}

									  	 else{

								  	 		that.find('.flipbutton').find('.text').text('<?php echo $Ue['TIMELINE_DETAILS']; ?>'); 
								  	 		that.find('.flipbutton').find('i').removeClass('fa-angle-left').addClass('fa-info');		
									  	 }
						  		}, 75);


						  });


					  	  if(that.hasClass('flipped')){
					  	  						
					  	  			if(that.find('.back_body .hiddenbackcontent').length>0 && !loading){
				  	  						// only get content if not already appended (check by div "hiddenbackcontent")
				  	  						loading = true;

				  	  						var loadingcover =jQuery(getLoadingCover());

				  	  						that.find('.front').append(loadingcover);
				  	  						loadingcover.show();

							  	  			getBackContent(that,version, i,function(){

								  	  		
										  		that.attr('back_height',that.find('.b_content').height()+20);
										  		that.height(parseInt(that.attr('back_height')));
										  	 	loading = false;
										  	 	loadingcover.remove();
									  	 		that.flip('toggle');
									  	 		addPopups(that);
							  	  			});

					  	  			}

					  	  			else{
					  	  				
				  	  					
								  	 	that.attr('back_height',that.find('.b_content').height()+20);
								  	 	that.height(parseInt(that.attr('back_height')));
								  	 	that.flip('toggle');

					  	  			}


							}

						  	 else{
						  	 	setTimeout(function(){
						  	 		 that.flip('toggle');
						  	 		},250)
						  	 	that.height(parseInt(that.attr('original_height'))); 




						  	 }



					})


			})

			setTimeout(function(){
				jQuery(window).scrollTop(0)
			},200)
			

	jQuery(document).on("scroll", function() {

	   var scrollTop = Math.round(jQuery(document).scrollTop())
	   jQuery('.va_version_headline').css('opacity',(100-scrollTop)/100)

		if(scrollTop>50) {
			jQuery('.va_version_headline').addClass('fadeOut')
		}
		else{
			jQuery('.va_version_headline').removeClass('fadeOut')
		}

	});

	}) //ready



	function getBackContent(that, version, index, callback){

	        getVaVersionText(version,function(res){


		  	var div = that.find('.back_body');
			   	if(res["text"]){
		   		div.html(res["text"]);
		   	}
		   	else{
	   			div.html('<?php echo $Ue['TIMELINE_ARTICLE_NOT_DONE'];?><br /><br />')			
		   	}

		  var charts = jQuery('<div class="charts"></div>');
		  div.append(charts); 	

			  charts.before("<h2 class='quant_headline'><?php echo $Ue['TIMELINE_QUANT'];?>:</h2>");
			  charts.append("<div class='chart_container'><h4><?php echo $Ue['NEW_RECORDS'];?></h4><div class='timeline_chart' id='instance_chart_" + index + "'></div></div>");
		  charts.append("<div class='chart_container'><h4><?php echo $Ue['NEW_MORPH_TYPES'];?></h4><div class='timeline_chart' id='type_chart_" + index + "'></div></div>");
		  charts.append("<div class='chart_container'><h4><?php echo $Ue['NEW_CONCEPTS'];?></h4><div class='timeline_chart' id='concept_chart_" + index + "'></div>");
			  
		      barChart(res["data"]["instances"], "#instance_chart_" + index, 300,75);
		      barChart(res["data"]["types"], "#type_chart_" + index, 150,30);
		      barChart(res["data"]["concepts"], "#concept_chart_" + index, 300,120);
		      

		      var images = div.find('img');
		      var image_count = 0;
   			  var img_num = images.length;
   			  if(img_num>0){

   			  images.each(function(){
	   			  	jQuery(this).on('load',function(){
	   			  		image_count++;
	   			  		if(image_count==img_num){
	   			  			callback();
	   			  		}
	   			  	})
   			  })

   			  }

   			  else{
   			  	 callback();
   			  }

		 
		   	//TODO GET HTML IN

		   
	   })


}

	function getVaVersionText(version, callback){


		    var data = {
            "action" : "va",
            "namespace" : "va_versions",
            "query" : "get_va_version_text",
            "version" : version,
			"db" : ajax_object.db
   		 };
   
       
        jQuery.post(ajax_object.ajaxurl, data, function (response){

        	 var res = JSON.parse(response)
        	 callback(res);

        })


	}
		


		function getFlipTemplate(){

			return jQuery('<div class="lex_article" ><div class="flipbutton arrow type_L"> <i class="fas fa-info"></i> <span class="text">Details</span> <span class="arrow"></span> </div><div class="front"><div class="f_content"><div class="head"><div class="lex_button_container"><a class="lex_close lex_edit" title="Schließen"><button class="actionbtn"><i class="fas fa-times"></i></button></a></div><div class="lex_head_container"></div></div><div class="content"><div class="coverImageVersion"></div><div class="lexArticleVersionImage"></div></div></div></div><div class="back"><div class="b_content"><div class="head"><div class="lex_button_container bback"><a class="lex_close lex_edit" title="Schließen"><button class="actionbtn"><i class="fas fa-times"></i></button></a></div><div class="lex_head_container"></div></div><div class="back_body"><div class="hiddenbackcontent"></div></div></div></div></div></div>');

		}


		function addLexButtons(){
			return '<a class="lex_edit" title="Auf Karte visualisieren" target="_BLANK" href="http://localhost/VA/karte/?db=xxx&amp;single=B2304"><button class="actionbtn"><i class="fas fa-map-marked-alt"></i></button></a><span class="sep"></span><a class="lex_edit" title="Bearbeiten" target="_BLANK" href="http://localhost/VA/wp-admin/admin.php?page=edit_comments&amp;comment_id=B2304"><button class="actionbtn"><i class="fas fa-pencil-alt"></i></button></a><span class="sep"></span><a class="lex_edit"><button class="actionbtn"><i class="fas fa-link"></i></button></a><span class="sep"></span>'
		}


		function getLoadingCover(){

			return'<div class="lex_main_load_cover gallery"><div class="spinnerarea"><div class="sk-fading-circle"><div class="sk-circle1 sk-circle"></div><div class="sk-circle2 sk-circle"></div><div class="sk-circle3 sk-circle"></div><div class="sk-circle4 sk-circle"></div><div class="sk-circle5 sk-circle"></div><div class="sk-circle6 sk-circle"></div><div class="sk-circle7 sk-circle"></div><div class="sk-circle8 sk-circle"></div><div class="sk-circle9 sk-circle"></div><div class="sk-circle10 sk-circle"></div><div class="sk-circle11 sk-circle"></div><div class="sk-circle12 sk-circle"></div></div></div></div>'
		}


		function getPreviousVAVersions(max_version){
		
			var res = [];
			var last =  parseInt(max_version.substr(max_version.length - 1)); 
			var first = parseInt(max_version.substring(0, max_version.length - 1));
			max_version = {first:first,last:last};
			res.push(max_version);

			var prev_ver = getPreviousVAVersion(max_version)
			while(!(prev_ver.first==16 && prev_ver.last==1)){
				max_version = getPreviousVAVersion(max_version);
				res.push(max_version);
				prev_ver = max_version;
			}
			
			res.push({first:15,last:1});

			return res;
		}


		function getPreviousVAVersion(version){
				var prev_last = version.last;
				var prev_first = version.first;

					if(version.last==2){
					prev_last=1;
					}
					else{
					prev_last=2;
					prev_first = version.first -1;
					}
		
				return {first: prev_first, last:prev_last};
		}

		function barChart (data, div, height,left){
			var max_val = 0;
			for (let i = 0; i < data.length; i++){
				if (data[i][1] * 1 > max_val){
					max_val = data[i][1];
				}
			}

			var margin = {top: 20, right: 50, bottom: 40, left: left},
		    width = 280 - margin.left - margin.right;
		    height = height - margin.top - margin.bottom;

			var svg = d3.select(div)
    		  .append("svg")
    		    .attr("width", width + margin.left + margin.right)
    		    .attr("height", height + margin.top + margin.bottom)
    		  .append("g")
    		    .attr("transform",
    		          "translate(" + margin.left + "," + margin.top + ")");

			var color = d3.scaleOrdinal()
		    	.range(["#8dd3c7","#ffffb3","#bebada","#fb8072","#80b1d3","#fdb462","#b3de69","#fccde5","#d9d9d9","#bc80bd","#ccebc5","#ffed6f"]);

			  // Add X axis
			  var x = d3.scaleLinear()
			    .domain([0, max_val])
			    .range([ 0, width]);
// 			  svg.append("g")
// 			    .attr("transform", "translate(0," + height + ")")
// 			    .call(d3.axisBottom(x))
// 			    .selectAll("text")
// 			      .attr("transform", "translate(-10,0)rotate(-45)")
// 			      .style("text-anchor", "end");

			  // Y axis
			  var y = d3.scaleBand()
			    .range([ 0, height ])
			    .domain(data.map(function(d) { return d[0]; }))
			    .padding(.1);

			  var legend = svg.append("g")
			    .call(d3.axisLeft(y));
			    
			   legend.selectAll("text")
			    	.style("fill", "white")
			    	
			    legend.selectAll("line")
			    	.style("stroke", "white")
			    	
				legend.selectAll("path")
			    	.style("stroke", "white")

			  //Bars
			  var bars = svg.selectAll("myRect")
			    .data(data)
			    .enter()
			    .append("g")
			    
			  bars.append("rect")
			    	.attr("x", x(0) + 1 )
			    	.attr("y", function(d) { return y(d[0]); })
			    	.attr("width", function(d) { return x(d[1]); })
			    	.attr("height", y.bandwidth() )
			    	.attr("fill", (d, i) => color(i))

			   bars.append("text")
			    	.attr("x", function(d) { return x(d[1]) + 5; })
			    	.attr("y", function(d) { return y(d[0]) + 0.65 * y.bandwidth(); })
			    	.text((d) => d[1])
			    	.attr("fill", "white")
			    	.attr("text-anchor", "left")
			    	.attr("font-size" , "10px")

			    	
		}




function addPopups (div){
	addBiblioQTips(div);
}


	</script>
	
	<?php 
	echo '<div class="va_version_container">';
		echo '<div class="va_version_headline">';
		echo '<img class="lexlogo" src="' . VA_PLUGIN_URL . '/images/timeline.svg"/>';
		echo '</div>';

	  echo '<div class="va_versions">';

		echo '</div>';	
	echo '</div>';	
}
?>