<?php

namespace wcf\data\user\group\request;

use wcf\data\AbstractDatabaseObjectAction;
use wcf\data\user\group\MModeratedUserGroup;
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
		if (!empty($this->parameters['reply_htmlInputProcessor'])) {
			$this->parameters['data']['reply'] = $this->parameters['reply_htmlInputProcessor']->getHtml();
		}
		
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
		if (!empty($this->parameters['reply_htmlInputProcessor'])) {
			/** @noinspection PhpUndefinedMethodInspection */
			foreach ($this->getObjects() as $objectEditor) {
				$this->parameters['reply_htmlInputProcessor']->setObjectID($object->getObjectID());
				if (MessageEmbeddedObjectManager::getInstance()->registerObjects($this->parameters['reply_htmlInputProcessor'])) {
					$objectEditor->update(['replyHasEmbeddedObjects' => 1]);
				}
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
	
	/**
	 * @throws PermissionDeniedException
	 */
	protected function validateStatusChange() : void {
		$request = $this->getSingleObject();
		$group = $request->getGroup();
		
		$cache = UserGroupManagerCacheBuilder::getInstance()->getData();
		if ($group === null || !$group->getObjectID()) {
			throw new PermissionDeniedException();
		}
		else if ($group->isAdminGroup()) {
			throw new PermissionDeniedException();
		}
		else if (!\in_array($group->groupType, [MModeratedUserGroup::MODERATED, MModeratedUserGroup::CLOSEDMODERATED, MModeratedUserGroup::OPEN])) {
			throw new PermissionDeniedException();
		}
		else if (!isset($cache[$group->getObjectID()]) || !\in_array(WCF::getUser()->userID, $cache[$group->getObjectID()])) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * @throws PermissionDeniedException
	 */
	public function validateAccept() : void {
		$this->validateStatusChange();
	}
	
	public function accept() : void {
		$this->parameters['data']['status'] = 'accepted';
		$this->update();
		
		$user = UserRuntimeCache::getInstance()->getObject($this->getSingleObject()->userID);
		(new UserAction([$user], 'addToGroups', [
			'groups' => [$this->getSingleObject()->groupID,
			'deleteOldGroups' => false,
			'addDefaultGroups' => false,
		]]))->executeAction();
	}
	
	/**
	 * @throws PermissionDeniedException
	 */
	public function validateReject() : void {
		$this->validateStatusChange();
	}
	
	public function reject() : void {
		$this->parameters['data']['status'] = 'rejected';
		$this->update();
		
		$user = UserRuntimeCache::getInstance()->getObject($this->getSingleObject()->userID);
		$groupID = $this->getSingleObject()->groupID;
		if (\in_array($groupID, $user->getGroupIDs())) {
			(new UserAction([$user], 'removeFromGroups', [
				'groups' => [$groupID],
			]))->executeAction();
		}
	}
	
	/**
	 * @throws PermissionDeniedException
	 */
	public function validateEnqueue() : void {
		$this->validateStatusChange();
	}
	
	public function enqueue() : void {
		$this->parameters['data']['status'] = 'pending';
		$this->update();
		
		$user = UserRuntimeCache::getInstance()->getObject($this->getSingleObject()->userID);
		$groupID = $this->getSingleObject()->groupID;
		if (\in_array($groupID, $user->getGroupIDs())) {
			(new UserAction([$user], 'removeFromGroups', [
				'groups' => [$groupID],
			]))->executeAction();
		}
	}
}
