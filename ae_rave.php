<?php
/*
Plugin Name: Flutterwave Rave for Enginethemes.com DirectoryEngine, FreelanceEngine site
Plugin URI: http://rave.flutterwave.com/
Description: Integrates Rave payment gateway to DirectoryEngine, FreelanceEngine site
Version: 1.0
Author: Oluwole Adebiyi (King Flamez)
Author URI: https://github.com/kingflamez/
License: GPLv2
*/
add_filter('ae_admin_menu_pages','ae_rave_add_settings', 10, 2 );
function ae_rave_add_settings($pages){
	$sections = array();
	$options = AE_Options::get_instance();
	/**
	 * ae fields settings
	 */
	$sections = array(
		'args' => array(
			'title' => __("Rave Settings", ET_DOMAIN) ,
			'id' => 'meta_field',
			'icon' => 'F',
			'class' => ''
		) ,
		'groups' => array(
			array(
				'args' => array(
					'title' => __("Rave Settings", ET_DOMAIN) ,
					'id' => 'secret-key',
					'class' => '',
					'desc' => __('Get your api keys from your Rave dashboard settings.<br>
						', ET_DOMAIN),
					'name' => 'rave'
				) ,
				'fields' => array(
					array(
                        'id' => 'mode',
                        // 'type' => 'radio',
                        'label' => __("Mode", ET_DOMAIN),
                        'title' => __("Mode", ET_DOMAIN),
                        'name' => 'mode',
                        'class' => '',
                         'type' => 'select',
                            'data' => array(
                                'disable' => __("Disable", ET_DOMAIN) ,
                                'test' => __("Test", ET_DOMAIN) ,
                                'live' => __("Live", ET_DOMAIN) ,
                            ) ,
                    ) ,
                    array(
						'id' => 'rsk',
						'type' => 'text',
						'label' => __("Secret Key", ET_DOMAIN) ,
						'name' => 'rsk',
						'class' => ''
					) ,
					array(
						'id' => 'rpk',
						'type' => 'text',
						'label' => __('Public Key', ET_DOMAIN),
						'name'  => 'rpk',
						'class' => ''
					),
					array(
						'id' => 'rlogo',
						'type' => 'text',
						'label' => __("Logo (Preferrably Square size) (Optional)", ET_DOMAIN) ,
						'name' => 'rlogo',
						'class' => ''
					) ,
					array(
						'id' => 'pym',
						'type' => 'dropdown',
						'label' => __('Payment Method', ET_DOMAIN),,
			            'options' => array(
			                'both' => 'All',
			                'card' => 'Card Only',
			                'account' => 'Account Only',
			                'ussd' => 'USSD Only'
			            ),
						'name'  => 'pym',
						'class' => ''
					) ,
					array(
						'id' => 'country',
						'type' => 'dropdown',
						'label' => __('Country', ET_DOMAIN),,
			            'options' => array(
			                'NG' => 'Nigeria',
			                'GH' => 'Ghana',
			                'KE' => 'Kenya'
			            ),
						'name'  => 'country',
						'class' => ''
					)
					
					
				)
			)
		)
	);

	$temp = new AE_section($sections['args'], $sections['groups'], $options);
	$rave_setting = new AE_container(array(
		'class' => 'field-settings',
		'id' => 'settings',
	) , $temp, $options);
	$pages[] = array(
		'args' => array(
			'parent_slug' => 'et-overview',
			'page_title' => __('Rave', ET_DOMAIN) ,
			'menu_title' => __('Rave settings', ET_DOMAIN) ,
			'cap' => 'administrator',
			'slug' => 'ae-rave',
			'icon' => '$',
			'desc' => __("Integrate the Rave payment gateway to your site", ET_DOMAIN)
		) ,
		'container' => $rave_setting
	);
	return $pages;
}
add_filter( 'ae_support_gateway', 'ae_rave_add' );
function ae_rave_add($gateways){
	$gateways['rave'] = 'Rave';
	return $gateways;
}
add_action('after_payment_list', 'ae_rave_render_button');
function ae_rave_render_button() {
	$rave = ae_get_option('rave');
	if($rave['mode'] ==  'disable')
		return false;
?>
	<li>
		<span class="title-plan select-payment" data-type="rave">
			<?php _e("Rave", ET_DOMAIN); ?>
			
		</span>
		<br>
			<img src="<?php echo plugins_url( 'rave.png' , __FILE__ ); ?>" alt="cardlogos" style="width: 200px !important; height: auto"/>
			
		<a href="#" class="btn btn-submit-price-plan select-payment" data-type="rave"><?php _e("Select", ET_DOMAIN); ?></a>
	</li>
<?php
}
add_filter('ae_setup_payment', 'ae_rave_setup_payment', 10, 4	);
function ae_rave_setup_payment($response, $paymentType, $order) {
    global $current_user,$user_email;
     // kiem tra payment gateway
    if ($paymentType == 'RAVE') {
        $rave = ae_get_option('rave');
		$mode = $rave['mode'];
 		if ($mode == 'test') {
			$rave_payment_url = 'https://rave-api-v2.herokuapp.com';
		}else{
			$rave_payment_url = 'https://api.ravepay.co';
		}

        $order_pay = $order->generate_data_to_pay();

		// Get rave env
		$publickey = $rave['rpk'];
		$secretkey = $rave['rsk'];
		$payment_method = $rave['pym'];
		$country = $rave['country'];
		$rlogo = $rave['rlogo'];

		// Prepare Rave
        $order_id = $order_pay['ID'];
		$ref = $order_id . '_' .time();
		$redirectURL = et_get_page_link('process-payment', array(
	                        'paymentType' => 'rave',
	                        'return' => "1"
                    	)) ;
        $amountToBePaid = $order_pay['total'];
        $currency = $order_pay['currencyCodeType'];


		$postfields = array();
		$postfields['PBFPubKey'] = $publicKey;
		$postfields['customer_email'] = $user_email;
		$postfields['custom_description'] = "Payment for Order: " . $order_id . " on " . get_bloginfo('name');
		$postfields['custom_title'] = get_bloginfo('name');
		$postfields['country'] = $country;
		$postfields['custom_logo'] = $rlogo;
		$postfields['redirect_url'] = $redirectURL;
		$postfields['txref'] = $ref;
		$postfields['payment_method'] = $payment_method;
		$postfields['amount'] = $amountToBePaid + 0;
		$postfields['currency'] = $currency;
		$postfields['hosted_payment'] = 1;
		ksort($postfields);
		$stringToHash = "";
		foreach ($postfields as $key => $val) {
		$stringToHash .= $val;
		}
		$stringToHash .= $secretkey;
		$hashedValue = hash('sha256', $stringToHash);
		$transactionData = array_merge($postfields, array('integrity_hash' => $hashedValue));
		$json = json_encode($transactionData);
		$htmlOutput = "
		    <script type='text/javascript' src='" . $baseUrl . "/flwv3-pug/getpaidx/api/flwpbf-inline.js'></script>
		    <script>
		    document.addEventListener('DOMContentLoaded', function(event) {
			    var data = JSON.parse('" . json_encode($transactionData = array_merge($postfields, array('integrity_hash' => $hashedValue))) . "');
			    getpaidSetup(data);
			});
		    </script>
		    ";
		echo $htmlOutput;
		exit;      
    }

    return $response;
}


$requeryCount = 0;

add_filter('ae_process_payment', 'ae_rave_process_payment', 10 ,2 );
function ae_rave_process_payment($payment_return, $data) {

    $paymenttype = $data['payment_type'];

	if($paymenttype == 'rave' && isset($_GET['txref'])) {
		return requery($payment_return, $data);
	} else {

	}
    return $payment_return;
}

function requery($payment_return, $data)
{
	$rave = ae_get_option('rave');
	$mode = $rave['mode'];
					
	if ($mode == 'test') {
		$apiLink = "http://flw-pms-dev.eu-west-1.elasticbeanstalk.com/";
	}else{
		$apiLink = "https://api.ravepay.co/";
	}

    $txref = $_GET['txref'];
    $GLOBALS['requeryCount']++;
    $data = array(
        'txref' => $txref,
        'SECKEY' => $GLOBALS['secretKey'],
        'last_attempt' => '1'
        // 'only_successful' => '1'
    );

	$args = array(
		'body' => $data
	);

	// Make request to endpoint
	$response = wp_remote_post($apiLink . 'flwv3-pug/getpaidx/api/xrequery', $args );

	$resp = json_decode(wp_remote_retrieve_body($response));

	if ($resp && $resp->status === "success") {
        if ($resp && $resp->data && $resp->data->status === "successful") {
            verifyTransaction($payment_return, $data, $resp->data);
        }
        elseif ($resp && $resp->data && $resp->data->status === "failed") {
            return failed($payment_return);
        }
        else {
            if ($GLOBALS['requeryCount'] > 4) {
                return failed($payment_return);
            }
            else {
                sleep(3);
                return requery($payment_return, $data);
            }
        }
    } 
    else {
        if ($GLOBALS['requeryCount'] > 4) {
            return failed($$payment_return);
        } else {
            sleep(3);
            return requery($payment_return, $data);
        }
    }
}
/**
 * Requeries a previous transaction from the Rave payment gateway
 * @param string $referenceNumber This should be the reference number of the transaction you want to requery
 * @return object
 * */
function verifyTransaction($payment_return, $data, $api)
{
    $paymenttype = $data['payment_type'];
    $order = $data['order'];
    $order_pay = $order->generate_data_to_pay();

    $amount = $order_pay['total'];
    $amount = $amount + 0;
    $currency = $order_pay['currencyCodeType'];
    $order_id = explode('_', $_GET["txref"]);
	$order_id = $order_id[0];

    
    if (($api->chargecode == "00" || $api->chargecode == "0") && ($api->amount >= $amount) && ($api->currency == $currency)) {
		$payment_return = array(
            'ACK' => true,
            'payment' => 'rave',
            'payment_status' => 'complete'
        );
		update_post_meta($order_id, 'paystack_status','COMPLETE');
    }
    elseif (($api->chargecode == "00" || $api->chargecode == "0") && ($api->amount < $amount)) {
    	$payment_return = array(
            'ACK' => false,
            'payment' => 'rave',
            'payment_status' => 'fail',
        	'msg' => 'Wrong amount paid'
        );
    }
    elseif (($api->chargecode == "00" || $api->chargecode == "0") && ($api->currency != $currency)) {
    	$payment_return = array(
            'ACK' => false,
            'payment' => 'rave',
            'payment_status' => 'fail',
        	'msg' => 'Wrong currency used'
        );
    } else {
    	$payment_return = array(
            'ACK' => false,
            'payment' => 'rave',
            'payment_status' => 'fail',
        	'msg' => 'Transaction Failed'
        );
    }

    return $payment_return;
}

function failed($payment_return)
{
    $payment_return = array(
        'ACK' => false,
        'payment' => 'rave',
        'payment_status' => 'fail',
    	'msg' => 'Transaction failed'
    );


    return $payment_return;
}
