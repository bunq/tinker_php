#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use bunq\Context\ApiContext;
use bunq\Context\BunqContext;
use bunq\Model\Core\BunqEnumOauthGrantType;
use bunq\Model\Core\OauthAccessToken;
use bunq\Model\Generated\Endpoint\MonetaryAccount;
use bunq\Model\Generated\Endpoint\OauthClient;
use bunq\tinker\BunqLib;
use bunq\tinker\SharedLib;
use bunq\Util\BunqEnumApiEnvironmentType;
use bunq\Util\FileUtil;


/**
* Option constants.
*/
const OPTION_AUTH_CODE = 'code';
const OPTION_CLIENT_CONFIGURATION = 'configuration';
const OPTION_REDIRECT = 'redirect';
const OPTION_CONTEXT = 'context';

/**
* API constants.
*/
const API_DEVICE_DESCRIPTION = '##### YOUR DEVICE DESCRIPTION #####';

const ALL_OAUTH_CONFIGURATION_OPTION = [
    OPTION_CONTEXT . SharedLib::OPTION_VALUE_REQUIRED,
    OPTION_CLIENT_CONFIGURATION . SharedLib::OPTION_VALUE_REQUIRED,
    OPTION_REDIRECT . SharedLib::OPTION_VALUE_REQUIRED,
    OPTION_AUTH_CODE . SharedLib::OPTION_VALUE_REQUIRED,
    SharedLib::OPTION_KEY_PRODUCTION
];

$allOption = getopt('', ALL_OAUTH_CONFIGURATION_OPTION);
$environment = SharedLib::determineEnvironmentType($allOption);

SharedLib::printHeader();

echo <<<EOL

  | Checking for OauthClient file.
   
    ...

EOL;

if (file_exists($allOption[OPTION_CONTEXT])) {
    BunqContext::loadApiContext(
        ApiContext::restore($allOption[OPTION_CONTEXT])
    );
} else {
    echo '  | Context not found. Exiting!';
    return;
}

$oauthClient = FileUtil::readObjectFromJsonFile($allOption[OPTION_CLIENT_CONFIGURATION], OauthClient::class);

// Obtain an access token.
$accessToken = OauthAccessToken::create(
    BunqEnumOauthGrantType::AUTHORIZATION_CODE(),
    $allOption[OPTION_AUTH_CODE],
    $allOption[OPTION_REDIRECT],
    $oauthClient
);

// The api token. Can be used as standard auth code.
// Make sure to store this token securely per user, otherwise you'll have to go through the entire authentication process over and over again.
$apiToken = $accessToken->getAccessTokenString();

// Create context using token.
$apiContext = ApiContext::create(
    BunqEnumApiEnvironmentType::SANDBOX(),
    $apiToken,
    API_DEVICE_DESCRIPTION
);
BunqContext::loadApiContext($apiContext);

echo <<<EOL
  | Got access through OAuth!
  
  | Performing test request.

EOL;

$allMonetaryAccount = MonetaryAccount::listing();
foreach ($allMonetaryAccount->getValue() as $monetaryAccount) {
    if (!is_null($monetaryAccount->getMonetaryAccountBank())) {
        echo vsprintf(
            '  | MonetaryAccountBank found with balance of: %s %s%s',
            [
                $monetaryAccount->getMonetaryAccountBank()->getBalance()->getValue(),
                $monetaryAccount->getMonetaryAccountBank()->getBalance()->getCurrency(),
                PHP_EOL,
            ]
        );
    }
}
