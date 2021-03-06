{**
 * index.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * User index.
 *
 * $Id$
 *}
{strip}
{assign var="pageTitle" value="user.userHome"}
{include file="common/header.tpl"}
{/strip}

{if $isSiteAdmin}
	{assign var="hasRole" value=1}
	&#187; <a href="{url press="index" page=$isSiteAdmin->getRolePath()}">{$isSiteAdmin->getRoleName()|escape}</a>
	{call_hook name="Templates::User::Index::Site"}
{/if}

{if $showAllPresses}

<h3>{translate key="user.myPresses"}</h3>

{foreach from=$userPresses item=press}
	{assign var="hasRole" value=1}
	<h4><a href="{url press=$press->getPath() page="user"}">{$press->getLocalizedName()|escape}</a></h4>
	<ul class="plain">
		{assign var="pressId" value=$press->getId()}
		{section name=role loop=$userRoles[$pressId]}
			{if $userRoles[$pressId][role]->getPath() != 'reader'}
				{if $userRoles[$pressId][role]->isCustomRole()}
					{url|assign:"rolePath" press=$press->getPath() page=$userRoles[$pressId][role]->getPath() roleId=$userRoles[$pressId][role]->getId()}
				{else}
					{url|assign:"rolePath" press=$press->getPath() page=$userRoles[$pressId][role]->getPath()}
				{/if}
				<li>&#187; <a href="{$rolePath|escape}">{$userRoles[$pressId][role]->getLocalizedName()|escape}</a></li>
			{/if}
		{/section}
		{call_hook name="Templates::User::Index::Press" press=$press}
	</ul>
{/foreach}

{else}{* $showAllPresses *}

<h3>{$userPress->getLocalizedName()}</h3>
<ul class="plain">
	{if $isSiteAdmin && !$hasOtherPresses}
		{assign var="hasRole" value=1}
		<li>&#187; <a href="{url press="index" page=$isSiteAdmin->getRolePath()}">{translate key=$isSiteAdmin->getRoleName()}</a></li>
	{/if}
	{assign var="pressId" value=$userPress->getId()}
	{section name=role loop=$userRoles[$pressId]}
		{assign var="hasRole" value=1}
		{if $userRoles[$pressId][role]->getPath() != 'reader'}
			{if $userRoles[$pressId][role]->isCustomRole()}
				{url|assign:"rolePath" press=$userPress->getPath() page=$userRoles[$pressId][role]->getPath() roleId=$userRoles[$pressId][role]->getId()}
			{else}
				{url|assign:"rolePath" press=$userPress->getPath() page=$userRoles[$pressId][role]->getPath()}
			{/if}
			<li>&#187; <a href="{$rolePath|escape}">{$userRoles[$pressId][role]->getLocalizedName()|escape}</a></li>
		{/if}
	{/section}
</ul>
{/if}{* $showAllPresses *}

{if !$hasRole}
	{if $currentPress}
		<p>{translate key="user.noRoles.noRolesForPress"}</p>
		<ul class="plain">
			<li>
				&#187;
				{if $allowRegAuthor}
					{url|assign:"submitUrl" page="author" op="submit"}
					<a href="{url op="become" path="author" source=$submitUrl}">{translate key="user.noRoles.submitMonograph"}</a>
				{else}{* $allowRegAuthor *}
					{translate key="user.noRoles.submitMonographRegClosed"}
				{/if}{* $allowRegAuthor *}
			</li>
			<li>
				&#187;
				{if $allowRegReviewer}
					{url|assign:"userHomeUrl" page="user" op="index"}
					<a href="{url op="become" path="reviewer" source=$userHomeUrl}">{translate key="user.noRoles.regReviewer"}</a>
				{else}{* $allowRegReviewer *}
					{translate key="user.noRoles.regReviewerClosed"}
				{/if}{* $allowRegReviewer *}
			</li>
		</ul>
	{else}{* $currentPress *}
		<p>{translate key="user.noRoles.choosePress"}</p>
		<ul class="plain">
			{foreach from=$allPresses item=thisPress}
				<li>&#187; <a href="{url press=$thisPress->getPath() page="user" op="index"}">{$thisPress->getLocalizedName()|escape}</a></li>
			{/foreach}
		</ul>
	{/if}{* $currentPress *}
{/if}{* !$hasRole *}

<h3>{translate key="user.myAccount"}</h3>
<ul class="plain">
	{if $hasOtherPresses}
		{if !$showAllPresses}
			<li>&#187; <a href="{url press="index" page="user"}">{translate key="user.showAllPresses"}</a></li>
		{/if}
	{/if}
	<li>&#187; <a href="{url page="user" op="profile"}">{translate key="user.editMyProfile"}</a></li>

	{if !$implicitAuth}
		<li>&#187; <a href="{url page="user" op="changePassword"}">{translate key="user.changeMyPassword"}</a></li>
	{/if}

	<li>&#187; <a href="{url page="login" op="signOut"}">{translate key="user.logOut"}</a></li>
	{call_hook name="Templates::User::Index::MyAccount"}
</ul>

{include file="common/footer.tpl"}
