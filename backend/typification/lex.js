"use strict";

var /** DescriptionList*/ descriptionList;
var /** jQuery */ currentStimulusList;

jQuery(function() {
	
	addNewEnumValueScript(undefined, undefined, dbname);
	
	jQuery(".chosenSelect").chosen();
	
	descriptionList = new DescriptionList();
	
	if(window.localStorage.getItem('atlas')){
		jQuery('#filterAtlas').val(window.localStorage.getItem('atlas'));
		jQuery('#filterAtlas').trigger("chosen:updated");
	}
	
	//Change atlas
	jQuery("#filterAtlas").change (changeAtlas);
	
	//Change stimulus
	jQuery(".stimulusList select").change(changeStimulus);
	jQuery("#AllorNot").change(changeStimulus);
	jQuery("#AllorNotConcept").change(changeStimulus);
	
	//Select/Deselect token
	jQuery("#tokenAuswahlLex").change(changeRecord);
	
	//Typify
	jQuery(".assignButton").click(typify);
	jQuery("#newVAType").click(openMorphTypeDialog);
	jQuery("#editVAType").click(editMorphType);
	jQuery("#newMTypeButton").click (saveMorphType);
	
	//No Typification
	jQuery(".conceptButton").click(assignConcept);
	
	jQuery("#newConcept").click(function (){
		showTableEntryDialog('NeuesKonzept', function (data){
			if(data["Grammatikalisch"] == "1"){
				jQuery('#keinTypAuswahl').append("<option value='" + data["id"] + "'>" + data["Beschreibung_D"] + "</option>").trigger("chosen:updated");
			}
			if(data["Relevanz"] == "1"){
				jQuery('#konzeptAuswahl').append("<option value='" + data["id"] + "'>" + (data["Name_D"] != ""? data["Name_D"]: data["Beschreibung_D"]) + "</option>").trigger("chosen:updated");
			}
		}, selectModes.Chosen, dbname);
	});
	
	//Edit menu
	jQuery("#newReferenceButton").click(function () {
		showTableEntryDialog('NeueReferenzFuerZuweisung', callbackSaveReference, selectModes.Chosen, dbname);
	});
	
	jQuery("#newBaseTypeButton").click(function () {
		showTableEntryDialog('NeuerBTypFuerZuweisung', callbackSaveBaseType, selectModes.Chosen, dbname);
});
	
	jQuery('.infoSymbol').qtip();
	
	changeAtlas(true);
});

/**
 * 
 * @param {boolean} firstCall
 * 
 * @return {undefined}
 */
function changeAtlas (firstCall){
	if(currentStimulusList != null){
		currentStimulusList.toggle(false);
		currentStimulusList.find("select").chosen("destroy");
	}
	var atlas = jQuery("#filterAtlas").val();
	window.localStorage.setItem('atlas', atlas);
	
	if(atlas == ""){
		jQuery("#tokenAuswahlLex").chosen("destroy");
		jQuery(".tokenInfo").toggle(false);
		removeLock("Tokens", null, null, dbname);
		return;
	}
		
	currentStimulusList = jQuery(".stimulusList#" + atlas);
	currentStimulusList.find("select").val("");
	currentStimulusList.toggle(true);
	currentStimulusList.find("select").chosen({"allow_single_deselect" : true});
	
	changeStimulus(firstCall);
}

/**
 * 
 * @param {boolean} firstCall
 * 
 * @return {undefined}
 */
function changeStimulus(firstCall) {

	removeLock("Tokens", null, null, dbname);

	jQuery("#tokenAuswahlLex").val([]);

	var selectObject = jQuery(".stimulusList#" + jQuery('#filterAtlas').val() + " select");
	var id = selectObject.val();
	
	jQuery("#tokenAuswahlLex").chosen("destroy");
	jQuery(".tokenInfo").toggle(false);
	
	jQuery("#recordSummary tr").not(":has(th)").remove();
	descriptionList.removeAll();
	
	if(id == ""){
		jQuery("#pdfFrame").attr("src", "about:blank");
		return;
	}
	
	jQuery("#AllorNot").prop("disabled", true);
	jQuery("#AllorNotConcept").prop("disabled", true);
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
		"dbname" : dbname
	};
	jQuery.post(ajaxurl, data, function(response) {
		var tokens = JSON.parse(response);
		
		var optionsHtml = "";
		for (var i = 0; i < tokens.length; i++){
			descriptionList.addDescription(tokens[i]);
		}
		
		jQuery("#tokensLoading").toggle(false);
		
		jQuery("#tokenAuswahlLex").html(descriptionList.getOptionsHtml());
		jQuery(".tokenInfo").toggle(true);
		jQuery("#tokenAuswahlLex").chosen({"allow_single_deselect" : true});
		
		jQuery("#AllorNot").prop("disabled", false);
		jQuery("#AllorNotConcept").prop("disabled", false);
		selectObject.prop("disabled", false).trigger("chosen:updated");
		jQuery("#filterAtlas").prop("disabled", false).trigger("chosen:updated");
	});
	
	if(this.id != "AllorNot" && this.id != "AllorNotConcept"){
		var file = selectObject.find(":selected").attr("data-file").replace("#", "%23");
		jQuery("#pdfFrame").attr("src", scanUrl + file);
	}
}

function changeRecord (obj,changed){
	if(changed.hasOwnProperty("selected")){
		var descr = descriptionList.getDescription(changed["selected"]);
		addLock("Tokens", descr.getLockName(), function (response){
			if(response != 'success' && writeMode){
				alert("Der Beleg \"" + descr.token + "\" wird bereits von einem anderen Benutzer typisiert!");
				jQuery("#tokenAuswahlLex").val(jQuery("#tokenAuswahlLex").val().filter(function (e){
					return e != changed["selected"];
				}));
				jQuery("#tokenAuswahlLex").trigger("chosen:updated");
				jQuery("#recordSummary tr").filter("tr[data-id-description=" + changed["selected"] + "]").remove();
			}
		}, dbname);
		
		jQuery("#recordSummary").append(descr.createTableRow());
		
		addRowEventListeners();
	}
	else if (changed.hasOwnProperty("deselected")){
		var descr = descriptionList.getDescription(changed["deselected"]);
		jQuery("#recordSummary tr").filter("tr[data-id-description=" + changed["deselected"] + "]").remove();
		removeLock("Tokens", descr.getLockName(), null, dbname);
	}
	else {
		alert("Error: " + JSON.stringify(changed));
	}
}

function deleteTypification (row){
	var descr = descriptionList.getDescription(row.data("id-description"));
	descr.vatype = "---LOADING---";
	row.children().last().html("<img src='" + loadingUrl + "' />");
	
	var data = {
		"action" : "va",
		"namespace" : "typification",
		"query" : "removeTypification",
		"description" : JSON.stringify(descr),
		"dbname" : dbname
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
		"dbname" : dbname
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
	var selectedIds = jQuery("#tokenAuswahlLex").getSelectionOrder();
	
	jQuery("#recordSummary tr").not(":has(th)").remove();
	
	for (var index in selectedIds){
		jQuery("#recordSummary").append(descriptionList.getDescription(selectedIds[index]).createTableRow());
	}
	addRowEventListeners();
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
	
	var selectedIds = jQuery("#tokenAuswahlLex").getSelectionOrder();
	if(selectedIds.length == 0){
		alert("Keine Belege ausgewÃ¤hlt!");
		return;
	}
	
	if(this.id == "assignVA"){
		var newTypeId = jQuery("#morphTypenAuswahl").val();
	}
	else {
		alert("UngÃ¼ltige Auswahl!");
		return;
	}
	
	var descrList = [];
	var warningMessage = false;
	for (var i = 0; i < selectedIds.length; i++){
		var descr = descriptionList.getDescription(selectedIds[i]);
		if(!warningMessage && descr.id_vatype != null && descr.id_vatype != newTypeId){
			var cont = confirm("Manche der Belege wurden bereits abweichend typisiert. Diese Typisierung wird Ã¼berschrieben. Fortsetzen?");
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
		"dbname" : dbname
	};
	jQuery.post(ajaxurl, data, function (response){
		if(response != "success"){
			alert(response);
		}
		else {
			removeLock("Tokens", null, null, dbname);
			
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
	
	var selectedIds = jQuery("#tokenAuswahlLex").getSelectionOrder();
	if(selectedIds.length == 0){
		alert("Keine Belege ausgewÃ¤hlt!");
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
		alert("UngÃ¼ltige Auswahl!");
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
		"dbname" : dbname
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
			errors += descrList[i].name + ": Konzept " + responseArray[i] + "\n";
		}
	}
	jQuery("#tokenAuswahlLex").trigger("chosen:updated");
	
	if(errors !== ""){
		alert("Folgende Belege konnten nicht bearbeitet werden, das sie schon mit einem anderen Konzept verbunden sind:\n\n" + errors);
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
		alert("Das Feld \"Orth\" darf nicht leer sein!");
		return;
	}

	jQuery.post(ajaxurl, data, function (response){
		try {
			var typeInfo = JSON.parse(response);
			closeMorphDialog();
			
			if(edit){
				var typeSelect = jQuery("#morphTypenAuswahl option:selected");
				jQuery("#auswahlBestandteile option[value=" + typeSelect.val() + "]").text(typeInfo['Name']);
				jQuery("#auswahlBestandteile").trigger("chosen:updated");
				typeSelect.text(typeInfo['Name']);
				jQuery("#morphTypenAuswahl").trigger("chosen:updated");
				
				descriptionList.changeTypeName(id, typeInfo['Name']);
				repaintRecordSummary();
			}
			else {
				var optionHtml = "<option value='" + typeInfo['Id'] + "'>" + typeInfo['Name']  + "</option>";
				jQuery("#auswahlBestandteile").append(optionHtml).trigger("chosen:updated");
				jQuery("#morphTypenAuswahl").append(optionHtml).trigger("chosen:updated");
			}
		}
		catch (e) {
			alert(e + "(" + response + ")");
		}
	});
}

function editMorphType (){
	var caller = this;
	var data = {
		"action" : "va",
		"namespace" : "typification",
		"query" : "getMorphTypeDetails",
		"id" : jQuery("#morphTypenAuswahl").val(),
		"dbname" : dbname
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
