<?php

namespace wcf\data\user\group;

use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

class MModeratedUserGroupAction extends UserGroupAction {
	public function overrideLeaders() {
		$group = $this->getSingleObject();
		$this->readIntegerArray('userIDs', true);
		$userIDs = $this->parameters['userIDs'];
		
		// TODO compatibility
		$delete = WCF::getDB()->prepareStatement("DELETE FROM wcf" . WCF_N . "_user_group_manager WHERE groupID = ?");
		$delete->execute([$group->groupID]);
		
		if (!empty($userIDs)) {
			// TODO compatibility
			$insert = WCF::getDB()->prepareStatement("INSERT INTO wcf" . WCF_N . "_user_group_manager (userID, groupID) VALUES (?, ?)");
			WCF::getDB()->beginTransaction();
			foreach ($userIDs as $userID) $insert->execute([$userID, $group->groupID]);
			WCF::getDB()->commitTransaction();
		}
		
		UserStorageHandler::getInstance()->resetAll('isGroupManager');
		
		UserGroupEditor::resetCache();
	}
	
	public function addLeaders() {
		$group = $this->getSingleObject();
		$this->readIntegerArray('userIDs');
		$userIDs = $this->parameters['userIDs'];
		
		// TODO compatibility
		$insert = WCF::getDB()->prepareStatement("INSERT IGNORE INTO wcf" . WCF_N . "_user_group_manager (userID, groupID) VALUES (?, ?)");
		WCF::getDB()->beginTransaction();
		foreach ($userIDs as $userID) $insert->execute([$userID, $group->groupID]);
		WCF::getDB()->commitTransaction();
		
		UserStorageHandler::getInstance()->resetAll('isGroupManager');
		
		UserGroupEditor::resetCache();
	}
	
	public function validateJoin() {
		$this->getSingleObject();
	}
	
	public function join() {
	
	}
	
	public function validateLeave() {
		$this->getSingleObject();
	}
	
	public function leave() {
	
	}
	
	public function validateCancel() {
		$this->getSingleObject();
	}
	
	public function cancel() {
	
	}
}
