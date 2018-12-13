<?php
if (function_exists ('acf_add_local_field_group')) :
	
	acf_add_local_field_group (array(
		'key' => 'group_5b226c1432da9',
		'title' => 'Fragebogen',
		'fields' => array(
			array(
				'key' => 'field_fb_seiten',
				'label' => 'Seite',
				'name' => 'fb_seite',
				'type' => 'repeater',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => ''
				),
				'collapsed' => '',
				'min' => 0,
				'max' => 0,
				'layout' => 'table',
				'button_label' => 'Seite hinzufügen',
				'sub_fields' => array(
					array(
						'key' => 'field_5b226c1b78926',
						'label' => 'Frage',
						'name' => 'fb_frage',
						'type' => 'repeater',
						'instructions' => '',
						'required' => 0,
						'conditional_logic' => 0,
						'wrapper' => array(
							'width' => '',
							'class' => '',
							'id' => ''
						),
						'collapsed' => '',
						'min' => 0,
						'max' => 0,
						'layout' => 'table',
						'button_label' => 'Frage hinzufügen',
						'sub_fields' => array(
							array(
								'key' => 'field_5b226c3578927',
								'label' => 'Beschreibung',
								'name' => 'fb_uberschrift',
								'type' => 'wysiwyg',
								'instructions' => '',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array(
									'width' => '40',
									'class' => '',
									'id' => ''
								),
								'default_value' => '',
								'tabs' => 'all',
								'toolbar' => 'full',
								'media_upload' => 1,
								'delay' => 0
							),
							array(
								'key' => 'field_5b226c4078928',
								'label' => 'Typ',
								'name' => 'fb_typ',
								'type' => 'select',
								'instructions' => '',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array(
									'width' => '20',
									'class' => '',
									'id' => ''
								),
								'choices' => array(
									'Text' => 'Text',
									'Auswahl' => 'Auswahl',
									'Karte' => 'Karte'
								),
								'default_value' => array(),
								'allow_null' => 0,
								'multiple' => 0,
								'ui' => 0,
								'ajax' => 0,
								'return_format' => 'value',
								'placeholder' => ''
							),
							array(
								'key' => 'field_5b226c5c78929',
								'label' => 'Details',
								'name' => 'fb_details',
								'type' => 'group',
								'instructions' => '',
								'required' => 0,
								'conditional_logic' => 0,
								'wrapper' => array(
									'width' => '40',
									'class' => '',
									'id' => ''
								),
								'layout' => 'block',
								'sub_fields' => array(
									array(
										'key' => 'field_necessary',
										'label' => 'Notwendig',
										'name' => 'fb_necessary',
										'type' => 'true_false',
										'instructions' => '',
										'required' => 0,
										'conditional_logic' => 0,
										'wrapper' => array(
											'width' => '',
											'class' => '',
											'id' => ''
										),
										'message' => '',
										'default_value' => 0,
										'ui' => 0,
										'ui_on_text' => '',
										'ui_off_text' => '',
									),
									array(
										'key' => 'field_5b226c687892a',
										'label' => 'Optionen',
										'name' => 'fb_cb_optionen',
										'type' => 'textarea',
										'instructions' => '',
										'required' => 0,
										'conditional_logic' => array(
											array(
												array(
													'field' => 'field_5b226c4078928',
													'operator' => '==',
													'value' => 'Auswahl'
												)
											)
										),
										'wrapper' => array(
											'width' => '',
											'class' => '',
											'id' => ''
										),
										'default_value' => '',
										'placeholder' => '',
										'maxlength' => '',
										'rows' => '',
										'new_lines' => ''
									),
									array(
										'key' => 'field_5b226d5d545a8',
										'label' => 'Mittelpunkt',
										'name' => 'fb_map_center',
										'type' => 'group',
										'instructions' => '',
										'required' => 0,
										'conditional_logic' => array(
											array(
												array(
													'field' => 'field_5b226c4078928',
													'operator' => '==',
													'value' => 'Karte'
												)
											)
										),
										'wrapper' => array(
											'width' => '',
											'class' => '',
											'id' => ''
										),
										'layout' => 'row',
										'sub_fields' => array(
											array(
												'key' => 'field_5b226d78545a9',
												'label' => 'Lat',
												'name' => 'fb_map_lat',
												'type' => 'text',
												'instructions' => '',
												'required' => 0,
												'conditional_logic' => 0,
												'wrapper' => array(
													'width' => '',
													'class' => '',
													'id' => ''
												),
												'default_value' => '46.059547',
												'placeholder' => '',
												'prepend' => '',
												'append' => '',
												'maxlength' => ''
											),
											array(
												'key' => 'field_5b226d85545aa',
												'label' => 'Lng',
												'name' => 'fb_map_lng',
												'type' => 'text',
												'instructions' => '',
												'required' => 0,
												'conditional_logic' => 0,
												'wrapper' => array(
													'width' => '',
													'class' => '',
													'id' => ''
												),
												'default_value' => '11.132220',
												'placeholder' => '',
												'prepend' => '',
												'append' => '',
												'maxlength' => ''
											)
										)
									),
									array(
										'key' => 'field_5b226d95545ab',
										'label' => 'Zoomstufe',
										'name' => 'fb_map_zoom',
										'type' => 'number',
										'instructions' => '',
										'required' => 0,
										'conditional_logic' => array(
											array(
												array(
													'field' => 'field_5b226c4078928',
													'operator' => '==',
													'value' => 'Karte'
												)
											)
										),
										'wrapper' => array(
											'width' => '',
											'class' => '',
											'id' => ''
										),
										'default_value' => 7,
										'placeholder' => '',
										'prepend' => '',
										'append' => '',
										'min' => '',
										'max' => '',
										'step' => ''
									)
								)
							)
						)
					)
				)
			),
			array(
				'key' => 'field_continue_button',
				'label' => 'Beschriftung Weiter-Button',
				'name' => 'fb_continue_button',
				'type' => 'text',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => ''
				),
				'default_value' => 'Weiter',
				'placeholder' => '',
				'prepend' => '',
				'append' => '',
				'maxlength' => ''
			),
			array(
				'key' => 'field_finish_button',
				'label' => 'Beschriftung Abschicken-Button',
				'name' => 'fb_finish_button',
				'type' => 'text',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => ''
				),
				'default_value' => 'Fragebogen abschicken',
				'placeholder' => '',
				'prepend' => '',
				'append' => '',
				'maxlength' => ''
			),
			array(
				'key' => 'field_error_text',
				'label' => 'Meldung bei Nicht-Beantwortung notwendiger Fragen',
				'name' => 'fb_error_text',
				'type' => 'text',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => ''
				),
				'default_value' => 'Bitte alle Fragen beantworten, die mit einem roten Sternchen markiert sind!',
				'tabs' => 'all',
				'toolbar' => 'full',
				'media_upload' => 1,
				'delay' => 0
			),
			array(
				'key' => 'field_finished_text',
				'label' => 'Text nach dem Abschicken',
				'name' => 'fb_finish_text',
				'type' => 'wysiwyg',
				'instructions' => '',
				'required' => 0,
				'conditional_logic' => 0,
				'wrapper' => array(
					'width' => '',
					'class' => '',
					'id' => ''
				),
				'default_value' => 'Vielen Dank für die Teilnahme!',
				'tabs' => 'all',
				'toolbar' => 'full',
				'media_upload' => 1,
				'delay' => 0
			),
		),
		'location' => array(
			array(
				array(
					'param' => 'post_type',
					'operator' => '==',
					'value' => 'fragebogen'
				)
			)
		),
		'menu_order' => 0,
		'position' => 'normal',
		'style' => 'default',
		'label_placement' => 'top',
		'instruction_placement' => 'label',
		'hide_on_screen' => '',
		'active' => 1,
		'description' => ''
	));

endif;

?>