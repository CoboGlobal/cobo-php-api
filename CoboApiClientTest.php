<?php

use BI\BigInteger;
use PHPUnit\Framework\TestCase;

require "LocalSigner.php";
require "CoboApiClient.php";
require "Config.php";

class CoboApiClientTest extends TestCase
{
    const apiKey = "0397ef0d81938bcf9587466ee33ab93caa77677416ada3297e70e92aa42245d99e";
    const apiSecret = "e7e73fabdd9edb8bddf947954c400a63bf93edc57abf170544ec570757df5453";
    const coboPub = "032f45930f652d72e0c90f71869dfe9af7d713b1f67dc2f7cb51f9572778b9c876";
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $signer = new LocalSigner(self::apiSecret);
        $this->client = new CoboApiClient($signer, self::apiKey, self::coboPub, Config::HOST_SANDBOX);
    }


    /**
     * @throws Exception
     */
    public function testGetAddressHistoryList()
    {
        $res = $this->client->getAddressHistoryList("ETH");
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testGetStakingProductList()
    {
        $res = $this->client->getStakingProductList(null, "zh");
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testGetStakingHistory()
    {
        $res = $this->client->getStakingHistory([]);
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testGetPendingDepositDetails()
    {
        $res = $this->client->getPendingDepositDetails("20200604171238000354106000006405");
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testUnstake()
    {
        $res = $this->client->unstake("159165", new BigInteger("1000000"));
        $this->assertTrue(true);
    }

    /**
     * @throws Exception
     */
    public function testWithdraw()
    {
        $res = $this->client->withdraw("TETH",
            "request_id_" . time(),
            "0xb744adc8d75e115eec8e582eb5e8d60eb0972037",
            new BigInteger("1"));
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testGetPendingTransactions()
    {
        $res = $this->client->getPendingTransactions([]);
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testGetUnstakingData()
    {
        $res = $this->client->getUnstakingData();
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testCheckLoopAddressDetails()
    {
        $res = $this->client->checkLoopAddressDetails("ETH", "0x05325e6f9d1f0437bd78a72c2ae084fbb8c039ee");
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testGetWithdrawInfo()
    {
        $res = $this->client->getWithdrawInfo("teth29374893624");
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testGetTransactionsByTime()
    {
        $res = $this->client->getTransactionsByTime();
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testGetAccountInfo()
    {
        $res = $this->client->getAccountInfo();
        $this->assertTrue($res->success);

    }

    /**
     * @throws Exception
     */
    public function testBatchNewDepositAddress()
    {
        $res = $this->client->batchNewDepositAddress("ETH", 4);
        $this->assertTrue($res->success);

    }

    /**
     * @throws Exception
     */
    public function testGetTransactionHistory()
    {
        $res = $this->client->getTransactionHistory();
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testGetStakingProductDetails()
    {
        $products = $this->client->getStakingProductList()->result;
        $res = $this->client->getStakingProductDetails($products[0]->product_id);
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testGetStakingData()
    {
        $res = $this->client->getStakingData([]);
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testVerifyLoopAddressList()
    {
        $addresses = [
            "0x05325e6f9d1f0437bd78a72c2ae084fbb8c039ee",
            "0xe105a42297428575086387de415900a08765a8af",
            "0x641733cde30e99fe0d6082c2ed96601c37a1718b",
            "0xf3a4a281e92631cb06b53895b6db25c6ffcf7c3d"
        ];

        $res = $this->client->verifyLoopAddressList("ETH", join(",", $addresses));
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testNewDepositAddress()
    {
        $res = $this->client->newDepositAddress("BTC");
        $this->assertTrue($res->success);

    }

    /**
     * @throws Exception
     */
    public function testVerifyDepositAddress()
    {
        $res = $this->client->verifyValidAddress("BTC", "3Qd8ZV4DWxMPK1HfitxccZXV2H8mCST3kM");
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testGetTransactionsById()
    {
        $res = $this->client->getTransactionsById();
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testGetTransactionDetails()
    {
        $res = $this->client->getTransactionDetails("20210422193807000343569000002370");
        $this->assertTrue($res->success);

    }

    /**
     * @throws Exception
     */
    public function testStake()
    {
        $products = $this->client->getStakingProductList("DASH");
        $this->client->stake($products->result[0]->product_id, new BigInteger("100000"));
        $this->assertTrue(true);
    }

    /**
     * @throws Exception
     */
    public function testGetCoinDetails()
    {
        $res = $this->client->getCoinDetails("ETH");
        $this->assertTrue($res->success);

    }

    /**
     * @throws Exception
     */
    public function testVerifyValidAddress()
    {
        $res = $this->client->verifyValidAddress("BTC", "3Qd8ZV4DWxMPK1HfitxccZXV2H8mCST3kM");
        $this->assertTrue($res->result);

        $res = $this->client->verifyValidAddress("BTC", "3Qd8ZV4DWxMPK1HfitxccZXV2H8mCST3kL");
        $this->assertFalse($res->result);
    }

    function printdoc(string $desc,string $code, $res) {

    }
}
