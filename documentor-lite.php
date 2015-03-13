<?php /*********************************************************
Plugin Name: Documentor Lite
Plugin URI: http://documentor.in/
Description: Best plugin to create online documentation or product guide on WordPress.
Version: 1.0
Author: WebFanzine Media
Author URI: http://www.webfanzine.com/
Wordpress version supported: 3.6 and above
*----------------------------------------------------------------*
* Copyright 2015  WebFanzine Media  (email : support@documentor.in)
* Developers: (Sampada, Tejaswini) WebFanzine Media
* Tested by: (Sagar, Sanjeev) WebFanzine Media
*****************************************************************/
class DocumentorLite{
	var $documentor;
	public $default_documentor_settings;
	public $documentor_global_options;
	function __construct()
	{
		$this->_define_constants();
		$this->default_documentor_settings = array(
			'skin' => 'default',
			'animation' => '',
			'indexformat'=> 1,
			'navmenu_default' => 1,
			'navt_font' =>'regular',
			'navmenu_tfont' => 'Arial,Helvetica,sans-serif',
			'navmenu_tfontg' => '',
			'navmenu_tfontgw' => '',
			'navmenu_tfontgsubset' => '',
			'navmenu_custom' => '',
			'navmenu_color' => '#000',
			'navmenu_fsize' => 14,
			'navmenu_fstyle' => 'normal',
			'actnavbg_default' => 1,
			'actnavbg_color' =>'#cccccc',
			'section_element' => '3',
			'sectitle_default' => 1,
			'sect_font' => 'regular',
			'sectitle_color' => '#000',
			'sectitle_font' => 'Helvetica,Arial,sans-serif',
			'sectitle_fontg' => '',
			'sectitle_fontgw' => '',
			'sectitle_fontgsubset' => '',
			'sectitle_custom' => '',
			'sectitle_fsize' => 28,
			'sectitle_fstyle' => 'normal',
			'seccont_default' => 1,
			'seccont_color' => '#000',
			'secc_font' => 'regular',
			'seccont_font' => 'Arial,Helvetica,sans-serif',
			'seccont_fontg' => '',
			'seccont_fontgw' => '',
			'seccont_fontgsubset' => '',
			'seccont_custom' => '',
			'seccont_fsize' => 14,
			'seccont_fstyle' => 'normal',
			'guide' => array()
		);
		$this->documentor_global_options = array( 'custom_post' => 1 );
		$this->_register_hooks();
		$this->include_files();
		$this->create_custom_post();
	}
	// Create Text Domain For Translations
	
	function _define_constants()
	{
		if ( ! defined( 'DOCUMENTORLITE_TABLE' ) ) define('DOCUMENTORLITE_TABLE','documentor'); //Documentor TABLE NAME
		if ( ! defined( 'DOCUMENTORLITE_SECTIONS' ) ) define('DOCUMENTORLITE_SECTIONS','documentor_sections'); //sections TABLE NAME
		if ( ! defined( 'DOCUMENTORLITE_VER' ) ) define("DOCUMENTORLITE_VER","1.0",false);//Current Version of Documentor
		if ( ! defined( 'DOCUMENTORLITE_PLUGIN_BASENAME' ) )
			define( 'DOCUMENTORLITE_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		if ( ! defined( 'DOCUMENTORLITE_CSS_DIR' ) )
			define( 'DOCUMENTORLITE_CSS_DIR', WP_PLUGIN_DIR.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)).'/skins/' );
	}
	function _register_hooks()
	{
		add_action('plugins_loaded', array(&$this, 'documentor_update_db_check'));
		load_plugin_textdomain('documentorlite', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
		if (!shortcode_exists( 'documentor' ) ) add_shortcode('documentor', array(&$this,'shortcode'));
	}
	function install_documentor() {
		global $wpdb, $table_prefix;
		$documentorlite_db_version = DOCUMENTORLITE_VER;
		$installed_ver = get_site_option( "documentorlite_db_version" );
		if( $installed_ver != $documentorlite_db_version ) {
			$table_name = $table_prefix.DOCUMENTORLITE_TABLE;
			if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
				$sql = "CREATE TABLE $table_name (
							doc_id int(5) NOT NULL AUTO_INCREMENT,
							doc_title varchar(50) NOT NULL,
							settings varchar(4000) NOT NULL DEFAULT '',
							sections_order varchar(2000) NOT NULL DEFAULT '',
							rel_id bigint(20),
							rel_title varchar(50) NOT NULL DEFAULT 'Relevant Links',
							UNIQUE KEY doc_id(doc_id)
						);";
				$rs = $wpdb->query($sql);
				$settings = json_encode( $this->default_documentor_settings );
				$wpdb->insert( 
						$table_name, 
						array(
							'doc_id' => 1,
							'doc_title' => 'Documentor Guide',
							'settings'	=> $settings
						), 
						array( 
							'%d',
							'%s', 
							'%s'
						) 
					);
			}
		
			$table_name = $table_prefix.DOCUMENTORLITE_SECTIONS;
			if($wpdb->get_var("show tables like '$table_name'") != $table_name) {
				$sql = "CREATE TABLE $table_name (
							sec_id int(5) NOT NULL AUTO_INCREMENT,
							doc_id int(5) NOT NULL,
							post_id bigint(20) NOT NULL,
							type varchar(50) NOT NULL,
							upvote int(5) NOT NULL,
							downvote int(5) NOT NULL,
							UNIQUE KEY sec_id(sec_id)
						);";
				$rs = $wpdb->query($sql);
			}
			update_option( "documentorlite_db_version", $documentorlite_db_version );
			//global setting
			$global_settings = $this->documentor_global_options;
			$global_settings_curr = get_option('documentor_global_options');
			if( !$global_settings_curr ) {
				$global_settings_curr = array();
			}
			foreach($global_settings as $key=>$value) {
				if(!isset($global_settings_curr[$key])) {
					$global_settings_curr[$key] = $value;
				}
			}
			update_option('documentor_global_options',$global_settings_curr);
		}//end of if db version chnage
	}

	function shortcode( $atts ) {
		$doc_id = isset($atts[0])?$atts[0]:'';
		$id = intVal($doc_id);
		$guide = new DocumentorLiteGuide( $id );
		$html = $guide->view();
		return $html;
	}
	
	function include_files() { 
		require_once (dirname (__FILE__) . '/core/includes/class.documentorLiteFonts.php');
		require_once (dirname (__FILE__) . '/core/class.DocumentorLiteAdmin.php');
		require_once (dirname (__FILE__) . '/core/class.documentorLiteGuide.php');
		require_once (dirname (__FILE__) . '/core/class.documentorLiteSection.php');
		require_once (dirname (__FILE__) . '/core/class.documentorLiteAjax.php');
	}
	
	function documentor_plugin_url( $path = '' ) {
		global $wp_version;
		if ( version_compare( $wp_version, '2.8', '<' ) ) { // Using WordPress 2.7
			$folder = dirname( plugin_basename( __FILE__ ) );
			if ( '.' != $folder )
				$path = path_join( ltrim( $folder, '/' ), $path );

				return plugins_url( $path );
			}
		return plugins_url( $path, __FILE__ );
	}

	function documentor_admin_url( $query = array() ) {
		global $plugin_page;

		if ( ! isset( $query['page'] ) )
			$query['page'] = $plugin_page;

		$path = 'admin.php';

		if ( $query = build_query( $query ) )
			$path .= '?' . $query;

		$url = admin_url( $path );

		return esc_url_raw( $url );
	}
	/* Added for auto update - start */
	function documentor_update_db_check() {
		$documentorlite_db_version = DOCUMENTORLITE_VER;
		if (get_site_option('documentorlite_db_version') != $documentorlite_db_version) {
			$this->install_documentor();
		}
	}
	function create_custom_post() {
		//New Custom Post Type
		$global_settings_curr = get_option('documentor_global_options');
		if( isset( $global_settings_curr['custom_post'] ) && $global_settings_curr['custom_post'] == '1' && !post_type_exists('documentor-sections') ){
			add_action( 'init', array( &$this, 'section_post_type'), 11 );
			//add filter to ensure the text Sections, or Section, is displayed when user updates a Section 
			add_filter('post_updated_messages', array( &$this, 'section_updated_messages') );
		} //if custom_post is true
	}
	function section_post_type() {
		$labels = array(
		'name' => _x('Sections', 'post type general name'),
		'singular_name' => _x('Section', 'post type singular name'),
		'add_new' => _x('Add New', 'documentor'),
		'add_new_item' => __('Add New Documentor Section'),
		'edit_item' => __('Edit Documentor Section'),
		'new_item' => __('New Documentor Section'),
		'all_items' => __('All Documentor Sections'),
		'view_item' => __('View Documentor Section'),
		'search_items' => __('Search Documentor Sections'),
		'not_found' =>  __('No Documentor sections found'),
		'not_found_in_trash' => __('No Documentor section found in Trash'), 
		'parent_item_colon' => '',
		'menu_name' => 'Sections'
		);
		$args = array(
		'labels' => $labels,
		'public' => true,
		'publicly_queryable' => true,
		'show_ui' => true, 
		'show_in_menu' => false,
		'show_in_nav_menus' => false, 
		'query_var' => true,
		'rewrite' => array('slug' => 'documentor-sections','with_front' => false),
		'capability_type' => 'post',
		'has_archive' => false, 
		'hierarchical' => false,
		'can_export' => true,
		'menu_position' => null,
		'supports' => array('title','editor','thumbnail','excerpt','custom-fields')
		); 
		register_post_type('documentor-sections',$args);
	}
	function section_updated_messages( $messages ) {
		global $post, $post_ID;
		$messages['document'] = array(
		0 => '', // Unused. Messages start at index 1.
		1 => sprintf( __('Documentor Section updated. <a href="%s">View Documentor section</a>'), esc_url( get_permalink($post_ID) ) ),
		2 => __('Custom field updated.'),
		3 => __('Custom field deleted.'),
		4 => __('Documentor Section updated.'),
		/* translators: %s: date and time of the revision */
		5 => isset($_GET['revision']) ? sprintf( __('Documentor section restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => sprintf( __('Documentor Section published. <a href="%s">View Documentor section</a>'), esc_url( get_permalink($post_ID) ) ),
		7 => __('Section saved.'),
		8 => sprintf( __('Documentor Section submitted. <a target="_blank" href="%s">Preview Documentor Section</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		9 => sprintf( __('Documentor Sections scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Documentor Section</a>'),
		  // translators: Publish box date format, see http://php.net/date
		  date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
		10 => sprintf( __('Documentor Section draft updated. <a target="_blank" href="%s">Preview Documentor Section</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);

		return $messages;
	}
}

if(!function_exists('get_documentor')){
	function get_documentor( $id=0 ) {
		$guide = new DocumentorLiteGuide( $id );
		$html = $guide->view();
		echo $html;
	}
}

if( class_exists( 'DocumentorLite' ) ) {
  $cn = new DocumentorLite();
  // Register for activation
  register_activation_hook( __FILE__, array( &$cn, 'install_documentor') );
}
?>