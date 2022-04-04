<?php

namespace wcf\form;

use wcf\data\user\group\MModeratedUserGroup;
use wcf\data\user\group\request\UserGroupRequest;
use wcf\data\user\group\request\UserGroupRequestAction;
use wcf\data\user\group\UserGroup;
use wcf\page\MyGroupsPage;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\wysiwyg\WysiwygFormContainer;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\field\MCTextDisplayFormField;
use wcf\system\form\builder\IFormDocument;
use wcf\system\form\builder\TemplateFormNode;
use wcf\system\menu\user\UserMenu;
use wcf\system\request\LinkHandler;
use wcf\system\WCF;
use wcf\util\HeaderUtil;

/**
 * @property	UserGroupRequest	$formObject
 */
class GroupRequestForm extends AbstractFormBuilderForm {
	/**
	 * @inheritDoc
	 */
	public $loginRequired = true;
	
	/**
	 * @inheritDoc
	 */
	public $objectActionClass = UserGroupRequestAction::class;
	
	/**
	 * @var UserGroup|null
	 */
	protected ?UserGroup $group = null;
	
	/**
	 * @inheritDoc
	 */
	public function readParameters() {
		parent::readParameters();
		
		if (isset($_REQUEST['id'])) {
			$this->formObject = new UserGroupRequest((int) $_REQUEST['id']);
			if (!$this->formObject->getObjectID()) {
				throw new IllegalLinkException();
			}
			if ($this->formObject->userID !== WCF::getUser()->userID) {
				// TODO check for group managers
				throw new PermissionDeniedException();
			}
			
			$this->formAction = 'edit';
			$this->group = $this->formObject->getGroup();
		}
		else if (isset($_REQUEST['groupID'])) {
			$this->group = new UserGroup((int) $_REQUEST['groupID']);
		}
		
		if ($this->group === null || !$this->group->getObjectID()) {
			throw new IllegalLinkException();
		}
		else if ($this->group->isAdminGroup()) {
			throw new PermissionDeniedException();
		}
		else if (!\in_array($this->group->groupType, [MModeratedUserGroup::MODERATED, MModeratedUserGroup::CLOSEDMODERATED, MModeratedUserGroup::OPEN])) {
			throw new PermissionDeniedException();
		}
	}
	
	/**
	 * @inheritDoc
	 */
	protected function createForm() {
		parent::createForm();
		
		$wysiwyg = WysiwygFormContainer::create('message');
		$wysiwyg->label('wcf.acp.group.mmoderated.request.message');
		$wysiwyg->messageObjectType('de.mysterycode.wcf.group.moderated.request.message');
		$wysiwyg->minimumLength(200);
		$wysiwyg->maximumLength(10000);
		$wysiwyg->supportMentions();
		$wysiwyg->supportQuotes();
		$wysiwyg->supportSmilies();
		$wysiwyg->required();
		
		$statement = WCF::getDB()->prepare('
			SELECT	userID
			FROM	wcf1_user_group_manager
			WHERE	groupID = ?
		');
		$statement->execute([$this->group->groupID]);
		$userIDs = $statement->fetchAll(\PDO::FETCH_COLUMN);
		$userList = [];
		if (!empty($userIDs)) {
			$userList = UserProfileRuntimeCache::getInstance()->getObjects($userIDs);
		}
		
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
					TemplateFormNode::create('manager')
						->templateName('groupManagerList')
						->application('wcf')
						->variables([
							'userList' => $userList,
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
				$parameters['data']['userID'] = WCF::getUser()->userID;
				$parameters['data']['username'] = WCF::getUser()->username;
				$parameters['data']['groupID'] = $this->group->getObjectID();
				$parameters['data']['time'] = TIME_NOW;
				
				return $parameters;
			}));
		}
		else {
			$this->form->getDataHandler()->addProcessor(new CustomFormDataProcessor('injectData', function(IFormDocument $document, array $parameters) {
				$parameters['data']['changeTime'] = TIME_NOW;
				
				return $parameters;
			}));
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function saved() {
		parent::saved();
		
		if ($this->formObject === null) {
			HeaderUtil::redirect(LinkHandler::getInstance()->getControllerLink(MyGroupsPage::class));
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function setFormAction() {
		parent::setFormAction();
		
		if ($this->formObject === null) {
			$this->form->action(LinkHandler::getInstance()->getControllerLink(static::class, [
				'groupID' => $this->group->getObjectID(),
			]));
		}
	}
	
	/**
	 * @inheritDoc
	 */
	public function show() {
		// set active tab
		UserMenu::getInstance()->setActiveMenuItem('wcf.user.menu.userGroup.myGroups');
		
		parent::show();
	}
}
