<?php

namespace wcf\data\user\group;

class MModeratedUserGroup extends UserGroup {
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
}
