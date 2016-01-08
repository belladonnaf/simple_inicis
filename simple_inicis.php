<?php
/*
* Plugin Name: Simple Inicis Payment ShortCode
* Description: Shortcode 로 짤라붙이는 Inicis 결제
* Version: 1.0
* Author: Wper.kr
* Author URI: http://wper.kr
*/

define( 'WIP_VERSION', 	'1.0' );					// plugin version
define( 'WIP_FILE', 		__FILE__ );											// plugin's main file path
define( 'WIP_DIR', 			dirname( plugin_basename( WIP_FILE ) ) );			// plugin's directory
define( 'WIP_PATH',			untrailingslashit( plugin_dir_path( WIP_FILE ) ) );	// plugin's directory path
define( 'WIP_URL', 			untrailingslashit( plugin_dir_url( WIP_FILE ) ) );	// plugin's directory URL

// Directories
define( 'WIP_INC_DIR',		'includes' );	// includes directory
define( 'WIP_CSS_DIR', 		'css' );		// stylesheets directory
define( 'WIP_LANG_DIR', 	'languages' );	// languages directory

function wper_inicis_shortcode($atts,$content = null){

  global $wpdb, $wper_inicis_fired;

	if($atts['woocommerce_product_id'] && ( !$atts['item_name'] ) ){
		$sql = " select post_title from ".$wpdb->prefix."posts where ID = ".$atts['woocommerce_product_id']; 
		$atts['item_name'] = $wpdb->get_var($sql);
	}

	if($atts['woocommerce_product_id'] && ( !$atts['item_amount'] ) ){
		$sql = " select meta_value from ".$wpdb->prefix."postmeta where post_id = ".$atts['woocommerce_product_id']. " and meta_key = '_price' "; 
		$atts['item_amount'] = $wpdb->get_var($sql);
	}

	$cu = wp_get_current_user();
	$def_btn = get_option('wper_inicis_option_1');
	$mid = get_option('wper_inicis_option_3');
	$signKey = get_option('wper_inicis_option_4');
	$usermeta_tel_field = get_option('wper_inicis_option_5');
	$buddypress_tel_field = get_option('wper_inicis_option_6');
	
	if( $cu->exists() ){
		$buyername = $cu->display_name;
		$buyeremail = $cu->user_email;

		if($buddypress_tel_field){
			$sql = " select value from ".$wpdb->prefix."bp_xprofile_data where user_id = ".$cu->ID." and field_id = ".$buddypress_tel_field;
			$buyertel = $wpdb->get_var($sql);
		}			
		
		if(!$buyertel && $usermeta_tel_field ){
			$sql = " select meta_value from ".$wpdb->prefix."usermeta where user_id = ".$cu->ID." and meta_key = '".$usermeta_tel_field."'";
			$buyertel = $wpdb->get_var($sql);
		}
		
	}

  extract(shortcode_atts(array(
      'woocommerce_product_id' => '',
      'item_name' => '',
      'item_amount' => ''
  ), $atts));

   if(empty($content)) {
       $content='<input type="button" class="payment_button" value="'.$def_btn.'" onclick="call_payment(jQuery(this));">';
   } else {
       $content='<input type="button" class="payment_button" value="'.$content.'" onclick="call_payment(jQuery(this));">';
   }
	

	if(!$wper_inicis_fired){
	
$wper_inicis_js_func = <<< EOF
<link rel="stylesheet" id="wper-inicis-css" href="/wp-content/plugins/simple_inicis/css/front.css" type="text/css" media="all" />
<script>	
	function call_payment(t){
	
		var tid = t.parent().parent().attr("data-id");
		
		console.log(tid);
		var item_name = jQuery("input[id=item_name_" + tid + "]").val();
		console.log(item_name);
		var item_amount = jQuery("input[id=item_amount_" + tid + "]").val();
		var buyername = jQuery("input[id=buyername_" + tid + "]").val();
		var buyertel = jQuery("input[id=buyertel_" + tid + "]").val();
		var buyeremail = jQuery("input[id=buyeremail_" + tid + "]").val();
	  var mid = '$mid';
	  var signKey = '$signKey';

    jQuery.ajax({
        type: 'POST',
        url: '/wp-content/plugins/simple_inicis/INIStdPaySample/INIStdPayRequest.php',
        data: {
            item_amount: item_amount,
            item_name:item_name,
            buyername:buyername,
            buyertel:buyertel,
            buyeremail:buyeremail,
            mid:mid,
            signKey:signKey
        },
        success: function(data) {
					console.log(".wper-inicis-payment[data-id=" + tid + "] .inicis_button_wrapper");
					jQuery(".wper-inicis-payment[data-id=" + tid + "] .inicis_button_wrapper").html(data);
        }
    });
	
	}

</script>

EOF;

} else {
	$wper_inicis_js_func = '';
}

$wper_inicis_fired = 1;

$mt_rand = mt_rand(10000,99999);

$ret = <<< EOF
$wper_inicis_js_func
<div class="wper-inicis-payment" data-id="$mt_rand">
			<input type="hidden" id="item_name_$mt_rand" name="item_name" value="$item_name">
			<input type="hidden" id="item_amount_$mt_rand" name="item_amount" value="$item_amount">
			<input type="hidden" id="buyername_$mt_rand" name="buyername" value="$buyername">
			<input type="hidden" id="buyertel_$mt_rand" name="buyertel" value="$buyertel">
			<input type="hidden" id="buyeremail_$mt_rand" name="buyeremail" value="$buyeremail">
			<div class="inicis_button_wrapper">
			$content
			</div>
</div>
	
EOF;

return $ret;

}

$domain = 'simple-inicis';

$external_mofile = WP_LANG_DIR . '/plugins/'. $domain . '-' . $locale . '.mo';
if ( get_bloginfo( 'version' ) <= 3.6 && file_exists( $external_mofile ) ) { // external translation exists
	load_textdomain( $domain, $external_mofile );
} else {
	$languages_dir = WIP_DIR . '/' . trailingslashit( WIP_LANG_DIR ); // ensure trailing slash
	load_plugin_textdomain( $domain, false, $languages_dir );
}

function wper_inicis_setup_menu(){
	add_submenu_page('woocommerce','WPER-Inicis','WPER-Inicis','manage_options','wper-inicis-payment','wper_inicis_setup_option');
}

add_action( 'wp_ajax_set_simple_inicis', 'set_simple_inicis' );

function set_simple_inicis() {

	global $wpdb;
	
	$option_1 = $_POST['option_1'];
	$option_2 = $_POST['option_2'];
	$option_3 = $_POST['option_3'];
	$option_4 = $_POST['option_4'];
	$option_5 = $_POST['option_5'];
	$option_6 = $_POST['option_6'];
	
	update_option('wper_inicis_option_1',$option_1);
	update_option('wper_inicis_option_2',$option_2);
	update_option('wper_inicis_option_3',$option_3);
	update_option('wper_inicis_option_4',$option_4);
	update_option('wper_inicis_option_5',$option_5);
	update_option('wper_inicis_option_6',$option_6);
	
	$arr_option = array('wper_inicis_option_1'=>$option_1
										, 'wper_inicis_option_2'=>$option_2
										, 'wper_inicis_option_3'=>$option_3
										, 'wper_inicis_option_4'=>$option_4
										, 'wper_inicis_option_5'=>$option_5
										, 'wper_inicis_option_6'=>$option_6
	);

	echo json_encode($arr_option);
	wp_die();
}

function wper_inicis_setup_option(){

global $wpdb;

$wper_inicis_option_1 = get_option('wper_inicis_option_1');
$wper_inicis_option_2 = get_option('wper_inicis_option_2');
$wper_inicis_option_3 = get_option('wper_inicis_option_3');
$wper_inicis_option_4 = get_option('wper_inicis_option_4');
$wper_inicis_option_5 = get_option('wper_inicis_option_5');
$wper_inicis_option_6 = get_option('wper_inicis_option_6');

if(!$wper_inicis_option_1){
	$wper_inicis_option_1 = __('Prepare Check','simple-inicis');
}

if(!$wper_inicis_option_2){
	$wper_inicis_option_2 = __('Checkout','simple-inicis');
}

?>
<script type="text/javascript" >

	function save_setting(){

		var data = {
			'action': 'set_simple_inicis',
			'option_1': jQuery("input[name=wper_inicis_option_1]").val(),
			'option_2': jQuery("input[name=wper_inicis_option_2]").val(),
			'option_3': jQuery("input[name=wper_inicis_option_3]").val(),
			'option_4': jQuery("input[name=wper_inicis_option_4]").val(),
			'option_5': jQuery("select[name=wper_inicis_option_5]").val(),
			'option_6': jQuery("select[name=wper_inicis_option_6]").val(),
		};
	
		jQuery.ajax({
		   type: "POST",
		   url: ajaxurl,
		   data: data,
   	   async:false,
		   success: function(data){

				console.log(data);
				alert('<?php echo __('Update Complete','simple-inicis'); ?>');

		 	},
		 	cache: false,
		 	dataType:"json"
	  });
  
	
	}
	
	jQuery(document).ready(function(){
	
		jQuery("select[name=wper_inicis_option_5]").val("<?php echo $wper_inicis_option_5; ?>");
		jQuery("select[name=wper_inicis_option_6]").val("<?php echo $wper_inicis_option_6; ?>");
	
	});
	
</script>
<?

$sql = " select meta_key from ".$wpdb->prefix."usermeta group by meta_key order by meta_key asc ";
$arr_usermeta = $wpdb->get_col($sql);

$str1 = '';
foreach($arr_usermeta as $um){
	$str1 .= '<option value="'.$um.'">'.$um.'</option>';
}

$str2 = '';

$sql = " select id,name from ".$wpdb->prefix."bp_xprofile_fields order by id asc ";
$arr_buddy_profile = $wpdb->get_results($sql,ARRAY_A);

foreach($arr_buddy_profile as $bp){
	$str2 .= '<option value="'.$bp['id'].'">'.$bp['name'].'</option>';
}

echo '
<link rel="stylesheet" id="wper-inicis-css" href="/wp-content/plugins/simple_inicis/css/admin.css" type="text/css" media="all" />
<div class="wper_form_wrapper">
	<div class="option_list">
		<div class="option_1">
			<div class="option_label">'.__('Default Payment Button Value','simple-inicis').'
			</div>
			<div class="option_value"><input type="text" name="wper_inicis_option_1" value="'.$wper_inicis_option_1.'">
			</div>
		</div>	
		<div class="option_2">
			<div class="option_label">'.__('Default Pay-Confirm Button Value','simple-inicis').'
			</div>
			<div class="option_value"><input type="text" name="wper_inicis_option_2" value="'.$wper_inicis_option_2.'">
			</div>
		</div>	
		<div class="option_3">
			<div class="option_label">'.__('MID Value','simple-inicis').'
			</div>
			<div class="option_value"><input type="text" name="wper_inicis_option_3" value="'.$wper_inicis_option_3.'">
			</div>
		</div>	
		<div class="option_4">
			<div class="option_label">'.__('signKey Value','simple-inicis').'
			</div>
			<div class="option_value"><input type="text" name="wper_inicis_option_4" value="'.$wper_inicis_option_4.'">
			</div>
		</div>
		<div class="option_5">
			<div class="option_label">'.__('Choose Telephone Field from usermeta','simple-inicis').'
			</div>
			<div class="option_value"><select name="wper_inicis_option_5"><option value="">'.__('N/A','simple-inicis').'</option>'.$str1.'</select>
			</div>
			<div class="desc">'.__('Inicis need Telephone. You can use this plugin','simple-inicis').' <a href="https://wordpress.org/plugins/user-meta-manager/">User meta manager</a> <a href="http://wper.kr/wp-content/uploads/2015/12/how_to_add_tel.png">'.__('Screenshot','simple-inicis').'.</a></div>
		</div>
		<div class="option_6">
			<div class="option_label">'.__('Choose Telephone Field from Buddypress','simple-inicis').'
			</div>
			<div class="option_value"><select name="wper_inicis_option_6"><option value="">'.__('N/A','simple-inicis').'</option>'.$str2.'</select>
			</div>
			<div class="desc">'.__('This plugin support Buddypress. If Buddypress is Active, this field will be override.','simple-inicis').' <a href="https://wordpress.org/plugins/buddypress/">BuddyPress</a></div>
		</div>
		<div class="shortcode_example">
			<div class="example_title">'.__('Example','simple-inicis').'</div>
			<textarea name="example" cols="80" rows="10">
'.__('Simple','simple-inicis').' 
[wper_inicis woocommerce_product_id="3741"]	

'.__('Custom Text','simple-inicis').' 
[wper_inicis woocommerce_product_id="3741"]'.__('Custom Text','simple-inicis').'[/wper_inicis]	

'.__('Payment Test','simple-inicis').' 
[wper_inicis woocommerce_product_id="3741" item_name="Test" item_amount="1000"]'.__('Payment Test','simple-inicis').'[/wper_inicis]	
			</textarea>
		</div>
		<div class="option_save">
			<div class="save_btn"><input type="button" value="'.__('Save','simple-inicis').'" onclick="save_setting();"></div>
		</div>	
	</div>
</div>';

}

add_shortcode('wper_inicis', 'wper_inicis_shortcode');

add_action('admin_menu','wper_inicis_setup_menu');

?>