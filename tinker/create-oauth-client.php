#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use bunq\Context\ApiContext;
use bunq\Context\BunqContext;
use bunq\Model\Core\BunqEnumOauthResponseType;
use bunq\Model\Core\OauthAuthorizationUri;
use bunq\Model\Generated\Endpoint\OauthCallbackUrl;
use bunq\Model\Generated\Endpoint\OauthClient;
use bunq\tinker\SharedLib;
use bunq\Util\FileUtil;

/**
 * File constants.
 */
const FILE_OAUTH_CONFIGURATION = 'tinker/data/oauth.conf';

/**
 * Option constants.
 */
const OPTION_CONTEXT = 'context';
const OPTION_REDIRECT_URI = 'redirect';

const ALL_OAUTH_CONFIGURATION_OPTION = [
    OPTION_CONTEXT . SharedLib::OPTION_VALUE_REQUIRED,
    OPTION_REDIRECT_URI . SharedLib::OPTION_VALUE_REQUIRED
];

$allOption = getopt('', ALL_OAUTH_CONFIGURATION_OPTION);

SharedLib::printHeader();

echo <<<EOL

  | Checking for ApiContext file.
   
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

// Try to load oauth client
if (file_exists(FILE_OAUTH_CONFIGURATION)) {
    $oauthClient = FileUtil::readObjectFromJsonFile(FILE_OAUTH_CONFIGURATION, OauthClient::class);
} else {
    // Create the OauthClient
    $oauthClientIdResponse = OauthClient::create();
    $oauthClient = OauthClient::get($oauthClientIdResponse->getValue())->getValue();

    // Store the oauth details
    try {
        FileUtil::saveObjectAsJson(FILE_OAUTH_CONFIGURATION, $oauthClient);
    } catch (\bunq\Exception\BunqException $e) {
        error_log($e->getTraceAsString());
    }

    // Create the callback
    OauthCallbackUrl::create(
        $oauthClient->getId(),
        $allOption[OPTION_REDIRECT_URI]
    );
}

// We're set up correctly now. Continue by authorizing the user.
$oauthAuthorizationUri = OauthAuthorizationUri::create(
    BunqEnumOauthResponseType::CODE(),
    $allOption[OPTION_REDIRECT_URI],
    $oauthClient
);

echo <<<EOL
  | Got an OAuth client!

  | Point your user to {$oauthAuthorizationUri->getAuthorizationUriString()}.

  | ✅  Obtain the code from the redirect URL.

  | ▶️  Continue with test-oauth.php


EOL;
