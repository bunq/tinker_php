#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use bunq\Context\ApiContext;
use bunq\tinker\SharedLib;
use bunq\Util\SecurityUtil;

/**
 * API constants.
 */
const API_DEVICE_DESCRIPTION = '##### YOUR DEVICE DESCRIPTION #####';

/**
 * Option constants.
 */
const OPTION_CERTIFICATE = 'certificate';
const OPTION_CERTIFICATE_CHAIN = 'chain';
const OPTION_PRIVATE_KEY = 'key';

const ALL_PSD2_CONFIGURATION_OPTION = [
    OPTION_CERTIFICATE . SharedLib::OPTION_VALUE_REQUIRED,
    OPTION_CERTIFICATE_CHAIN . SharedLib::OPTION_VALUE_REQUIRED,
    OPTION_PRIVATE_KEY . SharedLib::OPTION_VALUE_REQUIRED,
    SharedLib::OPTION_KEY_PRODUCTION,
];

/**
 * File constants.
 */
const FILE_PSD2_CONFIGURATION = __DIR__ . '/../psd2.conf';

$allOption = getopt('', ALL_PSD2_CONFIGURATION_OPTION);
$environment = SharedLib::determineEnvironmentType($allOption);

SharedLib::printHeader();

echo <<<EOL

  | Creating API Context for PSD2 usage.
   
    ...

EOL;

// Store the context
$apiContext = ApiContext::createForPsd2(
    $environment,
    SecurityUtil::getCertificateFromFile($allOption[OPTION_CERTIFICATE]),
    SecurityUtil::getPrivateKeyFromFile($allOption[OPTION_PRIVATE_KEY]),
    [
        SecurityUtil::getCertificateFromFile($allOption[OPTION_CERTIFICATE_CHAIN])
    ],
    API_DEVICE_DESCRIPTION
);
$apiContext->save(FILE_PSD2_CONFIGURATION);

echo <<<EOL

  | ✅  PSD2 Api Context created and saved!

  | ▶️  Continue with create-oauth-client.php


EOL;
