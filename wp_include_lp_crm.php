<?
add_filter( 'et_theme_builder_template_layouts', 'et_divi_disable_theme_builder_header_footer_on_blank_template' );

add_action('woocommerce_thankyou', 'enroll_student', 10, 1);
function enroll_student( $order_id ) {
   
    $productMap = [220181=>5, 219977=>6, 219242=>7, 219241=>8, 218838=>9, 218827=>10 ];
    $paymentMap = ['bacs'=>2, 'cod'=>4];
    

        // Get an instance of the WC_Order object
        $order = wc_get_order( $order_id );

        $orderInfo=[
        'order_id'=>$order_id, 
      	'name' => $order->get_billing_first_name(),
		'payment' => $paymentMap[$order->get_payment_method()],
        'delivery_adress' => $order->get_billing_city().', '. $order->get_billing_last_name(),
        'phone' => $order->get_billing_phone(),
        'comment' => $order-> get_customer_note(),
        'products'=>[],
    	];

        // Loop through order items
        foreach ( $order->get_items() as $item_id => $item ) {
            $product = $item->get_product();
            $product_id = $product->get_id();
          		$orderInfo['products'][]=[
				    'product_id' => $productMap[$product_id],
				    'count' =>  $item->get_quantity(),
				];
        }  
               	
		send_to_lp_crm($orderInfo);
        // Flag the action as done (to avoid repetitions on reload for example)
        $order->update_meta_data( '_thankyou_action_done', true );
        $order->save();
    
}

if ( ! function_exists( 'send_to_lp_crm' ) ){
	function send_to_lp_crm($order){

		$data = array(
		    'key' => '46555abc6168afbfee7c0b91618c0f5b', //Ваш секретный токен
		    'order_id'=>  number_format(round(microtime(true)*10),0,'.',''),
		    'country' => 'UA',                   // Географическое направление заказа
		    'products' => urlencode(serialize($order['products'])),// массив с товарами в заказе
		    'bayer_name' => $order['name'],             // покупатель (Ф.И.О)
		    'phone' => $order['phone'],           // телефон
		    'site' => $_SERVER['SERVER_NAME'],  // сайт отправляющий запрос
    		'comment'         =>$order['comment'],    // комментарий
    		'delivery'        =>1,        // способ доставки (id в CRM)
    		'delivery_adress' => $order['delivery_adress'], // адрес доставки
    		'payment'         => $order['payment'],	
		);


		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, 'http://edsistore.lp-crm.biz/api/addNewOrder.html');
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		$out = curl_exec($curl);
		curl_close($curl);
	}
}