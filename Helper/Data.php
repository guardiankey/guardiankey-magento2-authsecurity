<?php

namespace GuardianKey\AuthSecurity\Helper;

// use Magento\Framework\App\Helper\AbstractHelper;
// use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Data
{
	const XML_PATH_HELLOWORLD = 'authsecurity/';

	protected $scopeConfig;

	public function __construct(ScopeConfigInterface $scopeConfig)
    {
		$this->scopeConfig = $scopeConfig;
		return $this;
	}

	public function getConfigValue($field, $storeId = null)
	{
		return $this->scopeConfig->getValue(
			$field, ScopeInterface::SCOPE_STORE, $storeId
		);
	}

	public function getGeneralConfig($code, $storeId = null)
	{
		return $this->getConfigValue(self::XML_PATH_HELLOWORLD .'general/'. $code, $storeId);
	}

}


// class Data extends AbstractHelper
// {

// 	const XML_PATH_HELLOWORLD = 'authsecurity/';

// 	// public function __construct(Context $context)
//     // {
// 	// 	return parent::__construct($context);
// 	// }

// 	public function getConfigValue($field, $storeId = null)
// 	{
// 		return $this->scopeConfig->getValue(
// 			$field, ScopeInterface::SCOPE_STORE, $storeId
// 		);
// 	}

// 	public function getGeneralConfig($code, $storeId = null)
// 	{

// 		return $this->getConfigValue(self::XML_PATH_HELLOWORLD .'general/'. $code, $storeId);
// 	}

// }