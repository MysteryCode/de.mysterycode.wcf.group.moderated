{include file='userMenuSidebar'}

{include file='header'}

{hascontent}
	<div class="paginationTop">
		{content}
			{pages print=true assign=pagesLinks controller="ModeratedGroups" link="pageNo=%d"}
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
									<li>{user object=$manager}</li>
								{/foreach}
							{/if}
						{/content}
					</ul>
				</dd>
			</dl>
		{/hascontent}

		<dl>
			<dt>{lang}wcf.acp.group.mmoderated.members{/lang}</dt>
			<dd>
				{$group->memberCount}
			</dd>
		</dl>

		<dl>
			<dt>{lang}wcf.acp.group.mmoderated.requests{/lang}</dt>
			<dd>
				{$group->requestCount}
				{if $group->openRequestCount}
					<br>
					<span class="icon icon16 fa-exclamation-triangle red"></span> {lang}wcf.acp.group.mmoderated.requests.pending{/lang}
				{/if}
			</dd>
		</dl>

		<dl class="wide">
			<dt></dt>
			<dd>
				<ul class="buttonList">
					{if $group->requestCount}
						<li><a class="button small" href="{link controller='GroupRequestList' id=$group->groupID}{/link}"><span>{lang}wcf.acp.group.mmoderated.requests{/lang}</span></a></li>
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

{include file='footer'}
