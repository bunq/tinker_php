<?php
namespace bunq\tinker;

use bunq\Context\ApiContext;
use bunq\Context\BunqContext;
use bunq\Exception\BunqException;
use bunq\Http\Pagination;
use bunq\Model\Generated\Endpoint\Card;
use bunq\Model\Generated\Endpoint\MonetaryAccountBank;
use bunq\Model\Generated\Endpoint\Payment;
use bunq\Model\Generated\Endpoint\RequestInquiry;
use bunq\Model\Generated\Endpoint\UserCompany;
use bunq\Model\Generated\Endpoint\UserLight;
use bunq\Model\Generated\Endpoint\UserPerson;
use bunq\Model\Generated\Object\Amount;
use bunq\Model\Generated\Object\LabelMonetaryAccount;
use bunq\Model\Generated\Object\NotificationFilter;
use bunq\Model\Generated\Object\Pointer;
use bunq\Util\BunqEnumApiEnvironmentType;
use bunq\Util\InstallationUtil;

/**
 * Some simple helper methods to get you started in seconds.
 */
class BunqLib
{
    /**
     * Error constants.
     */
    const ERROR_USER_TYPE_UNEXPECTED = 'User of type "%s" is unexpected';
    const ERROR_COULD_NOT_DETERMINE_ALIAS_OF_TYPE_IBAN = 'Could not find alias with type IBAN for monetary account "%s';
    /**
     * Config file name constants.
     */
    const CONFIG_FILE_NAME_SANDBOX = 'bunq-sandbox.conf';
    const CONFIG_FILE_NAME_PRODUCTION = 'bunq-production.conf';

    /**
     * The first index of an array.
     */
    const INDEX_FIRST = 0;

    /**
     * Type/category constants.
     */
    const CURRENCY_TYPE_EUR = 'EUR';

    const POINTER_TYPE_EMAIL = 'EMAIL';
    const POINTER_TYPE_IBAN = 'IBAN';
    const CARD_PIN_ASSIGNMENT_PRIMARY = 'PRIMARY';
    const NOTIFICATION_DELIVERY_METHOD_URL = 'URL';
    const NOTIFICATION_CATEGORY_MUTATION = 'MUTATION';

    /**
     * Status constants.
     */
    const MONETARY_ACCOUNT_STATUS_ACTIVE = 'ACTIVE';

    /**
     * @var UserCompany|UserPerson|UserLight
     */
    protected $user;


    /**
     * @var BunqEnumApiEnvironmentType
     */
    protected $environment;

    /**
     * @param BunqEnumApiEnvironmentType $bunqEnumApiEnvironmentType
     */
    public function __construct(BunqEnumApiEnvironmentType $bunqEnumApiEnvironmentType)
    {
        $this->environment = $bunqEnumApiEnvironmentType;
        $this->setupContext();
        $this->setupCurrentUser();
    }

    /**
     * Restores the context from the saved file during creation.
     */
    private function setupContext()
    {
        if (is_file($this->determineBunqConfFileName())) {
            // Config is already present
        } elseif (BunqEnumApiEnvironmentType::SANDBOX()->equals($this->environment)) {
            InstallationUtil::automaticInstall($this->environment, $this->determineBunqConfFileName());
        }

        $apiContext = ApiContext::restore($this->determineBunqConfFileName());
        $apiContext->ensureSessionActive();
        $apiContext->save($this->determineBunqConfFileName());

        BunqContext::loadApiContext($apiContext);
    }

    /**
     * @return string
     */
    private function determineBunqConfFileName(): string
    {
        if ($this->environment->equals(BunqEnumApiEnvironmentType::PRODUCTION())) {
            return self::CONFIG_FILE_NAME_PRODUCTION;
        } else {
            return self::CONFIG_FILE_NAME_SANDBOX;
        }
    }

    /**
     * Retrieves the user that belongs to the API key.
     *
     * @throws BunqException
     */
    private function setupCurrentUser()
    {
        if (BunqContext::getUserContext()->isOnlyUserCompanySet()) {
            $this->user = BunqContext::getUserContext()->getUserCompany();
        } elseif (BunqContext::getUserContext()->isOnlyUserPersonSet()) {
            $this->user = BunqContext::getUserContext()->getUserPerson();
        } else {
            throw new BunqException(vsprintf(self::ERROR_USER_TYPE_UNEXPECTED, [get_class($this->user)]));
        }
    }

    /**
     * @param LabelMonetaryAccount $bankAccountPublicInformation
     * @param MonetaryAccountBank[] $allMonetaryAccountBank
     *
     * @return MonetaryAccountBank|null
     */
    public static function getBankAccountFromPublicInformation(
        LabelMonetaryAccount $bankAccountPublicInformation,
        array $allMonetaryAccountBank
    ) {
        $monetaryAccountLabelIban = $bankAccountPublicInformation->getIban();

        foreach ($allMonetaryAccountBank as $monetaryAccount) {
            $monetaryAccountIban =
                static::getIbanAliasForBankAccount(
                    $monetaryAccount
                )->getValue();

            if ($monetaryAccountIban === $monetaryAccountLabelIban) {
                return $monetaryAccount;
            }
        }

        return null;
    }

    /**
     * @param MonetaryAccountBank $bankAccount
     *
     * @return Pointer
     * @throws BunqException
     */
    public static function getIbanAliasForBankAccount(MonetaryAccountBank $bankAccount): Pointer
    {
        foreach ($bankAccount->getAlias() as $alias) {
            if ($alias->getType() === self::POINTER_TYPE_IBAN) {
                return $alias;
            }
        }

        throw new BunqException(
            vsprintf(self::ERROR_COULD_NOT_DETERMINE_ALIAS_OF_TYPE_IBAN, [$bankAccount->getDescription()])
        );
    }

    /**
     * Saves the API context back to the file.
     */
    public function updateContext()
    {
        BunqContext::getApiContext()->save($this->determineBunqConfFileName());
    }

    /**
     * @return UserCompany|UserLight|UserPerson
     */
    public function getCurrentUser()
    {
        return $this->user;
    }

    /**
     * @param int $count
     *
     * @return MonetaryAccountBank[]
     */
    public function getAllActiveBankAccount(int $count = 10): array
    {
        $pagination = new Pagination();
        $pagination->setCount($count);


        $allMonetaryAccount = MonetaryAccountBank::listing(
            [],
            $pagination->getUrlParamsCountOnly()
        )->getValue();
        $allActiveMonetaryAccount = [];

        foreach ($allMonetaryAccount as $monetaryAccount) {
            if ($monetaryAccount->getStatus() === self::MONETARY_ACCOUNT_STATUS_ACTIVE) {
                $allActiveMonetaryAccount[] = $monetaryAccount;
            }
        }

        return $allActiveMonetaryAccount;
    }

    /**
     * @param string $description
     * @param MonetaryAccountBank $monetaryAccount
     */
    public function updateBankAccountDescription(string $description, MonetaryAccountBank $monetaryAccount)
    {
        MonetaryAccountBank::update(
            $monetaryAccount->getId(),
            $description
        );
    }

    /**
     * @param string $amount
     * @param string $recipient
     * @param string $description
     * @param MonetaryAccountBank $monetaryAccount
     *
     * @return int
     */
    public function makePayment(
        string $amount,
        string $recipient,
        string $description,
        MonetaryAccountBank $monetaryAccount
    ): int {
        // Create a new payment and retrieve it's id.
        return Payment::create(
            new Amount($amount, self::CURRENCY_TYPE_EUR),
            new Pointer(self::POINTER_TYPE_EMAIL, $recipient),
            $description,
            $monetaryAccount->getId()
        )->getValue();
    }

    /**
     * @param string $amount
     * @param string $recipient
     * @param string $description
     * @param string $name
     * @param MonetaryAccountBank $monetaryAccount
     *
     * @return int
     */
    public function makePaymentIBAN(
        string $amount,
        string $recipient,
        string $description,
	string $name,
        MonetaryAccountBank $monetaryAccount
    ): int {
        // Create a new payment and retrieve it's id.
        return Payment::create(
            new Amount($amount, self::CURRENCY_TYPE_EUR),
            new Pointer(self::POINTER_TYPE_IBAN, $recipient, $name),
            $description,
            $monetaryAccount->getId()
        )->getValue();
    }

    /**
     * @param MonetaryAccountBank $monetaryAccount
     * @param int $count
     *
     * @return Payment[]
     */
    public function getAllPayment(MonetaryAccountBank $monetaryAccount, int $count): array
    {
        $pagination = new Pagination();
        $pagination->setCount($count);

        return Payment::listing(
            $monetaryAccount->getId(),
            $pagination->getUrlParamsCountOnly()
        )->getValue();
    }

    /**
     * @param string $amount
     * @param string $recipient
     * @param string $description
     * @param MonetaryAccountBank $monetaryAccount
     *
     * @return int
     */
    public function makeRequest(
        string $amount,
        string $recipient,
        string $description,
        MonetaryAccountBank $monetaryAccount
    ): int {
        // Create a new request and retrieve it's id.
        return RequestInquiry::create(
            new Amount($amount, self::CURRENCY_TYPE_EUR),
            new Pointer(self::POINTER_TYPE_EMAIL, $recipient),
            $description,
            true,
            $monetaryAccount->getId()
        )->getValue();
    }

    /**
     * @param MonetaryAccountBank $monetaryAccount
     * @param int $count
     *
     * @return RequestInquiry[]
     */
    public function getAllRequest(MonetaryAccountBank $monetaryAccount, int $count): array
    {
        $paginationCountOnly = new Pagination();
        $paginationCountOnly->setCount($count);

        return RequestInquiry::listing(
            $monetaryAccount->getId(),
            $paginationCountOnly->getUrlParamsCountOnly()
        )->getValue();
    }

    /**
     * @param int $count
     *
     * @return Card[]
     */
    public function getAllCard(int $count = 10): array
    {
        $pagination = new Pagination();
        $pagination->setCount($count);

        return Card::listing(
            [],
            $pagination->getUrlParamsCountOnly()
        )->getValue();
    }

    // HELPERS

    /**
     * @param Card $card
     * @param MonetaryAccountBank $monetaryAccount
     */
    public function linkCardToBankAccount(Card $card, MonetaryAccountBank $monetaryAccount)
    {
        Card::update(
            $card->getId(),
            null, /* pinCode */
            null, /* activationCode */
            null, /* status */
            null, /* limit */
            null, /* magStripePermission */
            null, /* countryPermission */
            $monetaryAccount->getId()
        );
    }

    /**
     * @param string $callbackUrl
     */
    public function addCallbackUrl(string $callbackUrl)
    {
        // Get the existing filters to not override the current filters.
        $allCurrentNotificationFilter = $this->user->getNotificationFilters();
        $allUpdatedNotificationFilter = [];

        foreach ($allCurrentNotificationFilter as $notificationFilter) {
            if ($notificationFilter->getNotificationTarget() !== $callbackUrl) {
                $allUpdatedNotificationFilter[] = $notificationFilter;
            }
        }

        $allUpdatedNotificationFilter[] = new NotificationFilter(
            self::NOTIFICATION_DELIVERY_METHOD_URL,
            $callbackUrl,
            self::NOTIFICATION_CATEGORY_MUTATION
        );

        UserPerson::update(
            null, /* firstName */
            null, /* middleName */
            null, /* lastName */
            null, /* publicNickName */
            null, /* addressMain */
            null, /* addressPostal */
            null, /* avatarUuid */
            null, /* taxResident */
            null, /* documentType */
            null, /* documentNumber */
            null, /* documentCountryOfIssuance */
            null, /* documentFrontAttachmentId */
            null, /* documentBackAttachmentId */
            null, /* dataOfBirth */
            null, /* placeOfBirth */
            null, /* countryOfBirth */
            null, /* nationality */
            null, /* language */
            null, /* region */
            null, /* gender */
            null, /* status */
            null, /* subStatus */
            null, /* legalGuardianAlias */
            null, /* sessionTimeout */
            null, /* CardIds */
            null, /* cardLimits */
            null, /* dailyLimitWithoutConfirmationLogin */
            $allUpdatedNotificationFilter
        );
    }

    /**
     * @return Pointer[]
     */
    public function getAllUserAlias(): array
    {
        return $this->getCurrentUser()->getAlias();
    }
}
