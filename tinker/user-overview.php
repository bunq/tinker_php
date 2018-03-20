#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use bunq\tinker\BunqLib;
use bunq\tinker\SharedLib;

$allOption = getopt('', SharedLib::ALL_OPTION_KEY);
$environment = SharedLib::determineEnvironmentType($allOption);

SharedLib::printHeader();

$bunq = new BunqLib($environment);

/**
 * Get current user and print it.
 */
$user = $bunq->getCurrentUser();
SharedLib::printUser($user);

/**
 * Get the bank accounts of the current user and print it.
 */
const NUMBER_OF_BANK_ACCOUNTS_SHOWN = 1;
$monetaryAccounts = $bunq->getAllActiveBankAccount(NUMBER_OF_BANK_ACCOUNTS_SHOWN);
SharedLib::printAllBankAccount($monetaryAccounts, NUMBER_OF_BANK_ACCOUNTS_SHOWN);

/**
 * Get payments of the current user.
 */
const NUMBER_OF_PAYMENTS_SHOWN = 1;
$payments = $bunq->getAllPayment($monetaryAccounts[0], NUMBER_OF_PAYMENTS_SHOWN);
SharedLib::printAllPayment($payments);

/**
 * Get requests of the current user.
 */
const NUMBER_OF_REQUESTS_SHOWN = 1;
$requests = $bunq->getAllRequest($monetaryAccounts[0], 1);
SharedLib::printAllRequest($requests);

/**
 * Get cards of the current user.
 */
const NUMBER_OF_CARDS_SHOWN = 1;
$cards = $bunq->getAllCard(NUMBER_OF_CARDS_SHOWN);
SharedLib::printAllCard($cards, $monetaryAccounts, NUMBER_OF_CARDS_SHOWN);

if (\bunq\Util\BunqEnumApiEnvironmentType::SANDBOX()->equals($environment)) {
    $allUserAlias = $bunq->getAllUserAlias();
    SharedLib::printAllUserAlias($allUserAlias);
}

echo <<<EOL


   Want to see more monetary accounts, payments, requests or even cards?
   Adjust this file.



EOL;

// Save the API context to account for all the changes that might have occurred to it during the example execution
$bunq->updateContext();
