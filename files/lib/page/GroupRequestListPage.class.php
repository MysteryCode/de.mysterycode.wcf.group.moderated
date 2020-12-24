<?php

namespace wcf\page;

use wcf\data\user\group\request\UserGroupRequestList;
use wcf\data\user\group\UserGroup;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\menu\user\UserMenu;
use wcf\system\WCF;

/**
 * @property	UserGroupRequestList	$objectList
 */
class GroupRequestListPage extends MultipleLinkPage {
	/**
	 * @inheritDoc
	 */
	public $loginRequired = true;
	
	/**
	 * @inheritDoc
	 */
	public $objectListClassName = UserGroupRequestList::class;
	
	/**
	 * @inheritDoc
	 */
	public $sortField = 'time';
	
	/**
	 * @inheritDoc
	 */
	public $sortOrder = 'DESC';
	
	/**
	 * @var UserGroup
	 */
	protected $group;
	
	/**
	 * @inheritDoc
	 */
	public function readParameters() {
		parent::readParameters();
		
		if (isset($_REQUEST['id'])) $this->group = new UserGroup(intval($_REQUEST['id']));
		if ($this->group === null || !$this->group->groupID) {
			throw new IllegalLinkException();
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function checkPermissions() {
		parent::checkPermissions();
		
		$statement = WCF::getDB()->prepareStatement("SELECT userID FROM wcf" . WCF_N . "_user_group_manager WHERE groupID = ?");
		$statement->execute([$this->group->groupID]);
		$managerIDs = $statement->fetchList('userID');
		if (!in_array(WCF::getUser()->userID, $managerIDs)) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * @inheritDoc
	 */
	protected function initObjectList() {
		parent::initObjectList();
		
		$this->objectList->getConditionBuilder()->add('groupID = ?', [$this->group->groupID]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function readData() {
		parent::readData();
		
		foreach ($this->objectList as $request) {
			UserProfileRuntimeCache::getInstance()->cacheObjectID($request->userID);
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign([
			'group' => $this->group
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
