<?php
/**
Plugin Name: Map Lists
Plugin URI: 
Description: A plugin created by TechRiver specifically for savejon.org
Author: Tech River
Version: 0.0.2
Author URI: http://ma.tt/
*/



define( 'MAP_PLUGIN_PATH', plugin_dir_path( __FILE__ ) ); 
if ( !defined( 'ABSPATH' ) ) exit;

class Techriver_Map_lists {
	
	protected $version="0.0.2";
	
	protected $loader;
	
	protected $tablename;
	
	public function __construct() {
		global $wpdb;
		add_action('admin_menu',array($this,'load_views'));
		add_action('wp_enqueue_scripts',array($this,'enqueExt'));
		add_action('wp_print_styles',array($this,'enqueStyles'));
		add_shortcode('tc_maplists',array($this,'handle_tcmap_sc'));
		
		
		//Set class vars
		$this->tablename = $wpdb->prefix . 'techriver_maplists';
		
		//Load dependencies
		$this->load_dependencies();
	}	
	
	
	private function load_dependencies() {
		require_once(MAP_PLUGIN_PATH.'map_lists_install.php');
		$this->loader = new Techriver_Map_lists_install($this->version);
	}
	
	public function setupPlugin() {
		$this->loader->install();
	}
	
	public function enqueExt() {
		wp_enqueue_script('simpleModalJS',plugins_url('assets/js/simpleModalJS.js',__FILE__),array('jquery'),'1.0.0',true);
		
		//Google Maps
		wp_register_script('tc_googlemaps','http://maps.googleapis.com/maps/api/js?libraries=places',false,'3');
		wp_enqueue_script('tc_googlemaps');
		
		//local js
		wp_register_script('techriver_maplistsJS',plugins_url('assets/js/plugin.js',__FILE__,array('jquery'),'1.0.0',true));
		$resource_array = array(
		'bubble_marker' => plugins_url('assets/images/bubble-marker.png',__FILE__), // deprecated old marker.
		'bubble_marker_xsmall' => plugins_url('assets/images/bubble_marker_xsmall.png',__FILE__),
		'bubble_marker_small' => plugins_url('assets/images/bubble_marker_small.png',__FILE__),
		'bubble_marker_medium' => plugins_url('assets/images/bubble_marker_medium.png',__FILE__),
		'bubble_marker_lg' => plugins_url('assets/images/bubble_marker_lg.png',__FILE__),
		'ajax_url' => admin_url('admin-ajax.php'),__FILE__);
		
		//Get map data for to display coords
		global $wpdb;
		$sql = "SELECT * FROM {$this->tablename}";
		$results = $wpdb->get_results($sql,ARRAY_A);
		$resource_array['map_data'] = $results; // Store to localized array data
		
		
		//Enque and localize
		wp_localize_script('techriver_maplistsJS','tc_resource_obj_ml',$resource_array);
		wp_enqueue_script('techriver_maplistsJS');
		
		
		
		
		
	}
	
	public function enqueStyles() {
		wp_enqueue_style('techriver_maplistsCSS',plugins_url('assets/css/style.css',__FILE__));
	}
	
	public function updatePlugin() {
		$this->loader->update();
	}
	
	
	public function load_views() {
		add_menu_page('TC Map Lists','TC Map Lists','manage_options','tcmaplists_admin',array($this,'load_admin_view'),'dashicons-admin-site');
		add_submenu_page('tcmaplists_admin','TC Map Lists Add new','Add new','manage_options','tcmaplists_admin_add',array($this,'load_admin_view_add'));
	}
	
	
	public function load_admin_view() {
		require_once(MAP_PLUGIN_PATH.'/admin/main.php');
	}
	
	
	public function load_admin_view_add() {
		require_once(MAP_PLUGIN_PATH.'/admin/add.php');
	}
	
	public function handle_tcmap_sc() {
		$wpnonce_form = wp_nonce_field('add_location');
		$output = '<!--MODALS-->
	<div id="simplemodal-modal">
		<i class="simplemodal-close icon ion-close-round"></i>
		<div class="content">
			<h2>
				Add a new location
			</h2>
			<hr/>
			<p>
				<form id="tc_google_map_submit" method="post">
				'.$wpnonce_form.'
				<input type="hidden" name="tc_form_submit_maplists" value="1"/>
					<div class="formgroup">
						<label>Name:</label>	<input type="text" name="name" placeholder="Enter your name"/>
					</div><br/><br/>
					<div class="formgroup">
						<label>Email:</label>	<input type="email" name="email" placeholder="Enter your email"/>
					</div><br/><br/>
					<div class="formgroup">
						<label>Current Address:</label>	<input type="text" id="google-loc" name="location" class="loc" placeholder="Address (ex. 148 Elm St.)"/>
					</div>
					<div class="formgroup">
						<textarea name="desc" placeholder= "place a unique description of yourself"></textarea>
					</div><br/><br/>
					<input type="hidden" id="google-loc-lat" name="latitude" value="0"/>
					<input type="hidden" id="google-loc-long" name="longitude" value="0"/>
					<div class="formgroup">
						<input type="submit" value="Submit Data" />
						
					</div>
				</form>
			</p>
		<div style="clear:both;"></div>
		</div>
	</div>
	<!--End of Modals-->
	
<div id="googleMapContainer">
	<div id="user-controls">
		<ul class="user-controls-list" style="list-style-type:none;display:inline;">
			<li><a href="#" class="new-content-btn"><i class="icon ion-compose"></i></a></li>
			<li><a href="#" class="tc-open-sidebar" data-target="maplist"><i class="icon ion-navicon-round"></i></a></li>
		</ul>
	</div>
	<div id="googleMap" style="width:100%;height:620px;">
	
	</div>
</div>';
		return $output;
	}
	
	
	public function handle_forms_submit() {
		if(isset($_POST['tc_form_submit_maplists']) && '1' == $_POST['tc_form_submit_maplists']) {
			if(isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'],'add_location')){
				global $wpdb;
				$parse_data = array(
				'name' => mysql_real_escape_string($_POST['name']),
				'email' => mysql_real_escape_string($_POST['email']),
				'location' => mysql_real_escape_string($_POST['location']),
				'langitude' => mysql_real_escape_string($_POST['langitude']),
				'longitude' => mysql_real_escape_string($_POST['longitude']),
				'established' => 'NOW()');
				
				$wpdb->insert($this->tablename,$parse_data);
				
			}
				
			
		}
	}
	
	
	
}

if( class_exists('Techriver_Map_lists')) {
	$map_lists = new Techriver_Map_lists();
	register_activation_hook(__FILE__,array(&$map_lists,'setupPlugin'));
}

require_once(MAP_PLUGIN_PATH.'map_lists_ajax.php');