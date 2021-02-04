<?php

namespace GuardianKey\AuthSecurity\Controller\Index;

use GuardianKey\AuthSecurity\Lib\GuardianKey;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Request\InvalidRequestException;

class Checkpoint extends \Magento\Framework\App\Action\Action  //implements \Magento\Framework\App\CsrfAwareActionInterface
{

	protected $helperData;
	protected $context;
	protected $dictionary = array("pt_BR" => array(
													"Invalid response!" => "Resposta inválida!",
													"Security Incident Reported" => "Incidente de Segurança Reportado",
													"Invalid token!" => "Token inválido",
													"Event already resolved!" => "Evento já resolvido!",
													"Input registered! Thank you!" => "Informação registrada! Obrigado!"
							));


	public function __construct(
		\Magento\Framework\App\Action\Context $context,
		\GuardianKey\AuthSecurity\Helper\Data $helperData
	)
	{
		$this->helperData = $helperData;
		$this->context = $context;
		return parent::__construct($context);
	}

	public function execute()
	{
		$GK = new GuardianKey(null);
		$eventid = $this->context->getRequest()->getParam('e');
		$token = $this->context->getRequest()->getParam('t');
		$post = $this->context->getRequest()->getPostValue();
		if (isset($post['action'])) {
	
			if ($post['action'] == "GOOD") {
				$GK->resolveEvent($eventid,$token,'GOOD');
				// echo "Thank You!";
			} elseif ($post['action'] == "BAD") {
				$GK->resolveEvent($eventid,$token,'BAD');
				// echo "Thank You. Please contact System Administrator!!";

				// SEND MSG

				$returned = $GK->getEvent($eventid,$token);
				$datetime = gmdate("Y-m-d\ H:i:s\ ", $returned->DATETIME)." UTC";

				$emailhtml= $this->template("support_email.html"); //file_get_contents(dirname(__FILE__).'/templates/support_email.html');
				$emailtext= $this->template("support_email.txt"); //file_get_contents(dirname(__FILE__).'/templates/support_email.txt');

				$emailhtml=str_replace("[USERNAME]",$returned->USERNAME,$emailhtml);
                $emailhtml=str_replace("[DATETIME]",$datetime,$emailhtml);
                $emailhtml=str_replace("[LOCATION]",$returned->LOCATION,$emailhtml);
                $emailhtml=str_replace("[SYSTEM]",$returned->SYSTEM,$emailhtml);
				$emailhtml=str_replace("[IPADDRESS]",$returned->IPADDRESS,$emailhtml);
				
				$emailtext=str_replace("[USERNAME]",$returned->USERNAME,$emailtext);
                $emailtext=str_replace("[DATETIME]",$datetime,$emailtext);
                $emailtext=str_replace("[LOCATION]",$returned->LOCATION,$emailtext);
                $emailtext=str_replace("[SYSTEM]",$returned->SYSTEM,$emailtext);
                $emailtext=str_replace("[IPADDRESS]",$returned->IPADDRESS,$emailtext);

				$email = new \Zend_Mail();
				$email->setSubject($this->i18n("Security Incident Reported"));
				$email->setBodyText($emailtext);
				$email->setFrom($this->helperData->getGeneralConfig("from_email_addr"));
				$email->addTo($this->helperData->getGeneralConfig("support_email_addr"));
				$email->setBodyHtml($emailhtml);
				$email->send();

			} else {
				$html_content= $this->template("checkpoint_info.html"); //file_get_contents(dirname(__FILE__).'/templates/checkpoint_info.html');
				$message = '<center> 
								   <h2>'.$this->i18n("Invalid response!").'</h2><br>
		  					    </center>';
				$html_content=str_replace("[MESSAGE]",$message,$html_content);
				echo $html_content;
				exit();			
			}
			
		} else {
			$returned = $GK->getEvent($eventid,$token);

			// Adjust the timezone
			date_default_timezone_set("America/Sao_Paulo");
			//$returned->DATETIME=$returned->DATETIME+date("Z");

			if (! isset($returned->USERNAME)) {
				
				$html_content= $this->template("checkpoint_info.html"); //file_get_contents(dirname(__FILE__).'/templates/checkpoint_info.html');
				$message = '<center> 
								   <h2>'.$this->i18n("Invalid token!").'</h2><br>
		  					    </center>';
				$html_content=str_replace("[MESSAGE]",$message,$html_content);
				echo $html_content;
				exit();
			}elseif (isset($returned->STATUS) && $returned->STATUS == "RESOLVED") {
				$html_content= $this->template("checkpoint_info.html"); //file_get_contents(dirname(__FILE__).'/templates/checkpoint_info.html');
				$message = '<center> 
								   <h2>'.$this->i18n("Event already resolved!").'</h2><br>
									  <i class="fa fa-check" aria-hidden="true" style="color: darkgreen; font-size:48px;"></i>
								  </center>';
				$html_content=str_replace("[MESSAGE]",$message,$html_content);
				echo $html_content; 
				exit();
			} else {

				$html_content= $this->template("checkpoint.html"); //file_get_contents(dirname(__FILE__).'/templates/checkpoint.html');
                $html_content=str_replace("[USERNAME]",$returned->USERNAME,$html_content);
                $html_content=str_replace("[DATETIME]",$returned->DATETIME,$html_content);
                $html_content=str_replace("[LOCATION]",$returned->LOCATION,$html_content);
                $html_content=str_replace("[SYSTEM]",$returned->SYSTEM,$html_content);
                $html_content=str_replace("[IPADDRESS]",$returned->IPADDRESS,$html_content);
                $html_content=str_replace("[TIMESTAMP]",$returned->DATETIME,$html_content);
				echo $html_content;
				exit();
			}
		}

		$html_content= $this->template("checkpoint_info.html"); //file_get_contents(dirname(__FILE__).'/templates/checkpoint_info.html');
		$message = '<center> 
						   <h2>'.$this->i18n("Input registered! Thank you!").'</h2><br>
							  <i class="fa fa-check" aria-hidden="true" style="color: darkgreen; font-size:48px;"></i>
						  </center>';
		$html_content=str_replace("[MESSAGE]",$message,$html_content);
		echo $html_content;

		exit();
	}

	protected function i18n($text)
	{
		$lang=$this->helperData->getGeneralConfig("language");
		if(isset($this->dictionary[$lang][$text]))
			return $this->dictionary[$lang][$text];
		else
			return $text;
	}

	protected function template($template)
	{
		$lang=$this->helperData->getGeneralConfig("language");
		if($lang == 'pt_BR')
			$template =str_replace('.','_pt_BR.',$template);
		return file_get_contents(dirname(__FILE__)."/templates"."/".$template);
	}


	/** * @inheritDoc */
	public function createCsrfValidationException( RequestInterface $request ): ?       InvalidRequestException {
		return null;
	}
	/** * @inheritDoc */
	public function validateForCsrf(RequestInterface $request): ?bool {
	return true;
	}
}