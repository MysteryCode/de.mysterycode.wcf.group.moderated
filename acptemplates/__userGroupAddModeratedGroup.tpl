{if $moderatedGroupTypesEnabled}
	<section class="section">
		<header class="sectionHeader">
			<h2 class="sectionTitle">{lang}wcf.acp.group.mmoderatedGroup{/lang}</h2>
		</header>

		<dl{if $errorType[type]|isset} class="formError"{/if}>
			<dt><label for="type">{lang}wcf.acp.group.mmoderated.type{/lang}</label></dt>
			<dd>
				<select name="type" id="type">
					{foreach from=$moderatedGroupTypesAvailable key=gTypeInt item=gType}
						<option value="{$gTypeInt}"{if $type == $gTypeInt} selected{/if}>{lang}wcf.acp.group.mmoderated.type.{$gTypeInt}{/lang}</option>
					{/foreach}
				</select>
				{if $errorType[type]|isset}
					<small class="innerError">
						{lang}wcf.acp.group.mmoderated.type.error.{@$errorType[type]}{/lang}
					</small>
				{/if}
				<small>{lang}wcf.acp.group.mmoderated.type.description{/lang}</small>
			</dd>
		</dl>

		<dl{if $errorType[manager]|isset} class="formError"{/if}>
			<dt><label for="manager">{lang}wcf.acp.group.mmoderated.manager{/lang}</label></dt>
			<dd>
				<input type="text" id="manager" name="manager" value="{$manager}" class="long" />
				{if $errorType[manager]|isset}
					<small class="innerError">
						{lang}wcf.acp.group.mmoderated.manager.error.{@$errorType[manager]}{/lang}
					</small>
				{/if}
				<small>{lang}wcf.acp.group.mmoderated.manager.description{/lang}</small>
			</dd>
		</dl>

		<script data-relocate="true">
			require(['WoltLabSuite/Core/Ui/User/Search/Input'], function (UiUserSearchInput) {
				new UiUserSearchInput(elById('manager'));
			});
		</script>
	</section>
{/if}
