<?php

namespace wcf\system\event\listener;

use wcf\acp\form\UserGroupAddForm;
use wcf\acp\form\UserGroupEditForm;
use wcf\data\user\group\MModeratedUserGroup;
use wcf\data\user\group\MModeratedUserGroupAction;
use wcf\data\user\group\UserGroup;
use wcf\data\user\UserProfile;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;
use wcf\util\ArrayUtil;

class MModeratedUserGroupAddListener extends AbstractEventListener {
	/**
	 * @var boolean
	 */
	protected bool $available = true;
	
	/**
	 * @var mixed[]
	 */
	protected array $types = [];
	
	/**
	 * @var integer
	 */
	protected int $type = 4;
	
	/**
	 * @var string[]
	 */
	protected array $managers = [];
	
	/**
	 * @var UserProfile[]
	 */
	protected array $managerProfiles = [];
	
	/**
	 * @param    UserGroupAddForm    $eventObj
	 * @param    array               $parameters
	 */
	public function onReadParameters(UserGroupAddForm $eventObj, array &$parameters) : void {
		if ($eventObj instanceof UserGroupEditForm && $eventObj->group && \in_array($eventObj->group->groupType, [UserGroup::EVERYONE, UserGroup::GUESTS, UserGroup::OWNER, UserGroup::USERS])) {
			$this->available = false;
		}
		
		#if (PackageCache::getInstance()->getPackageID('com.woltlab.wcf.moderatedUserGroup')) {
		#	// TODO compatibility
		#}
	}
	
	/**
	 * @param    UserGroupAddForm    $eventObj
	 * @param    array               $parameters
	 */
	public function onReadFormParameters(UserGroupAddForm $eventObj, array &$parameters) : void {
		if (!$this->available) {
			return;
		}
		
		if (!empty($_POST['type'])) {
			$this->type = (int) $_POST['type'];
		}
		if (!empty($_POST['manager'])) {
			$this->managers = ArrayUtil::trim(\explode(',', $_POST['manager']));
			$this->managerProfiles = UserProfile::getUserProfilesByUsername($this->managers);
		}
	}
	
	/**
	 * @param    UserGroupAddForm    $eventObj
	 * @param    array               $parameters
	 */
	public function onReadData(UserGroupAddForm $eventObj, array &$parameters) : void {
		if (!$this->available) {
			return;
		}
		
		$this->types = [
			MModeratedUserGroup::CLOSED => 'closed',
			MModeratedUserGroup::MODERATED => 'moderated',
			MModeratedUserGroup::CLOSEDMODERATED => 'closedmoderated',
			MModeratedUserGroup::OPEN => 'open',
		];
		
		if (empty($_POST) && $eventObj instanceof UserGroupEditForm) {
			$statement = WCF::getDB()->prepare('
					SELECT	user.username
					FROM	wcf1_user_group_manager manager, wcf1_user user
					WHERE	user.userID = manager.userID
						AND manager.groupID = ?
				');
			$statement->execute([$eventObj->group->groupID]);
			$usernames = $statement->fetchAll(\PDO::FETCH_COLUMN);
			$this->managerProfiles = UserProfile::getUserProfilesByUsername($usernames);
			foreach ($this->managerProfiles as $profile) {
				$this->managers[] = $profile->getUsername();
			}
			$this->type = $eventObj->group->groupType;
		}
	}
	
	/**
	 * @param    UserGroupAddForm    $eventObj
	 * @param    array               $parameters
	 */
	public function onValidate(UserGroupAddForm $eventObj, array &$parameters) : void {
		if (!$this->available) {
			return;
		}
		
		if (empty($this->type)) {
			throw new UserInputException('type');
		}
		if ($this->type == MModeratedUserGroup::MODERATED && empty($this->managers)) {
			throw new UserInputException('manager');
		}
	}
	
	/**
	 * @param    UserGroupAddForm    $eventObj
	 * @param    array               $parameters
	 */
	public function onSave(UserGroupAddForm $eventObj, array &$parameters) : void {
		if (!$this->available) {
			return;
		}
		
		// TODO compatibility
		$eventObj->additionalFields['groupType'] = $this->type;
		
		if ($eventObj instanceof UserGroupEditForm) {
			$userIDs = [];
			foreach ($this->managerProfiles as $profile) {
				$userIDs[] = $profile->userID;
			}
			(new MModeratedUserGroupAction([$eventObj->group->groupID], 'overrideLeaders', ['userIDs' => $userIDs]))->executeAction();
		}
	}
	
	/**
	 * @param    UserGroupAddForm    $eventObj
	 * @param    array               $parameters
	 */
	public function onSaved(UserGroupAddForm $eventObj, array &$parameters) : void {
		if (!$this->available) {
			return;
		}
		
		if (!($eventObj instanceof UserGroupEditForm)) {
			$groupID = $eventObj->objectAction->getReturnValues()['returnValues']->groupID;
			$userIDs = [];
			foreach ($this->managerProfiles as $profile) {
				$userIDs[] = $profile->userID;
			}
			(new MModeratedUserGroupAction([$groupID], 'overrideLeaders', ['userIDs' => $userIDs]))->executeAction();
		}
	}
	
	/**
	 * @param    UserGroupAddForm    $eventObj
	 * @param    array               $parameters
	 */
	public function onAssignVariables(UserGroupAddForm $eventObj, array &$parameters) : void {
		WCF::getTPL()->assign([
			'moderatedGroupTypesEnabled' => $this->available,
			'moderatedGroupTypesAvailable' => $this->types,
			'manager' => \implode(', ', $this->managers),
			'type' => $this->type,
		]);
	}
}
