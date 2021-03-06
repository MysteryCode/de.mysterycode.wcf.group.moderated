<?php

namespace wcf\data\user\group\request;

use wcf\data\DatabaseObject;
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
	 * @var UserProfile
	 */
	protected $applicant;
	
	/**
	 * Returns the profile object of the applicant's user account
	 *
	 * @return UserProfile
	 */
	public function getApplicantProfile() {
		if ($this->applicant === null) {
			$this->applicant = UserProfileRuntimeCache::getInstance()->getObject($this->userID);
		}
		
		return $this->applicant;
	}
	
	/**
	 * Returns the formatted message by the applicant
	 *
	 * @return string
	 */
	public function getFormattedMessage() {
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
	public function getFormattedReply() {
		MessageEmbeddedObjectManager::getInstance()->loadObjects('de.mysterycode.wcf.group.moderated.request.reply', [$this->getObjectID()]);
		
		$processor = new HtmlOutputProcessor();
		$processor->enableUgc = false;
		$processor->process($this->reply, 'de.mysterycode.wcf.group.moderated.request.reply', $this->getObjectID(), false);
		
		return $processor->getHtml();
	}
}
