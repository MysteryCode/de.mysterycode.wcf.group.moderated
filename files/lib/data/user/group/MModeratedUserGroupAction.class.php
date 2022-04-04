<?php

namespace wcf\data\user\group;

use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

class MModeratedUserGroupAction extends UserGroupAction {
	public function overrideLeaders() : void {
		$group = $this->getSingleObject();
		$this->readIntegerArray('userIDs', true);
		$userIDs = $this->parameters['userIDs'];
		
		// TODO compatibility
		$delete = WCF::getDB()->prepare('
			DELETE FROM     wcf1_user_group_manager
			WHERE		groupID = ?
		');
		$delete->execute([$group->groupID]);
		
		if (!empty($userIDs)) {
			// TODO compatibility
			$insert = WCF::getDB()->prepare('
				INSERT INTO     wcf1_user_group_manager
				        	(userID, groupID)
		                VALUES		(?, ?)
	                ');
			WCF::getDB()->beginTransaction();
			foreach ($userIDs as $userID) {
				$insert->execute([$userID, $group->groupID]);
			}
			WCF::getDB()->commitTransaction();
		}
		
		UserStorageHandler::getInstance()->resetAll('isGroupManager');
		
		UserGroupEditor::resetCache();
	}
	
	public function addLeaders() : void {
		$group = $this->getSingleObject();
		$this->readIntegerArray('userIDs');
		$userIDs = $this->parameters['userIDs'];
		
		// TODO compatibility
		$insert = WCF::getDB()->prepare('
			INSERT IGNORE INTO	wcf1_user_group_manager
						(userID, groupID)
			VALUES			(?, ?)
		');
		WCF::getDB()->beginTransaction();
		foreach ($userIDs as $userID) {
			$insert->execute([$userID, $group->groupID]);
		}
		WCF::getDB()->commitTransaction();
		
		UserStorageHandler::getInstance()->resetAll('isGroupManager');
		
		UserGroupEditor::resetCache();
	}
	
	public function validateJoin() : void {
		$this->getSingleObject();
	}
	
	public function join() : void {
	
	}
	
	public function validateLeave() : void {
		$this->getSingleObject();
	}
	
	public function leave() : void {
	
	}
	
	public function validateCancel() : void {
		$this->getSingleObject();
	}
	
	public function cancel() : void {
	
	}
}
