<?php

namespace wcf\data\user\group;

class MModeratedUserGroup extends UserGroup {
	/**
	 * Type for closed groups.
	 * @var integer
	 */
	const CLOSED = 1;
	
	/**
	 * Type for moderated groups.
	 * @var integer
	 */
	const MODERATED = 2;
	
	/**
	 * Type for closed and moderated groups.
	 * @var integer
	 */
	const CLOSEDMODERATED = 3;
	
	/**
	 * Type for open groups.
	 * @var integer
	 */
	const OPEN = 4;
}
