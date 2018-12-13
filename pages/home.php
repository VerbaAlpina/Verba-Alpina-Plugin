<?php
function showStartPage (){
	global $va_xxx;
	global $lang;
	global $Ue;
	
	?>
	<style type="text/css">
		.version_text h5 {
			background: #008000;
			color: white;
			text-align: center;
			margin-top: 0;
			cursor: pointer;
			margin-bottom: 0;
		}
		
		.version_text span {
			padding: 5px;
			display: none;
		}
		
		.version_text {
			border-style: solid;
			border-width: 1px;
		}
		
		.version_text a:link {
			color: white;
		}
		
		.version_text a:visited {
			color: white;
		}

		#page{
          position: relative;
    	  overflow: visible;
		}

		.start_options_right{
			position: absolute;
			right: 10px;
			top:28px;
		}

		.start_options_right div{
			color: white !important;
			opacity: 0.65;
			display: inline-block;
			padding-top: 6px;
			padding-bottom: 6px;
			padding-left: 10px;
			padding-right: 12px;
			box-shadow: 0 2px 6px rgba(100, 100, 100, 0.3);
			margin-bottom: 5px;
			background: #47864c;
			font-size: 12px;
			border-radius: 4px;
			
		}

		.start_options_right a{
			text-decoration: none !important;
		}

		.start_options_right div:hover{
			opacity: 1.0;
			cursor: pointer;
		}

		.cite_btn{
	    margin-left: 5px;
		font-size: 12px;
		border-radius: 2px;
		padding: 3px 5px 3px 5px;
		box-shadow: 0 2px 6px rgba(100, 100, 100, 0.3);
	    background: #47864c;
	    color: white;
        opacity: 0.65;
       }

       .cite_btn:hover{
       		cursor: pointer;
       		opacity: 1.0;
       }

		@media only screen and (min-width: 1467px) {

			.start_options_right{
			top:15px;
			}

		}	

		@media only screen and (max-width: 959px) {
			.start_options_right{
			top:38px;
			}
		}	

		@media only screen and (max-width: 459px) {
			.start_options_right div{
			display: block;
			}
			.start_options_right{
			top:28px;
			}
		}	

	</style>
	
	<script type="text/javascript">
		jQuery(function (){
			addBiblioQTips(jQuery(".entry-content"));

		jQuery('.cite_btn').on('click',function(){
			jQuery('#cite_modal').modal();
		})

		jQuery('#copy_btn').on('click',function(){
			var text  = jQuery('#cite_field').text();
			text = text.trim();
			var doneLines = "";

			var lines = text.split('\n');
			for(var l in lines){
				var line = lines[l];
				var done = line.trim();
				doneLines += (done+'\n');
			}

			copyToClipboard(doneLines);
		})


		function copyToClipboard(text){
			    var dummy = document.createElement("input");
			    document.body.appendChild(dummy);
			    dummy.setAttribute('value', text);
			    dummy.select();
			    document.execCommand("copy");
			    document.body.removeChild(dummy);
		}

	});




	</script>
	
	<div class="entry-content">



	<div class="start_options_right">
		<a href= '<?php echo get_page_link(get_page_by_title("LIVEGRAPH")); ?>' ><div><i class="fas fa-chart-pie"></i> <?php echo ucfirst($Ue['LIVEGRAPH'])?></div></a>
		<!-- <div><i class="fas fa-graduation-cap"></i> TUTORIAL </div>  TODO: ADD WHEN AVAILABLE-->  
	 </div>

		<?php	
		
		global $va_xxx;
		global $admin;
		global $va_mitarbeiter;
		global $va_current_db_name;
		$intern = ($admin || $va_mitarbeiter) && $va_current_db_name == 'va_xxx';
		
		$curr_version = $va_xxx->get_var('SELECT MAX(Nummer) FROM Versionen', 0, 0);
		// if(isDevTester()){
		// 	echo '<div class="version_text">';
		// 	echo "<h5 onClick='jQuery(\".version_text span\").toggle();'>+++ " . $Ue['VERSION'] . ' '. va_format_version_number($curr_version) . ' ' . $Ue['ERLAEUTERUNGEN'] . ' +++</h5>';
		// 	$text = $va_xxx->get_var("SELECT Erlaeuterung_$lang FROM Glossar WHERE Terminus_D = 'Version$curr_version'");
		// 	parseSyntax($text, false, $intern);
		// 	echo '<span>' . $text . '</span>';
		// 	echo '</div>';
		// }
		?>


		
		<h3><?php echo $Ue['LEITUNG']; ?></h3>
		Thomas Krefeld | Stephan Lücke



		<?php
// 		$tutorial_post = get_page_by_title('Interaktive Karte Tutorial', OBJECT, 'post');
// 		if($tutorial_post){
// 			if(function_exists('mlp_get_linked_elements')){ //Translated version
// 				$linked = mlp_get_linked_elements( 3105, '', 1 );
// 			}
// 			else {
// 				$linked = array(3105);
// 			}
			
// 			echo '<span class="eyecatcher"><a href="' . get_permalink($linked[get_current_blog_id()]) . '">TUTORIAL</a></span>';
// 		}
		?>
		
		<h3><?php echo $Ue['DAS_PROJEKT']; ?>	
			<span class="cite_btn"><i class="fas fa-book" style="padding-right: 2px;"></i>
			<?php echo $Ue['ZITIEREN']; ?>
			</span>
		</h3>	


		
		<?php
		$res = $va_xxx->get_results('SELECT Id_Eintrag, Erlaeuterung_' . $lang . " AS Text FROM Glossar WHERE Terminus_D = 'Projektbeschreibung'", ARRAY_A);
		parseSyntax($res[0]['Text'], false, $intern);
		va_add_glossary_meta_information($res, $lang);
		echo $res[0]['Text'];
		echo '<br />';
		echo va_add_glossary_authors($res[0]['Autoren'], $res[0]['Uebersetzer']);
		?>
	</div>



<div id="cite_modal" class="modal fade top_menu_modal">
  <div class="modal-dialog">
    <div class="modal-content">

     <div class="modal-header">
        <h5 class="modal-title"><span><?php echo $Ue['ZITIEREN']; ?> </span></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>

       <div class="modal-body">
       <div style="line-height: 1.45;" id="cite_field">
   	    Krefeld, Thomas | Lücke, Stephan (Hrsgg.) (2014–): <br>
   	    VerbaAlpina. Der alpine Kulturraum im Spiegel seiner Mehrsprachigkeit,<br> München, online, 
		http://dx.doi.org/10.5282/verba-alpina
		</div>	 
        
      </div>

      <div class="modal-footer">
      	<button id="copy_btn" style="background: transparent;">Kopieren</button>
      </div>

    </div>
  </div>
</div>

	<?php
}
?>