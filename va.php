<?php

/**
 * Plugin Name: VerbaAlpina
 * Plugin URI: keine
 * Description: VerbaAlpina
 * Version: 0.0
 * Author: fz
 * Author URI: keine
 * Text Domain: verba-alpina
 * License: ?
 */

define('DIEONDBERROR', true ); //Benötigt wegen Wordpress Multisites

register_activation_hook(__FILE__, 'va_install');

function va_install (){
	//Register capabilities
	global $wp_roles;
	$administrator = $wp_roles->role_objects['administrator'];
	$administrator->add_cap('data-base'); //TODO prefix with va
	$administrator->add_cap('glossar');
	$administrator->add_cap('verba_alpina');
	$administrator->add_cap('va_see_progress_page');
	$administrator->add_cap('va_transcription_tool_read');
	$administrator->add_cap('va_transcription_tool_write');
	$administrator->add_cap('va_transcripts_read');
	$administrator->add_cap('va_transcripts_write');
	$administrator->add_cap('va_typification_tool_read');
	$administrator->add_cap('va_typification_tool_write');
	$administrator->add_cap('va_concept_tree_read');
	$administrator->add_cap('va_concept_tree_write');
}

$login_data = file(plugin_dir_path(__FILE__) . 'login', FILE_IGNORE_NEW_LINES);

if(class_exists('IM_Initializer') && $login_data !== false){
	
	define ('VA_PLUGIN_URL', plugins_url('', __FILE__));
	
	$dbuser = $login_data[0];
	$dbpassw = $login_data[1];
	$dbhost = 'gwi-sql2.gwi.uni-muenchen.de';
	
	
	global $va_current_db_name;
	$va_current_db_name = 'va_xxx';
	if(isset($_REQUEST['db'])){
		$va_current_db_name = 'va_' . $_REQUEST['db'];
	}
	
	global $vadb;
	//Data base for general frontend query (in general readonly except it is va_xxx)
	$vadb = new wpdb($dbuser, $dbpassw, $va_current_db_name, $dbhost);
	$vadb->show_errors();
	
	global $va_xxx;
	//Va_xxx data base, used for all queries that have to be placed in the current working version
	$va_xxx = new wpdb($dbuser, $dbpassw, 'va_xxx', $dbhost);
	$va_xxx->show_errors();
	
	//Action Handler/Filter:
	add_action('plugins_loaded', 'va_load_textdomain' );
	
	//Map Plugin Hooks
	add_action('im_define_main_file_constants', 'va_map_plugin_version');
	add_action('im_plugin_files_ready', 'va_load_im_config_files');
	add_action('im_translation_list', 'va_map_translations');
	add_action('init', 'widget_includes', 0);
	add_action('init', 'includes', 11);
	add_action( 'widgets_init', function(){
		register_widget( 'VersionWidget' );
		register_widget( 'InternalWidget' );
	});
	
	//Scripts
	add_action( 'wp_enqueue_scripts', 'scripts_fe' ); //Skripte für das Frontend einbinden
	add_action( 'admin_enqueue_scripts', 'scripts_be' ); //Skripte für das Backend einbinden
	
	add_filter( 'the_title', 'translate_page_titles', 9, 2 ); //Page titles
	add_filter( 'wp_title', 'translate_title', 10, 2 ); //Title in html-Head
	add_action('admin_menu', 'addMenuPoints'); //Admin Menü
	
	add_action('admin_bar_menu', 'va_menu_bar', 1000);
	add_filter('show_admin_bar', '__return_true' );
	
	add_action('wp_login', 'loginFunc', 10, 2);
	add_action('login_head', 'add_favicon');
	add_action('admin_head', 'add_favicon');
	add_filter('the_content', 'parse_syntax_for_posts');
	
	add_filter('logout_url', 'stay_on_page');
	add_filter('login_url', 'stay_on_page');
	add_filter('get_pages', 'internal_pages');
	add_filter('sidebars_widgets', 'filter_widgets');
	
	add_filter('page_link', 'va_add_query_vars');
	add_filter('mlp_linked_element_link', function ($url){
		//This is needed to avoid a bug in wordpress' add_query_arg
		//Compare with https://core.trac.wordpress.org/ticket/36397
		return str_replace('#038;', '&', $url);
	}, 11);
	
	add_filter( 'acf_the_content', 'va_add_footnotes_support');
	
	function va_map_translations ($list){
		global $Ue;
		
		$list['ALPENKONVENTTION_INFORMANTEN'] = $Ue['ALPENKONVENTTION_INFORMANTEN'];
		$list['AUF_GEMEINDE'] = $Ue['AUF_GEMEINDE'];
		$list['DRUCKFASSUNG'] = $Ue['DRUCKFASSUNG'];
		
		return $list;
	}
	
	function va_load_im_config_files (){
		global $vadb;
		
		//Map plugin
		IM_Initializer::$instance->database = $vadb;
		IM_Initializer::$instance->map_function = 'create_va_map';
		IM_Initializer::$instance->load_function = 'load_va_data';
		IM_Initializer::$instance->edit_function = 'edit_va_data';
		
		add_filter('im_comment', function ($text, $id){
			global $Ue;
			parseSyntax($text, true);
			
			global $va_current_db_name;
			
			if($va_current_db_name != 'va_xxx'){
				$citation = va_create_comment_citation($id, $Ue);
				if($citation)
					$text .= '<span class="quote" title="' . $citation . '" style="font-size: 75%; cursor : pointer; color : grey;">(' . $Ue['ZITIEREN'] . ')</span>';
			}
			return $text;
		}, 10, 2);
	}
	
	function va_map_plugin_version (){
		if(!isDevTester()){
			define('IM_MAIN_PHP_FILE', dirname(__FILE__) . '/im_config/live/im_live.phar');
			define('IM_MAIN_JS_FILE', plugin_dir_url(__FILE__) . 'im_config/live/im_live.js');
			define('IM_MAIN_CSS_FILE', plugin_dir_url(__FILE__) . 'im_config/live/im_live.css');
		}
	}

	function va_menu_bar ($wp_admin_bar){
		$nodes = $wp_admin_bar->get_nodes();
		
		foreach ($nodes as $node){
			//if($node->parent == 'my-account')
				//error_log(json_encode($node));
			if($node->id != 'top-secondary' && $node->id != 'my-account' && $node->id != 'user-actions' && $node->parent != 'user-actions'){
				$wp_admin_bar->remove_node($node->id);
			}
		}
		
		if(!is_user_logged_in()){
			$wp_admin_bar->add_node(array(
				'id' => 'va_login',
				'title' => __('Log in'),
				'href' => wp_login_url(),
				'parent' => 'top-secondary'
			));
		}
		
// 		$wp_admin_bar->add_node(array(
// 				'id' => 'va_lang',
// 				'title' => __('Language')
// 		));
		
// 		$langs = array ('D', 'I');
// 		foreach ($langs as $lang){
// 			$wp_admin_bar->add_node(array(
// 				'id' => 'va_lang_' . $lang,
// 				'title' => $lang,
// 				'parent' => 'va_lang',
// 				'href' => 'test'
// 			));
// 		}
	}
	
	function va_add_footnotes_support ($content){
		$swas_wp_footnotes = new swas_wp_footnotes();
		return $swas_wp_footnotes->process($content);
	}
	
	function va_add_query_vars ($url){
		if(isset($_GET['db'])){
			$url = add_query_arg('db', $_GET['db'], $url);
		}
		return $url;
	}
	
	function filter_widgets ($sidebar_widgets){
		global $admin;
		global $va_mitarbeiter;
		global $post;
	
		foreach ($sidebar_widgets['sidebar-1'] as $key => $widget){
			if($widget == 'version-2' && $post->post_title != 'KARTE' && $post->post_title != 'METHODOLOGIE' && $post->post_title != 'KOMMENTARE'){
				unset($sidebar_widgets['sidebar-1'][$key]);
			}
		}
		return $sidebar_widgets;
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
	
	function internal_pages ($pages){
		global $admin;
		global $va_mitarbeiter;
		
		if(get_current_blog_id() == 1){
			if(!current_user_can('va_transcripts_read')){
				$pages = array_filter($pages, function ($page){
					return $page->ID != 1760;
				});
			}
			
// 			if(!$admin && !$va_mitarbeiter){
// 				$pages = array_filter($pages, function ($page){
// 					return $page->ID != 2371; //Echo
// 				});
// 			}
		}

		return $pages;
	}
	
	function stay_on_page ($logout_url){
		return add_query_arg('redirect_to', urlencode(( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']), $logout_url);
	}
	
	function add_favicon() {
		echo '<link rel="shortcut icon" href="' . get_stylesheet_directory_uri() .'/favicon.ico" />';
	}
	
	function parse_syntax_for_posts ($content){
		if(get_post_type() === 'post'){
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
	
	//Skripte für das Frontend
	function scripts_fe (){
		global $post;
		global $admin;
		global $va_mitarbeiter;
		global $va_current_db_name;
		
		
		wp_enqueue_script('toolsSkript', plugins_url('/util/tools.js', __FILE__), array('jquery'), false, true);
		wp_enqueue_style('va_style', plugins_url('/css/styles.css', __FILE__));
		
		$ajax_url = admin_url( 'admin-ajax.php');
		if(isset($_GET['dev'])){
			$ajax_url = add_query_arg('dev', 'true', $ajax_url);
		}
		if($va_current_db_name != 'va_xxx'){
			$ajax_url = add_query_arg('db', substr($va_current_db_name, 3), $ajax_url);
		}
		
		wp_localize_script( 'toolsSkript', 'ajax_object', array( 
				'ajaxurl' => $ajax_url, 
				'site_url' => get_site_url(1), 
				'db' => substr($va_current_db_name, 3),
				'va_staff' => $admin || $va_mitarbeiter
		));
		
		IM_Initializer::$instance->enqueue_qtips();
		
		if(isset($post)){
			if($post->post_title == 'KARTE'){ //Interaktive Karte
				if(isDevTester())
					wp_enqueue_style('va_map_style', plugins_url('/im_config/map.css', __FILE__));
					wp_enqueue_style ('jsTreeStyle', VA_PLUGIN_URL . '/lib/jstree/dist/themes/default/style.min.css');
					wp_enqueue_script('jsTreeScript', VA_PLUGIN_URL . '/lib/jstree/dist/jstree.min.js', array('jquery'));
			}
			else if($post->post_title == 'Fortschritt'){
				va_enqueue_tabs();
			}
			else if($post->post_title == 'Protokolle'){
				wp_enqueue_script('jquery-ui-datepicker');
				wp_enqueue_script('history.js', VA_PLUGIN_URL . '/lib/history.js/scripts/bundled/html5/jquery.history.js');
				wp_enqueue_style('jquery_ui', plugins_url('plugin_interactive-map/lib/css/jquery-ui.min.css'));
			}
			else if($post->post_title == 'METHODOLOGIE'){
				wp_enqueue_script('clipboard', VA_PLUGIN_URL . '/lib/clipboard.min.js');
			}
			else if($post->post_title == 'KOMMENTARE'){
				wp_enqueue_script('clipboard', VA_PLUGIN_URL . '/lib/clipboard.min.js');
			}
		}
	}
	
	//Skripte für das Backend
	function scripts_be ($hook){
	
		wp_enqueue_style('va_style', plugins_url('/css/styles.css', __FILE__));
		
		IM_Initializer::$instance->enqueue_qtips();
		
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		
		wp_enqueue_script('toolsSkript', plugins_url('/util/tools.js', __FILE__));
		wp_localize_script( 'toolsSkript', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )) );
	
		if($hook === 'verba-alpina_page_typification'){
			IM_Initializer::$instance->enqueue_chosen_library();
			IM_Initializer::$instance->enqueue_gui_elements();
			wp_enqueue_script('lex_typifiy_script', plugins_url('/backend/typification/lex.js', __FILE__));
			wp_enqueue_script('typifiy_script', plugins_url('/backend/typification/util.js', __FILE__));
		}
		else if ($hook === 'verba-alpina_page_konzeptbaum'){
			IM_Initializer::$instance->enqueue_gui_elements();
			wp_enqueue_style ('jsTreeStyle', VA_PLUGIN_URL . '/lib/jstree/dist/themes/default/style.min.css');
			wp_enqueue_script('jsTreeScript', VA_PLUGIN_URL . '/lib/jstree/dist/jstree.min.js', array('jquery'));
		}
		else if ($hook === 'verba-alpina_page_transkription'){
			if(isDevTester()){//TODO remove
				IM_Initializer::$instance->enqueue_select2_library();
				IM_Initializer::$instance->enqueue_gui_elements();
				enqueuePEG();
			}
			IM_Initializer::$instance->enqueue_chosen_library();
		}
		else if ($hook === 'toplevel_page_glossar'){
			IM_Initializer::$instance->enqueue_chosen_library();
			IM_Initializer::$instance->enqueue_gui_elements();
			wp_enqueue_script('history.js', VA_PLUGIN_URL . '/lib/history.js/scripts/bundled/html5/jquery.history.js');
		}
	}
	
	function enqueuePEG (){
		wp_enqueue_script('grammarScript', content_url('/lib/peg-0.8.0.min.js'));
	}
	
	function getLanguage (){
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
		return in_array( 'devtester', (array) wp_get_current_user()->roles ) || isset($_GET['dev']);
	}
	
	function translate_page_titles ($title){
		global $Ue;
		return isset($Ue[$title]) && $Ue[$title] != ''? $Ue[$title] : $title;
	}
	
	function translate_title ($title){
		global $Ue;
		
		if(strpos($title, 'Seite_') === 0)
			return get_field('person');
		
		if(strpos($title, 'VerbaAlpina') === 0 || $title == '')
			return $title;
		
		$pos_sep = strpos($title, '|');
		$page_title = substr($title, 0, $pos_sep - 1);
		$page_title = isset($Ue[$page_title]) && $Ue[$page_title] != ''? $Ue[$page_title] : $page_title;
			
		return $page_title . ' | ' . substr($title, $pos_sep + 1);
	}
	
	function getTranslations ($lang){
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
	
	function widget_includes (){
		//Have to be included earlier since the widgit_init hook is called with priority 1
		include_once('widgets/db_widget.php');
		include_once('widgets/internal_widget.php');
	}
	
	function includes (){
		global $admin;
		global $va_mitarbeiter;
		global $lang;
		global $Ue;
		
		$lang = getLanguage();
		$Ue = getTranslations($lang);
		
		$current_user = wp_get_current_user();
		$roles = $current_user->roles;
		$admin = in_array("administrator", $roles);
		$va_mitarbeiter = in_array('projektmitarbeiter', $roles);
	
		if(isDevTester()){ //TODO remove, too and use select
			global $va_playground;
			global $va_xxx;
			$va_playground = new wpdb($va_xxx->dbuser, $va_xxx->dbpassword, 'va_playground', $va_xxx->dbhost);
			$va_playground->show_errors();
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
		
		if(current_user_can('va_transcripts_read')){
			add_shortcode('protokolle', function ($attr) {return protokolle ();}); //protokolle.php
		}
		
		if(current_user_can('va_see_progress_page')){
			add_shortcode('overview', 'overview_page');
		}
		
		if($va_mitarbeiter || $admin){
			add_shortcode('dbdescription', function ($attr) {return showDBDescription ();}); //admin_table.php
			add_shortcode('overview_app', 'overview_app_page');
		}
		
		//Includes
		include_once('util/tools.php');
		include_once('util/ajax/va_ajax.php');
		include_once('util/parseGlossarSyntax.php');
	
		include_once('backend/glossar_edit.php');
		
		//include_once('scans.php');
		include_once('backend/concept_tree.php');
	
		include_once('backend/cmedia.php');
	
		
		include_once('pages/home.php');
		
		include_once('pages/personen.php');
		
		include_once('pages/glossar.php');
		include_once('pages/comments.php');
		include_once('backend/translate.php');
		include_once('pages/publikationen.php');
		include_once('pages/kontakt.php');
		include_once('util/tree.php');
		
		if(isDevTester()){
			include_once('backend/transcription/transcription.php');
		}
		else {
			include_once('transkription/main.php');
		}
		
		include_once('backend/typification/lex.php');
		include_once('backend/typification/util.php');
		
		include_once('backend/auto/main.php');
		include_once 'backend/auto/ipa.php';
		include_once('util/pointListToKML.php');
		
		if(current_user_can('va_transcripts_read')){
			include_once('pages/protokolle.php');
		}
		
		if(current_user_can('va_see_progress_page')){
			include_once('pages/overview.php');
		}
		
		if($va_mitarbeiter || $admin){
			include_once('pages/app_overview.php');
			include_once('admin_table.php');
		}
		
		if(isDevTester()){
			include_once('im_config/map.php');
			include_once('im_config/db.php'); //TODO get dev-tester running for AJAX calls!!!
			
			include_once('backend/glossar_errors.php');
			include_once('test.php');
			include_once('backend/auto/clapie.php');
		}
	}
	
		
	//Admin Menü
	
	function addMenuPoints () {
		global $admin;
		global $va_mitarbeiter;
		
		add_menu_page('Verba Alpina','Verba Alpina', 'glossar', 'glossar');
		add_submenu_page('glossar', 'Glossar-Einträge ändern','Glossar-Einträge ändern', 'glossar', 'glossar', 'glossar');
		if(isDevTester()){
			add_submenu_page('glossar', __('Transcription tool', 'verba-alpina'), __('Transcription tool', 'verba-alpina'), 'va_transcription_tool_read', 'transkription', 'va_transcription');
		}
		else {
			add_submenu_page('glossar', __('Transcription tool', 'verba-alpina'), __('Transcription tool', 'verba-alpina'), 'va_transcription_tool_read', 'transkription', 'transkription');
		}
		
		add_submenu_page('glossar', 'Übersetzung Oberfläche', 'Übersetzung Oberfläche', 'verba_alpina', 'transl', 'va_translation_page'); 
		add_submenu_page('glossar', 'Konzeptbaum', 'Konzeptbaum', 'va_concept_tree_read', 'konzeptbaum', 'konzeptbaum'); 
		add_submenu_page('glossar', __('Typification', 'verba-alpina'), __('Typification', 'verba-alpina'), 'va_typification_tool_read', 'typification', 'lex_typification');
		
		add_menu_page('Tools','Tools', 'verba_alpina', 'va_tools', function (){echo '';});
		add_submenu_page('va_tools', 'Tools','SQL -> KML', 'verba_alpina', 'va_tools_kml', 'kml_transform');
		add_submenu_page('va_tools', 'Tools','Beta -> IPA', 'verba_alpina', 'va_tools_ipa', 'ipa_page');
		add_submenu_page('va_tools', 'Tools','Bibliographie', 'verba_alpina', 'va_tools_bib', 'itg_create_menu_page');
		if(isDevTester()){
			add_submenu_page('va_tools', 'Tools', 'Fehler im Glossar', 'verba_alpina', 'va_tools_glossary', 'search_glossary_errors');
			add_submenu_page('va_tools', 'Tools', 'VA-Seiten erstellen', 'verba_alpina', 'va_tools_create_pages', 'va_create_frontend_pages');
		}
		
		add_menu_page(__('Data base', 'verba-alpina'), __('Data base', 'verba-alpina'), 'data-base', 'dba', 'dba');
		
		
		if($va_mitarbeiter || $admin){
			
			add_pages_page('Persönliche Seite', 'Persönliche Seite', 'verba_alpina', 'personal_page', 'create_pp');
			if($va_mitarbeiter){
				remove_submenu_page('edit.php?post_type=page', 'post-new.php?post_type=page');
				remove_submenu_page('edit.php?post_type=page', 'edit.php?post_type=page');
			}
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
			
			if(!function_exists('ep_get_excluded_ids')){
				return 'Exclude Pages plugin has to be activated!';
			}
			
			//Exclude from navigation (Exclude Pages Plugin!)
			$excluded_ids = ep_get_excluded_ids();
			array_push( $excluded_ids, $id );
			$excluded_ids_str = implode( EP_OPTION_SEP, $excluded_ids );
			ep_set_option( EP_OPTION_NAME, $excluded_ids_str );
		}
		?>
			<script type="text/javascript">
				window.location = "<?php echo get_admin_url() . "post.php?post=$id&action=edit"; ?>";
			</script>
		<?php
	}
	
	function loginFunc ($user_login, $user){
		$roles = $user->roles;
		$admin = in_array("administrator", $roles);
		$va_mitarbeiter = in_array('projektmitarbeiter', $roles);
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
		$page = get_page_by_title('TERMINOLOGIE');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'TERMINOLOGIE',
					'post_content' => '[glossarSeite]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty.php',
					'post_autor' => get_current_user_id(),
					'menu_order' => 3
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
					'menu_order' => 7
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
					'post_parent' => $pub_page->ID,
					'menu_order' => 905
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
		//TODO try to get rid of the exclude pages plugin
	}
}
?>