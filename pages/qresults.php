<?php 
function show_questionnaire_results ($attrs){
	global $wpdb;
	global $va_xxx;
	
	$export = false;
	if (isset($_REQUEST['format']) && $_REQUEST['format'] == 'export'){
		$export = true;
	}
	
	?>
<script type="text/javascript">
	jQuery(function (){
		jQuery(".point").each(function (){
			var that = this;
			jQuery.post(ajax_object.ajaxurl, {
				"action" : "va",
				"namespace" : "util",
				"query" : "get_community_name",
				"point" : jQuery(this).data("point")
			}, function (response){
				jQuery(that).parent().html(response);
			});
		});

		jQuery("#format").change(function (){
			reloadPageWithParam("format", jQuery(this).val());
		});

		<?php if ($export){
		?>
		jQuery("#format").val("export");

		jQuery("#page").css("max-width", "100%");
		jQuery("#page").css("width", "100%");
		jQuery("#page").css("margin-left", "0");
		jQuery("#page").css("margin-right", "0");
		<?php
		}
		?>
	});
</script>
	
	<select id="format">
		<option value="standard">Standard</option>
		<option value="export">Export</option>
	</select>
	<?php

	$condition = '';
	if (isset($attrs['minpages'])){
		$condition .= $wpdb->prepare(' AND EXISTS (SELECT * FROM questionnaire_results q2 WHERE q.user_id = q2.user_id AND page = %d)', $attrs['minpages']);
	}
	
	$results = $wpdb->get_results($wpdb->prepare('
		SELECT page, user_id, question, question_text, (
			SELECT REPLACE(answer, "###", ", ")
			FROM questionnaire_results q2 
			WHERE q2.page = q.page and q2.user_id = q.user_id and q2.question = q.question
			ORDER BY timestamp DESC
			LIMIT 1
		) as answer
		FROM questionnaire_results q
		WHERE post_id = %d AND page IS NOT NULL' . $condition . '
		GROUP BY page, user_id, question
		ORDER BY user_id ASC, page ASC, question ASC', $attrs['id']), ARRAY_A);

	if ($export){
		$questions = $wpdb->get_results($wpdb->prepare('
		SELECT DISTINCT page, question 
		FROM questionnaire_results 
		WHERE post_id = %d AND page IS NOT NULL
		ORDER BY page, question', $attrs['id']), ARRAY_A);
		
		echo '<div style="overflow-x: scroll; width: 100%;"><table style="table-layout: fixed; width: 100%;"><thead><tr><th>User-ID</th>';
		foreach ($questions as $question){
			echo '<th>' . ($question['page'] + 1) . '#' . ($question['question'] + 1) . '</th>';
		}
		echo '</tr></thead><tbody>';
		
		$rows = [];
		foreach ($results as $result){
			if (!isset($rows[$result['user_id']])){
				$rows[$result['user_id']] = [];
			}
			
			$idq = ($result['page'] + 1) . '#' . ($result['question'] + 1);
			$rows[$result['user_id']][$idq] = $result['answer'];
		}
		
		foreach ($rows as $id => $row){
			echo '<tr><td>' . $id . '</td>';
			foreach ($row as $answer){
				echo va_quest_format_answer($answer);
			}
			echo '</tr>';
		}
		
		echo '</tbody></table>';
	}
	else {
		echo '<table class="easy-table easy-table-default"></div>';
		echo '<thead><tr><th>User-ID</th><th>Seite</th><th>Fragenummer</th><th>Frage</th><th>Antwort</th></tr></thead><tbody>';
		
		$colors = ['white', 'whitesmoke'];
		$colorIndex = 0;
		
		foreach ($results as $result){
			if ($result['page'] == 0 and $result['question'] == 0){
				$colorIndex = ($colorIndex + 1) % count($colors);	
			}
			
			echo '<tr style="background: ' . $colors[$colorIndex] . '">';
			echo '<td>' . $result['user_id'] . '</td>';
			echo '<td>' . ($result['page'] + 1) . '</td>';
			echo '<td>' . ($result['question'] + 1) . '</td>';
			echo '<td>' . explode("\n", $result['question_text'])[0] . '</td>';
			echo va_quest_format_answer($result['answer']);
			echo '</tr>';
		}
		
		echo '</tbody></table>';
	}
}

function va_quest_format_answer ($answer){
	if(mb_strpos($answer, 'POINT') === 0){
		return '<td><img src="' . VA_PLUGIN_URL . '/images/Loading.gif" class="point" data-point="' . $answer . '" /></td>';
	}
	else {
		return '<td>' . $answer . '</td>';
	}
}
?>