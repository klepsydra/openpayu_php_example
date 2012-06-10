<?php

/**
 *	OrderCreateRequest message processing. This is order initialization phase. 
 *
 *	@copyright  Copyright (c) 2011-2012, PayU
 *	@license    http://opensource.org/licenses/GPL-3.0  Open Software License (GPL 3.0)		
 */
	
session_start();

include_once("sdk/openpayu.php");
include_once("config.php");

// openpayu service configuration
// some preprocessing
$dir = explode(basename(dirname(__FILE__)).'/', $_SERVER['SCRIPT_NAME']);
$directory = $dir[0].basename(dirname(__FILE__));
$myUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . ':' . $_SERVER['SERVER_PORT'] .$directory;

$_SESSION['sessionId'] = md5(rand() . rand() . rand() . rand());

// shippingCost structure
// http://www.payu.com/pl/openpayu/OrderDomainRequest.html#Link5E
$shippingCost = array(	'CountryCode' => 'PL', 
						'ShipToOtherCountry' => 'true',
						'ShippingCostList' => array(
							'ShippingCost' => array(
								'Type' => 'courier_0',
								'CountryCode' => 'PL',
								'Price' => array(
									'Gross' => '1220', 'Net' => '1000', 'Tax' => '22', 'TaxRate' => '22', 'CurrencyCode' => 'PLN'
								)
							)
						)
					);

// initialization of order is done with OrderCreateRequest message sent.

// important!, dont use urlencode() function in associative array, in connection with sendOpenPayuDocumentAuth() function.
// urlencoding is done inside OpenPayU SDK, file openpayu.php.

// http://www.payu.com/pl/openpayu/OrderDomainRequest.html#Link88
// http://www.payu.com/pl/openpayu/OrderDomainRequest.html#Link8A
$item = array(	'Quantity' => 1,
				'Product' => array (
					'Name' => 'random test product',
					'UnitPrice' => array (
						'Gross' => 12200, 'Net' => 10000, 'Tax' => 22, 'TaxRate' => '22', 'CurrencyCode' => 'PLN'
					)
				)
			);

// http://www.payu.com/pl/openpayu/OrderDomainRequest.html#Link46
$shoppingCart = array( 	'GrandTotal' => 24400,
						'CurrencyCode' => 'PLN',
						'ShoppingCartItems' => array (
							array ('ShoppingCartItem' => $item),
							array ('ShoppingCartItem' => $item)							
						)
					);


// http://www.payu.com/pl/openpayu/OrderDomainRequest.html#Link18
$order = array (	'MerchantPosId' => OpenPayU_Configuration::$merchantPosId,
					'SessionId' => $_SESSION['sessionId'],
					'OrderUrl' => $myUrl . '/layout/page_cancel.php?order=' . rand(), // is url where customer will see in myaccount, and will be able to use to back to shop.
					'OrderCreateDate' => date("c"),
					'OrderDescription' => 'random description (' . md5(rand()) . ')',											
					'MerchantAuthorizationKey' => OpenPayU_Configuration::$posAuthKey,
					'OrderType' => 'MATERIAL', // keyword: MATERIAL or VIRTUAL 										
					'ShoppingCart' => $shoppingCart
				);

// http://www.payu.com/pl/openpayu/OrderDomainRequest.html#Link2
$OCReq = array (	'ReqId' =>  md5(rand()), 
					'CustomerIp' => '127.0.0.1', // note, this should be real ip of customer retrieved from $_SERVER['REMOTE_ADDR']
					'NotifyUrl' => $myUrl . '/OrderNotifyRequest.php', // url where payu service will send notification with order processing status changes
					'OrderCancelUrl' => $myUrl . '/layout/page_cancel.php',
					'OrderCompleteUrl' => $myUrl . '/layout/page_success.php',
					'Order' => $order,
					'ShippingCost' => array(
						'AvailableShippingCost' => $shippingCost,
						'ShippingCostsUpdateUrl' => $myUrl . '/ShippingCostRetrieveRequest.php' // this is url where payu checkout service will send shipping costs retrieve request 
					)																
				);


// send message OrderCreateRequest, $result->response = OrderCreateResponse message
$result = OpenPayU_Order::create($OCReq);

if ($result->getSuccess()) {
	echo OpenPayU_Order::printOutputConsole();
?>

	<form method="GET" action="<?php echo OpenPayU_Configuration::getAuthUrl(); ?>">
		<input type="hidden" name="redirect_uri" value="<?php echo $myUrl . "/BeforeSummaryPage.php";?>">
		<input type="hidden" name="response_type" value="code">
		<input type="hidden" name="client_id" value="<?php echo OpenPayU_Configuration::getClientId(); ?>">
		<input type="submit" value="Next step (user authorization) >">
	</form>

<?php
} else {
	echo OpenPayU_Order::printOutputConsole();
	echo "<br/><br/><br/>ERROR: " . $result->getError() . "<br/>";	
}


?>