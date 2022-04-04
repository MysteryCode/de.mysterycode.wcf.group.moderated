<?php

namespace wcf\data\user\group;

use wcf\data\DatabaseObjectDecorator;
use wcf\system\user\storage\UserStorageHandler;
use wcf\system\WCF;

class MModeratedUserGroup extends DatabaseObjectDecorator {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = UserGroup::class;
	
	/**
	 * Type for closed groups.
	 * @var integer
	 */
	const CLOSED = 4;
	
	/**
	 * Type for moderated groups.
	 * @var integer
	 */
	const MODERATED = 6;
	
	/**
	 * Type for closed and moderated groups.
	 * @var integer
	 */
	const CLOSEDMODERATED = 7;
	
	/**
	 * Type for open groups.
	 * @var integer
	 */
	const OPEN = 5;
	
	/**
	 * Detects whether the given user is a manager of any group.
	 *
	 * @param	integer|null	$userID
	 * @return	boolean
	 */
	public static function isGroupManager(?int $userID = null) : bool {
		if ($userID === null) {
			$userID = WCF::getUser()->userID;
		}
		if (!$userID) {
			return false;
		}
		
		$var = UserStorageHandler::getInstance()->getField('isGroupManager', $userID);
		if ($var !== null) {
			return (bool) $var;
		}
		
		$statement = WCF::getDB()->prepare('
			SELECT	groupID
			FROM	wcf1_user_group_manager
			WHERE	userID = ?
		');
		$statement->execute([$userID]);
		$result = $statement->fetchArray() !== false;
		
		UserStorageHandler::getInstance()->update($userID, 'isGroupManager', $result);
		return $result;
	}
	
	/**
	 * Detects whether some groups are available that aren't closed (un-moderated) groups.
	 *
	 * @return boolean
	 */
	public static function specialGroupsAvailable() : bool {
		$groups = UserGroup::getGroupsByType([5, 6, 7]);
		foreach ($groups as $group) {
			if (!$group->isAdminGroup()) {
				return true;
			}
		}
		
		return false;
	}
}
