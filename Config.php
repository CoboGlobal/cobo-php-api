<?php

namespace Cobo\Custody;

class Config
{
    const PROD = [
        "host" => "https://api.custody.cobo.com",
        "coboPub" => "02c3e5bacf436fbf4da78597e791579f022a2e85073ae36c54a361ff97f2811376"
    ];

    const DEV = [
        "host" => "https://api.dev.cobo.com",
        "coboPub" => "03596da539963fb1dd29d5859e25903eb76b9f7ed2d58516e29c9f80c201ff2c1b"
    ];

    const PROD_DATA = [
        "cobo_id" => "20220311154108000184408000002833",
        "tx_id" => "4041A888C9966BE8916FE65F2FEE7AE9A9DC3F49D0F1643A768C842CA95FA736",
        "pending_id" => "20200604171238000354106000006405",
        "withdraw_id" => "sdk_request_id_fe80cc5f_1647068483396",
        "deposit_address" => "36xYx7vf7DUKpJDixpY3EoV2jchFwYSNCb",
    ];
    const DEV_DATA = [
        "cobo_id" => "20231213152104000114035000006167",
        "tx_id" => "332d0377c0cc08bc9f9d5b07320add949e30d8da0b5fea5140de63e3779101a0",
        "pending_id" => "20230613161855000121785000007181",
        "withdraw_id" => "82ddd375-901a-4d0f-81a4-36d04fbc69a4",
        "deposit_address" => "3HMVjbnkFqg6pD1cJ7PZeLsFkNGDh9Nqy2",
    ];
}