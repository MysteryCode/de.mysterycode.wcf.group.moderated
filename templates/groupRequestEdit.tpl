{include file='userMenuSidebar'}

{include file='header'}

{@$form->getHtml()}

{if $commentList|count || $commentCanAdd}
	<section id="comments" class="section sectionContainerList">
		<h2 class="sectionTitle">{lang}wcf.global.comments{/lang}{if $formObject->comments} <span class="badge">{#$formObject->comments}</span>{/if}</h2>

		{include file='__commentJavaScript' commentContainerID='requestCommentList'}

		<ul id="requestCommentList" class="commentList containerList" data-can-add="{if $commentCanAdd}true{else}false{/if}" data-object-id="{@$formObject->getObjectID()}" data-object-type-id="{@$commentObjectTypeID}" data-comments="{@$commentList->countObjects()}" data-last-comment-time="{@$lastCommentTime}">
			{if $commentCanAdd}{include file='commentListAddComment' wysiwygSelector='requestCommentListAddComment'}{/if}
			{include file='commentList'}
		</ul>
	</section>
{/if}

<footer class="contentFooter">
	{hascontent}
		<nav class="contentFooterNavigation">
			<ul>
				{content}{event name='contentFooterNavigation'}{/content}
			</ul>
		</nav>
	{/hascontent}
</footer>

{include file='footer'}
