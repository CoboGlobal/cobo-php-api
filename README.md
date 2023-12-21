# The Official PHP SDK for Cobo WaaS API

[![License: GPL v2](https://img.shields.io/badge/License-GPL_v2-blue.svg)](https://www.gnu.org/licenses/old-licenses/gpl-2.0.en.html)
[![GitHub Release](https://img.shields.io/github/release/CoboGlobal/cobo-php-api.svg?style=flat)]()


## About

This repository contains the official PHP SDK for Cobo WaaS API, enabling developers to integrate with Cobo's Custodial
and/or MPC services seamlessly using the PHP programming language.

## Documentation

To access the API documentation, navigate to
the [API references](https://www.cobo.com/developers/api-references/overview/).

For more information on Cobo's PHP SDK, refer to
the [PHP SDK Guide](https://www.cobo.com/developers/sdks-and-tools/sdks/waas/php).

## Usage

### Before You Begin

Ensure that you have created an account and configured Cobo's Custodial and/or MPC services.
For detailed instructions, please refer to
the [Quickstart](https://www.cobo.com/developers/get-started/overview/quickstart) guide.

### Requirements

PHP 7.0 or newer.

### Installation

The cobo_custody library can be conveniently installed using Composer.

- first you need to install [Composer](https://getcomposer.org/)

- then add dependency in composer.json

```json
{
  "require": {
    "cobo/cobo_custody": "0.2.20"
  }
}
```

### Code Sample

#### Generate Key Pair

```php
<?php
require 'vendor/autoload.php';

use Cobo\Custody\Config;
use Cobo\Custody\LocalSigner;
use Cobo\Custody\Client;
$key = LocalSigner::generateKeyPair();
echo "apiSecret:", $key['apiSecret'],"\n";
echo "apiKey:", $key['apiKey'];
```

#### Initialize RestClient

```php
<?php
require 'vendor/autoload.php';

use Cobo\Custody\Config;
use Cobo\Custody\LocalSigner;
use Cobo\Custody\Client;

$client = new Client($signer, Config::DEV, false);
```

#### Initialize ApiSigner

`ApiSigner` can be instantiated through `$signer = new LocalSigner($key['apiSecret']);`

```php
$signer = new LocalSigner($key['apiSecret']);
```

In certain scenarios, the private key may be restricted from export, such as when it is stored in AWS Key Management Service (KMS). 
In such cases, please pass in a custom implementation using the ApiSigner interface:

####  Complete Code Sample
```php
<?php
require 'vendor/autoload.php';

use Cobo\Custody\Config;
use Cobo\Custody\LocalSigner;
use Cobo\Custody\Client;

$key = LocalSigner::generateKeyPair();
echo "apiSecret:", $key['apiSecret'],"\n";
echo "apiKey:", $key['apiKey'];

$signer = new LocalSigner($key['apiSecret']);
$client = new Client($signer, Config::DEV, false);
$res = $client->getAccountInfo();

?>
```

