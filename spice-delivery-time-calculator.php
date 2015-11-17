<?php
/**
 * Plugin Name: Spice Delivery time Calculator
 * Text Domain: spice_dtc
 * Domain Path: /languages
 * Plugin URI:  http://wordpress.org/plugins/search-and-replace/
 * Description: Calcolcatore di tempi di spedizione
 * Author:      Marco Visonà
 * Author URI:  http://marcovisona.it
 * Version:     1.0.0
 * License:     GPLv2+
 * Donate URI:
 *
 *
 */

//avoid direct calls to this file, because now WP core and framework has been used
if ( ! function_exists( 'add_action' ) ) {
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}

// Pre-2.6 compatibility
if ( ! defined( 'WP_CONTENT_URL' ) ) {
	define( 'WP_CONTENT_URL', get_option( 'siteurl' ) . '/wp-content' );
}
if ( ! defined( 'WP_CONTENT_DIR' ) ) {
	define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}
if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
	define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

// plugin definitions
define( 'FB_SAR_BASENAME', plugin_basename( __FILE__ ) );
define( 'FB_SAR_BASEDIR', dirname( plugin_basename( __FILE__ ) ) );
define( 'FB_SAR_TEXTDOMAIN', 'spice_dtc' );

function spice_dtc_textdomain() {

	load_plugin_textdomain( FB_SAR_TEXTDOMAIN, FALSE, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'plugins_loaded', 'spice_dtc_init' );
/**
 * Init to WP
 *
 * @return void
 */
function spice_dtc_init() {

	add_action( 'admin_init', 'spice_dtc_textdomain' );
	// add_action( 'admin_menu', 'spice_dtc_add_settings_page' );
	// add_action( 'admin_print_scripts', 'spice_dtc_add_js_head' );
}

//[foobar]
function spice_dtc( $atts ){

	$a = shortcode_atts( array(
	    'shipping_time' => 24,  // in hours
	    'threshold' => 2
	), $atts );

	extract($a);

	$from_date = new DateTime();
	if (!empty($atts["from_date"])) {
		$from_date = new DateTime($atts["from_date"]);
	}

	return calc_delivery_time($from_date, $shipping_time, $threshold);
}
add_shortcode( 'spice_dtc', 'spice_dtc' );

function calc_delivery_time($from_date, $shipping_time, $threshold=12){

    // date_default_timezone_set("Europe/Rome");
    // setlocale(LC_ALL, 'it_IT');

    $thresholdDateTime  = clone $from_date;
    $thresholdDateTime->setTime(0, 0, 0);
    $thresholdDateTime->modify($threshold . ' hour');

    $nowWeekday = (7 + date( "w", $from_date->getTimestamp()) -1) % 7;

    if ($nowWeekday >= 0 && $nowWeekday <= 3) {
        if ( intval($from_date->format('H')) > $threshold) {  // abbiamo superato la soglia di spedizione giornaliera


            $thresholdDateTime->modify('1 day');
            $nowWeekday++;
        }
    }

    if ($nowWeekday >= 4) {
        $shipping_time =  (7 - $nowWeekday)*24 + $shipping_time;
        $thresholdDateTime->modify((7 - $nowWeekday) . ' day');
    }
    

    $remaining_hours = round(($thresholdDateTime->getTimestamp() - $from_date->getTimestamp()) / 60 / 60);

    $from_date->modify('+'.$shipping_time.' hour');
    $deliveryWeekday  = date( "w", $from_date->getTimestamp());


    $strFromDate = utf8_encode(strftime("%A %e %B", $from_date->getTimestamp()));
    return "<div class=\"delivery-time\"><span class=\"delivery-time-inner\">Vuoi riceverlo $strFromDate?</span> Ordina entro <span class=\"remaining-time\">$remaining_hours ore</span>.</div>";
}
