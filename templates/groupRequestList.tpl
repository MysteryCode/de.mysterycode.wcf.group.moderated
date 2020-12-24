{capture assign='contentTitle'}{lang}wcf.acp.group.mmoderated.request.list.group{/lang}{/capture}
{capture assign='pageTitle'}{lang}wcf.acp.group.mmoderated.request.list.group{/lang}{/capture}

{include file='userMenuSidebar'}

{include file='header'}

{hascontent}
	<div class="paginationTop">
		{content}
			{pages print=true assign=pagesLinks controller="GroupRequestList" id=$group->groupID link="pageNo=%d"}
		{/content}
	</div>
{/hascontent}

<div class="section tabularBox">
	<table class="table">
		<thead>
			<tr>
				<th class="columnTitle" colspan="3">{lang}wcf.acp.group.mmoderated.request.user{/lang}</th>
				<th class="columnText">{lang}wcf.acp.group.mmoderated.request.status{/lang}</th>
				<th class="columnDate">{lang}wcf.acp.group.mmoderated.request.time{/lang}</th>
			</tr>
		</thead>
		<tbody>
			{foreach from=$objects item=request}
				<tr>
					<td class="columnIcon">
						<a href="{link controller='GroupRequestEdit' id=$request->getObjectID()}{/link}"><span class="icon icon16 fa-pencil jsTooltip" title="{lang}wcf.global.button.edit{/lang}"></span></a>
					</td>
					<td class="columnID">{$request->getObjectID()}</td>
					<td class="columnTitle">{user object=$request->getApplicantProfile()}</td>
					<td class="columnText">{lang}wcf.acp.group.mmoderated.request.status.{$request->status}{/lang}</td>
					<td class="columnDate">{@$request->time|plainTime}</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
</div>

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

{include file='footer'}
