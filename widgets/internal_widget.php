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
		?>
		
		<aside class="widget">
		
			<h3 class="widget-title">Intern</h3>
			
			<ul>
				<li>
					<a href="<?php echo get_page_link(1839);?>"> Fortschritt </a>
				</li>
				
				<li>
					<a href="<?php echo get_page_link(1760);?>"> Protokolle </a>
				</li>
				
				<li>
					<a href="<?php echo get_page_link(2724);?>"> Übersicht Datenerhebung </a>
				</li>
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