<dl>
	<dt>{lang}wcf.acp.group.mmoderated.manager{/lang}</dt>
	<dd>
		<ul class="inlineList commaSeparated">
			{foreach from=$userList item=user}
				<li>{user object=$user}</li>
			{/foreach}
		</ul>
	</dd>
</dl>
