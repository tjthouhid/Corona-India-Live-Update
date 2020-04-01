<?php
/* 
Plugin Name: Corona India Live Update
Plugin URI: https://github.com/tjthouhid/Corona-India-Live-Update/
Description: This Plugin get live data from https://www.mohfw.gov.in/ and update. 
Version: 1.0.2 
Author: Tj Thouhid 
Author URI: https://tjthouhid.me/
License: GPLv2 or later 
*/
   


global $jal_db_version;
$jal_db_version = '1.0';

function corona_ilu_install() {
	global $wpdb;
	global $jal_db_version;

	$table_name = $wpdb->prefix . 'corona_ilu_info';
	
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		last_data int(100) NOT NULL,
		time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		#passengers_screened_at_airport varchar(100) DEFAULT '' NOT NULL,
		#passengers_screened_at_airport_info text NOT NULL,
		active_case varchar(100) DEFAULT '' NOT NULL,
		#confirmed_case_info text NOT NULL,
		recovered_case varchar(100) DEFAULT '' NOT NULL,
		#recovered_case_info text NOT NULL,
		death varchar(100) DEFAULT '' NOT NULL,
		#death_info text NOT NULL,
		migrated varchar(100) DEFAULT '' NOT NULL,
		total_case varchar(100) DEFAULT '' NOT NULL,
		#migrated_info text NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

	add_option( 'jal_db_version', $jal_db_version );

	wp_schedule_event( time(), '15min', 'update_corona_ilu_event' );
}
register_activation_hook( __FILE__, 'corona_ilu_install' );

function cli_my_cron_schedules($schedules){
    if(!isset($schedules["5min"])){
        $schedules["5min"] = array(
            'interval' => 5*60,
            'display' => __('Once every 5 minutes'));
    }
    if(!isset($schedules["15min"])){
        $schedules["15min"] = array(
            'interval' => 15*60,
            'display' => __('Once every 15 minutes'));
    }
    return $schedules;
}
add_filter('cron_schedules','cli_my_cron_schedules');

register_deactivation_hook( __FILE__, 'my_deactivation' ); 
function my_deactivation() {
	global $wpdb;
    $table_name = $wpdb->prefix . 'corona_ilu_info';
    $sql = "DROP TABLE IF EXISTS $table_name";
    $wpdb->query($sql);
    wp_clear_scheduled_hook( 'update_corona_ilu_event' );
}
function corona_ilu_install_data() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'corona_ilu_info';
	
	$wpdb->insert( 
		$table_name, 
		array( 
			'time' => current_time( 'mysql' ), 
			'last_data' => '101', 
			//'passengers_screened_at_airport' => '0', 
			//'passengers_screened_at_airport_info' => '0', 
			'active_case' => '0', 
			//'confirmed_case_info' => '0', 
			'recovered_case' => '0', 
			//'recovered_case_info' => '0', 
			'death' => '0', 
			//'death_info' => '0'  
			'migrated' => '0', 
			'total_case' => '0', 
			//'migrated_info' => '0' 
		) 
	);
	update_corona_ilu_info();
}

register_activation_hook( __FILE__, 'corona_ilu_install_data' );

function update_corona_ilu_info() {
	global $wpdb;
	
	$table_name = $wpdb->prefix . 'corona_ilu_info';


	$url="https://www.mohfw.gov.in/";  
	$original_file = file_get_contents("$url");

	//preg_match_all( "@<h2 class=\"case\">(.*)</h2>@siU", $original_file, $case );
	//preg_match_all( "@<span class=\"info\">(.*)</span>@siU", $original_file, $case_info );	
	$dom = new DOMDocument;

	preg_match_all( "@<li class=\"bg-blue\">(.*)</li>@siU", $original_file, $dealer_price );
	$dom->loadHTML($dealer_price[1][0]);
	$active = $dom->getElementsByTagName('strong')->item(0);

	preg_match_all( "@<li class=\"bg-green\">(.*)</li>@siU", $original_file, $dealer_price );
	$dom->loadHTML($dealer_price[1][0]);
	$cured = $dom->getElementsByTagName('strong')->item(0);
	
	preg_match_all( "@<li class=\"bg-red\">(.*)</li>@siU", $original_file, $dealer_price );
	$dom->loadHTML($dealer_price[1][0]);
	$death = $dom->getElementsByTagName('strong')->item(0);
	
	preg_match_all( "@<li class=\"bg-orange\">(.*)</li>@siU", $original_file, $dealer_price );
	$dom->loadHTML($dealer_price[1][0]);
	$migrated = $dom->getElementsByTagName('strong')->item(0);
	
	$data = array(
		'time' => current_time( 'mysql' ),
		//'passengers_screened_at_airport' => $case[1][0], 
		//'passengers_screened_at_airport_info' => '0',  
		'active_case' => $active->nodeValue, 
		//'confirmed_case_info' => $case_info[1][0], 
		'recovered_case' => $cured->nodeValue, 
		//'recovered_case_info' => $case_info[1][3], 
		'death' => $death->nodeValue, 
		'migrated' => $migrated->nodeValue, 
		//'death_info' => $case_info[1][4],
		'total_case' => ($active->nodeValue+$cured->nodeValue+$death->nodeValue+$migrated->nodeValue), 
		//'migrated_info' => '0',
	);

	$wpdb->update($table_name, $data, array('last_data'=>'101'));
}

add_action( 'update_corona_ilu_event', 'update_corona_ilu_info' );






function corona_ilu_info_shortcode( $atts ) {
   $a = shortcode_atts( array(
      'name' => 'confirmed_case'
   ), $atts );
   global $wpdb;
   $name = $a['name'];
   //update_corona_ilu_info();
   $table_name = $wpdb->prefix . 'corona_ilu_info';
   $post_data = $wpdb->get_results("SELECT $name FROM $table_name WHERE last_data = '101'");
  // echo "<pre>";
  // print_r($post_data[0]->$name);
   return $post_data[0]->$name;
}
add_shortcode( 'corona-ilu-info', 'corona_ilu_info_shortcode' );

add_action( 'admin_menu', 'corona_ilu_menu' );

function corona_ilu_menu() {
	$page_title='Corona India Live Update';
	$menu_title='Corona ILU';
	$capability=1;
	$menu_slug='corona_ilu_menu';
	$function='corona_ilu_template';
	$icon_url='';
	$position=69.9;
	// $ch_menu_slug="fit_slider_setting";
	// $ch_page_title="FIT Slider Setting";
	// $ch_menu_title="Setting";
	// $ch_function="fit_sl_setting";

	add_menu_page( $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url, $position );
	//add_submenu_page( $menu_slug, $ch_page_title, $ch_menu_title, $capability, $ch_menu_slug, $ch_function );
	//add_options_page( $page_title, $menu_title, $capability,$menu_slug,$function);
}

function corona_ilu_template(){
	global $wpdb;
	$table_name = $wpdb->prefix . 'corona_ilu_info';
	$post_data = $wpdb->get_results("SELECT * FROM $table_name WHERE last_data = '101'");
	?>
	<style type="text/css">
		.result-clb{margin-top: 60px;}
		.clb{font-size: 18px;font-weight: bold;}
		.clb_spn{font-size: 15px;font-weight: bold;color: #da1324;background-color: #fff;padding: 20px;}
		.result-clb table{background-color: #fff;width: 400px;}
		.result-clb table td{padding: 10px;}
		.attr-rs{color: #da1324;font-weight: bold;}
		.clvalue{font-size: 20px;font-weight: 700;text-align: center;color: #ffffff;background: #2eb734;}
	</style>
	<h1>Corona India Live Update</h1>
	<div class="result-clb">
		<label class="clb">Shordcode : </label>
		<span class="clb_spn"> [corona-ilu-info name='confirmed_case']</span>
	</div>
	<div class="result-clb">
		<label class="clb">Attribute 'name' Values : </label>
		<br>
		<br>
		<table border="1">
			<tr>
				<th>RESULT</th>
				<th>attribute</th>
				<th>Value</th>
			</tr>

			<tr>
				<td>Active COVID 2019 case</td>
				<td class="attr-rs">active_case</td>
				<td class="clvalue"><?php echo $post_data[0]->active_case;?></td>
			</tr>
			<tr>
				<td>Cured/discharged cases</td>
				<td class="attr-rs">recovered_case</td>
				<td class="clvalue"><?php echo $post_data[0]->recovered_case;?></td>
			</tr>
			<tr>
				<td>Death cases</td>
				<td class="attr-rs">death</td>
				<td class="clvalue"><?php echo $post_data[0]->death;?></td>
			</tr>
			<tr>
				<td>Migrated COVID-19 Patient</td>
				<td class="attr-rs">migrated</td>
				<td class="clvalue"><?php echo $post_data[0]->migrated;?></td>
			</tr>
			<tr>
				<td>Total Case</td>
				<td class="attr-rs">total_case</td>
				<td class="clvalue"><?php echo $post_data[0]->total_case;?></td>
			</tr>
		</table>
	</div>
	
	<?php 

}