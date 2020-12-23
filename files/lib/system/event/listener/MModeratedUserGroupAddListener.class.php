<?php

namespace wcf\system\event\listener;

use wcf\acp\form\UserGroupAddForm;
use wcf\acp\form\UserGroupEditForm;
use wcf\data\package\PackageCache;
use wcf\data\user\group\MModeratedUserGroup;
use wcf\data\user\group\MModeratedUserGroupAction;
use wcf\data\user\group\UserGroup;
use wcf\data\user\UserProfile;
use wcf\system\exception\UserInputException;
use wcf\system\WCF;
use wcf\util\ArrayUtil;

class MModeratedUserGroupAddListener implements IParameterizedEventListener {
	/**
	 * @var boolean
	 */
	protected $available = true;
	
	/**
	 * @var mixed[]
	 */
	protected $types = [];
	
	/**
	 * @var integer
	 */
	protected $type = 4;
	
	/**
	 * @var string[]
	 */
	protected $managers = [];
	
	/**
	 * @var UserProfile[]
	 */
	protected $managerProfiles = [];
	
	/**
	 * @inheritDoc
	 */
	public function execute($eventObj, $className, $eventName, array &$parameters) {
		/** @var UserGroupAddForm $eventObj */
		
		if ($eventObj instanceof UserGroupEditForm && $eventObj->group && in_array($eventObj->group->groupType, [UserGroup::EVERYONE, UserGroup::GUESTS, UserGroup::OWNER, UserGroup::USERS])) {
			$this->available = false;
		}
		
		if (PackageCache::getInstance()->getPackageID('com.woltlab.wcf.moderatedUserGroup')) {
			// TODO compatibility
		}
		
		if ($eventName == 'readFormParameters' && $this->available) {
			if (!empty($_POST['type'])) $this->type = intval($_POST['type']);
			if (!empty($_POST['manager'])) {
				$this->managers = ArrayUtil::trim(explode(',', $_POST['manager']));
				$this->managerProfiles = UserProfile::getUserProfilesByUsername($this->managers);
			}
		} else if ($eventName == 'readData' && $this->available) {
			$this->types[MModeratedUserGroup::CLOSED] = 'closed';
			$this->types[MModeratedUserGroup::MODERATED] = 'moderated';
			$this->types[MModeratedUserGroup::OPEN] = 'open';
			
			if (empty($_POST) && $eventObj instanceof UserGroupEditForm) {
				$statement = WCF::getDB()->prepareStatement("SELECT user.username FROM wcf" . WCF_N . "_user_group_manager manager, wcf" . WCF_N . "_user user WHERE user.userID = manager.userID AND manager.groupID = ?");
				$statement->execute([$eventObj->group->groupID]);
				$usernames = $statement->fetchList('username');
				$this->managers = implode(', ', $usernames);
				$this->managerProfiles = UserProfile::getUserProfilesByUsername($usernames);
			}
		} else if ($eventName == 'validate' && $this->available) {
			if (empty($this->type)) {
				throw new UserInputException('type');
			}
			if ($this->type == MModeratedUserGroup::MODERATED && empty($this->managers)) {
				throw new UserInputException('manager');
			}
		} else if ($eventName == 'save' && $this->available) {
			$eventObj->additionalFields['type'] = $this->type;
			
			if ($eventObj instanceof UserGroupEditForm) {
				$userIDs = [];
				foreach ($this->managerProfiles as $profile) {
					$userIDs[] = $profile->userID;
				}
				(new MModeratedUserGroupAction([$eventObj->group->groupID], 'overrideLeaders', ['userIDs' => $userIDs]))->executeAction();
			}
		} else if ($eventName == 'saved' && $this->available) {
			if (!($eventObj instanceof UserGroupEditForm)) {
				$groupID = $eventObj->objectAction->getReturnValues()['returnValues']->groupID;
				$userIDs = [];
				foreach ($this->managerProfiles as $profile) {
					$userIDs[] = $profile->userID;
				}
				(new MModeratedUserGroupAction([$groupID], 'overrideLeaders', ['userIDs' => $userIDs]))->executeAction();
			}
		} else if ($eventName == 'assignVariables') {
			WCF::getTPL()->assign([
				'moderatedGroupTypesEnabled' => $this->available,
				'moderatedGroupTypesAvailable' => $this->types
			]);
		}
	}
}
