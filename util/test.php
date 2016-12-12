<?php
	if($_REQUEST['full']){
		$red = $_REQUEST['red'];
		$green = $_REQUEST['green'];
		$blue = $_REQUEST['blue'];
		$size = $_REQUEST['size'];
		
		header ("Content-type: image/png");
		$symbol = imagecreatetruecolor($size,$size);
		$farbe = ImageColorAllocate($symbol,$red,$green,$blue);
		
		imagealphablending($symbol, false);
		$transparency = imagecolorallocatealpha($symbol, 0, 0, 0, 127);
		imagefill($symbol, 0, 0, $transparency);
		imagesavealpha($symbol, true);
		imagefilledellipse($symbol, $size/2, $size/2, $size, $size, $farbe);
		
		ImagePNG($symbol);
	}
	else if($_REQUEST['plus']){
		$red = $_REQUEST['red'];
		$green = $_REQUEST['green'];
		$blue = $_REQUEST['blue'];
		$size = $_REQUEST['size'];
		
		header ("Content-type: image/png");
		$symbol = imagecreatetruecolor($size,$size);
		$farbe = ImageColorAllocate($symbol,$red,$green,$blue);
		
		imagealphablending($symbol, false);
		$transparency = imagecolorallocatealpha($symbol, 0, 0, 0, 127);
		imagefill($symbol, 0, 0, $transparency);
		imagesavealpha($symbol, true);
		imagefilledrectangle($symbol, $size/3, 0, $size * 2 / 3, $size, $farbe);
		imagefilledrectangle($symbol, 0, $size/3, $size, $size * 2 / 3, $farbe);
		
		ImagePNG($symbol);
	}
	else if($_REQUEST['half']){
		$red = $_REQUEST['red'];
		$green = $_REQUEST['green'];
		$blue = $_REQUEST['blue'];
		$size = $_REQUEST['size'];
		
		header ("Content-type: image/png");
		$symbol = imagecreatetruecolor($size,$size);
		$farbe = ImageColorAllocate($symbol,$red,$green,$blue);
		$farbe2 = ImageColorAllocate($symbol,0,0,0);
		
		imagealphablending($symbol, false);
		$transparency = imagecolorallocatealpha($symbol, 0, 0, 0, 127);
		imagefill($symbol, 0, 0, $transparency);
		imagesavealpha($symbol, true);
		imagefilledellipse($symbol, $size/2, $size/2, $size, $size, $farbe);
		
		ImagePNG($symbol);
	}
 ?>