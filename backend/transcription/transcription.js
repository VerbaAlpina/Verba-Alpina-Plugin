var select2Data;
var parser;

var dbname = "va_playground"; //TODO remove

var lastInput = null;

jQuery(function (){
	jQuery("#atlasSelection").val(-1);
	
	jQuery('.infoSymbol').qtip();
	
	layout();
	
	jQuery(".helpIcon").each(addUpperQTips);
	
	select2Data = {
		data : Concepts,
		sorter : function (results){
			var term = State.currentConceptField.value.toUpperCase();
			if(term){
				results.sort(function (a, b){
					var t1 = a.text.toUpperCase();
					var t2 = b.text.toUpperCase();

					var diff = t1.indexOf(term) - t2.indexOf(term);
					if(diff == 0){
						return t1.localeCompare(t2);
					}
					else {
						return diff;
					}
				});
			}
			return results;
		},
		matcher : va_matcher
	};
	
	jQuery("select:not(.conceptList, #mapSelection)").select2();
	jQuery("#mapSelection").select2({
		templateResult: function (state){
			colString = "";
			if(state.element){
				var colString = " style='background-color: " + jQuery(state.element).css("background-color") + ";'";
			}
			return jQuery("<span" + colString + ">" + state.text + "</span>");
		},
		matcher : va_matcher
	});
	
	jQuery("#atlasSelection").change (atlasChanged);
	jQuery("#mapSelection").change(function (){
		mapChanged(jQuery("#mapSelection").val());
	});
	jQuery("#mode").change(ajax_info);
	jQuery("#region").change(ajax_info);
	
	jQuery("td.imageTranscriptionRule").click(clickOnRule);
	
	jQuery(document).on("focus", ".inputStatement", function (){
		lastInput = this;
	});
	
	jQuery("#addRow").click(function (){
		var data = {'action' : 'va',
				'namespace' : 'transcription',
				'query' : 'get_new_row',
				'index' : ++State.Index,
				'dbname' : dbname
		};
		jQuery.post(ajaxurl, data, function (response){
			jQuery("#inputTable").append(response);
			
			fixTdWidths();
			
			getField("inputStatement", State.Index).focus();
			getField("classification", State.Index).val(getField("classification", 0).val());
			getField("conceptList", State.Index).val(getField("conceptList", 0).val()).trigger("change");
			
			addRowJS(data.index);

		});
	});
	
	var backspaceIsPressed = false;
    jQuery(document).keydown(function(event){
        if (event.which == 8) {
            backspaceIsPressed = true;
            
        }
    });
    jQuery(document).keyup(function(event){
        if (event.which == 8) {
            backspaceIsPressed = false;
        }
    });
    
    jQuery(window).on('beforeunload', function(){
        if (backspaceIsPressed) {
            backspaceIsPressed = false;
            return "Are you sure you want to leave this page?";
        }
    });
    
    jQuery(document).on("focus", ".select2-search__field", {}, function (){
    	State.currentConceptField = this;
    })
    
    jQuery("#addConcept").click(function (){
    	showTableEntryDialog("newConceptDialog", function (paramData){
    		var text = paramData["Name_D"]? paramData["Name_D"] : paramData["Beschreibung_D"];
    		insertConcept(paramData["id"], text);
    	}, selectModes.Select2, "va_playground"); //TODO remove
    });
    
    addNewEnumValueScript ("#newConceptDialog select", selectModes.Select2, "va_playground"); //TODO playground
    
    jQuery(document).on("keyup paste", ".inputStatement", updateOriginal);
    
    parser = peg.generate(grammarText);
});

function updateOriginal (){
	var thisObject = this;
	setTimeout(function () { //Timeout is needed since the paste event is fired before the input value has changed
		jQuery(thisObject).next().html(convertToOriginal(thisObject.value));
	}, 0);
}

function clickOnRule (){

	if(!jQuery("#input_fields").hasClass("hidden_c")){
		var beta = jQuery(this).parent().find("td.betaTranscriptionRule").text();
		if (jQuery(".inputStatement").length == 1 && !jQuery(".inputStatement").is(":disabled")){
			jQuery(".inputStatement").val(jQuery(".inputStatement").val() + beta);
			updateOriginal.call(jQuery(".inputStatement")[0]);
		}
		else if (lastInput != null && !jQuery(lastInput).is(":disabled")) {
			jQuery(lastInput).val(jQuery(lastInput).val() + beta);
			updateOriginal.call(lastInput);
		}
	}
}

function addUpperQTips (){
	jQuery(this).qtip({
		content : {
			text : jQuery("#" + this.id.replace("Icon", ""))
		},
		position : {
			my : "bottom right",
			target : "top left"
		},
		style : {
			width : "600px"
		},
		hide : {
			fixed : true
		}
	});
}

function convertToOriginal (text){
	
	if(!text || text == '<vacat>' || text == '<problem>')
		return "";
	
	text = text.trim();
	
	try {
		var charList = parser.parse(text);
	}
	catch (e){
		return "<span style='color: red'>NICHT GÜLTIG</span>";
	}
	result = "";
	for (var i = 0; i < charList.length; i++){
		var match = charList[i];
		if(match.startsWith("\\\\")){
			var entry = match.substring(2);
		}
		else {
			var entry = Codepage[match];
		}
		if(entry)
			result += "<span style='position: relative;'>" + entry + "</span>";
		else
			result += "<span style='color: red'>" + match + "</span>";
	}
	return result;
}

function insertConcept (id, text){
	for (var i = 0; i < Concepts.length; i++){
		if(name.localeCompare(Concepts[i]["text"]) > 0){
			Concepts.splice(i - 1, 0, {"id" : id, "text" : text});
			break;
		}
	}
}

function va_matcher (params, data) {
    // Always return the object if there is nothing to compare
    if (jQuery.trim(params.term) === '') {
      return data;
    }

    var original = data.text.toUpperCase();
    var term = params.term.toUpperCase();

    // Check if the text contains the term
    if (original.indexOf(term) > -1) {
      return data;
    }

    // If it doesn't contain the term, don't return anything
    return null;
}

function addRowJS (index){
	var concepSelect = getField("conceptList", index);
	concepSelect.select2(select2Data);
	
	jQuery("#inputRow" + index + " .helpIcon").each(addUpperQTips);
	
	var inputField = getField("inputStatement", index);
	inputField.next().html(convertToOriginal(inputField.val()));
	
	if(index > 0){
		getField("remover", index).click(function (){
			var element = jQuery(this).closest("tr");
			element.find(".conceptList").select2("destroy");
			element.remove();
			State.Index--;
			reindexRows();
			fixTdWidths();
		});
	}
}

function fixTdWidths (){
	var widthNumber = 0;
	var authorWidth = 0;
	var deleteWidth = 0;
	for (var i = 0; i <= State.Index; i++){
		widthNumber = Math.max(widthNumber, getField("spanNumber", i).width());
		authorWidth = Math.max(authorWidth, getField("authorSpan", i).width());
		deleteWidth = Math.max(deleteWidth, getField("deleteSpan", i).width());
	}
	
	var classWidth = getField("classification", 0).width() + 5;
	var totalWidth = jQuery(window).width() - 36 - 10 - 12 - 16 - 16;
	var sum = widthNumber + classWidth + authorWidth + deleteWidth;
	
	jQuery("#inputTable tr td:nth-child(1)").css("width", widthNumber);
	jQuery("#inputTable tr td:nth-child(2)").css("width", (totalWidth - sum) / 2);
	jQuery("#inputTable tr td:nth-child(3)").css("width", classWidth);
	jQuery("#inputTable tr td:nth-child(4)").css("width", (totalWidth - sum) / 2);
	jQuery("#inputTable tr td:nth-child(5)").css("width", authorWidth);
	jQuery("#inputTable tr td:nth-child(6)").css("width", deleteWidth);
}

var State = {
	currentConceptField : null,
	Id_Stimulus : 0,
	Id_Informant : 0,
	Index : 0
}

function reindexRows (){
	var index = 0;
	jQuery("#inputTable tr").each(function (){
		jQuery(this).find("td:first span").html((index + 1) + ".)");
		jQuery(this).prop("id", "inputRow" + index);
		index++;
	});
}

function layout(){
	if (!jQuery(document.body).hasClass('folded')){
		jQuery(document.body).addClass('folded');
	}
	
	var inputHeight = jQuery("#enterTranscription").outerHeight();
	var headerHeight = jQuery("#wpadminbar").outerHeight();
	var documentHeight = jQuery(document).outerHeight();
	var iFrameHeight = documentHeight - inputHeight - headerHeight;
	jQuery("#iframeScanDiv").css("height", iFrameHeight + "px");
	jQuery("#iframeCodepageDiv").css("height", iFrameHeight + "px");
}

function atlasChanged(){
	jQuery("#mapSelectionDiv").css("display", "none");
	mapChanged("");
	
	if(this.value == -1)
		return;
		
	var data = {'action' : 'va',
				'namespace' : 'transcription',
				'query' : 'get_map_list',
				'atlas' : this.value,
				'dbname' :  dbname
	};
	jQuery.post(ajaxurl, data, function (response){
		jQuery("#mapSelection").html(response);
		jQuery("#mapSelectionDiv").css("display", "inline");
		mapChanged(jQuery("#mapSelection").val());
	});
}

function mapChanged (value){
	var pos = value.indexOf('|');
	jQuery("#mapSelection").next().css("width", "30%");
	var selElement = jQuery("#mapSelection").next().find(".select2-selection__rendered");
	if(pos != -1){
		State.Id_Stimulus = value.substring(0, pos);
		var map = value.substring(pos + 1);
		changeIFrame("iframeScan", url + 'scans/' + map.substring(0, map.indexOf("#")) + "/" + map.replace('#', '%23'));
		selElement.css("background-color", "#80FF80")
		ajax_info();
	}
	else {
		changeIFrame("iframeScan", "about:blank");
		selElement.css("background-color", "#fe7266")
		jQuery("#input_fields").addClass("hidden_c");
		jQuery("#informant_info").addClass("hidden_c");
		jQuery("#error").html("").addClass("hidden_coll");
		State.Id_Stimulus = 0;
		State.Id_Informant = 0;
		deleteLock();
	}
}

function changeIFrame (id, src){
	var ifr = jQuery("#" + id);
	if(ifr.prop("src") == src)
		return;
	ifr.replaceWith("<iframe id='" + id + "' src='" + src + "'></iframe>");
}

function ajax_info (){

	if(State.Id_Stimulus == "")
		return;
	
	jQuery("#input_fields").addClass("hidden_c");
	jQuery("#informant_info").addClass("hidden_c");
	
	var mapVal = jQuery("#mapSelection").val();
	
	var data = {'action' : 'va',
				'namespace' : 'transcription',
				'query' : 'update_informant',
				'mode' : jQuery("#mode").val(),
				'region': getRegion(jQuery("#region").val()),
				'id_stimulus' : mapVal.substring(0, mapVal.indexOf('|')),
				'dbname' : dbname
				
	};
	jQuery.post(ajaxurl, data, function (response) {
		updateFields(response);
	});
}

function deleteLock (){
	var data = {'action' : 'va',
				'namespace' : 'transcription',
				'query' : 'delete_locks',
				'dbname' : dbname
	};
	jQuery.post(ajaxurl, data, null);
}

function getRegion (value){
	if(value == ""){
		return "%";
	}
	return value;
}
var t;
function updateFields (info){
	var errorDiv = jQuery("#error");
	var mode = jQuery("#mode").val();
	
	try {
		var obj = JSON.parse(info);
		errorDiv.html("").addClass("hidden_coll");
		jQuery("#informant_info").html("<span class='informant_fields'>" + obj[0].Erhebung + " " + obj[0].Karte + " - " + obj[0].Stimulus 
				+ "</span> - Informant_Nr <span class='informant_fields'>" + obj[0].Informant_nummer + "</span> (" + obj[0].ortsname + ")");
		State.Id_Informant = obj[0].Id_Informant;
		
		jQuery(".conceptList").select2("destroy");
		jQuery("#inputTable").empty();
		
		State.Index = obj.length - 1;
		for (var i = 0; i < obj.length; i++){
			jQuery("#inputTable").append(obj[i].html.replace(/\\/g, ""));
			var inputField = getField("inputStatement", i);
			if(mode == 'first'){
				inputField.val("");
			}
			else {
				inputField.val(obj[i].Aeusserung);
				getField("classification", i).val(obj[i].Klassifizierung);
			}
			inputField.prop("disabled", obj[i].readonly);
			getField("classification", i).prop("disabled", obj[i].readonly);
			getField("conceptList", i).prop("disabled", obj[i].readonly).trigger("change");

			addRowJS(i);
			
			getField("conceptList", i).val(obj[i].Konzept_Ids).trigger("change");
		}
		fixTdWidths();
		
		jQuery("#input_fields").removeClass("hidden_c");
		jQuery("#informant_info").removeClass("hidden_c");
		getField("inputStatement", 0).focus();
	}
	catch (s){
		jQuery("#input_fields").addClass("hidden_c");
		jQuery("#informant_info").addClass("hidden_c");
		State.Id_Informant = 0;
		
		if(s instanceof SyntaxError){
			errorDiv.html(info);
		}
		else {
			errorDiv.text(s);
		}
		errorDiv.removeClass("hidden_coll");
	}	
}

function getField (className, index){
	return jQuery("#inputRow" + index + " ." + className);
}