#!/usr/bin/env php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use bunq\tinker\BunqLib;
use bunq\tinker\SharedLib;

$allOption = getopt('', SharedLib::ALL_OPTION_KEY);
$environment = SharedLib::determineEnvironmentType($allOption);

SharedLib::printHeader();

$bunq = new BunqLib($environment);

$card = SharedLib::getCardIdFromAllOptionOrStdIn($allOption, $bunq->getAllCard());
$account = SharedLib::getAccountIdFromAllOptionOrStdIn($allOption, $bunq->getAllActiveBankAccount());

echo <<<EOL

  | Link Card:    {$card->getId()}
  | To Account:   {$account->getId()}
   
    ...

EOL;

$bunq->linkCardToBankAccount($card, $account);

echo <<<EOL

  | ✅  Account switched

  | ▶️  Check your changed overview


EOL;

// Save the API context to account for all the changes that might have occurred to it during the example execution
$bunq->updateContext();
