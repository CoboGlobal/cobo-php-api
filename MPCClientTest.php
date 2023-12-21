<?php
require __DIR__ . "/vendor/autoload.php";

use BI\BigInteger;
use Cobo\Custody\MPCClient;
use Cobo\Custody\Config;
use Cobo\Custody\LocalSigner;
use PHPUnit\Framework\TestCase;

require "LocalSigner.php";
require "MPCClient.php";
require "Config.php";

$MPCApiSecret = get_cfg_var("MPCApiSecret");
$env = get_cfg_var("env");

class MPCClientTest extends TestCase
{
    private $mpcClient;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $env = Config::DEV;
        $this->data = Config::DEV;
        $signer = new LocalSigner($GLOBALS["MPCApiSecret"]);
        // $signer = new LocalSigner("fc068aeca9fe210851362891523dfba66481564fe28e22593fd27dd16f20e01d");
        $this->mpcClient = new MPCClient($signer, $env, false);
    }

    public function testGetSupportedChains()
    {
        $res = $this->mpcClient->getSupportedChains();

        $this->assertTrue($res->success);
    }

    public function testGetSupportedCoins()
    {
        $chainCode = "GETH";
        $res = $this->mpcClient->getSupportedCoins($chainCode);

        $this->assertTrue($res->success);
    }

    public function testGetWalletSupportedCoins()
    {
        $res = $this->mpcClient->getWalletSupportedCoins();

        $this->assertTrue($res->success);
    }

    public function testIsValidAddress()
    {
        $coin = "GETH";
        $address = "0x3ede1e59a3f3a66de4260df7ba3029b515337e5c";
        $res = $this->mpcClient->isValidAddress($coin, $address);

        $this->assertTrue($res->success);
    }

    public function testGetMainAddress()
    {
        $chainCode = "GETH";
        $res = $this->mpcClient->getMainAddress($chainCode);

        $this->assertTrue($res->success);
    }

    public function testGenerateAddresses()
    {
        $chainCode = "GETH";
        $count = 2;
        $res = $this->mpcClient->generateAddresses($chainCode, $count);

        $this->assertTrue($res->success);
    }

    public function testUpdateAddressDescription()
    {
        $coin = "GETH";
        $address = "0x6a060efe0ff887f4e24dc2d2098020abf28bcce4";
        $description = "test";
        $res = $this->mpcClient->updateAddressDescription($coin, $address, $description);

        $this->assertTrue($res->success);
    }

    public function testGetListAddresses()
    {
        $chainCode = "GETH";
        $res = $this->mpcClient->listAddresses($chainCode);

        $this->assertTrue($res->success);
    }

    public function testGetBalance()
    {
        $address = "0x6a060efe0ff887f4e24dc2d2098020abf28bcce4";
        $res = $this->mpcClient->getBalance($address);

        $this->assertTrue($res->success);
    }

    public function testListBalances()
    {
        $pageIndex = 0;
        $pageLength = 50;
        $res = $this->mpcClient->listBalances($pageIndex, $pageLength);

        $this->assertTrue($res->success);
    }

    public function testListSpendable()
    {
        $coin = "GETH";
        $res = $this->mpcClient->listSpendable($coin);

        $this->assertTrue($res->success);
    }

    public function testCreateTransaction()
    {
        $coin = "GETH";
        $requestId = time();
        $fromAddr = "0x6a060efe0ff887f4e24dc2d2098020abf28bcce4";
        $toAddr = "0x6a060efe0ff887f4e24dc2d2098020abf28bcce4";
        $amount = new BigInteger("10");
        $res = $this->mpcClient->createTransaction($coin, $requestId, $amount, $fromAddr, $toAddr);

        $this->assertTrue($res->success);
    }

    public function testTransactionsByRequestIds()
    {
        $requestIds = "1668678820274";
        $res = $this->mpcClient->transactionsByRequestIds($requestIds);

        $this->assertTrue($res->success);
    }

    public function testTransactionsByCoboIds()
    {
        $coboIds = "20231213152104000114035000006167";
        $res = $this->mpcClient->transactionsByCoboIds($coboIds);

        $this->assertTrue($res->success);
    }

    public function testListTransactions()
    {
        $res = $this->mpcClient->listTransactions();

        $this->assertTrue($res->success);
    }

    public function testEstimateFee()
    {
        $coin = "GETH";
        $amount = new BigInteger("10000000");
        $address = "0xEEACb7a5e53600c144C0b9839A834bb4b39E540c";
        $res = $this->mpcClient->estimateFee($coin, $amount, $address);

        $this->assertTrue($res->success);
    }

    // public function testListTssNodeRequests()
    // {
    //     $res = $this->mpcClient->listTssNodeRequests();

    //     $this->assertTrue($res->success);
    // }

    public function testListTssNode()
    {
        $res = $this->mpcClient->listTssNode();

        $this->assertTrue($res->success);
    }

    public function testGetMaxSendAmount()
    {
        $coin = "GETH";
        $to_address = "0x6a060efe0ff887f4e24dc2d2098020abf28bcce4";
        $res = $this->mpcClient->getMaxSendAmount($coin, "0", $to_address,$to_address);

        $this->assertTrue($res->success);
    }
}