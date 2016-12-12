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
	</style>
	
	<div class="entry-content">

		<?php	
		
		global $va_xxx;
		$curr_version = $va_xxx->get_var('SELECT MAX(Nummer) FROM Versionen', 0, 0);
		if(isDevTester()){
			echo '<div class="version_text">';
			echo "<h5 onClick='jQuery(\".version_text span\").toggle();'>+++ " . $Ue['VERSION'] . ' '. va_format_version_number($curr_version) . ' ' . $Ue['ERLAEUTERUNGEN'] . ' +++</h5>';
			$text = $va_xxx->get_var("SELECT Erlaeuterung_$lang FROM Glossar WHERE Terminus_D = 'Version$curr_version'");
			parseSyntax($text);
			echo '<span>' . $text . '</span>';
			echo '</div>';
		}
		?>
		
		<h3><?php echo $Ue['LEITUNG']; ?></h3>
		Thomas Krefeld | Stephan Lücke
		
		<?php
		$tutorial_post = get_page_by_title('Interaktive Karte Tutorial');
		if($tutorial_post){
			if(function_exists('mlp_get_linked_elements')){ //Translated version
				$linked = mlp_get_linked_elements( 3105, '', 1 );
			}
			else {
				$linked = array(3105);
			}
			
			echo '<span class="eyecatcher"><a href="' . get_permalink($linked[get_current_blog_id()]) . '">TUTORIAL</a></span>';
		}
		?>
		
		<h3><?php echo $Ue['DAS_PROJEKT']; ?></h3>
		
		<?php
		$res = $va_xxx->get_results('SELECT Id_Eintrag, Erlaeuterung_' . $lang . " FROM Glossar WHERE Terminus_D='Projektbeschreibung'", ARRAY_N);
		parseSyntax($res[0][1], true);
		va_add_glossary_meta_information($res, $lang);
		echo $res[0][1];
		echo '<br />';
		va_add_glossary_authors($res[0][3], $res[0][4]);
		?>
	</div>
	<?php
}
?>