<?php
/*
 * Plugin Name: Cpay Crypto Payment Gateway
 * Plugin URI: https://big-forests.com/woocommerce/payment-gateway-plugin.html
 * Description: Accept crypto currencies with Cpay payments gateways.
 * Author: Cpay Integrations
 * Author URI: http://Big-forests.com
 * Version: 2.0
 */
/*
 * This action hook registers our PHP class as a WooCommerce payment gateway
 */
add_filter( 'woocommerce_payment_gateways', 'cpay_add_gateway_class' );
function cpay_add_gateway_class( $gateways ) {
	$gateways[] = 'WC_Cpay_Gateway'; // your class name is here
	return $gateways;
}

/*
 * The class itself, please note that it is inside plugins_loaded action hook
 */
add_action( 'plugins_loaded', 'cpay_init_gateway_class' );
function cpay_init_gateway_class() {

	class WC_Cpay_Gateway extends WC_Payment_Gateway {

 		/**
 		 * Class constructor, more about it in Step 3
 		 */
 		public function __construct() {

                $this->id = 'cpay'; // payment gateway plugin ID
	$this->icon = 'https://big-forests.com/wp-content/uploads/2022/09/cpay-plugin-logo-1.png'; // URL of the icon that will be displayed on checkout page near your gateway name
	$this->has_fields = true; // in case you need a custom credit card form
	$this->method_title = 'Cpay Crypto Gateway';
	$this->method_description = 'Accept crypto currencies with cpay payments gateway'; // will be displayed on the options page

	// gateways can support subscriptions, refunds, saved payment methods,
	// but in this tutorial we begin with simple payments
	$this->supports = array(
		'products'
	);

	// Method with all the options fields
	$this->init_form_fields();

	// Load the settings.
	$this->init_settings();
	$this->title = $this->get_option( 'title' );
	$this->description = $this->get_option( 'description' );
	$this->enabled = $this->get_option( 'enabled' );
	$this->testmode = 'yes' === $this->get_option( 'testmode' );
	$this->private_key = $this->testmode ? $this->get_option( 'test_private_key' ) : $this->get_option( 'private_key' );
	$this->publishable_key = $this->testmode ? $this->get_option( 'test_publishable_key' ) : $this->get_option( 'publishable_key' );

	// This action hook saves the settings
	add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );

	// We need custom JavaScript to obtain a token
	add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
	
	// You can also register a webhook here
	// add_action( 'woocommerce_api_{webhook name}', array( $this, 'webhook' ) );
 }


		/**
 		 * Plugin options, we deal with it in Step 3 too
 		 */
 		public function init_form_fields(){

		$this->form_fields = array(
		'enabled' => array(
			'title'       => 'Enable/Disable',
			'label'       => 'Enable Cpay Gateway',
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no'
		),
		'title' => array(
			'title'       => 'Title',
			'type'        => 'text',
			'description' => 'This controls the title which the user sees during checkout.',
			'default'     => 'Cpay Crypto gateway',
			'desc_tip'    => true,
		),
		'description' => array(
			'title'       => 'Description',
			'type'        => 'textarea',
			'description' => 'This controls the description which the user sees during checkout.',
			'default'     => 'Pay with your crypto currencies safe &amp; secure without any banking details. Process: 1. Copy below wallet ID, 2. Place order, 3. Copy order id, 4. Go to your wallet and send btc/eth equal to purchase price with order id, website/app name &amp; your email. Within 10 minutes of placing order.',
		),
                'create account' => array(
			'title'       => 'Account Activation',
			'type'        => 'textarea',
			'description' => 'Copy and go through above url to activate your account.',
			'default'     => 'https://cpay-integrations.yolasite.com/',
                )
	);
}
	

		/**
		 * You will need it if you want your custom credit card form, Step 4 is about it
		 */
		public function payment_fields() {
 
	// ok, let's display some description before the payment form
	if ( $this->description ) {
		// you can instructions for test mode, I mean test card numbers etc.
		if ( $this->testmode ) {
			$this->description .= '';
		}
		// display the description with <p> tags etc.
		echo wpautop( wp_kses_post( $this->description ) );
	}
 
	// I will echo() the form, but you can close PHP tags and print it directly in HTML
	echo '<fieldset id="wc-' . esc_attr( $this->id ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';
 
	// Add this action hook if you want your custom payment gateway to support it
	do_action( 'woocommerce_credit_card_form_start', $this->id );
 
	// I recommend to use inique IDs, because other gateways could already use #ccNo, #expdate, #cvc
	echo '<div class="form-row form-row-wide"><label>Wallet Id = " @cpayme " <span class="required">*</span></label>
		
		</div>
		<div class="clear"></div>';
 
	do_action( 'woocommerce_credit_card_form_end', $this->id );
 
	echo '<div class="clear"></div></fieldset>';
				 
		}

		/*
		 * Custom CSS and JS, in most cases required only when you decided to go with a custom credit card form
		 */
	 	public function payment_scripts() {

		
	
	 	}

		/*
 		 * Fields validation, more in Step 5
		 */
		public function validate_fields() {

		

		}

		/*
		 * We're processing the payments here, everything about it is in Step 5
		 */
		public function process_payment( $order_id ) {

    global $woocommerce;
    $order = new WC_Order( $order_id );

    // Mark as on-hold (we're awaiting the crypto)
    $order->update_status('on-hold', __( 'Awaiting for your payment', 'woocommerce' ));

    // Remove cart
    $woocommerce->cart->empty_cart();

    // Return thankyou redirect
    return array(
        'result' => 'success',
        'redirect' => $this->get_return_url( $order )
    );
}
					

		/*
		 * In case you need a webhook, like PayPal IPN etc
		 */
		public function webhook() {

		
					
	 	}
 	}
}