<?php 


function va_live_graph_page (){

?>


<style>

.node {
  cursor: pointer;
}

.node:not(.node--n_root):hover {
/*  stroke: #000;
  stroke-width: 3px;*/

  fill-opacity:0.93;
}

.node--leaf {
  fill: white;
}

.node--leaf:hover {
  fill-opacity:0.93;
}

.label {
  font: "Helvetica Neue", Helvetica, Arial, sans-serif;
  text-anchor: middle;
  fill: #fff;
 /* text-shadow: 0 1px 0 #ccc, 1px 0 0 #ccc, -1px 0 0 #ccc, 0 -1px 0 #ccc;*/
}

#page{
	padding: 0;
}

#primary{
	margin-top: 0;
}

.entry-title > span:not(.cur_date):before{
	font-family:Font Awesome\ 5 Free;
	font-style: normal;
	font-weight: 900;
	content: "\f200  ";
}

.entry-header{
	position: absolute;
    z-index: 1;
    background: white;
    padding: 5px;
    padding-left: 28px;
    padding-top: 29px;
    padding-right: 10px;
    border-bottom-right-radius: 4px;
    
}

/*.entry-title{
	font-family: "Open Sans", Arial, sans-serif;
}
*/
.text_node{
	font-size: 22px;
}

.text_leaf{
	font-size: 10px;
}


.label,
.node--root,
.node--leaf {
  pointer-events: none;
}

#graphdiv{
	text-align: center;

	position: relative;
}

.cur_date{
    font-size: 10px;
    padding-left: 15px;
}

@media screen and (min-width: 960px) {
	#graphdiv{
	    top: -100px;
	}
}

@media screen and (max-width: 470px) {
	.entry-title{
	   margin-top: 12px;
	}
}

#fusszeile{
    padding-right: 20px;
}

</style>

<?php

	global $va_xxx;
	global $Ue;


// L_GRAPH_HEAD

		$partner_data = $va_xxx->get_results('SELECT DISTINCT Name as name, "partner" as type FROM `kooperationspartner` WHERE Status = "fix"');
		$quellen_data = $va_xxx->get_results('SELECT `Erhebung` as name, "quellen" as type, COUNT(*) as size FROM `stimuli` JOIN tokens ON stimuli.ID_Stimulus=tokens.ID_Stimulus GROUP BY `Erhebung`');
		$lang_data    = $va_xxx->get_results('SELECT Sprache as name, "lang_data" as type, count(*) as size FROM vtbl_token_morph_typ JOIN morph_typen on vtbl_token_morph_typ.Id_morph_Typ = morph_typen.ID_morph_Typ AND morph_typen.Quelle = "VA" WHERE morph_typen.ID_morph_Typ != 4144 GROUP BY Sprache');

		foreach ($partner_data as &$value) {
		     $value->size = 500;
		}

		foreach ($lang_data as &$value) {
			if($value->name == "ger")$value->name = $Ue['L_GRAPH_GERMANISCH'];  
			if($value->name == "rom")$value->name = $Ue['L_GRAPH_ROMANISCH']; 
			if($value->name == "sla")$value->name = $Ue['L_GRAPH_SLAWISCH']; 
		}


		$p_obj = new stdClass();
		$p_obj -> type = "partner";
		$p_obj -> name = $Ue['KOOPERATIONSPARTNER'];
		$p_obj -> children = $partner_data;

		$q_obj = new stdClass();
		$q_obj -> type = "quellen";
		$q_obj -> name = $Ue['L_GRAPH_SOURCES'];
		$q_obj -> children = $quellen_data;

		$l_obj = new stdClass();
		$l_obj -> type = "lang_data";
		$l_obj -> name =  $Ue['L_GRAPH_LANG_DATA'];
		$l_obj -> children = $lang_data;

		$result = [
		"name" => "va_data",
		"children" => [
			$p_obj,
			$q_obj,
			$l_obj
		]

		];


	
	wp_localize_script('toolsSkript', 'LIVE_DATA', 	$result);

?>


<div id="graphdiv">
<svg id="graph"></svg>
</div>

<script src="https://d3js.org/d3.v4.js"></script>

<script type="text/javascript">


var window_width;


	jQuery(function (){
		window_width = window.innerWidth;
		createCirclePacking();

			var currentdate = new Date(); 
			var datetime = currentdate.getDate() + "/"
			+ (currentdate.getMonth()+1)  + "/" 
			+ currentdate.getFullYear() + " | "  
			+ currentdate.getHours() + ":"  
			+ currentdate.getMinutes() + ":" 
			+ currentdate.getSeconds();

			var date_div = jQuery('<span class="cur_date">'+datetime+'</span>');

			jQuery(".entry-title").append(date_div);

	// .insertAfter(jQuery(".entry-title"));


		jQuery(window).on('resize',function(){
			var svg = jQuery('<svg id="graph"></svg>');
			jQuery('#graphdiv').empty().append(svg);
			createCirclePacking();
			window_width = window.innerWidth;
			jQuery('#graphdiv').css('top','');	
		})
	})


	function createCirclePacking(){

		var prevent_click = false;

		jQuery('.entry-header').show();

	    var svg_j = jQuery('#graphdiv').find('svg')[0];

		var width = jQuery('#graphdiv').width();
		svg_j.setAttribute('width', width);
		svg_j.setAttribute('height', width);

		var svg = d3.select("#graph");

		var margin = 20,
		    diameter = +svg.attr("width"),
		    g = svg.append("g").attr("transform", "translate(" + diameter / 2 + "," + diameter / 2 + ")");

		// var color = d3.scaleLinear()
		//     .domain([-1, 5])
		//     .range(["hsl(152,80%,80%)", "hsl(228,30%,40%)"])
		//     .interpolate(d3.interpolateHcl);

		var color = d3.scaleSequential(d3.interpolateMagma)
		.domain([-4, 4]);

		var pack = d3.pack()
		    .size([diameter - margin, diameter - margin])
		    .padding(5);

		  var n_root = d3.hierarchy(LIVE_DATA)
		      .sum(function(d) { return d.size; })
		      .sort(function(a, b) { return b.value - a.value; });

		  var focus = n_root,
		      nodes = pack(n_root).descendants(),
		      view;


		  var circle = g.selectAll("circle")
		    .data(nodes)
		    .enter().append("circle")

		      .attr("class", function(d) { return d.parent ? d.children ? "node" : "node node--leaf" : "node node--n_root"; })
		       .style("fill", function(d) {return getColor(d);})
	        // .style("fill", function(d) { return d.children ? color(d.depth) : null; })
		      .on("click", function(d) { if (focus !== d && !prevent_click) zoom(d), d3.event.stopPropagation(); });


		  var text = g.selectAll("text")
		    .data(nodes)
		    .enter().append("text").each(function(d) {

		       var stext = d3.select(this);
       	      stext.attr("class", function(d) { return d.parent ? d.children ? "text_node label" : "text_leaf label" : "text_root label";})
		      stext.style("fill-opacity", function(d) { return d.parent === n_root ? 1 : 0; })
		      stext.style("display", function(d) { return d.parent === n_root ? "inline" : "none"; })
		      // stext.attr('dy',"0.3em");

		       var string = d.data.name;
		       // if(d.data.size && d.data.size != 500.1024)string+=" "+d.data.size +"(Token)";
		       // var parts = string.split(" "); 

		       var parts;
       	       var i=0.2;

		       if(d.data.type != "partner"){

   		       parts = [string];
		       if(d.data.size)parts.push(d.data.size);

		  	 }else{

		  	 	var all_parts = string.split(" ");

		  	 	for(var j = 0; j<all_parts.length;j++){
		  	 		if(all_parts[j].length>d.r){

		  	 			var str1 = all_parts[j].substring(0, d.r-1);
		  	 			str1 = str1.replace(/-\s*$/, "");
		  	 			str1 += "-";

		  	 			var str2 = all_parts[j].substring(d.r-1,all_parts[j].length);
		  	 			all_parts[j] = str1;
		  	 			if(all_parts[j+1]!=undefined){
		  	 			all_parts[j+1] = str2 + " " + all_parts[j+1];
		  	 			}
		  	 			else{
		  	 				all_parts.push(str2);
		  	 			}

		  	 		}

		  	 	}


		  	 	var first_part = "";
		  	 	var k=0;	

		  	 	for(var j = 0; j<all_parts.length;j++){
		  	 		if((first_part + all_parts[k]).length<d.r){
		  	 		first_part+= all_parts[k] + " ";
		  	 		k++;
		  	 		}
		  	 		else break;
		  	 	}

		  	 	parts = [first_part];

		  	 	if(string.length>d.r){ 
	  	 		var second_part = "";	
	  	 	 	 	for(var j =0; j<all_parts.length;j++){
			  	 		if((second_part + all_parts[k]).length<d.r){
			  	 		second_part+= all_parts[k] + " ";
			  	 		k++;
			  	 		}
			  	 		else break;
			  	 	}
	  	 			i= 0.0;
	  	 		 parts.push(second_part);
		  	    }


		  	 	if(string.length>d.r*2){ 
	  	 		var third_part = "";	
	  	 	 	 	for(var j =0; j<all_parts.length;j++){
			  	 		if((third_part + all_parts[k]).length<d.r){
			  	 		third_part+= all_parts[k] + " ";
			  	 		k++;
			  	 		}
			  	 		else break;
			  	 	}
	  	 			i= -0.5;
	  	 			third_part = third_part.substring(0,third_part.length-4);
	  	 			third_part += "...";
	  	 		 parts.push(third_part);
		  	    }

		  	  //   var first_part = string.substring(0, d.r);
  	 	  //  	 	var second_part = string.substring(d.r, d.r*2);
		  	 	// var third_part = string.substring(d.r*2, (d.r*3)-4)+" ...";
		  	 	// parts = [first_part];
		  	 	// if(string.length>d.r){
		  	 	// 	parts.push(second_part);
		  	 	// 	i= 0.0;
		  	 	// };
		  	 	// if(string.length>d.r*2){
		  	 	// 	parts.push(third_part)
	  	 		// 	i = -0.5;
		  	 	// };

	
		  	 
		 	 }
	
		       for(var key in parts){
		       	var str = parts[key];
		       	stext.append("tspan").attr("x", 0)
      			.attr("y", function(d) { return i*13})
      			.text(str)
      			i++;
		       }
	
		      })
	

		  var node = g.selectAll("circle,text");

		  svg
		      .style("background", "rgb(255,255,255)")
		      .on("click", function() { if(!prevent_click)zoom(n_root); });


		  zoomTo([n_root.x, n_root.y, n_root.r * 2 + margin]);

		  function zoom(d) {

		  	prevent_click = true;	

			var zoomout;	

		    var focus0 = focus; focus = d;


		    if(focus0.depth>focus.depth)zoomout = true;
		    else zoomout = false;
		   	 if(focus0.depth==focus.depth)zoomout = -1;

		    if(zoomout !=-1 ){
		    	
		    	var top = "100px";

			    if(!zoomout){
		      		if(window_width>959)jQuery('#graphdiv').animate({top:"+="+top},d3.event.altKey ? 7500 : 680,'swing');
		      		jQuery('.entry-header').fadeOut('fast');
		      	}

		      	else{
		      		if(window_width>959)jQuery('#graphdiv').animate({top:"-="+top},d3.event.altKey ? 7500 : 680,'swing',function(){
		      			jQuery('.entry-header').fadeIn('fast');
		      		});
      				
		      	}

	    	}

		    var transition = d3.transition()
		        .duration(d3.event.altKey ? 7500 : 750)
		        .tween("zoom", function(d) {
		          var i = d3.interpolateZoom(view, [focus.x, focus.y, focus.r * 2 + margin]);
		          return function(t) { zoomTo(i(t)); };
		        });

		    transition.selectAll("text")
		      .filter(function(d) { return d.parent === focus || this.style.display === "inline"; })
		        .style("fill-opacity", function(d) { return d.parent === focus ? 1 : 0; })
		        .on("start", function(d) { if (d.parent === focus) this.style.display = "inline"; })
		        .on("end", function(d) { if (d.parent !== focus) this.style.display = "none"; prevent_click = false;});
		  }

		  function zoomTo(v) {
		    var k = diameter / v[2]; 
		    view = v;
		    node.attr("transform", function(d) { return "translate(" + (d.x - v[0]) * k + "," + (d.y - v[1]) * k + ")"; });
		    circle.attr("r", function(d) { return d.r * k; });
		  }



		  function getColor(d){

		  	var color;

		  		if(d.depth==0)color = "#fff";

		  		if(d.data.type == "partner"){
		  				if(d.depth==1)color = "#52758e";
						if(d.depth==2)color = "#7db2d8";
		  		}

  				if(d.data.type == "quellen"){
						if(d.depth==1)color = "#9e74c1";
						if(d.depth==2)color = "#c48ef2";
		  			
		  		}

  				if(d.data.type == "lang_data"){
						if(d.depth==1)color = "#92bc74";
						if(d.depth==2)color = "#b5d18c";
		  				
		  		}

		  	return color;
		  }
	

		  }

</script>

<?php 
}
?>
