<?php
function pointListToKML ($sql){
	global $va_xxx;
	
	$data = $va_xxx->get_results(stripslashes($sql), ARRAY_N);
	echo '<?xml version="1.0" encoding="UTF-8"?>';
	?>


<kml xmlns="http://www.opengis.net/kml/2.2">
	<Document>
<?php foreach ($data as $d){
	?>
		<Placemark>
			<name>
				<?php echo $d[1];?>
			
			</name>
			<description>
				<?php echo $d[2];?>
			
			</description>
			<Point>
				<coordinates>
					<?php echo $d[0];?>,0
				</coordinates>
			</Point>
		</Placemark>
<?php
	}
?>
	</Document>
</kml>
<?php
}
?>