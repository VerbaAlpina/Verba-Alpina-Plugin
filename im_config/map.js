//TODO Änderungsmodus ausblenden für Zitierversion

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
});

jQuery(document).on("im_map_initialized", function (){

	if(ajax_object.db != "xxx")
		categoryManager.addAjaxData("db", ajax_object.db);
	
	categoryManager.addInfoWindowContentConstructor("record", RecordInfoWindowContent);
	
	if (PATH["tk"] == undefined)
		categoryManager.loadData(5, "E17", {"subElementCategory" : -1});
	else
	 	categoryManager.loadSynopticMap(PATH["tk"] * 1);
	
	commentManager.commentTabOpened = /** @param {jQuery} element */ function (element){
		try {
			addBiblioQTips(element);
		}
		catch (/** @type{string} */ e){
			console.log(e);
		}
	}
	
	commentManager.commentTabClosed = /** @param {jQuery} element */ function (element){
		element.find(".bibl").qtip("destroy", true);
	}
	
	/**
	 * @param {number} categoryID
	 * @param {string} elementID
	 */
	commentManager.showCommentMenu = function (categoryID, elementID){
		return ajax_object.db == "xxx";
	};
	
	categoryManager.addAjaxData("outside", false);
	optionManager.addOption("ak", new BoolOption(false, TRANSLATIONS["ALPENKONVENTTION_INFORMANTEN"], function(val) {
		optionManager.enableOptions(false);
		categoryManager.addAjaxData("outside", val);
		legend.reloadMarkers(function (){
			optionManager.enableOptions(true);
		});
	}));
	
	categoryManager.addAjaxData("community", true);
	optionManager.addOption("comm", new BoolOption(true, TRANSLATIONS["AUF_GEMEINDE"], function(val) {
		optionManager.enableOptions(false);
		categoryManager.addAjaxData("community", val);
		legend.reloadMarkers(function (){
			optionManager.enableOptions(true);
		});
	}));
	
	if(ajax_object.va_staff == "1") {
		optionManager.addOption("print", new BoolOption(false, TRANSLATIONS["DRUCKFASSUNG"], function (val){
			
		}));
	}
});


jQuery(document).on("im_legend_before_rebuild", function (event, legend){
	//Remove old qtips
	jQuery("#IM_legend tr td:nth-child(3)").qtip("destroy", true);
});
	
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
	ExtraLing : 5
};

var /**AlphabetSorter */ alphabetSorter = new AlphabetSorter();
var /**RecordNumberSorter */ numRecSorter = new RecordNumberSorter();

var il = new SimpleListBuilder(["name", "description"]);
il.addListPrinter(new JsonListPrinter());
il.addListPrinter(new HtmlListPrinter());
il.addListPrinter(new CsvListPrinter());

var /** FieldType */ stringInput = new StringInputType();

var /** !Object<string, Array<FieldInformation>> */ informantFields = {};
informantFields[google.maps.drawing.OverlayType.MARKER] = [
	new FieldInformation("Erhebung", stringInput, true),
	new FieldInformation("Nummer", stringInput, true),
	new FieldInformation("Ortsname", stringInput, false),
	new FieldInformation("Bemerkungen", stringInput, false)
];


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
		new EditConfiguration (
			informantFields,
			true,
			true,
			true
		)
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

//var /** FlexSearchComponent */ extraLingFlex = new FlexSearchComponent({
//	"start" : 0,
//	"url" : "/wp-content/plugins/flex-search/",
//	"fields" : 
//		/**
//		* @param {number} categoryID
//		* @param {string} elementID
//		* 
//		* @return {Array<{name:string, field:string}>}
//		*/
//		function (categoryID, elementID){
//			var /** {Array<{name:string, field:string}>} */ result = [];
//			var /** Object<string, Array<string>>|null */ tagList = ELing[categoryID][1];
//			if(tagList){
//				for (tag : tagList){
//					result
//				}
//			}
//		}
//});

categoryManager.registerCategory (
	new CategoryInformation (
		categories.ExtraLing,
		"E",
		"",
		"",
		"extraLingSelect",
		undefined,
		undefined,
		Ue["KOMMENTAR_AUSSERSPR_SCHREIBEN"],
		undefined,
		undefined,
		function (key){
			return /** @type{string} */ (ELing[key.substring(1)][0]);
		}
	)
);
