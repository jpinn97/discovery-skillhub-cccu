<?php
/*
* FILEORGANIZER
* https://fileorganizer.net/
* (c) FileOrganizer Team
*/

if(!defined('FILEORGANIZER_VERSION')){
	die('Hacking Attempt!');
}
// The fileorganizer Header
function fileorganizer_page_header($title = 'FILE ORGANIZER'){
	
	wp_enqueue_style('forg-elfinder');
	wp_enqueue_script('forg-elfinder');
	
	echo '
<div class="fileorganizer-box-container" style="margin:0">
	<h2>
		<table cellpadding="2" cellspacing="1" width="100%" class="fixed" border="0">
			<tr>
				<td valign="top">
					<h1>'.esc_html($title).'</h1>
				</td>
			</tr>
		</table>
	</h2>
	<hr/>';

}
// The fileorganizer Settings footer
function fileorganizer_page_footer($no_twitter = 0){
	
	echo '
		<br />
		<a href="https://fileorganizer.net" target="_blank">FILE ORGANIZER</a><span> v'.FILEORGANIZER_VERSION.' You can report any bugs </span><a href="http://wordpress.org/support/plugin/fileorganizer" target="_blank">here</a>.
	</div>';
	
}

function fileorganizer_render_page(){
	echo '<div class="wrap">';

	fileorganizer_page_header();

	echo '<div id="fileorganizer_elfinder"></div>';

	fileorganizer_page_footer();

	echo '</div>
	<script>
		
		var fileorganizer_ajaxurl = "'.admin_url( 'admin-ajax.php' ).'";
		var fileorganizer_ajax_nonce = "'. wp_create_nonce( 'fileorganizer_ajax' ) .'";
		var fileorganizer_url = "'. FILEORGANIZER_URL .'/manager/";
		
		jQuery(document).ready(function() {

		jQuery("#fileorganizer_elfinder").elfinder({
			url: fileorganizer_ajaxurl,
			customData: {
				action: "fileorganizer_file_folder_manager",
				fileorganizer_nonce: fileorganizer_ajax_nonce
			},
			height: 500,
			baseUrl: fileorganizer_url,
			}).elfinder("instance");
		});
	</script>';
}