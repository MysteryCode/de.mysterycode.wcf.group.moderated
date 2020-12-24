<?php

namespace wcf\system\cache\builder;

use wcf\system\WCF;

class UserGroupManagerCacheBuilder extends AbstractCacheBuilder {
	protected function rebuild(array $parameters) {
		$statement = WCF::getDB()->prepareStatement("SELECT groupID, userID FROM wcf" . WCF_N . "_user_group_manager");
		$statement->execute();
		return $statement->fetchMap('groupID', 'userID', false);
	}
}
