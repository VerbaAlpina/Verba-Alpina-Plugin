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
	 * @return {undefined} 
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
		
		if(elementId == "P62"){
		
			var /** Element */ result = document.createElement("div");
			
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