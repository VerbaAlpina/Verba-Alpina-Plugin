<?php
function va_lexicon(){
	
	global $vadb;
	global $lang;
	global $Ue;
	global $admin;
	global $va_mitarbeiter;
	global $va_current_db_name;
	
	?>
	
	
	
<script type="text/javascript">
	var qtipApis;
  	var gettingarticles = false;
  	var all_active_ids = {};
	var ids_to_idx = {};
  	var append_alphabetically = true;
	var all_data = [];
	var filtered_data = [];
	var clusterize;
	var getting_sec_data = false;
	var searching = false;
	var stateUrl = "<?php 
	   global $wp;
       echo add_query_arg('state', '§§§', add_query_arg( $_SERVER['QUERY_STRING'], '', home_url( $wp->request )));
    ?>";

	
	jQuery(document).ready(function () {

		addCopyButtonSupport();
		
		jQuery('#lextitelinput').val('');

		jQuery('.lexstartcontent').fadeIn('fast');

		jQuery('#page').addClass('lex');
		jQuery('body').addClass('lex');

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

		getAllArticles(function() {
			let urlParams = new URLSearchParams(window.location.search);
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
		});

		jQuery(window).on("hashchange", function (){
			let id = window.location.hash.substring(1);
			if (jQuery('.lexstartcontent').length > 0){
				jQuery('.lexstartcontent').fadeOut('fast');
			}
			localLink(id);
		});

		if (window.location.hash){
			let id = window.location.hash.substring(1);
			addArticlesByIds([id], null, true, null);
			all_active_ids[id] = true;
			jQuery('.lexstartcontent').fadeOut('fast');
		}

	});


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
					all_active_ids[article_id] = true
			
					if(current_art.backOpen){
					    jQuery('#detailview_'+article_id).find('.flipbutton').click()	
					    
					    for(var key in current_art['openSubs']){
					    	var index = parseInt(current_art['openSubs'][key]["index"]);
					        var idx = index+1;
			    		    var subcont = jQuery('#detailview_'+article_id).find('.sub_head:nth-child('+idx+') .sub_head_content');
			    		    subcont.click()

			    		    if(current_art['openSubs'][key]["secTables"]){

				    		    current_art['openSubs'][key]["secTables"].map(subtable_idx => {
				    		    	 var tr = subcont.parent().find('.backtable tbody tr:not(.second_row):nth-child('+subtable_idx+')')
				    		    	 openSecTable(tr, jQuery('#detailview_'+article_id),true)
				    		    })

			    		    }

					    }

					}

					else if (current_art.frontOpen){
						jQuery('#detailview_'+article_id).find('.lex_read_more').click()	
					}

				})

			updateVisibleItems();
			jQuery('.entry-content.lex').animate({ scrollTop: (stateData.scroll_pos)}, 'slow');
			// jQuery('.entry-content.lex').scrollTop(stateData.scroll_pos);
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
	let data = saveCurrentState();

	jQuery.post(ajax_object.ajaxurl, {
		"action": "va",
		"namespace": "lex_alp",
		"query": "save_state",
		"version_number": ajax_object.db === "xxx"? ajax_object.next_version: ajax_object.db,
		"data": data
	}, function (response){
		callback(stateUrl.replace("§§§", response));
	});
}


function resizeBehavior(){


	if(window.innerWidth > 768){
		jQuery('.lexsidebar').show();
		jQuery('.lexsidebar').css('top','');
		jQuery('.mobile_sidebar_bg').hide();
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

	jQuery('.lex_article.show.open').each(function(){

			var f_cont = jQuery(this).find('.f_content');
			var el_cont = f_cont[0];
			var front  = jQuery(this).find('.front');
			readMoreFunction(jQuery(this), el_cont, front);		

	})

	jQuery('.lex_article.flipped').each(function(){
	closeAllBack(jQuery(this),false);
	})


	clusterize.refresh(true);

}

	

function localLink (id){

	let callback = function (){
		let j = jQuery("#detailview_" + id);
		if (j.hasClass("open")){
			j[0].scrollIntoView();
		}
		else {
			var el_cont = j.find('.f_content')[0];
			var front  = j.find('.front');
			readMoreFunction(j, el_cont, front, function (){
				j[0].scrollIntoView();
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

function addPopups (div){

	addBiblioQTips(div);
	
	div.find(".lex_quote").each(function (){
		jQuery(this).qtip({
			"show" : "click",
			"hide" : "unfocus",
			"content" : {
				"text" : "<div>" + jQuery(this).data("quote").replace(/(http[^ ]*)/, "<a href='$1'>$1</a>")
				+ "</div><br /><input class='copyButton' style='display: block; margin: auto;' type='button' data-content='" 
				+ jQuery(this).data("quote") + "' value='<?php echo $Ue['KOPIEREN']; ?>' />"
 			},
 			"position" : {
 				"my": "top right",  
 				"at": "bottom left"
 			}
		});
	});
}

function removePopus (div){
	div.find(".bibl, .vaabr, .sabr").qtip("destroy");
	div.find(".lex_quote").qtip("destroy");	
}

function clickListItem(_this){

 	 	var id = jQuery(_this).attr('id');

		if(jQuery(_this).hasClass('active')){

			closeArticle(jQuery('#detailview_'+id),id);
			jQuery(_this).removeClass('active');
			
		}
		else {
			jQuery(_this).addClass('active');
	  	  	var prev_id = getPrevId(id);
		    addArticlesByIds([id],prev_id,true,null);
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

	  var data = {
            "action" : "va",
            "namespace" : "lex_alp",
            "query" : "get_all_articles"
    };

    jQuery.post(ajax_object.ajaxurl, data, function (response){
    	var res = JSON.parse(response);
    	for(var i=0; i<res.length;i++){
    		var article = res[i];
    		var type = article['Id'].substring(0, 1);
    		var row = '<li id="'+article["Id"]+'"><span class="list_marker type_'+type+'"></span><span class="title-string">'+article["Title_Html"]+'</span></li>';
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

	 jQuery('#lextitellist').on('click', 'li', function() {
  	 		if(!gettingarticles) clickListItem(this);
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
					completeReset(true);
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
		 }
		 else {
	 		filtered_data = [];
		 	clusterize.update(all_data);
		 }
    	 jQuery('.lex_load_cover').fadeOut();
		}

		callback()
    }) // post


}


function getFilterResults(filter){

	  var data = {
            "action" : "va",
            "namespace" : "lex_alp",
            "search_val": removeDiacritics(filter),
            "query" : "get_filter_results"
    };

    jQuery.post(ajax_object.ajaxurl, data, function (response)
    {
    	var res = JSON.parse(response);
    	var filtered_data = [];

    	for(var i=res.length-1; i>=0;i--){
		  	var idx = ids_to_idx[res[i]]
		  	var article = all_data[idx]
    		filtered_data.push(article)
    	}
  	    clusterize.update(filtered_data);
    })
}


function completeReset(show){

		filtered_data = [];
		clusterize.update(all_data)
		jQuery('.lex_article').remove();
		jQuery('.no_results').remove();
		all_active_ids = {};
		jQuery('#lextitelinput').val('');
		if(show)jQuery('.lexstartcontent').fadeIn('fast');
		jQuery('.lexsearch input').attr("placeholder", getPlaceHolderText());
		updateVisibleItems();
		
		jQuery('.lex_article').each(function(){
			removePopus(jQuery(this));
		})
}


function updateVisibleItems(){
		for(key in all_active_ids){
		    			if(!(jQuery('#'+key).hasClass('active'))){
		    				jQuery('#'+key).addClass('active');
		    			}
		}

		if(Object.keys(all_active_ids).length==0){
			jQuery('.lexlist li.active').removeClass('active');
		}
}


function addArticlesByIds(ids,prev_id,append_alphabetically,highlight, callback){

    var data = {
            "action" : "va",
            "namespace" : "lex_alp",
            "query" : "get_text_content",
            "id" : ids
    };
    gettingarticles = true;
       
        jQuery.post(ajax_object.ajaxurl, data, function (response){

            	 var articles_to_append = jQuery(response);
            	 gettingarticles = false;

            	 jQuery(articles_to_append).each(function(){

            	 	if(!append_alphabetically)jQuery('.lex_articles').append(jQuery(this)); 
            	 	else{
	            	 		if(jQuery('.lex_article').length==0)jQuery('.lex_articles').append(jQuery(this));

	            	 	     else{
	            	 		 	if(prev_id)jQuery("#detailview_"+prev_id).after(jQuery(this));
	            	 		 	else jQuery('.lex_articles').prepend(jQuery(this));
	            	 		 }

            	 	    }


    	 	   
            	 	var el = jQuery(this)[0];
    	 			var that = jQuery(this);
    	 			var f_cont = jQuery(this).find('.f_content');
    	 			var el_cont = f_cont[0];
    	 			var front  = jQuery(this).find('.front');
    	

					that.find('.backtable').bind("sortStart",function(e, table) {
					
					   jQuery(table).find('tr').removeClass('active').off();
					   jQuery(table).find('.second_row').remove();
				
					})
					.bind("sortEnd",function(e, table) {
						 	openSecData(jQuery(table),that);
					}).bind("tablesorter-initialized",function(e, table) {
	
							openSecData(jQuery(table),that);
   					});
					
				    that.find('.backtable').tablesorter({theme: 'dark'}); 
	
		  	 		var total_height = (el.offsetHeight > 88) ? el.offsetHeight : 88;

        	 		that.height(total_height);
        	 		front.height(total_height);

        	 		that.attr('original_height',total_height);
        	 		that.css('max-height','initial');

            	 	if (total_height < el_cont.scrollHeight) {

            	 		var readmore = jQuery('<div class="lex_read_more extend"><span class="extend"><?php echo $Ue['LEX_READ_MORE']; ?></span></div>');
            	 		
            	 		jQuery(this).find('.f_content').append(readmore);

            	 		jQuery(this).addClass('overflow');
	
            	 		that.css('max-height','initial');
            	 		front.css('max-height','initial');
				
							readmore.on('click',function(){
								readMoreFunction(that, el_cont, front);						
							})



					} 

					that.find('.back').show();	
				  	that.flip({axis: 'x', trigger: 'manual',speed:450});	
              
	

					  jQuery(this).find('.lex_close').on('click',function(){
		  				 var id = that.attr('id').split('_')[1];
		  					
		  					closeArticle(that,id);

  				   	 	 // jQuery('.lexsearch input').attr("placeholder", getPlaceHolderText());			
					  });	


					  jQuery(this).find('.flipbutton').off().on('click',function(){

					  	if(that.hasClass('autoclick')) return;
											  	 			  	
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

					  	 that.flip('toggle');
			  	 	  	 that.toggleClass('flipped');
					  	 that.addClass('toggle_scale');
					  	 jQuery(this).hide();

					  	 setTimeout(function() {that.removeClass('toggle_scale')}, 200);

						  that.one('flip:done',function(){

						  		setTimeout(function() {
						  			that.find('.flipbutton').fadeIn('fast');	
								  	  if(that.hasClass('flipped')){
										  	 	that.find('.flipbutton').find('.text').text('<?php echo $Ue['LEX_BACK']; ?>');
										  	 	that.find('.flipbutton').find('i').removeClass('fa-database').addClass('fa-angle-left');
										}

									  	 else{
								  	 		that.find('.flipbutton').find('.text').text('<?php echo $Ue['LEX_DATA']; ?>'); 
								  	 		that.find('.flipbutton').find('i').removeClass('fa-angle-left').addClass('fa-database');		
									  	 }
						  		}, 75);
						  });


					  	  if(that.hasClass('flipped')){


							  	 	that.attr('back_height',that.find('.b_content').height()+20);
							  	 	that.height(parseInt(that.attr('back_height')));

								  	 	that.find('.sub_head').off().on('click',function(e){

								  	 		var clicked_item = jQuery(this);

								  	 				if(jQuery(this).hasClass('sliding') || !jQuery(e.target).hasClass('sub_head_content')) return;
								  	 				var head = jQuery(this);
								  	 				head.toggleClass('open');
								  	 				head.addClass('sliding');

								  	 				if(jQuery(this).hasClass('open')){

								  	 					jQuery(this).find('.hiddenbackcontent').slideDown(function(){
								  	 						head.removeClass('sliding');
								  	 						that.find('.back').addClass('table_open');
								  	 					});

								  	 					jQuery(this).find('i').removeClass('fa-angle-right').addClass('fa-angle-down');

								  	 					if(that.find('.open').length==1){

											  	 		that.css('height','100%');
											  	 		setTimeout(function() {	that.find('.back').css('overflow','auto').css('max-height','initial');}, 10);
														
														}

										  	 		}

										  	 		else{
									  	 				jQuery(this).find('i').removeClass('fa-angle-down').addClass('fa-angle-right');
								  	 					head.addClass('sliding');
									  	 
										  	 			jQuery(this).find('.hiddenbackcontent').slideUp(function(){
										  	 				head.removeClass('sliding');
										  	 				 clicked_item.find('.second_row').remove();
										  	 				 clicked_item.find('.backtable tr').removeClass('active');
										  	 				 that.find('.back').removeClass('table_open');

										  	 				if(that.find('.open').length==0){											
															  setTimeout(function() { that.find('.back').css('overflow','hidden').css('max-height','209px');}, 10);	
											  	 			  that.height(parseInt(that.attr('back_height')));

											  	 			  setTimeout(
											  	 			  	function() {
											  	 			  		if(that.find('.open').length==0){
											  	 			  		 
											  	 			  		}
											  	 			  }, 500);										  	 			 
							  	 			  		  	 	}
								  	 			  	

										  	 			 });

								  	 				    
										  	 		}
								  	 
								  	 		
								  	 	});

							}

						  	 else{
						  	 	that.height(parseInt(that.attr('original_height')));  	 	
						  	 }

						}// if not open 	 

					  });
					

					  jQuery(this).on('click',function(e){
					    //  	dont flip if clicked on extend, is open or link
						  	 if(!jQuery(e.target).hasClass('extend') && 
						  	 	!that.hasClass('open') && 
						  	 	!jQuery(e.target).closest('a').length && 
						  	 	!jQuery(e.target).hasClass('sub_head_content') && 
						  	 	!jQuery(this).find('.sub_head').hasClass('open')
						  	 	)

						  	  { 

						

					  	     }


					  	     if(that.height()>209 && that.find('.lex_read_more').hasClass('no_grad') && !jQuery(e.target).closest('a').length){
				  	     		 readMoreFunction(that, el_cont, front);			
					  	     }
					  	  
					  });

					jQuery(this).addClass('show');

					addPopups(that);

            	 }); // each article

			if(searching){
				finalizeSearch(highlight);
			}

			if (callback){
				callback();
			}

        }); // ajax


};

function closeAllBack(that,flip){

		var num_open_items = that.find('.open').length;
		var count = 0;
			that.find('.open').removeClass('open').find('.hiddenbackcontent').slideUp(function(){
			that.find('.back').removeClass('table_open');
			that.removeClass('sliding');
			count ++;
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

			completeReset(false);

			searching = true;

				jQuery('.lexsearch button').addClass('no_hover');
				jQuery('.lexsearch button i').first().removeClass('fa-search').addClass('fa-circle-notch fa-spin lex-search-spinner');


				    var data = {
			            "action" : "va",
			            "namespace" : "lex_alp",
			            "query" : "get_search_results",
			            "search_val" : removeDiacritics(val),
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
			    	completeReset(false);
			    	jQuery('.lexstartcontent').hide();
			    	if(jQuery('.lex_articles .no_results').length==0){
			    		jQuery('.lex_articles').append('<div class="no_results"><?php echo $Ue['LEX_NO_RESULTS']; ?></div>') 
			    		setTimeout(function() {completeReset(true)}, 1000);
			    	}
			    };
			


			    });


    }



}

function highlightSearchResults(val){

jQuery(".lex_article").mark(val, {
    "element": "span",
    "className": "highlight"
});

}

function closeArticle(that,id){

	 delete all_active_ids[id];
		  				 
  	 if(filtered_data.length>0)filterTitelList(filtered_data);

	 that.removeClass('show');
	 setTimeout(function() {
	 	that.remove();
 	  	 if(Object.keys(all_active_ids).length==0)jQuery('.lexstartcontent').fadeIn('fast');  
	 }, 500); // move to lex callback
	 jQuery('#'+id).removeClass('active');
	 removePopus(that);
}

function openSecData(table,that){

	table.find('tr').off().on('click',function(){
				openSecTable(jQuery(this),that,false)
	})
}



function openSecTable(tr,detailview,bypass){

		if(getting_sec_data && !bypass) return;

		var main_id_comb = detailview.attr('id').split('_')[1];
		var main_type = main_id_comb.substring(0, 1);
		var main_id = main_id_comb.substr(1);

		if(!tr.next().hasClass('second_row')){

			var id = tr.attr('id');
			if(id=='')id = null;

			var type = tr.parent().parent().attr('type');
			var row = tr;

			if(id && type){

			getSecondaryData(id.substring(1), type, row, main_type, main_id);
			tr.addClass('active');

			}

		}

		else{

			tr.next().remove();
			tr.removeClass('active');
		}

}

function readMoreFunction(that, el_cont, front, callback){

that.toggleClass('open');

		if(that.hasClass('open')){
			that.height(el_cont.scrollHeight+15);
			front.height(el_cont.scrollHeight+15);
		}
		else {
			  that.height(parseInt(that.attr('original_height')));
			  front.height(parseInt(that.attr('original_height')));
			  that.find('.lex_read_more').height(50).removeClass('no_grad');
			 
		};

		var transition_event = whichTransitionEvent();

		that.one(transition_event, function () {

		    if(that.hasClass('open')){that.find('.lex_read_more').addClass('no_grad');
		     that.find('.lex_read_more').height(10).find('span').text('<?php echo $Ue['LEX_READ_LESS']; ?>');
			}
			else {
					that.find('.lex_read_more').find('span').text('<?php echo $Ue['LEX_READ_MORE']; ?>');
					//that.find('.flipbutton').fadeIn();
				}
			if(callback) callback();	
		});
}




function getSecondaryData(id, type, row, main_type, main_id){


getting_sec_data = true;

var data = {
    "action" : "va",
    "namespace" : "lex_alp",
    "query" : "get_secondary_data",
    "id" : id,
    "parent_type": main_type,
    "parent_id" : main_id,
    "type" : type
};


jQuery.post(ajax_object.ajaxurl, data, function (response){

	var res = JSON.parse(response);

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

	 row.after(table);

	 table.find('.second_table').tablesorter({theme: 'dark'});   

	 getting_sec_data = false;
	 
});


};

function getPlaceHolderText(){
	    var placeholder = '<?php echo $Ue['LEX_ARTICLE_COUNT']; ?>';
	    var res = placeholder.replace('*NUMBER*', all_data.length);
		return res;
}
		

function clickLexSearchMenu(){

		if(jQuery('.lexsidebar').hasClass('in')){
			setTimeout(function() {
				jQuery('.lexsidebar').css('top','100%');
			}, 10);
			jQuery('.lexsidebar').removeClass('in');
			jQuery('.mobile_sidebar_bg').fadeOut();

		}
		else{
			jQuery('.mobile_sidebar_bg').fadeIn();
			jQuery('.lexsidebar').show();
			setTimeout(function() {
				jQuery('.lexsidebar').css('top','185px');
			}, 10);
			jQuery('.lexsidebar').addClass('in');
		}
}


	</script>
	
	<span  style="float: right;"><input type="text" id="seachComments" placeholder="<?php _e('Search');?>"></input></span>
	
	<?php 


		echo '<div class="lex_header">';

		// echo '<div class="lex_header_cover"></div>';	
			
		echo '<div class="lex_header_inner">';	
				echo '<div class="lexcontent">';
		// echo '<div class="lexgradient"></div>';
				// echo '<div class="lexhead">Lexicon Alpinum</div>';

			echo '<div class="lexlogo lexhead">	<img class="lexlogo" src="' . VA_PLUGIN_URL . '/images/lexicon_logo.svg"/></div>';

				echo '<div class="lexsearch"><div><input></input><button class="actionbtn"><i class="fas fa-search" aria-hidden="true"></i></button><div class="lexsep"></div><button class="actionbtn lexmenubtn"><i class="fas fa-bars" aria-hidden="true"></i></button></div></div>';

	     echo '</div>';
	   echo '</div>';
	echo '</div>';

	echo '<div class="entry-content lex">';

	echo '<div class="lexcontent">';
	
	
	$pre = $vadb->get_var("SELECT Erlaeuterung_$lang FROM glossar WHERE Terminus_D = 'Präambel_LexAlp'");
	if ($pre){
		parseSyntax($pre, true);
		echo '<div class="lex_articles"><div class="lexstartcontent">' . $pre . '</div></div>';
	}
	else{
		echo '<div class="lex_articles"><div class="lexstartcontent">No description available</div></div>';
	}	


    echo '</div>'; 
	
 echo '</div>';

    //SIDEBAR

    echo '<div class="mobile_sidebar_bg"></div>';

    echo '<div class="lexsidebar">';


    echo '<div class="search">
        <i class="fas fa-search" aria-hidden="true"></i><input id="lextitelinput" type="text" class="form-control input-md" placeholder="'.$Ue['LEX_FILTER'].'"> 
    </div>';

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