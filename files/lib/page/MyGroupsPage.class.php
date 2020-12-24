<?php

namespace wcf\page;

use wcf\acp\page\UserGroupListPage;
use wcf\data\user\group\request\UserGroupRequest;
use wcf\data\user\group\request\UserGroupRequestList;
use wcf\data\user\UserProfile;
use wcf\system\cache\builder\UserGroupManagerCacheBuilder;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\menu\user\UserMenu;
use wcf\system\WCF;

class MyGroupsPage extends UserGroupListPage {
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
	protected $managers = [];
	
	/**
	 * @var UserGroupRequest[]
	 */
	protected $requests = [];
	
	/**
	 * @inheritDoc
	 */
	protected function initObjectList() {
		parent::initObjectList();
		
		$this->objectList->getConditionBuilder()->add('user_group.groupType IN (?)', [[5, 6, 7]]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function readData() {
		parent::readData();
		
		if (!count($this->objectList)) {
			throw new PermissionDeniedException();
		}
		
		foreach ($this->objectList->getObjectIDs() as $groupID) {
			$userIDs = UserGroupManagerCacheBuilder::getInstance()->getData()[$groupID] ?? [];
			if (!empty($userIDs)) {
				UserProfileRuntimeCache::getInstance()->cacheObjectIDs($userIDs);
				$this->managers[$groupID] = $userIDs;
			}
		}
		
		$requestList = new UserGroupRequestList();
		$requestList->readObjects();
		foreach ($requestList as $request) {
			$this->requests[$request->groupID] = $request;
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
			'requests' => $this->requests
		]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function show() {
		// set active tab
		UserMenu::getInstance()->setActiveMenuItem('wcf.user.menu.userGroup.myGroups');
		
		parent::show();
	}
}
