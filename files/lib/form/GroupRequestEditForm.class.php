<?php

namespace wcf\form;

use wcf\data\comment\StructuredCommentList;
use wcf\data\user\group\MModeratedUserGroup;
use wcf\data\user\group\request\UserGroupRequest;
use wcf\data\user\group\request\UserGroupRequestAction;
use wcf\data\user\group\UserGroup;
use wcf\system\cache\builder\UserGroupManagerCacheBuilder;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\comment\CommentHandler;
use wcf\system\comment\manager\ICommentManager;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\wysiwyg\WysiwygFormContainer;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\field\MCTextDisplayFormField;
use wcf\system\form\builder\field\SingleSelectionFormField;
use wcf\system\form\builder\IFormDocument;
use wcf\system\menu\user\UserMenu;
use wcf\system\WCF;

/**
 * @property	UserGroupRequest	$formObject
 */
class GroupRequestEditForm extends AbstractFormBuilderForm {
	/**
	 * @inheritDoc
	 */
	public $loginRequired = true;
	
	/**
	 * @inheritDoc
	 */
	public $objectActionClass = UserGroupRequestAction::class;
	
	/**
	 * @inheritDoc
	 */
	public $formAction = 'edit';
	
	/**
	 * @inheritDoc
	 */
	public $templateName = 'groupRequestEdit';
	
	/**
	 * @var UserGroup|null
	 */
	protected ?UserGroup $group = null;
	
	/**
	 * comment object type id
	 * @var	integer
	 */
	public int $commentObjectTypeID;
	
	/**
	 * comment manager object
	 * @var	ICommentManager
	 */
	public ICommentManager $commentManager;
	
	/**
	 * list of comments
	 * @var	StructuredCommentList
	 */
	public StructuredCommentList $commentList;
	
	/**
	 * @inheritDoc
	 */
	public function readParameters() {
		parent::readParameters();
		
		if (isset($_REQUEST['id'])) {
			$this->formObject = new UserGroupRequest((int) $_REQUEST['id']);
		}
		if ($this->formObject === null || !$this->formObject->getObjectID()) {
			throw new IllegalLinkException();
		}
		
		$this->group = $this->formObject->getGroup();
		$cache = UserGroupManagerCacheBuilder::getInstance()->getData();
		if ($this->group === null || !$this->group->getObjectID()) {
			throw new IllegalLinkException();
		}
		else if ($this->group->isAdminGroup() || !$this->group->isAccessible()) {
			throw new PermissionDeniedException();
		}
		else if (!in_array($this->group->groupType, [MModeratedUserGroup::MODERATED, MModeratedUserGroup::CLOSEDMODERATED, MModeratedUserGroup::OPEN])) {
			throw new PermissionDeniedException();
		}
		else if (!isset($cache[$this->group->groupID]) || !\in_array(WCF::getUser()->userID, $cache[$this->group->groupID])) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function readData() {
		parent::readData();
		
		$this->commentObjectTypeID = CommentHandler::getInstance()->getObjectTypeID('de.mysterycode.wcf.group.moderated.request');
		$this->commentManager = CommentHandler::getInstance()->getObjectType($this->commentObjectTypeID)->getProcessor();
		$this->commentList = CommentHandler::getInstance()->getCommentList($this->commentManager, $this->commentObjectTypeID, $this->formObject->getObjectID());
	}
	
	/**
	 * @inheritDoc
	 */
	protected function createForm() {
		parent::createForm();
		
		$wysiwyg = WysiwygFormContainer::create('reply');
		$wysiwyg->label('wcf.acp.group.mmoderated.request.reply');
		$wysiwyg->messageObjectType('de.mysterycode.wcf.group.moderated.request.reply');
		$wysiwyg->supportMentions();
		$wysiwyg->supportQuotes();
		$wysiwyg->supportSmilies();
		
		$this->form->appendChildren([
			FormContainer::create('groupData')
				->label('wcf.acp.group.mmoderated.request.groupData')
				->appendChildren([
					MCTextDisplayFormField::create('groupName')
						->label('wcf.acp.group.mmoderated.request.groupName')
						->text($this->group->getName()),
					MCTextDisplayFormField::create('groupDescription')
						->label('wcf.acp.group.mmoderated.request.groupDescription')
						->text($this->group->getDescription())
						->available(!empty($this->group->groupDescription)),
				]),
			FormContainer::create('requestData')
				->label('wcf.acp.group.mmoderated.request.requestData')
				->appendChildren([
					MCTextDisplayFormField::create('username')
						->label('wcf.acp.group.mmoderated.request.user')
						->text(WCF::getTPL()->fetchString(WCF::getTPL()->getCompiler()->compileString('username', '{user object=$user}', [], true)['template'], ['user' => UserProfileRuntimeCache::getInstance()->getObject($this->formObject->userID)]))
						->supportHTML(),
					MCTextDisplayFormField::create('message')
						->label('wcf.acp.group.mmoderated.request.message')
						->text($this->formObject->getFormattedMessage())
						->supportHTML(),
					SingleSelectionFormField::create('status')
						->label('wcf.acp.group.mmoderated.request.status')
						->options([
							'pending' => 'wcf.acp.group.mmoderated.request.status.pending',
							'accepted' => 'wcf.acp.group.mmoderated.request.status.accepted',
							'rejected' => 'wcf.acp.group.mmoderated.request.status.rejected',
						]),
				]),
			$wysiwyg,
		]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function buildForm() {
		parent::buildForm();
		
		if ($this->formAction === 'create') {
			$this->form->getDataHandler()->addProcessor(new CustomFormDataProcessor('injectData', function(IFormDocument $document, array $parameters) {
				$parameters['data']['groupID'] = $this->group->getObjectID();
				
				return $parameters;
			}));
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function save() {
		$status = $this->form->getNodeById('status')->getValue();
		if ($status === 'accepted' && $this->formObject->status !== $status) {
			$this->objectActionName = 'accept';
		}
		else if ($status === 'rejected' && $this->formObject->status !== $status) {
			$this->objectActionName = 'reject';
		}
		else if ($status === 'pending' && $this->formObject->status !== $status) {
			$this->objectActionName = 'enqueue';
		}
		
		parent::save();
	}
	
	/**
	 * @inheritDoc
	 */
	public function assignVariables() {
		parent::assignVariables();
		
		WCF::getTPL()->assign([
			'commentCanAdd' => true,
			'commentList' => $this->commentList,
			'commentObjectTypeID' => $this->commentObjectTypeID,
			'lastCommentTime' => $this->commentList->getMinCommentTime(),
		]);
	}
	
	/**
	 * @inheritDoc
	 */
	public function show() {
		// set active tab
		UserMenu::getInstance()->setActiveMenuItem('wcf.user.menu.userGroup.moderatedGroups');
		
		parent::show();
	}
}
