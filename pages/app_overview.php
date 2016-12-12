<?php
function overview_app_page (){
	global $va_xxx;
	
	$results = $va_xxx->get_results('SELECT DISTINCT ClientType, IP, Id FROM App_Log', ARRAY_N);
		
	foreach ($results as $row){
		
		
		$sql_template = '
			SELECT Parameters, Timestamp
			FROM App_Log 
			WHERE ClientType = %s AND IP = %s AND Id = %d AND Action = %s
			ORDER BY Timestamp ASC';
		
		$community = $va_xxx->get_var($va_xxx->prepare($sql_template, $row[0], $row[1], $row[2], 'COMMUNITY'));
		
		if(!$community)
			continue;
		
		$answers = $va_xxx->get_results($va_xxx->prepare($sql_template, $row[0], $row[1], $row[2], 'ANSWER'), ARRAY_N);
		$answers = array_map(function ($e) {
			return array(json_decode($e[0]), $e[1]);
		}, $answers);

		
		$user_info = $va_xxx->get_results($va_xxx->prepare($sql_template, $row[0], $row[1], $row[2], 'USER_INFORMATION'), ARRAY_N);
		
		echo '<h3>' . $row[0] . '/' . $row[1] . '_' . $row[2] . '</h3>';
		echo 'Gemeinde: ' . $community . '<br />';
		foreach ($user_info as $info){
			$info_obj = json_decode($info[0]);
			if($info_obj[0] == 'Age'){
				echo 'Alter: ' . $info_obj[1] . '<br />';
			}
			if($info_obj[0] == 'Gender'){
				echo 'Geschlecht: ' . $info_obj[1] . '<br />';
			}
			if($info_obj[0] == 'WantsToContinue'){
				echo 'Fortsetzen: ' . $info_obj[1] . '<br />';
			}
			if($info_obj[0] == 'WantsToRegister'){
				echo 'Registrieren: ' . $info_obj[1] . '<br />';
			}
			if($info_obj[0] == 'QuestionType'){
				echo 'Frageart: ' . $info_obj[1] . '<br />';
			}
		}
		
		$a_arr = array();
		foreach ($answers as $answer){
			
			if($answer[0][1])
				$a_arr[$answer[0][0]] = $answer[0][1];
		}
		ksort($a_arr);
		
		foreach ($a_arr as $k => $v){
			echo $k . ': ' . $v . '<br />';
		}
		
	}
}
?>