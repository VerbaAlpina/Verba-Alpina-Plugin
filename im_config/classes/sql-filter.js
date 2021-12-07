/**
 *
 * @constructor
 * @implements {FilterComponent} 
 * 
 * @param {string} defaultVal
 * 
 */
function SQL_Filter (defaultVal){
	
	/**
	 * @const
	 * @type {string}
	 */
	this.defaultVal = defaultVal;
	
	/**
	 * @private
	 * @type{?string} 
	 */
	this.oldID = null;
	
	/**
	 * @override
	 * 
	 * @param {number} categoryId
	 * @param {string} elementId
	 * 
	 * @return {Element} 
	 */
	this.getFilterScreenElement = function (categoryId, elementId){
		var div = document.createElement("div");
		
		var nameField = document.createElement("input");
		nameField["type"] = "text";
		nameField["autocomplete"] = "off";
		nameField["id"] = "va_sql_name";
		nameField["style"]["margin-left"] = "5px";
		
		div.appendChild(document.createTextNode(Ue["NAME"]));
		div.appendChild(nameField);
		
		var text = document.createElement("div");
		text["style"]["margin-top"] = "40px";
		text.appendChild(document.createTextNode("WHERE"));
		
		var icon = document.createElement("i");
		icon["className"] = "far fa-question-circle";
		icon["id"] = "va_sql_help";
		icon["style"]["marginLeft"] = "10px";
		text.appendChild(icon);
		
		div.appendChild(text);
		
		var input = document.createElement("textarea");
		input["style"]["width"] = "500px";
		input["style"]["height"] = "150px";
		input["style"]["margin-top"] = "10px";
		input["style"]["margin-bottom"] = "50px";
		input["id"] = "va_sql_textarea";
		input["autocomplete"] = "off";
		input.appendChild(document.createTextNode(this.defaultVal));
		
		div.appendChild(input);
		
		var headlineGroup = document.createElement("h1");
		headlineGroup.appendChild(document.createTextNode(Ue["GRUPPIERUNG"] + " (" + Ue["OPTIONAL"] + ")"));
		div.appendChild(headlineGroup);
		
		var groupField = document.createElement("input");
		groupField["type"] = "text";
		groupField["autocomplete"] = "off";
		groupField["id"] = "va_sql_group";
		groupField["style"]["margin-left"] = "5px";
		groupField["style"]["width"] = "80%";
		
		var br = document.createElement("br");
		div.appendChild(br);
		
		div.appendChild(document.createTextNode("GROUP BY "));
		div.appendChild(groupField);
		
		var icon2 = document.createElement("i");
		icon2["className"] = "far fa-question-circle";
		icon2["id"] = "va_sql_group_help";
		icon2["style"]["marginLeft"] = "10px";
		text.appendChild(icon2);
		
		div.appendChild(icon2);
		
		div.appendChild(document.createTextNode(Ue["NAME"] + " " + Ue["GRUPPIERUNG"]));
		
		var groupNameField = document.createElement("input");
		groupNameField["type"] = "text";
		groupNameField["autocomplete"] = "off";
		groupNameField["id"] = "va_sql_group_name";
		groupNameField["style"]["margin-left"] = "5px";
		groupNameField["style"]["margin-top"] = "10px";
		
		div.appendChild(groupNameField);
	
		return div;
	};
	
	/**
	 * @override
	 * 
	 * @param {Object<string, ?>} data
	 * 
	 * @return {boolean} 
	 */
	this.storeData = function (data){
		data["where"] = jQuery("#va_sql_textarea").val();
		data["query_name"] = jQuery("#va_sql_name").val();
		data["groupingSQL"] = jQuery("#va_sql_group").val();
		data["groupingName"] = jQuery("#va_sql_group_name").val();
		if (data["groupingSQL"]){
			data["subElementCategory"] = categories.CustomSub;
		}

		/*
		 * Give every new query a new id, except an older query is reloaded
		 */
		if (this.oldID){
			data["id"] = this.oldID;
			this.oldID = null;
		}
		else {
			data["id"] = "SQL" + this.getNextSQLId();
		}

		return true;
	};
	
	/**
	 * @override
	 * 
	 * @param {Object<string, ?>} data
	 * @param {number} categoryId
	 * @param {string} elementId
	 * 
	 * @return {undefined} 
	 */
	this.storeDefaultData = function (data, categoryId, elementId){
		data["where"] = this.defaultVal;
		data["id"] = "SQL" + this.getNextSQLId();
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
		jQuery("#va_sql_textarea").val(data["where"]);
		jQuery("#va_sql_name").val(data["query_name"]);
		jQuery("#va_sql_group").val(data["groupingSQL"]);
		jQuery("#va_sql_group_name").val(data["groupingName"]);
		this.oldID = data["id"];
	};
	
	/**
	 * @override
	 * 
	 * @param {Element} element
	 * @param {number} mainCategoryId
	 * @param {string} elementId
	 * 
	 * @return {undefined}
	 * 
	 */
	this.afterAppending = function (element, mainCategoryId, elementId){
		addMouseOverHelpSingleElement(jQuery(element).find("#va_sql_help"), /** @type{string} */ (jQuery("#va_sql_help_div").html()));
		addMouseOverHelpSingleElement(jQuery(element).find("#va_sql_group_help"), /** @type{string} */ (jQuery("#va_sql_group_help_div").html()));
	};
	
	/**
	 * @private
	 * 
	 * @return {number}
	 */
	this.getNextSQLId = function (){
		
		let maxid = -1;
		for (let i = 0; i < legend.getLength(); i++){
			let /** @type{LegendElement|MultiLegendElement}*/ le = legend.getElement(i);
			if (le.filterData && le.filterData["where"]){
				let sqlid = le.key.substring(3) * 1;
				maxid = Math.max(maxid, sqlid);
			}
		}
		
		return maxid + 1;
	}
}