<?php

add_action('wp_ajax_get_clapie_file', 'readClapieFile');

function clapie (){
	?>
	<script type="text/javascript">
		jQuery(function (){
			jQuery("#readButton").on("click", function (){
				jQuery.post(ajaxurl, {"action": "get_clapie_file"}, function (response){
					jQuery("#output").text(response);
				});
			});
		});	
	</script>
	
	<br />
	<br />
	<input type="button" value="Read Clapie File" id="readButton" />
	
	<textarea id="output" rows="20" cols="100">
		
	</textarea>
	
	<?php
}


function readClapieFile (){

	$data = json_decode(file_get_contents(plugin_dir_path(__FILE__) . 'clapie_flat_utf8.json'));
	
	$objects = array();
	$paragraphs = array();
	$values = array();
	$connections = array();
	
	echo count($data->results);
	
	/*foreach($data->results as $index => $obj){
		$objects[$index] = array();
		foreach($obj as $key => $attr){
			if($key == 'connection'){
				if($obj->connection->connections_out){
					foreach ($obj->connection->connections_out as $conn){
						$type = $conn->connection_type;
						$tot = $conn->total;
						foreach ($conn->entities as $ent){
							$index_con = count($connections);
							$connections[$index_con] = array();
							$connections[$index_con]['connection_type'] = $type;
							$connections[$index_con]['id_to'] = $ent->id;
							$connections[$index_con]['id_from'] = $obj->id;
						}
					}
				}
			}
			else if ($key == 'paragraph'){
				foreach ($obj->paragraph as $par){
					$index_par = count($paragraphs);
					$paragraphs[$index_par] = array();
					$paragraphs[$index_par]['id_object'] = $obj->id;
					foreach ($par as $key_par => $val_par){
						if($key_par == 'value'){
							$index_val = count($values);
							$values[$index_val] = array();
							$values[$index_val]['id_paragraph'] = $par->id;
							foreach ($par->value as $key_val => $val_val){
								$values[$index_val][$key_val] = $val_val;
							}
						}
						else {
							$paragraphs[$index_par][$key_par] = $val_par;
						}
					}
				}
			}
			else {
				$objects[$index][$key] = $attr;
			}
		}
	}
	
	global $wpdb;
	$pva_clapie = new wpdb($wpdb->dbuser, $wpdb->dbpassword, 'pva_clapie', $wpdb->dbhost);
	$pva_clapie->show_errors();
	
	foreach ($objects as $object){
		if(!$pva_clapie->insert('objects', $object)){
			var_dump($object);
			die;
		}
	}
	foreach ($paragraphs as $paragraph){
		if(!$pva_clapie->insert('paragraphs', $paragraph)){
			var_dump($object);
			die;
		}
	}
	foreach ($values as $value){
		if(!$pva_clapie->insert('values', $value)){
			var_dump($object);
			die;
		}
	}
	foreach ($connections as $connection){
		if(!$pva_clapie->insert('connections', $connection)){
			var_dump($object);
			die;
		}
	}
	
	echo 'success';*/
	
	die;
}
?>