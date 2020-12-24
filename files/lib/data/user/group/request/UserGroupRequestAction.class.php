<?php

namespace wcf\data\user\group\request;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\user\group\MModeratedUserGroup;
use wcf\data\user\group\UserGroup;
use wcf\data\user\UserAction;
use wcf\system\cache\builder\UserGroupManagerCacheBuilder;
use wcf\system\cache\runtime\UserRuntimeCache;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\message\embedded\object\MessageEmbeddedObjectManager;
use wcf\system\message\quote\MessageQuoteManager;
use wcf\system\WCF;

/**
 * @property	UserGroupRequestEditor[]	$objects
 * @method  	UserGroupRequestEditor[]	getObjects()
 * @method  	UserGroupRequestEditor		getSingleObject()
 */
class UserGroupRequestAction extends AbstractDatabaseObjectAction {
	/**
	 * @inheritDoc
	 */
	public function create() {
		if (isset($this->parameters['message_htmlInputProcessor'])) {
			$this->parameters['data']['message'] = $this->parameters['message_htmlInputProcessor']->getHtml();
		}
		
		if (!isset($this->parameters['data']['userID'])) $this->parameters['data']['userID'] = WCF::getUser()->userID;
		if (!isset($this->parameters['data']['username'])) $this->parameters['data']['username'] = WCF::getUser()->username;
		if (!isset($this->parameters['data']['time'])) $this->parameters['data']['time'] = TIME_NOW;
		if (!isset($this->parameters['data']['status'])) $this->parameters['data']['status'] = 'pending';
		
		/** @var UserGroupRequest $object */
		$object = parent::create();
		
		if (isset($this->parameters['removeQuoteIDs']) && !empty($this->parameters['removeQuoteIDs'])) {
			MessageQuoteManager::getInstance()->markQuotesForRemoval($this->parameters['removeQuoteIDs']);
		}
		MessageQuoteManager::getInstance()->removeMarkedQuotes();
		
		if (!empty($this->parameters['message_htmlInputProcessor'])) {
			/** @noinspection PhpUndefinedMethodInspection */
			$this->parameters['message_htmlInputProcessor']->setObjectID($object->getObjectID());
			if (MessageEmbeddedObjectManager::getInstance()->registerObjects($this->parameters['message_htmlInputProcessor'])) {
				$objectEditor = new UserGroupRequestEditor($object);
				$objectEditor->update(['messageHasEmbeddedObjects' => 1]);
			}
		}
		
		return $object;
	}
	
	/**
	 * @inheritDoc
	 */
	public function update() {
		if (!empty($this->parameters['message_htmlInputProcessor'])) {
			$this->parameters['data']['message'] = $this->parameters['message_htmlInputProcessor']->getHtml();
		}
		if (!empty($this->parameters['reply_htmlInputProcessor'])) {
			$this->parameters['data']['reply'] = $this->parameters['reply_htmlInputProcessor']->getHtml();
		}
		
		if (!isset($this->parameters['data']['changeTime'])) $this->parameters['data']['changeTime'] = TIME_NOW;
		
		if (isset($this->parameters['removeQuoteIDs']) && !empty($this->parameters['removeQuoteIDs'])) {
			MessageQuoteManager::getInstance()->markQuotesForRemoval($this->parameters['removeQuoteIDs']);
		}
		MessageQuoteManager::getInstance()->removeMarkedQuotes();
		
		parent::update();
		
		if (!empty($this->parameters['message_htmlInputProcessor'])) {
			/** @noinspection PhpUndefinedMethodInspection */
			foreach ($this->getObjects() as $objectEditor) {
				$this->parameters['message_htmlInputProcessor']->setObjectID($objectEditor->getObjectID());
				if (MessageEmbeddedObjectManager::getInstance()->registerObjects($this->parameters['message_htmlInputProcessor'])) {
					$objectEditor->update(['messageHasEmbeddedObjects' => 1]);
				}
			}
		}
		if (!empty($this->parameters['reply_htmlInputProcessor'])) {
			/** @noinspection PhpUndefinedMethodInspection */
			foreach ($this->getObjects() as $objectEditor) {
				$this->parameters['reply_htmlInputProcessor']->setObjectID($objectEditor->getObjectID());
				if (MessageEmbeddedObjectManager::getInstance()->registerObjects($this->parameters['reply_htmlInputProcessor'])) {
					$objectEditor->update(['replyHasEmbeddedObjects' => 1]);
				}
			}
		}
	}
	
	protected function validateStatusChange() {
		$request = $this->getSingleObject();
		$group = new UserGroup($request->groupID);
		
		$cache = UserGroupManagerCacheBuilder::getInstance()->getData();
		if ($group === null || !$group->groupID) {
			throw new PermissionDeniedException();
		}
		else if ($group->isAdminGroup()) {
			throw new PermissionDeniedException();
		} else if (!in_array($group->groupType, [MModeratedUserGroup::MODERATED, MModeratedUserGroup::CLOSEDMODERATED, MModeratedUserGroup::OPEN])) {
			throw new PermissionDeniedException();
		} else if (!isset($cache[$group->groupID]) || !in_array(WCF::getUser()->userID, $cache[$group->groupID])) {
			throw new PermissionDeniedException();
		}
	}
	
	public function validateAccept() {
		$this->validateStatusChange();
	}
	
	public function accept() {
		$this->parameters['data']['status'] = 'accepted';
		$this->update();
		
		$user = UserRuntimeCache::getInstance()->getObject($this->getSingleObject()->userID);
		(new UserAction([$user], 'addToGroups', [
			'groups' => [$this->getSingleObject()->groupID,
			'deleteOldGroups' => false,
			'addDefaultGroups' => false
		]]))->executeAction();
	}
	
	public function validateReject() {
		$this->validateStatusChange();
	}
	
	public function reject() {
		$this->parameters['data']['status'] = 'rejected';
		$this->update();
		
		$user = UserRuntimeCache::getInstance()->getObject($this->getSingleObject()->userID);
		$groupID = $this->getSingleObject()->groupID;
		if (in_array($groupID, $user->getGroupIDs())) {
			(new UserAction([$user], 'removeFromGroups', ['groups' => [$groupID]]))->executeAction();
		}
	}
	
	public function validateEnqueue() {
		$this->validateStatusChange();
	}
	
	public function enqueue() {
		$this->parameters['data']['status'] = 'pending';
		$this->update();
		
		$user = UserRuntimeCache::getInstance()->getObject($this->getSingleObject()->userID);
		$groupID = $this->getSingleObject()->groupID;
		if (in_array($groupID, $user->getGroupIDs())) {
			(new UserAction([$user], 'removeFromGroups', ['groups' => [$groupID]]))->executeAction();
		}
	}
}
