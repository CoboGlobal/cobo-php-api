<?php

use Elliptic\EC;

require __DIR__ . "/vendor/autoload.php";

class CoboApiClient
{
    private $apiSigner;
    private $apiKey;
    private $coboPub;
    private $host;

    public function __construct($apiSigner, $apiKey, $coboPub, $host)
    {
        $this->apiKey = $apiKey;
        $this->apiSigner = $apiSigner;
        $this->coboPub = $coboPub;
        $this->host = $host;
    }

    function verifyEcdsa($message, $timestamp, $signature)
    {
        $message = hash("sha256", hash("sha256", "{$message}|{$timestamp}", True), True);
        $ec = new EC('secp256k1');
        $key = $ec->keyFromPublic($this->coboPub, "hex");
        return $key->verify(bin2hex($message), $signature);
    }

    private function sortData($data): string
    {
        ksort($data);
        $result = [];
        foreach ($data as $key => $val) {
            array_push($result, $key . "=" . urlencode($val));
        }
        return join("&", $result);
    }

    /**
     * @throws Exception
     */
    function request($method, $path, $data)
    {
        $ch = curl_init();
        $sorted_data = $this->sortData($data);
        $nonce = time() * 1000;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Biz-Api-Key:" . $this->apiKey,
            "Biz-Api-Nonce:" . $nonce,
            "Biz-Api-Signature:" . $this->apiSigner->sign(join("|", [$method, $path, $nonce, $sorted_data]))
        ]);

        if ($method == "POST") {
            curl_setopt($ch, CURLOPT_URL, $this->host . $path);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_URL, $this->host . $path . "?" . $sorted_data);
        }
        list($header, $body) = explode("\r\n\r\n", curl_exec($ch), 2);
        preg_match("/biz_timestamp: (?<timestamp>[0-9]*)/i", $header, $match);
        $timestamp = $match["timestamp"];
        preg_match("/biz_resp_signature: (?<signature>[0-9abcdef]*)/i", $header, $match);
        $signature = $match["signature"];
        if ($this->verifyEcdsa($body, $timestamp, $signature) != 1) {
            throw new Exception("signature verify fail");
        }
        curl_close($ch);
        return $body;
    }

    /***
     * Check Account Details
     * @return mixed|string
     * @throws Exception
     */
    function checkAccountDetails()
    {
        return $this->request("GET", "/v1/custody/org_info/", []);
    }

    /***
     * @param $coin String Coin code
     * @return mixed
     * @throws Exception
     */
    function getCoinDetails(string $coin)
    {
        $params = [
            "coin" => $coin
        ];
        return $this->request("GET", "/v1/custody/coin_info/", $params);
    }

    /***
     * Get New Deposit Address
     * @param string $coin
     * @param false $native_segwit
     * @return mixed|string
     * @throws Exception
     */
    function newDepositAddress(string $coin, bool $native_segwit = false)
    {
        $params = [
            "coin" => $coin,
            "native_segwit" => $native_segwit
        ];
        return $this->request("POST", "/v1/custody/new_address/", $params);
    }

    /***
     * Batch Get New Deposit Address
     * @param string $coin
     * @param false $native_segwit
     * @param int $count
     * @return mixed|string
     * @throws Exception
     */
    function batchNewDepositAddress(string $coin, int $count, bool $native_segwit = false)
    {
        $params = [
            "coin" => $coin,
            "count" => $count,
            "native_segwit" => $native_segwit
        ];
        return $this->request("POST", "/v1/custody/new_addresses/", $params);
    }

    /***
     * Verify Deposit Address
     * @param string $coin
     * @param string $address
     * @return mixed|string
     * @throws Exception
     */
    function verifyDepositAddress(string $coin, string $address)
    {
        $params = [
            "coin" => $coin,
            "address" => $address
        ];
        return $this->request("GET", "/v1/custody/address_info/", $params);
    }

    /***
     * Verify Valid Address
     * @param string $coin
     * @param string $address
     * @return mixed|string
     * @throws Exception
     */
    function verifyValidAddress(string $coin, string $address)
    {
        $params = [
            "coin" => $coin,
            "address" => $address
        ];
        return $this->request("GET", "/v1/custody/is_valid_address/", $params);
    }

    /***
     * Get Address History List
     * @param string $coin
     * @return mixed|string
     * @throws Exception
     */
    function getAddressHistoryList(string $coin)
    {
        $params = [
            "coin" => $coin
        ];
        return $this->request("GET", "/v1/custody/address_history/", $params);
    }

    /***
     * Check Loop Address Details
     * @param string $coin
     * @param string $address
     * @param string $memo
     * @return mixed|string
     * @throws Exception
     */
    function checkLoopAddressDetails(string $coin, string $address, string $memo)
    {
        $params = [
            "coin" => $coin,
            "address" => $address,
            "memo" => $memo
        ];
        return $this->request("GET", "/v1/custody/internal_address_info/", $params);
    }

    /***
     * Verify Loop Address List
     * @param string $coin
     * @param string $address
     * @return mixed|string
     * @throws Exception
     */
    function verifyLoopAddressList(string $coin, string $address)
    {
        $params = [
            "coin" => $coin,
            "address" => $address
        ];
        return $this->request("GET", "/v1/custody/internal_address_info_batch/", $params);
    }

    /***
     * Get Transaction Details
     * @param string $id
     * @return mixed|string
     * @throws Exception
     */
    function getTransactionDetails(string $id)
    {
        $params = [
            "id" => $id
        ];
        return $this->request("GET", "/v1/custody/transaction/", $params);
    }

    /***
     * Get transactions by id
     * @param array $params
     * @return mixed|string
     * @throws Exception
     */
    function getTransactionsById(array $params = [])
    {
        return $this->request("GET", "/v1/custody/transactions_by_id/", $params);
    }

    /***
     * Get transactions by time
     * @param array $params
     * @return mixed|string
     * @throws Exception
     */
    function getTransactionsByTime(array $params = [])
    {
        return $this->request("GET", "/v1/custody/transactions_by_time/", $params);
    }

    /***
     * Get Pending Transactions
     * @param array $params
     * @return mixed|string
     * @throws Exception
     */
    function getPendingTransactions(array $params = [])
    {
        return $this->request("GET", "/v1/custody/pending_transactions/", $params);
    }

    /***
     * Get Pending Deposit Details
     * @param string $id
     * @return mixed|string
     * @throws Exception
     */
    function getPendingDepositDetails(string $id)
    {
        return $this->request("GET", "/v1/custody/pending_transaction/", ["id" => $id]);
    }

    /***
     * Get Transaction History
     * @param array $params
     * @return mixed|string
     * @throws Exception
     */
    function getTransactionHistory(array $params = [])
    {
        return $this->request("GET", "/v1/custody/transaction_history/", $params);
    }

    /***
     * submit new withdraw request
     * @param string $coin
     * @param string $requestId
     * @param string $address
     * @param string $amount
     * @param array $options
     * @return mixed|string
     * @throws Exception
     */
    function withdraw(string $coin, string $requestId, string $address, string $amount, array $options = [])
    {
        $params = array_merge([
            "coin" => $coin,
            "request_id" => $requestId,
            "address" => $address,
            "amount" => $amount,
        ], $options);
        return $this->request("POST", "/v1/custody/new_withdraw_request/", $params);
    }

    /***
     * get withdraw information
     * @param string $requestId
     * @return mixed|string
     * @throws Exception
     */
    function getWithdrawInfo(string  $requestId) {
        return $this->request("GET", "/v1/custody/new_withdraw_request/", ["request_id"=>$requestId]);
    }

    /***
     * Get a Staking Product Details
     * @param string $productId
     * @param string $lang
     * @throws Exception
     */
    function getStakingProductDetails(string $productId, string $lang="en") {
        $params = [
            "product_id"=>$productId,
            "language"=>$lang
        ];
        $this->request("GET","/v1/custody/staking_product/",$params);
    }

    /***
     * Get All Staking Product List
     * @param array $params
     * @return mixed|string
     * @throws Exception
     */
    function getStakingProductList(array $params=[]) {
        return $this->request("GET","/v1/custody/staking_products/",$params);
    }

    /***
     * Stake
     * @param string $product_id
     * @param string $amount
     * @return mixed|string
     * @throws Exception
     */
    function stake(string $product_id, string $amount) {
        $params=[
            "product_id"=>$product_id,
            "amount"=>$amount
        ];
        return $this->request("POST","/v1/custody/staking_stake/",$params);
    }

    /***
     * unstake
     * @param string $product_id
     * @param string $amount
     * @return mixed|string
     * @throws Exception
     */
    function unstake(string $product_id, string $amount) {
        $params=[
            "product_id"=>$product_id,
            "amount"=>$amount
        ];
        return $this->request("POST","/v1/custody/staking_unstake/",$params);
    }

    /***
     * Get Staking Data
     * @param array $params
     * @return mixed|string
     * @throws Exception
     */
    function getStakingData(array $params=[]) {
        return $this->request("GET","/v1/custody/stakings/",$params);
    }

    /***
     * Get Unstaking Data
     * @param array $params
     * @return mixed|string
     * @throws Exception
     */
    function getUnstakingData(string $coin=null) {
        return $this->request("GET","/v1/custody/unstakings/",$coin?["coin"=>$coin]:[]);
    }

    /***
     * @param array $params
     * @return mixed|string
     * @throws Exception
     */
    function getStakingHistory(array $params=[]) {
        return $this->request("GET","/v1/custody/staking_history/",$params);
    }




}