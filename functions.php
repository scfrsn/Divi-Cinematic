<?php

// Set a global timezone for use in our code
date_default_timezone_set('America/Vancouver');

// Include the movie post type
require_once('movies/index.php');

// Shorthand function for echoing variables that might not exist
function echo_var(&$var, $default = false) {
    return isset($var) ? $var : $default;
}

// Shorthand function for echoing checkbox values
function echo_checkbox(&$var, $value, $default = false) {
	if (isset($var)) {
		checked($var, $value);
	}
}

// WordPress debugging function
// Useful for debugging things like 'save-post'
if(!function_exists('log_it')){
  function log_it( $message ) {
    if( WP_DEBUG === true ){
      if( is_array( $message ) || is_object( $message ) ){
        error_log( print_r( $message, true ) );
      } else {
        error_log( $message );
      }
    }
  }
}

// Shorthand function for input saves in our metaboxes
function save_if_changed($value, $post_id, $is_string) {
	if (isset($_POST[$value]) && ($_POST['fetched'] != true)) {
		$stored = get_post_meta($post_id, $value, true);
		$proposed = $_POST[$value];
		if($proposed != $stored) { // Only save the value if it has changed
			if($is_string) {
				update_post_meta($post_id, $value, sanitize_text_field($proposed));
			} else { // For saving checkbox values, etc., use this
				update_post_meta($post_id, $value, $proposed);
			}
		}
	}
}

// Our theme needs it's own textdomain for translation
add_action('after_setup_theme', 'divi_cinematic_setup');
function divi_cinematic_setup(){
	load_child_theme_textdomain( 'divi-cinematic', get_stylesheet_directory_uri() . '/languages' );
}

// Create a settings section in the customizer to give us control over our default showtimes
add_action( 'customize_register', 'divi_cinematic_customize_register' );
function divi_cinematic_customize_register( $wp_customize ) {
	$wp_customize->add_section( 'divi-cinematic-defaults' , array(
    'title'      => __( 'Movie Listing Defaults', 'divi-cinematic' ),
    'priority'   => 30,
	));
	$wp_customize->add_setting( 'divi-cinematic-early' , array('default' => '6:30 PM'));
	$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'divi-cinematic-early', array(
		'label'      => __( 'Early Time', 'divi-cinematic' ),
		'section'    => 'divi-cinematic-defaults',
		'settings'   => 'divi-cinematic-early',
	)));
	$wp_customize->add_setting( 'divi-cinematic-late' , array('default' => '9:00 PM'));
	$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'divi-cinematic-late', array(
		'label'      => __( 'Late Time', 'divi-cinematic' ),
		'section'    => 'divi-cinematic-defaults',
		'settings'   => 'divi-cinematic-late',
	)));
	$wp_customize->add_setting( 'divi-cinematic-matinee' , array('default' => '2:00 PM'));
	$wp_customize->add_control( new WP_Customize_Control( $wp_customize, 'divi-cinematic-matinee', array(
		'label'      => __( 'Matinee Time', 'divi-cinematic' ),
		'section'    => 'divi-cinematic-defaults',
		'settings'   => 'divi-cinematic-matinee',
	)));
}

// Automatic theme updates from the GitHub repository
add_filter('pre_set_site_transient_update_themes', 'divi_cinematic_updates', 100, 1);
function divi_cinematic_updates($data) {
	$theme = wp_get_theme();
	$repository = $theme->get('ThemeURI'); // We're using the 'Theme URI' property in our theme stylesheet header
  $file = @json_decode(@file_get_contents('https://api.github.com/repos'.parse_url($repository)['path'].'/tags', false,
      stream_context_create(['http' => ['header' => "User-Agent: scfrsn\r\n"]])
  ));
	if($file) {
		$data->response['divi-cinematic'] = array(
			'theme'       => 'divi-cinematic',
			// Strip the version number of any non-alpha characters (excluding the period)
			'new_version' => filter_var(reset($file)->name, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION),
			'url'         => $repository,
			'package'     => $repository.'/archive/'.reset($file)->name.'.zip',
		);
	}
  return $data;
}

// Enqueue the scripts and stylesheets for our interactive tutorials
add_action( 'admin_enqueue_scripts', 'tutorial_scripts', 10, 1);
function tutorial_scripts($hook) {
	// Return if the hook doesn't need the scripts and stylesheets
	if($hook != 'newsletter_page_newsletter_emails_index' && $hook != 'admin_page_newsletter_emails_edit' && $hook != 'post.php' && $hook != 'post-new.php' && $hook != 'edit.php') {
		return;
  }

  // // Load the latest jQuery
  // wp_deregister_script('jquery');
	// wp_register_script('jquery', ("http://code.jquery.com/jquery-latest.min.js"), false, '');
	// wp_enqueue_script('jquery');

	wp_enqueue_style ( 'tutorial-intro-css', get_stylesheet_directory_uri() . '/assets/css/intro.css' );
	wp_enqueue_style ( 'tutorial-intro-theme-css', get_stylesheet_directory_uri() . '/assets/css/intro-theme.css' );
  wp_enqueue_script( 'tutorial_intro', get_stylesheet_directory_uri() . '/assets/js/intro.js' );
  wp_enqueue_script( 'tutorial_tutorials', get_stylesheet_directory_uri() . '/assets/js/tutorials.js' );
}

// Replace the footer copyright text
// We could override footer.php entirely, but this way we don't have to
// check for syntax changes every time the parent theme gets updated
add_action('wp_head', 'buffer_start');
add_action('wp_footer', 'buffer_end');
function callback($buffer) {
	$source  = '#<p id="footer-info">(.*?)</p>#s';
	$replace = '<p id="footer-info">'.sprintf( __( 'Designed & Powered by %1$s', 'divi-cinematic' ), '<a href="http://www.onetrix.com"><img src="'.get_stylesheet_directory_uri().'/assets/images/onetrix.png" alt="O-Netrix Solutions" title="O-Netrix Solutions" id="onetrix" />' ).'</p>';
  return preg_replace($source,$replace,$buffer);
}
function buffer_start() { ob_start("callback"); }
function buffer_end() { ob_end_flush(); }

?>
