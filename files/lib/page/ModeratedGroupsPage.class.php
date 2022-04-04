<?php

namespace wcf\page;

use wcf\acp\page\UserGroupListPage;
use wcf\data\user\group\UserGroup;
use wcf\data\user\UserProfile;
use wcf\system\cache\builder\UserGroupManagerCacheBuilder;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\menu\user\UserMenu;
use wcf\system\WCF;

class ModeratedGroupsPage extends UserGroupListPage {
	/**
	 * @inheritDoc
	 */
	public $loginRequired = true;
	
	/**
	 * @inheritDoc
	 */
	public $neededPermissions = [];
	
	/**
	 * @var UserProfile[]
	 */
	protected array $managers = [];
	
	/**
	 * @inheritDoc
	 */
	protected function initObjectList() {
		parent::initObjectList();
		
		if (!empty($this->objectList->sqlSelects)) $this->objectList->sqlSelects .= ', ';
		$this->objectList->sqlSelects .= '(SELECT COUNT(user_to_group.userID) FROM wcf' . WCF_N . '_user_to_group user_to_group WHERE user_to_group.groupID = user_group.groupID) as memberCount';
		$this->objectList->sqlSelects .= ', ';
		$this->objectList->sqlSelects .= '(SELECT COUNT(requests.userID) FROM wcf' . WCF_N . '_user_group_request requests WHERE requests.groupID = user_group.groupID) as requestCount';
		$this->objectList->sqlSelects .= ', ';
		$this->objectList->sqlSelects .= '(SELECT COUNT(requests_open.userID) FROM wcf' . WCF_N . '_user_group_request requests_open WHERE requests_open.groupID = user_group.groupID AND requests_open.status = \'pending\') as openRequestCount';
		
		$this->objectList->getConditionBuilder()->add('user_group.groupType NOT IN (?)', [[
			UserGroup::EVERYONE,
			UserGroup::GUESTS,
			UserGroup::USERS,
			UserGroup::OTHER,
		]]);
		$this->objectList->getConditionBuilder()->add('user_group.groupID IN (SELECT manager.groupID FROM wcf'.WCF_N.'_user_group_manager manager WHERE manager.userID = ?)', [WCF::getUser()->userID]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function readData() {
		parent::readData();
		
		if (!\count($this->objectList)) {
			throw new PermissionDeniedException();
		}
		
		foreach ($this->objectList->getObjectIDs() as $groupID) {
			$userIDs = UserGroupManagerCacheBuilder::getInstance()->getData()[$groupID] ?? [];
			if (!empty($userIDs)) {
				UserProfileRuntimeCache::getInstance()->cacheObjectIDs($userIDs);
				$this->managers[$groupID] = $userIDs;
			}
		}
		
		foreach ($this->managers as $groupID => $userIDs) {
			$this->managers[$groupID] = UserProfileRuntimeCache::getInstance()->getObjects($userIDs);
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign([
			'managers' => $this->managers,
		]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function show() {
		// set active tab
		UserMenu::getInstance()->setActiveMenuItem('wcf.user.menu.userGroup.moderatedGroups');
		
		parent::show();
	}
}
