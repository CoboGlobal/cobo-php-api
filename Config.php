<?php

namespace Cobo\Custody;


class Config
{
    const PROD = [
        "host" => "https://api.custody.cobo.com",
        "coboPub" => "02c3e5bacf436fbf4da78597e791579f022a2e85073ae36c54a361ff97f2811376"
    ];
    const SANDBOX = [
        "host" => "https://api.sandbox.cobo.com",
        "coboPub" => "032f45930f652d72e0c90f71869dfe9af7d713b1f67dc2f7cb51f9572778b9c876"
    ];

    const SANDBOX_DATA = [
        "tx_id" => "20210422193807000343569000002370",
        "pending_tx_id" => "20200604171238000354106000006405",
        "withdraw_id" => "teth29374893624",
        "deposit_address" => "0x05325e6f9d1f0437bd78a72c2ae084fbb8c039ee",
    ];
    const PROD_DATA = [
        "tx_id" => "20220311154108000184408000002833",
        "pending_tx_id" => "20200604171238000354106000006405",
        "withdraw_id" => "sdk_request_id_fe80cc5f_1647068483396",
        "deposit_address" => "0xc2451931f8569a5887a35021ea46e83f70b5801b",
    ];
}