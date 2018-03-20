<?php
namespace bunq\tinker;

use bunq\Exception\BunqException;
use bunq\Model\Generated\Endpoint\Card;
use bunq\Model\Generated\Endpoint\MonetaryAccount;
use bunq\Model\Generated\Endpoint\MonetaryAccountBank;
use bunq\Model\Generated\Endpoint\Payment;
use bunq\Model\Generated\Endpoint\RequestInquiry;
use bunq\Model\Generated\Endpoint\UserCompany;
use bunq\Model\Generated\Endpoint\UserLight;
use bunq\Model\Generated\Endpoint\UserPerson;
use bunq\Model\Generated\Object\Pointer;
use bunq\Util\BunqEnumApiEnvironmentType;

/**
 * @author Kevin Hellemun <khellemun@bunq.com>
 * @since  20180205 Initial creation.
 */
class SharedLib
{
    /**
     * Error constants.
     */
    const ERROR_COULD_NOT_DETERMINE_INPUT = 'Could not find input from terminal';
    const ERROR_COULD_NOT_FIND_CARD_BY_ID = 'Card with id "%d" could not be found.';
    const ERROR_COULD_NOT_DETERMINE_RECIPIENT =
        'Could not determine recipient, if using sandbox environment you can use bravo@bunq.com';
    const ERROR_COULD_NOT_FIND_MONETARY_ACCOUNT_BY_ID = 'Monetary account with id "%d" could not be found.';

    /**
     * Regular expression constants.
     */
    const REGEX_FIND_INPUT = '/(?P<input>.+?\n)/';
    const REGEX_NAMED_GROUP_INPUT = 'input';

    /**
     * Default description.
     */
    const DEFAULT_CARD_DESCRIPTION = 'bunq card';
    const DEFAULT_MONETARY_ACCOUNT_DESCRIPTION = 'account description';

    /**
     * Option constants.
     */
    const OPTION_KEY_AMOUNT = 'amount';
    const OPTION_KEY_ACCOUNT = 'account-id';
    const OPTION_KEY_DESCRIPTION = 'description';
    const OPTION_KEY_NAME = 'name';
    const OPTION_KEY_CARD = 'card-id';
    const OPTION_KEY_URL = 'url';
    const OPTION_KEY_PRODUCTION = 'production';
    const OPTION_KEY_RECIPIENT = 'recipient';
    const OPTION_VALUE_REQUIRED = ':';
    const ALL_OPTION_KEY = [
        self::OPTION_KEY_AMOUNT . self::OPTION_VALUE_REQUIRED,
        self::OPTION_KEY_ACCOUNT . self::OPTION_VALUE_REQUIRED,
        self::OPTION_KEY_DESCRIPTION . self::OPTION_VALUE_REQUIRED,
        self::OPTION_KEY_NAME . self::OPTION_VALUE_REQUIRED,
        self::OPTION_KEY_CARD . self::OPTION_VALUE_REQUIRED,
        self::OPTION_KEY_URL . self::OPTION_VALUE_REQUIRED,
        self::OPTION_KEY_PRODUCTION,
        self::OPTION_KEY_RECIPIENT . self::OPTION_VALUE_REQUIRED,
    ];

    /**
     * Input/output contains.
     */
    const FOPEN_MODE_READ = 'r';
    const FILE_NAME_STDIN = 'php://stdin';

    /**
     * String format constants.
     */
    const STRING_FORMAT_SPACE = ' ';

    /**
     * Type constants.
     */
    const POINTER_TYPE_IBAN = 'IBAN';
    const POINTER_TYPE_EMAIL = 'EMAIL';

    /**
     * Echo constants.
     */
    const ECHO_USER = PHP_EOL . 'User' . PHP_EOL;
    const ECHO_MONETARY_ACCOUNT = PHP_EOL . 'Monetary Accounts' . PHP_EOL;
    const ECHO_PAYMENT = PHP_EOL . 'Payments' . PHP_EOL;
    const ECHO_REQUEST = PHP_EOL . 'Requests' . PHP_EOL;
    const ECHO_CARD = PHP_EOL . 'Cards' . PHP_EOL;
    const ECHO_AMOUNT_IN_EUR = PHP_EOL . '%sAmount (EUR):%s';
    const ECHO_DESCRIPTION = '%sDescription:%s';
    const ECHO_NEW_NAME = PHP_EOL . '%sNew Name:%s%s';
    const ECHO_CARD_ID = PHP_EOL . '%sCard (ID):%s%s';
    const ECHO_ACCOUNT_ID = '%sAccount (ID):%s';
    const ECHO_CALLBACK_URL = PHP_EOL . '%sCallback URL:    ';
    const ECHO_SANDBOX_LOGIN_CREDENTIAL_TEXT =
        '%sYou can use these login credentials to login in to the bunq sandbox app.';
    const ECHO_RECIPIENT = '%sRecipient(%s):%s';
    const ECHO_EXAMPLE_EMAIL = 'e.g. bravo@bunq.com';
    const INDENTATION_NORMAL = '   ';
    const INDENTATION_SMALL = '  ';
    const INDENTATION_EXTRA_SMALL = ' ';
    const MESSAGE_CARD_NOT_LINKED = 'Not linked yet.';
    const FORMAT_LINKED_ACCOUNT = "%s (%s)";

    /**
     * @var BunqEnumApiEnvironmentType
     */
    private static $environment;

    /**
     * @param string[] $allOption
     *
     * @return BunqEnumApiEnvironmentType
     * @throws BunqException
     */
    public static function determineEnvironmentType(array $allOption): BunqEnumApiEnvironmentType
    {
        if (isset($allOption[self::OPTION_KEY_PRODUCTION])) {
            static::$environment = BunqEnumApiEnvironmentType::PRODUCTION();
        } else {
            static::$environment = BunqEnumApiEnvironmentType::SANDBOX();
        }

        return static::$environment;
    }

    /**
     */
    public static function printHeader()
    {
        if (BunqEnumApiEnvironmentType::PRODUCTION()->equals(static::$environment)) {
            echo <<<EOL
\033[93m
  ██████╗ ██████╗  ██████╗ ██████╗ ██╗   ██╗ ██████╗████████╗██╗ ██████╗ ███╗   ██╗
  ██╔══██╗██╔══██╗██╔═══██╗██╔══██╗██║   ██║██╔════╝╚══██╔══╝██║██╔═══██╗████╗  ██║
  ██████╔╝██████╔╝██║   ██║██║  ██║██║   ██║██║        ██║   ██║██║   ██║██╔██╗ ██║
  ██╔═══╝ ██╔══██╗██║   ██║██║  ██║██║   ██║██║        ██║   ██║██║   ██║██║╚██╗██║
  ██║     ██║  ██║╚██████╔╝██████╔╝╚██████╔╝╚██████╗   ██║   ██║╚██████╔╝██║ ╚████║
  ╚═╝     ╚═╝  ╚═╝ ╚═════╝ ╚═════╝  ╚═════╝  ╚═════╝   ╚═╝   ╚═╝ ╚═════╝ ╚═╝  ╚═══╝
\033[0m
EOL;
        } else {
            echo <<<EOL
\033[94m
  ████████╗██╗███╗   ██╗██╗  ██╗███████╗██████╗ ██╗███╗   ██╗ ██████╗ 
  ╚══██╔══╝██║████╗  ██║██║ ██╔╝██╔════╝██╔══██╗██║████╗  ██║██╔════╝ 
     ██║   ██║██╔██╗ ██║█████╔╝ █████╗  ██████╔╝██║██╔██╗ ██║██║  ███╗
     ██║   ██║██║╚██╗██║██╔═██╗ ██╔══╝  ██╔══██╗██║██║╚██╗██║██║   ██║
     ██║   ██║██║ ╚████║██║  ██╗███████╗██║  ██║██║██║ ╚████║╚██████╔╝
     ╚═╝   ╚═╝╚═╝  ╚═══╝╚═╝  ╚═╝╚══════╝╚═╝  ╚═╝╚═╝╚═╝  ╚═══╝ ╚═════╝ 
\033[0m
EOL;
        }
    }

    /**
     * Print the user.
     *
     * @param UserPerson|UserCompany|UserLight $user
     */
    public static function printUser($user)
    {
        echo vsprintf(self::ECHO_USER, [self::INDENTATION_NORMAL]);

        echo <<<EOL
  ┌────────────────┬───────────────────────────────────────────────────────
  │ ID             │ {$user->getId()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Username       │ {$user->getDisplayName()}
  └────────────────┴───────────────────────────────────────────────────────
EOL;
    }

    /**
     * Print the provided bank accounts - limited by a count.
     *
     * @param MonetaryAccountBank[] $allMonetaryAccountBank
     * @param int $count
     */
    public static function printAllBankAccount(array $allMonetaryAccountBank, int $count)
    {
        echo vsprintf(self::ECHO_MONETARY_ACCOUNT, []);

        foreach ($allMonetaryAccountBank as $index => $accountBank) {
            if ($index >= $count) {
                break;
            }

            static::printBankAccount($accountBank);
            echo PHP_EOL;
        }
    }

    /**
     * Print a provided bank account.
     *
     * @param MonetaryAccountBank $monetaryAccount
     *
     * @throws BunqException
     */
    public static function printBankAccount(MonetaryAccountBank $monetaryAccount)
    {

        $firstAliasOfTypeForMonetaryAccount =
            BunqLib::getIbanAliasForBankAccount($monetaryAccount);

        echo <<<EOL
  ┌────────────────┬───────────────────────────────────────────────────────
  │ ID             │ {$monetaryAccount->getId()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Description    │ {$monetaryAccount->getDescription()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ IBAN           │ {$firstAliasOfTypeForMonetaryAccount->getValue()}
EOL;
        if (!is_null($monetaryAccount->getBalance())) {
            echo <<<EOL

  ├────────────────┼───────────────────────────────────────────────────────
  │ Balance        │ {$monetaryAccount->getBalance()->getCurrency()} {$monetaryAccount->getBalance()->getValue()}
EOL;
        } else {
            // Cannot show balance, as we dont have permission to view it.
        }

        echo <<<EOL

  └────────────────┴───────────────────────────────────────────────────────
EOL;
    }

    /**
     * Print all provided payments.
     *
     * @param Payment[] $allPayment
     */
    public static function printAllPayment(array $allPayment)
    {
        echo vsprintf(self::ECHO_PAYMENT, [self::INDENTATION_NORMAL]);

        foreach ($allPayment as $payment) {
            static::printPayment($payment);
            print PHP_EOL;
        }
    }

    /**
     * Print the provided payment.
     *
     * @param Payment $payment
     */
    public static function printPayment($payment)
    {

        echo <<<EOL
  ┌────────────────┬───────────────────────────────────────────────────────
  │ ID             │ {$payment->getId()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Date           │ {$payment->getCreated()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Description    │ {$payment->getDescription()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Amount         │ {$payment->getAmount()->getCurrency()} {$payment->getAmount()->getValue()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Recipient      │ {$payment->getCounterpartyAlias()->getDisplayName()}
  └────────────────┴───────────────────────────────────────────────────────
EOL;
    }

    /**
     * Print all provided requests.
     *
     * @param RequestInquiry[] $allRequest
     */
    public static function printAllRequest(array $allRequest)
    {
        echo vsprintf(self::ECHO_REQUEST, [self::INDENTATION_NORMAL]);

        foreach ($allRequest as $request) {
            static::printRequest($request);
            print PHP_EOL;
        }
    }

    /**
     * Print the provided request.
     *
     * @param RequestInquiry $request
     */
    public static function printRequest(RequestInquiry $request)
    {

        echo <<<EOL
  ┌────────────────┬───────────────────────────────────────────────────────
  │ ID             │ {$request->getId()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Date           │ {$request->getCreated()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Description    │ {$request->getDescription()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Status         │ {$request->getStatus()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Amount         │ {$request->getAmountInquired()->getCurrency()} {$request->getAmountInquired()->getValue()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Recipient      │ {$request->getCounterpartyAlias()->getDisplayName()}
  └────────────────┴───────────────────────────────────────────────────────
EOL;
    }

    /**
     * Print provided cards - limited by a count.
     *
     * Monetary accounts are needed to reference the iban and name.
     *
     * @param Card[] $allCard
     * @param MonetaryAccount[] $allMonetaryAccount
     */
    public static function printAllCard(array $allCard, $allMonetaryAccount)
    {
        echo vsprintf(self::ECHO_CARD, [self::INDENTATION_NORMAL]);

        foreach ($allCard as $card) {
            static::printCard($card, $allMonetaryAccount);
            print PHP_EOL;
        }
    }

    /**
     * Print the provided card.
     *
     * Monetary accounts are needed to reference the iban and name.
     *
     * @param Card $card
     * @param MonetaryAccount[] $allMonetaryAccount
     */
    public static function printCard(Card $card, $allMonetaryAccount)
    {

        if (!is_null($card->getLabelMonetaryAccountCurrent())) {
            $monetaryAccount = BunqLib::getBankAccountFromPublicInformation(
                $card->getLabelMonetaryAccountCurrent(),
                $allMonetaryAccount
            );
            $monetaryAccountDescription
                = $monetaryAccount->getDescription() ?? self::DEFAULT_MONETARY_ACCOUNT_DESCRIPTION;
            $accountLinked = vsprintf(
                self::FORMAT_LINKED_ACCOUNT,
                [
                    $monetaryAccountDescription,
                    $card->getLabelMonetaryAccountCurrent()->getIban(),
                ]
            );
        } else {
            $accountLinked = self::MESSAGE_CARD_NOT_LINKED;
        }
        $cardDescription = ($card->getSecondLine() ? $card->getSecondLine() : self::DEFAULT_CARD_DESCRIPTION);

        echo <<<EOL
  ┌────────────────┬───────────────────────────────────────────────────────
  │ ID             │ {$card->getId()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Type           │ {$card->getType()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Name on Card   │ {$card->getNameOnCard()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Description    │ {$cardDescription}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Linked Account │ {$accountLinked}
  └────────────────┴───────────────────────────────────────────────────────
EOL;
    }

    /**
     * @param string[] $allOption
     *
     * @return string
     */
    public static function getAmountFromAllOptionOrStdIn(array $allOption): string
    {
        $amountKey = self::OPTION_KEY_AMOUNT;

        if (array_key_exists($amountKey, $allOption)) {
            return $allOption[$amountKey];
        } else {
            echo vsprintf(self::ECHO_AMOUNT_IN_EUR, [self::INDENTATION_NORMAL, self::INDENTATION_EXTRA_SMALL]);

            return static::readFromLine();
        }
    }

    /**
     * @return string
     */
    public static function readFromLine(): string
    {
        $handle = fopen(self::FILE_NAME_STDIN, self::FOPEN_MODE_READ);
        $line = fgets($handle);

        return static::removeAllWhitespace($line);
    }

    /**
     * @param string $input
     *
     * @return string
     * @throws BunqException
     */
    private static function removeAllWhitespace(string $input): string
    {
        $allMatch = [];
        preg_match(self::REGEX_FIND_INPUT, $input, $allMatch);

        if (isset($allMatch[self::REGEX_NAMED_GROUP_INPUT])) {
            return trim($allMatch[self::REGEX_NAMED_GROUP_INPUT]);
        } else {
            throw new BunqException(self::ERROR_COULD_NOT_DETERMINE_INPUT);
        }
    }

    /**
     * @param string[] $allOption
     *
     * @return string
     */
    public static function getDescriptionFromAllOptionOrStdIn(array $allOption): string
    {
        $descriptionKey = self::OPTION_KEY_DESCRIPTION;

        if (array_key_exists($descriptionKey, $allOption)) {
            return $allOption[$descriptionKey];
        } else {
            echo self::ECHO_DESCRIPTION;

            return static::readFromLine();
        }
    }

    /**
     * @param array $allOption
     *
     * @return string
     */
    public static function getRecipientFromAllOptionOrStdIn(array $allOption): string
    {
        if (array_key_exists(self::OPTION_KEY_RECIPIENT, $allOption)) {
            return $allOption[self::OPTION_KEY_RECIPIENT];
        } else {
            if (static::$environment->equals(BunqEnumApiEnvironmentType::SANDBOX())) {
                $echoString =
                    vsprintf(
                        self::ECHO_RECIPIENT,
                        [self::INDENTATION_NORMAL, self::ECHO_EXAMPLE_EMAIL, self::INDENTATION_EXTRA_SMALL]
                    );
            } else {
                $echoString =
                    vsprintf(
                        self::ECHO_RECIPIENT,
                        [self::INDENTATION_NORMAL, self::POINTER_TYPE_EMAIL, self::INDENTATION_EXTRA_SMALL]
                    );
            }
            echo $echoString;

            return static::readFromLine();
        }
    }

    /**
     * @param string[] $AllOption
     *
     * @return string
     */
    public static function getNameFromAllOptionOrStdIn(array $AllOption)
    {
        $nameKey = self::OPTION_KEY_NAME;

        if (array_key_exists($nameKey, $AllOption)) {
            return $AllOption[$nameKey];
        } else {
            echo vsprintf(
                self::ECHO_NEW_NAME,
                [self::INDENTATION_NORMAL, self::INDENTATION_NORMAL, self::INDENTATION_NORMAL]
            );

            return static::readFromLine();
        }
    }

    /**
     * @param string[] $allOption
     * @param Card[] $allCard
     *
     * @return Card
     * @throws BunqException
     */
    public static function getCardIdFromAllOptionOrStdIn(array $allOption, array $allCard): Card
    {
        if (array_key_exists(self::OPTION_KEY_CARD, $allOption)) {
            $cardId = $allOption[self::OPTION_KEY_CARD];
        } else {
            echo vsprintf(
                self::ECHO_CARD_ID,
                [self::INDENTATION_NORMAL, self::INDENTATION_NORMAL, self::INDENTATION_NORMAL]
            );
            $cardId = static::readFromLine();
        }

        foreach ($allCard as $card) {
            if ($card->getId() === intval($cardId)) {
                return $card;
            }
        }

        throw new BunqException(vsprintf(self::ERROR_COULD_NOT_FIND_CARD_BY_ID, [$cardId]));
    }

    /**
     * @param string[] $allOption
     * @param MonetaryAccountBank[] $allAccount
     *
     * @return MonetaryAccountBank
     * @throws BunqException
     */
    public static function getAccountIdFromAllOptionOrStdIn(array $allOption, array $allAccount): MonetaryAccountBank
    {
        if (array_key_exists(self::OPTION_KEY_ACCOUNT, $allOption)) {
            $accountId = $allOption[self::OPTION_KEY_ACCOUNT];
        } else {
            echo vsprintf(self::ECHO_ACCOUNT_ID, [self::INDENTATION_NORMAL, self::INDENTATION_NORMAL]);
            $accountId = static::readFromLine();
        }

        foreach ($allAccount as $account) {
            if ($account->getId() === intval($accountId)) {
                return $account;
            }
        }

        throw new BunqException(vsprintf(self::ERROR_COULD_NOT_FIND_MONETARY_ACCOUNT_BY_ID, [$accountId]));
    }

    /**
     * @param string[] $AllOption
     *
     * @return string
     */
    public static function getCallbackUrlFromAllOptionOrStdIn(array $AllOption): string
    {
        $urlKey = self::OPTION_KEY_URL;

        if (array_key_exists($urlKey, $AllOption)) {
            return $AllOption[$urlKey];
        } else {
            echo vsprintf(self::ECHO_CALLBACK_URL, [self::INDENTATION_NORMAL]);

            return static::readFromLine();
        }
    }

    /**
     * @param Pointer[] $allUserAlias
     */
    public static function printAllUserAlias(array $allUserAlias)
    {
        echo PHP_EOL . vsprintf(self::ECHO_SANDBOX_LOGIN_CREDENTIAL_TEXT, [self::INDENTATION_NORMAL]);

        foreach ($allUserAlias as $alias) {
            echo <<<EOL
            
  ┌────────────────┬───────────────────────────────────────────────────────
  │ Value          │ {$alias->getValue()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Type           │ {$alias->getType()}
  ├────────────────┼───────────────────────────────────────────────────────
  │ Login Code     │ 000000
  └────────────────┴───────────────────────────────────────────────────────
  
EOL;
        }
    }
}
