<?php

namespace wcf\data\user\group\request;

use wcf\data\DatabaseObjectEditor;

/**
 * @method	UserGroupRequest	create()
 * @mixin	UserGroupRequest
 */
class UserGroupRequestEditor extends DatabaseObjectEditor {
	/**
	 * @inheritDoc
	 */
	protected static $baseClass = UserGroupRequest::class;
}
