		
		var tr_region = document.getElementById("Region").value;
		if(tr_region == '')
			tr_region = '%';
		var tr_eintrag = document.getElementById("Eintrag").value;
		if(tr_eintrag == '')
			tr_eintrag = '1';
		var tr_modus = document.getElementById("Modus").value;
		var id_informant;
		var id_stimulus = document.getElementById("Karte").value.substring(0, document.getElementById("Karte").value.indexOf('|'));
		var id_aeusserung;
		var aeusserung_alt;
		
		jQuery(document).ready(function (){
			jQuery('select').not(".noChosen").chosen({allow_single_deselect: true});
			
			jQuery("#atlasAuswahl").val(-1);
			jQuery("#Karte").val("");
			mapChanged("");
			
			addNewValueScript ('#konzepteL', "reload", selectModes.Chosen);
		});
		
		
		
		function writeAeusserung (text){
			var kids = jQuery("#konzepteL").val();
			if(text == ''){
				alert("Keine Eingabe vorhanden!");
				return;
			}
			if(text != '<vacat>' && text != '<problem>' && kids == null){
				alert("Kein Konzept ausgewählt!");
				return;
			}
			if(text == '<problem>' && tr_modus == 'extra'){
				alert("Es ist nicht möglich ein Problem hinzuzufügen, wenn bereits ein Eintrag existiert. Nutzen Sie Korrektur!");
				return;
			}
			
			if(text == '<vacat>' && tr_modus == 'extra'){
				alert("Es ist nicht möglich einen <vacat>-Wert hinzuzufügen, wenn bereits ein Eintrag existiert. Nutzen Sie Korrektur!");
				return;
			}
				
			document.getElementById('input_fields').className='informant_details hidden_c';
			var data = {'action' : 'dbtranskr',
						'type' : 'updateTranscription',
						'Id_Stimulus': id_stimulus,
						'Id_Informant': id_informant,
						'Aeusserung': text,
						'Modus': tr_modus,
						'Region': tr_region,
						'Eintrag' : tr_eintrag - 1,
						'Konzept_IDs': kids,
						'Id_Aeusserung' : id_aeusserung,
						'Klasse' :  jQuery("#Klasse").val(),
			};
			jQuery.post(ajaxurl, data, function (response) {
				updateFields(response);
			});
		}
		
		function ajax_info (type){
		
			document.getElementById('input_fields').className='informant_details hidden_c';
			
			var data = {'action' : 'dbtranskr',
						'type' : type,
						'Modus' : tr_modus,
						'Region': tr_region,
						'Eintrag': tr_eintrag - 1,
						'Id_Stimulus' : id_stimulus,
						
			};
			jQuery.post(ajaxurl, data, function (response) {
				updateFields(response);
			});
		}
		
		function updateFields (info){
			var fehler = document.getElementById("fehler");
			
			try {
				var obj = JSON.parse(info);
				var eingabe = document.getElementById('inputAeusserung');
				fehler.className = 'hidden_coll';
				fehler.innerHTML = '';
				document.getElementById("Informant_Info").innerHTML = "<span class='informant_fields'>" + obj.Erhebung + " " + obj.Karte + " - " + obj.Stimulus + "</span> - Informant_Nr <span class='informant_fields'>" + obj.Informant_Nr + "</span> (" + obj.Ortsname + ")";
				aeusserung_alt = obj.Aeusserung;
				id_informant = obj.Id_Informant;
				id_aeusserung = obj.Id_Aeusserung;
				if(tr_modus == 'first' || tr_modus == 'extra')
					eingabe.value='';
				else {
					jQuery("#Klasse").val(obj.Klassifizierung);
					eingabe.value=obj.Aeusserung;
				}
				document.getElementById('input_fields').className='informant_details';
				eingabe.focus();
				jQuery("#konzepteL").val(obj.Konzept_IDs);
				jQuery("#konzepteL").trigger("chosen:updated");
			}
			catch (s){
				document.getElementById('input_fields').className='informant_details hidden_c';
				fehler.innerHTML = info;
				fehler.className = '';			
			}	
		}
		
		function mapChanged (value){
			var pos = value.indexOf('|');
			if(pos != -1){
				id_stimulus = value.substring(0, pos);
				karte = value.substring(pos + 1);
				parent.frames[0].location.href = url + 'scans/' + karte.substring(0, karte.indexOf("#")) + "/" + karte.replace('#', '%23');
				if(karte.substring(0,3) == "SLA"){
					parent.frames[1].location.href = url + 'transkription/TranskriptionsregelnSLA.pdf';
				}
				else {
					parent.frames[1].location.href = url + 'transkription/Codepage_Allgemein.pdf';
				}

				ajax_info('selectKarte');
			}
			else {
				parent.frames[0].location.href=document.getElementById('iframe1').getAttribute('data-default');
				
				document.getElementById('input_fields').className='informant_details hidden_c';
			}
		}
		
		function atlasChanged(value){
			jQuery("#Karte").css("display", "none");
			mapChanged("");
			
			if(value == -1)
				return;
				
			var data = {'action' : 'dbtranskr',
						'type' : 'changeAtlas',
						'atlas' : value
			};
			jQuery.post(ajaxurl, data, function (response){
				jQuery("#Karte").html(response);
				jQuery("#Karte").css("display", "inline");
				mapChanged(jQuery("#Karte").val());
			});
		}
