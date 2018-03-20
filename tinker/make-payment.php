#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use bunq\tinker\BunqLib;
use bunq\tinker\SharedLib;

$allOption = getopt('', SharedLib::ALL_OPTION_KEY);
$environment = SharedLib::determineEnvironmentType($allOption);

SharedLib::printHeader();

$bunq = new BunqLib($environment);

$amount = SharedLib::getAmountFromAllOptionOrStdIn($allOption);
$description = SharedLib::getDescriptionFromAllOptionOrStdIn($allOption);
$recipient = SharedLib::getRecipientFromAllOptionOrStdIn($allOption);

echo <<<EOL

  | Sending:      € {$amount}
  | To:           {$recipient}
  | Description:  {$description}
   
    ...

EOL;

$bankAccounts = $bunq->getAllActiveBankAccount();
$bunq->makePayment($amount, $recipient, $description, $bankAccounts[0]);

echo <<<EOL

  | ✅  Payment sent

  | ▶️  Check your changed overview


EOL;

// Save the API context to account for all the changes that might have occurred to it during the example execution
$bunq->updateContext();
