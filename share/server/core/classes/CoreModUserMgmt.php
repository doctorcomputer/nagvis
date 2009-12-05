<?php
class CoreModUserMgmt extends CoreModule {
	protected $CORE;
	protected $FHANDLER;
	
	public function __construct($CORE) {
		$this->CORE = $CORE;
		
		$this->aActions = Array('view' => REQUIRES_AUTHORISATION,
		                        'getUserRoles' => REQUIRES_AUTHORISATION,
		                        'getAllRoles' => REQUIRES_AUTHORISATION,
		                        'doAdd' => REQUIRES_AUTHORISATION,
		                        'doEdit' => REQUIRES_AUTHORISATION,
		                        'doDelete' => REQUIRES_AUTHORISATION);
		
		$this->FHANDLER = new CoreRequestHandler($_POST);
	}
	
	public function handleAction() {
		$sReturn = '';
		
		if($this->offersAction($this->sAction)) {
			switch($this->sAction) {
				// The best place for this would be a FrontendModule but this needs to
				// be in CoreModule cause it is fetched via ajax. The error messages
				// would be printed in HTML format in nagvis-js frontend.
				case 'view':
					$VIEW = new NagVisViewUserMgmt($this->AUTHENTICATION, $this->AUTHORISATION);
					$sReturn = json_encode(Array('code' => $VIEW->parse()));
				break;
				case 'doAdd':
					$aReturn = $this->handleResponseAdd();
					
					if($aReturn !== false) {
						// Try to apply the changes
						if($this->AUTHENTICATION->createUser($aReturn['user'], $aReturn['password'])) {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The user has been created.'));
							$sReturn = '';
						} else {
							// Invalid credentials
							$sReturn = $this->msgUserNotCreated();
						}
					} else {
						$sReturn = $this->msgInputNotValid();
					}
				break;
				case 'getUserRoles':
					// Parse the specific options
					$aVals = $this->getCustomOptions(Array('userId' => MATCH_INTEGER));
					$userId = $aVals['userId'];
					
					// Get current user roles
					$sReturn = json_encode($this->AUTHORISATION->getUserRoles($userId));
				break;
				case 'getAllRoles':
					// Get current permissions of role
					$sReturn = json_encode($this->AUTHORISATION->getAllRoles());
				break;
				case 'doEdit':
					$aReturn = $this->handleResponseEdit();
					
					if($aReturn !== false) {
						if($this->AUTHORISATION->updateUserRoles($aReturn['userId'], $aReturn['roles'])) {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The roles for this user have been updated.'));
							$sReturn = '';
						} else {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('Problem while updating user roles.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
				case 'doDelete':
					$aReturn = $this->handleResponseDelete();
					
					if($aReturn !== false) {
						if($this->AUTHORISATION->deleteUser($aReturn['userId'])) {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('The user has been deleted.'));
							$sReturn = '';
						} else {
							new GlobalMessage('NOTE', $this->CORE->getLang()->getText('Problem while deleting user.'));
							$sReturn = '';
						}
					} else {
						new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
						$sReturn = '';
					}
				break;
			}
		}
		
		return $sReturn;
	}
		
	private function handleResponseDelete() {
		$bValid = true;
		// Validate the response
		
		// Check for needed params
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('userId')) {
			$bValid = false;
		}
		
		// Parse the specific options
		// FIXME: validate
		$userId = intval($this->FHANDLER->get('userId'));
		
		// FIXME: Add check not to delete own user
		
	  // Store response data
	  if($bValid === true) {
		  // Return the data
		  return Array('userId' => $userId);
		} else {
			return false;
		}
	}
	
	private function handleResponseEdit() {
		$bValid = true;
		// Validate the response
		
		// Check for needed params
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('userId')) {
			$bValid = false;
		}
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('rolesSelected')) {
			$bValid = false;
		}
		
		// Parse the specific options
		// FIXME: validate
		$userId = intval($this->FHANDLER->get('userId'));
		
		$aPerms = Array();
		
	  // Store response data
	  if($bValid === true) {
		  // Return the data
		  return Array('userId' => $userId, 'roles' => $this->FHANDLER->get('rolesSelected'));
		} else {
			return false;
		}
	}
	
	private function handleResponseAdd() {
		$bValid = true;
		// Validate the response
		
		// Check for needed params
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('username')) {
			$bValid = false;
		}
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('password1')) {
			$bValid = false;
		}
		if($bValid && !$this->FHANDLER->isSetAndNotEmpty('password2')) {
			$bValid = false;
		}
		
		// Check length limits
		if($bValid && $this->FHANDLER->isLongerThan('username', AUTH_MAX_USERNAME_LENGTH)) {
			$bValid = false;
		}
		if($bValid && $this->FHANDLER->isLongerThan('password1', AUTH_MAX_PASSWORD_LENGTH)) {
			$bValid = false;
		}
		if($bValid && $this->FHANDLER->isLongerThan('password2', AUTH_MAX_PASSWORD_LENGTH)) {
			$bValid = false;
		}
		
		// Check if the user already exists
		
		if($bValid && $this->AUTHENTICATION->checkUserExists($this->FHANDLER->get('username'))) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The username is invalid or does already exist.'));
			
			$bValid = false;
		}
		
		// Check if new passwords are equal
		if($bValid && $this->FHANDLER->get('password1') !== $this->FHANDLER->get('password2')) {
			new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The two passwords are not equal.'));
			
			$bValid = false;
		}
		
		//@todo Escape vars?
		
	  // Store response data
	  if($bValid === true) {
		  // Return the data
		  return Array(
		               'user' => $this->FHANDLER->get('username'),
		               'password' => $this->FHANDLER->get('password1'));
		} else {
			return false;
		}
	}
	
	public function msgInputNotValid() {
		new GlobalMessage('ERROR', $this->CORE->getLang()->getText('You entered invalid information.'));
		return '';
	}
	
	public function msgUserNotCreated() {
		new GlobalMessage('ERROR', $this->CORE->getLang()->getText('The user could not be created.'));
		return '';
	}
}

?>