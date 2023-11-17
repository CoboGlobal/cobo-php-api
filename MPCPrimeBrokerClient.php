<?php

namespace Cobo\Custody;

use BI\BigInteger;
use Elliptic\EC;
use PHPUnit\Runner\Exception;


class MPCPrimeBrokerClient
{
    private $apiSigner;
    private $apiKey;
    private $coboPub;
    private $host;
    private $debug;

    public function __construct(ApiSigner $apiSigner, array $config, bool $debug = false)
    {
        $this->apiKey = $apiSigner->getPublicKey();
        $this->apiSigner = $apiSigner;
        $this->coboPub = $config['coboPub'];
        $this->host = $config['host'];
        $this->debug = $debug;
    }

    /**
     * @throws Exception
     */
    function request(string $method, string $path, array $data)
    {
        $ch = curl_init();
        $sorted_data = $this->sortData($data);
        list($microsecond, $second) = explode(' ', microtime());
        $nonce = (float)sprintf('%.0f', (floatval($microsecond) + floatval($second)) * 1000);
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
        if ($this->debug) {
            echo "request >>>>>>>>\n";
            echo join("|", [$method, $path, $nonce, $sorted_data]), "\n";
        }

        list($header, $body) = explode("\r\n\r\n", curl_exec($ch), 2);
        preg_match("/biz_timestamp: (?<timestamp>[0-9]*)/i", $header, $match);
        $timestamp = $match["timestamp"];
        preg_match("/biz_resp_signature: (?<signature>[0-9abcdef]*)/i", $header, $match);
        $signature = $match["signature"];

        if ($this->debug) {
            echo "response <<<<<<<<\n";
            echo "$body|$timestamp", "\n";
            echo "$signature", "\n";
        }
        if ($this->verifyEcdsa($body, $timestamp, $signature) != 1) {
            throw new Exception("signature verify fail");
        }
        curl_close($ch);
        return json_decode($body);
    }

    private function sortData(array $data): string
    {
        ksort($data);
        $result = [];
        foreach ($data as $key => $val) {
            array_push($result, $key . "=" . urlencode($val));
        }
        return join("&", $result);
    }

    function verifyEcdsa(string $message, string $timestamp, string $signature): bool
    {
        $message = hash("sha256", hash("sha256", "$message|$timestamp", True), True);
        $ec = new EC('secp256k1');
        $key = $ec->keyFromPublic($this->coboPub, "hex");
        return $key->verify(bin2hex($message), $signature);
    }

    /***
     * create binding
     * @param string $userId
     * @return mixed|string
     */
    function createBinding(string $userId)
    {
        $params = [
            "user_id" => $userId,
        ];
        return $this->request("POST", "/v1/custody/auth/create_binding/", $params);
    }

    /***
     * query binding
     * @param string $binderId
     * @return mixed|string
     */
    function queryBinding(string $binderId)
    {
        $params = [
            "binder_id" => $binderId,
        ];
        return $this->request("GET", "/v1/custody/auth/query_binding/", $params);
    }
    
    /***
     * query user auth
     * @param string $userId
     * @return mixed|string
     */
    function queryUserAuth(string $userId)
    {
        $params = [
            "user_id" => $userId,
        ];
        return $this->request("GET", "/v1/custody/auth/query_user_auth/", $params);
    }

    /***
     * bind addresses
     * @param string $userId
     * @param string $addresses
     * @return mixed|string
     */
    function bindAddresses(string $userId, string $addresses)
    {
        $params = [
            "user_id" => $userId,
            "addresses" => $addresses,
        ];
        return $this->request("POST", "/v1/custody/auth/bind_addresses/", $params);
    }

    /***
     * change binding
     * @param string $userId
     * @return mixed|string
     */
    function changeBinding(string $userId)
    {
        $params = [
            "user_id" => $userId,
        ];
        return $this->request("POST", "/v1/custody/auth/change_binding/", $params);
    }

    /***
     * unbind binding
     * @param string $userId
     * @return mixed|string
     */
    function unbindBinding(string $userId)
    {
        $params = [
            "user_id" => $userId,
        ];
        return $this->request("POST", "/v1/custody/auth/unbind_binding/", $params);
    }

    /***
     * query statement
     * @param string $statementId
     * @return mixed|string
     */
    function queryStatement(string $statementId)
    {
        $params = [
            "statement_id" => $statementId,
        ];
        return $this->request("GET", "/v1/custody/auth/query_statement/", $params);
    }
}
