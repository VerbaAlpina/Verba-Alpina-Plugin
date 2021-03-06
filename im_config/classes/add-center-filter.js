/**
 *
 * @constructor
 * @implements {FilterComponent} 
 * 
 */
function CenterPointFilterComponent (){
	
	/**
	 * @override
	 * 
	 * @param {Object<string, ?>} filterData
	 * @param {Element} element
	 * 
	 * @return {boolean} 
	 */
	this.storeData = function (filterData, element){
		if(jQuery("#centerOption").is(":checked")){
			filterData["addCenterPoints"] = "1";
		}
		if(jQuery("#fragOption").is(":checked")){
			filterData["onlyMultipolygons"] = "1";
		}
		if(jQuery("#outsideOption").is(":checked")){
			filterData["centerOutsideContour"] = "1";
		}
		return true;
	};
	
	/**
	 * @override
	 * 
	 * @param {Object<string, ?>} filterData
	 * 
	 * @return {undefined} 
	 */
	this.storeDefaultData = function (filterData){
		//Do nothing
	};
	
	/**
	*
	* Uses the filter data to re-create the state in which this filter was submitted.
	*
	* @param {Object<string, ?>} data The complete filter data object after storeData has been called for all applicable filters
	* @param {Element} element The DOM element created by getFilterScreenElement.
	* @param {number} categoryId
	* @param {string} elementId
	* 
	* @return {undefined}
	*/
	this.setValues = function (data, element, categoryId, elementId){
		if (data["addCenterPoints"]){
			jQuery("#centerOption").prop("checked", true);
		}
		if (data["onlyMultipolygons"]){
			jQuery("#fragOption").prop("checked", true);
		}
		if (data["centerOutsideContour"]){
			jQuery("#outsideOption").prop("checked", true);
		}
	};
	
	/**
	 * @override
	 * 
	 * @param {number} categoryId
	 * @param {string} elementId
	 * 
	 * @return {Element} 
	 */
	this.getFilterScreenElement = function (categoryId, elementId){
		
		if(elementId.startsWith("A62") || elementId.startsWith("A60") || elementId.startsWith("A17")){
		
			var /** Element */ result = document.createElement("div");
			result["style"]["margin-top"] = "5px";
			
			var /** Element */ centerOption = document.createElement("input");
			centerOption["type"] = "checkbox";
			centerOption["id"] = "centerOption";
			
			result.appendChild(centerOption);
			result.appendChild(document.createTextNode("Mittelpunkte anzeigen"));
			result.appendChild(document.createElement("br"));
			
			var /** Element */ fragOption = document.createElement("input");
			fragOption["type"] = "checkbox";
			fragOption["id"] = "fragOption";
			
			result.appendChild(fragOption);
			result.appendChild(document.createTextNode("Nur Gemeinden aus mehreren Teilen anzeigen"));
			result.appendChild(document.createElement("br"));
			
			var /** Element */ outsideOption = document.createElement("input");
			outsideOption["type"] = "checkbox";
			outsideOption["id"] = "outsideOption";
			
			result.appendChild(outsideOption);
			result.appendChild(document.createTextNode("Nur Gemeinden mit Mittelpunkt außerhalb des Gemeindegebiets anzeigen"));
			result.appendChild(document.createElement("br"));
			
			return result;
		}
		return null;
	};
	
	/**
	 * @override
	 * 
	 * @param {Element} element
	 * 
	 * @return {undefined}
	 * 
	 */
	this.afterAppending = function (element){
		
	};
}