<?php

namespace wcf\system\cache\builder;

use wcf\system\WCF;

class UserGroupManagerCacheBuilder extends AbstractCacheBuilder {
	/**
	 * @inheritDoc
	 */
	protected function rebuild(array $parameters) {
		$statement = WCF::getDB()->prepare('
			SELECT	groupID, userID
			FROM    wcf1_user_group_manager
		');
		$statement->execute();
		
		return $statement->fetchMap('groupID', 'userID', false);
	}
}
