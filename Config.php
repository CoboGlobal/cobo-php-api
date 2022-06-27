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
        "cobo_id" => "20220314181458000331767000003732",
        "tx_id" => "0x1c4d137bc2a2ee8f22cbdf9e90405974e72e65d922f42eb81d9f7a05d0f64fc6",
        "pending_id" => "20200604171238000354106000006405",
        "withdraw_id" => "web_send_by_user_915_1647252768642",
        "deposit_address" => "3JBYNrbB4bHtGWHTEa3ZPuRK9kwTiEUo4D",
    ];
    const PROD_DATA = [
        "cobo_id" => "20220311154108000184408000002833",
        "tx_id" => "4041A888C9966BE8916FE65F2FEE7AE9A9DC3F49D0F1643A768C842CA95FA736",
        "pending_id" => "20200604171238000354106000006405",
        "withdraw_id" => "sdk_request_id_fe80cc5f_1647068483396",
        "deposit_address" => "36xYx7vf7DUKpJDixpY3EoV2jchFwYSNCb",
    ];
}