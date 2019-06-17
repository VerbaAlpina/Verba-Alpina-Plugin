<?php

/**
 * Plugin Name: VerbaAlpina
 * Plugin URI: keine
 * Description: VerbaAlpina
 * Version: 0.0
 * Author: fz
 * Author URI: keine
 * Text Domain: verba-alpina
 * License: CC BY-SA 4.0
 */

//TODO create variant that loads libs from urls to make github code working

define('DIEONDBERROR', true ); //Benötigt wegen Wordpress Multisites

register_activation_hook(__FILE__, 'va_install');

function va_install (){
	//Register capabilities
	global $wp_roles;
	$administrator = $wp_roles->role_objects['administrator'];
	$administrator->add_cap('data-base'); //TODO prefix with va
	$administrator->add_cap('va_tokenization');
	$administrator->add_cap('va_glossary');
	$administrator->add_cap('va_glossary_edit');
	$administrator->add_cap('va_glossary_translate');
	$administrator->add_cap('verba_alpina');
	$administrator->add_cap('va_see_progress_page');
	$administrator->add_cap('va_transcripts_read');
	$administrator->add_cap('va_transcripts_write');
	$administrator->add_cap('va_typification_tool_read');
	$administrator->add_cap('va_typification_tool_write');
	$administrator->add_cap('va_concept_tree_read');
	$administrator->add_cap('va_concept_tree_write');
	$administrator->add_cap('delete_others_va_posts');
	$administrator->add_cap('delete_va_posts');
	$administrator->add_cap('delete_private_va_posts');
	$administrator->add_cap('delete_published_va_posts');
	$administrator->add_cap('edit_others_va_posts');
	$administrator->add_cap('edit_va_posts');
	$administrator->add_cap('edit_private_va_posts');
	$administrator->add_cap('edit_published_va_posts');
	$administrator->add_cap('publish_va_posts');
	$administrator->add_cap('read_private_va_posts');
	
}

global $login_data;
$login_data = file(plugin_dir_path(__FILE__) . 'login', FILE_IGNORE_NEW_LINES);

if(class_exists('IM_Initializer') && $login_data !== false){
	
	define ('VA_PLUGIN_URL', plugins_url('', __FILE__));
	define ('VA_PLUGIN_PATH', plugin_dir_path(__FILE__));
	
	//Action Handler/Filter:
	add_action('plugins_loaded', 'va_load_textdomain' );
	
	//Map Plugin Hooks
	add_action('im_define_main_file_constants', 'va_map_plugin_version');
	add_action('im_plugin_files_ready', 'va_load_im_config_files');
	add_filter('im_google_maps_api_key', function ($key){
		return 'AIzaSyC0ukPhw9Un5_3blrN02dKzUgilYzg_Kek';
	});
	add_action('im_translation_list', 'va_map_translations');
	add_filter('im_default_map_type', function ($type){
// 		global $va_mitarbeiter;
// 		global $admin;
// 		if ($admin || $va_mitarbeiter){
			return 'pixi';
// 		}
//		return $type;
	});
	
	add_action('set_current_user', function (){
		global $admin;
		global $va_mitarbeiter;
		
		$current_user = wp_get_current_user();
		$roles = $current_user->roles;
		$admin = in_array("administrator", $roles);
		$va_mitarbeiter = in_array('projektmitarbeiter', $roles);
	});
	
	add_action('init', 'va_includes', 11);
	add_action('init', 'va_check_api_call', 12);
	
	//Scripts
	add_action( 'wp_enqueue_scripts', 'scripts_fe' ); //Skripte für das Frontend einbinden
	add_action( 'admin_enqueue_scripts', 'scripts_be' ); //Skripte für das Backend einbinden
	
	add_filter( 'the_title', 'va_translate_page_titles', 9, 2 ); //Page titles
	add_filter( 'wp_title', 'va_translate_title', 10, 2 ); //Title in html-Head
	add_action('admin_menu', 'addMenuPoints'); //Admin Menü
	
	add_filter('show_admin_bar', 'va_show_admin_bar');
	
	add_action('login_head', 'add_favicon');
	add_action('admin_head', 'add_favicon');
	add_filter('the_content', 'parse_syntax_for_posts', 1);
	
	add_filter('logout_url', 'va_logout_url');
	add_filter('login_url', 'va_login_url');
	add_filter('get_pages', 'va_filter_pages');
	
	add_filter('page_link', 'va_add_query_vars', 10, 2);
	add_filter('mlp_linked_element_link', function ($url){
		//This is needed to avoid a bug in wordpress' add_query_arg
		//Compare with https://core.trac.wordpress.org/ticket/36397
		return str_replace('#038;', '&', $url);
	}, 11);
	
	add_filter( 'acf_the_content', 'va_add_footnotes_support');
	
	add_action( 'wp_footer', 'va_enqueue_scripts_footer');
	add_action ('admin_bar_menu', 'va_admin_bar_entries', 80);
	
	add_filter( 'w3tc_can_print_comment', '__return_false', 10, 1 );
	add_action( 'get_footer', 'va_footer' );
	
	function va_map_translations ($list){
		global $Ue;
		
		$list['ALPENKONVENTTION_INFORMANTEN'] = $Ue['ALPENKONVENTTION_INFORMANTEN'];
		$list['AUF_GEMEINDE'] = $Ue['AUF_GEMEINDE'];
		$list['DRUCKFASSUNG'] = $Ue['DRUCKFASSUNG'];
		
		
		return $list;
	}

	
	function va_load_im_config_files (){

		//Map plugin
		IM_Initializer::$instance->map_function = 'create_va_map';
		IM_Initializer::$instance->load_function = 'load_va_data';
		IM_Initializer::$instance->edit_function = 'edit_va_data';
		IM_Initializer::$instance->info_window_function = 'va_load_info_window';
		IM_Initializer::$instance->search_location_function = 'search_va_locations';
		IM_Initializer::$instance->get_location_function = 'get_va_location';
		IM_Initializer::$instance->global_search_function= 'va_ling_search';
		
		add_filter('im_comment', function ($text, $id, $lang){
			global $Ue;
			global $vadb;
			
			parseSyntax($text, true);

			global $va_current_db_name;

			if(va_version_newer_than('va_171')){
				$auth = $vadb->get_results($vadb->prepare("
				SELECT Vorname, Name, Affiliation
				FROM VTBL_Kommentar_Autor JOIN Personen USING (Kuerzel)
				WHERE Id_Kommentar = %s AND Aufgabe = 'auct'", $id), ARRAY_N);
				
				$trad = $vadb->get_results($vadb->prepare("
				SELECT Vorname, Name, Affiliation
				FROM VTBL_Kommentar_Autor JOIN Personen USING (Kuerzel)
				WHERE Id_Kommentar = %s AND Aufgabe = 'trad' AND Sprache = %s", $id, substr($lang, 0, 1)), ARRAY_N);
				
				$authors = va_add_glossary_authors($auth, $trad);
				parseSyntax($authors, true);
				$text .= '<div>' . $authors . '</div>';
			}
			
			
			
			if($va_current_db_name != 'va_xxx'){
				$citation = va_create_comment_citation($id, $Ue);
				if($citation)
					$text .= '<span class="quote" title="' . $citation . '" style="font-size: 75%; cursor : pointer; color : grey;">(' . $Ue['ZITIEREN'] . ')</span>';
			}
			
			return $text;
		}, 10, 3);
	}
	
	function va_check_api_call (){
		if (isset($_REQUEST['api']) && $_REQUEST['api']){
			include_once 'util/api.php';
			va_handle_api_call();
			exit;
		}
	}
		
	function va_map_plugin_version (){
		if(!isDevTester() && (!is_admin() || (isset($_POST['action']) && $_POST['action'] == 'im_a'))){
			define('IM_MAIN_PHP_FILE', dirname(__FILE__) . '/im_config/live/im_live.phar');
			define('IM_MAIN_JS_FILE', plugin_dir_url(__FILE__) . 'im_config/live/im_live.js');
			define('IM_MAIN_CSS_FILE', plugin_dir_url(__FILE__) . 'im_config/live/im_live.css');
		}
	}
	
	function va_show_admin_bar ($bool){
		return false;
	}
	
	function va_add_footnotes_support ($content){
		if(class_exists('swas_wp_footnotes')){
			$swas_wp_footnotes = new swas_wp_footnotes();
			return $swas_wp_footnotes->process($content);
		}
		return $content;
	}
	
	function va_add_query_vars ($url, $post){
		if(!$post)
			return;
		
		$post_title = get_post($post)->post_title;
		
		if(is_multisite() && $post_title == 'KARTE'){
		    $new_blog_id = get_current_blog_id();
		    
		    $req = $_SERVER['REQUEST_URI'];
		    if ($req[1] == '?'){
		        $path = '/';
		    }
		    else {
		        $path = substr($req, 0, 4);
		    }
		    
		    $current_blog_id = get_blog_id_from_url($_SERVER['HTTP_HOST'], $path);
		    
		    if($new_blog_id != $current_blog_id){ //Keep url params for different language links but not for menu link
    			$url_params_map = ['tk', 'ak', 'comm', 'simple_polygons'];
    			foreach ($url_params_map as $par){
    				if(isset($_GET[$par])){
    					$url = add_query_arg($par, $_GET[$par], $url);
    				}
    			}
		    }
		}
		
		if(isset($_GET['db'])){
			$url = add_query_arg('db', $_GET['db'], $url);
		}
		else {
			global $va_current_db_name;
			$url = add_query_arg('db', substr($va_current_db_name, 3), $url);
		}
		
		return $url;
	}
	
	function va_load_textdomain() {
		//To make this compatible with the native dashboard plugin the respective filter is removed for ajax calls with
		//a lang attribute
		global $wp_filter;
		if(isset($_POST['lang']) && isset($wp_filter['locale']['9999'])){
			foreach ($wp_filter['locale']['9999'] as $key => $val){
				if(substr($key, -9) === 'on_locale'){
					remove_filter('locale', $key, 9999);
					break;
				}
			}
		}
		
		load_plugin_textdomain( 'verba-alpina', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
	
	function va_get_menu_items (){
		return array('KARTE', 'METHODOLOGIE', 'PERSONEN', 'MITARBEITER', 'PARTNER', 'PUBLIKATIONEN',
			'WISS_PUBLIKATIONEN', 'BEITRAEGE', 'INFORMATIONSMATERIAL', 'KOMMENTARE', 'ECHO', 'BIBLIOGRAPHIE', 'CS_MITMACHEN', 'APIDOKU');
	}
	
	function va_filter_pages ($pages){
		
		if(!is_admin()){
			global $admin;
			global $va_mitarbeiter;
			
			$menu_items = va_get_menu_items();
			
			if(get_current_blog_id() == 1){
				if(current_user_can('va_transcripts_read')){
					$menu_items[] = 'Protokolle';
				}
			}
				
			global $lang;
			$pages = array_filter($pages, function ($page) use ($menu_items, $lang){
				return ($lang == 'D' && $page->ID == 8172 /* Ausschreibungen */) || in_array($page->post_title, $menu_items);
			});
		}
		return $pages;
	}
	
	function va_logout_url ($url){
		if(isset($_GET['db']) && $_GET['db'] == 'xxx'){
			global $va_xxx;
			$rurl = urlencode(( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . add_query_arg('db', $va_xxx->get_var('SELECT MAX(Nummer) FROM Versionen'), $_SERVER['REQUEST_URI']));
		}
		else {
			$rurl = urlencode(( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
		}
		
		return add_query_arg('redirect_to', $rurl, $url);
	}
	
	function va_login_url ($url){
		return add_query_arg('redirect_to', urlencode(( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . add_query_arg('db', 'xxx', $_SERVER['REQUEST_URI'])), $url);
	}
	
	function add_favicon() {
		echo '<link rel="shortcut icon" href="' . get_stylesheet_directory_uri() .'/favicon.ico" />';
	}
	
	function parse_syntax_for_posts ($content){
		$type = get_post_type();
		if($type === 'post' || $type === 'page'){
			global $va_mitarbeiter;
			global $admin;
			parseSyntax($content, false, $va_mitarbeiter || $admin);
			?>
				<script type="text/javascript">
					jQuery(document).ready(function() {
						addBiblioQTips(jQuery(".entry-content"));
					});
				</script> 
			<?php
		}
		return $content;
	}
	
	function va_enqueue_tabs (){
		wp_enqueue_script('jquery-ui-tabs');
		wp_enqueue_style('im_jquery-ui-style');
		wp_enqueue_style('im_jquery-ui-style2');
		wp_enqueue_style('im_tabs-css');
	}
	
	function va_footer (){
		wp_enqueue_style('va_style', plugins_url('/css/styles.css', __FILE__));
	}
	
	//Skripte für das Frontend
	function scripts_fe (){
		global $post;
		global $admin;
		global $va_mitarbeiter;
		global $va_current_db_name;
		global $va_next_db_name;
		global $Ue;
		
		wp_enqueue_script('toolsSkript', plugins_url('/util/tools.js', __FILE__), array('jquery'), false, true);
		IM_Initializer::$instance->enqueue_font_awesome();
		va_enqueue_bootstrap();
		wp_enqueue_script('cookieConsent', VA_PLUGIN_URL . '/lib/cookieconsent.min.js');
		
		$ajax_url = admin_url( 'admin-ajax.php');
		if(isset($_GET['dev'])){
			$ajax_url = add_query_arg('dev', 'true', $ajax_url);
		}

		$ajax_url = add_query_arg('db', substr($va_current_db_name, 3), $ajax_url);
		
		$ajax_object = array( 
			'ajaxurl' => $ajax_url, 
			'site_url' => get_site_url(1), 
			'plugin_url' => VA_PLUGIN_URL,
			'db' => substr($va_current_db_name, 3),
			'va_staff' => $admin || $va_mitarbeiter,
			'dev' => isDevTester()? '1': '0',
			'user' => wp_get_current_user()->user_login,
	        'next_version' => $va_next_db_name
		);
		
		if(isset($post)){
			$ajax_object['page_title'] = $post->post_title;
		}
		
		wp_localize_script( 'toolsSkript', 'ajax_object', $ajax_object);
	
		IM_Initializer::$instance->enqueue_qtips();
		
		if(isset($post)){
			if($post->post_title == 'KARTE' || $post->post_title == 'Karte-Test'){ //Interaktive Karte
				if(isDevTester())
					wp_enqueue_style('va_map_style', plugins_url('/im_config/va_map.css', __FILE__));
					wp_enqueue_style ('jsTreeStyle', VA_PLUGIN_URL . '/lib/jstree/dist/themes/default/style.min.css');
					wp_enqueue_script('jsTreeScript', VA_PLUGIN_URL . '/lib/jstree/dist/jstree.min.js', array('jquery'));
			}
			else if($post->post_title == 'Fortschritt'){
				va_enqueue_tabs();
			}
			else if($post->post_title == 'Protokolle'){
				wp_enqueue_script('jquery-ui-datepicker');
				wp_enqueue_script('jquery-ui-dialog');
				wp_enqueue_script('history.js', VA_PLUGIN_URL . '/lib/history.js/scripts/bundled/html5/jquery.history.js');
				wp_enqueue_style('jquery_ui', plugins_url('plugin_interactive-map/lib/css/jquery-ui.min.css'));
			}
			else if($post->post_title == 'METHODOLOGIE'){
				wp_enqueue_script('clipboard', VA_PLUGIN_URL . '/lib/clipboard.min.js');
			}
			else if($post->post_title == 'KOMMENTARE'){
				wp_enqueue_script('clipboard', VA_PLUGIN_URL . '/lib/clipboard.min.js');
			}
			else if($post->post_title == 'Datenbank-Dokumentation'){
				va_enqueue_tabs();
			}
			else if ($post->post_title == 'Todos'){
			    wp_enqueue_script('jquery-ui-dialog');
			    wp_enqueue_style('im_jquery-ui-style');
			}
			else if ($post->post_title == 'DizMT Eingabe'){
				IM_Initializer::$instance->enqueue_select2_library();
			}
			else if ($post->post_type == 'fragebogen'){
				IM_Initializer::$instance->enqueue_select2_library();
			}
		}	
	}
	
	function va_enqueue_scripts_footer (){
		global $record_input_shortcode;
		if($record_input_shortcode){
			IM_Initializer::$instance->enqueue_gui_elements();
			wp_localize_script('toolsSkript', 'ajaxurl', admin_url('admin-ajax.php'));
		}
	}
	
	//TODO move to theme
	function va_enqueue_bootstrap (){
		wp_enqueue_script('va_tether', plugins_url('/lib/bootstrap/tether.min.js', __FILE__));
		wp_enqueue_style('va_tether_style', plugins_url('/lib/bootstrap/tether.min.css', __FILE__));
		wp_enqueue_script('va_bootstrap', plugins_url('/lib/bootstrap/bootstrap.min.js', __FILE__));
		wp_enqueue_style('va_bootstrap_style', plugins_url('/lib/bootstrap/bootstrap.min.css', __FILE__));
		wp_enqueue_script('va_hammer', plugins_url('/lib/hammer.js/hammer.min.js', __FILE__));
		wp_enqueue_script('va_hammer_jq', plugins_url('/lib/hammer.js/jquery.hammer.js', __FILE__));
	}
	
	//Skripte für das Backend
	function scripts_be ($hook){
		wp_enqueue_style('va_style', plugins_url('/css/styles.css', __FILE__));
		
		if ($hook != 'verba-alpina_page_transcription'){
			IM_Initializer::$instance->enqueue_qtips(); //Use own version of lib
		}
		
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		
		wp_enqueue_script('toolsSkript', plugins_url('/util/tools.js', __FILE__));
		wp_localize_script( 'toolsSkript', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )) );
	
		if($hook === 'verba-alpina_page_typification'){
			IM_Initializer::$instance->enqueue_chosen_library();
			IM_Initializer::$instance->enqueue_select2_library();
			IM_Initializer::$instance->enqueue_gui_elements();
			wp_enqueue_script('lex_typifiy_script', plugins_url('/backend/typification/lex.js', __FILE__));
			wp_enqueue_script('typifiy_script', plugins_url('/backend/typification/util.js', __FILE__));
		}
		else if ($hook === 'verba-alpina_page_base_types'){
			IM_Initializer::$instance->enqueue_chosen_library();
			IM_Initializer::$instance->enqueue_select2_library();
			IM_Initializer::$instance->enqueue_gui_elements();
			wp_enqueue_script('typifiy_script', plugins_url('/backend/typification/util.js', __FILE__));
		}
		else if ($hook === 'verba-alpina_page_konzeptbaum'){
			IM_Initializer::$instance->enqueue_gui_elements();
			wp_enqueue_style ('jsTreeStyle', VA_PLUGIN_URL . '/lib/jstree/dist/themes/default/style.min.css');
			wp_enqueue_script('jsTreeScript', VA_PLUGIN_URL . '/lib/jstree/dist/jstree.min.js', array('jquery'));
			global $va_xxx;
			$parents = $va_xxx->get_results('SELECT Id_Kategorie, Id_Ueberkategorie FROM Konzepte_Kategorien WHERE Id_Ueberkategorie IS NOT NULL', ARRAY_N);
			wp_localize_script('jsTreeScript', 'PARENTS', va_two_dim_to_assoc($parents));
		}
		else if ($hook === 'verba-alpina_page_transcription'){
			IM_Initializer::$instance->enqueue_gui_elements();
		}
		else if ($hook === 'toplevel_page_glossar'){
			IM_Initializer::$instance->enqueue_chosen_library();
			IM_Initializer::$instance->enqueue_gui_elements();
			wp_enqueue_script('history.js', VA_PLUGIN_URL . '/lib/history.js/scripts/bundled/html5/jquery.history.js');
		}
		else if ($hook === 'verba-alpina_page_edit_comments'){
			IM_Initializer::$instance->enqueue_chosen_library();
		}
		else if ($hook === 'toplevel_page_va_tools'){
			enqueuePEG();
		}
		else if ($hook === 'tools_page_va_tools_bsa'){
			IM_Initializer::$instance->enqueue_select2_library();
			IM_Initializer::$instance->enqueue_chosen_library();
			IM_Initializer::$instance->enqueue_gui_elements();
		}
		else if ($hook === 'verba-alpina_page_test'){
			enqueuePEG();
		}
		else if ($hook === 'tools_page_va_tools_emails'){
			wp_enqueue_script('tablesorter.js', VA_PLUGIN_URL . '/lib/jquery.tablesorter.min.js');
		}
		else if ($hook === 'tools_page_va_tools_single_comments'){
			IM_Initializer::$instance->enqueue_select2_library();
		}
		else if ($hook === 'tools_page_va_tools_informant_geo'){
			IM_Initializer::$instance->enqueue_select2_library();
		}
	}
	
	function va_admin_bar_entries ($wp_admin_bar){
		
		if (is_admin() && isset($_REQUEST['page'])){
			if ($_REQUEST['page'] == 'glossar'){
				$id = isset($_REQUEST['entry'])? $_REQUEST['entry']: null;
				
				$wp_admin_bar->add_node([
					'id' => 'show_glossary_entry',
					'title' => 'Eintrag anzeigen',
					'href' => va_get_glossary_link($id),
					'meta' => [
						'target' => '_blank'
					]
				]);
			}
			else if ($_REQUEST['page'] == 'edit_comments'){
				$id = isset($_REQUEST['comment_id'])? $_REQUEST['comment_id']: null;
				
				$wp_admin_bar->add_node([
					'id' => 'show_comment',
					'title' => 'Eintrag anzeigen',
					'href' => va_get_comments_link($id),
					'meta' => [
						'target' => '_blank'
					]
				]);
			}
		}
	}
	
	function enqueuePEG (){
		wp_enqueue_script('grammarScript', VA_PLUGIN_URL . '/lib/peg-0.10.0.min.js');
	}
	
	function va_get_language (){
		switch(get_locale()){
			case 'fr_FR':
				return 'F';
			case 'it_IT':
				return 'I';
			case 'de_DE';
				return 'D';
			case 'sl_SI';
				return 'S';
			case 'rg_CH';
				return 'R';
			case 'en_UK':
			case 'en_US':
				return 'E';
			default:
				return 'D';
		}
	}
	
	function isDevTester (){
		return in_array( 'devtester', (array) wp_get_current_user()->roles ) || isset($_REQUEST['dev']);
	}
	
	function va_translate_page_titles ($title, $id){
		$type = get_post_type($id);
		
		if($type == 'page'){
			global $Ue;
			return isset($Ue[$title]) && $Ue[$title] != ''? ucfirst($Ue[$title]) : $title;
		}
		
		if($type == 'ethnotext'){
			$post = get_post($id);
			$informant = get_field('et_informant', $id);
			$datum = get_field('et_datum', $id);
			$ort = get_field('et_ort', $id);
			return $informant . ' - ' . $datum . ' (' . $ort . ')';
		}
		
		return $title;
	}
	
	function va_translate_title ($title){
		global $Ue;
		
		if(strpos($title, 'Seite_') === 0)
			return get_field('person');
		
		if(strpos($title, 'VerbaAlpina') === 0 || $title == '')
			return $title;
		
		$pos_sep = strpos($title, '|');
		$page_title = substr($title, 0, $pos_sep - 1);
		
		$cur_post = get_queried_object();
		if($cur_post && $cur_post->post_type == 'ethnotext'){
			$page_title = get_the_title();
		}
		
		$page_title = isset($Ue[$page_title]) && $Ue[$page_title] != ''? $Ue[$page_title] : $page_title;
		
		return $page_title . ' | ' . substr($title, $pos_sep + 1);
	}
	
	function va_get_translations ($lang){
		global $va_xxx;
		
		$transl = 'Begriff_' . $lang;
		
		$res = $va_xxx->get_results("SELECT Schluessel, IF($transl = '', CONCAT(Begriff_D, '(!!!)'), $transl) FROM Uebersetzungen" , ARRAY_N);
		
		$Ue = array();
		foreach ($res as $r){
			$Ue[$r[0]] = $r[1];
		}
		return $Ue;
	}
	
	function localize(){
		wp_localize_script('toolsSkript', 'Ue', $Ue);
	}
	
	$admin = false;
	
	function va_includes (){
		global $admin;
		global $va_mitarbeiter;
		global $lang;
		global $Ue;
		global $login_data;
		
		$dbuser = $login_data[0];
		$dbpassw = $login_data[1];
		$dbhost = $login_data[2];
		
		global $va_xxx;
		//Va_xxx data base, used for all queries that have to be placed in the current working version
		$va_xxx = new wpdb($dbuser, $dbpassw, 'va_xxx', $dbhost);
		$va_xxx->show_errors();
		
		global $va_current_db_name;
		global $va_next_db_name;
		
		$max_version = $va_xxx->get_var('SELECT MAX(Nummer) FROM Versionen');
		$va_next_db_name = va_increase_version($max_version);
		
		if(is_user_logged_in()){
			$va_current_db_name = 'va_xxx';
		}
		else {
			$va_current_db_name = 'va_' . $max_version;
		}
		if(isset($_GET['db'])){
			$va_current_db_name = 'va_' . $_GET['db'];
		}
		
		if(isset($_GET['page_id']) && !isset($_GET['db'])){
			header('Location: ' . add_query_arg('db', substr($va_current_db_name, 3), get_permalink()));
			exit;
		}
		
		global $vadb;
		//Data base for general frontend query (in general readonly except it is va_xxx)
		$vadb = new wpdb($dbuser, $dbpassw, $va_current_db_name, $dbhost);
		$vadb->show_errors();
		
		if(isset($_POST['action']) && $_POST['action'] == 'im_a' 
			&& isset($_POST['namespace']) && ($_POST['namespace'] == 'save_syn_map' || $_POST['namespace'] == 'load_syn_map'))
		{
			//Always get the synoptic map outline data from the xxx version:
			IM_Initializer::$instance->database = $va_xxx;
		}
		else {
			IM_Initializer::$instance->database = $vadb;
		}
		
		$lang = va_get_language();
		$Ue = va_get_translations($lang);

		if(isDevTester()){ //TODO remove, too and use select
			global $va_playground;
			$va_playground = new wpdb($va_xxx->dbuser, $va_xxx->dbpassword, 'va_playground', $va_xxx->dbhost);
			$va_playground->show_errors();

			add_shortcode('todos', function ($attr){return va_todo_page($attr['person']);}); //pages/todos.php
			add_shortcode('correct', 'va_correction_test_page');
		}
		
		if (class_exists('TranscriptionTool')){
			$mappings = [];
			
			$mappings['codepage_original'] = new TableMapping('tcodepage_original');
			$mappings['transcription_rules'] = new TableMapping(get_user_locale() == 'de_DE'? 'trules': 'trules_e');
			$mappings['stimuli'] = new TableMapping('tstimuli');
			$mappings['informants'] = new TableMapping('tinformants');
			$mappings['locks'] = new TableMapping('locks', ['Context' => 'Tabelle', 'Value' => 'Wert', 'Locked_By' => 'Gesperrt_Von', 'Time' => 'Zeit']);
			$mappings['c_attestation_concept'] = new TableMapping('VTBL_Aeusserung_Konzept', ['Id_Concept' => 'Id_Konzept', 'Id_Attestation' => 'Id_Aeusserung']);
			$mappings['attestations'] = new TableMapping('Aeusserungen', [
				'Id_Attestation' => 'Id_Aeusserung',
				'Attestation' => 'Aeusserung',
				'Transcribed_By' => 'Erfasst_Von',
				'Created' => 'Erfasst_Am',
				'Classification' => 'Klassifizierung',
				'Tokenized' => 'Tokenisiert'
			],[
				'Classification' => ['A' => 'B']
			]);
			
			//TranscriptionTool::add_ajax_param('dev', '1');
			
			TranscriptionTool::add_special_val_button(
				'vacat', 
				'vacat', 
				__('Adds a marker to the data base that there are no attestations for this informant.', 'verba-alpina'));
			
			TranscriptionTool::add_special_val_button(
				__('Concept does not exist', 'verba-alpina'),
				__('Concept does not exist', 'verba-alpina'),
				__('Adds a marker to the data base that this concept is not known at this place.', 'verba-alpina'));
			
			TranscriptionTool::add_special_val_button(
				__('Term not known', 'verba-alpina'),
				__('Term not known', 'verba-alpina'),
				__('Adds a marker to the data base that this concept is known at this place, but the informant does not know a term for it.', 'verba-alpina'));
			
			TranscriptionTool::add_informant_filter('Alpine_Convention', '1', '%d', true, __('Only within alpine convention', 'verba-alpina'));
			
			TranscriptionTool::init(
			'/dokumente/scans/',
			$va_xxx,
			$va_xxx->get_col('SELECT DISTINCT Erhebung FROM Stimuli JOIN Bibliographie ON Abkuerzung = Erhebung WHERE VA_Beta'),
			$va_xxx->get_results("SELECT Id_Konzept AS id, IF(Name_D != '', Name_D, Beschreibung_D) as text FROM Konzepte ORDER BY Text ASC", ARRAY_A),
			$mappings
			);
		}
		
		
		//Shortcodes (to replace page content)
		add_shortcode('home', function ($attr){return showStartPage();}); //home.php
		add_shortcode('personen', function ($attr){return va_show_team();}); //personen.php
		add_shortcode('localize', function ($attr) {return localize();});
		add_shortcode('terminologie', function ($attr) {return termino ();}); //glossar.php
		add_shortcode('partner', function ($attr){return partnerAnzeigen();}); //personen.php
		add_shortcode('glossarSeite', function ($attr){return ladeGlossar();}); //glossar.php
		add_shortcode('wissPub', function ($attr) {return wissPub ();}); //publikationen.php
		add_shortcode('infoMat', function ($attr) {return infoMat ();}); //publikationen.php
		add_shortcode('kontakt', function ($attr) {return kontaktSeite ();}); //kontakt.php
		add_shortcode('internePub', function ($attr) {return intPub ();}); //publikationen.php
		add_shortcode('kommentare', 'comment_list'); //pages/comments.php
		add_shortcode('echo', 'va_echo_page');//publikation.php
		add_shortcode('showCodepages', 'va_codepage_page');//codepage.php
		add_shortcode('csgraph', 'va_graph_page');//cs_graph.php
		add_shortcode('livegraph', 'va_live_graph_page'); //live_graph.php
		add_shortcode('showBib', 'show_bib_page');//pages/bib.php
		add_shortcode('showConceptIllustrations', 'show_concept_images'); //pages/concept_images.php
		add_shortcode('normPage', 'va_norm_page'); //pages/norm.php
		
		add_shortcode('callList', 'va_call_list'); //pages/calls.php 
		
		if(current_user_can('va_transcripts_read')){
			add_shortcode('protokolle', function ($attr) {return protokolle ();}); //protokolle.php
		}
		
		if(current_user_can('va_see_progress_page')){
			add_shortcode('overview', 'overview_page');
		}
		
		add_shortcode('dbdescription', function ($attr) {return va_show_DB_description ();}); //pages/admin_table.php
		
		if($va_mitarbeiter || $admin){
			add_shortcode('statistics', 'va_statistics');
			add_shortcode('overview_app', 'overview_app_page');
			add_shortcode('recordInput', function ($attr){
				if(!isset($attr['stimulus']) || !isset($attr['informant']))
					return 'Stimulus or informant missing!';

					return va_record_input_for_concept(
							$attr['stimulus'], 
							$attr['informant'], 
							(isset($attr['classification'])? $attr['classification']: null),
							isset($attr['shownotes']) && $attr['shownotes'] == 'true');
			});
			
			add_shortcode('questionnaireResults', 'show_questionnaire_results'); //pages/qresults.php
		}
		
		//Includes
		require 'lib/html5-dom-document/autoload.php';
		
		include_once('util/tools.php');
		include_once('util/tokenization_functions.php');
		include_once('util/ajax/va_ajax.php');
		include_once('util/parseGlossarSyntax.php');
	
		include_once('backend/glossar_edit.php');
		include_once('backend/comments_edit.php');
		
		//include_once('scans.php');
		include_once('backend/concept_tree.php');
	
		include_once('backend/cmedia.php');
	
		
		include_once('pages/home.php');
		
		include_once('pages/personen.php');
		
		include_once('pages/glossar.php');
		include_once('pages/codepage.php');
		include_once('pages/comments.php');
		include_once('backend/translate.php');
		include_once('pages/publikationen.php');
		include_once('pages/kontakt.php');
		include_once('pages/bib.php');
		include_once('pages/concept_images.php');
		include_once('util/tree.php');
		include_once('pages/norm.php');
		include_once('pages/calls.php');
		
		include_once('backend/typification/lex.php');
		include_once('backend/typification/util.php');
		include_once('backend/typification/base.php');
		
		include_once('backend/auto/tokenize.php');
		include_once('backend/auto/kml.php');
		include_once 'backend/auto/ipa.php';
		include_once('util/pointListToKML.php');
		
		if(current_user_can('va_transcripts_read')){
			include_once('pages/protokolle.php');
		}
		
		if(current_user_can('va_see_progress_page')){
			include_once('pages/overview.php');
		}
		
		include_once('pages/admin_table.php');
		include_once('pages/cs_graph.php');
		include_once('pages/live_graph.php');
		
		if($va_mitarbeiter || $admin){
			include_once('pages/app_overview.php');
			include_once('pages/statistics.php');
			include_once('backend/cs_emails.php');
			include_once('backend/auto/import_bsa.php');
			include_once('pages/record_input.php');
			include_once('pages/qresults.php');
			include_once('backend/glossar_errors.php');
			include_once('backend/comments_single.php');
		}
		
		if(isDevTester()){
		    include_once('pages/todos.php');
		    
			include_once('im_config/va_map.php');
			include_once('im_config/db.php'); //TODO get dev-tester running for AJAX calls!!!
			
			include_once('backend/check_tokens.php');
			include_once('test.php');
			include_once('backend/auto/clapie.php');
			include_once('backend/auto/tagung_to_kit.php');
			include_once('backend/auto/tokenizer_tests.php');
			include_once('backend/auto/geonames.php');
			include_once('backend/auto/informant_geo.php');
			
			include_once('util/corrections.php');
		}
		
		include_once('util/va_beta_parser.php');
		include_once('acf_groups/questionnaire.php');
		
		include_once('export/converter.php');
	}
	
		
	//Admin Menü
	
	function addMenuPoints () {
		global $admin;
		global $va_mitarbeiter;
		
		add_menu_page('Verba Alpina','Verba Alpina', 'va_glossary', 'glossar');
		add_submenu_page('glossar', 'Glossar-Einträge bearbeiten','Glossar-Einträge bearbeiten', 'va_glossary', 'glossar', 'glossar');
		add_submenu_page('glossar', 'Kommentare bearbeiten','Kommentare bearbeiten', 'va_glossary', 'edit_comments', 'va_edit_comments_page');
		
		if (class_exists('TranscriptionTool')){
			TranscriptionTool::create_menu('glossar');
		}
		
		add_submenu_page('glossar', 'Übersetzung Oberfläche', 'Übersetzung Oberfläche', 'verba_alpina', 'transl', 'va_translation_page'); 
		add_submenu_page('glossar', 'Konzeptbaum', 'Konzeptbaum', 'va_concept_tree_read', 'konzeptbaum', 'konzeptbaum'); 
		add_submenu_page('glossar', __('Typification', 'verba-alpina'), __('Typification', 'verba-alpina'), 'va_typification_tool_read', 'typification', 'lex_typification');
		add_submenu_page('glossar', 'Basistypen bearbeiten', 'Basistypen bearbeiten', 'va_typification_tool_read', 'base_types', 'va_edit_base_type_page');
		
		add_menu_page('Tools','Tools', 'verba_alpina', 'va_tools');
		add_submenu_page('va_tools', 'Tools', 'Beta -> IPA', 'verba_alpina', 'va_tools', 'ipa_page');
		add_submenu_page('va_tools', 'Tools', 'Tokenisierung', 'va_tokenization', 'va_tools_tok', 'va_create_tokenizer_page');
		add_submenu_page('va_tools', 'Tools', 'SQL -> KML', 'verba_alpina', 'va_tools_kml', 'kml_transform');
		add_submenu_page('va_tools', 'Tools', 'Import BSA', 'verba_alpina', 'va_tools_bsa', 'va_import_bsa_page');
		add_submenu_page('va_tools', 'Tools', 'Fehler im Glossar', 'verba_alpina', 'va_tools_glossary', 'search_glossary_errors');
		add_submenu_page('va_tools', 'Tools', 'Kommentare Orte', 'verba_alpina', 'va_tools_single_comments', 'va_single_comments_page');
		
		if(isDevTester()){
			add_submenu_page('va_tools', 'Tools', 'VA-Seiten erstellen', 'verba_alpina', 'va_tools_create_pages', 'va_create_frontend_pages');
			//add_submenu_page('va_tools', 'Tools','Bibliographie', 'verba_alpina', 'va_tools_bib', 'itg_create_menu_page');
			add_submenu_page('va_tools', 'Tools', 'Tagung -> KIT', 'verba_alpina', 'va_tools_kit', 'va_kit_transform');
			add_submenu_page('va_tools', 'Tools', 'Tokenisierung verifizieren', 'verba_alpina', 'va_tools_check_tokens', 'va_check_tokens');
			add_submenu_page('va_tools', 'Tools', 'Tokenisierer testen', 'verba_alpina', 'va_tools_check_tokenizer', 'va_check_tokenizer');
			add_submenu_page('va_tools', 'Tools', 'Geonames', 'verba_alpina', 'va_tools_geonames', 'va_geonames_page');
			add_submenu_page('va_tools', 'Tools', 'Geodaten Informanten', 'verba_alpina', 'va_tools_informant_geo', 'va_informant_geo_page');
		}
		
		add_menu_page(__('Data base', 'verba-alpina'), __('Data base', 'verba-alpina'), 'data-base', 'dba', 'dba');
		
		
		if($va_mitarbeiter || $admin){
			add_submenu_page('va_tools', 'Tools', 'CS Emails', 'verba_alpina', 'va_tools_emails', 'va_cs_emails');
			
			add_pages_page('Persönliche Seite', 'Persönliche Seite', 'verba_alpina', 'personal_page', 'create_pp');
// 			if($va_mitarbeiter){
// 				remove_submenu_page('edit.php?post_type=page', 'post-new.php?post_type=page');
// 				remove_submenu_page('edit.php?post_type=page', 'edit.php?post_type=page');
// 			}
		}
		
		if($admin){
			add_submenu_page('glossar', 'Autom. Operationen','Autom. Operationen', 'verba_alpina', 'auto', 'va_auto');
			add_submenu_page('glossar', 'Test','Test', 'verba_alpina', 'test', 'test');
			add_submenu_page('glossar', 'Clapie','Clapie', 'verba_alpina', 'clapie', 'clapie');
		}
		
	}
	
	function create_pp (){
		$username = wp_get_current_user()->user_login;
		$page = get_page_by_title('Seite_' . $username);
		
		if($page){
			$id = $page->ID;
		}
		else {
			$id = wp_insert_post(array (
				'post_name' => 'Seite_' . $username,
				'post_title' => 'Seite_' . $username,
				'post_status' => 'publish',
				'post_type' => 'page',
				'page_template' => 'template_personal.php',
				'post_autor' => get_current_user_id(),
			));
		}
		?>
			<script type="text/javascript">
				window.location = "<?php echo get_admin_url() . "post.php?post=$id&action=edit"; ?>";
			</script>
		<?php
	}
	
	function dba (){
		?>
			<script type="text/javascript">
				window.location = "https://pma.gwi.uni-muenchen.de:8888/index.php#PMAURL-0:db_structure.php?db=va_xxx&table=&server=1&target=";
			</script>
		<?php
	}
	
	function va_create_frontend_pages (){
		?>
		<br />
		<br />
		<b>Folgende Seiten wurden neu erstellt:</b>
		<br />
		<?php
		$page = get_page_by_title('KARTE');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'KARTE',
					'post_content' => '[im_show_map name="VA"]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty_wide.php',
					'post_autor' => get_current_user_id(),
					'menu_order' => 1
			));
			echo 'Interaktive Karte<br />';
		}
		$page = get_page_by_title('METHODOLOGIE');
		if($page == null){
			wp_insert_post(array (
				'post_title' => 'METHODOLOGIE',
				'post_content' => '[terminologie]',
				'post_status' => 'publish',
				'post_type' => 'page',
				'page_template' => 'template_empty.php',
				'post_autor' => get_current_user_id(),
				'menu_order' => 5
			));
			echo 'Methodologie<br />';
		}
		$page = get_page_by_title('PERSONEN');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'PERSONEN',
					'post_content' => '',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_child.php',
					'post_autor' => get_current_user_id(),
					'menu_order' => 99
			));
			echo 'Personen<br />';
		}
		$person_page = get_page_by_title('PERSONEN');
		$page = get_page_by_title('MITARBEITER');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'MITARBEITER',
					'post_content' => '[personen]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id(),
					'post_parent' => $person_page->ID,
					'menu_order' => 701
			));
			echo 'Team<br />';
		}
		$page = get_page_by_title('PARTNER');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'PARTNER',
					'post_content' => '[partner]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id(),
					'post_parent' => $person_page->ID,
					'menu_order' => 703
			));
			echo 'Projektpartner<br />';
		}
		$page = get_page_by_title('PUBLIKATIONEN');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'PUBLIKATIONEN',
					'post_content' => '',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_child.php',
					'post_autor' => get_current_user_id(),
					'menu_order' => 9
			));
			echo 'Texte und Präsentationen<br />';
		}
		$pub_page = get_page_by_title('PUBLIKATIONEN');
		$page = get_page_by_title('WISS_PUBLIKATIONEN');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'WISS_PUBLIKATIONEN',
					'post_content' => '[wissPub]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id(),
					'post_parent' => $pub_page->ID,
					'menu_order' => 901
			));
			echo 'Projektpublikationen<br />';
		}
		$page = get_page_by_title('BEITRAEGE');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'BEITRAEGE',
					'post_content' => '[internePub]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id(),
					'post_parent' => $pub_page->ID,
					'menu_order' => 902
			));
			echo 'Vorträge<br />';
		}
		$page = get_page_by_title('INFORMATIONSMATERIAL');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'INFORMATIONSMATERIAL',
					'post_content' => '[infoMat]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id(),
					'post_parent' => $pub_page->ID,
					'menu_order' => 903
			));
			echo 'Informationsmaterial<br />';
		}
		$page = get_page_by_title('KOMMENTARE');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'KOMMENTARE',
					'post_content' => '[kommentare]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id(),
					'menu_order' => 6
			));
			echo 'Kommentare<br />';
		}
		$page = get_page_by_title('KONTAKT');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'KONTAKT',
					'post_content' => '[kontakt]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id(),
					'menu_order' => 11
			));
			echo 'Kontakt<br />';
		}
		$page = get_page_by_title('Datenbank-Dokumentation');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'Datenbank-Dokumentation',
					'post_content' => '[dbdescription]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id(),
					'menu_order' => 0
			));
			echo 'Datenbank Übersicht<br />';
		}
		$page = get_page_by_title('Fortschritt');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'Fortschritt',
					'post_content' => '[overview]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id(),
					'menu_order' => 0
			));
			echo 'Fortschritt<br />';
		}
		$page = get_page_by_title('Protokolle');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'Protokolle',
					'post_content' => '[protokolle]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id(),
					'menu_order' => 906,
					'post_parent' => $pub_page->ID,
			));
			echo 'Protokolle<br />';
		}
		$page = get_page_by_title('ECHO');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'ECHO',
					'post_content' => '[echo]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id(),
					'menu_order' => 905,
					'post_parent' => $pub_page->ID,
			));
			echo 'Echo<br />';
		}
		$page = get_page_by_title('CSGRAPH');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'CSGRAPH',
					'post_content' => '[csgraph]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id()
			));
			echo 'CS Graph<br />';
		}

		$page = get_page_by_title('LIVEGRAPH');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'LIVEGRAPH',
					'post_content' => '[livegraph]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id()
			));
			echo 'Live Graph<br />';
		}
		
		$page = get_page_by_title('CONCEPT_IMAGES');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'CONCEPT_IMAGES',
					'post_content' => '[showConceptIllustrations]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id()
			));
			echo 'CONCEPT_IMAGES<br />';
		}
		
		$page = get_page_by_title('CODEPAGE');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'CODEPAGE',
					'post_content' => '[showCodepages]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id()
			));
			echo 'CODEPAGE<br />';
		}
		
		$page = get_page_by_title('Home');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'Home',
					'post_content' => '[home]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id()
			));
			echo 'Home<br />';
		}
	}
}

function va_increase_version ($old){
    if (substr($old, -1) == '2'){
        return (intval(substr($old, 0, -1)) + 1) . '1';
    }
    else {
        return substr($old, 0, -1) . '2';
    }
}

function va_produce_external_map_link ($atlas, $map, $num, $informant){
	$attributes = ' style="text-decoration: underline;" target="_BLANK" ';
	
	if($atlas == 'AIS'){
		if($num == '1'){
			$link = 'http://www3.pd.istc.cnr.it/navigais-web/?map=' . $map . '&point=' . $informant;
		}
		else {
			$link = 'http://www3.pd.istc.cnr.it/navigais-web/?map=' . $map;
		}
		return 'G. Tisato - NavigAIS - <a' . $attributes . 'href="' . $link . '">' . $link . '</a>';
	}
	
	if($atlas == 'ALF'){
		if(is_numeric($map)){
			$number = str_pad($map, 4, '0', STR_PAD_LEFT);
		}
		else if (in_array(substr($map, -1), ['A', 'B'])){
			$number = str_pad(substr($map, 0, -1), 4, '0', STR_PAD_LEFT) . substr($map, -1);
		}
		else {
			return null; //No maps for supplements
		}
		$link = 'http://lig-tdcge.imag.fr/cartodialect3/visualiseur?numCarte=' . $number;
		
		return '<a' . $attributes . 'href="' . $link . '">Link</a>';
	}
	
	return null;
}
?>