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
	
	/**
	 * @type {Array<string>} 
	 */
	this.concepts = data["concepts"].length == 0? [""]: data["concepts"];
	
	/**
	 * @type {Array<string>} 
	 */
	this.sources = new Array(this.concepts.length);
	for (var /** number */ i = 0; i < this.concepts.length; i++){
		this.sources[i] = data["source"];
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
		if(this.record.indexOf("TYP") == 0){
			result += "<table style='width : 100%'><tr><td>" + Ue['KEIN_BELEG'] + "</td>";
			if(index == 0 || optionManager.getOptionState("polymode") == "hex")
				result += "<td><h2 class='community singleRecord'>" + this.communityName + "</h2></td>";
			result += "</tr></table>";
		}
		else {
			var /** string */ recordName = hashIndex == -1? escapeHtml(this.record) : escapeHtml(this.record.substring(0, hashIndex)) + "<font color='red'>*</font>";
			result += "<table style='width : 100%'><tr><td><h1 class='singleRecord'>" + recordName + "</h1><div style='display: none'>";
			if (this.encoding == 1){
				result += "Darstellung: IPA " + Ue["QUELLE"];
			}
			else if (this.encoding == 2){
				result += "Darstellung: IPA VA";
			}
			else if (this.encoding == 3){
				result += "Darstellung: DST " + Ue["QUELLE"];
			}
			
			var /** string */ tokenAlone = this.record;
			var /** number */ indexHashes = tokenAlone.indexOf("###");
			if(indexHashes != -1){
				tokenAlone = tokenAlone.substring(0, indexHashes);
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
			
			
			if(this.encoding != 3 && originalString && originalString != tokenAlone){
				result += "<br /><br /><span>DST " + Ue["QUELLE"] + ": </span><span class='originalRecord'>" + escapeHtml(originalString) + "</span>";
			}
			result += "</div><span>(" + Ue['EINZELBELEG'] + ")</span></td>";
			if(index == 0  || optionManager.getOptionState("polymode") == "hex")
				result += "<td><h2 class='community singleRecord'>" + this.communityName + "</h2></td>";
			result += "</tr></table>";
		}
		result += "<br /><br />" + this.typeTable + "<br /><br /><table class='easy-table easy-table-default'><tr><th>" + Ue["QUELLE"] + "</th><th>" + Ue["KONZEPT"] + "</th></tr>";
		
		for (var /** number */ i = 0; i < this.sources.length; i++){
			var /** Array<Array<string>|string|null>> */ conceptArray = Concepts[this.concepts[i].substring(1)];
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
			
			if(conceptName == "" || conceptName == conceptDescription)
				result += "<tr><td class='atlasSource'>" + this.sources[i] + "</td><td>" + conceptDescription + "</td></tr>";
			else
				result += "<tr><td class='atlasSource'>" + this.sources[i] + "</td><td><div class='currentRecordWindowConcept' data-concept-descr='" + conceptDescription+ "'>" + conceptName + "</div></td></tr>";
		}
		
		result += "</table>";
		
		if(hashIndex != -1){
			result += "<br /><font color='red'>* " + Ue["BELEG_TEIL"] + " <b>" + escapeHtml(this.record.substring(hashIndex + 3)) + "</b></font>";
		}
		
		return result + "</div>";
	};
	
	/** 
	 * 
	 * @param {MapSymbol} mapSymbol
	 * @param {LegendElement} owner
	 * 
	 * @return {boolean}
	 *  
	 */
	this.tryMerge = function (mapSymbol, owner){
		for (var /** number */ i = 0; i < mapSymbol.infoWindowContents.length; i++){
			var /**InfoWindowContent */ content = mapSymbol.infoWindowContents[i];
			if(content instanceof RecordInfoWindowContent && owner == mapSymbol.getOwner(i) && content.record == this.record){
				for(var j = 0; j < this.sources.length; j++){
					content.sources.push(this.sources[j]);
					content.concepts.push(this.concepts[j]);
					content.original.push(this.original[0]);
				}
				return true;
			}
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
				"attr" : 'data-concept-descr'
			},
			"position" : {
				"my" : "bottom left",
				"at" : "top left"
			}
		});
		var /** Object */ capi = concepts.qtip("api");
		if(capi != null)
			this.tooltipApis.push(capi);
		
		var /** jQuery */ records = jQuery(content).find(".singleRecord:not(.community)");
		
		records.each(function (){
			var /** jQuery*/ textElement = jQuery(this).next();
			if(textElement.html() != ""){
				jQuery(this).qtip({
					"content" : {
						text : textElement
					},
					"position" : {
						"my" : "top left",
						"at" : "bottom left"
					},
					"style" : {
						"classes" : "qtip-record"
					}
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
}