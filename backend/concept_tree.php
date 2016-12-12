<?php

global $NO_CATEGORY;
$NO_CATEGORY = '(Keine Kategorie)';

function konzeptbaum (){
	global $NO_CATEGORY;
	?>
	<script type="text/javascript">
	var curNode;
	var update = false;
	
	jQuery(function () {
		jQuery("#selectMKat").val("0");
		jQuery("#selectKat").val("0");

		jQuery("#selectMKat").on("change", function(){
			jQuery("#selectKat").val("0").trigger("change");

			var hkat = this.value;
			if(hkat == "0"){
				jQuery("#selectKat").toggle(false);
			}
			else {
				jQuery("#selectKat").toggle(true);
			}
		});
		
		jQuery("#selectKat").on("change", function(){
			var kat = this.value;
			jQuery("#treeContainer").children().jstree("destroy");
			if(kat == "0"){
				jQuery("#treeContainer").html("");
			}
			else {
				jQuery.post(ajaxurl, {
						"action": 'va', 
						'namespace' : 'concept_tree',
						"query" : "show_tree", 
						"category" : kat.substring(1),
						"main_category" : jQuery("#selectMKat").val().substring(1)
						}, function (response) {
					jQuery("#treeContainer").html(response);
					jQuery("#treeContainer").children().jstree({
						"core" : {
							"check_callback" : true
						},
						"plugins" : [ "dnd", "contextmenu" , "sort"],
						"contextmenu" : {
							"items" : function (node){
								return {
									"newAtTop" : {"label" : "Neues Konzept auf höchster Ebene anlegen", action : function (){update = false; curNode = jQuery("#treeContainer ul > li:first"); neuesKonzept();}},
									"newAtPos" : {"label" : "Neues Unterkonzept an dieser Stelle anlegen", action : function (){update = false; curNode = jQuery("#" + node.id); neuesKonzept();}},
									"editConcept" : {"label" : "Dieses Konzept bearbeiten", action : function () {update = true; curNode = jQuery("#" + node.id); neuesKonzept();}}, 
								};
							}
						}
					});
					
					jQuery('.konzeptbaum').on('move_node.jstree', function (e, data){
						var id_konzept = data.node.data.konzept * 1;
						var id_ueberkonzept = jQuery('#' + data.node.parent).attr("data-konzept");
						jQuery.post(ajaxurl, {
							'action' : 'va',
							'namespace' : 'concept_tree',
							'query' : 'update_node',
							'concept' : id_konzept,
							'superconcept' : id_ueberkonzept
						}, function (response){
							if(response != "success"){
								alert(response);
							}
						});
					});
				});
			}
		});
		
		
		jQuery.jstree.defaults.dnd.is_draggable = function (nodes) {
			var id_konzept = nodes[0].li_attr["data-konzept"] * 1;
			if(id_konzept == 707){
				alert("Bitte das oberste Konzept nicht verschieben!");
				return false;
			}
			return true;
		};
		
		jQuery.jstree.defaults.sort = function (a, b){
			return this.get_text(a).toLowerCase() > this.get_text(b).toLowerCase() ? 1 : -1;
		}
	
	});
	
	
	
	function neuesKonzept (){
		var e = document.forms["inputNewConceptForTree"].elements;
		if(update){
			jQuery.post(ajaxurl, {
				'action' : 'va',
				'namespace' : 'concept_tree',
				'query' : 'get_concept_info',
				'concept' : curNode.attr("data-konzept")
			}, function (response){
				var data = JSON.parse(response);
				for (var i = 0; i < data[0].length; i++) {
					if(i != 2)
						e[i].value = data[0][i];
				}
				if(data[0][2] == 1)
					e[2].checked = true;
				else
					e[2].checked = false;

				showTableEntryDialog("NewConceptForTree", conceptCallback, undefined, undefined, true, "Id_Konzept", curNode.attr("data-konzept"));
			});
		}
		else {
			for (var i = 0; i < e.length; i++){
				if(e[i].type != "hidden")
					e[i].value = '';
			}
			showTableEntryDialog("NewConceptForTree", conceptCallback);
		}
			
	}
	
	
	function conceptCallback (result){
		var name = result["Name_D"] == ""? result["Beschreibung_D"]: result["Name_D"] + "(" + result["Beschreibung_D"] + ")";
		var kategorieNeu = result["Kategorie"];
		var hkategorieNeu = result["Hauptkategorie"];
		//TODO maybe change color here if main category has changed
		
		if(kategorieNeu == jQuery("#selectKat").val().substring(1) && (hkategorieNeu == jQuery("#selectMKat").val().substring(1) || hkategorieNeu == "Allgemein")){	
			if(update){
				jQuery("#treeContainer").children().jstree('set_text', curNode, name);
			}
			else {
				jQuery("#treeContainer").children().jstree(true).create_node(curNode, {"text" : name, "data" : {"konzept" : result["id"]}});
			}
		}
		else {
			if(update){
				jQuery("#treeContainer").children().jstree(true).delete_node(curNode);
			}
		}
	}
	</script>
	
	<h1> Konzeptbaum </h1>
	
	<br />
	<br />
	
	<select id="selectMKat">
		<option value="0" selected>--- Hauptkategorie wählen ---</option>
	<?php
	global $va_xxx;
	$kategorien = $va_xxx->get_col('SELECT DISTINCT Hauptkategorie FROM Konzepte ORDER BY Hauptkategorie');
	
	foreach ($kategorien as $kat){
		echo '<option value="S' . $kat . '">' . ($kat == ''? $NO_CATEGORY: $kat) . '</option>';
	}
	?>
	</select>
	
	<select id="selectKat" style="display : none">
		<option value="0" selected>--- Kategorie wählen ---</option>
	<?php
	global $va_xxx;
	$kategorien = $va_xxx->get_col('SELECT DISTINCT Kategorie FROM Konzepte ORDER BY Kategorie');
	
	foreach ($kategorien as $kat){
		echo '<option value="S' . $kat . '">' . ($kat == ''? $NO_CATEGORY: $kat) . '</option>';
	}
	?>
	</select>
	
	<div id="treeContainer">
		
	</div>
			
	<?php	
	
	echo im_table_entry_box ('NewConceptForTree', new IM_Row_Information('Konzepte', array(
			new IM_Field_Information('Name_D', 'V', false),
			new IM_Field_Information('Beschreibung_D', 'V', true),
			new IM_Field_Information('Relevanz', 'B', false),
			new IM_Field_Information('Kategorie', 'E', true, true),
			new IM_Field_Information('Hauptkategorie', 'E', true, true),
			new IM_Field_Information('Kommentar_Intern', 'V', false)
			
	)));
}

function showTree ($mkat, $kat){
	global $va_xxx;
	global $Ue;
	$top_konzepte = $va_xxx->get_col("SELECT Id_Konzept FROM Konzepte JOIN Ueberkonzepte USING (Id_Konzept) WHERE Id_Ueberkonzept = 707 AND Relevanz AND Kategorie = '$kat' AND (Hauptkategorie = '$mkat' OR Hauptkategorie = 'Allgemein')");
	
	$currentKat = 'XXX';
	
	$result = '<div class="konzeptbaum" id="DivKat' . $kat . '"><ul><li class="jstree-open" data-konzept="707"> (KONZEPTE)';
	
	foreach ($top_konzepte as $tk){
		$result .= getConceptForId($tk, 'D', $Ue, $mkat, true);
	}
	$result .= '</ul></li></div>';
	return $result;
}


function getConceptForId ($id, $lang, &$Ue, $mkat, $showZeroRecords = false){
	
	global $va_xxx;
	
	$conceptInfo =  $va_xxx->get_row("SELECT Name_$lang, Beschreibung_$lang, IF(Anzahl_Allein IS NULL, 0, Anzahl_Allein), IF(Anzahl_Komplett IS NULL, 0, Anzahl_Komplett), Hauptkategorie FROM Konzepte LEFT JOIN A_Anzahl_Konzept_Belege USING (Id_Konzept) WHERE Id_Konzept = $id", ARRAY_N);
	if($conceptInfo[3] == '0' || ($conceptInfo[4] != 'Allgemein' && $conceptInfo[4] != $mkat))
		return '';
		
	$children = $va_xxx->get_col("SELECT Id_Konzept FROM Ueberkonzepte WHERE Id_Ueberkonzept = $id");
	$res = '<ul><li class="' . ($conceptInfo[4] == 'Allgemein'? ' generalConcept' : 'specificConcept') .'" data-konzept="' . $id . '">' . ($conceptInfo[0] == ''? ($conceptInfo[1]): $conceptInfo[0] . ' (' . ($conceptInfo[1]) . ')') . ' (' . $conceptInfo[2] . ' ' . ($conceptInfo[2] == '1'? $Ue['BELEG'] : $Ue['BELEGE']) . ')';
	
	foreach ($children as $child){
		$res .=  getConceptForId($child, $lang, $Ue, $mkat, $showZeroRecords);
	}
	return $res . '</li></ul>';
}
?>