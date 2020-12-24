<?php

namespace wcf\system\menu\user;

use wcf\data\user\group\MModeratedUserGroup;

class MyGroupsMenuItemProvider extends DefaultUserMenuItemProvider {
	/**
	 * @inheritDoc
	 */
	public function isVisible() {
		return MModeratedUserGroup::specialGroupsAvailable();
	}
}
