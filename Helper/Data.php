<?php

namespace GuardianKey\AuthSecurity\Helper;

// use Magento\Framework\App\Helper\AbstractHelper;
// use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Data
{
	const XML_PATH_GK = 'authsecurity/';

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
		return $this->getConfigValue(self::XML_PATH_GK .'general/'. $code, $storeId);
	}

}
