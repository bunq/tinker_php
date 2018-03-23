#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use bunq\tinker\BunqLib;
use bunq\tinker\SharedLib;

$allOption = getopt('', SharedLib::ALL_OPTION_KEY);
$environment = SharedLib::determineEnvironmentType($allOption);

SharedLib::printHeader();

$bunq = new BunqLib($environment);

$account = SharedLib::getAccountIdFromAllOptionOrStdIn($allOption, $bunq->getAllActiveBankAccount());
$name = SharedLib::getMonetaryAccountNewNameFromAllOptionOrStdIn($allOption);

echo <<<EOL

  | Updating Name:      {$name}
  | of Account:         {$account->getId()}
   
    ...

EOL;

$bunq->updateBankAccountDescription($name, $account);

echo <<<EOL

  | ✅  Account updated

  | ▶️  Check your changed overview


EOL;

// Save the API context to account for all the changes that might have occurred to it during the example execution
$bunq->updateContext();
