<?php
function va_call_list (){
	$posts = get_posts([
		'post_type' => 'ausschreibung',
		'posts_per_page' => -1
	]);
	
	$res = '<ul style="margin-top: 30px">';
	foreach ($posts as $post){
		$terms = array_map(function ($e){return $e->name;}, wp_get_post_terms($post->ID, 'fach'));
		$termStr = '';
		
		if(count($terms) > 0){
			$termStr = ' (' . implode(', ', $terms) . ')';
		}
		
		$res .= '<li><a href="' . get_permalink($post) . '">' . get_the_title($post) . '</a>' . $termStr . '</li>';
	}
	$res .= '</ul>';
	
	return $res;
}