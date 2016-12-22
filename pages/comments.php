<?php
function comment_list (){
 	global $vadb;
	global $lang;
	global $Ue;
	global $admin;
	global $va_mitarbeiter;
	global $va_current_db_name;
 	
 	if(isset($_GET['prefix'])){
 		
		?>
		<script type="text/javascript">
			jQuery(function () {
				addBiblioQTips(jQuery(".entry-content"));

				jQuery(".quote").qtip({
					"show" : "click",
					"hide" : "unfocus"
				});
			})
		</script>
		<?php
		
 		echo '<div class="entry-content"><h1>' . $Ue['KOMMENTARE'] . ' "' . $vadb->get_var("SELECT getCategoryName('{$_GET['prefix']}', '$lang') FROM im_comments") . '"</h1></div><br />';
		
		$comments = $vadb->get_results("SELECT Comment, Id, Language FROM im_comments WHERE substr(Id,1,1) = '{$_GET['prefix']}' and substr(Language,1,1) = '$lang'", ARRAY_A);
		
		$commentList = array ();
		foreach ($comments as $comment){
			$title = va_sub_translate($vadb->get_var("SELECT getEntryName('{$comment['Id']}', '$lang')"), $Ue);
			$commentList[$title] = array('Id' => $comment['Id'], 'Text' => $comment['Comment']);
		}
		ksort($commentList);
		
		foreach ($commentList as $title => $comment){			
			$app = '';
			if($admin || $va_mitarbeiter){
				$app .= '<span style="font-size: 70%">   [[Kommentar:' . $comment['Id'] . ']]</span>';
			}
			if($va_current_db_name != 'va_xxx'){
				$citation = va_create_comment_citation($comment['Id'], $Ue);
				if($citation)
					$app .= '&nbsp;<span class="quote" title="' . $citation . '" style="font-size: 50%; cursor : pointer; color : grey;">(' . $Ue['ZITIEREN'] . ')</span>';
			}
			
			echo '<a name="' . $comment['Id'] . "\"></a>";
			echo '<header class="entry-header" style="margin-bottom: 1rem;"><h1 class="entry-title">' . $title . $app . '</h1></header><div class="entry-content">';
			parseSyntax($comment['Text'], true, $admin || $va_mitarbeiter);
			echo $comment['Text'] . '</div><br /><br /><br />';
		}
 	}
	else {
		?>	
		<div class="entry-content">
		 	<h1><?php echo $Ue['KOMMENTARE'];?></h1>
		 	<?php
				
			global $wp;
			$current_url = add_query_arg( $wp->query_string, '', get_permalink() );
			
			$names = $vadb->get_results("SELECT DISTINCT getCategoryName(substr(Id,1,1), '$lang') as Name, substr(Id,1,1) as Prefix FROM im_comments ORDER BY Name", ARRAY_A);
			echo '<ul>';
			foreach ($names as $name){
				echo '<li><h3><a href="' . add_query_arg('prefix', $name['Prefix'], $current_url) . '">' . $name['Name'] . '</a></h3></li>';
			}
			echo '</ul>';
			?>
			</div>
			<?php
	}
}
?>