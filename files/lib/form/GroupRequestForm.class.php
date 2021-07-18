<?php

namespace wcf\form;

use wcf\data\user\group\MModeratedUserGroup;
use wcf\data\user\group\request\UserGroupRequest;
use wcf\data\user\group\request\UserGroupRequestAction;
use wcf\data\user\group\UserGroup;
use wcf\system\cache\runtime\UserProfileRuntimeCache;
use wcf\system\exception\IllegalLinkException;
use wcf\system\exception\PermissionDeniedException;
use wcf\system\form\builder\container\FormContainer;
use wcf\system\form\builder\container\wysiwyg\WysiwygFormContainer;
use wcf\system\form\builder\data\processor\CustomFormDataProcessor;
use wcf\system\form\builder\field\MCTextDisplayFormField;
use wcf\system\form\builder\IFormDocument;
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
	 * @var UserGroup
	 */
	protected $group;
	
	/**
	 * @inheritDoc
	 */
	public function readParameters() {
		parent::readParameters();
		
		if (isset($_REQUEST['id'])) {
			$this->formObject = new UserGroupRequest(intval($_REQUEST['id']));
			if ($this->formObject === null || !$this->formObject->requestID) {
				throw new IllegalLinkException();
			}
			if ($this->formObject->userID !== WCF::getUser()->userID) {
				// TODO check for group managers
				throw new PermissionDeniedException();
			}
			$this->formAction = 'edit';
			$this->group = new UserGroup($this->formObject->groupID);
		} else if (isset($_REQUEST['groupID'])) {
			$this->group = new UserGroup(intval($_REQUEST['groupID']));
		}
		
		if ($this->group === null || !$this->group->groupID) {
			throw new IllegalLinkException();
		} else if ($this->group->isAdminGroup()) {
			throw new PermissionDeniedException();
		} else if (!in_array($this->group->groupType, [MModeratedUserGroup::MODERATED, MModeratedUserGroup::CLOSEDMODERATED, MModeratedUserGroup::OPEN])) {
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
		
		$statement = WCF::getDB()->prepareStatement("SELECT userID FROM wcf" . WCF_N . "_user_group_manager WHERE groupID = ?");
		$statement->execute([$this->group->groupID]);
		$userIDs = $statement->fetchList('userID');
		if (!empty($userIDs)) {
			$profiles = UserProfileRuntimeCache::getInstance()->getObjects($userIDs);
			$tpl = <<<TPL
<ul class="inlineList commaSeparated">
	{foreach from=\$userList item=user}
		<li>
			{user object=\$user}
		</li>
	{/foreach}
</ul>
TPL;
			$managertpl = WCF::getTPL()->fetchString(WCF::getTPL()->getCompiler()->compileString('managerList', $tpl, [], true)['template'], ['userList' => $profiles]);
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
					MCTextDisplayFormField::create('manager')
						->label('wcf.acp.group.mmoderated.manager')
						->text($managertpl ?? '')
						->supportHTML()
						->available(!empty($userIDs)),
				]),
			$wysiwyg
		]);
		
		$this->form->getDataHandler()->addProcessor(new CustomFormDataProcessor('injectData', function(IFormDocument $document, array $parameters) {
			$parameters['data']['groupID'] = $this->group->groupID;
			
			return $parameters;
		}));
	}
	
	/**
	 * @inheritDoc
	 */
	public function saved() {
		parent::saved();
		
		if ($this->formObject === null) HeaderUtil::redirect(LinkHandler::getInstance()->getLink('MyGroups'));
	}
	
	/**
	 * @inheritDoc
	 */
	public function setFormAction() {
		parent::setFormAction();
		
		if ($this->formObject === null) $this->form->action(LinkHandler::getInstance()->getControllerLink(static::class, ['groupID' => $this->group->groupID]));
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
