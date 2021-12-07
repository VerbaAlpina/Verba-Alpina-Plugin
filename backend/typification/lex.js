"use strict";

var /** DescriptionList*/ descriptionList;
var /** boolean */ shiftPressed = false;

jQuery(function() {
	
	jQuery("#morphTypenAuswahl").val("").select2({
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
	
	jQuery("#konzeptAuswahl").val("").select2({
		ajax: {
			"url": ajaxurl,
			"type" : "POST",
			"dataType": "json",
			"data": function (params) {
				var query = {
					"action" : "va",
					"namespace" : "util",
					"query" : "getConceptsForSelect",
					"search": params.term,
					"ignore_gram" : true,
					"page": params.page || 1
				}
	
	      		return query;
			}
		}
	});
	
	addNewEnumValueScript(undefined, undefined, DATA.dbname);
	
	jQuery(".chosenSelect").chosen({"normalize_search_text" : removeDiacritics});
	
	descriptionList = new DescriptionList();
	
	if(window.localStorage.getItem('atlas')){
		jQuery('#filterAtlas').val(window.localStorage.getItem('atlas'));
		jQuery('#filterAtlas').trigger("chosen:updated");
	}
	
	jQuery(document).keydown(function (key){
		if(key.keyCode == 16){
			shiftPressed = true;
		}
	});
	
	jQuery(document).keyup(function (key){
		if(key.keyCode == 16){
			shiftPressed = false;
		}
	});
	
	jQuery("#emptySelection").click(emptySelection);
	
	//Change atlas
	jQuery("#filterAtlas").change (changeAtlas);
	
	//Change stimulus
	jQuery(document).on("change", ".stimulusList", changeStimulus);
	jQuery("#AllorNot").change(changeStimulus);
	jQuery("#AllorNotConcept").change(changeStimulus);
	jQuery("#AllorNotAlpes").change(changeStimulus);
	
	//Select/Deselect token
	jQuery("#tokenAuswahlLex").change(changeRecord);
	
	//Typify
	jQuery(".assignButton").click(typify);
	jQuery("#editVAType").click(editMorphType);
	jQuery("#dupVAType").click(editMorphType);
	
	//No Typification
	jQuery(".conceptButton").click(assignConcept);
	
	jQuery("#newConcept").click(function (){
		showTableEntryDialog('NeuesKonzept', function (data){
			if(data["Grammatikalisch"] == "1"){
				jQuery('#keinTypAuswahl').append("<option value='" + data["id"] + "'>" + data["Beschreibung_D"] + "</option>").trigger("chosen:updated");
			}
			if(data["Relevanz"] == "1"){
				jQuery('#konzeptAuswahl').append("<option value='" + data["id"] + "'>" + (data["Name_D"] != ""? data["Name_D"]: data["Beschreibung_D"]) + "</option>").val(data["id"]).trigger("change");
			}
		}, selectModes.Chosen, DATA.dbname);
	});
	
	//Edit menu
	jQuery('.infoSymbol').qtip();
	
	addListenersForCreateLexType();
	
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
			"minimumInputLength" : 2,
			"dropdownParent": jQuery('#VAProblemOverlay')
		});
	});
	
	jQuery(document).on("click", ".problemRemoveRef", function (){
		jQuery(this).closest("tr").find("select").select2("destroy");
		jQuery(this).closest("tr").remove();
	});
	
	jQuery(document).on("click", ".correctButton", function (){
		var description = descriptionList.getDescription(jQuery(this).closest("tr").data("id-description"));
		
		var res = "";
		for (var i = 0; i < description.idlist.length; i++){
			res += description.idlist[i] + "\t\t\t " + TRANSLATIONS.ATTESTATION + ": " + description.aelist[i] + " --- (Id_Aeusserung  " + description.aeidlist[i] + ")\n";
		}
		
		if(description.kind == "T" || description.kind == "P" || description.kind == "M")
			alert("Token-Ids:\n" + res);
		else
			alert("Tokengruppe-Ids:\n" + res);
	});
	
	jQuery(document).on("click", ".problemButton", function (){
		
		var id_desc = jQuery(this).closest("tr").data("id-description");
		var description = descriptionList.getDescription(id_desc);
		
		jQuery('#VAProblemOverlay #problemComment').val("");
		jQuery('#VAProblemOverlay #problemNewType').val("");
		jQuery('#VAProblemOverlay #problemType').val("");
		jQuery('#VAProblemOverlay #problemRefTable').empty();
		
		jQuery("#VAProblemOverlay #problemDescId").val(id_desc);
		jQuery("#VAProblemOverlay #problemStimulus").val(jQuery("#filterAtlas").val() + ": " + jQuery(".stimulusList option:selected").text());
		jQuery("#VAProblemOverlay #problemRecord").val(description.name);
		
		jQuery('#VAProblemOverlay').dialog({
			"minWidth" : 700,
			"modal": true,
			"close" : function (){
				jQuery("#VAProblemOverlay select").select2("destroy");
			}
		});
		
		jQuery("#VAProblemOverlay #problemType").select2({
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
			},
			width: "400px",
			dropdownParent: jQuery('#VAProblemOverlay')
		});
	});
	
	jQuery("#problemConfirm").click(function (){
		
		if (!jQuery("#VAProblemOverlay #problemComment").val()){
			alert("Please enter a comment!");
			return;
		}
			
		var desc_id = jQuery("#VAProblemOverlay #problemDescId").val();
		var description = descriptionList.getDescription(desc_id);
		var refs = [];
		
		jQuery("#VAProblemOverlay #problemRefTable tr").each(function (){
			let newRef = {"id" : jQuery(this).find("td:first select").val(), "text" : jQuery(this).find("td:nth-child(2) input").val()};
			refs.push(newRef);
		});
		
		jQuery.post(ajaxurl, {
			"action" : "va",
			"namespace" : "typification",
			"query" : "add_problem",
			"id_stimulus" : description.id_stimulus,
			"record" : description.name,
			"kind" : description.kind,
			"ids" : description.idlist,
			"comment" : jQuery("#VAProblemOverlay #problemComment").val(),
			"id_type" : jQuery("#VAProblemOverlay #problemType").val(),
			"type_text" : jQuery("#VAProblemOverlay #problemNewType").val(),
			"refs" : refs
		}, function (response){
			if (response === "success"){
				var selectedIds = jQuery("#tokenAuswahlLex").getSelectionOrder();
				
				jQuery("#tokenAuswahlLex").setSelectionOrder(selectedIds.filter(item => item !== desc_id), true);
				jQuery("#recordSummary tr[data-id-description=" + desc_id + "]").remove();
				
				jQuery("#tokenAuswahlLex option[value=" + desc_id + "]").css("background", "red").prop("disabled", true);
				jQuery("#tokenAuswahlLex").trigger("chosen:updated");
				
				descriptionList.remove(desc_id);
				jQuery('#VAProblemOverlay').dialog("close");
			}
			else {
				alert("Error");
			}
		});
	});
	
	changeAtlas(true);
});

function emptySelection (){
	var values = jQuery("#tokenAuswahlLex").val();
	if (values != null){
		for (var i = 0; i < values.length; i++){
			var descr = descriptionList.getDescription(values[i]);
			jQuery("#recordSummary tr").filter("tr[data-id-description=" + values[i] + "]").remove();
			removeLock("Tokens", descr.getLockName(), null, DATA.dbname);
		}
		jQuery("#tokenAuswahlLex").val([]).trigger("chosen:updated");
	}
}

/**
 * 
 * @param {boolean} firstCall
 * 
 * @return {undefined}
 */
function changeAtlas (firstCall){
	if(jQuery(".stimulusList").length > 0){
		jQuery(".stimulusList").chosen("destroy");
		jQuery("#stimulusListDiv").html("");
	}
	var atlas = jQuery("#filterAtlas").val();
	window.localStorage.setItem('atlas', atlas);
	
	jQuery(".tokenInfo").toggle(false);
	
	if(!atlas){
		jQuery("#tokenAuswahlLex").chosen("destroy");
		removeLock("Tokens", null, null, DATA.dbname);
		return;
	}
	
	var data = {
		"action" : "va",
		"namespace" : "typification",
		"query" : "getStimulusList",
		"atlas" : atlas,
		"all" : (jQuery("#AllorNot").is(":checked")? "0": "1"),
		"allC" : (jQuery("#AllorNotConcept").is(":checked")? "0": "1"),
		"allA" : (jQuery("#AllorNotAlpes").is(":checked")? "0": "1"),
		"dbname" : DATA.dbname
	};
	
	jQuery.post(ajaxurl, data, function(response) {
		if (response){
			jQuery("#stimulusListDiv").html(response);
			jQuery(".stimulusList").chosen({"allow_single_deselect" : true, "normalize_search_text" : removeDiacritics});
			changeStimulus(firstCall);
		}
	});
}

/**
 * 
 * @param {boolean} firstCall
 * 
 * @return {undefined}
 */
function changeStimulus(firstCall) {

	removeLock("Tokens", null, null, DATA.dbname);

	jQuery("#tokenAuswahlLex").val([]);

	var selectObject = jQuery(".stimulusList");
	var id = selectObject.val();
	
	jQuery("#tokenAuswahlLex").chosen("destroy");
	jQuery(".tokenInfo").toggle(false);
	
	jQuery("#recordSummary tr").not(":has(th)").remove();
	descriptionList.removeAll();
	
	let checkboxTriggered = this != undefined && (this.id == "AllorNot" || this.id == "AllorNotConcept" || this.id == "AllorNotAlpes");
	if (checkboxTriggered){
			
		if(jQuery(".stimulusList").length > 0){
			jQuery(".stimulusList").chosen("destroy");
			jQuery("#stimulusListDiv").html("");
		}
		
		var data = {
		"action" : "va",
		"namespace" : "typification",
		"query" : "getStimulusList",
		"atlas" : jQuery("#filterAtlas").val(),
		"all" : (jQuery("#AllorNot").is(":checked")? "0": "1"),
		"allC" : (jQuery("#AllorNotConcept").is(":checked")? "0": "1"),
		"allA" : (jQuery("#AllorNotAlpes").is(":checked")? "0": "1"),
		"dbname" : DATA.dbname
		};
		
		jQuery.post(ajaxurl, data, function(response) {
			if (response){
				jQuery("#stimulusListDiv").html(response);
				jQuery(".stimulusList").chosen({"allow_single_deselect" : true, "normalize_search_text" : removeDiacritics}).val(id);
				var selectObject = jQuery(".stimulusList");
				changeStimulusRest(selectObject.val(), selectObject, checkboxTriggered);
			}
		});
	}
	else {
		changeStimulusRest(id, selectObject, checkboxTriggered)
	}
}

function changeStimulusRest (id, selectObject, checkboxTriggered){
	
	if(!id){
		jQuery("#pdfFrame").attr("src", "about:blank");
		return;
	}
	
	jQuery("#AllorNot").prop("disabled", true);
	jQuery("#AllorNotConcept").prop("disabled", true);
	jQuery("#AllorNotAlpes").prop("disabled", true);
	selectObject.prop("disabled", true).trigger("chosen:updated");
	jQuery("#filterAtlas").prop("disabled", true).trigger("chosen:updated");
	jQuery("#tokensLoading").toggle(true);

	var data = {
		"action" : "va",
		"namespace" : "typification",
		"query" : "getTokenList",
		"id" : id,
		"all" : (jQuery("#AllorNot").is(":checked")? "0": "1"),
		"allC" : (jQuery("#AllorNotConcept").is(":checked")? "0": "1"),
		"allA" : (jQuery("#AllorNotAlpes").is(":checked")? "0": "1"),
		"dbname" : DATA.dbname
	};
	jQuery.post(ajaxurl, data, function(response) {
		var tokens = JSON.parse(response);
		
		descriptionList = new DescriptionList();
		
		for (var i = 0; i < tokens.length; i++){
			descriptionList.addDescription(tokens[i]);
		}
		
		jQuery("#tokensLoading").toggle(false);
		
		jQuery("#tokenAuswahlLex").html(descriptionList.getOptionsHtml());
		jQuery(".tokenInfo").toggle(true);
		jQuery("#tokenAuswahlLex").chosen({"allow_single_deselect" : true, "normalize_search_text" : removeDiacritics});
		
		jQuery("#AllorNot").prop("disabled", false);
		jQuery("#AllorNotConcept").prop("disabled", false);
		jQuery("#AllorNotAlpes").prop("disabled", false);
		selectObject.prop("disabled", false).trigger("chosen:updated");
		jQuery("#filterAtlas").prop("disabled", false).trigger("chosen:updated");
	});
	
	if(!checkboxTriggered){
		var file = selectObject.find(":selected").attr("data-file");
		
		var data = {
			"action" : "va",
			"namespace" : "typification",
			"query" : "checkFileExists",
			"file" : file
		};
		
		jQuery.post(ajaxurl, data, function (response){
			if(response != "no")
				jQuery("#pdfFrame").attr("src", DATA.scanUrl + response.replace("#", "%23"));
		});
	}
}

function changeRecord (obj,changed){
	
	if(changed.hasOwnProperty("selected")){
		var descr = descriptionList.getDescription(changed["selected"]);
		addLock("Tokens", descr.getLockName(), function (response){
			if(response != 'success' && DATA.writeMode){
				alert("Der Beleg \"" + descr.token + "\" wird bereits von einem anderen Benutzer typisiert!");
				jQuery("#tokenAuswahlLex").val(jQuery("#tokenAuswahlLex").val().filter(function (e){
					return e != changed["selected"];
				}));
				jQuery("#tokenAuswahlLex").trigger("chosen:updated");
				jQuery("#recordSummary tr").filter("tr[data-id-description=" + changed["selected"] + "]").remove();
			}
		}, DATA.dbname);
		
		jQuery("#recordSummary").append(descr.createTableRow());
		
		addRowEventListeners();
		
		if(shiftPressed){
			var otherIds = descriptionList.getIdenticalNames(descr.name, descr.id);
			var oldIds = jQuery("#tokenAuswahlLex").getSelectionOrder();
			for (var i = 0; i < otherIds.length; i++){
				if (oldIds.indexOf(otherIds[i] + "") == -1){
					var descrOther = descriptionList.getDescription(otherIds[i]);
					oldIds.push(otherIds[i] + "");
					addLock("Tokens", descrOther.getLockName(), function (name, id, response){
						if(response != 'success' && DATA.writeMode){
							alert("Der Beleg \"" + name + "\" wird bereits von einem anderen Benutzer typisiert!");
							jQuery("#tokenAuswahlLex").val(jQuery("#tokenAuswahlLex").val().filter(function (e){
								return e != id;
							}));
							jQuery("#tokenAuswahlLex").trigger("chosen:updated");
							jQuery("#recordSummary tr").filter("tr[data-id-description=" + id + "]").remove();
						}
					}.bind(this, descrOther.token, otherIds[i]), DATA.dbname);
				}
			}
			jQuery("#tokenAuswahlLex").setSelectionOrder(oldIds, true);
			repaintRecordSummary();
		}
	}
	else if (changed.hasOwnProperty("deselected")){
		var descr = descriptionList.getDescription(changed["deselected"]);
		jQuery("#recordSummary tr").filter("tr[data-id-description=" + changed["deselected"] + "]").remove();
		removeLock("Tokens", descr.getLockName(), null, DATA.dbname);
	}
	else {
		alert("Error: " + JSON.stringify(changed));
	}
}

function deleteTypification (row){
	var descr = descriptionList.getDescription(row.data("id-description"));
	descr.vatype = "---LOADING---";
	row.find("td:nth-last-child(2)").html("<img src='" + DATA.loadingUrl + "' />");
	
	var data = {
		"action" : "va",
		"namespace" : "typification",
		"query" : "removeTypification",
		"description" : JSON.stringify(descr),
		"dbname" : DATA.dbname
	};
	jQuery.post(ajaxurl, data, function (response){
		if(response != "success"){
			alert(response);
		}
		else {
			descr.vatype = null;
			descr.id_vatype = null;
			var removedIds = descriptionList.removeDuplicatesOf(descr);
			var selectedIds = jQuery("#tokenAuswahlLex").getSelectionOrder();
			selectedIds = selectedIds.filter(function (val){
				return removedIds.indexOf(val) == -1;
			});
			jQuery("#tokenAuswahlLex").html(descriptionList.getOptionsHtml());
			jQuery("#tokenAuswahlLex").setSelectionOrder(selectedIds, true);
			
			repaintRecordSummary();
		}
	});
}

function deleteConcept (element){
	var descr = descriptionList.getDescription(element.closest("tr").data("id-description"));
	var id = element.attr("id");
	descr.setConceptLoading(id, true);
	repaintRecordSummary();
	
	var data = {
		"action" : "va",
		"namespace" : "typification",
		"query" : "removeConcept",
		"description" : JSON.stringify(descr),
		"concept" : id,
		"dbname" : DATA.dbname
	};
	jQuery.post(ajaxurl, data, function (response){
		if(response != "success"){
			alert(response);
		}
		else {

			descr.removeConcept(id);
			var removedIds = descriptionList.removeDuplicatesOf(descr);
			var selectedIds = jQuery("#tokenAuswahlLex").getSelectionOrder();
			selectedIds = selectedIds.filter(function (val){
				return removedIds.indexOf(val) == -1;
			});
			jQuery("#tokenAuswahlLex").html(descriptionList.getOptionsHtml());
			jQuery("#tokenAuswahlLex").setSelectionOrder(selectedIds, true);
			
			repaintRecordSummary();
		}
	});
}

function repaintRecordSummary (){
	if(jQuery("#tokenAuswahlLex").data("chosen")){
		var selectedIds = jQuery("#tokenAuswahlLex").getSelectionOrder();
		
		jQuery("#recordSummary tr").not(":has(th)").remove();
		
		for (var index in selectedIds){
			jQuery("#recordSummary").append(descriptionList.getDescription(selectedIds[index]).createTableRow());
		}
		addRowEventListeners();
	}
}

function addRowEventListeners (){
	jQuery(".deleteTypification").on("click", function (){
		deleteTypification(jQuery(this).closest("tr"));
	});
	jQuery(".deleteConcept").on("click", function (){
		deleteConcept(jQuery(this).closest(".chosen-like-button"));
	});
	jQuery("#recordSummary td a").on("mouseover", function (){
		jQuery(this).addClass("selected");
	});
	jQuery("#recordSummary td a").on("mouseout", function (){
		jQuery(this).removeClass("selected");
	});
}

function typify (){
	
	if (!jQuery("#morphTypenAuswahl").val()){
		alert(TRANSLATIONS.CHOOSE_TYPE);
		return;
	}
	
	var selectedIds = jQuery("#tokenAuswahlLex").getSelectionOrder();
	if(selectedIds.length == 0){
		alert("Keine Belege ausgewählt!");
		return;
	}
	
	if(this.id == "assignVA"){
		var newTypeId = jQuery("#morphTypenAuswahl").val();
	}
	else {
		alert("Ungültige Auswahl!");
		return;
	}
	
	var descrList = [];
	var warningMessage = false;
	for (var i = 0; i < selectedIds.length; i++){
		var descr = descriptionList.getDescription(selectedIds[i]);
		if(!warningMessage && descr.id_vatype != null && descr.id_vatype != newTypeId){
			var cont = confirm("Manche der Belege wurden bereits abweichend typisiert. Diese Typisierung wird überschrieben. Fortsetzen?");
			if(!cont){
				return;
			}
			warningMessage = true;
		}
		descrList.push(descr);
	}
	
	var data = {
		"action" : "va",
		"namespace" : "typification",
		"query" : "addTypification",
		"descriptionList" : JSON.stringify(descrList),
		"newTypeId" : newTypeId,
		"dbname" : DATA.dbname
	};
	jQuery.post(ajaxurl, data, function (response){
		if(response != "success"){
			alert(response);
		}
		else {
			removeLock("Tokens", null, null, DATA.dbname);
			
			jQuery("#tokenAuswahlLex").setSelectionOrder([], true);
			jQuery("#recordSummary tr").not(":has(th)").remove();
			
			if(jQuery("#AllorNot").is(":checked")){
				//Remove newly typified values
				for (var i = 0; i < selectedIds.length; i++){
					jQuery("#tokenAuswahlLex option[value=" + selectedIds[i] + "]").remove();
					descriptionList.remove(selectedIds[i]);
				}
			}
			else {
				//Update newly typified values
				for (var i = 0; i < selectedIds.length; i++){
					jQuery("#tokenAuswahlLex option[value=" + selectedIds[i] + "]").css("font-weight", "");
					descrList[i].id_vatype = newTypeId;
					descrList[i].vatype = jQuery("#morphTypenAuswahl option:selected").text();
				}
			}
			jQuery("#tokenAuswahlLex").trigger("chosen:updated");
		}
	});
}

function assignConcept (){
	
	if (this.id == "assignConcept" && !jQuery("#konzeptAuswahl").val()){
		alert(TRANSLATIONS.CHOOSE_CONCEPT);
		return;
	}
	
	var selectedIds = jQuery("#tokenAuswahlLex").getSelectionOrder();
	if(selectedIds.length == 0){
		alert(TRANSLATIONS.NO_RECORDS);
		return;
	}
	
	if(this.id == "noTypeButton"){
		var newConceptId = jQuery("#keinTypAuswahl").val();
	}
	else if(this.id == "assignConcept"){
		var newConceptId = jQuery("#konzeptAuswahl").val();
		var multiple = true;
	}
	else {
		alert(TRANSLATIONS.INVALID_SELECTION);
		return;
	}
	
	var descrList = [];
	for (var i = 0; i < selectedIds.length; i++){
		var descr = descriptionList.getDescription(selectedIds[i]);
		if(!descr.hasConcept(newConceptId)){
			descrList.push(descr);
		}	
	}
	repaintRecordSummary();
	
	var data = {
		"action" : "va",
		"namespace" : "typification",
		"query" : "addConcept",
		"descriptionList" : JSON.stringify(descrList),
		"newConceptId" : newConceptId,
		"dbname" : DATA.dbname
	};
	
	if(multiple){
		data["allowMultipleConcepts"] = true;
		var callbackFunction = callbackConceptAssign;
	}
	else {
		var callbackFunction = callbackNoType;
	}
	
	jQuery.post(ajaxurl, data,callbackFunction.bind(this, newConceptId, selectedIds, descrList));
}

function callbackNoType (newConceptId, selectedIds, descrList, response){
	try {
		var responseArray = JSON.parse(response);
	}
	catch (e){
		alert(response);
		return;
	}
	
	var errors = "";
	
	for (var i = 0; i < responseArray.length; i++){
		if(responseArray[i] === "success"){
			jQuery("#recordSummary tr").filter("tr[data-id-description=" + selectedIds[i] + "]").remove();
			jQuery("#tokenAuswahlLex option[value=" + selectedIds[i] + "]").remove();
			descriptionList.remove(selectedIds[i]);
		}
		else {
			errors += descrList[i].name + ": " + TRANSLATIONS.CONCEPT + " " + responseArray[i] + "\n";
		}
	}
	jQuery("#tokenAuswahlLex").trigger("chosen:updated");
	
	if(errors !== ""){
		alert(TRANSLATIONS.NO_CONCEPT_ASSIGNED + ":\n\n" + errors);
	}
}

function callbackConceptAssign (newConceptId, selectedIds, descrList, response){
	try {
		var responseArray = JSON.parse(response);
	}
	catch (e){
		alert(response);
		return;
	}
	
	for (var i = 0; i < responseArray.length; i++){
		if(responseArray[i] === "success"){
			if(jQuery("#AllorNotConcept").is(":checked")){
				jQuery("#tokenAuswahlLex option[value=" + selectedIds[i] + "]").remove();
				descriptionList.remove(selectedIds[i]);
			}
			else {
				descrList[i].addConcept(newConceptId);
				jQuery("#tokenAuswahlLex option[value=" + selectedIds[i] + "]").css("font-style", "");
			}
		}
		else {
			alert(responseArray[i]);
		}
	}

	jQuery("#tokenAuswahlLex").trigger("chosen:updated");
	repaintRecordSummary();
}

function saveMorphType (){
	var edit = jQuery("#saveCaller").val() == "editVAType";
	var id;
	if(edit){
		id = jQuery("#morphTypenAuswahl option:selected").val();
	}
	var data = getMorphTypeData(id);
	
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
			
			var optionHtml = "<option value='" + typeInfo['Id'] + "'>" + typeInfo['Name']  + "</option>";

			if(edit){
				jQuery("#morphTypenAuswahl option[value=" + typeInfo['Id'] + "]").remove();
				
				descriptionList.changeTypeName(id, typeInfo['Name']);
				repaintRecordSummary();
			}
			
			jQuery("#morphTypenAuswahl").append(optionHtml).val(typeInfo['Id']).trigger("change");
		}
		catch (e) {
			alert(e + "(" + response + ")");
		}
	});
}

function editMorphType (){
	
	if (!jQuery("#morphTypenAuswahl").val()){
		alert(TRANSLATIONS.CHOOSE_TYPE);
		return;
	}
	
	var caller = this;
	var data = {
		"action" : "va",
		"namespace" : "typification",
		"query" : "getMorphTypeDetails",
		"id" : jQuery("#morphTypenAuswahl").val(),
		"dbname" : DATA.dbname
	};
	jQuery.post(ajaxurl, data, function (response){
		try {
			var data = JSON.parse(response);
			openMorphTypeDialog.call(caller);
			setMorphTypeData(data);
		}
		catch (e) {
			alert(response);
		}
	});
}