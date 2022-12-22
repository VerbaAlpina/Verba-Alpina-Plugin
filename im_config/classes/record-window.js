/**
 * @constructor
 * @struct
 * @implements {InfoWindowContent}
 * 
 * @param {number} categoryID
 * @param {string} elementID
 * @param {OverlayType} overlayType
 * @param {Object<string, ?>} data
 */
function RecordInfoWindowContent (categoryID, elementID, overlayType, data){
	
	/**
	 * @type {number}
	 */
	this.numRecords = 1;
	
	/**
	 * @type {string} record 
	 */
	this.record = data["record"];
	
	/**
	 * @type {Array<string>}
	 */
	this.original = [data["original"]];
	
	/**
	 * @type {number}
	 */
	this.encoding = data["encoding"] * 1;


	var concepts = data["concepts"].length == 0? false: data["concepts"].slice(0);
	
	/**
	* @type{boolean|string}
	*/
	this.meaningLang = concepts === false? data["meanings"][0]: false; //Only set meaning lang if there are no concepts, since it it used to determine if concepts or meanings are shown
	
	if (concepts === false){
		if (this.meaningLang){
			concepts = data["meanings"][1];
		}
		else {
			concepts = [""];
		}
	}
	
	/**
	 * @type {Array<string>} 
	 */
	this.concepts = concepts;
	
	/**
	 * @type {Array<string>} 
	 */
	this.sources = new Array(this.concepts.length);
	this.sources[0] = data["source"];
	for (var /** number */ i = 1; i < this.concepts.length; i++){
		this.sources[i] = "";
	}
	
	/** 
	 * @type {string} 
	 */
	this.typeTable = data["typeTable"];
	
	/**
	 * @type {string} 
	 */
	this.communityName = data["community"];
	
	/**
	 * @type {string} 
	 */
	this.geonamesID = data["geonames"];
	
	/**
	* @type {string}
	*/
	this.ex_id = data["external_id"];
	
	/**
	 * @type {Array<Object>}
	 */
	this.tooltipApis = [];
	
	/** 
	 * @override
	 * 
	 * @param {number} index
	 * 
	 * @return {string} 
	 */
	this.getHtml = function (index){

		var /** string */ result = "<div>";
		var /** number */ hashIndex = this.record.indexOf("###");

		var /** string */ crecord = hashIndex == -1? this.record : this.record.substring(0, hashIndex);
		if (this.encoding == 4){
			crecord = escapeHtml(crecord);
		}
		
		var geonames = this.geonamesID? "<a target='_BLANK' href='https://www.geonames.org/" + this.geonamesID + "'><img class='geonamesLogo' src='" + ajax_object["plugin_url"] + "/images/geonames-icon.svg' /></a>": "";
		
		if(crecord.substring(1,4) == "TYP"){
			result += "<table style='width : 100%'><tr><td>" + Ue['KEIN_BELEG'] + "</td>";
			if(index == 0 || optionManager.getOptionState("polymode") == "hex"){
				result += "<td><h2 class='community singleRecord'>" + this.communityName + geonames + "</h2></td>";
			}
			result += "</tr></table>";
		}
		else {
			var /** string */ recordName = hashIndex == -1? crecord : crecord + "<font color='red'>*</font>";
			result += "<table style='width : 100%'><tr><td><h1 class='singleRecord'>" + recordName + "</h1><div style='display: none'>";
			if (this.encoding == 1){
				result += "Darstellung: IPA " + Ue["QUELLE"];
			}
			else if (this.encoding == 2){
				result += "Darstellung: IPA VA";
			}
			else {
				result += "Darstellung: DST " + Ue["QUELLE"];
			}
			
			var /** string */ originalString;
			var /** string */ firstOriginal = this.original[0];
			var /** boolean */ allIdentical = true;
			var /** boolean */ emptyValues = false;
			for (var j = 0; j < this.original.length; j++){
				if(!this.original[j]){
					emptyValues = true;
					break;
				}
				if(this.original[j] != firstOriginal){
					allIdentical = false;
				}
			}
			
			if(!emptyValues){
				if(allIdentical){
					originalString = firstOriginal;
				}
				else {
					originalString = this.original.join(" / ");
				}
			}

			if(this.encoding < 3 && originalString){
				result += "<br /><br /><span>DST " + Ue["QUELLE"] + ": </span><span class='originalRecord'>" + originalString + "</span>";
			}
			
			result += "<br /><br />VA-ID: " + this.ex_id;
			result += "<br /><br /><a href='" + ajax_object.site_url +"?api=1&action=getRecord&id=" + this.ex_id + "&version=" + (ajax_object.db == "xxx"? ajax_object.max_db: ajax_object.db) + "' target='_BLANK' style='margin: 5px;'><i class='list_export_icon fas fa-file-download' title='" + Ue["API_LINK_RECORD"] + "'></i></a>";
			
			result += "</div><span>(" + Ue['EINZELBELEG'] + ")</span></td>";
			if(index == 0  || optionManager.getOptionState("polymode") == "hex")
				result += "<td><h2 class='community singleRecord'>" + this.communityName + geonames + "</h2></td>";
			result += "</tr></table>";
		}
		
		if (this.meaningLang){
			var meaningName = Ue["BEDEUTUNG"] + " " + Ue["QUELLE"] + " (" + this.meaningLang + ")";
		}
		else {
			var meaningName = Ue["KONZEPT"];
		}
		
		result += "<br /><br />" + this.typeTable + "<br /><br /><table class='easy-table easy-table-default va_record_source_table'><tr><th>" + Ue["QUELLE"] + "</th><th>" + meaningName + "</th></tr>";
		
		for (var /** number */ i = 0; i < this.sources.length; i++){
			if (this.concepts[i].match(/^C[0-9]+$/)){
				var /** string */ cid = this.concepts[i].substring(1);
				var /** Array<Array<string>|string|null>> */ conceptArray = Concepts[cid];
				var /**string */ conceptName;
				var /**string */ conceptDescription;
				if(conceptArray !== undefined){
					conceptName = /** @type {string} */ (conceptArray[0]);
					conceptDescription = /** @type {string} */ (conceptArray[1]);
				}
				else {
					conceptName = "";
					conceptDescription = Ue["KEIN_KONZEPT"];
				}
				
				var /** string */ wdataLink = "";
				if(QIDS[cid]){
					wdataLink = " <a target='_BLANK' href='https://www.wikidata.org/wiki/Q" + QIDS[cid] + "'>(Wikidata)</a>";
				}
				
				let cdescr;
				if (conceptName != "" && conceptName != conceptDescription){
					cdescr = conceptDescription.replace("'", "&apos;");
				}
				else {
					cdescr = "";
					conceptName = conceptDescription;
				}
				
				result += "<tr><td class='atlasSource'>" + this.sources[i] + "</td><td><span class='currentRecordWindowConcept' data-id='" + cid + "' data-concept-descr='" + cdescr + "'>" + conceptName + "</span>" + wdataLink + "</td></tr>";
			}
			else {
				result += "<tr><td class='atlasSource'>" + this.sources[i] + "</td><td>„" + this.concepts[i] + "“</td></tr>";
			}
				
		}
		
		result += "</table>";
		
		if(hashIndex != -1){
			result += "<br /><span class='fullRecordInfo'>* " + Ue["BELEG_TEIL"] + " <span>" + this.record.substring(hashIndex + 3) + "</span></font>";
		}
		
		return result + "</div>";
	};
	
	/** 
	 * 
	 * @param {InfoWindowContent} oldContent
	 * 
	 * @return {boolean}
	 *  
	 */
	this.tryMerge = function (oldContent){
		if(oldContent instanceof RecordInfoWindowContent && oldContent.record == this.record){
			for(var j = 0; j < this.sources.length; j++){
				oldContent.sources.push(this.sources[j]);
				oldContent.concepts.push(this.concepts[j]);
				oldContent.original.push(this.original[0]);
			}
			oldContent.numRecords++;
			return true;
		}
		return false;
	};
	
	/**
	 * @override
	 * 
	 * @param {Element} content
	 * 
	 * @return {undefined} 
	 */
	this.onOpen = function (content){

		var /** jQuery*/ concepts = jQuery(content).find(".currentRecordWindowConcept");
		concepts.qtip({
			"content" : {
				text : function (){
					let res = "";
					if (jQuery(this).data("concept-descr")){
						res += jQuery(this).data("concept-descr") + "<br /><br />";
					}
					res += "VA-ID: C" + jQuery(this).data("id");
					res += "<br /><br /><a href='" + ajax_object.site_url +"?api=1&action=getRecord&id=C" + jQuery(this).data("id") + "&version=" + (ajax_object.db == "xxx"? ajax_object.max_db: ajax_object.db) + "' target='_BLANK' style='margin: 5px;'><i class='list_export_icon fas fa-file-download' title='" + Ue["API_LINK_CONCEPT"] + "'></i></a>";
					res += "<br /><br /><a href='" + ajax_object["lex_path"] + "#C" + jQuery(this).data("id") + "' target='_BLANK'>" + Ue["LEX_ALP_REF"] + "</a>";
					return res;
				},
				title: {
					button: true // Close button
				}
			},
			"position" : {
				"my" : "bottom left",
				"at" : "top left"
			},
			"events": {
				"render":
				/**
				 * @param {Object} event
				 * @param {Object} api
				 */
				function(event, api) {
					api.elements.target.bind('click', function() {
						api.set('hide.event', false);
					});
				},
				"hide": 
				/**
				 * @param {Object} event
				 * @param {Object} api
				 */
				function(event, api) {
					api.set('hide.event', 'mouseleave');
				}
			}
		});
		var /** Object */ capi = concepts.qtip("api");
		if(capi != null)
			this.tooltipApis.push(capi);
		
		var /** jQuery */ records = jQuery(content).find(".singleRecord:not(.community)");
		
		records.each(function (){
			var /** jQuery*/ textElement = jQuery(this).next().clone();
			if(textElement.html() != ""){
				jQuery(this).qtip({
					"content" : {
						text : textElement,
						title: {
							button: true // Close button
						}
					},
					"position" : {
						"my" : "top left",
						"at" : "bottom left"
					},
					"style" : {
						"classes" : "qtip-record"
					},
					"events": {
						"render":
						/**
						 * @param {Object} event
						 * @param {Object} api
						 */
						function(event, api) {
							api.elements.target.bind('click', function() {
								api.set('hide.event', false);
							});
						},
						"hide": 
						/**
						 * @param {Object} event
						 * @param {Object} api
						 */
						function(event, api) {
							api.set('hide.event', 'mouseleave');
						}
					},
				});
			}
		});
		
		var /** RecordInfoWindowContent */ thisObject = this;
		records.each(function (){
			thisObject.tooltipApis.push(jQuery(this).qtip("api"));
		});

		//Listener for multiple typings
		jQuery(content).find(".infoWindowTypeSelect").each(/** @this{Element} */ function (){
			jQuery(this).change(/** @this{Element} */ function(){
				//TODO use class or something
				jQuery(this).parent().parent().children().eq(1).html(/** @type{string} */ (jQuery(this).find("option:selected").data("tname")));
			});
		});
		
		var /** Array<Object>*/ apis = addBibLikeQTips(jQuery(content).find(".va_record_source_table"), ["bibl", "stimulus", "informant"], ["blue", "blue", "blue"], ["", "sti", "inf"]);
		for (let i = 0; i < apis.length; i++){
			thisObject.tooltipApis.push(apis[i]);
		}
		
		apis = addBibLikeQTips(jQuery(content).find(".va_type_table"), ["iso"], ["light"], ["ISO_"]);
		for (let i = 0; i < apis.length; i++){
			thisObject.tooltipApis.push(apis[i]);
		}
		
		let communities = jQuery(content).find(".community");
		communities.each(function (){
			jQuery(this).qtip({
				"content" : {
					text : function (){
						let res = "VA-ID: A" + data['id_community'];
						if (data["comm_download"]){
							res += data["comm_download"];
						}
						return res;
					},
					title: {
						button: true // Close button
					}
				},
				"position" : {
					"my" : "top left",
					"at" : "bottom left"
				},
				"style" : {
					"classes" : "qtip-record"
				},
				"events": {
					"render":
					/**
					 * @param {Object} event
					 * @param {Object} api
					 */
					function(event, api) {
						api.elements.target.bind('click', function() {
							api.set('hide.event', false);
						});
					},
					"hide": 
					/**
					 * @param {Object} event
					 * @param {Object} api
					 */
					function(event, api) {
						api.set('hide.event', 'mouseleave');
					}
				}
			});
		});
		communities.each(function (){
			thisObject.tooltipApis.push(jQuery(this).qtip("api"));
		});
		
		let lexTypes = jQuery(content).find(".va_lex_type");
		lexTypes.each(function (){
			jQuery(this).qtip({
				"content" : {
					text : function (){
						let res = "VA-ID: L" + jQuery(this).data("id");
						res += "<br /><br /><a href='" + ajax_object.site_url +"?api=1&action=getRecord&id=L" + jQuery(this).data("id") + "&version=" + (ajax_object.db == "xxx"? ajax_object.max_db: ajax_object.db) + "' target='_BLANK' style='margin: 5px;'><i class='list_export_icon fas fa-file-download' title='" + Ue["API_LINK_LEX"] + "'></i></a>";
						res += "<br /><br /><a href='" + ajax_object["lex_path"] + "#L" + jQuery(this).data("id") + "' target='_BLANK'>" + Ue["LEX_ALP_REF"] + "</a>";
						
						let lidString = jQuery(this).data("lids") + "";
						if (lidString){
							res += "<br /><br />Wikidata: ";
							let lids = lidString.split(",");
							for (let i = 0; i < lids.length; i++){
								if (i > 0){
									res += ", ";
								}
								res += "<a target='_BLANK' href='https://www.wikidata.org/wiki/Lexeme:L" + lids[i] + "'>L" + lids[i] + "</a>";
							}
						}
						
						return res;
					},
					title: {
						button: true // Close button
					}
				},
				"position" : {
					"my" : "top left",
					"at" : "bottom left"
				},
				"style" : {
					"classes" : "qtip-record"
				},
				"events": {
					"render":
					/**
					 * @param {Object} event
					 * @param {Object} api
					 */
					function(event, api) {
						api.elements.target.bind('click', function() {
							api.set('hide.event', false);
						});
					},
					"hide": 
					/**
					 * @param {Object} event
					 * @param {Object} api
					 */
					function(event, api) {
						api.set('hide.event', 'mouseleave');
					}
				}
			});
		});
		lexTypes.each(function (){
			thisObject.tooltipApis.push(jQuery(this).qtip("api"));
		});
		
		let baseTypes = jQuery(content).find(".va_base_type");
		baseTypes.each(function (){
			jQuery(this).qtip({
				"content" : {
					text : function (){
						let res = "VA-ID: B" + jQuery(this).data("id");
						res += "<br /><br /><a href='" + ajax_object.site_url +"?api=1&action=getRecord&id=B" + jQuery(this).data("id") + "&version=" + (ajax_object.db == "xxx"? ajax_object.max_db: ajax_object.db) + "' target='_BLANK' style='margin: 5px;'><i class='list_export_icon fas fa-file-download' title='" + Ue["API_LINK_BASE"] + "'></i></a>";
						res += "<br /><br /><a href='" + ajax_object["lex_path"] + "#B" + jQuery(this).data("id") + "' target='_BLANK'>" + Ue["LEX_ALP_REF"] + "</a>";
						return res;
					},
					title: {
						button: true // Close button
					}
				},
				"position" : {
					"my" : "top left",
					"at" : "bottom left"
				},
				"style" : {
					"classes" : "qtip-record"
				},
				"events": {
					"render":
					/**
					 * @param {Object} event
					 * @param {Object} api
					 */
					function(event, api) {
						api.elements.target.bind('click', function() {
							api.set('hide.event', false);
						});
					},
					"hide": 
					/**
					 * @param {Object} event
					 * @param {Object} api
					 */
					function(event, api) {
						api.set('hide.event', 'mouseleave');
					}
				}
			});
		});
		baseTypes.each(function (){
			thisObject.tooltipApis.push(jQuery(this).qtip("api"));
		});
	};
	
	/**
	 * @override
	 * 
	 * @param {Element} content
	 * 
	 * @return {undefined} 
	 */
	this.onClose = function (content){
		for(var i = 0; i < this.tooltipApis.length; i++){
			if(this.tooltipApis[i])
				this.tooltipApis[i]["destroy"](true);
		}
	};
	
	/**
	 * @override
	 * 
	 * @return {Array<Object<string, string>>} 
	 */
	this.getData = function () {
		return []; //TODO implement
	};
	
	/**
	 * @override
	 * 
	 * @return {string}
	 */
	this.getName = function (){
		return "";
	};
	
	/**
	*
	* @override
	*
	* @return {undefined} 
	*/
	this.resetState = function (){
		this.original = [data["original"]];
		this.concepts = data["concepts"].length == 0? [""]: data["concepts"].slice(0);
		this.sources = new Array(this.concepts.length);
		this.sources[0] = data["source"];
		for (var /** number */ i = 1; i < this.concepts.length; i++){
			this.sources[i] = "";
		}
		this.numRecords = 1;
	};
	
	/**
	 * @override
	 * 
	 * @return {number}
	 */
	this.getNumElements = function (){
		return this.numRecords;
	};
}