<?php

	use \Hcode\Model\User;
	use \Hcode\Model\Cart;

	function formatPrice($vlprice){

		if(!$vlprice > 0) $vlprice = 0;
		return number_format($vlprice, 2, ",",".");

	}

	function formatDate($date){

		return date('d/m/Y', strtotime($date));

	}


	function checkLogin($inadmin = true){

		return User::checkLogin($inadmin);

	}

	function getUserName()
{

	$user = User::getFromSession();

	return $user->getdesperson();

}

	function getCartNrqtd(){

		$cart = Cart::getSession();

		$total = $cart->getProductsTotal();

		return $total['nrqtd'];

	}

	function getCartVlSubtotal(){

		$cart = Cart::getSession();

		$total = $cart->getProductsTotal();

		return formatPrice($total['vlprice']);

	}


?>