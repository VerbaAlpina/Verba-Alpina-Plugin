
var block_click = false;

function addHelpTourLex(translations){

const tour = new Shepherd.Tour({
  defaultStepOptions: {
    cancelIcon: {
      enabled: true
    },
    classes: 'class-1 class-2'
  },
  useModalOverlay: true
});

tour.addStep({
  title: 'LEXICON ALPINUM',
  text: translations['step_1'],
  attachTo: {
    element: '.lexsearch input',
    on: 'bottom'
  },
  buttons: [
    {
      action() {
       this.cancel();
      },
      text: translations['end_tour']
    },
    {
      action() {
       this.next();
      },
      text: translations['next']
    }
  ],
  id: 'step_1'
});

tour.addStep({
  title: 'LEXICON ALPINUM',
  text: translations['step_2'],
  attachTo: {
    element: '.lexsearch .actionbtn i',
    on: 'bottom'
  },
  buttons: [
    {
      action() {
       this.back();
      },
      text: translations['back']
    },
    {
      action() {
    	if(window.innerWidth<=768){
    		  var that = this;
    			jQuery('.lexmenubtn').click();
    			  				setTimeout(function() { 
    			  				that.next();			
						}, 600);	

    	}
    	else{
        this.next();
    	}
    
      },
      text: translations['next']
    }
  ],
  id: 'step_2',
  when: {
  show: function() {
      block_click = false;
  }
}
});

tour.addStep({
  title: 'LEXICON ALPINUM',
  text: translations['step_3'],
  attachTo: {
    element: '#lextitelinput',
    on: (window.innerWidth<=768) ? 'bottom' : 'right'
  },
  buttons: [
    {
      action() {
    		if(window.innerWidth<=768) {
    			slideMobileMenuDown();
    			this.back();
    		}else{
    		  this.back();
    		}
      },
      text: translations['back']
    },
    {
      action() {
        this.next();
      },
      text: translations['next']
    }
  ],
  id: 'step_3',
  when: {
  show: function() {
      block_click = false;
  }
}
});


tour.addStep({
  title: 'LEXICON ALPINUM',
  text: translations['step_4'],
  attachTo: {
    element: '.lex_abc > div:nth-child(11)',
    on: (window.innerWidth<=768) ? 'bottom' : 'right'
  },
  buttons: [
    {
      action() {
        this.back();
      },
      text: translations['back']
    },
    {
      action() {
       this.next();
      },
      text: translations['next']
    }
  ],
  id: 'step_4'
});

tour.addStep({
  title: 'LEXICON ALPINUM',
  text: translations['step_5'],
  attachTo: {
    element: '#scrollArea',
    on: (window.innerWidth<=768) ? 'bottom' : 'right'
  },
  buttons: [
    {
      action() {
        this.back();
      },
      text: translations['back']
    },
     {
      action() {
        if(block_click) return;
        block_click = true;
      	var that = this;
      	jQuery('.lexstartcontent').fadeOut();
      	jQuery('html').css('overflow-y','hidden');
    		completeReset(false, true);
    		if(window.innerWidth<=768) {
    			slideMobileMenuDown();
    		}
      	addArticlesByIds(["C612"], null, true, null, function(){
      				setTimeout(function() { 
      					  jQuery('html').css('overflow-y','auto');
  					   	  prevent_tour_click = true;
      						that.next();
      				}, 600);	
      	});
      },
      text: translations['next']
    }
  ],
  id: 'step_5',
    when: {
  show: function() {
      block_click = false;
  }
}
});



tour.addStep({
  title: 'LEXICON ALPINUM',
  text: translations['step_6'],
  attachTo: {
    element: '.lex_article .lex_button_container',
    on: (window.innerWidth<=768) ? 'bottom' : 'left'
  },
  buttons: [
    {
      action() {
        if(block_click) return;
            block_click = true;
           	if(window.innerWidth<=768){
    		  var that = this;
    			jQuery('.lexmenubtn').click();
    			  				setTimeout(function() { 
    			  				that.back();			
						}, 600);	

    	}
    	else{
         this.back();
    	}
      },
      text: translations['back']
    },
     {
      action() {
         this.next();
      },
      text: translations['next']
    }
  ],
  id: 'step_6',
  when: {
  show: function() {
      block_click = false;
  }
}
});

tour.addStep({
  title: 'LEXICON ALPINUM',
  text: translations['step_7'],
  attachTo: {
    element: '.lex_read_more > span',
    on: 'bottom'
  },
  buttons: [
    {
      action() {
        this.back();
      },
      text: translations['back']
    },
    {
      action() {
      	prevent_tour_click = false;
         this.next();
      },
      text: translations['next']
    }
  ],
  id: 'step_7',
  when: {
  show: function() {
      block_click = false;
  }
}
});

tour.addStep({
  title: 'LEXICON ALPINUM',
  text: translations['step_8'],
  attachTo: {
    element: '.flipbutton',
    on: (window.innerWidth<=768) ? 'bottom' : 'left'
  },
  buttons: [
    {
      action() {
         this.back();
      },
      text: translations['back']
    },
    {
      action() {
        if(block_click) return;
        block_click = true;
      	var that = this;
      	prevent_tour_click = false;
        jQuery('.flipbutton').click();
           		setTimeout(function() { 
           				that.next();
				}, 800);	
      },
      text: translations['next']
    }
  ],
  id: 'step_8',
  when: {
  show: function() {
    	jQuery('.lex_read_more').click();
    	  	prevent_tour_click = true;
          block_click = false;
  }
}
});


tour.addStep({
  title: 'LEXICON ALPINUM',
  text: translations['step_9'],
  attachTo: {
    element: '.sub_head:first-child .sub_head_content',
    on: 'bottom'
  },
  buttons: [
    {
      action() {
        if(block_click) return;
        block_click = true;
      	var that = this;
        prevent_tour_click = false;
        jQuery('.flipbutton').click();
      		setTimeout(function() { 
			    			that.back();
					}, 600);	
      },
      text: translations['back']
    },
    {
      action() {
      if(block_click) return;
      block_click = true;
    	prevent_tour_click = false;
     	var that = this;
          var clicked_item_tour =  jQuery('.sub_head:first-child');
          clicked_item_tour.toggleClass('open');
          clicked_item_tour.addClass('sliding');
          var article = jQuery('#detailview_C612');
          slideSubHeadDown(article, clicked_item_tour, function(){
              that.next();
          });
      },
      text: translations['next']
    }
  ],
  id: 'step_9',
  when: {
  show: function() {
          block_click = false;
    	  	prevent_tour_click = true;
  }
}
});


tour.addStep({
  title: 'LEXICON ALPINUM',
  text: translations['step_10'],
  attachTo: {
    element: '#L465',
    on: 'bottom'
  },
  buttons: [
    {
      action() {
      if(block_click) return; 
      block_click = true;
			var that = this;
			prevent_tour_click = false;
			jQuery('.sub_head:first-child .sub_head_content').click();
			setTimeout(function() { 
			that.back();
			}, 600);	
      },
      text: translations['back']
    },
    {
      action() {
            if(block_click) return;
            block_click = true; 
      	 	  prevent_tour_click = false;
				    jQuery('#L465').click();
				    this.next();
      },
      text: translations['next']
    }
  ],
  id: 'step_10',
  when: {
  show: function() {
          block_click = false;
    	  	prevent_tour_click = true;
  }
}
});

tour.addStep({
  title: 'LEXICON ALPINUM',
  text: translations['step_11'],
  attachTo: {
    element: '.flipbutton',
    on: (window.innerWidth<=768) ? 'bottom' : 'left'
  },
  buttons: [
    {
      action() {
        if(block_click) return; 
        block_click = true;
      	prevent_tour_click = false;
      	 jQuery('#L465').click();
         return this.back();
      },
      text: translations['back']
    },
    {
      action() {
        if(block_click) return; 
        block_click = true;
      	var that = this;
      	prevent_tour_click = false;
        jQuery('.flipbutton').click();
           		setTimeout(function() { 
           				that.next();
				}, 1500);	
      },
      text: translations['next']
    }
  ],
  id: 'step_11',
  when: {
  show: function() {
          block_click = false;
    	  	prevent_tour_click = true;
  }
}
});


tour.addStep({
  title: 'LEXICON ALPINUM',
  text: translations['step_12'],
  attachTo: {
    element: '.tb_share_menu i',
    on: 'bottom'
  },
  buttons: [
    {
      action() {
        if(block_click) return; 
        var that = this;
        prevent_tour_click = false;
        completeReset(true, true);
        this.complete();
      },
       text: translations['end_tour']
    }
  ],
  id: 'step_12',
  when: {
  show: function() {
          block_click = false;
          prevent_tour_click = true;
  }
}
});


tour.on("show", function(){
	setTimeout(function() { 

		 if(tour.getCurrentStep().id=="step_1" 
 		  || tour.getCurrentStep().id=="step_3"
      || tour.getCurrentStep().id=="step_4"
 		  || tour.getCurrentStep().id=="step_5"){
		 	 	completeReset(true, true)
		 	 	prevent_tour_click = false;
		 }

		 else 	{
		  //do not reset and not enable click
		}

  // console.log(tour.getCurrentStep().id)

	}, 10);	
	


})


tour.on("cancel", function(){
	  prevent_tour_click = false;
})

tour.on("complete", function(){
	  prevent_tour_click = false;
})

jQuery('.tb_i_container.help').on('click',function(){
		tour.start();
})

return tour;

}