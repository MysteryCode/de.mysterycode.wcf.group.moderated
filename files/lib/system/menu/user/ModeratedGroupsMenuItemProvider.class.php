<?php

namespace wcf\system\menu\user;

use wcf\data\user\group\MModeratedUserGroup;

class ModeratedGroupsMenuItemProvider extends DefaultUserMenuItemProvider {
	/**
	 * @inheritDoc
	 */
	public function isVisible() {
		return MModeratedUserGroup::isGroupManager();
	}
}
