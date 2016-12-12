<!DOCTYPE html>
<html>
	<body>

	
	
<?php
	$a = array();
	$a[] = array ('Pulle', 'm');
	var_dump(in_array(array('Pulle', 'm'), $a));
	var_dump(in_array(array('Pulle', 'f'), $a));
	$a[] = array ('Pulle', 'f');
	var_dump(in_array(array('Pulle', 'm'), $a));
	var_dump(in_array(array('Pulle', 'f'), $a));
?>

		
	</body>
</html>