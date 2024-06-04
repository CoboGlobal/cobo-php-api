<?php

namespace Cobo\Custody;

use BI\BigInteger;
use Elliptic\EC;
use PHPUnit\Runner\Exception;


class MPCClient
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
        $nonce = (float) sprintf('%.0f', (floatval($microsecond) + floatval($second)) * 1000);
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
     * get supported chains
     * @return mixed|string
     */
    function getSupportedChains()
    {
        return $this->request("GET", "/v1/custody/mpc/get_supported_chains/", []);
    }

    /***
     * get supported coins
     * @param string $chainCode
     * @return mixed|string
     */
    function getSupportedCoins(string $chainCode)
    {
        $params = [
            "chain_code" => $chainCode,
        ];
        return $this->request("GET", "/v1/custody/mpc/get_supported_coins/", $params);
    }

    /***
     * get supported nft collections
     * @param string $chainCode
     * @return mixed|string
     */
    function getSupportedNftCollections(string $chainCode)
    {
        $params = [
            "chain_code" => $chainCode,
        ];
        return $this->request("GET", "/v1/custody/mpc/get_supported_nft_collections/", $params);
    }

    /***
     * get wallet supported coins
     * @return mixed|string
     */
    function getWalletSupportedCoins()
    {
        return $this->request("GET", "/v1/custody/mpc/get_wallet_supported_coins/", []);
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
        return $this->request("GET", "/v1/custody/mpc/coin_info/", $params);
    }

    /***
     * check valid address
     * @param string $coin
     * @param string $address
     * @return mixed|string
     */
    function isValidAddress(string $coin, string $address)
    {
        $params = [
            "coin" => $coin,
            "address" => $address,
        ];
        return $this->request("GET", "/v1/custody/mpc/is_valid_address/", $params);
    }

    /***
     * get main address
     * string $chainCode
     * @return mixed|string
     */
    function getMainAddress(string $chainCode)
    {
        $params = [
            "chain_code" => $chainCode,
        ];
        return $this->request("GET", "/v1/custody/mpc/get_main_address/", $params);
    }

    /***
     * generate address
     * string $chainCode
     * int $count
     * @return mixed|string
     */
    function generateAddresses(string $chainCode, int $count, int $encoding = null)
    {
        $params = [
            "chain_code" => $chainCode,
            "count" => $count,
        ];
        if ($encoding) {
            $params = array_merge($params, ["encoding" => $encoding]);
        }
        return $this->request("POST", "/v1/custody/mpc/generate_addresses/", $params);
    }

    /***
     * generate address
     * string $chainCode
     * string $address
     * int $count
     * @return mixed|string
     */
    function generateAddressMemo(string $chainCode, string $address, int $count)
    {
        $params = [
            "chain_code" => $chainCode,
            "address" => $address,
            "count" => $count,
        ];
        return $this->request("POST", "/v1/custody/mpc/generate_address_memo/", $params);
    }

    /***
     * update address description
     * string $coin
     * string $address
     * string $description
     * @return mixed|string
     */
    function updateAddressDescription(string $coin, string $address, string $description)
    {
        $params = [
            "coin" => $coin,
            "address" => $address,
            "description" => $description,
        ];
        return $this->request("POST", "/v1/custody/mpc/update_address_description/", $params);
    }

    /***
     * list addresses
     * string $chainCode
     * string $startId
     * string $endId
     * int $limit
     * int $sort 0:DESCENDING 1:ASCENDING
     * @return mixed|string
     */
    function listAddresses(string $chainCode, string $startId = null, string $endId = null, int $limit = 50, int $sort = null)
    {
        $params = [
            "chain_code" => $chainCode,
            "limit" => $limit,
        ];

        if ($startId) {
            $params = array_merge($params, ["start_id" => $startId]);
        }
        if ($endId) {
            $params = array_merge($params, ["end_id" => $endId]);
        }
        if ($sort) {
            $params = array_merge($params, ["sort" => $sort]);
        }

        return $this->request("GET", "/v1/custody/mpc/list_addresses/", $params);
    }

    /***
     * get balance
     * string $address
     * string $chainCode
     * string $coin
     * @return mixed|string
     */
    function getBalance(string $address, string $chainCode = null, string $coin = null)
    {
        $params = [
            "address" => $address,
        ];

        if ($chainCode) {
            $params = array_merge($params, ["chain_code" => $chainCode]);
        }
        if ($coin) {
            $params = array_merge($params, ["coin" => $coin]);
        }

        return $this->request("GET", "/v1/custody/mpc/get_balance/", $params);
    }

    /***
     * list balances
     * string $pageIndex
     * string $pageLength
     * string $coin
     * string $chainCode
     * @return mixed|string
     */
    function listBalances(int $pageIndex, int $pageLength, string $coin = null, string $chainCode = null)
    {
        $params = [
            "page_index" => $pageIndex,
            "page_length" => $pageLength,
        ];

        if ($coin) {
            $params = array_merge($params, ["coin" => $coin]);
        }
        if ($chainCode) {
            $params = array_merge($params, ["chain_code" => $chainCode]);
        }

        return $this->request("GET", "/v1/custody/mpc/list_balances/", $params);
    }

    /***
     * list spendable
     * string $coin
     * string $address
     * @return mixed|string
     */
    function listSpendable(string $coin, string $address = null)
    {
        $params = [
            "coin" => $coin,
        ];

        if ($address) {
            $params = array_merge($params, ["address" => $address]);
        }

        return $this->request("GET", "/v1/custody/mpc/list_spendable/", $params);
    }

    /***
     * create transaction
     * string $coin
     * string $requestId
     * BigInteger $amount
     * string $fromAddr
     * string $toAddr
     * string $toAddressDetails
     * string $fee
     * BigInteger $gasPrice
     * BigInteger $gasLimit
     * int $operation
     * string $extraParameters
     * BigInteger $feeAmount
     * @return mixed|string
     */
    function createTransaction(string $coin, string $requestId, BigInteger $amount = null, string $fromAddr = null,
        string $toAddr = null, string $toAddressDetails = null, BigInteger $fee = null,
        BigInteger $gasPrice = null, BigInteger $gasLimit = null, int $operation = null,
        string $extraParameters = null, BigInteger $maxFee = null, BigInteger $maxPriorityFee = null,
        BigInteger $feeAmount = null, string $remark = null, int $autoFuel = null, string $memo = null)
    {
        $params = [
            "coin" => $coin,
            "request_id" => $requestId,
        ];

        if ($amount) {
            $params = array_merge($params, ["amount" => $amount->toString()]);
        }
        if ($fromAddr) {
            $params = array_merge($params, ["from_address" => $fromAddr]);
        }
        if ($toAddr) {
            $params = array_merge($params, ["to_address" => $toAddr]);
        }
        if ($toAddressDetails) {
            $params = array_merge($params, ["to_address_details" => $toAddressDetails]);
        }
        if ($fee) {
            $params = array_merge($params, ["fee" => $fee]);
        }
        if ($gasPrice) {
            $params = array_merge($params, ["gas_price" => $gasPrice->toString()]);
        }
        if ($gasLimit) {
            $params = array_merge($params, ["gas_limit" => $gasLimit->toString()]);
        }
        if ($operation) {
            $params = array_merge($params, ["operation" => $operation]);
        }
        if ($extraParameters) {
            $params = array_merge($params, ["extra_parameters" => $extraParameters]);
        }
        if ($maxFee) {
            $params = array_merge($params, ["max_fee" => $maxFee->toString()]);
        }
        if ($maxPriorityFee) {
            $params = array_merge($params, ["max_priority_fee" => $maxPriorityFee->toString()]);
        }
        if ($feeAmount) {
            $params = array_merge($params, ["fee_amount" => $feeAmount->toString()]);
        }
        if ($remark) {
            $params = array_merge($params, ["remark" => $remark]);
        }
        if ($autoFuel) {
            $params = array_merge($params, ["auto_fuel" => $autoFuel]);
        }
        if ($memo) {
            $params = array_merge($params, ["memo" => $memo]);
        }

        return $this->request("POST", "/v1/custody/mpc/create_transaction/", $params);
    }

    /***
     * sign message
     * string $chainCode
     * string $requestId
     * string $fromAddr
     * int $signVersion
     * string $extraParameters
     * @return mixed|string
     */
    function signMessage(string $chainCode, string $requestId, string $fromAddr,
        int $signVersion, string $extraParameters)
    {
        $params = [
            "chain_code" => $chainCode,
            "request_id" => $requestId,
            "from_address" => $fromAddr,
            "sign_version" => $signVersion,
            "extra_parameters" => $extraParameters,
        ];

        return $this->request("POST", "/v1/custody/mpc/sign_message/", $params);
    }

    /***
     * drop transaction
     * string $coboId
     * string $requestId
     * string $fee
     * BigInteger $gasPrice
     * BigInteger $gasLimit
     * BigInteger $feeAmount
     * string $extraParameters
     * @return mixed|string
     */
    function dropTransaction(string $coboId, string $requestId, string $fee = null, BigInteger $gasPrice = null,
        BigInteger $gasLimit = null, BigInteger $feeAmount = null, int $autoFuel = null, string $extraParameters = null)
    {
        $params = [
            "cobo_id" => $coboId,
            "request_id" => $requestId,
        ];

        if ($fee) {
            $params = array_merge($params, ["fee" => $fee]);
        }
        if ($gasPrice) {
            $params = array_merge($params, ["gas_price" => $gasPrice->toString()]);
        }
        if ($gasLimit) {
            $params = array_merge($params, ["gas_limit" => $gasLimit->toString()]);
        }
        if ($feeAmount) {
            $params = array_merge($params, ["fee_amount" => $feeAmount->toString()]);
        }
        if ($autoFuel) {
            $params = array_merge($params, ["auto_fuel" => $autoFuel]);
        }
        if ($extraParameters) {
            $params = array_merge($params, ["extra_parameters" => $extraParameters]);
        }

        return $this->request("POST", "/v1/custody/mpc/drop_transaction/", $params);
    }

    /***
     * speedup transaction
     * string $coboId
     * string $requestId
     * string $fee
     * BigInteger $gasPrice
     * BigInteger $gasLimit
     * BigInteger $feeAmount
     * string $extraParameters
     * @return mixed|string
     */
    function speedupTransaction(string $coboId, string $requestId, string $fee = null, BigInteger $gasPrice = null,
        BigInteger $gasLimit = null, BigInteger $feeAmount = null, int $autoFuel = null, string $extraParameters = null)
    {
        $params = [
            "cobo_id" => $coboId,
            "request_id" => $requestId,
        ];

        if ($fee) {
            $params = array_merge($params, ["fee" => $fee]);
        }
        if ($gasPrice) {
            $params = array_merge($params, ["gas_price" => $gasPrice->toString()]);
        }
        if ($gasLimit) {
            $params = array_merge($params, ["gas_limit" => $gasLimit->toString()]);
        }
        if ($feeAmount) {
            $params = array_merge($params, ["fee_amount" => $feeAmount->toString()]);
        }
        if ($autoFuel) {
            $params = array_merge($params, ["auto_fuel" => $autoFuel]);
        }
        if ($extraParameters) {
            $params = array_merge($params, ["extra_parameters" => $extraParameters]);
        }

        return $this->request("POST", "/v1/custody/mpc/speedup_transaction/", $params);
    }

    /***
     * transactions by requestIds
     * string $requestIds
     * int $status
     * @return mixed|string
     */
    function transactionsByRequestIds(string $requestIds, int $status = null)
    {
        $params = [
            "request_ids" => $requestIds,
        ];

        if ($status) {
            $params = array_merge($params, ["status" => $status]);
        }

        return $this->request("GET", "/v1/custody/mpc/transactions_by_request_ids/", $params);
    }

    /***
     * transactions by coboIds
     * string $coboIds
     * int $status
     * @return mixed|string
     */
    function transactionsByCoboIds(string $coboIds, int $status = null)
    {
        $params = [
            "cobo_ids" => $coboIds,
        ];

        if ($status) {
            $params = array_merge($params, ["status" => $status]);
        }

        return $this->request("GET", "/v1/custody/mpc/transactions_by_cobo_ids/", $params);
    }


    /***
     * transactions by coboIds
     * string $txHash
     * int $transactionType
     * @return mixed|string
     */
    function transactionsByTxHash(string $txHash, int $transactionType = null)
    {
        $params = [
            "tx_hash" => $txHash,
        ];

        if ($transactionType) {
            $params = array_merge($params, ["transaction_type" => $transactionType]);
        }

        return $this->request("GET", "/v1/custody/mpc/transactions_by_tx_hash/", $params);
    }

    /***
     * list transactions
     * int $startTime
     * int $endTime
     * int $status
     * string $order
     * string $order_by
     * int $transactionType
     * string $coins
     * string $fromAddress
     * string $toAddress
     * int $limit
     * @return mixed|string
     */
    function listTransactions(int $startTime = null, int $endTime = null, int $status = null, string $order = null,
        string $order_by = null, int $transactionType = null, string $coins = null, string $fromAddress = null,
        string $toAddress = null, int $limit = 50)
    {
        $params = [
            "limit" => $limit,
        ];

        if ($startTime) {
            $params = array_merge($params, ["start_time" => $startTime]);
        }
        if ($endTime) {
            $params = array_merge($params, ["end_time" => $endTime]);
        }
        if ($status) {
            $params = array_merge($params, ["status" => $status]);
        }
        if ($order) {
            $params = array_merge($params, ["order" => $order]);
        }
        if ($order_by) {
            $params = array_merge($params, ["order_by" => $order_by]);
        }
        if ($transactionType) {
            $params = array_merge($params, ["transaction_type" => $transactionType]);
        }
        if ($coins) {
            $params = array_merge($params, ["coins" => $coins]);
        }
        if ($fromAddress) {
            $params = array_merge($params, ["from_address" => $fromAddress]);
        }
        if ($toAddress) {
            $params = array_merge($params, ["to_address" => $toAddress]);
        }

        return $this->request("GET", "/v1/custody/mpc/list_transactions/", $params);
    }

    /***
     * estimate fee
     * string $coin
     * BigInteger $amount
     * string $address
     * @return mixed|string
     */
    function estimateFee(string $coin, BigInteger $amount = null, string $address = null, string $replaceCoboId = null, string $fromAddress = null,
        string $toAddressDetails = null, string $fee = null, BigInteger $gasPrice = null, BigInteger $gasLimit = null,
        string $extraParameters = null)
    {
        $params = [
            "coin" => $coin,
        ];

        if ($amount) {
            $params = array_merge($params, ["amount" => $amount->toString()]);
        }
        if ($address) {
            $params = array_merge($params, ["address" => $address]);
        }
        if ($replaceCoboId) {
            $params = array_merge($params, ["replace_cobo_id" => $replaceCoboId]);
        }
        if ($fromAddress) {
            $params = array_merge($params, ["from_address" => $fromAddress]);
        }
        if ($toAddressDetails) {
            $params = array_merge($params, ["to_address_details" => $toAddressDetails]);
        }
        if ($fee) {
            $params = array_merge($params, ["fee" => $fee]);
        }
        if ($gasPrice) {
            $params = array_merge($params, ["gas_price" => $gasPrice->toString()]);
        }
        if ($gasLimit) {
            $params = array_merge($params, ["gas_limit" => $gasLimit->toString()]);
        }
        if ($extraParameters) {
            $params = array_merge($params, ["extra_parameters" => $extraParameters]);
        }

        return $this->request("GET", "/v1/custody/mpc/estimate_fee/", $params);
    }

    /***
     * list tss node requests
     * int $requestType
     * int $status
     * string $address
     * @return mixed|string
     */
    function listTssNodeRequests(int $requestType = null, int $status = null)
    {
        $params = [
            "request_type" => $requestType,
        ];

        if ($status) {
            $params = array_merge($params, ["status" => $status]);
        }

        return $this->request("GET", "/v1/custody/mpc/list_tss_node_requests/", $params);
    }

    /***
     * list tss node
     * @return mixed|string
     */
    function listTssNode()
    {
        return $this->request("GET", "/v1/custody/mpc/list_tss_node/", []);
    }

    /***
     * sign messages by requestIds
     * string $requestIds
     * @return mixed|string
     */
    function signMessageByRequestIds(string $requestIds)
    {
        $params = [
            "request_ids" => $requestIds,
        ];

        return $this->request("GET", "/v1/custody/mpc/sign_messages_by_request_ids/", $params);
    }

    /***
     * sign messages by coboIds
     * string $coboIds
     * @return mixed|string
     */
    function signMessageByCoboIds(string $coboIds)
    {
        $params = [
            "cobo_ids" => $coboIds,
        ];

        return $this->request("GET", "/v1/custody/mpc/sign_messages_by_cobo_ids/", $params);
    }

    /***
     * retry double check
     * string $requestId
     * @return mixed|string
     */
    function retryDoubleCheck(string $requestId)
    {
        $params = [
            "request_id" => $requestId,
        ];

        return $this->request("POST", "/v1/custody/mpc/retry_double_check/", $params);
    }

    function getMaxSendAmount(string $coin, string $fee_rate, string $to_address, string $from_address = null)
    {
        $params = [
            "coin" => $coin,
            "fee_rate" => $fee_rate,
            "to_address" => $to_address
        ];

        if ($from_address) {
            $params = array_merge($params, ["from_address" => $from_address]);
        }

        return $this->request("GET", "/v1/custody/mpc/get_max_send_amount/", $params);
    }

    /***
     * lock spendable
     * string $coin
     * string $txHash
     * int $voutN
     */
    function lockSpendable(string $coin, string $txHash, int $voutN)
    {
        $params = [
            "coin" => $coin,
            "tx_hash" => $txHash,
            "vout_n" => $voutN,
        ];

        return $this->request("POST", "/v1/custody/mpc/lock_spendable/", $params);
    }

    /***
     * unlock spendable
     * string $coin
     * string $txHash
     * int $voutN
     */
    function unlockSpendable(string $coin, string $txHash, int $voutN)
    {
        $params = [
            "coin" => $coin,
            "tx_hash" => $txHash,
            "vout_n" => $voutN,
        ];

        return $this->request("POST", "/v1/custody/mpc/unlock_spendable/", $params);
    }

    /***
     * get rare satoshis
     * string $coin
     * string $txHash
     * int $voutN
     */
    function getRareSatoshis(string $coin, string $txHash, int $voutN)
    {
        $params = [
            "coin" => $coin,
            "tx_hash" => $txHash,
            "vout_n" => $voutN,
        ];

        return $this->request("GET", "/v1/custody/mpc/get_rare_satoshis/", $params);
    }

    function getUTXOAssets(string $coin, string $txHash, int $voutN)
    {
        $params = [
            "coin" => $coin,
            "tx_hash" => $txHash,
            "vout_n" => $voutN,
        ];

        return $this->request("GET", "/v1/custody/mpc/get_utxo_assets/", $params);
    }

    function getOrdinalsInscription(string $inscriptionId)
    {
        $params = [
            "inscription_id" => $inscriptionId,
        ];

        return $this->request("GET", "/v1/custody/mpc/get_ordinals_inscription/", $params);
    }

    function babylonPrepareStaking(string $requestId, string $stakeInfo, string $feeRate, BigInteger $maxStakingFee = null)
    {
        $params = [
            "request_id" => $requestId,
            "stake_info" => $stakeInfo,
            "fee_rate" => $feeRate,
        ];

        if ($maxStakingFee) {
            $params = array_merge($params, ["max_staking_fee" => $maxStakingFee]);
        }

        return $this->request("POST", "/v1/custody/mpc/babylon/prepare_staking/", $params);
    }

    function babylonReplaceStakingFee(string $requestId, string $relatedRequestId, string $feeRate, BigInteger $maxStakingFee = null)
    {
        $params = [
            "request_id" => $requestId,
            "related_request_id" => $relatedRequestId,
            "fee_rate" => $feeRate,
        ];

        if ($maxStakingFee) {
            $params = array_merge($params, ["max_staking_fee" => $maxStakingFee]);
        }

        return $this->request("POST", "/v1/custody/mpc/babylon/replace_staking_fee/", $params);
    }

    function babylonBroadcastStakingTransaction(string $requestId)
    {
        $params = [
            "request_id" => $requestId,
        ];

        return $this->request("POST", "/v1/custody/mpc/babylon/broadcast_staking_transaction/", $params);
    }

    function babylonGetStakingInfo(string $requestId)
    {
        $params = [
            "request_id" => $requestId,
        ];

        return $this->request("GET", "/v1/custody/mpc/babylon/get_staking_info/", $params);
    }

    function babylonListWaitingBroadcastTransactions(string $coin, string $address)
    {
        $params = [
            "asset_coin" => $coin,
            "address" => $address,
        ];

        return $this->request("GET", "/v1/custody/mpc/babylon/list_waiting_broadcast_transactions/", $params);
    }

    function getApprovalDetails(string $requestId)
    {
        $params = [
            "request_id" => $requestId,
        ];

        return $this->request("GET", "/v1/custody/mpc/get_approval_details/", $params);
    }
}