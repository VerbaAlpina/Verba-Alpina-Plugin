<?php

$srcDir = '../../../plugin_interactive-map/src';

echo "Build PHP...\n";
$phar = new Phar('im_live.phar');
$phar->buildFromDirectory($srcDir . '/php');
$phar->addFile('../db.php', 'db.php');
$phar->addFile('../map.php', 'map.php');
$phar->setStub(file_get_contents("stub.php"));

echo "Build CSS...\n";
$css_files = array(
	'../map.css',
	$srcDir . '/../src/css/styles.css',
	$srcDir . '/../src/css/google-maps.css'
);

$css = fopen('im_live.css', 'w');
foreach ($css_files as $cf){
	$in = fopen($cf, 'r');
	while ($line = fgets($in)){
		fwrite($css, $line);
	}
	fclose($in);
}
fclose($css);

echo "Build JS...\n";
echo shell_exec('ant -file compile_all.ant');
copy('../../../plugin_interactive-map/compiled/interactive_map_compiled.js', 'im_live.js');

echo 'Done';