<?php

use \Hcode\Page;
use \Hcode\Model\Product;

$app->get('/', function() {

	$products = Product::listAll();
	 
	$page = new Page();

	$page->setTpl('body',[
		'products'=>Product::checkList($products)
	]);


});


?>