<?php

namespace wcf\data\user\group\request;

use wcf\data\DatabaseObject;
use wcf\data\user\group\UserGroup;
use wcf\data\user\UserProfile;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\html\output\HtmlOutputProcessor;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;

/**
 * @property-read	integer	$requestID
 * @property-read	integer	$userID
 * @property-read	string	$username
 * @property-read	integer	$groupID
 * @property-read	integer	$comments
 * @property-read	integer	$time
 * @property-read	integer	$changeTime
 * @property-read	string	$message
 * @property-read	string	$reply
 * @property-read	string	$status
 */
class UserGroupRequest extends DatabaseObject {
	/**
	 * @var UserProfile|null
	 */
	protected ?UserProfile $applicant;
	
	/**
	 * @var UserGroup|null
	 */
	protected ?UserGroup $userGroup;
	
	/**
	 * Returns the profile object of the applicant's user account
	 *
	 * @return UserProfile
	 */
	public function getApplicantProfile() : UserProfile {
		if ($this->applicant === null) {
			$this->applicant = UserProfileRuntimeCache::getInstance()->getObject($this->userID);
			
			if ($this->applicant === null) {
				$this->applicant = UserProfile::getGuestUserProfile($this->username);
			}
		}
		
		return $this->applicant;
	}
	
	/**
	 * Returns the formatted message by the applicant
	 *
	 * @return string
	 */
	public function getFormattedMessage() : string {
		MessageEmbeddedObjectManager::getInstance()->loadObjects('de.mysterycode.wcf.group.moderated.request.message', [$this->getObjectID()]);
		
		$processor = new HtmlOutputProcessor();
		$processor->enableUgc = false;
		$processor->process($this->message, 'de.mysterycode.wcf.group.moderated.request.message', $this->getObjectID(), false);
		
		return $processor->getHtml();
	}
	
	/**
	 * Returns the formatted reply
	 *
	 * @return string
	 */
	public function getFormattedReply() : string {
		MessageEmbeddedObjectManager::getInstance()->loadObjects('de.mysterycode.wcf.group.moderated.request.reply', [$this->getObjectID()]);
		
		$processor = new HtmlOutputProcessor();
		$processor->enableUgc = false;
		$processor->process($this->reply, 'de.mysterycode.wcf.group.moderated.request.reply', $this->getObjectID(), false);
		
		return $processor->getHtml();
	}
	
	/**
	 * @return UserGroup|null
	 */
	public function getGroup() : ?UserGroup {
		if (!isset($this->userGroup)) {
			$this->userGroup = new UserGroup($this->groupID);
			
			if (!$this->userGroup->getObjectID()) {
				$this->userGroup = null;
			}
		}
		
		return $this->userGroup ?? null;
	}
}
