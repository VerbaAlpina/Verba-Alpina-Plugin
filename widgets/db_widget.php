<?php
//Widget zur Auswahl der Datenbank-Version
class VersionWidget extends WP_Widget {

	private static $col_current = "#FFA9A9";
	private static $col_last = "#8FFA81";
	private static $col_old = "#e6e6e6";

	public function __construct() {
		parent::__construct(
			'Version',
			'Datenbank Version',
			array('description' => 'Auswahl der zu benutzenden Datenbank-Version' )
			);
		global $va_xxx;
		$this->versionen = $va_xxx->get_results('SELECT Nummer from Versionen WHERE Website ORDER BY Nummer DESC', ARRAY_N);
	}

	
	public function widget( $args, $instance ) {
		global $admin;
		global $va_mitarbeiter;
		global $Ue;
		global $va_current_db_name;
		
		$col_selected = self::getVersionColor($va_current_db_name);
		
		echo '<h3 class="widget-title">' . $Ue['DATENBANK_VERSION'];
		echo '&nbsp;' . va_get_glossary_help(61, $Ue) . '</h3>';
		echo '<br />';
		echo '<select class="noChosen" id="changeDBSelect" style="background-color: ' . $col_selected . '" autocomplete="off">'; //js in tools.js
		
		echo '<option value="va_xxx"' . ($va_current_db_name == 'va_xxx'? ' selected' : '') . ' style="background-color: ' . self::$col_current . '">XXX  (' . $Ue['ARBEITSVERSION'] . ')</option>';
		
		$first = true;
		foreach ($this->versionen as $version){
		if($first){
			echo '<option value="va_' . $version[0] . '" ' . ('va_' . $version[0] == $va_current_db_name? 'selected ': '') . ' style="background-color: ' . self::$col_last . '">' . va_format_version_number($version[0]) . ' (' . $Ue['ZITIERVERSION'] . ')</option>';
			$first = false;
		}
		else
			echo '<option value="va_' . $version[0] . '" ' . ('va_' . $version[0] == $va_current_db_name? 'selected ': '') . ' style="background-color: ' . self::$col_old . '">' . va_format_version_number($version[0]) . '</option>';
		}
		echo '</select><br /><br /><br />';
	}

	public function getVersionColor ($db){
		
		if($db == 'va_xxx')
			return self::$col_current;
		if($db == 'va_' . $this->versionen[0][0])
			return self::$col_last;
		return self::$col_old;
	}

	public function form( $instance ) {
		//Keine Eingaben möglich
	}

	public function update( $new_instance, $old_instance ) {
		//Keine Eingaben möglich
	}
}
?>