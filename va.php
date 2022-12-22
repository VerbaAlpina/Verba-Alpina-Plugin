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
	$administrator->add_cap('delete_others_questionnaires');
	$administrator->add_cap('delete_questionnaires');
	$administrator->add_cap('delete_private_questionnaires');
	$administrator->add_cap('delete_published_questionnaires');
	$administrator->add_cap('edit_others_questionnaires');
	$administrator->add_cap('edit_questionnaires');
	$administrator->add_cap('edit_private_questionnaires');
	$administrator->add_cap('edit_published_questionnaires');
	$administrator->add_cap('publish_questionnaires');
	$administrator->add_cap('read_private_questionnaires');
	
	$administrator->add_cap('read_db_documentation'); //TODO move to dbdocu plugin
}

global $login_data;
$login_data = file(plugin_dir_path(__FILE__) . 'login', FILE_IGNORE_NEW_LINES);

$is_external = get_option('va_external', NULL);

if ($is_external === NULL){
	
	if (isset($_REQUEST['va_external'])){
		update_option('va_external', $_REQUEST['va_external'] == 1);
	}
	else {
		add_action('admin_notices', function (){
		?>
		<div class="update-nag">VerbaAlpina-Plugin: <a href="?va_external=0">Interne (im GWI-Netz)</a> oder <a href="?va_external=1">externe</a> Installation</div>
		<?php
		});
	}
}

if(class_exists('IM_Initializer') && $login_data !== false && $is_external !== NULL){
	
	define ('VA_PLUGIN_URL', plugins_url('', __FILE__));
	define ('VA_PLUGIN_PATH', plugin_dir_path(__FILE__));
	
	remove_filter('the_content', 'wptexturize');
	
	//Action Handler/Filter:
	add_action('plugins_loaded', 'va_load_textdomain');
	add_action('wp_loaded', function (){
	    $mapPage = get_page_by_title('KARTE');
	    if($mapPage != null){
	        define ('VA_MAP_URL', get_page_link($mapPage));
	    }
	});
	
	add_action( 'signup_extra_fields', function ($errors){
		if (class_exists('ReallySimpleCaptcha')){
			$wpud = wp_upload_dir();
			
			$captcha_instance = new ReallySimpleCaptcha();
			$captcha_instance->tmp_dir = $wpud['path'] . '/captcha/';
		
			$word = $captcha_instance->generate_random_word();
			
			$prefix = mt_rand();
			$file = $captcha_instance->generate_image( $prefix, $word );
			
			$captcha_instance2 = new ReallySimpleCaptcha();
			$captcha_instance2->tmp_dir = $wpud['path'] . '/captcha/';
		
			$word = $captcha_instance2->generate_random_word();
			
			$prefix2 = mt_rand();
			$file2 = $captcha_instance2->generate_image( $prefix2, $word );
			
			echo '<br /><br /><div style="inline-block"><img src="' . $wpud['url'] . '/captcha/' . $file . '">';
			echo '<img src="' . $wpud['url'] . '/captcha/' . $file2 . '"></div>';
			$errmsg = $errors->get_error_message( 'captcha' );
			if ( $errmsg ) {
				echo '<p class="error">' . $errmsg . '</p>';
			}
			echo '<input name="captcha" type="text"><input name="image_prefix" type="hidden" value="' . $prefix . '"><input name="extra" type="hidden" value="' . $prefix2 . '"><br />';
			_e( 'Please type the characters you see in the picture above (without any space).' );
		}
	});
	
	add_filter( 'wpmu_validate_user_signup', function ($result) {
		$wpud = wp_upload_dir();
		
		$captcha_instance = new ReallySimpleCaptcha();
		$captcha_instance->tmp_dir = $wpud['path'] . '/captcha/';
		
		$captcha_instance2 = new ReallySimpleCaptcha();
		$captcha_instance2->tmp_dir = $wpud['path'] . '/captcha/';
		
		$correct = false;
		if (isset($_POST['captcha']) && $_POST['captcha']){
			$correct = $captcha_instance->check($_POST['image_prefix'], substr($_POST['captcha'], 0, 4)) && $captcha_instance2->check($_POST['extra'], substr($_POST['captcha'], 4));
		}
		
		if (!$correct){
			$result['errors']->add( 'captcha', __( 'Please type the characters you see in the picture above' ) );
		}
		return $result;
	});
	
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
	
	add_action ('tt_missing_original_char', function ($char){
		global $va_xxx;
		$entry = $va_xxx->get_var($va_xxx->prepare('SELECT Beta FROM Codepage_Original WHERE Beta = %s', $char));
		
		if (!$entry){
			$va_xxx->insert('Codepage_Original', ['Beta' => $char, 'Original' => '']);
		}
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
	
	global $useEnglishLocale; //This var is used mainly for use of remove_accents where "ü" should not be replaced by "ue" etc.
	$useEnglishLocale = false;
	
	add_filter('locale', function ($l){
		global $useEnglishLocale;
		if ($useEnglishLocale){
			return 'en_GB';
		}
		return $l;
	});
	
// 	add_filter('tt_get_rules_text', function ($false, $lang){
// 		global $va_xxx;
		
// 		error_log($lang);
// 		$lang_short = substr($lang, 0, 1);
// 		$texts = $va_xxx->get_row('SELECT Terminus_E, Erlaeuterung_E, Terminus_' . $lang_short . ', Erlaeuterung_' . $lang_short . ' FROM Glossar WHERE Id_Eintrag = 150', ARRAY_N);
		
// 		if ($texts[2] && $texts[3]){
// 			return '<h1 style="text-align: center">' . $texts[2] . '</h1>' . $texts[3];
// 		}
		
// 		return '<h1 style="text-align: center">' . $texts[0] . '</h1>' . $texts[1];
// 	});
	
	//Scripts
	add_action( 'wp_enqueue_scripts', 'scripts_fe' ); //Skripte für das Frontend einbinden
	add_action( 'admin_enqueue_scripts', 'scripts_be' ); //Skripte für das Backend einbinden
	
	add_action('delete_attachment', 'va_no_media_deletion', 11, 1);

	add_filter( 'the_title', 'va_translate_page_titles', 9, 2 ); //Page titles
	add_filter( 'wp_title', 'va_translate_title', 10, 2 ); //Title in html-Head
	add_action('admin_menu', 'addMenuPoints'); //Admin Menü
	
	add_filter('show_admin_bar', 'va_show_admin_bar');
	
	add_action('login_head', 'add_favicon');
	add_action('admin_head', 'add_favicon');
	add_filter('the_content', 'parse_syntax_for_posts', 15); //Has to be after footnotes (11) to enable e.g. Bibl in footnotes (otherwise the html is escaped). Used to be 1 for an unknown reason.
	
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
	
	add_filter('tiny_mce_before_init', 'va_fonts');
	
	// add_action('init', 'va_rewrite');
	
	// function va_rewrite (){
		// add_rewrite_rule('page_id=2374$', 'page_id=12180', 'bottom');
	// }
	
	function va_no_media_deletion($postID){
		exit('You cannot delete media.');
	}
	
	function va_fonts ($init) {

		$stylesheet_url = plugins_url('/css/fonts_editor.css', __FILE__);

		if(empty($init['content_css'])){
			$init['content_css'] = $stylesheet_url;
		} else {
			$init['content_css'] = $init['content_css'].','.$stylesheet_url;
		}
		
		if (isset($init['font_formats'])){
			$init['font_formats'] .= ';Doulos SIL=doulosSIL';
		}
		else {
			$init['font_formats'] = 'Andale Mono=andale mono,times;Arial=arial,helvetica,sans-serif;Arial Black=arial black,avant garde;Book Antiqua=book antiqua,palatino;Comic Sans MS=comic sans ms,sans-serif;Courier New=courier new,courier;Georgia=georgia,palatino;Helvetica=helvetica;Impact=impact,chicago;Symbol=symbol;Tahoma=tahoma,arial,helvetica,sans-serif;Terminal=terminal,monaco;Times New Roman=times new roman,times;Trebuchet MS=trebuchet ms,geneva;Verdana=verdana,geneva;Webdings=webdings;Wingdings=wingdings,zapf dingbats;Doulos SIL=doulosSIL';
		}
		

		return $init;
	}
	
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
		IM_Initializer::$instance->similarity_function= 'va_dialectology';
		
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
			
			$text .= '<br /><a target="_BLANK" href="' . va_get_comments_link($id) . '">' . $Ue['LEX_ALP_REF'] . '</a>';
			
			return $text;
		}, 10, 3);
	}
	
	function va_check_api_call (){
		if (isset($_REQUEST['api']) && $_REQUEST['api']){
			include_once 'export/api.php';
			va_handle_api_call();
			exit;
		}
	}
		
	function va_map_plugin_version (){
		if(!isDevTester() && (!is_admin() || (isset($_POST['action']) && $_POST['action'] == 'im_a'))){
			define('IM_MAIN_PHP_FILE', dirname(__FILE__) . '/im_config/live/im_live.phar');
			define('IM_MAIN_JS_FILE', plugin_dir_url(__FILE__) . 'im_config/live/im_live.js?v=5');
			define('IM_MAIN_CSS_FILE', plugin_dir_url(__FILE__) . 'im_config/live/im_live.css?v=1');
		}
	}
	
	function va_show_admin_bar ($bool){
		return false;
	}
	
	function va_add_footnotes_support ($content){
		if(function_exists('process_footnote')){
		    return process_footnote($content);
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
			'WISS_PUBLIKATIONEN', 'BEITRAEGE', 'INFORMATIONSMATERIAL', 'LexAlp', 'REZEPTION', 'BIBLIOGRAPHIE', 'CS_MITMACHEN', 'APIDOKU', 'CSGRAPH');
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
		if (mb_strpos($_SERVER['REQUEST_URI'], 'wp-activate') === false){
			return add_query_arg('redirect_to', urlencode(( is_ssl() ? 'https://' : 'http://' ) . $_SERVER['HTTP_HOST'] . add_query_arg('db', 'xxx', $_SERVER['REQUEST_URI'])), $url);
		}
		return $url;
	}
	
	function add_favicon() {
		echo '<link rel="shortcut icon" href="' . get_stylesheet_directory_uri() .'/favicon.ico" />';
	}
	
	function parse_syntax_for_posts ($content){
		$type = get_post_type();
		if($type === 'post' || $type === 'page' || $type === 'revision'){
			global $va_mitarbeiter;
			global $admin;
			parseSyntax($content, false, $va_mitarbeiter || $admin, 'A', $type === 'post' || $type === 'revision');
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
		wp_enqueue_style('va_style', plugins_url('/css/styles.css?v=4', __FILE__));
	}
	
	//Skripte für das Frontend
	function scripts_fe (){
		global $post;
		global $admin;
		global $va_mitarbeiter;
		global $va_current_db_name;
		global $va_max_db_name;
		global $va_next_db_name;
		global $Ue;
		
		wp_enqueue_script('toolsSkript', plugins_url('/util/tools.js?v=1', __FILE__), array('jquery'), false, true);
		IM_Initializer::$instance->enqueue_font_awesome();
		IM_Initializer::$instance->enqueue_bootstrap();
		wp_enqueue_script('cookieConsent', VA_PLUGIN_URL . '/lib/cookieconsent.min.js');

		wp_enqueue_script('QRCode', VA_PLUGIN_URL . '/lib/easy.qrcode.js');

		
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
		    'max_db' => substr($va_max_db_name, 3),
			'va_staff' => $admin || $va_mitarbeiter,
			'dev' => isDevTester()? '1': '0',
			'user' => wp_get_current_user()->user_login,
	        'next_version' => $va_next_db_name,
		    'lex_path' => va_get_comments_link(),
		    'local_db' => get_option('va_local_db', false)
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
				wp_enqueue_script('tablesorter.js', VA_PLUGIN_URL . '/lib/tablesorter/jquery.tablesorter.min.js');
				wp_enqueue_style('tablesorter.js', VA_PLUGIN_URL . '/lib/tablesorter/theme.default.css');
			}
			else if($post->post_title == 'Protokolle'){
				wp_enqueue_script('jquery-ui-datepicker');
				wp_enqueue_script('jquery-ui-dialog');
				wp_enqueue_script('history.js', VA_PLUGIN_URL . '/lib/history.js/scripts/bundled/html5/jquery.history.js');
				wp_enqueue_style('jquery_ui', plugins_url('plugin_interactive-map/lib/css/jquery-ui.min.css'));
			}
			else if($post->post_title == 'KOMMENTARE' || $post->post_title == 'LexAlp' || $post->post_title == 'METHODOLOGIE'  || $post->post_title == 'Methodologie NEU'){
				wp_enqueue_script('flip', VA_PLUGIN_URL . '/lib/jquery.flip.min.js');
				wp_enqueue_script('clusterize', VA_PLUGIN_URL . '/lib/clusterize.js');
				wp_enqueue_script('detect_zoom', VA_PLUGIN_URL . '/lib/detect_zoom.js');
				wp_enqueue_script('mark', VA_PLUGIN_URL . '/lib/jquery.mark.min.js');
				wp_enqueue_script('clipboard', VA_PLUGIN_URL . '/lib/clipboard.min.js');
				wp_enqueue_script('tablesorter.js', VA_PLUGIN_URL . '/lib/tablesorter/jquery.tablesorter.min.js');
				wp_enqueue_style('tablesorter.css', VA_PLUGIN_URL . '/lib/tablesorter/theme.dark.css');
				wp_enqueue_script('shepherd.js', VA_PLUGIN_URL . '/lib/shepherd/shepherd.min.js');
				wp_enqueue_style('shepherd.css', VA_PLUGIN_URL . '/lib/shepherd/shepherd.css');
				wp_enqueue_script('lexiconHelpTour.js', VA_PLUGIN_URL . '/lib/shepherd/lexiconHelpTour.js');
			}

			else if($post->post_title == 'Versionen'){
				wp_enqueue_script('flip', VA_PLUGIN_URL . '/lib/jquery.flip.min.js');
			}

			else if($post->post_title == 'DATENBANK-DOKU'){
				va_enqueue_tabs();
			}			
			else if ($post->post_title == 'Todos'){
			    wp_enqueue_script('jquery-ui-dialog');
			    wp_enqueue_style('im_jquery-ui-style');
			}
			else if ($post->post_title == 'DizMT Eingabe'){
				IM_Initializer::$instance->enqueue_select2_library();
			}
			else if ($post->post_title == 'DBDokuNeu'){
			    wp_enqueue_script('jquery-ui-accordion');
			    wp_enqueue_style('im_jquery-ui-style');
			}
			else if ($post->post_type == 'fragebogen'){
				IM_Initializer::$instance->enqueue_select2_library();
			}			
			else if ($post->post_type == 'post'){
				wp_enqueue_script('clipboard', VA_PLUGIN_URL . '/lib/clipboard.min.js');
			}
		}	
	}
	
	function va_enqueue_scripts_footer (){
		global $record_input_shortcode;
		if($record_input_shortcode){
			IM_Initializer::$instance->enqueue_gui_elements();
			wp_localize_script('toolsSkript', 'ajaxurl', admin_url('admin-ajax.php'));
		}
		
		global $post;
		if(isset($post)){
    		if ($post->post_title == 'LexAlp'){
    		    global $vadb;
    		    $comp_ids = $vadb->get_col('SELECT DISTINCT Id FROM a_lex_titles WHERE Id LIKE "%+%"');
    		    $mapping = [];
    		    foreach ($comp_ids as $cid){
    		        $ids = explode('+', substr($cid, 1));
    		        foreach ($ids as $sid){
    		            $mapping[substr($cid, 0, 1) . $sid] = $cid;
    		        }
    		    }
    		    wp_localize_script( 'toolsSkript', 'ID_MAPPING', $mapping);
    		}
    		else if ($post->post_title == 'METHODOLOGIE' || $post->post_title == 'Methodologie NEU'){
    		    wp_localize_script( 'toolsSkript', 'ID_MAPPING', []);
    		}
		}
	}
	
	//Skripte für das Backend
	function scripts_be ($hook){
		wp_enqueue_style('va_style', plugins_url('/css/styles.css?v=3', __FILE__));
		
		if ($hook != 'va-tools-i_page_transcription'){
			IM_Initializer::$instance->enqueue_qtips(); //Use own version of lib
		}
		
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		
		wp_enqueue_script('toolsSkript', plugins_url('/util/tools.js', __FILE__));
		wp_localize_script( 'toolsSkript', 'ajax_object', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )) );
	
		if($hook === 'va-tools-i_page_typification'){
			va_lex_scripts();
		}
		else if ($hook === 'va-tools-i_page_base_types'){
			IM_Initializer::$instance->enqueue_chosen_library();
			IM_Initializer::$instance->enqueue_select2_library();
			IM_Initializer::$instance->enqueue_gui_elements();
			wp_enqueue_script('typifiy_script', plugins_url('/backend/typification/util.js', __FILE__));
		}
		else if ($hook === 'va-tools-i_page_konzeptbaum'){
			IM_Initializer::$instance->enqueue_gui_elements();
			wp_enqueue_style ('jsTreeStyle', VA_PLUGIN_URL . '/lib/jstree/dist/themes/default/style.min.css');
			wp_enqueue_script('jsTreeScript', VA_PLUGIN_URL . '/lib/jstree/dist/jstree.min.js', array('jquery'));
			global $va_xxx;
			$parents = $va_xxx->get_results('SELECT Id_Kategorie, Id_Ueberkategorie FROM Konzepte_Kategorien WHERE Id_Ueberkategorie IS NOT NULL', ARRAY_N);
			wp_localize_script('jsTreeScript', 'PARENTS', va_two_dim_to_assoc($parents));
		}
		else if ($hook === 'va-tools-i_page_transcription'){
			IM_Initializer::$instance->enqueue_gui_elements();
		}
		else if ($hook === 'toplevel_page_va'){
			IM_Initializer::$instance->enqueue_chosen_library();
			IM_Initializer::$instance->enqueue_gui_elements();
			wp_enqueue_script('history.js', VA_PLUGIN_URL . '/lib/history.js/scripts/bundled/html5/jquery.history.js');
		}
		else if ($hook === 'va-tools-i_page_edit_comments'){
			IM_Initializer::$instance->enqueue_chosen_library();
		}
		else if ($hook === 'toplevel_page_va_tools'){
			enqueuePEG();
		}
		else if ($hook === 'va-tools-ii_page_va_tools_bsa'){
			IM_Initializer::$instance->enqueue_select2_library();
			IM_Initializer::$instance->enqueue_chosen_library();
			IM_Initializer::$instance->enqueue_gui_elements();
		}
		else if ($hook === 'va-tools-i_page_test'){
			enqueuePEG();
		}
		else if ($hook === 'va-tools-ii_page_va_tools_emails'){
		    wp_enqueue_script('tablesorter.js', VA_PLUGIN_URL . '/lib/tablesorter/jquery.tablesorter.min.js');
		    wp_enqueue_style('tablesorter.js', VA_PLUGIN_URL . '/lib/tablesorter/theme.default.css');
		}
		else if ($hook === 'va-tools-ii_page_va_tools_single_comments'){
			IM_Initializer::$instance->enqueue_select2_library();
		}
		else if ($hook === 'va-tools-ii_page_va_tools_informant_geo'){
			IM_Initializer::$instance->enqueue_select2_library();
		}
		else if ($hook === 'va-tools-ii_page_va_tools_drg_concepts'){
			IM_Initializer::$instance->enqueue_select2_library();
			IM_Initializer::$instance->enqueue_gui_elements();
		}
		else if ($hook === 'va-tools-ii_page_va_tools_references'){
			IM_Initializer::$instance->enqueue_select2_library();
		}		
		else if ($hook === 'va-tools-i_page_lex_problems'){
		    va_enqueue_tabs();
		    IM_Initializer::$instance->enqueue_select2_library();
		    IM_Initializer::$instance->enqueue_chosen_library();
		    IM_Initializer::$instance->enqueue_gui_elements();
		    wp_enqueue_script('typifiy_script', plugins_url('/backend/typification/util.js', __FILE__));
		    va_lex_translations();
		}
		else if ($hook=== 'va-tools-ii_page_va_tools_bulk_download'){
			IM_Initializer::$instance->enqueue_select2_library();
		}
	}
	
	function va_admin_bar_entries ($wp_admin_bar){
		
		if (is_admin() && isset($_REQUEST['page'])){
			if ($_REQUEST['page'] == 'va'){
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
			$title = isset($Ue[$title]) && $Ue[$title] != ''? ucfirst($Ue[$title]) : $title;
			
			return $title;
		}
		
		if($type == 'ethnotext'){
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
		
		if ($page_title == 'Lexicon Alpinum' && va_is_municipality_list()){
			$page_title = $Ue['Gemeinden'];
		}
		
		return $page_title . ' | ' . substr($title, $pos_sep + 1);
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
		
		include_once('util/tools.php');
		list($dbuser, $dbpassw, $dbhost) = va_get_db_creds($login_data);

		global $va_xxx;
		//Va_xxx data base, used for all queries that have to be placed in the current working version
		$va_xxx = new wpdb($dbuser, $dbpassw, 'va_xxx', $dbhost);
		$va_xxx->show_errors();
		
		global $va_current_db_name;
		global $va_next_db_name;
		global $va_max_db_name;
		
		$max_version = $va_xxx->get_var('SELECT MAX(Nummer) FROM Versionen');
		$va_next_db_name = va_increase_version($max_version);
		$va_max_db_name = 'va_' . $max_version;
		
		if(is_user_logged_in()){
			$va_current_db_name = 'va_xxx';
		}
		else {
			$va_current_db_name = 'va_' . $max_version;
		}
		
		if(isset($_REQUEST['db'])){
			if ($_REQUEST['db'] > $max_version){
				$va_current_db_name = 'va_xxx';
			}
			else {
				$va_current_db_name = 'va_' . $_REQUEST['db'];
			}
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
		else if (isset($_POST['action']) && $_POST['action'] == 'im_a' && isset($_POST['namespace']) && $_POST['namespace'] == 'load_data'){
		    $dbuser_ro = $login_data[2];
		    $dbpassw_ro = $login_data[3];
		    
		    $va_ro = new wpdb($dbuser_ro, $dbpassw_ro, $va_current_db_name, $dbhost);
		    IM_Initializer::$instance->database = $va_ro;
		}
		else {
			IM_Initializer::$instance->database = $vadb;
		}
		
		$lang = va_get_language();
		$Ue = va_get_translations($va_xxx, $lang);

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
			if (is_admin()){
			    $mappings['transcription_rules'] = new TableMapping(get_user_locale() == 'de_DE'? 'trules': (get_user_locale() == 'fr_FR'? 'trules_f': 'trules_e'));
			}
			else {
			    $mappings['transcription_rules'] = new TableMapping(get_locale() == 'de_DE'? 'trules': (get_locale() == 'fr_FR'? 'trules_f': 'trules_e'));
			}
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
			$va_xxx->get_results("SELECT Id_Konzept AS id, IF(Name_D != '' AND Name_D != Beschreibung_D, CONCAT(Name_D, ' (', Beschreibung_D, ')'), Beschreibung_D) as text FROM Konzepte ORDER BY Text ASC", ARRAY_A),
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
		add_shortcode('lexicon', 'va_lexicon'); //pages/lexicon.php
		add_shortcode('versionGallery', 'va_version_gallery'); //pages/lexicon.php
		add_shortcode('echo', 'va_echo_page');//publikation.php
		add_shortcode('showCodepages', 'va_codepage_page');//codepage.php
		add_shortcode('csgraph', 'va_graph_page');//cs_graph.php
		add_shortcode('livegraph', 'va_live_graph_page'); //live_graph.php
		add_shortcode('showBib', 'show_bib_page');//pages/bib.php
		add_shortcode('showConceptIllustrations', 'show_concept_images'); //pages/concept_images.php
		add_shortcode('normPage', 'va_norm_page'); //pages/norm.php
		add_shortcode('version_list', 'va_version_list'); //util/tools.php
		
		add_shortcode('callList', 'va_call_list'); //pages/calls.php 
		add_shortcode('db_doku_page', 'va_show_db_doku'); //pages/db_doku.php
		
		if (isDevTester()){
			
			//add_shortcode('cluster_test', 'va_compute_clusters');
			add_shortcode('zooniverse_results', 'va_zooniverse_results'); //pages/zooniverse.php
		}
		
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
		}
		
		if (current_user_can('edit_questionnaires')){
			add_shortcode('questionnaireResults', 'show_questionnaire_results'); //pages/qresults.php
		}
		
		//Includes
		require 'lib/html5-dom-document/autoload.php';
		
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
		include_once('pages/lexicon.php');
		include_once('pages/comments.php');
		include_once('backend/translate.php');
		include_once('pages/publikationen.php');
		include_once('pages/kontakt.php');
		include_once('pages/bib.php');
		include_once('pages/concept_images.php');
		include_once('util/tree.php');
		include_once('pages/norm.php');
		include_once('pages/calls.php');
		include_once('pages/versiongallery.php');
		
		include_once('backend/typification/lex.php');
		include_once('backend/typification/problems.php');
		include_once('backend/typification/util.php');
		include_once('backend/typification/base.php');
		
		include_once('backend/auto/tokenize.php');
		include_once('backend/auto/kml.php');
		include_once 'backend/auto/ipa.php';
		include_once 'backend/auto/original.php';
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
		include_once('pages/db_doku.php');
		
		if($va_mitarbeiter || $admin){
			include_once('pages/app_overview.php');
			include_once('pages/statistics.php');
			include_once('backend/cs_emails.php');
			include_once('backend/auto/import_bsa.php');
			include_once('pages/record_input.php');
			include_once('backend/glossar_errors.php');
			include_once('backend/comments_single.php');
			include_once('backend/konzepte_drg.php');
			include_once('backend/references.php');
			include_once('backend/bulk_download_media.php');
		}
		
		if (current_user_can('edit_questionnaires')){
			include_once('pages/qresults.php');
		}
		
		if(isDevTester()){
			//include_once('util/type_clusters.php');
			include_once('pages/zooniverse.php');


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
			include_once('backend/dibs.php');
			
			
			include_once('util/corrections.php');
		}
		
		include_once('util/va_beta_parser.php');
		include_once('acf_groups/questionnaire.php');
		
		include_once('export/converter.php');
		include_once('export/text_converter.php');
		
		include_once('lib/simplediff.php');
	}
	
		
	//Admin Menü
	
	function addMenuPoints () {
		global $admin;
		global $va_mitarbeiter;
		
		add_menu_page('Verba Alpina','VA-Tools I', 'va_glossary', 'va');
		add_submenu_page('va', 'Verba Alpina','Glossar-Einträge bearbeiten', 'va_glossary', 'va', 'glossar');
		add_submenu_page('va', 'Kommentare bearbeiten','Kommentare bearbeiten', 'va_glossary', 'edit_comments', 'va_edit_comments_page');
		
		if (class_exists('TranscriptionTool')){
			TranscriptionTool::create_menu('va');
		}
		
		add_submenu_page('va', 'Übersetzung Oberfläche', 'Übersetzung Oberfläche', 'verba_alpina', 'transl', 'va_translation_page'); 
		add_submenu_page('va', 'Konzeptbaum', 'Konzeptbaum', 'va_concept_tree_read', 'konzeptbaum', 'konzeptbaum'); 
		add_submenu_page('va', __('Typification', 'verba-alpina'), __('Typification', 'verba-alpina'), 'va_typification_tool_read', 'typification', 'lex_typification');
		add_submenu_page('va', __('Problems Typification', 'verba-alpina'), __('Typification - Problems', 'verba-alpina'), 'va_typification_tool_read', 'lex_problems', 'lex_problems');
		add_submenu_page('va', 'Basistypen bearbeiten', 'Basistypen bearbeiten', 'va_typification_tool_read', 'base_types', 'va_edit_base_type_page');
		
		add_menu_page('Tools','VA-Tools II', 'verba_alpina', 'va_tools');
		add_submenu_page('va_tools', 'Tools', 'Beta -> IPA', 'verba_alpina', 'va_tools', 'ipa_page');
		add_submenu_page('va_tools', 'Tools', 'Tokenisierung', 'va_tokenization', 'va_tools_tok', 'va_create_tokenizer_page');
		add_submenu_page('va_tools', 'Tools', 'SQL -> KML', 'verba_alpina', 'va_tools_kml', 'kml_transform');
		add_submenu_page('va_tools', 'Tools', 'Import BSA', 'verba_alpina', 'va_tools_bsa', 'va_import_bsa_page');
		add_submenu_page('va_tools', 'Tools', 'Fehler im Glossar', 'verba_alpina', 'va_tools_glossary', 'search_glossary_errors');
		add_submenu_page('va_tools', 'Tools', 'Kommentare Orte', 'verba_alpina', 'va_tools_single_comments', 'va_single_comments_page');
		add_submenu_page('va_tools', 'Tools', 'Beta -> Original', 'verba_alpina', 'va_tools_original_conv', 'va_original_page');
		add_submenu_page('va_tools', 'Tools', 'DRG-Konzepte', 'verba_alpina', 'va_tools_drg_concepts', 'va_drg_concepts');
		add_submenu_page('va_tools', 'Tools', 'Typ-Referenz-Zuordnung', 'verba_alpina', 'va_tools_references', 'va_reference_page');

		add_submenu_page('va_tools', 'Tools', 'BulkDownloadTool', 'verba_alpina', 'va_tools_bulk_download', 'va_bulk_download_media');				
		
		if(isDevTester()){
			add_submenu_page('va_tools', 'Tools', 'VA-Seiten erstellen', 'verba_alpina', 'va_tools_create_pages', 'va_create_frontend_pages');
			//add_submenu_page('va_tools', 'Tools','Bibliographie', 'verba_alpina', 'va_tools_bib', 'itg_create_menu_page');
			add_submenu_page('va_tools', 'Tools', 'Tagung -> KIT', 'verba_alpina', 'va_tools_kit', 'va_kit_transform');
			add_submenu_page('va_tools', 'Tools', 'Tokenisierung verifizieren', 'verba_alpina', 'va_tools_check_tokens', 'va_check_tokens');
			add_submenu_page('va_tools', 'Tools', 'Tokenisierer testen', 'verba_alpina', 'va_tools_check_tokenizer', 'va_check_tokenizer');
			add_submenu_page('va_tools', 'Tools', 'Geonames', 'verba_alpina', 'va_tools_geonames', 'va_geonames_page');
			add_submenu_page('va_tools', 'Tools', 'Geodaten Informanten', 'verba_alpina', 'va_tools_informant_geo', 'va_informant_geo_page');
			add_submenu_page('va_tools', 'Tools', 'DIBS Import', 'verba_alpina', 'va_tools_dibs_import', 'va_tools_dibs_import');
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
			add_submenu_page('va', 'Autom. Operationen','Autom. Operationen', 'verba_alpina', 'auto', 'va_auto');
			add_submenu_page('va', 'Test','Test', 'verba_alpina', 'test', 'test');
			add_submenu_page('va', 'Clapie','Clapie', 'verba_alpina', 'clapie', 'clapie');
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
				'post_content' => '[glossarSeite]',
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
		$page = get_page_by_title('LexAlp');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'LexAlp',
					'post_content' => '[lexicon]',
					'post_status' => 'publish',
					'post_type' => 'page',
					'page_template' => 'template_empty_wide.php',
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
		$page = get_page_by_title('DATENBANK-DOKU');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'DATENBANK-DOKU',
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
		$page = get_page_by_title('REZEPTION');
		if($page == null){
			wp_insert_post(array (
					'post_title' => 'REZEPTION',
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
					'post_autor' => get_current_user_id(),
    			    'menu_order' => 909,
    			    'post_parent' => $pub_page->ID
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

function va_decrease_version ($old){
	if (substr($old, -1) == '1'){
        return (intval(substr($old, 0, -1)) - 1) . '2';
    }
    else {
        return substr($old, 0, -1) . '1';
    }
}

global $dibs_tm;
$dibs_tm = false;

function va_get_dibs_tm (){
	global $dibs_tm;
	
	if (!$dibs_tm){
		global $va_xxx;
		$dibs_tm = $va_xxx->get_var('SELECT key FROM pva_dibs.metadata WHERE value = "tm"');
	}
	
	return $dibs_tm;
}

global $stim_ext_id;
$stim_ext_id = false;

function va_get_external_id_for_stimulus ($id_stimulus){
	
	global $stim_ext_id;
	
	if (!$stim_ext_id){
		global $va_xxx;
		$old_db = $va_xxx->dbname;
		$va_xxx->select('va_xxx'); //TODO remove if the problem in va_ajax is solved
		
		$stim_ext_id = va_two_dim_to_assoc($va_xxx->get_results('SELECT Id_Stimulus, Id_Extern FROM Stimuli WHERE Id_Extern IS NOT NULL', ARRAY_N));
		$va_xxx->select($old_db);
	}
	
	if (isset($stim_ext_id[$id_stimulus])){
		return $stim_ext_id[$id_stimulus];
	}
	
	return false;
}

function va_produce_external_map_link ($atlas, $map, $num, $informant, $id_stimulus){
	$attributes = ' style="text-decoration: underline;" target="_BLANK" ';
	
	if($atlas == 'AIS'){
		if($num == '1'){
			$link = 'https://navigais-web.pd.istc.cnr.it/?map=' . $map . '&point=' . $informant;
		}
		else {
			$link = 'https://navigais-web.pd.istc.cnr.it/?map=' . $map;
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
	
	if ($atlas == 'DIBS'){
		$link = 'https://lexhelfer.dibs.badw.de/index.php?executeSearch=' . $map;
		return '<a' . $attributes . 'href="' . $link . '">Link</a>';
	}
	
	if ($atlas == 'DRG'){
		$id_ex = va_get_external_id_for_stimulus($id_stimulus);
		$link = 'http://online.drg.ch/#' . $id_ex;
		return '<a' . $attributes . 'href="' . $link . '">Link</a>';
	}
	
	return null;
}
?>