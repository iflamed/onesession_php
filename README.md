onesession_php
==============

a session solution for mult language development

## StoreInterface

a store interface to connect with each cahce or database

## version 1.0.0

now it is surpport the memcache session store with the memacche and memcached extension

## useage
```
<?php
require 'vendor/autoload.php';
$cacheConfig = array(
	'config'=>array(
		array(
				'host'=>'127.0.0.1',
				'port'=>11211,
				'weight'=>10,
		),
	),
	'useMemcached'=>false,
);
$keyPrefix='localhost';
$storeClassName = 'MemcacheStore';
Onesession\HttpSession::init($storeClassName,$cacheConfig,$keyPrefix);
session_start();
$_SESSION['serialisation'] = 'should be in json';
if (!isset($_SESSION['a'])) {
	$_SESSION['a'] = 0;
}
$_SESSION['a']++;
var_dump($_SESSION);
?>

```
