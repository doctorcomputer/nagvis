<?php
class FrontendModLogonDialog extends FrontendModule {
	protected $CORE;
	protected $FHANDLER;
	protected $SHANDLER;
	
	// Maximum length of input in forms
	protected $iInputMaxLength = 12;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$this->aActions = Array('view' => 0, 'login' => 0);
		
		$this->FHANDLER = new FrontendRequestHandler($_POST);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				case 'view':
					// Check if user is already authenticated
					if(!isset($this->AUTHENTICATION) || !$this->AUTHENTICATION->isAuthenticated()) {
						$sReturn = $this->displayDialog();
					} else {
						// When the user is already authenticated redirect to start page (overview)
						Header('Location:'.$this->CORE->getMainCfg()->getValue('paths', 'htmlbase'));
					}
				break;
				case 'login':
					// Check if user is already authenticated
					if(!isset($this->AUTHENTICATION) || !$this->AUTHENTICATION->isAuthenticated()) {
						$aReturn = $this->handleResponse();
						
						$AUTH = new FrontendModAuth($this->CORE);
						
						if($aReturn !== false) {
							$AUTH->setAction('login');
							$AUTH->passCredentials($aReturn);
							
							return $AUTH->handleAction();
						} else {
							$sReturn = $AUTH->msgInvalidCredentials();
						}
					} else {
						// When the user is already authenticated redirect to start page (overview)
						Header('Location:'.$this->CORE->getMainCfg()->getValue('paths', 'htmlbase'));
					}
				break;
			}
		}
		
		return $sReturn;
	}
	
	private function displayDialog() {
		$VIEW = new NagVisLoginView($this->CORE);
		return $VIEW->parse();
	}
	
	private function handleResponse() {
		$bValid = true;
		// Validate the response
		
		// Check for needed params
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('username')) {
			$bValid = false;
		}
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('password')) {
			$bValid = false;
		}
		
		// Check length limits
		if($bValid && $this->FHANDLER->isLongerThan('username', $this->iInputMaxLength)) {
			$bValid = false;
		}
		if($bValid && $this->FHANDLER->isLongerThan('password', $this->iInputMaxLength)) {
			$bValid = false;
		}
		
		//@todo Escape vars?
		
	  // Store response data
	  if($bValid === true) {
	  	$sUsername = $this->FHANDLER->get('username');
	  	$sPassword = $this->FHANDLER->get('password');
		  
		  // Return the data
		  return Array('user' => $sUsername, 'password' => $sPassword);
		} else {
			return false;
		}
	}
}

?>
