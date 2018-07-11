<?php 
function va_graph_page (){
	
	global $va_xxx;
	
	$start_date = '2017-02-10';
	$bar_width = 25;
	
	$data = $va_xxx->get_results("
			SELECT 
				Day, 
				Date, 
				SUM(COUNT_Sonstige) AS COUNT_Sonstige, 
				SUM(COUNT_SI) AS COUNT_SI, 
				SUM(COUNT_IT) AS COUNT_IT, 
				SUM(COUNT_FR) AS COUNT_FR, 
				SUM(COUNT_DE) AS COUNT_DE, 
				SUM(COUNT_CH) AS COUNT_CH, 
				SUM(Count_AT) AS COUNT_AT, 
				SUM(Gesamt) AS Gesamt, 
				SUM(Personen) AS Personen, 
				GROUP_CONCAT(Name, ':', Wert, ':', Gesamt) AS Gemeinden
			FROM
			(SELECT 
				DATE_FORMAT(Erfasst_Am, '%a %b %d %Y') AS Day, 
				Date(Erfasst_Am) AS Date, 
				sum(IF(Wert NOT IN ('svn','aut','deu','fra','ita','che'), 1, 0)) as COUNT_Sonstige,
				sum(IF(Wert = 'svn', 1, 0)) as COUNT_SI,
				sum(IF(Wert = 'ita', 1, 0)) as COUNT_IT,
				sum(IF(Wert = 'fra', 1, 0)) as COUNT_FR,
				sum(IF(Wert = 'deu', 1, 0)) as COUNT_DE,
				sum(IF(Wert = 'che', 1, 0)) as COUNT_CH,
				sum(IF(Wert = 'aut', 1, 0)) as COUNT_AT,
				count(*) as Gesamt,
				count(DISTINCT Id_Informant) as Personen,
				Name,
				Wert,
				count(*) AS COUNT_Comm
			FROM Aeusserungen JOIN Informanten USING (Id_Informant) JOIN Orte ON Id_Ort = Id_Gemeinde JOIN Orte_Tags USING (Id_Ort)
			WHERE Id_Stimulus = 90322 AND Tag = 'LAND' AND Erfasst_Am > '$start_date'
			GROUP BY DATE(Erfasst_Am), Id_Gemeinde) c
			GROUP BY Date
			ORDER BY Date ASC", ARRAY_A);
	
	$num_days = $va_xxx->get_var("SELECT datediff(NOW(), '$start_date')");
	
	$reports = $va_xxx->get_results("SELECT Datum, Bericht FROM Berichte WHERE Datum >  '$start_date' ORDER BY Datum", ARRAY_A);
	
	wp_localize_script('toolsSkript', 'CS_DATA', $data);
	wp_localize_script('toolsSkript', 'REPORTS', $reports);
?>
<div style="overflow-x: auto; overflow-y: hidden;" id="graphdiv">
<svg id="graph" style="width: <?php echo $num_days * $bar_width;?>px; height: 700px;"></svg>
</div>

<script src="https://d3js.org/d3.v4.js"></script>

<script type="text/javascript">

var colorMapping = {"Sonstige" : "black", "svn": "red", "ita" : "purple", "fra": "lightblue", "deu" : "green", "che" : "gold", "aut" : "darkblue"}

jQuery(function (){
	jQuery("#graphdiv").scrollLeft(<?php echo $num_days * $bar_width;?>);

	jQuery("#page").css("max-width", "95%");
	jQuery("#colophon").css("max-width", "95%");

	var countries = Object.keys(CS_DATA[0]).filter(function (e){
		return e.startsWith("COUNT_");
	});

	//Add empty days
	var CS_DATA_NEW = [];
	var currDate = new Date(<?php echo substr($start_date, 0, 4);?>, <?php echo intval(substr($start_date, 5, 2)) - 1;?>, <?php echo substr($start_date, 8, 2);?>);
	var stopDate = new Date();
	var csIndex = 0;
	var repIndex = 0;

	while(currDate < stopDate){
		if(repIndex < REPORTS.length && new Date(REPORTS[repIndex]["Datum"]).toDateString() == currDate.toDateString()){
			var report = REPORTS[repIndex++]["Bericht"];
		}
		else {
			var report = null;
		}

		if(csIndex < CS_DATA.length && new Date(CS_DATA[csIndex]["Date"]).toDateString() == currDate.toDateString()){
			CS_DATA_NEW.push(Object.assign(CS_DATA[csIndex++], {Report : report}));
		}
		else {
			CS_DATA_NEW.push({Day : currDate.toDateString(), Personen : 0, Report : report});
		}
		currDate.setDate(currDate.getDate() + 1);
	}
	
	var svg = d3.select("#graph"),
	    margin = {top: 50, right: 50, bottom: 100, left: 40},
	    width = parseInt(svg.style("width"), 10) - margin.left - margin.right,
	    height = parseInt(svg.style("height"), 10) - margin.top - margin.bottom;
	
	var x = d3.scaleBand()
		.rangeRound([0, width])
		.padding(0.1)
		.domain(CS_DATA_NEW.map(function(d) { return d["Day"]; }));

	var y = d3.scaleLinear()
		.rangeRound([height, 0])
		.domain([0, d3.max(CS_DATA_NEW, function(d) { return d["Gesamt"] * 1; })]);
	
	var z = d3.scaleOrdinal()
		.range(["black", "red", "purple", "lightblue", "green", "gold", "darkblue"])
		.domain(countries);
	
	var g = svg.append("g")
	    .attr("transform", "translate(" + margin.left + "," + margin.top + ")");
	
	g.append("g")
	    .attr("class", "axis axis--x")
	    .attr("transform", "translate(0," + height + ")")
	    .call(d3.axisBottom(x))
	    .selectAll("text")
	    	.attr("y", x.bandwidth() / 2)
	    	.attr("x", 7)
	    	.attr("transform", "rotate(45)")
	    	.attr("text-anchor", "start")
	    	.attr("fill", function (d){
				if(d.startsWith("Sun") || d.startsWith("Sat")){
					return "red";
				}
				else {
					return "blue";
				}
	    	})
	    	.attr("font-family", "sans-serif")
	    	.attr("font-size", 10);
	
	g.append("g")
	    .attr("class", "axis axis--y")
	    .call(d3.axisLeft(y));

	g.append("g")
    .attr("class", "axis axis--y")
    .attr("transform", "translate(" + width + " ,0)")
    .call(d3.axisRight(y))

	// Balken
	g.append("g")
	  .attr("id", "ggraph")
	  .selectAll("g")
	  .data(d3.stack().keys(countries)(CS_DATA_NEW))
	  .enter().append("g")
	  	.attr("fill", function (d) { return z(d.key); })
	  .selectAll("rect")
	  .data(function(d) { return d; })
	  .enter().append("rect")
	  	.attr("data-comm", function(d) { return commWindow(d.data["Gemeinden"]); })
	    .attr("x", function(d) { return x(d.data["Day"]); })
	    .attr("y", function(d) { return isNaN(d[1])? 0 : y(d[1]); })
	    .attr("width", x.bandwidth())
	    .attr("height", function(d) { return isNaN(d[1])? 0:  y(d[0]) - y(d[1]); });

	// Anzahl Nutzer
    d3.selectAll("#ggraph").each(function (){
		d3.select(this.lastChild)
			.selectAll("text")
			.data(CS_DATA_NEW)
			.enter().append("text")
			.attr("x", function (d) {return x(d["Day"]) + (x.bandwidth() / 2);})
			.attr("y", function (d) {return isNaN(d["Gesamt"])? 0 : y(d["Gesamt"]) - 5;})
			.attr("width", x.bandwidth())
			.text(function (d) {return d["Personen"] != 0 ? d["Personen"] : "";})
			.attr("font-family", "sans-serif")
			.attr("text-anchor", "middle")
	    	.attr("font-size", 10)
    		.attr("fill", "olive")
    		.attr("font-weight", "bold");
    });
	
	// Berichte
	g.append("g")
	  .selectAll("g")
	  .data(CS_DATA_NEW)
	  .enter().append("rect")
	    .attr("x", function(d) {return x(d["Day"]); })
	    .attr("y", height - 300)
	    .attr("width", function (d){return d["Report"] == null? 0 : 1;})
	    .attr("height", 300);
		
	var texts = 
	g.append("g")
	  .attr("transform", "translate(0, " + (height - 305) + ")")
	  .selectAll("g")
	  .data(CS_DATA_NEW)
	  .enter().append("g")
	    .attr("transform", function(d) {return "translate(" + x(d["Day"]) + ",0)"; });

	texts.append("rect");
	    
	texts.append("text")
		.attr("y", 0)
		.attr("x", 0)
		.text(function (d){return d["Report"];})
		.attr("transform", "rotate(45)")
		.attr("font-family", "sans-serif")
		.attr("font-size", 12)
		.attr("text-anchor", "end");

	var i = 0;
	texts.selectAll("text").each (function (d){
		CS_DATA_NEW[i++].bb = this.getBBox();
	});

	texts.selectAll("rect")
		.attr("x", function(d) {return d.bb.x;})
		.attr("y", function(d) {return d.bb.y;})
		.attr("width", function(d) {return d.bb.width;})
		.attr("height", function(d) {return d.bb.height;})
		.style("fill", "white")
		.attr("transform", "rotate(45)");


	//Legende
	var legend = g.append("g")
	    .attr("font-family", "sans-serif")
	    .attr("font-size", 10)
	    .attr("text-anchor", "end")
	    .attr("id", "glegend")
	    .attr("transform", "translate(" + <?php echo $num_days * $bar_width - 200;?> + ",0)")
	  .selectAll("g")
	  .data(countries.slice().reverse())
	  .enter().append("g")
	    .attr("transform", function(d, i) { return "translate(0," + i * 20 + ")"; });
	
	legend.append("rect")
	    .attr("x", 50)
	    .attr("y", - 30)
	    .attr("width", 19)
	    .attr("height", 19)
	    .attr("fill", function (d){ 
			if(d.startsWith("_____"))
				return "white";
		    return z(d);});
	
	legend.append("text")
	    .attr("x", 40)
	    .attr("y", -20.5)
	    .attr("dy", "0.32em")
	    .text(function(d) { return d.substring(6); });

    var le = d3.select("#glegend").append("g");

    le.append("text")
    	.text("1")
    	.attr("x", 60)
    	.attr("y", (countries.length + 1) * 19 - 30)
    	.attr("dy", "0.32em")
    	.attr("font-weight", "bold")
    	.attr("fill", "olive")
    	.attr("text-anchor", "middle")
    
    le.append("text")
    	.text("Anzahl unterschiedlicher Nutzer")
    	.attr("x", 40)
    	.attr("y", (countries.length + 1) * 19 - 30)
    	.attr("dy", "0.32em");

    jQuery("rect").qtip({
		"content" : {
			"attr" : "data-comm",
			title: {
				button: true // Close button
			}
		},
		"show": {
			event: "mousedown",
			solo: true
		},
		"events": {
			render:	function(event, api) {
				api.elements.target.bind('click', function() {
					api.set('hide.event', false);
				});
			}
		},
		"position" : {
	        target: 'mouse',
	        adjust: { mouse: false }
	    }
    });
});

function commWindow (str){
	if(!str)
		return "";
	
	var comms = str.split(",").map(x => x.split(":"));
	var sum = 0;
	
	var result = "<div><table style='border: 1px solid black; border-collapse: separate;'>";
	for (var i = 0; i < comms.length; i++){
		var col = colorMapping[comms[i][1]];
		result += "<tr style='background: " + col + "; padding: 3px; color: " + (col == "gold"? "black": "white") + ";'><td style='padding: 3px;'>" + comms[i][0] + "</td><td style='padding: 3px;'>" + comms[i][2] + "</td></tr>";
		sum += (comms[i][2] * 1);
	}

	result += "<tr><td style='border-top: 1px solid black; padding: 3px;'></td><td style='border-top: 1px solid black; padding: 3px;'>" + sum + "</td></tr>";
		
	return result + "</table></div>";
}
</script>

<br />
<br />

Gesamteintragungen: <?php echo $va_xxx->get_var('SELECT COUNT(*) FROM Aeusserungen WHERE Id_Stimulus = 90322');?>
<br />
<br />
<div class="entry-content">
	<ul>
	<?php 
	$country_counts = $va_xxx->get_results("
			SELECT Wert, COUNT(*) AS Anz
			FROM Aeusserungen JOIN Informanten USING (Id_Informant) JOIN Orte_Tags ON Id_Ort = Id_Gemeinde AND Tag = 'LAND'
			WHERE Erhebung = 'CROWD'
			GROUP BY Wert", ARRAY_A);
	
	foreach ($country_counts as $cc){
		echo '<li>' . $cc['Wert'] . ': ' . $cc['Anz'] . '</li>';
	}
	?>
	</ul>
Anzahl Informanten: <?php echo $va_xxx->get_var("SELECT COUNT(*) FROM Informanten WHERE Erhebung = 'CROWD'");?>
</div>
<?php 
}
?>