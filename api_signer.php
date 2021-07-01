<?php
namespace Cobo\Custody;

interface ApiSigner
{
    public function sign($message): string;

    public function getPublicKey(): string;
}