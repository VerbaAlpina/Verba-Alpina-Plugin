ajaxurl = ajax_object.ajaxurl;

/**@enum {number}*/
var categories = {
	Informant : 0,
	Concept : 1,
	PhoneticType: 2,
	MorphologicType : 3,
	BaseType : 4,
	ExtraLing : 5,
	Polygon : 6
};

//TODO fix eling error if z_geo is empty, at least "no data" should be shown again
//TODO make polymode = hex in url work

chosenSettings["normalize_search_text"] = removeDiacriticsPlusSpecial;

jQuery(function (){
	jQuery(".conceptTooltip").each(/** @this{Element} */ function (){
		var /** string */ id = /** @type{string} */ (jQuery(this).data("id"));
		jQuery(this).qtip({
			"content" : {
				"text" : createConceptToolTipContent(id)
			},
			"position" : {
				"my" : "bottom right",
				"at" : "top left"
			}
		});
	});
	
	addMouseOverHelp(jQuery("#trSelectionBar"));
	bindMenuSlide();
	
	if (PATH["tk"] != undefined || PATH["single"] != undefined){
		jQuery('#legend_heading').trigger('click');
	}
});

/**
 * @return Array<Array<{id: string, name: string}>|boolean>
 */
function getHexagonChoices (){
	var /** Array<{id: string, name: string}>*/ possibleValues = [];
	jQuery("#hexagonSelect option[value!='']").each(function (){
		possibleValues.push({"id" : "A" + /**@type{string}*/ (jQuery(this).val()), "name" : /** @type{string}*/ (jQuery(this).text())});
	});
	
	var /** boolean|string */ areaId = false;
	for (var i = 0; i < legend.getLength(); i++){
		var /**LegendElement|MultiLegendElement*/ element = legend.getElement(i);
		if (element.category == 6 /** Areas */){
			areaId = element.key;
			var /** number */ indexPipe = areaId.indexOf("|");
			if(indexPipe !== -1)
				areaId = areaId.substring(0, indexPipe);
			break;
		}
	}
	
	if (areaId === false){
		return [possibleValues, true];
	}
	else {
		var /** Array<{id: string, name: string}>*/ restrictedList = [];
		for (i = 0; i < possibleValues.length; i++){
			var /**string */ hexaCategory = possibleValues[i]["id"];
			hexaCategory = hexaCategory.substring(0, hexaCategory.indexOf("|"));
			if (hexaCategory == areaId){
				restrictedList.push(possibleValues[i]);
			}
		}
		if (restrictedList.length == 0){
			return [possibleValues, true];
		}
		else {
			return [restrictedList, false];
		}
	}
}

/**
 * 
 * @return {undefined}
 */
function bindMenuSlide(){

	jQuery('.mode_switch_label').on('click', function(){
		if(!jQuery(this).hasClass("disabled")){
			if(jQuery(this).attr('id') == "phy_label"){
				optionManager.setOption("polymode", "phy");
			}
			else {
				var /** Array<Array<{id: string, name: string}>|boolean>*/ result = getHexagonChoices();
				var /** Array<{id: string, name: string}>*/ listValues = /** @type{Array<{id: string, name: string}>}*/ (result[0]);
				if(listValues.length == 1){
					var /** string */ id = listValues[0]["id"];
					optionManager.setOption("polymode", "hex", {"hex" : id});
				}
				else {
					buildHexModal(listValues, /** @type{boolean} */ (result[1]));
				}
				
			}
		}
	});

	jQuery('.move_menu').on('click',function (){
		jQuery( ".move_menu .active").fadeOut('fast', function(){
			jQuery( ".move_menu .inactive").fadeIn('fast', function(){
				jQuery(".move_menu span").toggleClass("active inactive");
			});
		})
	
		jQuery( "#leftTable" ).slideToggle(function(){
	
		});
	})
	
	jQuery('.menu_heading').on('click',function (e){

		if(!jQuery(e.target).hasClass('mode_switch_label')){

			var that = jQuery(this).parent();
		
			that.find('.menu_caret').toggleClass("fa-caret-right fa-caret-down");
			that.find('.menu_collapse').slideToggle();
			jQuery('.menu_grp').removeClass('active');
			if(!that.hasClass('active'))
				that.addClass('active');
		
			jQuery('.menu_collapse').each(function(){
				if(!jQuery(this).parent().hasClass('active')){
					jQuery(this).slideUp();
			
					if(jQuery(this).parent().find('.menu_caret').hasClass('fa-caret-down')){
						jQuery(this).parent().find('.menu_caret').removeClass('fa-caret-down').addClass('fa-caret-right');
					}
				}
			});

		}
	});

	jQuery(document).on('im_load_data', function(event, category, key, filterData){
		if(category == categories.Polygon && optionManager.getOptionState("polymode") == "hex"){
			//Update ajax data
			categoryManager.addAjaxData("hexgrid", key);
			
			//Reload other legend entries
			for (var i = 0; i < legend.getLength(); i++){
				var /** LegendElement|MultiLegendElement */ element = legend.getElement(i);
				if(element.category != categories.Polygon){
					element.reloadSymbols();
				}
			}
		}
		
		jQuery('#legend_heading').trigger('click');
	});

	jQuery(document).on('im_load_syn_map', function(){
		jQuery('#legend_heading').trigger('click');
	});


	jQuery('#syn_heading').one('click',function(){
	    jQuery("#IM_Syn_Map_Selection").chosen('destroy');
		jQuery("#IM_Syn_Map_Selection").val("").chosen({allow_single_deselect: true});
	});
	

	jQuery(window).resize(function() {
	  if(jQuery('.menu-toggle').hasClass('toggled-on'))jQuery('.menu-toggle').trigger('click');
	  setTableRowWidth();
	  
	});
}

/**
 * 
 * @param {Array<{id: string, name: string}>} list_values
 * @param {boolean} trigger_load
 * 
 * @return {undefined}
 */
function buildHexModal(list_values, trigger_load){

	var /** jQuery */ modal_content = getHexModalContent(list_values);
	
	jQuery('.select_hex_popup .hex-modal-btn').remove();
 	jQuery('.select_hex_popup .hex-modal-btn-grp').append(modal_content);
 	jQuery('.select_hex_popup').modal();

 	jQuery('.hex-modal-btn').one('click',function(){
 		var /** string*/ id = /** @type{string} */ (jQuery(this).attr('id'));
 		
 		var /** string */ shortenedId = id.substring(0, id.indexOf("|"));
 		var /** Object<string, string> */ optionData = {"hex" : id};
 		if (trigger_load){
			optionData["load"] = shortenedId;
		}
 		optionManager.setOption("polymode", "hex", optionData);

		jQuery('.select_hex_popup').modal('hide');
 	});


 	jQuery('.select_hex_popup').on('hide.bs.modal',function(){
 		syncOptionAndBtnStates();
 	});
}

/**
 * 
 * @return {undefined}
 */
function syncOptionAndBtnStates(){
	var /** string */ state = /** @type {string} */ (optionManager.getOptionState('polymode'));
	var /** string */ id = /** @type{string}*/ (jQuery('.map_mode_selection label.active').attr('id'));
	id = id.replace("_label", "");

	 if(id != state){
	 	jQuery('.map_mode_selection label').removeClass('active');
	 	var label_id = state += "_label";
	 	jQuery('#' + label_id).addClass('active');	
	 }
}

/**
 * 
 * @param {Array<{id: string, name: string}>} list_values
 * 
 * @return {jQuery}
 */
function getHexModalContent(list_values){
	var /** string*/ content = "";
	for(var i = 0; i < list_values.length; i++){
		var /** {id: string, name: string} */ value = list_values[i];
		content += '<button id="'+ value["id"] +'" type="button" class="btn btn-secondary hex-modal-btn">' + value["name"] + '</button>';
	}

	return jQuery(content);
}

// currently not needed

function adjustMenuToTopBar(){
	var window_width = window.innerWidth;
	var margin_top = jQuery('.main-navigation').height() + jQuery('#wpadminbar').height();


	if(!(jQuery('.menu-toggle').hasClass('toggled-on')))
	{
		jQuery('.tablecontainer').css('margin-top', margin_top+'px');
	} //dont change if responsive menu is open or still left open

	var window_height = jQuery(document).height();

	var rest=0;
	var bottom_gap = window_height * 0.15; //15% of height

	jQuery('.menu_heading').each(function(){
		rest += jQuery(this).outerHeight();
	});

	rest += jQuery('.move_menu').outerHeight();
	rest +=margin_top;
	rest += bottom_gap;
	var remaining = window_height - rest;
	remaining = Math.round(remaining);
	
	jQuery('.legendtable tbody').css('max-height',remaining+="px");
}

/**
 * 
 * @return {undefined}
 */
function setTableRowWidth(){
	var /** number */ width = /** @type{number}*/ (jQuery('.tablecontainer').width());	
	jQuery('.legendtable tr').width(width + "px");
}

/**
 * 
 * @param {string} key
 * 
 * @return {string}
 */
function simplifyELingKey (key){
	var /** number */ posPipe = key.indexOf("|");
	if(posPipe !== -1)
		return key.substring(1, posPipe);
	return key.substring(1);
}

jQuery(document).on("im_map_initialized", function (){

	if(ajax_object.db != "xxx")
		categoryManager.addAjaxData("db", ajax_object.db);
	
	categoryManager.addInfoWindowContentConstructor("record", RecordInfoWindowContent);
	
	if (PATH["tk"] == undefined && PATH["single"] == undefined){
		categoryManager.loadData(6, "A17", {"subElementCategory" : -1});
	}
	
	commentManager.commentTabOpened = /** @param {jQuery} element */ function (element){
		try {
			addBiblioQTips(element);
			element.find(".quote").qtip({
				"show" : "click",
				"hide" : "unfocus"
			});
			jQuery("#commentTitle").append("&nbsp;");
			jQuery("#commentTitle").append(element.find(".quote"));
		}
		catch (/** @type{string} */ e){
			console.log(e);
		}
	}
	
	commentManager.commentTabClosed = /** @param {jQuery} element */ function (element){
		element.find(".bibl").qtip("destroy", true);
		jQuery("#commentTitle").find(".quote").qtip("destroy", true);
	}
	
	/**
	 * @param {number} categoryID
	 * @param {string} elementID
	 */
	commentManager.showCommentMenu = function (categoryID, elementID){
		return ajax_object.db == "xxx";
	};
});

var /** boolean*/ backupCommunities;
jQuery(document).on("im_edit_mode_started", function (){
	backupCommunities = /** @type{boolean}*/ (optionManager.getOptionState("comm"));
	optionManager.setOption("comm", false, {"reload" : false});
});

jQuery(document).on("im_edit_mode_stopped", function (){
	if(backupCommunities !== undefined){
		optionManager.setOption("comm", backupCommunities, {"reload" : false});
	}
});

jQuery(document).on("im_add_options", function (){
	
	categoryManager.addAjaxData("outside", false);
	
	if(ajax_object.dev == "1"){
		optionManager.addOption("wkt", new ClickOption("Show WKT", function (){
			var wkt = prompt("WKT:");
			if(wkt){
				var geoList = wkt.split(";");
				for (var i = 0; i < geoList.length; i++){
					var feature = new google.maps.Data.Feature ({"geometry" : parseGeoData(geoDataToStrictFormat(geoList[i]))});
					map.data.add(feature);
				}
			}
		}));
	}
	
	optionManager.addOption("polymode", new HexagonOption());
	
	
	optionManager.addOption("ak", new BoolOption(false, TRANSLATIONS["ALPENKONVENTTION_INFORMANTEN"], function(val, details) {
		categoryManager.addAjaxData("outside", val);
		if(!details || details["first"] !== true){
			optionManager.enableOptions(false);
			legend.reloadMarkers(function (){
				optionManager.enableOptions(true);
			});
		}
	}));
	
	categoryManager.addAjaxData("community", true);
	optionManager.addOption("comm", new BoolOption(true, TRANSLATIONS["AUF_GEMEINDE"], function(val, details) {
		categoryManager.addAjaxData("community", val);
		if(!details || (details["reload"] !== false && details["first"] !== true)){
			optionManager.enableOptions(false);
			legend.reloadMarkers(function (){
				optionManager.enableOptions(true);
			});
		}
	}));
	
	if(ajax_object["db"] == "xxx" || ajax_object["db"] * 1 > 171){
		categoryManager.addAjaxData("simple_polygons", true);
		optionManager.addOption("simple_polygons", new BoolOption(true, Ue["VEREINFACHTE_POLYGONE"], function(val, details) {
			categoryManager.addAjaxData("simple_polygons", val);
			if(!details || (details["reload"] !== false && details["first"] !== true)){
				optionManager.enableOptions(false);
				legend.reloadMarkers(function (){
					optionManager.enableOptions(true);
				});
			}
		}));
	}
	
	if(ajax_object.va_staff == "1") {
		var /** BoolOption */ printOption = new BoolOption(false, TRANSLATIONS["DRUCKFASSUNG"], function (val, details){
   

			if(!details || !details["first"]){  //TODO ONLY CALL WHEN URL PARAM FOR PRINT EXISTS

					if(val){
						//TODO should be moved to map interface
						map.setMapTypeId(google.maps.MapTypeId.ROADMAP);
						map.setOptions({"styles": emptyMapStyle});
					}
					else {
		   		  		mapInterface.resetStyle();
					}
			}
		});
		
		jQuery(document).on("im_quantify_mode", function (event, val){
			printOption.setEnabled(!val);
		});
		
		optionManager.addOption("print", printOption);
	}
});


jQuery(document).on("im_legend_before_rebuild", function (event, legend){
	//Remove old qtips
	jQuery("#IM_legend tr td:nth-child(3)").qtip("destroy", true);
});

jQuery(document).on("im_show_edit_mode", 
	/**
	* @param {Event} event
	* @param {{result : boolean}} paramObject
	* 
	* @return {undefined}
	*/
	function (event, paramObject){
		paramObject.result = ajax_object.db == "xxx";
	}
);
	
jQuery(document).on("im_legend_element_created", 
	/**
	 * @param {Object} event
	 * @param {LegendElement} legendElement
	 * @param {Element} DOMElement
	 */
	function (event, legendElement, DOMElement){
		addConceptQTip(legendElement);
	});

/**
 *
 * @param {LegendElement|MultiLegendElement} currentElement
 * 
 * @returns {undefined}
 */
function addConceptQTip(currentElement){
	if(currentElement.category == categories.Concept && currentElement.key != -1){
		var /**jQuery*/ element = createConceptToolTipContent(currentElement.key.substring(1));
		
		if(element){
			jQuery(currentElement.htmlElement).find("td:nth-child(3)").qtip({
				"content" : {
					text : element
				},
				"position" : {
					"my" : "bottom left",
					"at" : "top left"
				}
			});
		}
	}
}

/**
 * @param {string} id
 * 
 * @return {jQuery}
 */
function createConceptToolTipContent (id){
	var /** Array */ concept = Concepts[id];
	
	var /** string */ conceptName = /** @type{string} */ (concept[0]);
	var /** string */ conceptDescr = /** @type{string} */ (concept[1]);
	var /** string */ conceptImg = /** @type{string} */ (concept[4]);
	
	if((conceptName && conceptName != conceptDescr) || conceptImg){
		var /** Element */ result = document.createElement("div");
		
		if(conceptImg){
			var /** Element */ img = document.createElement("img");
			img["src"] = concept[4];
			img["style"]["display"] = "block";
			img["style"]["margin"] = "auto";
			img["style"]["max-width"] = "100%";
			result.appendChild(img);
			result.appendChild(document.createElement("br"));
		}
		
		if(conceptName && conceptName != conceptDescr){
			var /** Element */ span = document.createElement("span");
			span["style"]["text-align"] = "center";
			span.appendChild(document.createTextNode(concept[1]));
			result.appendChild(span);
		}
		
		return jQuery(result);
	}
	return null;
}

var /**AlphabetSorter */ alphabetSorter = new AlphabetSorter();
var /**RecordNumberSorter */ numRecSorter = new RecordNumberSorter();

var il = new SimpleListBuilder(["name", "description"]);
il.addListPrinter(new JsonListPrinter());
il.addListPrinter(new HtmlListPrinter());
il.addListPrinter(new CsvListPrinter());

var /** FieldType */ stringInput = new StringInputType();

var /** EditConfiguration */ informatEditConfig = new EditConfiguration();
informatEditConfig.setFieldData(OverlayType.PointSymbol, [
	new FieldInformation("Erhebung", stringInput, true),
	new FieldInformation("Nummer", stringInput, true),
	new FieldInformation("Ortsname", stringInput, false),
	new FieldInformation("Bemerkungen", stringInput, false)
]);
informatEditConfig.allowNewOverlays(OverlayType.PointSymbol);
informatEditConfig.allowGeoDataChange(OverlayType.PointSymbol);

categoryManager.registerCategory (
	buildCategoryInformation ({
		"categoryID" : categories.Informant, 
		"categoryPrefix" : "I",
		"name" : Ue["INFORMANTEN"],
		"elementID" : "informantSelect", 
		"textForNewComment" : Ue["KOMMENTAR_INFORMANT_SCHREIBEN"],
		"textForListRetrieval" : "Informanten-Daten exportieren",
		"listBuilder" : il,
		"editConfiguration" : informatEditConfig
	})
);

var /** Array<{tag: string, name : string}>*/ lingTags = [{"tag" : "ERHEBUNG", "name": "Atlas"}]; ///TODO translate Atlas

var lingTagFunction =
/** 
 * @param {number} categoryID
 * @param {string} elementID
 * 
 * @return {Object<string, Array<string>>}
 */
function (categoryID, elementID){
	return {"ERHEBUNG" : SourceMapping[elementID].split(",")};
};

categoryManager.registerCategory (
	buildCategoryInformation ({
		"categoryID" : categories.PhoneticType,
		"categoryPrefix" : "P",
		"name" : Ue["PHON_TYP"],
		"nameEmpty" : Ue["NICHT_TYPISIERT"],
		"elementID" : "phonTypeSelect",
		"filterComponents" : [
			new GroupingComponent([categories.Concept], categories.Concept, new Sorter([alphabetSorter, numRecSorter]), undefined, lingTags),
			new MarkingComponent(lingTagFunction)],
		"countNames" : [Ue["BELEG"], Ue["BELEGE"]],
		"textForNewComment" : Ue["KOMMENTAR_PTYP_SCHREIBEN"]
	})
);

categoryManager.registerCategory (
	buildCategoryInformation ({
		"categoryID" : categories.MorphologicType,
		"categoryPrefix" : "L",
		"name" : Ue["MORPH_TYP"],
		"nameEmpty" : Ue["NICHT_TYPISIERT"],
		"elementID" : "morphTypeSelect",
		"filterComponents" : [
			new GroupingComponent([categories.PhoneticType, categories.Concept], categories.Concept, new Sorter([alphabetSorter, numRecSorter]), undefined, lingTags),
			new MarkingComponent(lingTagFunction)],
		"countNames" : [Ue["BELEG"], Ue["BELEGE"]],
		"textForNewComment" : Ue["KOMMENTAR_MTYP_SCHREIBEN"]
	})
);

categoryManager.registerCategory (
	buildCategoryInformation ({
		"categoryID" : categories.BaseType,
		"categoryPrefix" : "B",
		"name" : Ue["BASISTYP"],
		"nameEmpty" : Ue["NICHT_TYPISIERT"],
		"elementID" : "baseTypeSelect",
		"filterComponents" : [
			new GroupingComponent([categories.PhoneticType, categories.MorphologicType, categories.Concept], categories.Concept, new Sorter([alphabetSorter, numRecSorter]), undefined, lingTags),
			new MarkingComponent(lingTagFunction)],
		"countNames" : [Ue["BELEG"], Ue["BELEGE"]],
		"textForNewComment" :Ue["KOMMENTAR_BASIS_SCHREIBEN"]
	})
);

var /** function(number,string,number,number):boolean */ conceptSorterFunc = function (mainCategoryId, elementId, subCategoryId, filterId){
	return filterId != 1 || subCategoryId == categories.MorphologicType;
};

categoryManager.registerCategory (
	buildCategoryInformation ({
		"categoryID" : categories.Concept,
		"categoryPrefix" : "C",
		"name" : Ue["KONZEPT"],
		"nameEmpty" : Ue["KEIN_KONZEPT"],
		"elementID" : "conceptSelect",
		"filterComponents" : [
			new ConceptFilterComponent(), 
			new GroupingComponent(
				[categories.PhoneticType, categories.MorphologicType, categories.BaseType], 
				categories.MorphologicType, 
				new Sorter([alphabetSorter, new LanguageFamilySortComponent (), numRecSorter]), 
				conceptSorterFunc,
				lingTags
				), 
			new MarkingComponent(lingTagFunction)],
		"countNames" : [Ue["BELEG"], Ue["BELEGE"]],
		"textForNewComment" : Ue["KOMMENTAR_KONZEPT_SCHREIBEN"],
		"costumGetNameFunction" : function (key){
			key = key.substring(1); //Remove prefix
			return Concepts[key][0] == ""? /** @type{string} */ (Concepts[key][1]): /** @type{string} */ (Concepts[key][0]);
		}
	})
);

var /** function (number, string) : Object<string, Array<string>> */ elingTagFunction =  function (categoryID, elementID){
	var /** Object<string, Array<string>>*/ result = {};
	var /** boolean */ ak = /** @type{boolean}*/ (optionManager.getOptionState("ak"));
	var /** Object<string, Array<{value: string, ak: number}>> */ tagObject = ELing[simplifyELingKey(elementID)][1];
	
	if(tagObject){
		for (var /** string */ key in tagObject){
			for (var i = 0; i <  tagObject[key].length; i++){
				if(ak || tagObject[key][i]["ak"] == "1"){
					if(!result.hasOwnProperty(key)){
						result[key] = [];
					}
					result[key].push(tagObject[key][i]["value"]);
				}
			}
		}
	}
	
	return  result;
};

var /** function (number, string): Array<{tag:string, name:string}> */ elingGroupFunction = function (categoryID, elementID){
	var /**Array<{tag:string, name:string}>*/ result = [];
	var /** boolean */ ak = /** @type{boolean} */ (optionManager.getOptionState("ak"));
	var /** Object<string, Array<{value: string, ak: number}>> */ tagObject = ELing[simplifyELingKey(elementID)][1];
	if(tagObject){
		for (var key in tagObject){
			for (var i = 0; i <  tagObject[key].length; i++){
				if(ak || tagObject[key][i]["ak"] == "1"){
					var /**string*/ translTag = Ue[key];
					result.push({tag : key, name : (translTag? translTag : key)});
					break;
				}
			}
		}
	}
	
	return result;
};

var /** TagComponent */ elingTag = new TagComponent(elingTagFunction);
var /** GroupingComponent */ elingGroupingE = new GroupingComponent([], undefined, undefined, undefined, elingGroupFunction)

categoryManager.registerCategory (
	buildCategoryInformation ({
		"categoryID" : categories.ExtraLing,
		"categoryPrefix" : "E",
		"name" : Ue["AUSSERSPR"],
		"nameEmpty" : Ue["EMPTY"],
		"elementID" : "extraLingSelect",
		"filterComponents" : [elingTag, elingGroupingE],
		"textForNewComment" : Ue["KOMMENTAR_AUSSERSPR_SCHREIBEN"],
		"costumGetNameFunction" : function (key){
			return /** @type{string} */ (ELing[simplifyELingKey(key)][0]);
		}
	})
);

var /** GroupingComponent */ elingGroupingP = new GroupingComponent(function (categoryID, elementID){
	var /**Array<number>*/ result = [];
	
	if(ajax_object.va_staff == "1" && (elementID == "A62" || elementID == "A60")){
		result.push(-4);
	}
	
	if(simplifyELingKey(elementID) == "63" || simplifyELingKey(elementID) == "17" || simplifyELingKey(elementID) == "74"){
		result.push(-1);
	}
	
	return result;
}, function (categoryID, elementID){
	if(simplifyELingKey(elementID) == "63" || simplifyELingKey(elementID) == "17" || simplifyELingKey(elementID) == "74"){
		return -1;
	}
	if(simplifyELingKey(elementID) == "62" || simplifyELingKey(elementID) == "60"){
		return "LAND";
	}
	return undefined;
}, undefined, undefined, elingGroupFunction);

var /** EditConfiguration */ polyEditConfig = new EditConfiguration();
polyEditConfig.allowGeoDataChange(OverlayType.PointSymbol, function (elementID){
	return elementID == "A62";
});

categoryManager.registerCategory (
	buildCategoryInformation ({
		"categoryID" : categories.Polygon,
		"categoryPrefix" : "A",
		"name" : Ue["POLYGONE"],
		"nameEmpty" : Ue["EMPTY"],
		"elementID" : "polygonSelect",
		"filterComponents" : [elingTag, elingGroupingP,	new CenterPointFilterComponent()],
		"textForNewComment" : Ue["KOMMENTAR_AUSSERSPR_SCHREIBEN"],
		"costumGetNameFunction" : function (key){
			return /** @type{string} */ (ELing[simplifyELingKey(key)][0]);
		},
		"editConfiguration" : polyEditConfig,
		"singleSelect" : true,
		"forbidRemovingFunction" : function (key){
			return optionManager.getOptionState("polymode") === "hex";
		}
		})
);

//"Real" tag translations
for (var i = 0; i < TagValues.length; i++){
	if(Ue[TagValues[i]]){
		categoryManager.addTagTranslation(TagValues[i], Ue[TagValues[i]]);
	}
}
//Pseudo tag translations
categoryManager.addTagTranslation("ERHEBUNG", Ue["ERHEBUNG"]);
