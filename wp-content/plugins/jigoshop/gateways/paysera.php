<?php

require_once('vendor/webtopay/libwebtopay/WebToPay.php');

/**
 * Add the gateway to JigoShop
 **/
function add_paysera_gateway($methods) {
    $methods[] = 'jigoshop_paysera';
    return $methods;
}

add_filter('jigoshop_payment_gateways', 'add_paysera_gateway', 1);


class jigoshop_paysera extends jigoshop_payment_gateway {

    public function __construct() {

        parent::__construct();

        $this->id          = 'paysera';
        $this->icon        = jigoshop::assets_url() . '/assets/images/icons/paysera.png';
        $this->has_fields  = false;
        $this->enabled     = Jigoshop_Base::get_options()->get_option('jigoshop_paysera_enabled');
        $this->title       = Jigoshop_Base::get_options()->get_option('jigoshop_paysera_title');
        $this->description = Jigoshop_Base::get_options()->get_option('jigoshop_paysera_description');
        $this->merchantId  = Jigoshop_Base::get_options()->get_option('jigoshop_paysera_merchant_id');
        $this->projectId   = Jigoshop_Base::get_options()->get_option('jigoshop_paysera_project_id');
        $this->projectPass = Jigoshop_Base::get_options()->get_option('jigoshop_paysera_project_password');
        $this->testMode    = Jigoshop_Base::get_options()->get_option('jigoshop_paysera_test');

        add_action('init', array(&$this, 'check_ipn_response'));
        add_action('paysera_callback', array(&$this, 'payment_callback'));
        add_action('receipt_paysera', array(&$this, 'receipt_page'));
    }


    /**
     * Default Option settings for WordPress Settings API using the Jigoshop_Options class
     *
     * These will be installed on the Jigoshop_Options 'Payment Gateways' tab by the parent class 'jigoshop_payment_gateway'
     *
     */
    protected function get_default_options() {
        $defaults = array();

        // Define the Section name for the Jigoshop_Options
        $defaults[] = array('name' => __('Paysera Payment', 'jigoshop'), 'type' => 'title', 'desc' => __('Allows Paysera payments. Allows you to make test purchases without having to use the sandbox area of a payment gateway. Quite useful for demonstrating to clients and for testing order emails and the \'success\' pages etc.', 'jigoshop'));

        // List each option in order of appearance with details
        $defaults[] = array(
            'name'    => __('Enable Paysera Payment', 'jigoshop'),
            'desc'    => '',
            'tip'     => '',
            'id'      => 'jigoshop_paysera_enabled',
            'std'     => 'yes',
            'type'    => 'checkbox',
            'choices' => array(
                'no'  => __('No', 'jigoshop'),
                'yes' => __('Yes', 'jigoshop')
            )
        );

        $defaults[] = array(
            'name' => __('Method Title', 'jigoshop'),
            'desc' => '',
            'tip'  => __('This controls the title which the user sees during checkout.', 'jigoshop'),
            'id'   => 'jigoshop_paysera_title',
            'std'  => __('Paysera Payment', 'jigoshop'),
            'type' => 'text'
        );

        $defaults[] = array(
            'name' => __('Project ID', 'jigoshop'),
            'desc' => '',
            'tip'  => __('This controls the title which the user sees during checkout.', 'jigoshop'),
            'id'   => 'jigoshop_paysera_project_id',
            'std'  => __('Paysera Payment', 'jigoshop'),
            'type' => 'text'
        );

        $defaults[] = array(
            'name' => __('Project password', 'jigoshop'),
            'desc' => '',
            'tip'  => __('This controls the title which the user sees during checkout.', 'jigoshop'),
            'id'   => 'jigoshop_paysera_project_password',
            'std'  => __('Paysera Payment', 'jigoshop'),
            'type' => 'text'
        );

        $defaults[] = array(
            'name'    => __('Enable test mode', 'jigoshop'),
            'desc'    => '',
            'tip'     => '',
            'id'      => 'jigoshop_paysera_test',
            'std'     => 'yes',
            'type'    => 'radio',
            'choices' => array(
                '0' => __('No', 'jigoshop'),
                '1' => __('Yes', 'jigoshop')
            )
        );

        return $defaults;
    }

    /**
     * There are no payment fields for paysera, but we want to show the description if set.
     **/
    function payment_fields() {
        if ($this->description) echo wpautop(wptexturize($this->description));
    }

    /**
     * Generate the paysera button link
     **/
    public function generate_paysera_form($order_id) {
        $Order = new jigoshop_order($order_id);

        try {
            $request = WebToPay::buildRequest(array(
                'projectid'     => $this->projectId,
                'sign_password' => $this->projectPass,

                'orderid'       => $order_id,
                'amount'        => intval(number_format($Order->_data['order_total'], 2, '', '')),
                'currency'      => Jigoshop_Base::get_options()->get_option('jigoshop_currency'),

                'accepturl'     => add_query_arg('key', $Order->order_key, add_query_arg('order', $order_id, get_permalink(apply_filters('jigoshop_get_checkout_redirect_page_id', jigoshop_get_page_id('thanks'))))),
                'cancelurl'     => $Order->get_cancel_order_url(),
                'callbackurl'   => trailingslashit(get_bloginfo('wpurl')) . '?payseraListener=payseraCallback',
                'country'       => $Order->billing_country,

                'p_firstname'   => $Order->billing_first_name,
                'p_lastname'    => $Order->billing_last_name,
                'p_email'       => $Order->billing_email,
                'p_street'      => $Order->billing_address_1,
                'p_city'        => $Order->billing_city,
                'p_state'       => $Order->billing_state,
                'p_zip'         => $Order->billing_postcode,
                'p_countrycode' => $Order->billing_country,
                'test'          => $this->testMode,
            ));
        } catch (WebToPayException $e) {
            echo get_class($e) . ': ' . $e->getMessage();
        }

        $form   = array();
        $form[] = '<form action="' . WebToPay::PAY_URL . '" method="post" id="paysera_payment_form">';

        foreach ($request as $key => $value) {
            $form[] = '<input type="hidden" name="' . $key . '" value="' . $value . '" />';
        }

        $form[] = '<input type="submit" class="button-alt" id="submit_paysera_payment_form" value="' . __('Pay via Paysera', 'jigoshop') . '" /> <a class="button cancel" href="' . esc_url($Order->get_cancel_order_url()) . '">' . __('Cancel order &amp; restore cart', 'jigoshop') . '</a>';
        $form[] = '<script type="text/javascript">
					jQuery(function(){
						jQuery("body").block({
							message: "<img src=\"' . jigoshop::assets_url() . '/assets/images/ajax-loader.gif\" alt=\"Redirecting...\" />' . __('Thank you for your order. We are now redirecting you to Paysera to make payment.', 'jigoshop') . '",
							overlayCSS: {
								background: "#fff",
								opacity: 0.6
							},
							css: {
								padding:		20,
								textAlign:	  "center",
								color:		  "#555",
								border:		 "3px solid #aaa",
								backgroundColor:"#fff",
								cursor:		 "wait"
							}
						});
						jQuery("#submit_paysera_payment_form").click();
					});
				</script>';
        $form[] = '</form>';

        return implode("\n", $form);
    }

    /**
     * Process the payment and return the result
     **/
    function process_payment($order_id) {
        $order = new jigoshop_order($order_id);

        return array(
            'result'   => 'success',
            'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(jigoshop_get_page_id('pay'))))
        );
    }

    /**
     * receipt_page
     **/
    function receipt_page($orderId) {
        echo '<p>' . __('Thank you for your order, please click the button below to pay with Paysera.', 'jigoshop') . '</p>';
        echo $this->generate_paysera_form($orderId);
    }

    /**
     * Check for Paysera IPN Response
     **/
    function check_ipn_response() {
        if (is_admin()) return;

        if (isset($_REQUEST['payseraListener']) && $_REQUEST['payseraListener'] == 'payseraCallback') {
            @ob_clean();

            header('HTTP/1.1 200 OK');

            do_action('paysera_callback', $_REQUEST);
        }
    }

    /**
     * Paysera Callback
     **/
    function payment_callback($request) {
        try {
            $response = WebToPay::checkResponse($_REQUEST, array(
                'projectid'     => $this->projectId,
                'sign_password' => $this->projectPass,
            ));

            if ($response['status'] == 1) {

                $Order = new jigoshop_order($response['orderid']);

                if ((intval(number_format($Order->_data['order_total'], 2, '', ''))) != $response['amount']) {
                    throw new Exception('Amounts do not match ' . (intval(number_format($Order->_data['order_total'], 2, '', ''))) . '!=' . $response['amount']);
                }

                if (Jigoshop_Base::get_options()->get_option('jigoshop_currency') != $response['currency']) {
                    throw new Exception('Currencies do not macth');
                }

                if ($Order->status !== 'completed') {
                    $Order->add_order_note(__('Callback payment completed', 'jigoshop'));
                    $Order->payment_complete();

                    jigoshop_log("PAYSERA: Payment validation successful for order ID: " . $response['orderid']);
                }
            }
            exit('OK');
        } catch (Exception $e) {
            $msg = get_class($e) . ': ' . $e->getMessage();

            jigoshop_log('Payment validation error: ' . $msg);

            echo $msg;
        }

        exit();
    }

    public function process_gateway($subtotal, $shipping_total, $discount = 0) {

        $ret_val = false;
        if (!(isset($subtotal) && isset($shipping_total))) return $ret_val;

        // check for free (which is the sum of all products and shipping = 0) Tax doesn't count unless prices
        // include tax
        if (($subtotal <= 0 && $shipping_total <= 0) || (($subtotal + $shipping_total) - $discount) == 0) :
            // true when force payment = 'yes'
            $ret_val = ($this->force_payment == 'yes'); elseif (($subtotal + $shipping_total) - $discount < 0) :
            // don't process paysera if the sum of the product prices and shipping total is less than the discount
            // as it cannot handle this scenario
            $ret_val = false; else :
            $ret_val = true;
        endif;

        return $ret_val;

    }

}
