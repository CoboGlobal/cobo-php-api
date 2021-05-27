<?php
require 'LocalSigner.php';
require "CoboApiClient.php";

$apiKey = "0397ef0d81938bcf9587466ee33ab93caa77677416ada3297e70e92aa42245d99e";
$apiSecret = "e7e73fabdd9edb8bddf947954c400a63bf93edc57abf170544ec570757df5453";
$coboPub = "032f45930f652d72e0c90f71869dfe9af7d713b1f67dc2f7cb51f9572778b9c876";
$signer = new LocalSigner($apiSecret);

$client = new CoboApiClient($signer, $apiKey, $coboPub);
$a= $client->checkAccountDetails();
$a= $client->getCoinDetails("ETH");
var_dump($a);
