<?php

namespace GuardianKey\AuthSecurity\Model\Rewrite;

use GuardianKey\AuthSecurity\Helper\Data;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InvalidEmailOrPasswordException;
use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Exception\EmailNotConfirmedException;
use GuardianKey\AuthSecurity\Lib\GuardianKey;
use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\ValidationResultsInterfaceFactory;
use Magento\Customer\Helper\View as CustomerViewHelper;
use Magento\Customer\Model\Config\Share as ConfigShare;
use Magento\Customer\Model\Customer as CustomerModel;
use Magento\Customer\Model\Customer\CredentialsValidator;
use Magento\Customer\Model\Metadata\Validator;
use Magento\Eav\Model\Validator\Attribute\Backend;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\DataObjectFactory as ObjectFactory;
use Magento\Framework\Encryption\EncryptorInterface as Encryptor;
use Magento\Framework\Encryption\Helper\Security;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\MailException;
use Magento\Framework\Exception\State\ExpiredException;
use Magento\Framework\Exception\State\InputMismatchException;
use Magento\Framework\Exception\State\InvalidTransitionException;
use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Math\Random;
use Magento\Framework\Phrase;
use Magento\Framework\Reflection\DataObjectProcessor;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime;
use Magento\Framework\Stdlib\StringUtils as StringHelper;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface as PsrLogger;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Session\SaveHandlerInterface;
use Magento\Customer\Model\ResourceModel\Visitor\CollectionFactory;
use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Api\SessionCleanerInterface;
use Magento\Customer\Model\AccountConfirmation;
use Magento\Customer\Model\AddressRegistry;
use Magento\Customer\Model\ForgotPasswordToken\GetCustomerByToken;
use Magento\Directory\Model\AllowedCountries;

class AccountManagement extends \Magento\Customer\Model\AccountManagement
{
    protected $customerFactory;
    protected $validationResultsDataFactory;
    protected $eventManager;
    protected $storeManager;
    protected $mathRandom;
    protected $validator;
    protected $addressRepository;
    protected $customerMetadataService;
    protected $encryptor;
    protected $customerRegistry;
    protected $configShare;
    protected $customerRepository;
    protected $scopeConfig;
    protected $transportBuilder;
    protected $emailNotification;
    protected $eavValidator;
    protected $credentialsValidator;
    protected $dateTimeFactory;
    protected $accountConfirmation;
    protected $searchCriteriaBuilder;
    protected $addressRegistry;
    protected $allowedCountriesReader;
    protected $getByToken;
    protected $sessionCleaner;


    public function __construct(
        \Magento\Customer\Model\CustomerFactory $customerFactory,
        ManagerInterface $eventManager,
        StoreManagerInterface $storeManager,
        Random $mathRandom,
        Validator $validator,
        ValidationResultsInterfaceFactory $validationResultsDataFactory,
        AddressRepositoryInterface $addressRepository,
        CustomerMetadataInterface $customerMetadataService,
        \Magento\Customer\Model\CustomerRegistry $customerRegistry,
        PsrLogger $logger,
        Encryptor $encryptor,
        ConfigShare $configShare,
        StringHelper $stringHelper,
        CustomerRepositoryInterface $customerRepository,
        ScopeConfigInterface $scopeConfig,
        TransportBuilder $transportBuilder,
        DataObjectProcessor $dataProcessor,
        Registry $registry,
        CustomerViewHelper $customerViewHelper,
        DateTime $dateTime,
        CustomerModel $customerModel,
        ObjectFactory $objectFactory,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter,
        CredentialsValidator $credentialsValidator = null,
        DateTimeFactory $dateTimeFactory = null,
        AccountConfirmation $accountConfirmation = null,
        SessionManagerInterface $sessionManager = null,
        SaveHandlerInterface $saveHandler = null,
        CollectionFactory $visitorCollectionFactory = null,
        SearchCriteriaBuilder $searchCriteriaBuilder = null,
        AddressRegistry $addressRegistry = null,
        GetCustomerByToken $getByToken = null,
        AllowedCountries $allowedCountriesReader = null,
        SessionCleanerInterface $sessionCleaner = null
        ) {
            $this->customerFactory = $customerFactory;
            $this->eventManager = $eventManager;
            $this->storeManager = $storeManager;
            $this->mathRandom = $mathRandom;
            $this->validator = $validator;
            $this->validationResultsDataFactory = $validationResultsDataFactory;
            $this->addressRepository = $addressRepository;
            $this->customerMetadataService = $customerMetadataService;
            $this->customerRegistry = $customerRegistry;
            $this->logger = $logger;
            $this->encryptor = $encryptor;
            $this->configShare = $configShare;
            $this->stringHelper = $stringHelper;
            $this->customerRepository = $customerRepository;
            $this->scopeConfig = $scopeConfig;
            $this->transportBuilder = $transportBuilder;
            $this->dataProcessor = $dataProcessor;
            $this->registry = $registry;
            $this->customerViewHelper = $customerViewHelper;
            $this->dateTime = $dateTime;
            $this->customerModel = $customerModel;
            $this->objectFactory = $objectFactory;
            $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
            $objectManager = ObjectManager::getInstance();
            $this->credentialsValidator =
                $credentialsValidator ?: $objectManager->get(CredentialsValidator::class);
            $this->dateTimeFactory = $dateTimeFactory ?: $objectManager->get(DateTimeFactory::class);
            $this->accountConfirmation = $accountConfirmation ?: $objectManager
                ->get(AccountConfirmation::class);
            $this->searchCriteriaBuilder = $searchCriteriaBuilder
                ?: $objectManager->get(SearchCriteriaBuilder::class);
            $this->addressRegistry = $addressRegistry
                ?: $objectManager->get(AddressRegistry::class);
            $this->getByToken = $getByToken
                ?: $objectManager->get(GetCustomerByToken::class);
            $this->allowedCountriesReader = $allowedCountriesReader
                ?: $objectManager->get(AllowedCountries::class);
            $this->sessionCleaner = $sessionCleaner ?? $objectManager->get(SessionCleanerInterface::class);


		return parent::__construct($customerFactory,
        $eventManager,
        $storeManager,
        $mathRandom,
        $validator,
        $validationResultsDataFactory,
        $addressRepository,
        $customerMetadataService,
        $customerRegistry,
        $logger,
        $encryptor,
        $configShare,
        $stringHelper,
        $customerRepository,
        $scopeConfig,
        $transportBuilder,
        $dataProcessor,
        $registry,
        $customerViewHelper,
        $dateTime,
        $customerModel,
        $objectFactory,
        $extensibleDataObjectConverter,
        $credentialsValidator,
        $dateTimeFactory,
        $accountConfirmation,
        $sessionManager,
        $saveHandler,
        $visitorCollectionFactory,
        $searchCriteriaBuilder,
        $addressRegistry,
        $getByToken,
        $allowedCountriesReader,
        $sessionCleaner);
        }
    

    private function GKObject()
    {
        $config = new Data($this->scopeConfig);
        $GKconfig = array(
            'email'   => $config->getGeneralConfig("support_email_addr"),   /* Admin e-mail */
            'agentid' => $config->getGeneralConfig("organization_id"),  /* ID for the agent (your system) */
            'key'     => $config->getGeneralConfig("key"),     /* Key in B64 to communicate with GuardianKey */
            'iv'      => $config->getGeneralConfig("iv"),      /* IV in B64 for the key */
            'service' => "Magento",      /* Your service name*/
            'orgid'   => $config->getGeneralConfig("organization_id"),   /* Your Org identification in GuardianKey */
            'authgroupid' => $config->getGeneralConfig("authgroup_id"), /* A Authentication Group identification, generated by GuardianKey */
            'reverse' => "True", /* If you will locally perform a reverse DNS resolution */
        );
        return new GuardianKey($GKconfig);
    }

    protected function GKLoginFailed($username)
    {
        $this->logger->info("GuardianKey: login failed");
        $config = new Data($this->scopeConfig);
        if($config->getGeneralConfig("enable"))
        {
            $GK    = $this->GKObject();
            $GKJSONReturn = $GK->checkaccess($username,$username,"1");  
            // $this->logger->info(json_encode($GKJSONReturn));
        }
    }


    protected function GKCheckaccess($username)
    {
        $config = new Data($this->scopeConfig);
        if($config->getGeneralConfig("enable"))
        {
            $GK    = $this->GKObject();
            $GKRet = $GK->checkaccess($username,$username,"0");    
            $GKJSONReturn = @json_decode($GKRet); 

            if ($GKJSONReturn->response == 'BLOCK' )
            { 
                if($config->getGeneralConfig("test_mode"))
                {
                    $this->logger->info("GuardianKey recommended to block, but it is under test mode. Access not blocked!");
                }else
                {   // Block the access!
                    throw new InvalidEmailOrPasswordException(__('Invalid login or password.'));
                }
            }elseif($GKJSONReturn->response == 'NOTIFY' || $GKJSONReturn->response == 'HARD_NOTIFY' )
            {
               $this->logger->info(json_encode($GKJSONReturn));

                $baseurl = $this->storeManager->getStore()->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_WEB);

                $datetime = gmdate("Y-m-d\ H:i:s\ ", $GKJSONReturn->generatedTime)." UTC";
                $emailhtml = $config->getGeneralConfig("email_html");
                $emailhtml=str_replace("[USERNAME]",$username,$emailhtml);
                $emailhtml=str_replace("[DATETIME]",$datetime,$emailhtml);
                $emailhtml=str_replace("[LOCATION]",$GKJSONReturn->country,$emailhtml);
                $emailhtml=str_replace("[SYSTEM]",$GKJSONReturn->client_ua."/".$GKJSONReturn->client_os,$emailhtml);
                $emailhtml=str_replace("[IPADDRESS]",$GK->getUserIP(),$emailhtml);
                $emailhtml=str_replace("[TOKEN]",$GKJSONReturn->event_token,$emailhtml);
                $emailhtml=str_replace("[EVENTID]",$GKJSONReturn->eventId,$emailhtml);
                $emailhtml=str_replace("[CHECKURL]",$baseurl."/gk/index/checkpoint?e=".$GKJSONReturn->eventId."&t=".$GKJSONReturn->event_token,$emailhtml);
                $emailhtml=str_replace("[]","",$emailhtml);
                $emailhtml=str_replace("()","",$emailhtml);

                $emailtext = $config->getGeneralConfig("email_text");
                $emailtext=str_replace("[USERNAME]",$username,$emailtext);
                $emailtext=str_replace("[DATETIME]",$datetime,$emailtext);
                $emailtext=str_replace("[LOCATION]",$GKJSONReturn->country,$emailtext);
                $emailtext=str_replace("[SYSTEM]",$GKJSONReturn->client_ua."/".$GKJSONReturn->client_os,$emailtext);
                $emailtext=str_replace("[IPADDRESS]",$GK->getUserIP(),$emailtext);
                $emailtext=str_replace("[TOKEN]",$GKJSONReturn->event_token,$emailtext);
                $emailtext=str_replace("[EVENTID]",$GKJSONReturn->eventId,$emailtext);
                $emailtext=str_replace("[CHECKURL]",$baseurl."/gk/index/checkpoint?e=".$GKJSONReturn->eventId."&t=".$GKJSONReturn->event_token,$emailtext);
                $emailtext=str_replace("[]","",$emailtext);
                $emailtext=str_replace("()","",$emailtext);
                
                $email = new \Zend_Mail();
                $email->setSubject($config->getGeneralConfig("email_subject"));
                $email->setBodyText($emailtext);
                $email->setFrom($config->getGeneralConfig("from_email_addr"));
                $email->setBodyHtml($emailhtml);
                if(!$config->getGeneralConfig("test_mode"))
                {
                    $email->addTo($username);
                    if($config->getGeneralConfig("support_email_addr_bcc"))
                    {
                        $email->addBcc($config->getGeneralConfig("support_email_addr"));
                    }
                }else
                {
                    $email->addTo($config->getGeneralConfig("support_email_addr"));
                }
                $email->send();
            }

        }
    }

    public function authenticate($username, $password)
    {
        try {
            $customer = $this->customerRepository->get($username);
        } catch (NoSuchEntityException $e) {
            // TODO: change
            //$this->GKCheckaccess($username);
            $this->GKLoginFailed($username);
            throw new InvalidEmailOrPasswordException(__('Invalid login or password.'));
        }
        
        $customerId = $customer->getId();
        if ($this->getAuthentication()->isLocked($customerId)) {
            $this->GKLoginFailed($username);
            throw new UserLockedException(__('The account is locked.'));
        }
        try {
            $this->getAuthentication()->authenticate($customerId, $password);
        } catch (InvalidEmailOrPasswordException $e) {
            $this->GKLoginFailed($username);
            throw new InvalidEmailOrPasswordException(__('Invalid login or password.'));
        }

        $this->GKCheckaccess($username);

        if ($customer->getConfirmation() && $this->isConfirmationRequired($customer)) {
            throw new EmailNotConfirmedException(__("This account isn't confirmed. Verify and try again."));
        }

        $customerModel = $this->customerFactory->create()->updateData($customer);
        $this->eventManager->dispatch(
            'customer_customer_authenticated',
            ['model' => $customerModel, 'password' => $password]
        );

        $this->eventManager->dispatch('customer_data_object_login', ['customer' => $customer]);

        return $customer;
    }

    protected function getAuthentication()
    {
        if (!($this->authentication instanceof AuthenticationInterface)) {
            return \Magento\Framework\App\ObjectManager::getInstance()->get(
                \Magento\Customer\Model\AuthenticationInterface::class
            );
        } else {
            return $this->authentication;
        }
    }

}