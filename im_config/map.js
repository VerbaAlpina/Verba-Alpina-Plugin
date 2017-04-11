ajaxurl = ajax_object.ajaxurl;

jQuery(function (){
	var/** number */ gm_height = (window.innerHeight - (document.getElementById("content").offsetTop) / 2);
	jQuery("#IM_googleMap").css("height", gm_height  + "px");
	jQuery("#IM_legend").css("height", gm_height - 311  + "px");
	
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
});

jQuery(document).on("im_map_initialized", function (){

	if(ajax_object.db != "xxx")
		categoryManager.addAjaxData("db", ajax_object.db);
	
	categoryManager.addInfoWindowContentConstructor("record", RecordInfoWindowContent);
	
	if (PATH["tk"] == undefined)
		categoryManager.loadData(5, "P17", {"subElementCategory" : -1});
	else
	 	categoryManager.loadSynopticMap(PATH["tk"] * 1);
	
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
	
	if(ajax_object.va_staff == "1") {
		var /** BoolOption */ printOption = new BoolOption(false, TRANSLATIONS["DRUCKFASSUNG"], function (val){
			if(val){
				map.setMapTypeId(google.maps.MapTypeId.ROADMAP);
				map.setOptions({"styles": emptyMapStyle});
			}
			else {
				map.setMapTypeId(google.maps.MapTypeId.TERRAIN);
   		  		map.setOptions({styles: {}});
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


/**@enum {number}*/
var categories = {
	Informant : 0,
	Concept : 1,
	PhoneticType: 2,
	MorphologicType : 3,
	BaseType : 4,
	ExtraLing : 5,
	Polygons : 6
};

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
	new CategoryInformation (
		categories.Informant, 
		"I",
		Ue["INFORMANTEN"],
		"", 
		"informantSelect", 
		undefined,
		undefined, 
		Ue["KOMMENTAR_INFORMANT_SCHREIBEN"],
		"Informanten-Daten exportieren",
		il,
		undefined,
		informatEditConfig
	)
);

categoryManager.registerCategory (
	new CategoryInformation (
		categories.PhoneticType,
		"P",
		Ue["PHON_TYP"],
		Ue["NICHT_TYPISIERT"],
		"phonTypeSelect",
		[new GroupingComponent([categories.Concept], categories.Concept, new Sorter([alphabetSorter, numRecSorter]))],
		[Ue["BELEG"], Ue["BELEGE"]],
		Ue["KOMMENTAR_PTYP_SCHREIBEN"]
	)
);

categoryManager.registerCategory (
	new CategoryInformation (
		categories.MorphologicType,
		"L",
		Ue["MORPH_TYP"],
		Ue["NICHT_TYPISIERT"],
		"morphTypeSelect",
		[new GroupingComponent([categories.PhoneticType, categories.Concept], categories.Concept, new Sorter([alphabetSorter, numRecSorter]))],
		[Ue["BELEG"], Ue["BELEGE"]],
		Ue["KOMMENTAR_MTYP_SCHREIBEN"]
	)
);

categoryManager.registerCategory (
	new CategoryInformation (
		categories.BaseType,
		"B",
		Ue["BASISTYP"],
		Ue["NICHT_TYPISIERT"],
		"baseTypeSelect",
		[new GroupingComponent([categories.PhoneticType, categories.MorphologicType, categories.Concept], categories.Concept, new Sorter([alphabetSorter, numRecSorter]))],
		[Ue["BELEG"], Ue["BELEGE"]],
		Ue["KOMMENTAR_BASIS_SCHREIBEN"]
	)
);

var /** function(number,string,number,number):boolean */ conceptSorterFunc = function (mainCategoryId, elementId, subCategoryId, filterId){
	return filterId != 1 || subCategoryId == categories.MorphologicType;
};

categoryManager.registerCategory (
	new CategoryInformation (
		categories.Concept,
		"C",
		Ue["KONZEPT"],
		Ue["KEIN_KONZEPT"],
		"conceptSelect",
		[new ConceptFilterComponent(), new GroupingComponent(
				[categories.PhoneticType, categories.MorphologicType, categories.BaseType], 
				categories.MorphologicType, 
				new Sorter([alphabetSorter, new LanguageFamilySortComponent (), numRecSorter]), 
				conceptSorterFunc
				)],
		[Ue["BELEG"], Ue["BELEGE"]],
		Ue["KOMMENTAR_KONZEPT_SCHREIBEN"],
		undefined,
		undefined,
		function (key){
			key = key.substring(1); //Remove prefix
			return Concepts[key][0] == ""? /** @type{string} */ (Concepts[key][1]): /** @type{string} */ (Concepts[key][0]);
		}
	)
);

var /** function (number, string) : Object<string, Array<string>> */ elingTagFunction =  function (categoryID, elementID){
	return  /** @type{Object<string, Array<string>>} */ (ELing[elementID.substring(1)][1]);
};

var /** function (number, string): Array<{tag:string, name:string}> */ elingGroupFunction = function (categoryID, elementID){
	var /**Array<{tag:string, name:string}>*/ result = [];
	var /** Object<string, Array<string>>}*/ tagList = /** @type{Object<string, Array<string>>} */ (ELing[elementID.substring(1)][1]);
	if(tagList){
		for (var tagKey in tagList){
			var /**string*/ translTag = Ue[tagKey];
			result.push({tag : tagKey, name : (translTag? translTag : tagKey)});
		}
	}
	
	return result;
};

var /** TagComponent */ elingTag = new TagComponent(elingTagFunction);
var /** GroupingComponent */ elingGroupingE = new GroupingComponent([], undefined, undefined, undefined, elingGroupFunction)

categoryManager.registerCategory (
	new CategoryInformation (
		categories.ExtraLing,
		"E",
		Ue["AUSSERSPR"],
		"",
		"extraLingSelect",
		[
			elingTag, 
			elingGroupingE
		],
		undefined,
		Ue["KOMMENTAR_AUSSERSPR_SCHREIBEN"],
		undefined,
		undefined,
		function (key){
			return /** @type{string} */ (ELing[key.substring(1)][0]);
		}
	)
);

var /** GroupingComponent */ elingGroupingP = new GroupingComponent(function (categoryID, elementID){
	var /**Array<number>*/ result = [];
	
	if(ajax_object.va_staff == "1" && elementID == "P62"){
		result.push(-4);
	}
	
	return result;
}, undefined, undefined, undefined, elingGroupFunction);

var /** EditConfiguration */ polyEditConfig = new EditConfiguration();
polyEditConfig.allowGeoDataChange(OverlayType.PointSymbol, function (elementID){
	return elementID == "P62";
});

categoryManager.registerCategory (
		new CategoryInformation (
			categories.Polygons,
			"P",
			"Polygone", //TODO translation
			"",
			"polygonSelect",
			[
				elingTag, 
				elingGroupingP,
				new CenterPointFilterComponent()
			],
			undefined,
			Ue["KOMMENTAR_AUSSERSPR_SCHREIBEN"],
			undefined,
			undefined,
			function (key){
				return /** @type{string} */ (ELing[key.substring(1)][0]);
			},
			polyEditConfig
		)
	);

for (var i = 0; i < TagValues.length; i++){
	categoryManager.addElementName("#" + TagValues[i], (Ue[TagValues[i]]? Ue[TagValues[i]] : TagValues[i]));
}
