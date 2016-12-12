/**
 * @constructor
 * @struct
 * 
 */
function DescriptionList (){
	var list = {};
	var currentId = -1;
	
	this.addDescription = function (ajaxObject){
		var description = new TokenDescription (++currentId, ajaxObject["Art"], ajaxObject["Id_Typ"], 
			ajaxObject["Token"], ajaxObject["IPA"], ajaxObject["Original"], ajaxObject["Id_Stimulus"], ajaxObject["Erhebung"], 
			ajaxObject["Genus"], ajaxObject["Konzepte"], ajaxObject["Informanten"], ajaxObject["Id_morph_Typ"],
			ajaxObject["Typ"]);
		list[currentId] = description;
		return description;
	};
	
	this.getDescription = function (id){
		return list[id];
	};
	
	this.remove = function (id){
		delete list[id];
	};
	
		
	this.removeAll = function (){
		list = {};
		currentId = -1;
	};
	
	this.removeDuplicatesOf = function (descr){
		var removed = [];
		for (var id in list){
			var element = list[id];
			if(element.id != descr.id && element.equals(descr)){
				descr.addInformants(element.informants);
				this.remove(id);
				removed.push(id);
			}
		}
		return removed;
	};
	
	this.getOptionsHtml = function (){
		var result = "";
		for (var id in list){
			result += list[id].createOptionHtml();
		}
		return result;
	};
	
	this.changeTypeName = function (id_vatype, newTypeName){
		for (var id in list){
			var element = list[id];
			if(element.id_vatype == id_vatype){
				element.vatype = newTypeName;
			}
		}
	};
}

/**
 * @constructor
 * @struct
 * 
 * @param {number} id
 * @param {string} kind
 * @param {number} id_type
 * @param {string} token
 * @param {string} ipa
 * @param {string} original
 * @param {number} id_stimulus
 * @param {string} source
 * @param {string} gender
 * @param {Array<number>|null} concepts
 * @param {string} informants
 * @param {number} id_vatype
 * @param {number} vatype
 */
function TokenDescription (id, kind, id_type, token, ipa, original, id_stimulus, source, gender, concepts, informants, id_vatype, vatype){
	this.id = id;
	this.kind = kind;
	this.id_type = id_type;
	
	if(kind == "T" || kind == "G"){
		var first = original? original : token;
		var second = ipa? ' --- ' + ipa : '';
		var third = ' (' + (gender == ''? '?': gender) + ')';
		this.name = first + second + third;
	}
	else {
		this.name = token + ' (' + (gender == ''? '?': gender) + ')';
	}
	
	this.token = token;
		
	this.id_stimulus = id_stimulus;
	this.source = source;
	this.gender = gender;
	this.concepts = concepts == null? []: concepts.split(",");
	this.conceptLoadingList = [];
	
	this.informants = informants;
	
	this.vatype = vatype;
	this.id_vatype = id_vatype;
	
	this.shortenInformants = function (){
		if(this.informants.length > 50){
			var sub = this.informants.substring(0,50);
			var lastSem = sub.lastIndexOf(",");
			this.informants = this.informants.substring(0, lastSem) + ",...";
		}
	}
	this.shortenInformants();
	
	this.createOptionHtml = function (){
		var style = "";
		if(this.vatype == null){
			style += "font-weight : bold;";
		}
		if(this.concepts.length == 0){
			style += "font-style: italic;";
		}
		return '<option value="' + this.id + '" style="' + style + '">' + this.name + '</option>';
	};
	
	this.createTableRow = function (){
		var result = "<tr data-id-description='" + this.id + "'>";
		result += "<td style='font-size: 16px;'>" + this.name + "</td>";
		result += "<td>" + this.informants + "</td>";
		var conceptList = this.concepts.map(this.getConceptName.bind(this));
		result += "<td>" + conceptList.join("") + "</td>";
		if(this.vatype == null)
			result += "<td></td>";
		else if (this.vatype == "---LOADING---")
			result += "<td><img src='" + loadingUrl + "' /></td>";
		else
			result += "<td><span class='chosen-like-button'><span>" + this.vatype + "</span><a class='deleteTypification' /></span></td>";
		result += "</tr>";
		return result;
	};
	
	this.getConceptName = function (id){
		if(id){
			if(this.conceptLoadingList.indexOf(id) === -1){
				return "<span class='chosen-like-button' id='" + id + "'><span>" 
					+ jQuery("#konzeptAuswahl option[value=" + id + "]").text() 
					+ "</span><a class='deleteConcept' /></span>";
			}
			else {
				return "<img src='" + loadingUrl + "' />";
			}
		}
		return "";
	};
	
	this.setConceptLoading = function (id, loading){
		if(loading){
			this.conceptLoadingList.push(id);
		}
		else {
			var ind = this.conceptLoadingList.indexOf(id);
			if(ind !== -1){
				this.conceptLoadingList.splice(ind, 1);
			}
		}
	};
	
	this.equals = function (obj){
		return this.token == obj.token && this.id_stimulus == obj.id_stimulus && this.gender == obj.gender && 
			this.kind == obj.kind && this.id_type == obj.id_type && this.id_vatype == obj.id_vatype && arraysEqual(this.concepts, obj.concepts);
	};
	
	this.removeConcept = function (id){
		this.concepts.splice(this.concepts.indexOf(id), 1);
		
		var ind = this.conceptLoadingList.indexOf(id);
		if(ind !== -1){
			this.conceptLoadingList.splice(ind, 1);
		}
	};
	
	this.addConcept = function (id){
		this.concepts.push(id);
		this.concepts.sort(function (a, b){
			return a * 1 - b * 1;
		});
	};
	
	this.hasConcept = function (id){
		return this.concepts.indexOf(id) !== -1;
	};
	
	this.addInformants = function (str){
		str = str.replace(",...", "");
		var strOld = this.informants.replace(",...", "");
		var numsAll = strOld.split(",").concat(str.split(","));
		numsAll.sort();
		this.informants = numsAll.join(",");
		this.shortenInformants();
	};
	
	this.getLockName = function (){
		return this.token + "%%%" + this.gender + "%%%" + this.id_stimulus;
	}
}

function callbackSaveReference (data){
	var genderInfo = " (" + data["Genera"] + ")";
	jQuery('#auswahlReferenz').append("<option value='" + data["id"] + "'>" + data["Quelle"] + ": " + data["Subvocem"] + (genderInfo != " ()"? genderInfo: "") + "</option>").trigger("chosen:updated");
}

function callbackSaveBaseType (data){
	jQuery('#auswahlBasistyp').append("<option value='" + data["id"] + "'>" + data["Orth"] + "</option>").trigger("chosen:updated");
}

function setMorphTypeData (data){
	var e = document.forms["eingabeMorphTyp"].elements;
	
	jQuery(e["Orth"]).val(data.type.Orth);
	jQuery(e["Sprache"]).val(data.type.Sprache).trigger("chosen:updated");
	jQuery(e["Wortart"]).val(data.type.Wortart).trigger("chosen:updated");
	jQuery(e["Affix"]).val(data.type.Affix);
	jQuery(e["Genus"]).val(data.type.Genus).trigger("chosen:updated");
	jQuery(e["Kommentar_Intern"]).val(data.type.Kommentar_Intern);

	jQuery("#auswahlBestandteile").val(data.parts).trigger("chosen:updated");
	jQuery("#auswahlReferenz").val(data.refs).trigger("chosen:updated");
	jQuery("#auswahlBasistyp").val(data.btypes).trigger("chosen:updated");
}

function getMorphTypeData (id){
	var data = {};
	
	data.action = "va";
	data.namespace = "typification";
	data.query = "saveMorphType";
	data.dbname = dbname;
	
	data.id = id;
	
	data.type = {};
	
	var e = document.forms["eingabeMorphTyp"].elements;
	
	data.type.Orth = e["Orth"].value;
	data.type.Sprache = e["Sprache"].value;
	data.type.Wortart = e["Wortart"].value;
	data.type.Affix = e["Affix"].value;
	data.type.Genus = e["Genus"].value;
	data.type.Kommentar_Intern = e["Kommentar_Intern"].value;

	data.parts = jQuery("#auswahlBestandteile").val();
	data.refs = jQuery("#auswahlReferenz").val();
	data.btypes = jQuery("#auswahlBasistyp").val();
	
	return data;
}

function openMorphTypeDialog(){
	jQuery("#saveCaller").val(this.id);
	var e = document.forms["eingabeMorphTyp"].elements;
	for (var i = 0; i < e.length; i++){
		jQuery(e[i]).val("");
	}
	
	jQuery("#auswahlBestandteile").val([]);
	jQuery("#auswahlReferenz").val([]);
	jQuery("#auswahlBasistyp").val([]);
	
	jQuery('#VATypeOverlay').dialog({
		"minWidth" : 700,
		"modal": true,
		"close" : function (){
			jQuery("#VATypeOverlay select").chosen("destroy");
		}
	});
	
	jQuery("#VATypeOverlay form[name=eingabeMorphTyp] select").chosen({"allow_single_deselect" : true, "width": "165px"});
	jQuery("#VATypeOverlay select[multiple=multiple]").chosen({"allow_single_deselect" : true, "width": "600px"});
}

function closeMorphDialog (){
	jQuery("#VATypeOverlay").dialog("close");
}
