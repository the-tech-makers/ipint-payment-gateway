<?php
/**
 * Plugin Name: WooCommerce iPint Payments Gateway
 * Plugin URI: https://thetechmakers.com/
 * Description: Adds the iPint Payments gateway to your WooCommerce website.
 * Version: 1.0
 *
 * Author: The Tech Makers
 * Author URI: https://thetechmakers.com/
 *
 * Text Domain: ipint
 * Domain Path: /i18n/languages/
 *
 * Requires at least: 4.2
 * Tested up to: 4.9
 *
 */


// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'IPINT_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'IPINT_LIVE_API_URL',  'https://api.ipint.io:8003');
define( 'IPINT_TEST_API_URL',  'https://api.ipint.io:8002');
define( 'IPINT_PAYMENT_URL',  'https://ipint.io');
// define( 'IPINT_PROXY_URL',  'http://103.86.177.3/proxy.php');
define( 'IPINT_PROXY_URL',  '');


/**
 * WC iPint Payment gateway plugin class.
 *
 * @class WC_Ipint_Payments
 */
class WC_Ipint_Payments {

	/**
	 * Plugin bootstrapping.
	 */
	public static function init() {

		// iPint Payments gateway class.
		add_action( 'plugins_loaded', array( __CLASS__, 'includes' ), 0 );
		
		// add_action( 'init', array( __CLASS__, 'register_ipint_website_url' ) );		
 
		add_filter( 'generate_rewrite_rules', array( __CLASS__, 'register_ipint_website_url2' ) );
		add_filter('query_vars', array( __CLASS__, 'ipint_register_query_vars' ) );
		add_action('template_redirect', array( __CLASS__, 'ipint_handle_order_received' ) );
		

		// Make the iPint Payments gateway available to WC.
		add_filter( 'woocommerce_payment_gateways', array( __CLASS__, 'add_gateway' ) );
		
		// to display meta fields in admin order detail page
		add_action( 'woocommerce_admin_order_data_after_order_details', array( __CLASS__, 'ipint_display_order_data_in_admin' ) );

		// Display order meta fields on order received page
		add_action('woocommerce_thankyou', array( __CLASS__, 'ipint_display_order_data_in_thankyou_page') );

		// Display order meta fields on order page my-account
		add_action('woocommerce_order_details_after_order_table', array( __CLASS__, 'ipint_display_order_data_in_my_account') );

		// Display order meta fields on mail
		add_action('woocommerce_email_order_details', array( __CLASS__, 'ipint_mail_order_data') );
	}

	/**
	 * Add the iPint Payment gateway to the list of available gateways.
	 *
	 * @param array
	 */
	public static function add_gateway( $gateways ) {
		$gateways[] = 'WC_Gateway_Ipint';
		return $gateways;
	}

	// display the extra data in the order admin panel
	public static function ipint_display_order_data_in_admin( $order ){

		$transaction_onclick = get_post_meta( $order->get_id(), 'ipint_transaction_onclick', true );
	    echo '<div class="order_data_column">';
		echo '<h4> '. _e( "<label>Order Data</label>" ).'</h4>';
        echo '<p><strong>' . __( 'Invoice Amount(in USD)' ) . ': </strong>' . get_post_meta( $order->get_id(), 'ipint_invoice_amount_in_usd', true ) . '</p>';
        echo '<p><strong>' . __( 'Invoice Amount(in Local Currency)' ) . ': </strong>' . get_post_meta( $order->get_id(), 'ipint_invoice_amount_in_local_currency', true ) . '</p>';
        echo '<p><strong>' . __( 'Received Amount(in USD)' ) . ': </strong>' . get_post_meta( $order->get_id(), 'ipint_received_amount_in_usd', true ) . '</p>';
        echo '<p><strong>' . __( 'Received Amount(in Local Currency)' ) . ': </strong>' . get_post_meta( $order->get_id(), 'ipint_received_amount_in_local_currency', true ) . '</p>';
        echo '<p><strong>' . __( 'Transaction Status' ) . ': </strong>' . get_post_meta( $order->get_id(), 'ipint_transaction_status', true ) . '</p>';
        echo '<p><strong>' . __( 'Transaction Time' ) . ': </strong>' . get_post_meta( $order->get_id(), 'ipint_transaction_time', true ) . '</p>';
        echo '<p><a href="'.$transaction_onclick.'" target="blank">View on blockchain explorer</a></p>';
	    echo '</div>';

	    echo '<style>.order_data_column{ width: fit-content !important;margin-top: 40px; }</style>';
	} 

	// display the extra data in the Thankyou page
	public static function ipint_display_order_data_in_thankyou_page( $order ){

		$transaction_onclick = get_post_meta( $order, 'ipint_transaction_onclick', true );
	    echo '<div class="order_data_table">';
	    echo '<table><thead><tr><td colspan="2">Payment Details</td></tr></thead><tbody>';
	    echo '<tr><td><strong>' . __( 'Invoice Amount(in USD)' ) . ': </strong></td><td>'. get_post_meta( $order, 'ipint_invoice_amount_in_usd', true ) .'</td></tr>';
	    echo '<tr><td><strong>' . __( 'Invoice Amount(in Local Currency)' ) . ': </strong></td><td>'. get_post_meta( $order, 'ipint_invoice_amount_in_local_currency', true ) .'</td></tr>';
	    echo '<tr><td><strong>' . __( 'Received Amount(in USD)' ) . ': </strong></td><td>'. get_post_meta( $order, 'ipint_received_amount_in_usd', true ) .'</td></tr>';
	    echo '<tr><td><strong>' . __( 'Received Amount(in Local Currency)' ) . ': </strong></td><td>'. get_post_meta( $order, 'ipint_received_amount_in_local_currency', true ) .'</td></tr>';
	    echo '<tr><td><strong>' . __( 'Transaction Status' ) . ': </strong></td><td>'. get_post_meta( $order, 'ipint_transaction_status', true ) .'</td></tr>';
	    echo '<tr><td><strong>' . __( 'Transaction Time' ) . ': </strong></td><td>'. get_post_meta( $order, 'ipint_transaction_time', true ) .'</td></tr>';
	    echo '<tr><td colspan="2"><a href="'.$transaction_onclick.'" target="blank">View on blockchain explorer</a></td></tr>';
		echo '</tbody></table>';
	    echo '</div>';

	} 

	// display the extra data in the my-account-order page
	public static function ipint_display_order_data_in_my_account( $order ){
		$order = json_decode($order);

		$transaction_onclick = get_post_meta( $order->id, 'ipint_transaction_onclick', true );
	    echo '<div class="order_data_table">';
	    echo '<table><thead><tr><td colspan="2"><strong>Payment Details</strong></td></tr></thead><tbody>';
	    echo '<tr><td><strong>' . __( 'Invoice Amount(in USD)' ) . ': </strong></td><td>'. get_post_meta( $order->id, 'ipint_invoice_amount_in_usd', true ) .'</td></tr>';
	    echo '<tr><td><strong>' . __( 'Invoice Amount(in Local Currency)' ) . ': </strong></td><td>'. get_post_meta( $order->id, 'ipint_invoice_amount_in_local_currency', true ) .'</td></tr>';
	    echo '<tr><td><strong>' . __( 'Received Amount(in USD)' ) . ': </strong></td><td>'. get_post_meta( $order->id, 'ipint_received_amount_in_usd', true ) .'</td></tr>';
	    echo '<tr><td><strong>' . __( 'Received Amount(in Local Currency)' ) . ': </strong></td><td>'. get_post_meta( $order->id, 'ipint_received_amount_in_local_currency', true ) .'</td></tr>';
	    echo '<tr><td><strong>' . __( 'Transaction Status' ) . ': </strong></td><td>'. get_post_meta( $order->id, 'ipint_transaction_status', true ) .'</td></tr>';
	    echo '<tr><td><strong>' . __( 'Transaction Time' ) . ': </strong></td><td>'. get_post_meta( $order->id, 'ipint_transaction_time', true ) .'</td></tr>';
	    echo '<tr><td colspan="2"><a href="'.$transaction_onclick.'" target="blank">View on blockchain explorer</a></td></tr>';
		echo '</tbody></table>';
	    echo '</div>';

	} 

	// Send order meta data on mail 
	public static function ipint_mail_order_data( $order, $sent_to_admin, $plain_text, $email ){

		$transaction_onclick = get_post_meta( $order, 'ipint_transaction_onclick', true );
	    
	    echo '<table><thead><tr><td colspan="2">Payment Details</td></tr></thead><tbody>';
	    echo '<tr><td><strong>' . __( 'Invoice Amount(in USD)' ) . ': </strong></td><td>'. get_post_meta( $order, 'ipint_invoice_amount_in_usd', true ) .'</td></tr>';
	    echo '<tr><td><strong>' . __( 'Invoice Amount(in Local Currency)' ) . ': </strong></td><td>'. get_post_meta( $order, 'ipint_invoice_amount_in_local_currency', true ) .'</td></tr>';
	    echo '<tr><td><strong>' . __( 'Received Amount(in USD)' ) . ': </strong></td><td>'. get_post_meta( $order, 'ipint_received_amount_in_usd', true ) .'</td></tr>';
	    echo '<tr><td><strong>' . __( 'Received Amount(in Local Currency)' ) . ': </strong></td><td>'. get_post_meta( $order, 'ipint_received_amount_in_local_currency', true ) .'</td></tr>';
	    echo '<tr><td><strong>' . __( 'Transaction Status' ) . ': </strong></td><td>'. get_post_meta( $order, 'ipint_transaction_status', true ) .'</td></tr>';
	    echo '<tr><td><strong>' . __( 'Transaction Time' ) . ': </strong></td><td>'. get_post_meta( $order, 'ipint_transaction_time', true ) .'</td></tr>';
	    echo '<tr><td colspan="2"><a href="'.$transaction_onclick.'" target="blank">View on blockchain explorer</a></td></tr>';
		echo '</tbody></table>';
	    echo '</div>';

	} 

	/**
	 * Plugin includes.
	 */
	public static function includes() {

		// Make the WC_Gateway_Ipint class available.
		if ( class_exists( 'WC_Payment_Gateway' ) ) {
			require_once( 'includes/class-wc-gateway-ipint.php' );
			require_once( 'includes/class-wc-gateway-ipint-settings.php' );
			require_once( 'includes/functions.php' );
		}
	}

	/**
	 * Plugin url.
	 *
	 * @return string
	 */
	public static function plugin_url() {
		return untrailingslashit( plugins_url( '/', __FILE__ ) );
	}

	public static function register_ipint_website_url() {
		if(is_admin()){
			return false;
		}
		
		global $woocommerce;
		$checkout_url = $woocommerce->cart->get_checkout_url();

		$checkout_endpoint = str_replace(home_url(), "", $checkout_url);
		$checkout_endpoint = trim($checkout_endpoint, '/');

		// Create iPint redirect URL
		// add_rewrite_rule( $checkout_endpoint .'/ipint-payment/?$', 'index.php?ipint-payment=$matches[1]', 'top' );
		add_rewrite_rule( 'ipintpayment/?([^/]*)/?', 'index.php?ipintpayment=$matches[1]', 'top' );
		add_rewrite_rule( 'ipint-callback/([a-z0-9A-Z]+)[/]?$', 'index.php?ipintcallback=$matches[1]', 'top' );
		// add_rewrite_endpoint('ipint-payment', EP_ROOT | EP_PAGES);

		add_filter( 'query_vars', function( $query_vars ) {
			$query_vars[] = 'ipintpayment';
			$query_vars[] = 'ipint-callback';
			return $query_vars;
		} );
		
		add_action( 'template_include', function( $template ) {
			if ( get_query_var( 'ipintpayment' ) == false || get_query_var( 'ipintpayment' ) == '' ) {
				return $template;
			}

			return IPINT_PLUGIN_PATH . 'templates'. DIRECTORY_SEPARATOR .'ipint-website-redirect-url.php';
		} );

		function prefix_url_rewrite_templates() {
			if ( get_query_var( 'ipintpayment' ) ) {
				add_filter( 'template_include', function() {
					return IPINT_PLUGIN_PATH . 'templates'. DIRECTORY_SEPARATOR .'ipint-website-redirect-url.php';
				});
			}
		}

		add_action( 'template_redirect', 'prefix_url_rewrite_templates' );
		
	}

	public static function register_ipint_website_url2( $wp_rewrite ) {

		$new_rules = array(
			// 'ipintpayment/?$'  => 'index.php?page=ipintpayment',
			'ipintpayment/(\d+)/?$'  => sprintf('index.php?ipint_page=ipintpayment&order_id=%s', $wp_rewrite->preg_index(1)),
			// 'ipintcallback/?$' => 'index.php?page=ipintcallback',
			'ipintcallback/(\d+)/?$' => sprintf('index.php?ipint_page=ipintcallback&order_id=%s', $wp_rewrite->preg_index(1))
		);


		$wp_rewrite->rules = $new_rules + $wp_rewrite->rules;
		return $wp_rewrite->rules;
	}

	public static function ipint_handle_order_received() {
		register_log("########### Payment return Start #############");

		$page = get_query_var('ipint_page');
		$order_id = (int)get_query_var('order_id', 0);

		
		if ($page == 'ipintpayment' && !empty($order_id) && $order_id > 0) {

			register_log($order_id); 
			register_log(print_r($_GET, true)); 


			global $woocommerce;
			$order = new WC_Order( $order_id );
			$WC_Gateway_Ipint = new WC_Gateway_Ipint();
			
			// Mark as on-hold (we're awaiting the cheque)
			$order->update_status( 'wc-payment-processing', __( 'Payment Processing', 'ipint' ));
			
			// Reduce stock levels
			wc_reduce_stock_levels( $order_id );
			$WC_Gateway_Ipint->ipit_update_order_data($order_id);

			// $this->ipit_update_order_data($order_id);
			
			// Remove cart
			$woocommerce->cart->empty_cart();

			// die;

			$redirect_url = $order->get_checkout_order_received_url();
			wp_safe_redirect($redirect_url);
			register_log("########### Payment return End #############");

		} else if ($page == 'ipintcallback' && !empty($order_id) && $order_id > 0) {

			// echo "check: ". get_post_meta($order_id, 'ipint_invoice_id', true); die;

			register_log("########### Callback Request Start #############");

			$post_body = file_get_contents('php://input');
			register_log( print_r( $post_body, true ) );
			
			$post_body = json_decode($post_body);

			global $woocommerce;
			$order = new WC_Order( $order_id );
			$WC_Gateway_Ipint = new WC_Gateway_Ipint();

			$endpoint = $WC_Gateway_Ipint->get_ipint_invoice_url();
			$endpoint .= "?id=". get_post_meta($order_id, 'ipint_invoice_id', true);

			$nonce = intval(microtime(true) * 1000000);



			$api_path = '/invoice?id='. get_post_meta($order_id, 'ipint_invoice_id', true);

			$signature = '/api/'. $nonce . $api_path;
			$signature = hash_hmac('sha384', $signature, $WC_Gateway_Ipint->get_ipint_secret_key());

			$post_data = array(
				'method'      => 'GET',
				'timeout'     => 60,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => true,			
				'sslverify'   => false,
				'headers'     => array(
					'Content-Type' => 'application/json',
					'apikey'       => $WC_Gateway_Ipint->get_ipint_api_key(),
					'signature'    => $signature,
					'nonce'        => $nonce,
				),
			);

			register_log( print_r( $post_data, true ) );

			$response = wp_remote_post( $endpoint, $post_data );

			if ( is_wp_error( $response ) ) {
				$error_message = $response->get_error_message();
				register_log ("Something went wrong: $error_message");
			} else {

				$response = json_decode( $response['body'], true );			
				register_log(print_r($response, true));

				wc_reduce_stock_levels( $order_id );
				$WC_Gateway_Ipint->ipit_update_order_response_data($order_id, $response['data']);

				$order->payment_complete();

			}
			register_log("########### Callback Request End #############");
			die;
		}


	}

	
	/**
	 * Register custom query vars
	 *
	 * @param array $vars The array of available query variables
	 *
	 * @return array
	 *
	 * @link https://codex.wordpress.org/Plugin_API/Filter_Reference/query_vars
	 */
	public static function ipint_register_query_vars($vars){
		$vars[] = 'ipint_page';
		$vars[] = 'order_id';
		// $vars[] = 'ipintpayment';
		return $vars;

	}

	public static function add_ipint_website_return_url($query_vars) {
		$query_vars['ipint-payment'] = get_option( 'ipint_return_url', 'ipint-return-url' );
		return $query_vars;
	}
	public static function ipint_return_url_title($query_vars) {
		$title = __( 'iPint Payment Return URL', 'ipint' );
		return $title;
	}
	
}

WC_Ipint_Payments::init();
