<?php
require __DIR__ . "/vendor/autoload.php";

use BI\BigInteger;
use Cobo\Custody\Client;
use Cobo\Custody\Config;
use Cobo\Custody\LocalSigner;
use PHPUnit\Framework\TestCase;

require "LocalSigner.php";
require "Client.php";
require "Config.php";

$secret = get_cfg_var("secret");
$env = get_cfg_var("env");

class ClientTest extends TestCase
{
    private $client;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {   
        $signer = new LocalSigner($GLOBALS["secret"]);
        if($GLOBALS['env'] == "develop"){
            $env = Config::DEV;
            $this->data = Config::DEV_DATA;
        }
        elseif($GLOBALS['env'] == "prod"){
            $env = Config::PROD;
            $this->data = Config::PROD_DATA;
        }
        else{
            throw new Exception("Invalid env parameter.");
        }
        $signer = new LocalSigner($GLOBALS["secret"]);
        $this->client = new Client($signer, $env, false);
    }

    /**
     * @test
     * @throws Exception
     */
    public function testGetAccountInfo()
    {
        $res = $this->client->getAccountInfo();
        $this->assertNotEmpty($res->result->name);
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     * @test
     * @dataProvider GetValidCoinDetailsProvider
     */
    public function testGetValidCoinDetails($coin)
    {
        $res = $this->client->getCoinDetails($coin);
        $this->assertTrue($res->success);
    }

    public function GetValidCoinDetailsProvider()
    {
        return array(
            array("BTC"),
            array("ETH_USDT"),
        );
    }

    /**
     * @throws Exception
     * @dataProvider GetInvalidCoinDetails_Provider
     */
    public function GetInvalidCoinDetails($coin)
    {
        $res = $this->client->getCoinDetails($coin);
        $this->assertTrue($res->error_code, 12002);
    }

    public function GetInvalidCoinDetails_Provider()
    {
        return array(
            array("BTTB")
        );
    }

    /**
     * @throws Exception
     * @dataProvider NewValidDepositAddress_Provider
     */
    public function testNewValidDepositAddress($coin)
    {
        $res = $this->client->newDepositAddress($coin);
        $this->assertTrue($res->success);

    }

    public function NewValidDepositAddress_Provider()
    {
        return array(
            array("BTC"), 
            array("ETH"), 
            array("ETH_USDT")
        );
    }

    /**
     * @throws Exception
     * @dataProvider NewInvalidDepositAddress_Provider
     */
    public function testNewInvalidDepositAddress($coin)
    {
        $res = $this->client->newDepositAddress($coin);
        $this->assertFalse($res->success);

    }

    public function NewInvalidDepositAddress_Provider()
    {
        return array(
            array("BTTB"), 
        );
    }

    /**
     * @throws Exception
     * @dataProvider BatchValidNewDepositAddress_Provider
     */
    public function testBatchValidNewDepositAddress($coin, $count)
    {
        $res = $this->client->batchNewDepositAddress($coin, $count);
        $this->assertTrue($res->success);
    }

    public function BatchValidNewDepositAddress_Provider()
    {
        return array(
            array("ETH", 4),
            array("BTC", 2),
            array("ETH_USDT", 2)
        );
    }

    /**
     * @throws Exception
     * @dataProvider BatchInvalidNewDepositAddress_Provider
     */
    public function testBatchInvalidNewDepositAddress($coin, $count)
    {
        $res = $this->client->batchNewDepositAddress($coin, $count);
        $this->assertFalse($res->success);

    }

    public function BatchInvalidNewDepositAddress_Provider()
    {
        return array(
            array("BTTB", 4),
            array("ETTE", 2)
        );
    }

    /**
     * @throws Exception
     */
    public function testVerifyValidDepositAddress()
    {
        $res = $this->client->verifyDepositAddress("BTC", $this->data['deposit_address']);
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     * @dataProvider VerifyInvalidDepositAddress_Provider
     */
    public function testVerifyInvalidDepositAddress($coin, $address)
    {
        $res = $this->client->verifyDepositAddress($coin, $address);
        $this->assertFalse($res->success);
    }

    public function VerifyInvalidDepositAddress_Provider()
    {
        return array(
            array("BTC", "384rwCr8PHuQNTnKmThVFpyStUyfe6TjAb2"),
            array("ETH", "0x5325e6f9d1f0437bd78a72c2ae084fbb8c039ee")
        );
    }

    /**
     * @throws Exception
     * @dataProvider BatchVerifyValidDepositAddresses_Provider
     */
    public function testBatchVerifyValidDepositAddresses($addresses)
    {
        $res = $this->client->batchVerifyDepositAddresses("ETH", join(",", $addresses));
        $this->assertTrue($res->success);
    }

    public function BatchVerifyValidDepositAddresses_Provider()
    {
        return array(
            array([
                "0x05325e6f9d1f0437bd78a72c2ae084fbb8c039ee",
                "0xe105a42297428575086387de415900a08765a8af",
            ]),
            array([
                "0x641733cde30e99fe0d6082c2ed96601c37a1718b",
                "0xf3a4a281e92631cb06b53895b6db25c6ffcf7c3d"
            ])
        );
    }

    /**
     * @throws Exception
     * @dataProvider BatchVerifyInvalidDepositAddresses_Provider
     */
    public function testBatchVerifyInvalidDepositAddresses($addresses)
    {
        $res = $this->client->batchVerifyDepositAddresses("ETH", join(",", $addresses));
        $this->assertTrue($res->success);
    }

    public function BatchVerifyInvalidDepositAddresses_Provider()
    {
        return array(
            array(
                [
                    "0x05325e6f9d1f0437bd78a72c2ae084fbb8c039ee2",
                    "0xe105a42297428575086387de415900a08765a8af0",
                ]
            ),
            array(
                [
                    "0x641733cde30e99fe0d6082c2ed96601c37a1718",
                    "0xf3a4a281e92631cb06b53895b6db25c6ffcf7c3"
                ]
            )
        );
    }

    /**
     * @throws Exception
     * @dataProvider VerifyValidAddress_Provider
     */
    public function testVerifyValidAddress($coin, $address)
    {
        $res = $this->client->verifyValidAddress($coin, $address);
        $this->assertTrue($res->result);
    }

    public function VerifyValidAddress_Provider()
    {
        return array(
            array("BTC", "3Qd8ZV4DWxMPK1HfitxccZXV2H8mCST3kM"), 
            array("ETH_USDT", "0xEEACb7a5e53600c144C0b9839A834bb4b39E540c")
        );
    }

    /**
     * @throws Exception
     * @dataProvider VerifyInvalidAddress_Provider
     */
    public function testVerifyInvalidAddress($coin, $address)
    {
        $res = $this->client->verifyValidAddress($coin, $address);
        $this->assertFalse($res->result);
    }

    public function VerifyInvalidAddress_Provider()
    {
        return array(
            array("BTC", "3Qd8ZV4DWxMPK1HfitxccZXV2H8mCST3kM0"), 
            array("BTC", "3Qd8ZV4DWxMPK1HfitxccZXV2H8mCST3kL3")
        );
    }

    /**
     * @throws Exception
     * @dataProvider GetValidAddressHistoryList_Provider
     */
    public function testGetValidAddressHistoryList($coin, $num)
    {
        $res = $this->client->getAddressHistoryList($coin);
        // $array = $res->result;
        $this->assertEquals($res->result[$num]->coin, $coin);
        $this->assertTrue($res->success);
    }

    public function GetValidAddressHistoryList_Provider()
    {
        return array(
            array("ETH", 0),
            array("BTC", 1)
        );
    }

    /**
     * @throws Exception
     * @dataProvider GetValidAddressHistoryListWithValidPage_Provider
     */
    public function testGetValidAddressHistoryListWithValidPage($coin, $pageIndex, $pageLength)
    {
        $res = $this->client->getAddressHistoryList($coin, $pageIndex, $pageLength);
        $this->assertTrue($res->success);
        $this->assertEquals(sizeof($res->result), $pageLength);

    }

    public function GetValidAddressHistoryListWithValidPage_Provider()
    {
        return array(
            array("ETH", 0, 2)
        );
    }

    /**
     * @throws Exception
     * @dataProvider GetValidAddressHistoryListWithInvalidPage_Provider
     */
    public function testGetValidAddressHistoryListWithInvalidPage($coin, $pageIndex, $pageLength, $sortFlag)
    {
        $res = $this->client->getAddressHistoryList($coin, $pageIndex, $pageLength, $sortFlag);
        $this->assertEquals($res->error_code, 1011);
        $this->assertEquals($res->error_message, "Invalid page_length value");

    }

    public function GetValidAddressHistoryListWithInvalidPage_Provider()
    {
        return array(
            array("ETH", 0, 51, 1),
            array("ETH", 0, 0, 0)
        );
    }

    /**
     * @throws Exception
     * @dataProvider GetAddressHistoryListWithPage_Provider
     */
    public function testGetAddressHistoryListWithPage($coin, $pageIndex, $pageLength, $sortFlag)
    {
        $res = $this->client->getAddressHistoryList($coin, $pageIndex, $pageLength, $sortFlag);
        $this->assertTrue($res->success);
        $this->assertEquals(sizeof($res->result), $pageLength);

    }

    public function GetAddressHistoryListWithPage_Provider()
    {
        return array(
            array("ETH", 0, 2, 0),
            array("ETH", 0, 2, 1)
        );
    }
    /**
     * @throws Exception
     * @dataProvider GetInvalidAddressHistoryList_Provider
     */
    public function testGetInvalidAddressHistoryList($coin, $num)
    {
        $res = $this->client->getAddressHistoryList($coin);
        // $array = $res->result;
        $this->assertEquals($res->error_code, 12002);
    }

    public function GetInvalidAddressHistoryList_Provider()
    {
        return array(
            array("BTTB", 0),
        );
    }

    /**
     * @throws Exception
     * @dataProvider CheckLoopAddressDetails_Provider
     */
    public function testCheckLoopAddressDetails($coin, $address)
    {
        $res = $this->client->checkLoopAddressDetails($coin, $address);
        $this->assertTrue($res->success);
    }

    public function CheckLoopAddressDetails_Provider()
    {
        return array(
            array("ETH", "0x6a33f1fb0ff76518fd7a92bdfff4eb62619639e5"),
            array("BTC", "34WLjtk9ta96BVxc1jRF7j5eVvehoftsVV")
        );
    }

    /**
     * @throws Exception
     * @dataProvider BatchCheckLoopAddressesDetails_Provider
     */
    public function testBatchCheckLoopAddressesDetails($coin, $address)
    {
        $res = $this->client->verifyLoopAddressList($coin, $address);
        $this->assertTrue($res->success);
    }

    public function BatchCheckLoopAddressesDetails_Provider()
    {
        return array(
            array("ETH", "0xe7ebdc5bbb6c99cc8f7f2c1c83ff38aa6647f38a,0xe7ebdc5bbb6c99cc8f7f2c1c83ff38aa6647f38a"),
            array("BTC", "34WLjtk9ta96BVxc1jRF7j5eVvehoftsVV,33P1kjMfDCKipR58S7XbsCqbmPT5YGrhUo")
        );
    }

    /**
     * @throws Exception
     */
    public function testGetTransactionDetails()
    {
        $res = $this->client->getTransactionDetails($this->data['cobo_id']);
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
    public function testGetTransactionsByTime()
    {
        $res = $this->client->getTransactionsByTime();
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
    public function testGetPendingDepositDetails()
    {
        $res = $this->client->getPendingTransactions([]);
        if(count($res->result)>0){
            $id = $res->result[0]->id;
            $res = $this->client->getPendingDepositDetails($id);

            $this->assertTrue($res->success);
        }
        else{
            $this->markTestSkipped("no pending transactions.");
        }
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
     * @dataProvider Withdraw_Provider
     */
    public function testWithdraw($coin, $address, $memo)
    {
        $time = time();
        $addressHash = substr(hash("sha256", $address),0,8);
        $requestId = "sdk_request_id_{$addressHash}_$time";
        $res = $this->client->withdraw($coin,
            $requestId,
            $address,
            new BigInteger("1"),
            ["memo" => $memo]);
        $this->assertTrue($res->success);
    }

    public function Withdraw_Provider()
    {
        return array(
            array("COBO_ETH", "0xE410157345be56688F43FF0D9e4B2B38Ea8F7828", ""),
            array("XLM", "GBJDU6TPWHKGV7HRLNTIBA46MG3MB5DUG6BISHX3BF7I75H2HLPV6RJX", "4e73f03b")
        );
    }

    /**
     * @throws Exception
     * @dataProvider 
     */
    public function testGetWithdrawInfo()
    {
        $res = $this->client->getWithdrawInfo($this->data["withdraw_id"]);
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
    public function testGetStakingProductList()
    {
        $res = $this->client->getStakingProductList(null, "zh");
        $this->assertTrue($res->success);
    }

    /**
     * @throws Exception
     */
    public function testGetTransactionsByTxId()
    {
        $res = $this->client->getTransactionsByTxid($this->data["tx_id"]);
        $this->assertTrue($res->success);

    }

    /**
     * @throws Exception
     */
    public function testStake()
    {
        $res = $this->client->getStakingProductList("TETH", "en");
        if(count($res->result)>0){
            $products = $this->client->getStakingProductList("TETH", "en");
            $this->client->stake($products->result[0]->product_id, new BigInteger("100000"));
            $this->assertTrue(true);
        }
        else{
            $this->markTestSkipped("no TETH staking products.");
        }
    }

    /**
     * @throws Exception
     */
    public function testUnstake()
    {
        $res = $this->client->getStakingProductList("TETH", "en");
            if(count($res->result)>0){
            $products = $this->client->getStakingProductList("TETH", "en");
            $this->client->unstake($products->result[0]->product_id, new BigInteger("100000"));
            $this->assertTrue(true);
        }
        else{
            $this->markTestSkipped("no TETH staking products.");
        }
    }

    /**
     * @throws Exception
     */
    public function testGetStakingData()
    {
        $res = $this->client->getStakingData();
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
    public function testGetStakingHistory()
    {
        $res = $this->client->getStakingHistory();
        $this->assertTrue($res->success);
    }

    public function testGenerateKeyPair()
    {
        $key = LocalSigner::generateKeyPair();
        echo "apiSecret:", $key['apiSecret'], "\n";
        echo "apiKey:", $key['apiKey'];
        $this->assertTrue(true);
    }
}
