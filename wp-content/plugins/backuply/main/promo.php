<?php

if(!defined('ABSPATH')){
	die();
}

echo '
<style>
.backuply_button {
background-color: #4CAF50; /* Green */
border: none;
color: white;
padding: 8px 16px;
text-align: center;
text-decoration: none;
display: inline-block;
font-size: 16px;
margin: 4px 2px;
-webkit-transition-duration: 0.4s; /* Safari */
transition-duration: 0.4s;
cursor: pointer;
}

.backuply_button:focus{
border: none;
color: white;
}

.backuply_button1 {
color: white;
background-color: #4CAF50;
border:3px solid #4CAF50;
}

.backuply_button1:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
border:3px solid #4CAF50;
}

.backuply_button2 {
color: white;
background-color: #0085ba;
}

.backuply_button2:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
}

.backuply_button3 {
color: white;
background-color: #365899;
}

.backuply_button3:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
}

.backuply_button4 {
color: white;
background-color: rgb(66, 184, 221);
}

.backuply_button4:hover {
box-shadow: 0 6px 8px 0 rgba(0,0,0,0.24), 0 9px 25px 0 rgba(0,0,0,0.19);
color: white;
}

.backuply_promo-close{
float:right;
text-decoration:none;
margin: 5px 10px 0px 0px;
}

.backuply_promo-close:hover{
color: red;
}

#backuply_promo li {
list-style-position: inside;
list-style-type: circle;
}

.backuply-loc-types {
display:flex;
flex-direction: row;
align-items:center;
flex-wrap: wrap;
}

.backuply-loc-types li{
list-style-type:none !important;
margin-right: 10px;
}

</style>

<script>
jQuery(document).ready( function() {
	(function($) {
		$("#backuply_promo .backuply_promo-close").click(function(){
			var data;
			
			// Hide it
			$("#backuply_promo").hide();
			
			// Save this preference
			$.post("'.admin_url('?backuply_promo=0').'&security='.wp_create_nonce('backuply_nonce').'", data, function(response) {
				//alert(response);
			});
		});
		
		$("#backuply_holiday_promo .backuply_promo-close").click(function(){
			var data;
			
			// Hide it
			$("#backuply_holiday_promo").hide();
			
			// Save this preference
			$.post("'.admin_url('?backuply_holiday_promo=0').'&security='.wp_create_nonce('backuply_nonce').'", data, function(response) {
				//alert(response);
			});
		});
	})(jQuery);
});
</script>';

function backuply_base_promo(){
	echo '<div class="notice notice-success" id="backuply_promo" style="min-height:120px; background-color:#FFF; padding: 10px;">
	<a class="backuply_promo-close" href="javascript:" aria-label="Dismiss this Notice">
		<span class="dashicons dashicons-dismiss"></span> Dismiss
	</a>
	<table>
	<tr>
		<th>
			<img src="'.BACKUPLY_URL.'/assets/images/backuply-square.png" style="float:left; margin:10px 20px 10px 10px" width="100" />
		</th>
		<td>
			<p style="font-size:16px;">You have been using Backuply for few days and we hope we were able to add some value through Backuply.
			</p>
			<p style="font-size:16px">
			If you like our plugin would you please show some love by doing actions like
			</p>
			<p>
				<a class="backuply_button backuply_button1" target="_blank" href="https://backuply.com/pricing">Upgrade to Pro</a>
				<a class="backuply_button backuply_button2" target="_blank" href="https://wordpress.org/support/view/plugin-reviews/backuply">Rate it 5★\'s</a>
				<a class="backuply_button backuply_button3" target="_blank" href="https://www.facebook.com/backuply/">Like Us on Facebook</a>
				<a class="backuply_button backuply_button4" target="_blank" href="https://twitter.com/intent/tweet?text='.rawurlencode('I use @wpbackuply to backup my #WordPress site - https://backuply.com').'">Tweet about Backuply</a>
			</p>
	</td>
	</tr>
	</table>
</div>';
}

function backuply_holiday_offers(){

	$time = date('nj');

	if($time == 1225 || $time == 1224){
		backuply_christmas_offer();
	}
	
	if($time == 11){
		backuply_newyear_offer();
	}
}

function backuply_christmas_offer(){
	echo '<div class="notice notice-success" id="backuply_holiday_promo" style="min-height:120px; background-color:#FFF; padding: 10px;">
	<a class="backuply_promo-close" href="javascript:" aria-label="Dismiss this Notice">
		<span class="dashicons dashicons-dismiss"></span> Dismiss
	</a>
	<table>
	<tr>
		<th>
			<img src="'.BACKUPLY_URL.'/assets/images/25off.png" style="float:left; margin:10px 20px 10px 10px" width="100" />
		</th>
		<td><h2>Backuply Wishes you Merry Christmas 🎄</h2>
	<p style="font-size:16px">We are offering 25% off on every Backuply Plan today, so upgrade to Backuply Pro now and forget the need to create backups manully with Backuply\'s Auto Backups.</p>
	<a class="backuply_button backuply_button1" target="_blank" href="https://backuply.com/pricing">Upgrade to Pro</a>
	</td>
	</tr>
	</table>
</div>';
}

function backuply_newyear_offer(){
	echo '<div class="notice notice-success" id="backuply_holiday_promo" style="min-height:120px; background-color:#FFF; padding: 10px;">
	<a class="backuply_promo-close" href="javascript:" aria-label="Dismiss this Notice">
		<span class="dashicons dashicons-dismiss"></span> Dismiss
	</a>
	<table>
	<tr>
		<th>
			<img src="'.BACKUPLY_URL.'/assets/images/25off.png" style="float:left; margin:10px 20px 10px 10px" width="100" />
		</th>
		<td><h2>Backuply Wishes you a Happy New Year 🎉</h2>
	<p style="font-size:16px">We are offering 25% off on every Backuply Plan today, so upgrade to Backuply Pro now and forget the need to create backups manully with Backuply\'s Auto Backups.</p>
	<a class="backuply_button backuply_button1" target="_blank" href="https://backuply.com/pricing">Upgrade to Pro</a>
	</td>
	</tr>
	</table>
</div>';
}