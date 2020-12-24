<?php

namespace wcf\system\comment\manager;

use wcf\data\user\group\MModeratedUserGroup;
use wcf\data\user\group\request\UserGroupRequest;
use wcf\data\user\group\request\UserGroupRequestEditor;
use wcf\data\user\group\UserGroup;
use wcf\system\cache\builder\UserGroupManagerCacheBuilder;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;

class UserGroupRequestCommentManager extends AbstractCommentManager {
	/**
	 * @inheritDoc
	 */
	public function canAdd($objectID) {
		return $this->isAccessible($objectID, true);
	}
	
	/**
	 * @inheritDoc
	 */
	public function canAddWithoutApproval($objectID) {
		return $this->isAccessible($objectID, true);
	}
	
	/**
	 * @inheritDoc
	 */
	protected function canEdit($isOwner) {
		return $isOwner;
	}
	
	/**
	 * @inheritDoc
	 */
	protected function canDelete($isOwner) {
		return $isOwner;
	}
	
	/**
	 * @inheritDoc
	 */
	public function isAccessible($objectID, $validateWritePermission = false) {
		$request = new UserGroupRequest($objectID);
		if ($request === null || !$request->requestID) {
			return false;
		}
		
		$group = new UserGroup($request->groupID);
		
		$cache = UserGroupManagerCacheBuilder::getInstance()->getData();
		if ($group === null || !$group->groupID) {
			return false;
		}
		else if ($group->isAdminGroup()) {
			return false;
		} else if (!in_array($group->groupType, [MModeratedUserGroup::MODERATED, MModeratedUserGroup::CLOSEDMODERATED, MModeratedUserGroup::OPEN])) {
			return false;
		} else if (!isset($cache[$group->groupID]) || !in_array(WCF::getUser()->userID, $cache[$group->groupID])) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @inheritDoc
	 */
	public function getLink($objectTypeID, $objectID) {
		return LinkHandler::getInstance()->getLink('GroupRequestEdit', [
			'id' => $objectID
		], '#comments');
	}
	
	/**
	 * @inheritDoc
	 */
	public function getTitle($objectTypeID, $objectID, $isResponse = false) {
		return '';
	}
	
	/**
	 * @inheritDoc
	 */
	public function updateCounter($objectID, $value) {
		(new UserGroupRequestEditor(new UserGroupRequest($objectID)))->updateCounters([
			'comments' => $value
		]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function isContentAuthor($commentOrResponse) {
		$request = new UserGroupRequest($this->getObjectID($commentOrResponse));
		return $request->userID && $request->userID == $commentOrResponse->userID;
	}
}
