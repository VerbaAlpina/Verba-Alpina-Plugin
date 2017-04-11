<?php
class InternalWidget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'Intern',
			'Intern',
			array('description' => 'Interne Seiten' )
			);
	}

	
	public function widget( $args, $instance ) {
		global $va_mitarbeiter;
		global $admin;
		
		$list = array();
		
		if($va_mitarbeiter || $admin){
			$list[] = array(2724, 'Übersicht Datenerhebung');
		}
		
		if (current_user_can('va_see_progress_page')){
			$list[] = array(1839, 'Fortschritt');
		}
		
		if (current_user_can('va_transcripts_read')){
			$list[] = array(1760, 'Protokolle');
		}
		
		if(empty($list))
			return;
		
		?>

		<aside class="widget">
		
			<h3 class="widget-title">Intern</h3>
			
			<ul>
				<?php 
				foreach ($list as $link){
					echo '<li><a href="' . get_page_link($link[0]) . '">' . $link[1] . '</a></li>';
				}
				?>
			</ul>
			
		</aside>
		
		<?php
	}

	public function form( $instance ) {
		//Keine Eingaben m�glich
	}

	public function update( $new_instance, $old_instance ) {
		//Keine Eingaben m�glich
	}
}
?>