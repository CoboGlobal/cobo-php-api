<?php

use Elliptic\EC;

require __DIR__ . "/vendor/autoload.php";
class CoboApiClient
{

    private $apiSigner;
    private $apiKey;
    private $coboPub;
    private $HOST="https://api.sandbox.cobo.com";

    public function __construct($apiSigner, $apiKey, $coboPub)
    {
        $this->apiKey = $apiKey;
        $this->apiSigner = $apiSigner;
        $this->coboPub = $coboPub;
    }

    function verify_ecdsa($message, $timestamp, $signature)
    {
        $message = hash("sha256", hash("sha256", "{$message}|{$timestamp}", True), True);
        $ec = new EC('secp256k1');
        $key = $ec->keyFromPublic($this->coboPub, "hex");
        return $key->verify(bin2hex($message), $signature);
    }

    private function sort_data($data): string
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
        $sorted_data = $this->sort_data($data);
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
            curl_setopt($ch, CURLOPT_URL, $this->HOST . $path);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        } else {
            curl_setopt($ch, CURLOPT_URL, $this->HOST . $path . "?" . $sorted_data);
        }
        list($header, $body) = explode("\r\n\r\n", curl_exec($ch), 2);
        preg_match("/biz_timestamp: (?<timestamp>[0-9]*)/i", $header, $match);
        $timestamp = $match["timestamp"];
        preg_match("/biz_resp_signature: (?<signature>[0-9abcdef]*)/i", $header, $match);
        $signature = $match["signature"];
        if ($this->verify_ecdsa($body, $timestamp, $signature) != 1) {
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
    function checkAccountDetails() {
       return $this->request("GET", "/v1/custody/org_info/", []);
    }

    /***
     * @param $coin String Coin code
     * @return mixed
     * @throws Exception
     */
    function getCoinDetails(string $coin) {
        $params = [
            "coin"=>$coin
        ];
        return $this->request("GET", "/v1/custody/coin_info/", $params);
    }
}