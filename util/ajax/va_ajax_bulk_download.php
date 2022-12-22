<?php


function va_ajax_bulk_download (&$db){

	$query = $_POST['query'];
	$count = 0;

	foreach(preg_split("/((\r?\n)|(\r\n?))/", $query) as $line){

		if(strlen($line)==0)return;

		$parts = explode(";", $line);
		$id = $parts[0];
		$url = $parts[1];

		if($id && strlen($url)>0){
	    	uploadImageToMediaLib($url , $id, $db, $count);
	    }
	} 




}


function uploadImageToMediaLib($url, $id, &$db){

include(get_home_path()."wp-load.php");
include_once(get_home_path()."wp-admin/includes/image.php");

$imageurl = $url;
$imagetype = end(explode('/', getimagesize($imageurl)['mime']));
$uniq_name = date('dmY').''.(int) microtime(true); 
$filename = 'bulk_'.$uniq_name.'.'.$imagetype;

$uploaddir = wp_upload_dir();
$uploadfile = $uploaddir['path'] . '/' . $filename;

$contents= file_get_contents($imageurl);
$savefile = fopen($uploadfile, 'w');
fwrite($savefile, $contents);
fclose($savefile);

$wp_filetype = wp_check_filetype(basename($filename), null );
$attachment = array(
    'post_mime_type' => $wp_filetype['type'],
    'post_title' => $filename,
    'post_content' => '',
    'post_status' => 'inherit'
);

$attach_id = wp_insert_attachment( $attachment, $uploadfile );
$imagenew = get_post( $attach_id );
$fullsizepath = get_attached_file( $imagenew->ID );
$attach_data = wp_generate_attachment_metadata( $attach_id, $fullsizepath );
wp_update_attachment_metadata( $attach_id, $attach_data ); 

 //echo $attach_id;

//insertDBRelation($attach_id,$id, $db);


}

function insertDBRelation($Id_Medium, $Id_Konzept, &$db){
   
 $stmnt =  $db->prepare('INSERT INTO VTBL_Medium_Konzept (Id_Medium, Id_Konzept) VALUES (%d, %d)', $Id_Medium,  $Id_Konzept );
 //echo $stmnt;
 $db->query($stmnt);

}





?>