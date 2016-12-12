<?php

include_once('transkription.php');

function transkription () {

$folder = VA_PLUGIN_URL . '/transkription/';

$title="Transkription";

IM_Initializer::$instance->enqueue_gui_elements();

$divaa='<iframe src="'.$folder.'ge.jpg" class="frame" id="iframe1" data-default="'.$folder.'ge.jpg"></iframe>';

$divbb='<iframe src="'. home_url('/dokumente/transkription/Codepage_Allgemein.pdf', 'https') . '" class="frame" id="iframe2" data-default="'.$folder.'ge.jpg"></iframe>';

/*$divcc='<div class="content">
				<iframe src="'.$folder.'transkription.php" class="frame" id="iframe3" onload="  var mif=document.getElementById(\'iframe3\'); var sif = mif.contentDocument || mif.contentWindow.document; sif.getElementById(\'inputAeusserung\').focus();">
				</iframe>
		</div>';*/
		
$divcc = '<div id="iframe3">' . eingabe($folder) . '</div>';

?>
<link rel="stylesheet" type="text/css" href="<?php echo $folder;?>layout.css">
<script type="text/javascript">
jQuery(document).ready(layout);
//jQuery(window).resize(layout);

function layout(){
if ( !jQuery(document.body).hasClass('folded') ) {
        jQuery(document.body).addClass('folded');
    }
	var height3 = document.getElementById('iframe3').offsetHeight;
	var height = document.getElementById('wpfooter').offsetTop;
	document.getElementById('iframe1').style.height = Math.floor(height - height3) + "px";
	document.getElementById('iframe2').style.height = Math.floor(height - height3) + "px";
}
</script>
<?php
echo '
<div id="aa">
'.$divaa.'
</div>
<div id="bb">
'.$divbb.'
</div>
<div id="cc">
'.$divcc.'
</div>';
}
?>