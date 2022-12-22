<?php
function va_lexicon($attrs){
    
    $meth_list = false;
    if (isset($attrs['type']) && $attrs['type'] == 'methodology'){
        $meth_list = true;
    }
	
	global $vadb;
	global $lang;
	global $Ue;
	
	$mun_list = va_is_municipality_list();
	//$meth_list = va_is_methodology();
?>
	
	
	
<script type="text/javascript">
	var isMethodology = <?php echo $meth_list? 'true': 'false'; ?>;

	var qtipApis = {};
  	var all_active_ids = {};
  	var ids_to_remove = {};
	var ids_to_idx = {};
  	var append_alphabetically = true;
	var all_data = [];
	var filtered_data = [];
	var clusterize;
	var getting_sec_data = false;
	var searching = false;
	var stateUrl = "<?php  global $wp; echo add_query_arg('state', '§§§', add_query_arg( $_SERVER['QUERY_STRING'], '', home_url( $wp->request )));?>";
	var clear_titles = [];
	var zoomWarning = "<?php echo $Ue['ZOOM_WARNING']; ?>"
	var loadingCounter = 0;
	var saved_left = null;
	var prevent_tour_click = false;
	var blockimages = false;
	
	var urlParams = new URLSearchParams(window.location.search);
	
	jQuery(document).ready(function () {

		jQuery('.lmu_signum_bg').hide();

		var translations_help = {};

		translations_help['back'] = "<?php echo $Ue['LEX_BACK']; ?>";
		translations_help['next'] = "<?php echo $Ue['LEX_NEXT']; ?>";
		translations_help['end_tour'] = "<?php echo $Ue['LEX_ENDTOUR']; ?>";
		translations_help['step_1'] = "<?php echo $Ue['LEX_STEP_1']; ?>";
		translations_help['step_2'] = "<?php echo $Ue['LEX_STEP_2']; ?>";
		translations_help['step_3'] = "<?php echo $Ue['LEX_STEP_3']; ?>";
		translations_help['step_4'] = "<?php echo $Ue['LEX_STEP_4']; ?>";
		translations_help['step_5'] = "<?php echo $Ue['LEX_STEP_5']; ?>";
		translations_help['step_6'] = "<?php echo $Ue['LEX_STEP_6']; ?>";
		translations_help['step_7'] = "<?php echo $Ue['LEX_STEP_7']; ?>";
		translations_help['step_8'] = "<?php echo $Ue['LEX_STEP_8']; ?>";
		translations_help['step_9'] = "<?php echo $Ue['LEX_STEP_9']; ?>";
		translations_help['step_10'] = "<?php echo $Ue['LEX_STEP_10']; ?>";
		translations_help['step_11'] = "<?php echo $Ue['LEX_STEP_11']; ?>";
		translations_help['step_12'] = "<?php echo $Ue['LEX_STEP_12']; ?>";

		addCopyButtonSupport();
		addScrollShift();
		addSideBarCollapse();
		
		<?php
		if (!$meth_list){
		?>
		var tour = addHelpTourLex(translations_help);

		tour.on("cancel", function(){
			completeReset(true, true);
		})
		<?php
		}
		?>

		
		jQuery('#lextitelinput').val('');

		jQuery('.lexstartcontent').fadeIn('fast',function(){
			addPopups(jQuery(this));
		});

		jQuery('#page').addClass('lex');
		jQuery('body').addClass('lex');

		if(isMethodology)jQuery('body').addClass('meth');


		jQuery('.lexsearch button').first().on('click',function(){
			var val = jQuery('.lexsearch input').val();
			lexMainSearch(val);
		})


		jQuery('.lexmenubtn').on('click',function(){
			clickLexSearchMenu();
		})

		jQuery('.mobile_sidebar_bg').on('click',function(){
			clickLexSearchMenu();
		})

		jQuery('.lexsearch input').val('');
		jQuery('.lexsearch input').attr("placeholder", getPlaceHolderText());

		jQuery(window).on('resize',function(){
			resizeBehavior()
		}) // resize
		
		let singleId = false;
		if (window.location.hash){
			singleId = window.location.hash.substring(1);

			if (urlParams.get('letter') && !isNaN(singleId)){
				singleId = "M" + singleId;
			}
		}
		else {
			const sid = urlParams.get('single');
			
			if (sid){
				singleId = sid;
			}
		}
		
		if (singleId && singleId.substring(0, 1) == "A"){
			urlParams.set("list", "municipalities");
		}
		else if (singleId && isMethodology && !singleId.startsWith("M")){
			singleId = false;
		}
		else if (singleId && !isMethodology && singleId.startsWith("M")){
			singleId = false;
		}

		tagSet = false;
		if (isMethodology && !singleId && urlParams.get('tag')){
			tagSet = urlParams.get('tag');
		}

		getAllArticles(function() {
			let stateId = urlParams.get("state");

			if (stateId){
				jQuery.post(ajax_object.ajaxurl, {
					"action": "va",
					"namespace": "lex_alp",
					"query": "load_state",
					"id" : stateId
				}, function (response){
					if (response == "INVALID_STATE"){
						alert("Invalid state id: " + stateId);
						return;
					}
					
					let stateData = JSON.parse(response);
					let tdb = stateData["version"];
					
					if(tdb == ajax_object["next_version"]){ //Future version
						tdb = "xxx";
					}
					
					if(tdb != ajax_object["db"]){
						reloadPageWithParam(["db", "state"], [tdb, stateId]);
					}
					else {
						openSavedArticles(stateData);
					}
				});
			}
			else if (tagSet){
				let tids = [];
				jQuery("#lextitellist li").each(function (){
					let tags = (jQuery(this).data("tags") + "").split(",");
					if (tags.includes(tagSet)){
						tids.push(jQuery(this).attr("id"));
					}
				});

				addArticlesByIds(tids, null, false, null);
				for (let i = 0; i < tids.length; i++){
					all_active_ids[tids[i]] = true;
				}
				updateVisibleItems();
				jQuery('.lexstartcontent').fadeOut('fast');
			}

		 addABCScrolling(removeDiacriticsPlusSpecial);	

		});

		jQuery(window).on("hashchange", function (){
			let id = window.location.hash.substring(1);
			if (jQuery('.lexstartcontent').length > 0){
				jQuery('.lexstartcontent').fadeOut('fast');
			}
			localLink(id);
		});

		if (singleId){
			if (ID_MAPPING[singleId]){
				singleId = ID_MAPPING[singleId]; // L1 -> L1+5
			}
			addArticlesByIds([singleId], null, true, null);
			all_active_ids[singleId] = true;
			jQuery('.lexstartcontent').fadeOut('fast');
		}

		if((getZoomValues().actualZoom > 1.01 || getZoomValues().actualZoom < 1.0 ) && window.innerWidth>1100){  //temp fix didnt work for small screens after brwoser update
			//showBrowserZoomWarning(zoomWarning)
		}

	centerLexLogo(true);

	}); //ready


function addABCScrolling(characterFunction){

    var data = (filtered_data.length>0) ? filtered_data : all_data;

	var list = ["A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z"] 	
   
   	if(jQuery('.lex_abc').children().length==0){
		    list.map(o => {
		    	jQuery('.lex_abc').append('<div>'+o+'<hr class="lexlisthr"></hr></div>');
		    })
		    jQuery('.lexlisthr').last().remove()

		    clear_titles.map((el,i) => {
		    	var regres = /title=\"(.*?)\">/gi.exec(el);
		    	var titel;
		    	if(regres)
		    		 title = characterFunction(regres[1]);
		    	else title = characterFunction(jQuery(el).attr('title'));
		    	clear_titles[i] = title;
		   })
    }

    var letterToIndices = {}

    jQuery('.lex_abc div').removeClass('active');

    data.map((el,i) => {
    	var regres = /(id="(.*?)(\"))/g.exec(el);
    	var id; 
    	if(regres) id = regres[2];
    	else id = jQuery(el).attr('id');
    	var idx = ids_to_idx[id];
    	var title = clear_titles[idx];
		if (title){
			var first_letter = title.charAt(0).toUpperCase();
			if(letterToIndices[first_letter]==undefined && list.indexOf(first_letter)!=-1){
				letterToIndices[first_letter] = i;
				jQuery('.lex_abc div:contains("'+first_letter+'")').addClass('active');
			}
		}
    })



    letterToIndices['A'] = 0;

    jQuery('.lex_abc > div.active').off().on('click touchstart',function(){


    	 var row_height = jQuery('#lextitellist li').first().outerHeight();
    	 var letterscrollpos = letterToIndices[jQuery(this).text()]

    	 var scrolltop =  (letterscrollpos>0) ? (letterscrollpos*row_height)+row_height : 0
	     if(isMethodology) scrolltop-=row_height;
    	 if(letterscrollpos!=undefined) jQuery("#scrollArea").animate({ scrollTop: parseInt(scrolltop)}, 100);
    })

}


function openSavedArticles(stateData){

	var articles = stateData["articles"];
	jQuery('.lexstartcontent').fadeOut('fast');

	var openArticles = articles.map(o => o["id"]);

	var highlight = (stateData.highlighted!="") ? stateData.highlighted : false

	if(highlight) {
		searching = true;
		jQuery('.lexsearch input').val(highlight);
	}


	addArticlesByIds(openArticles, null, true,highlight, function(){

				articles.map((current_art)=>{
					article_id = current_art.id;
					let selector = '#detailview_' + article_id.replace(/\+/g, '\\+');
					all_active_ids[article_id] = true
			
					if(current_art.backOpen){
					    jQuery(selector).find('.flipbutton').click()	
					    
					    for(var key in current_art['openSubs']){
					    	var index = parseInt(current_art['openSubs'][key]["index"]);
					        var idx = index+1;
			    		    var subcont = jQuery(selector).find('.sub_head:nth-child('+idx+') .sub_head_content');
			    		    subcont.click()

			    		    if(current_art['openSubs'][key]["secTables"]){

				    		    current_art['openSubs'][key]["secTables"].map(subtable_idx => {
				    		    	 var tr = subcont.parent().find('.backtable tbody tr:not(.second_row):nth-child('+subtable_idx+')')
				    		    	 openSecTable(tr, jQuery(selector),true)
				    		    })

			    		    }

					    }

					}

					else if (current_art.frontOpen){
						jQuery(selector).find('.lex_read_more').click()	
					}

				})

			updateVisibleItems();
			jQuery('.entry-content.lex').animate({ scrollTop: (stateData.scroll_pos)}, 'slow');
			// jQuery('.entry-content.lex').scrollTop(stateData.scroll_pos);
	});

}


function addScrollShift(){

setTimeout(function() {

    var top = parseInt(jQuery('.lex_header').css('top').split('px')[0]);
    var margin_top = parseInt(jQuery('.entry-content.lex').css('margin-top').split('px')[0]);
    var padding_top = parseInt(jQuery('#scrollLex').css('padding-top').split('px')[0]); 
 	var prevScroll = 0;

 	addScrollShiftListener(margin_top, margin_top, padding_top, prevScroll);

 }, 10); // needed for correct css values


}

function addSideBarCollapse(){


	jQuery('.lex_slide_collapse').on('click',function(){
			if(window.innerWidth>=768){
				centerLexLogo(false)
				jQuery('.lexlogowrapper').addClass('shift');
				jQuery('.lex_header').addClass('shift');
				jQuery('body').addClass('sidebarCollapse');
				jQuery('.lex_slide_uncollapse').fadeIn();
				jQuery('.lexsidebar').css('left','-'+(jQuery('.lexsidebar').outerWidth()+5)+'px');
			}
	})

	jQuery('.lex_slide_uncollapse').on('click',function(){
		if(window.innerWidth>=768){
				centerLexLogo(true)
			    jQuery('.lexlogowrapper').removeClass('shift');
			    jQuery('.lex_header').removeClass('shift');
				jQuery('body').removeClass('sidebarCollapse');
				jQuery('.lex_slide_uncollapse').fadeOut();
				jQuery('.lexsidebar').css('left','0px');
			}
	})

	jQuery('.lex_scrollup').on('click',function(){
		jQuery("html, body").animate({ scrollTop: "0" });
	})

	jQuery('.lex_close_all').on('click',function(){
		completeReset(true, true);
		jQuery(this).fadeOut();
		jQuery("html, body").animate({ scrollTop: "0" });
	})
}


function centerLexLogo(withSideBar){
	var headerwidth = jQuery('.lex_header_inner').innerWidth();
	var innerheaderwidth = jQuery('.lexlogowrapper').innerWidth();
	var left = headerwidth/2 - innerheaderwidth/2;
	var add = 16;
	var left_articles = jQuery('.lex_articles').offset().left;

	if(!withSideBar || window.innerWidth<=768){
		jQuery('.lexlogowrapper').css('left',(left)+"px");
		var head_width = jQuery('.lexhead').innerWidth();
		var left = innerheaderwidth/2 - head_width/2;
		jQuery('.lexhead').css('left', (left-5) + "px");
		saved_left = left_articles;
	}
	else {
		if(!saved_left)jQuery('.lexlogowrapper').css('left',(left_articles)+"px");
		else jQuery('.lexlogowrapper').css('left',(saved_left)+"px");
		jQuery('.lexhead').css('left', "0px");
	}

}

function addScrollShiftListener(margin_top, margin_top,padding_top, prevScroll){

		jQuery(document).on("scroll", function() {

			   var scrollTop = Math.round(jQuery(document).scrollTop())
				 var scrollAdd = 90;
			     if(window.innerWidth<=1340) scrollAdd = 70;
			     if(window.innerWidth<=768 && window.innerWidth>721) scrollAdd = 85;
			     if(window.innerWidth<721) scrollAdd = 65;

			     if(scrollTop>75) {
		     		jQuery('img.lexlogo').addClass('fadeOut')
		     		jQuery('.lexlogo_text').addClass('fadeOut')
			     	jQuery('.lex_header').css('transform','translateY(-'+scrollAdd+'px)')
			     }
			     else{
		     	 	jQuery('.lex_header').css('transform','translateY(0px)')
				 	jQuery('img.lexlogo').removeClass('fadeOut')
			 		jQuery('.lexlogo_text').removeClass('fadeOut')
			     }


			     if(scrollTop > 100){
			     	jQuery('.lex_scrollup').fadeIn();
			     	jQuery('.lex_close_all').fadeIn();
			     }
			     else{
			     	jQuery('.lex_scrollup').fadeOut();
		     		jQuery('.lex_close_all').fadeOut();
			     }
		          	
    });
}


function saveCurrentState(){

	const scrollPos = jQuery('.entry-content.lex').scrollTop()
	const highlightString = jQuery('.lexsearch input').val()

	const openArticles = []
	jQuery(jQuery('.lex_article').get().reverse()).each((i,el) => {

		var obj = {"id": jQuery(el).attr("id").split('detailview_')[1]};
		if(jQuery(el).hasClass('flipped')){
			obj['backOpen'] = true
			const open_subs = []
			jQuery(el).find('.sub_head.open').each((i,el) => {

				var open_sec_tables = []
				jQuery(el).find('tr.second_row').each((i,el) => {
					    open_sec_tables.push(jQuery(el).index())
				})
				open_subs.push({"index": i, "secTables": open_sec_tables});
			})

			obj['openSubs'] = open_subs;
		}

	    else if (jQuery(el).hasClass('open'))obj['frontOpen'] = true
	    
		openArticles.push(obj)

	})
			
	return {articles:openArticles,scrollPos: scrollPos, highlightString: highlightString }
}

function produceLexURL (callback){

	if(prevent_tour_click){ 
		return;
	}


	let data = saveCurrentState();

	jQuery.post(ajax_object.ajaxurl, {
		"action": "va",
		"namespace": "lex_alp",
		"query": "save_state",
		"type": isMethodology? "M": "L",
		"version_number": ajax_object.db === "xxx"? ajax_object.next_version: ajax_object.db,
		"data": data
	}, function (response){
		callback(stateUrl.replace("§§§", response));
	});
}


function resizeBehavior(){

	centerLexLogo(true);
	setTimeout(function() {
		centerLexLogo(true);
	}, 1000);

	if(window.innerWidth >= 768){
		saved_left = null;
		jQuery('.lexsidebar').show();
		jQuery('.lexsidebar').css('top','');
		jQuery('.mobile_sidebar_bg').hide();

	jQuery('.lex_article.show.open').each(function(){

			var f_cont = jQuery(this).find('.f_content');
			var el_cont = f_cont[0];
			var front  = jQuery(this).find('.front');
			readMoreFunction(jQuery(this), el_cont, front);		

	})

	jQuery('.lex_article.flipped').each(function(){
	closeAllBack(jQuery(this),false);
	})

	}	

	if(jQuery('body').hasClass('sidebarCollapse')){
		jQuery('.lex_slide_uncollapse').click();
	}


	jQuery('.lex_article:not(.overflow)').css('height','initial')
	.find('.front').css('height','initial')
	.css('max-height','209px')
	.find('.front').css('max-height','209px')


	jQuery('.lex_article:not(.overflow)').each(function(){
	var el = jQuery(this).find('.front')[0];
	var total_height = (el.offsetHeight > 88) ? el.offsetHeight : 88;
		if(!jQuery(this).hasClass('flipped'))jQuery(this).height(total_height).attr('back_height','');
		jQuery(this).attr('original_height', total_height);
			jQuery(this).flip({axis: 'x', trigger: 'manual',speed:450});
			jQuery(this).find('.front').css('height','100%')	
	})



	if(clusterize)clusterize.refresh(true);

}

	

function localLink (id){

	if (ID_MAPPING[id]){
		id = ID_MAPPING[id]; // L1 -> L1+5
	}
	
	let callback = function (){
		let j = jQuery(".lex_article_" + id);
		if (j.hasClass("open")){
			scrollToEntry(id);
			//j[0].scrollIntoView();
		}
		else {
			var el_cont = j.find('.f_content')[0];
			var front  = j.find('.front');
			readMoreFunction(j, el_cont, front, function (){
				scrollToEntry(id);
				//j[0].scrollIntoView();
			});
		}
	}
	
	if (!all_active_ids[id]){
		addArticlesByIds([id], getPrevId(id), true, null, callback);
		all_active_ids[id] = true;
	}
	else {
		callback();
	}
}

function scrollToEntry (id){
	let j = jQuery(".lex_article_" + id);

	if(j.length>0){
		window.scrollTo({top: j.offset().top - jQuery(".lex_header_inner").height(), behavior: 'smooth'});
	}
}


function slideMobileMenuDown(){
		jQuery('.mobile_sidebar_bg').fadeOut();
		jQuery('.lexsidebar.in').removeClass('in');
		jQuery('.lexsidebar').css('top','100%');
}

function addPopups (div){

	addBiblioQTips(div);
	addCitations("<?php echo $Ue['KOPIEREN'] ?>");
	
// 	div.find(".lex_quote").each(function (){
// 		jQuery(this).qtip({
// 			"show" : "click",
// 			"hide" : "unfocus",
// 			"content" : {
// 				"text" : "<div>" + jQuery(this).data("quote").replace(/(http[^ ]*)/, "<a href='$1'>$1</a>")
// 				+ "</div><br /><input class='copyButton' style='display: block; margin: auto;' type='button' data-content='" 
//				+ jQuery(this).data("quote") + "' value='<?php echo $Ue['KOPIEREN']; ?>' />"
//  			},
//  			"position" : {
//  				"my": "top right",  
//  				"at": "bottom left"
//  			}
// 		});
// 	});
}

function removePopus (div){
	div.find(".bibl, .vaabr, .sabr").qtip("destroy");
	div.find(".lex_quote").qtip("destroy");	
}

function clickListItem(_this){
	
 	 	var id = jQuery(_this).attr('id');

 	 	if(ids_to_remove[id] == true) return;

		if(jQuery(_this).hasClass('active')){

			closeArticle(jQuery('#detailview_'+id.replace(/\+/g, '\\+')),id);
			jQuery(_this).removeClass('active');	
			 	  	

		}
		else {
			if(!jQuery('.lex_main_load_cover').is(":visible"))jQuery('.lex_main_load_cover').css("display", "flex").hide().fadeIn('fast');
			jQuery(_this).addClass('active');
	  	  	var prev_id = getPrevId(id);

	 
		    if(all_active_ids[id]==null){

				    	addArticlesByIds([id],prev_id,true,null,function (){
						scrollToEntry(id);
						
					});


			}

		    all_active_ids[id] = true;
		    	
		}

	    if(jQuery('.lexstartcontent').length>0)jQuery('.lexstartcontent').fadeOut('fast');
	    if(jQuery('.lex_articles .no_results').length>0)jQuery('.lex_articles .no_results').remove();	  
}



function getPrevId(id){
	var own_idx = ids_to_idx[id];
	var res = null;
    var mindist = Number.POSITIVE_INFINITY;
	for(var key in all_active_ids){
		var other_idx =  ids_to_idx[key];
		if(other_idx<own_idx){
		  var dist = own_idx-other_idx;
		  if(dist<mindist){
			res = key;
			mindist = dist;
		}
	}	
 }
return res;
}

function getAllArticles(callback){

	 var query = "get_all_articles";
	 if(urlParams.get("list") == "municipalities")query="get_all_municipalities";
	 if(isMethodology)query="get_all_methodology";

	  var data = {
            "action" : "va",
            "namespace" : "lex_alp",
            "query" : query,
			"db" : ajax_object.db
    };

    jQuery.post(ajax_object.ajaxurl, data, function (response){
    	var res = JSON.parse(response);

    	for(var i=0; i<res.length;i++){

    		var article = res[i];
    		var type = article['Id'].substring(0, 1);
		    if(isMethodology)type="A";	
    		var row = '<li id="'+article["Id"]+'"' + (article["Tags"]? ' data-tags="' + article["Tags"] + '"': '') + '><span class="list_marker type_'+type+'"></span><span class="title-string">'+article["Title_Html"]+'</span></li>';
    		clear_titles.push(article["Title_Html"])
    		all_data.push(row);
    		ids_to_idx[article['Id']] = i;

    	}


      clusterize = new Clusterize({
		  rows: all_data,
		  scrollId: 'scrollArea',
		  contentId: 'lextitellist',
		  no_data_text: '<?php echo $Ue['LEX_NO_RESULTS']; ?>', 
		   callbacks: {
		    clusterChanged: function() {
		    		updateVisibleItems();
		    	}
  			}
		});

	 jQuery('#lextitellist').on('mouseup', 'li', function() {
  	 		 clickListItem(this);
	 });

   	  jQuery('.lexsearch input').attr("placeholder", getPlaceHolderText());

   	  jQuery('.lex_load_cover').fadeOut();


   	  	var main_search_input = jQuery('.lexsearch input');

   	  	main_search_input.on('keyup', function () {

   	  		if(event.key == "Enter"){
   	  			var val = jQuery('.lexsearch input').val();
				lexMainSearch(val);
   	  		}

		if(main_search_input.val().length==0 && event.key!=="Enter"){
					completeReset(true, true);
		 	}
		});


   	 	var input = jQuery('.lexsidebar').find('.search').find('input');


		var doneTypingInterval = 250;
		var typingTimer;        

		input.on('keyup', function () {
		  jQuery('.lex_load_cover').fadeIn();
		  clearTimeout(typingTimer);
		  typingTimer = setTimeout(doneTyping, doneTypingInterval);
		});


		input.on('keydown', function () {
		  clearTimeout(typingTimer);
		});

		function doneTyping () {

		 if(input.val().length>0){
		 	getFilterResults(input.val());
		 	addABCScrolling(removeDiacriticsPlusSpecial)
		 }
		 else {
	 		filtered_data = [];
		 	clusterize.update(all_data);
		 	addABCScrolling(removeDiacriticsPlusSpecial)

		 }
    	 jQuery('.lex_load_cover').fadeOut();
		}

		callback()
    }) // post


}


function getFilterResults(filter){

	  var query = "get_filter_results";
	  if(urlParams.get("list") == "municipalities") query = "filter_municipalities_results";
      if(isMethodology) query = "filter_methodology_results";

	  var data = {
            "action" : "va",
            "namespace" : "lex_alp",
            "search_val": removeDiacritics(filter),
            "query" : query,
			"db" : ajax_object.db
    };

    jQuery.post(ajax_object.ajaxurl, data, function (response)
    {
    	var res = JSON.parse(response);
    	filtered_data = [];
  	

    	for(var i=res.length-1; i>=0;i--){

    		var id = res[i];

		  	var idx = ids_to_idx[id];
		
		  	var article = all_data[idx];
    		filtered_data.push(article);

    	}
  	    clusterize.update(filtered_data);
  	    addABCScrolling(removeDiacriticsPlusSpecial)
    })
}


function completeReset(show, resetSidebar){
		loadingCounter =0;
		filtered_data = [];
		clusterize.update(all_data)
		addABCScrolling(removeDiacriticsPlusSpecial)
		jQuery('.lex_article').remove();
		jQuery('.no_results').remove();
		all_active_ids = {};
		jQuery('#lextitelinput').val('');
		if(show){
			jQuery('.lexstartcontent').fadeIn('fast');
			jQuery('.lexsearch input').val('');
			jQuery('.lexsearch input').attr("placeholder", getPlaceHolderText());
		}
		updateVisibleItems();

		if(resetSidebar){
			jQuery('.lexsidebar #scrollArea').scrollTop(0);
			if(jQuery('.lexsidebar').css('left')!="0px"){
				jQuery('.lex_slide_uncollapse').click();
			}

		}
		
		jQuery('.lex_article').each(function(){
			removePopus(jQuery(this));
		})
}


function updateVisibleItems(){
		for(key in all_active_ids){
			let selector = "#" + key.replace(/\+/g, '\\+')
			if(!(jQuery(selector).hasClass('active'))){
				jQuery(selector).addClass('active');
			}
		}

		if(Object.keys(all_active_ids).length==0){
			jQuery('.lexlist li.active').removeClass('active');
		}
}


function addArticlesByIds(ids,prev_id,append_alphabetically,highlight, callback){

	loadingCounter++;



    var data = {
            "action" : "va",
            "namespace" : "lex_alp",
            "query" : "get_text_content",
            "type" : isMethodology? 'M': 'L',
            "id" : ids,
			"db" : ajax_object.db
    };
   
       
        jQuery.post(ajax_object.ajaxurl, data, function (response){

            	 var articles_to_append = jQuery(response);
            

            	 jQuery(articles_to_append).each(function(){

        	 	 var that = jQuery(this);
        	 	 var id = that.attr('id').split('_')[1];
        	 	

            	 	if(!append_alphabetically){
            	 		jQuery('.lex_articles').append(that);
            	 	}
            	 	else{
	            	 		if(jQuery('.lex_article').length==0 && all_active_ids[id]==true)jQuery('.lex_articles').append(that);

	            	 	     else{

		            	 	     	if(all_active_ids[id]==true && jQuery("#detailview_"+id).length==0){

		            	 	     		if(prev_id){

		            	 	     		var prev_element = jQuery("#detailview_"+prev_id.replace(/\+/g, '\\+'));


			            	 	     			if(prev_element.length>0) prev_element.after(that);
			            	 	     			else jQuery('.lex_articles').append(that);

		            	 	     		
		            	 	     		}	

			            	 		 	else jQuery('.lex_articles').prepend(jQuery(this));

		            	 		 	}

	            	 		 

	            	 		 }

            	 	    }

    	 	    	 generateFinalLexArticles(that)
    	 	
            	 }); // each article

		
				loadingCounter--;
				 if(loadingCounter==0)jQuery('.lex_main_load_cover').fadeOut('fast');
	
	 	

			if(searching){
				finalizeSearch(highlight);
			}

			if (callback){
				callback();
			}

        }); // ajax


};


function generateFinalLexArticles(that){


					var el = that[0];
    	 			var f_cont = that.find('.f_content');
    	 			var el_cont = f_cont[0];
    	 			var front  = that.find('.front');
    	
	
		  	 		var total_height = (el.offsetHeight > 88) ? el.offsetHeight : 88;

        	 		that.height(total_height);
        	 		front.height(total_height);

        	 		that.attr('original_height',total_height);
        	 		that.css('max-height','initial');

            	 	if (total_height < el_cont.scrollHeight) {

            	 		var readmore = jQuery('<div class="lex_read_more extend"><span class="extend"><?php echo $Ue['LEX_READ_MORE']; ?></span></div>');
            	 		
            	 		that.find('.f_content').append(readmore);

            	 		that.addClass('overflow');
	
            	 		that.css('max-height','initial');
            	 		front.css('max-height','initial');
				
							readmore.on('click',function(){
								readMoreFunction(that, el_cont, front);						
							})



					} 

					that.find('.back').show();	
				  	that.flip({axis: 'x', trigger: 'manual',speed:450});	
              
	

					  that.find('.lex_close').on('click',function(){
		  				 var id = that.attr('id').split('_')[1];

		  					if(!prevent_tour_click)closeArticle(that,id);

  				   	 	 // jQuery('.lexsearch input').attr("placeholder", getPlaceHolderText());			
					  });	


					  that.find('.lex_image_btn').on('click',function(){
					  	 if(blockimages) return;
					  	 blockimages = true;
		  				 var id = that.attr('id').split('_')[1];
							   getConceptImages(id,function(data){
							   		 createLexImageModal(data,id);
							   		  
							   });
					  });	


					  that.find('.flipbutton').off().on('click',function(){

					  	if(that.hasClass('autoclick') || prevent_tour_click) return;
											  	 			  	
				  		if(that.find('.open').length>0 || that.hasClass('open')){ //something is open => no flip first close stuff


		  			  	  if(that.hasClass('flipped')){
		  			  	  	 	closeAllBack(that,true);
		  			  	  }
		  			  	  else{

					  		 readMoreFunction(that, el_cont, front,function(){
					  		 	that.find('.flipbutton').click();
					  		 });

					  		 }	
					  	}

					  	else{


						setTimeout(function(){
							that.flip('toggle');
						},150)

			  	 	  	 that.toggleClass('flipped');
					  	 that.addClass('toggle_scale');
					  	 jQuery(this).hide();

					  	 setTimeout(function() {that.removeClass('toggle_scale')}, 200);

						  that.one('flip:done',function(){

						  		setTimeout(function() {
						  			that.find('.flipbutton').fadeIn();	
								  	  if(that.hasClass('flipped')){
										  	 	that.find('.flipbutton').find('.text').text('<?php echo $Ue['LEX_BACK']; ?>');
										  	 	that.find('.flipbutton').find('i').removeClass('fa-database').addClass('fa-angle-left');
										}

									  	 else{
								  	 		that.find('.flipbutton').find('.text').text('<?php echo $Ue['LEX_DATA']; ?>'); 
								  	 		that.find('.flipbutton').find('i').removeClass('fa-angle-left').addClass('fa-database');		
									  	 }
						  		}, 150);
						  });


					  	  if(that.hasClass('flipped')){


							  	 	that.attr('back_height',that.find('.b_content').height()+20);
							  	 	that.height(parseInt(that.attr('back_height')));

							  	 

								  	 	that.find('.sub_head').off().on('click',function(e){

								  	 		if(prevent_tour_click) return;

								  	 		var clicked_item = jQuery(this);

								  	 				if(jQuery(this).hasClass('sliding') || !jQuery(e.target).hasClass('sub_head_content')) return;
								  	 				
								  	 				clicked_item.toggleClass('open');
								  	 				clicked_item.addClass('sliding');

								  	 				if(jQuery(this).hasClass('open')){	

								  	 					if(clicked_item.find('.hiddenbackcontent').find('table').length==0){

																slideSubHeadDown(that, clicked_item, null);

								  	 					}

						  	 							else{

								  	 							clicked_item.find('i').removeClass('fa-angle-right').addClass('fa-angle-down');
						  	 									clicked_item.find('.hiddenbackcontent').slideDown(function(){
					  	 										clicked_item.addClass('open');	
																clicked_item.removeClass('sliding');
																clicked_item.find('.back').addClass('table_open');
																});

								  	 					}

								  	

										  	 		}

								  	 				else{
									  	 				
										  	 			openForSlideDown(that, clicked_item);
								  	 				    
										  	 		}

									  	 			if(that.find('.open').length==1){

															that.css('height','100%');
															setTimeout(function() {	that.find('.back').css('overflow','auto').css('max-height','initial');}, 10);

													}

										  	 
								  	 
								  	 		
								  	 	});

							}

						  	 else{
						  	 	that.height(parseInt(that.attr('original_height')));  	 	
						  	 }

						}// if not open 	 

					  });
					
		
			that.addClass('show');

			addPopups(that);

}


function openForSlideDown(that, clicked_item){
	clicked_item.find('i').removeClass('fa-angle-down').addClass('fa-angle-right');
			clicked_item.addClass('sliding');

				clicked_item.find('.hiddenbackcontent').slideUp(function(){
				clicked_item.removeClass('sliding');
				clicked_item.find('.second_row').remove();
				clicked_item.find('.backtable tr').removeClass('active');
				that.find('.back').removeClass('table_open');
				clicked_item.removeClass('open');

					if(that.find('.open').length==0){											
		  		setTimeout(function() { that.find('.back').css('overflow','hidden').css('max-height','209px');}, 10);	
				  that.height(parseInt(that.attr('back_height')));
									  	 			 
			  	}
	  	
			  	 	clicked_item.find('.hiddenbackcontent').empty();
			 });
	}


function slideSubHeadDown(that, clicked_item, callback){
		var id = that.attr('id').split('_')[1];
		var index = clicked_item.index();
		var selftype = id.charAt(0)
		var othertype = getOtherTypeByOwn(selftype,index);

		clicked_item.find('i').removeClass('fa-angle-right').addClass('fa-circle-notch fa-spin');

		getBackTable(id,selftype,othertype, function(html){

			clicked_item.find('.hiddenbackcontent').append(jQuery(html))
			addTableSorter(clicked_item.find('.hiddenbackcontent table'),that)

			clicked_item.find('.hiddenbackcontent').slideDown(function(){
			clicked_item.removeClass('sliding');
			that.find('.back').addClass('table_open');
			if(callback)callback();
			});
			clicked_item.find('i').removeClass('fa-circle-notch fa-spin').addClass('fa-angle-down');
		
		});

 						
		
	if(that.find('.open').length==1){

 		that.css('height','100%');
 		setTimeout(function() {	that.find('.back').css('overflow','auto').css('max-height','initial');}, 10);
	
	}

}


function createLexImageModal(urls, id){

var i = urls.length;
while (i--) {
   	var format = urls[i].split('.').pop();
    if (format=="mp4") { 
         urls.splice(i, 1);
    } 
}

var carousel_cover = jQuery('<div class="carousel-cover"></div>');
var lexclone = jQuery('.lex_load_cover .spinnerarea').clone();
carousel_cover.append(lexclone);
jQuery('#lexImageModal .modal-body').append(carousel_cover);

var titel = '<?php echo $Ue['KONZEPT'];?>'+': '+id;
jQuery('#lexImageModal .modal-title').text(titel);


if(urls.length==1){
	jQuery('#lexImageModal .carousel-indicators').hide();
	jQuery('#lexImageModal .cc_control').hide();
}

var count = 0;

jQuery('#lexImageModal').modal();

	jQuery('#lexImageModal').on('shown.bs.modal',function(){

			for(var i=0; i<urls.length;i++){
				var url = urls[i];
				var fakeImage = jQuery('<img src="'+url+'"/>');
				
				fakeImage.on('load',function(){
				
					if(count==urls.length-1){
						for(var j=0; j<urls.length;j++){
								var image_div = jQuery('<div class="carousel-item"><div class="lex_carousel_img" style="background-image:url('+urls[j]+')"></div></div>');
								
								jQuery('#lexImageModal .carousel-inner').append(image_div);
								var indicator = jQuery('<li data-target="#carouselExampleIndicators" data-slide-to="'+j+'"></li>');
								jQuery('.carousel-indicators').append(indicator);
								if(j==0){
									image_div.addClass('active');
									indicator.addClass('active');
								}

								if(j==urls.length-1){
								
									var carousel = jQuery('#lexImageModal .carousel').carousel()
									
									carousel_cover.fadeOut(function(){

										setTimeout(function(){
								 		   carousel.carousel('next');
									 	},1000); // initiate first slide
										blockimages = false;
									});
							
									
								}
						}
					}

					count++;
				});
			}


 	});


	jQuery('#lexImageModal').on('hidden.bs.modal',function(){

			jQuery('#lexImageModal .carousel').carousel('dispose');

			jQuery('#lexImageModal .carousel-inner').empty();
			jQuery('#lexImageModal .carousel-indicators').empty();
			jQuery('#lexImageModal .carousel-cover').remove();
			jQuery('#lexImageModal .carousel-indicators').show();
			jQuery('#lexImageModal .cc_control').show();
			blockimages = false;
	});



}


function getOtherTypeByOwn(owntype,index){

	if(owntype=="C" && index == 0){
		return "L"
	}
	else if (owntype=="C" && index == 1){
	  return "B"
	}
	else if (owntype=="L" && index == 0){
	  return "C"
	}
	else if (owntype=="L" && index == 1){
	  return "A"
	}
	else if (owntype=="B" && index == 0){
	  return "L"
	}
	else if (owntype=="B" && index == 1){
	  return "A"
	}
	else if (owntype=="B" && index == 2){
	  return "C"
	}
	else if (owntype=="A" && index == 0){
	  return "L"
	}
		else if (owntype=="A" && index == 1){
	  return "B"
	}
		else if (owntype=="A" && index == 2){
	  return "C"
	}
}


function addTableSorter(in_table,that){

	in_table.bind("sortStart",function(e, table) {
	
	   jQuery(table).find('tr').removeClass('active').off();
	   jQuery(table).find('.second_row').remove();

	})
	.bind("sortEnd",function(e, table) {
		 	openSecData(jQuery(table),that);
	}).bind("tablesorter-initialized",function(e, table) {

			openSecData(jQuery(table),that);
		});
	
    in_table.tablesorter({theme: 'dark'}); 
}

function closeAllBack(that,flip){

		var num_open_items = that.find('.open').length;
		var count = 0;
			that.find('.open').removeClass('open').find('.hiddenbackcontent').slideUp(function(){
			that.find('.back').removeClass('table_open');
			that.removeClass('sliding');
			count++;
			that.find('.second_row').remove();
			that.find('.backtable tr').removeClass('active');
			that.find('.back').css('overflow','hidden').css('max-height','209px');
			that.height(parseInt(that.attr('back_height')));
				if(count==num_open_items && flip){
					performAutoClick(that);
				}
			});
		that.find('.sub_head_content').find('i').removeClass('fa-angle-down').addClass('fa-angle-right');

}

function performAutoClick(that){
	that.addClass('autoclick');
    setTimeout(function() {that.removeClass('autoclick');that.find('.flipbutton').click();},700);
}

function finalizeSearch(highlight){

		jQuery('.lexstartcontent').hide();
		jQuery('.lexsearch button').removeClass('no_hover');
		jQuery('.lexsearch button i').first().removeClass('fa-circle-notch fa-spin lex-search-spinner').addClass('fa-search');
   		updateVisibleItems();
	    if(highlight)highlightSearchResults(highlight);	
   		searching = false;
}


function lexMainSearch(val){

if(searching) return;


	if(val.length<3){
		if(val.length>0){
				jQuery('.lexsearch input').val('').addClass('red').attr("placeholder",'Bitte mehr als 2 Buchstaben eingeben.');
				setTimeout(function() {
						jQuery('.lexsearch input').removeClass('red').attr("placeholder",getPlaceHolderText());
				}, 750);
		}
	}

		else{

			completeReset(false, true);

			searching = true;

				jQuery('.lexsearch button').addClass('no_hover');
				jQuery('.lexsearch button i').first().removeClass('fa-search').addClass('fa-circle-notch fa-spin lex-search-spinner');

					var query = "get_search_results";
					if(urlParams.get("list") == "municipalities")query="get_search_results_mun";
					if(isMethodology)query="get_search_results_meth";


				    var data = {
			            "action" : "va",
			            "namespace" : "lex_alp",
			            "query" : query,
			            "search_val" : removeDiacritics(val),
						"db" : ajax_object.db
			    		};
			       

			    jQuery.post(ajax_object.ajaxurl, data, function (response){

			    	var res = JSON.parse(response);
			   		var list = [];
			   		for(var i=0; i<res.length;i++){
			   			var id = res[i];
			   			list.push(id);
			   			all_active_ids[id] = true;
			   		}

			    if(list.length>0)addArticlesByIds(list,null,false,val);	
			    else{
			    	finalizeSearch(null);
			    	completeReset(false, true);
			    	jQuery('.lexstartcontent').hide();
			    	if(jQuery('.lex_articles .no_results').length==0){
			    		jQuery('.lex_articles').append('<div class="no_results"><?php echo $Ue['LEX_NO_RESULTS']; ?></div>') 
			    		setTimeout(function() {completeReset(true, true)}, 1000);
			    	}
			    };
			


			    });


    }



}

function highlightSearchResults(val){

jQuery(".lex_article").mark(val, {
    "element": "span",
    "className": "highlight",
	"synonyms": {"ss": "ß"}
});

}

function closeArticle(that,id){
	 delete all_active_ids[id];
	 ids_to_remove[id] = true;
		  				 
	 that.removeClass('show');
	 setTimeout(function() {
	 	that.remove();
 	  	 if(Object.keys(all_active_ids).length==0)jQuery('.lexstartcontent').fadeIn('fast');  
 	  	  delete ids_to_remove[id];
	 }, 500); 
	 jQuery("#" + id.replace(/\+/g, '\\+')).removeClass('active');
	 removePopus(that);
}



function openSecData(table,that){
var highlighted = false;

	table.find('tr').off().on('click',function(){
		if(prevent_tour_click) return;
		var row = jQuery(this)
		if(!highlighted){
				openSecTable(row,that,false);
	 	}
	})

	//do not open tr if text is highlighted
	table.find('tr').mouseup(function(){
	var highlightedText = "";
	if (window.getSelection) {
		highlightedText = window.getSelection().toString();
	} 
	else if (document.selection && document.selection.type != "Control") {
		highlightedText = document.selection.createRange().text;
	}
	if(highlightedText != "")
		highlighted = true;
		setTimeout(function() {
			highlighted = false;
		}, 100);
	});

}



function openSecTable(tr,detailview,bypass){

		if(getting_sec_data && !bypass) return;

		var main_id_comb = detailview.attr('id').split('_')[1];
		var main_type = main_id_comb.substring(0, 1);
		var main_id = main_id_comb.substr(1);

		var id = tr.attr('id');
		
		if(!id){
			id = null;
		}
		else {
			id = id.substring(1);
		}

		var type = tr.parent().parent().attr('type');
		
		if(!tr.next().hasClass('second_row')){
			var row = tr;

			if(type){
	
			tr.append('<div class="secRowLoading"><i class="fas fa-circle-notch fa-spin"></i></div>');
			tr.find('.secRowLoading').css('height',(tr.height()-1)+"px");

			getSecondaryData(id, type, row, main_type, main_id,function(){
				tr.find('.secRowLoading').remove()
			});
			tr.addClass('active');

			}

		}
		else{
			var qtip_key = type + id + "_" + main_type + main_id;

			for(var i = 0; i < qtipApis[qtip_key].length; i++){
				if(qtipApis[qtip_key][i])
					qtipApis[qtip_key][i]["destroy"](true);
			}

			delete qtipApis[qtip_key];
			
			tr.next().remove();
			tr.removeClass('active');
		}

}

function readMoreFunction(that, el_cont, front, callback){

if(prevent_tour_click) return;

that.toggleClass('open');

		if(that.hasClass('open')){
			 that.animate({height:el_cont.scrollHeight+15},0);
			 //use jQuery animate to fix scrollbar-bug
		}
		else {
		   that.animate({height:parseInt(that.attr('original_height'))},0);
		    //use jQuery animate to fix scrollbar-bug
			 that.find('.lex_read_more').height(50).removeClass('no_grad');
			 
		};

		var transition_event = whichTransitionEvent();

		that.one(transition_event, function () {

		    if(that.hasClass('open')){that.find('.lex_read_more').addClass('no_grad');
		     that.find('.lex_read_more').height(10).find('span').text('<?php echo $Ue['LEX_READ_LESS']; ?>');
			}
			else {

					that.find('.lex_read_more').find('span').text('<?php echo $Ue['LEX_READ_MORE']; ?>');
				}
			if(callback) callback();	
		});
}


function getConceptImages(id,callback){

	var data = {
	    "action" : "va",
	    "namespace" : "lex_alp",
	    "query" : "get_concept_images",
	    "id" : id
	};

	jQuery.post(ajax_object.ajaxurl, data, function (response){
		var image_urls = JSON.parse(response);
		callback(image_urls);
	})

}


function getSecondaryData(id, type, row, main_type, main_id, callback){


getting_sec_data = true;

var data = {
    "action" : "va",
    "namespace" : "lex_alp",
    "query" : "get_secondary_data",
    "id" : id,
    "parent_type": main_type,
    "parent_id" : main_id,
    "type" : type,
	"db" : ajax_object.db
};


jQuery.post(ajax_object.ajaxurl, data, function (response){

	var arr = JSON.parse(response);
	var res = arr[0];
	var extra = arr[1];

	var table = jQuery('<tr class="second_row"><td colspan="'+row.children().length+'"><table class="second_table"><thead><tr></tr></thead><tbody></tbody></table></td></tr>');

	for(var key in res[0]){
		table.find('thead > tr').append(jQuery('<th>'+key+'</th>'));
	}

	for(var key in res){

			var item = res[key];
			var tr = jQuery('<tr></tr>');

		for(var sub in item){
			var val = item[sub];
			tr.append(jQuery('<td>'+val+'</td>'));
		}
		table.find('tbody').append(tr);
	}

	table.find("table").after(extra);

	 row.after(table);

	 var apis = addBibLikeQTips(table, ["bibl", "stimulus", "informant"], ["blue", "blue", "blue"], ["", "sti", "inf"]);
	 qtipApis[type + id + "_" + main_type + main_id] = apis;

	 table.find('.second_table').tablesorter({theme: 'dark'});   

	 getting_sec_data = false;
	 callback();
	 
});


};

function getBackTable(id, selftype, othertype, callback){


var data = {
    "action" : "va",
    "namespace" : "lex_alp",
    "query" : "get_back_table",
    "id" : id,
    "selftype": selftype,
    "othertype" : othertype,
};


jQuery.post(ajax_object.ajaxurl, data, function (response){

	var res = JSON.parse(response);
	callback(res)
})


}

function getPlaceHolderText(){

		var res;

        //municipalities
	   if (urlParams.get("list")=="municipalities") {
		    res= '<?php echo $Ue['LEX_ARTICLE_COUNT']; ?>';	
		    var string = res.split(" ");
		    string[1] = all_data.length;
		    string[2] = '<?php echo $Ue['Gemeinden']; ?>';

		    res = string[0]+" "+string[1]+" "+string[2]+"...";
	    }

    	else{

	    var placeholder = '<?php echo $Ue['LEX_ARTICLE_COUNT']; ?>';
	    res = placeholder.replace('*NUMBER*', all_data.length);

	    }

		return res;
}
		

function clickLexSearchMenu(){

		if(jQuery('.lexsidebar').hasClass('in')){
			setTimeout(function() {
				jQuery('.lexsidebar').css('top','100%');
			}, 10);
			jQuery('.lexsidebar').removeClass('in');
			jQuery('.mobile_sidebar_bg').fadeOut();
			jQuery('html').removeClass('no_overflow');
			
		}
		else{
			jQuery('.mobile_sidebar_bg').fadeIn();
			jQuery('.lexsidebar').show();
			setTimeout(function() {
				jQuery('.lexsidebar').css('top','185px');
			}, 10);
			jQuery('.lexsidebar').addClass('in');
			jQuery('html').addClass('no_overflow')
			jQuery('.lexsidebar').focus()
		}
}


	</script>
	
	<?php 


echo '<div id="lexImageModal" class="modal fade top_menu_modal" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Bilder</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">


			<div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel" data-interval="2500" data-pause="false" data-wrap="true">
			  <ol class="carousel-indicators">
			  </ol>
			  <div class="carousel-inner">

			  </div>
			  <a class="carousel-control-prev cc_control" href="#carouselExampleIndicators" role="button" data-slide="prev">
			    <span class="carousel-control-prev-icon" aria-hidden="true"><i class="fas fa-chevron-left"></i></span>
			    <span class="sr-only">Previous</span>
			  </a>
			  <a class="carousel-control-next cc_control" style="right:0px;" href="#carouselExampleIndicators" role="button" data-slide="next">
			    <span class="carousel-control-next-icon" aria-hidden="true"><i class="fas fa-chevron-right"></i></span>
			    <span class="sr-only">Next</span>
			  </a>
			</div>


      </div>
    </div>
  </div>
</div>';

		echo '<div class="lex_header">';
		echo '<div class="lex_header_inner">';	
				

			echo '<div class="lexlogowrapper">';

			$extra_class = "";
			if ($mun_list || $meth_list)$extra_class= "no_svg";	
			
			echo '<div class="lexlogo lexhead '.$extra_class.'">';
			if (!$mun_list && !$meth_list){
				echo '<img class="lexlogo" src="' . VA_PLUGIN_URL . '/images/lexicon_logo.svg"/>';
			}
			else if($mun_list){
				echo '<div class="lexlogo_text">'.$Ue["Gemeinden"].'</div>';
			}
			else if($meth_list){
				echo '<div class="lexlogo_text" style="position: relative;">'.$Ue["METHODOLOGIE"].'</div>';
			}
			echo '</div>';

			//if (!$mun_list){
				echo '
				<div class="lexsearch">
				<div>
				<input></input>
				<button class="actionbtn"><i class="fas fa-search" aria-hidden="true"></i></button>
				<div class="lexsep" style="display:none;"></div>
				<button class="actionbtn lexmenubtn"><i class="fas fa-bars" aria-hidden="true"></i></button>
				</div>
				</div>';
			//}

	     echo '</div>';
	   echo '</div>';
	echo '</div>';

	echo '<div  id="scrollLex" class="entry-content lex">';



	echo '<div class="lexcontent">';

	$db_id = 0;

	global $va_xxx;
	
	if ($mun_list){

		$db_id = $va_xxx->get_var("SELECT Id_Eintrag FROM glossar WHERE Terminus_D = 'Präambel_Gemeinden'");

	}
	else if ($meth_list){

		$db_id = $va_xxx->get_var("SELECT Id_Eintrag FROM glossar WHERE Terminus_D = 'Präambel_Methodologie_Neu'");

	}
	else {
		$db_id = $va_xxx->get_var("SELECT Id_Eintrag FROM glossar WHERE Terminus_D = 'Präambel_LexAlp'");

	}

	// if ($pre){

    	$loc = get_locale();
        $langdb = explode("_", $loc)[0];
        $db_lang = strtoupper($langdb[0]);

		$glossary_entry = va_get_glossary_entry($db_id, $db_lang, true, "Sonder", $Ue);
        $text = va_get_glossary_html($glossary_entry[0], true);

        $pre = $text;

		parseSyntax($pre, true);
		echo '<div class="lex_articles" id="scrollLexContent"><div class="lexstartcontent">' . $pre . '</div></div>';


	// }
	// else{
		// echo '<div class="lex_articles" id="scrollLexContent"><div class="lexstartcontent">No description available</div></div>';
	// }	


    echo '</div>'; 
	echo '
	    <div class="lex_main_load_cover"><div class="spinnerarea">
		  <div class="sk-fading-circle">
		  <div class="sk-circle1 sk-circle"></div>
		  <div class="sk-circle2 sk-circle"></div>
		  <div class="sk-circle3 sk-circle"></div>
		  <div class="sk-circle4 sk-circle"></div>
		  <div class="sk-circle5 sk-circle"></div>
		  <div class="sk-circle6 sk-circle"></div>
		  <div class="sk-circle7 sk-circle"></div>
		  <div class="sk-circle8 sk-circle"></div>
		  <div class="sk-circle9 sk-circle"></div>
		  <div class="sk-circle10 sk-circle"></div>
		  <div class="sk-circle11 sk-circle"></div>
		  <div class="sk-circle12 sk-circle"></div>
		</div>
		</div>
		</div>';
	
 echo '</div>';

    //SIDEBAR

    echo '<div class="lex_slide_uncollapse"> <i class="fas fa-chevron-right"></i></div>';
    echo '<div class="lex_scrollup"> <i class="fas fa-chevron-up"></i></div>';

    echo '<div class="lex_close_all"> <i class="fas fa-times"></i></div>';

    echo '<div class="mobile_sidebar_bg"></div>';

    echo '<div class="lexsidebar">';
    echo '<div class="abc_wrap"><div class="lex_abc"></div></div>';

	if (!$mun_list){

    echo '<div class="search"><i class="fas fa-search" aria-hidden="true"></i><input id="lextitelinput" type="text" class="form-control input-md" placeholder="'.$Ue['LEX_FILTER'].'"> </div>';

    }

    else{
    	   echo '<div class="search"><i class="fas fa-search" aria-hidden="true"></i><input id="lextitelinput" type="text" class="form-control input-md" placeholder="'.$Ue['Gemeinden'].'..."> </div>';
    }

    echo '<div class="lex_slide_collapse"> <i class="fas fa-chevron-left"></i></div>';

    echo '<div id="scrollArea">';
    echo '<div class="lex_load_cover"><div class="spinnerarea">
	  <div class="sk-fading-circle">
	  <div class="sk-circle1 sk-circle"></div>
	  <div class="sk-circle2 sk-circle"></div>
	  <div class="sk-circle3 sk-circle"></div>
	  <div class="sk-circle4 sk-circle"></div>
	  <div class="sk-circle5 sk-circle"></div>
	  <div class="sk-circle6 sk-circle"></div>
	  <div class="sk-circle7 sk-circle"></div>
	  <div class="sk-circle8 sk-circle"></div>
	  <div class="sk-circle9 sk-circle"></div>
	  <div class="sk-circle10 sk-circle"></div>
	  <div class="sk-circle11 sk-circle"></div>
	  <div class="sk-circle12 sk-circle"></div>
	</div>
	</div>
	</div>';
    echo '<ul id="lextitellist" class="lexlist">';


    	// foreach ($comments as $comment){
    	// $type = substr($comment['Id'], 0, 1);
    	// echo '<li id="'.$comment['Id'].'" qid="'.$comment['QID'].'"><span class="list_marker type_'.$type.'"></span><span class="title-string">' . $comment['Title'] . '</span></li>';
    	// }

	echo '</ul>';
	echo '</div>';
    echo '</div>';

}
?>