<?php
/*
* FILEORGANIZER
* https://fileorganizer.net/
* (c) FileOrganizer Team
*/

//ABSPATH is required.	
if(!defined('ABSPATH')) exit;

define('FILEORGANIZER_DIR', dirname( FILEORGANIZER_FILE ));
define('FILEORGANIZER_PRO_DIR', FILEORGANIZER_DIR .'/main/premium');
define('FILEORGANIZER_BASE', plugin_basename(FILEORGANIZER_FILE));
define('FILEORGANIZER_PRO_BASE', 'fileorganizer-pro/fileorganizer-pro.php');
define('FILEORGANIZER_URL', plugins_url('', FILEORGANIZER_FILE));
define('FILEORGANIZER_BASE_NAME', basename(FILEORGANIZER_DIR));
define('FILEORGANIZER_VERSION', '1.0.1');
define('FILEORGANIZER_WP_CONTENT_DIR', defined('WP_CONTENT_FOLDERNAME') ? WP_CONTENT_FOLDERNAME : 'wp-content');
define('FILEORGANIZER_DEV', file_exists(dirname(__FILE__).'/dev.php') ? 1 : 0);
define('FILEORGANIZER_API', 'https://api.fileorganizer.net/');

function fileorganizer_died(){
	print_r(error_get_last());
}

if(FILEORGANIZER_DEV){
	include_once FILEORGANIZER_DIR.'/DEV.php';
	register_shutdown_function('fileorganizer_died');
}

class FileOrganizer{
	public $options = array();
}

function fileorganizer_autoloader($class){
	
	if(!preg_match('/^FileOrganizer\\\(.*)/is', $class, $m)){
		return;
	}
	
	// For Free
	if(file_exists(FILEORGANIZER_DIR.'/main/'.strtolower($m[1]).'.php')){
		include_once(FILEORGANIZER_DIR.'/main/'.strtolower($m[1]).'.php');
	}
	
	// For Pro
	if(file_exists(FILEORGANIZER_PRO_DIR.'/'.strtolower($m[1]).'.php')){
		include_once(FILEORGANIZER_PRO_DIR.'/'.strtolower($m[1]).'.php');
	}
}

spl_autoload_register(__NAMESPACE__.'\fileorganizer_autoloader');

// Ok so we are now ready to go
register_activation_hook( FILEORGANIZER_FILE , 'fileorganizer_activation');

// Is called when the ADMIN enables the plugin
function fileorganizer_activation(){
	global $wpdb;

	$sql = array();

	add_option('fileorganizer_version', FILEORGANIZER_VERSION);

}

// Looks if FileOrganizer just got updated
function fileorganizer_update_check(){
	
	$sql = array();
	$current_version = get_option('fileorganizer_version');	
	$version = (int) str_replace('.', '', $current_version);
	
	// No update required
	if($current_version == FILEORGANIZER_VERSION){
		return true;
	}
	
	// Is it first run ?
	if(empty($current_version)){
		
		// Reinstall
		fileorganizer_activation();

		// Trick the following if conditions to not run
		$version = (int) str_replace('.', '', FILEORGANIZER_VERSION);
		
	}
	
	// Save the new Version
	update_option('fileorganizer_version', FILEORGANIZER_VERSION);
	
}

// Add action to load FileOrganizer
add_action('plugin_loaded', 'fileorganizer_load_plugin');

// Load ajax
if(wp_doing_ajax()){
	include_once FILEORGANIZER_DIR . '/main/ajax.php';
}

function fileorganizer_load_plugin(){
	global $fileorganizer;
	
	if(empty($fileorganizer)){
		$fileorganizer = new FileOrganizer();
	}
	
	$options = get_option('fileorganizer_options');
	$fileorganizer->options = empty($options) ? array() : $options;
	$fileorganizer->license = array();
	
	fileorganizer_update_check();

	if(!is_admin()){

	}
}

// This adds the left menu in WordPress Admin page
add_action('admin_menu', 'fileorganizer_admin_menu', 5);
function fileorganizer_admin_menu() {

	global $wp_version;

	$capability = 'activate_plugins';// TODO : Capability for accessing this page

	// Add the menu page
	add_menu_page(__('FILE ORGANIZER'), __('File Organizer'), $capability, 'fileorganizer', 'fileorganizer_page_handler', 'dashicons-category');
	
}

function fileorganizer_page_handler(){
	
	// Register scripts
	wp_register_script('forg-elfinder', FILEORGANIZER_URL .'/manager/js/elfinder.min.js', array('jquery', 'jquery-ui-droppable', 'jquery-ui-resizable', 'jquery-ui-selectable', 'jquery-ui-slider', 'jquery-ui-button', 'jquery-ui-sortable'), FILEORGANIZER_VERSION);
	
	// Register styles
	wp_register_style('forg-admin', FILEORGANIZER_URL .'/css/admin.css', array(), FILEORGANIZER_VERSION);
	wp_register_style('forg-jquery-ui', FILEORGANIZER_URL .'/css/jquery-ui/jquery-ui.css', array(), FILEORGANIZER_VERSION);
	wp_register_style('forg-theme', FILEORGANIZER_URL .'/manager/css/theme.css', array(), FILEORGANIZER_VERSION);
	wp_register_style('forg-elfinder', FILEORGANIZER_URL .'/manager/css/elfinder.min.css', array('forg-admin', 'forg-jquery-ui', 'forg-theme'), FILEORGANIZER_VERSION);
	
	// Include the handler
	include_once (FILEORGANIZER_DIR .'/main/fileorganizer.php');
	
	// Render
	fileorganizer_render_page();
}