<?php

namespace GuardianKey\AuthSecurity\Block\Adminhtml\System\Config;

class Language implements \Magento\Framework\Data\OptionSourceInterface
{
 public function toOptionArray()
 {
  return [
    ['value' => 'en_US', 'label' => "English (en_US)"],
    ['value' => 'pt_BR', 'label' => "Portuguese (pt_BR)"]
  ];
 }
}