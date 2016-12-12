<?
if ($_REQUEST[results]=="qual") {
  createSymbolResult($_REQUEST[color],$_REQUEST[size]);
}

if ($_REQUEST[results]=="quant") {
  createSymbolSimilarity($_REQUEST[red],$_REQUEST[green],$_REQUEST[blue]);
}

if ($_REQUEST[shape]=="circle") {
  createCircle();
}

if ($_REQUEST[Mundartbezirk]) {
  createSymbolMundartbezirke($_REQUEST[Mundartbezirk]);
}

function createSymbolResult($color,$size) {
$colors=array(
	 schwarz 	=> array(0,0,0),
	 rot 		=> array(200,0,0),
	 blau 		=> array(0,0,255),
	 gelb 		=> array(255,255,0),
	 moosgruen 		=> array(0,128,64),
	 braun 		=> array(187,130,88),
	 orange 		=> array(255,128,0),
	 tuerkis 	=> array(0,255,255),
	 pink 	=> array(255,0,255),
	 gruen 	=> array(0,255,0),
	 grau 		=> array(150,150,150),
	 gelbgruen 	=> array(224,255,32),
	 violett 	=> array(140,25,198),
	 asica	 	=> array(223,245,251),
	 weiss 		=> array(255,255,255),
 );

$characters=array(
  1 => "A",
  2 => "B",
  3 => "C",
  4 => "D",
  5 => "E",
  6 => "F",
  7 => "G",
  8 => "H",
  9 => "I",
  10 => "J",
  11 => "K",
  12 => "L",
  13 => "M",
  14 => "N",
  15 => "O",
  16 => "P",
  17 => "Q",
  18 => "R",
  19 => "S",
  20 => "T",
  21 => "U",
  22 => "V",
  23 => "W",
  24 => "X",
  25 => "Y",
  26 => "Z",
  27 => "1",
  28 => "2",
  29 => "3",
  30 => "4",
  31 => "5",
  32 => "6",
  33 => "7",
  34 => "8",
  35 => "9",
);

$beschriftung=$characters[$color];

header ("Content-type: image/png");
$symbol = imagecreate($size,$size);
foreach($colors as $werte) {
  $farben[]=ImageColorAllocate($symbol,$werte[0],$werte[1],$werte[2]);
}
$fuellfarbe=$farben[$color];

if($color==3 || $color==5 || $color==6 || $color==7 || $color==8 || $color==9 || $color==10 || $color==11 || $color==13 || $color==14) {
  $textfarbe=$farben[0];
}
else {
  $textfarbe=$farben[14];
}
if($_REQUEST[sw]=="1") {
  $fuellfarbe=$farben[0];
  $textfarbe=$farben[14];
}
if($color>"15") {
  $fuellfarbe=$farben[0];
  $textfarbe=$farben[14];
}
$rahmenfarbe=ImageColorAllocate($symbol,0,0,0);
//imageFill($symbol,0,0,$farben[$color]);
imageFill($symbol,0,0,$fuellfarbe);
imagestring  ( $symbol , 1  ,  3  ,  1  ,  $beschriftung  ,  $textfarbe  );
imagerectangle($symbol,0,0,$size-1,$size-1,$rahmenfarbe);
$bild=ImagePNG($symbol);
return $bild;
}

function createSymbolSimilarity($red,$green,$blue) {
  $text=$_REQUEST["symbol"];
  $size=$_REQUEST[size];
	$total_lines=1;
	$line_number=1;
	$fontsize=round($size*0.1,0);
	$startx=ceil(($size-ImageFontWidth($fontsize) * strlen($text))/2);
	$starty=ceil((($size-ImageFontHeight($fontsize)*$total_lines)/2)+(($line_number-1)*ImageFontHeight($fontsize)));
  header ("Content-type: image/png");
  $symbol = imagecreate($size,$size);
  $farbe=ImageColorAllocate($symbol,$red,$green,$blue);
  if(0.2126*$red+0.7152*$green+0.0722*$blue < 256/2) { // Luminanz!
   $textfarbe=ImageColorAllocate($symbol,255,255,255);
  }
  else {
   $textfarbe=ImageColorAllocate($symbol,0,0,0);
  }
  $rahmenfarbe=ImageColorAllocate($symbol,0,0,0);
  imageFill($symbol,0,0,$farbe);
  imagestring ($symbol,$fontsize,$startx,$starty,$text,$textfarbe);
	imagerectangle($symbol,0,0,$size-1,$size-1,$rahmenfarbe);
  $bild=ImagePNG($symbol);
  return $bild;
}

function createCircle() {
// create image
$image = imagecreatetruecolor(100, 100);

// allocate some colors
$white    = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);
$gray     = imagecolorallocate($image, 0xC0, 0xC0, 0xC0);
$darkgray = imagecolorallocate($image, 0x90, 0x90, 0x90);
$navy     = imagecolorallocate($image, 0x00, 0x00, 0x80);
$darknavy = imagecolorallocate($image, 0x00, 0x00, 0x50);
$red      = imagecolorallocate($image, 0xFF, 0x00, 0x00);
$darkred  = imagecolorallocate($image, 0x90, 0x00, 0x00);

// make the 3D effect
for ($i = 60; $i > 50; $i--) {
   imagefilledarc($image, 50, $i, 100, 50, 0, 45, $darknavy, IMG_ARC_PIE);
   imagefilledarc($image, 50, $i, 100, 50, 45, 75 , $darkgray, IMG_ARC_PIE);
   imagefilledarc($image, 50, $i, 100, 50, 75, 360 , $darkred, IMG_ARC_PIE);
}

imagefilledarc($image, 50, 50, 100, 50, 0, 45, $navy, IMG_ARC_PIE);
imagefilledarc($image, 50, 50, 100, 50, 45, 75 , $gray, IMG_ARC_PIE);
imagefilledarc($image, 50, 50, 100, 50, 75, 360 , $red, IMG_ARC_PIE);
$bild=ImageGIF($image);
return $bild;
}


function createSymbolMundartbezirke($nr) {
// Create image
$width=$height=30;
$im = imagecreatetruecolor($width, $height);
$fontgroesse=ceil($width*0.5);

// Set alphablending to on
imagealphablending($im, true);

$white    = imagecolorallocate($im, 0xFF, 0xFF, 0xFF);
$gray     = imagecolorallocate($im, 0xC0, 0xC0, 0xC0);
$darkgray = imagecolorallocate($im, 0x90, 0x90, 0x90);
$navy     = imagecolorallocate($im, 0x00, 0x00, 0x80);
$darknavy = imagecolorallocate($im, 0x00, 0x00, 0x50);
$red      = imagecolorallocate($im, 0xFF, 0x00, 0x00);
$darkred  = imagecolorallocate($im, 0x90, 0x00, 0x00);
$black    = imagecolorallocate($im, 0x00, 0x00, 0x00);

// Draw a square
//imagefilledarc($im, 30, 30, 70, 70, imagecolorallocate($im, 255, 0, 0));

// Draw a circle
imagefilledarc($im, floor($width*0.5), floor($height*0.5), $width-1, $height-1, 0, 360, $white, IMG_ARC_PIE);
$thickness=1;
imagesetthickness($im,$thickness);
imagearc($im, floor($width*0.5), floor($height*0.5), $width-$thickness, $height-$thickness, 0, 359.9, $darkred);
// NOTE: Don't put exactly 360 instead of 359.9 because it seems that the implementation makes the test and uses imageellipse instead! - imagesetthickness hat keinen Effekt bei imageellipse!!!
imagecolortransparent  ( $im  ,  $black );
// Output
header('Content-type: image/png');

CenterImageString($im, $width, $nr, $fontgroesse, $height, $darknavy);

$bild=imagepng($im);
//imagedestroy($im);
return $bild;
}

function CenterImageString($image, $image_width, $string, $font_size, $image_height, $color) {
 $font = dirname($_SERVER["SCRIPT_FILENAME"]) . "/timesbd.ttf"; 
 $x = floor($image_width-$font_size/1.8*strlen($string))/2;
 $y = floor(0.49*($image_height+$font_size));
 imagettftext ($image, $font_size, 0, $x, $y, $color, $font, $string); 
}

?>
