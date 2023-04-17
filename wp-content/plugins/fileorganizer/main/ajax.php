<?php
/*
* FILEORGANIZER
* https://fileorganizer.net/
* (c) FileOrganizer Team
*/

if(!defined('FILEORGANIZER_VERSION')){
	die('Hacking Attempt!');
}

add_action('wp_ajax_fileorganizer_file_folder_manager', 'fileorganizer_ajax_handler');
function fileorganizer_ajax_handler(){

	check_admin_referer( 'fileorganizer_ajax' , 'fileorganizer_nonce' );
	
	if(!current_user_can('activate_plugins')){
		return;
	}

	$path = ABSPATH;
	$url = site_url();

	if(is_multisite()){
		$url = network_home_url();
	}
	
	$config = array(
		'debug' => false,
		'roots' => array(
			array(
				'driver' => 'LocalFileSystem',
				'path' => $path,
				'URL' => $url,
				'winHashFix' => DIRECTORY_SEPARATOR !== '/',
				'accessControl' => 'access',
				'acceptedName' => 'validName',
				'disabled' => array('help', 'preference','hide','netmount'),
			)
		),
	);

	require FILEORGANIZER_DIR.'/manager/php/autoload.php';

	// run elFinder
	$connector = new elFinderConnector(new elFinder($config));
	$connector->run();
	
}