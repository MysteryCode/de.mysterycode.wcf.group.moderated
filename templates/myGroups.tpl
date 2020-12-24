{include file='userMenuSidebar'}

{include file='header'}

{hascontent}
	<div class="paginationTop">
		{content}
			{pages print=true assign=pagesLinks controller="MyGroups" link="pageNo=%d"}
		{/content}
	</div>
{/hascontent}

{foreach from=$objects item=group}
	<section class="section groupInfoSection">
		<header class="sectionHeader">
			<h2 class="sectionTitle">
				{if $group->isMember()}<span class="icon icon16 fa-check green jsTooltip" title="{lang}wcf.acp.group.mmoderated.member.true{/lang}"></span>{/if}
				{$group->getName()}
				<span class="badge label">{lang}wcf.acp.group.mmoderated.type.{$group->groupType}{/lang}</span>
			</h2>
			<p class="sectionDescription">{@$group->getDescription()|newlineToBreak}</p>
		</header>
		
		{hascontent}
			<dl>
				<dt>{lang}wcf.acp.group.mmoderated.manager{/lang}</dt>
				<dd>
					<ul class="inlineList commaSeparated">
						{content}
							{if !$managers[$group->groupID]|empty}
								{foreach from=$managers[$group->groupID] item=manager}
									<li>{user object=$manager type='plain'}</li>
								{/foreach}
							{/if}
						{/content}
					</ul>
				</dd>
			</dl>
		{/hascontent}

		{if !$group->isMember() && $requests[$group->groupID]|isset}
			{assign var=request value=$requests[$group->groupID]}
			<dl>
				<dt>{lang}wcf.acp.group.mmoderated.request.status{/lang}</dt>
				<dd>
					{lang}wcf.acp.group.mmoderated.request.status.{$request->status}{/lang}
					{if $request->status == 'rejected' && !$request->reply|empty}
						<div class="error">{$request->reply}</div>
					{/if}
				</dd>
			</dl>
		{else}
			{assign var=request value=null}
		{/if}

		<dl class="wide">
			<dt></dt>
			<dd>
				<ul class="buttonList">
					{if !$group->isMember() && !$request}
						<li><a class="button small" href="{link controller='GroupRequest' groupID=$group->groupID}{/link}"><span>{lang}wcf.acp.group.mmoderated.apply{/lang}</span></a></li>
					{else if !$group->isMember() && $request}
						<li><a class="button small" href="{link controller='GroupRequest' id=$request->getObjectID()}{/link}"><span>{lang}wcf.acp.group.mmoderated.request.edit{/lang}</span></a></li>
						<li><span class="button small jsCancelButton" data-group-id="{$group->groupID}" data-request-id="{$request->requestID}">{lang}wcf.acp.group.mmoderated.request.cancel{/lang}</span></li>
					{else if $group->isMember()}
						<li><span class="button small jsLeaveButton" data-group-id="{$group->groupID}">{lang}wcf.acp.group.mmoderated.leave{/lang}</span></li>
					{/if}
				</ul>
			</dd>
		</dl>
	</section>
{/foreach}

<footer class="contentFooter">
	{hascontent}
		<div class="paginationBottom">
			{content}{@$pagesLinks}{/content}
		</div>
	{/hascontent}
	
	{hascontent}
		<nav class="contentFooterNavigation">
			<ul>
				{content}{event name='contentFooterNavigation'}{/content}
			</ul>
		</nav>
	{/hascontent}
</footer>

<script data-relocate="true">
	require(['Language', 'Ui/Confirmation', 'Ajax'], function (Language, UiConfirmation, Ajax) {
		Language.addObject({
			'wcf.acp.group.mmoderated.request.cancel.confirm': '{lang}wcf.acp.group.mmoderated.request.cancel.confirm{/lang}',
			'wcf.acp.group.mmoderated.request.leave.confirm': '{lang}wcf.acp.group.mmoderated.request.leave.confirm{/lang}',
		})

		elBySel('.jsCancelButton').addEventListener(WCF_CLICK_EVENT, function (event) {
			var element = event.currentTarget;
			UiConfirmation.show({
				confirm: function () {
					Ajax.apiOnce({
						data: {
							actionName: 'cancel',
							className: 'wcf\\data\\user\\group\\MModeratedUserGroupAction',
							objectIDs: [ elData(element, 'group-id') ],
							parameters: {
								requestID: elData(element, 'request-id')
							}
						},
						success: function() {
							window.location.reload();
						}
					})
				},
				message: Language.get('wcf.acp.group.mmoderated.request.cancel.confirm')
			});
		});
		elBySel('.jsLeaveButton').addEventListener(WCF_CLICK_EVENT, function (event) {
			var element = event.currentTarget;
			UiConfirmation.show({
				confirm: function () {
					Ajax.apiOnce({
						data: {
							actionName: 'leave',
							className: 'wcf\\data\\user\\group\\MModeratedUserGroupAction',
							objectIDs: [ elData(element, 'group-id') ]
						},
						success: function() {
							window.location.reload();
						}
					})
				},
				message: Language.get('wcf.acp.group.mmoderated.leave.confirm')
			});
		});
	})
</script>

{include file='footer'}
