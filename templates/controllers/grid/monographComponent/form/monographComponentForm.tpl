{**
 * monographComponentForm.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Component listing.
 *
 * $Id$
 *}

<h4>{translate key="inserts.monographComponents.heading.newComponent"}</h4>

<form action="{url router=$smarty.const.ROUTE_COMPONENT component="grid.submit.monographComponent.MonographComponentGridHandler" op="updateMonographComponent" monographId=$monographId}" method="post" enctype="multipart/form-data">

<table width="100%" class="data">
<tr valign="top">
	<td width="20%" class="label">{translate key="common.title"}</td>
	<td width="80%" class="value"><input type="text" class="textField" name="title[{$formLocale|escape}]" value="{$monographComponent->getLocalizedTitle()|escape}" size="30" maxlength="255" /></td>
</tr>
{if $workType == WORK_TYPE_EDITED_VOLUME}
<tr valign="top">
	<td width="20%" class="label">{translate key="user.role.authors"}</td>
	<td width="80%">
		<select name="contributors[]" multiple="multiple" size="7" style="width:20em">
			{foreach from=$monographContributors item=contributor}
			<option value="{$contributor->getId()|escape}"{if in_array($contributor->getId(), $contributorIds)} selected="selected"{/if}>{$contributor->getFullName()|escape} ({$contributor->getEmail()|escape})</option>
			{/foreach}
		</select>
	</td>
</tr>
<tr valign="top">
	<td width="20%" class="label">{translate key="user.primaryContact"}</td>
	<td width="80%">
		<select name="primaryContact">
			{foreach from=$monographContributors item=contributor}
			<option value="{$contributor->getId()|escape}"{if $contributor->getId() == $monographComponent->getPrimaryContact()} selected="selected"{/if}>{$contributor->getFullName()|escape} ({$contributor->getEmail()|escape})</option>
			{/foreach}
		</select>
	</td>
</tr>
{/if}
</table>

{if $gridId}
	<input type="hidden" name="gridId" value="{$gridId|escape}" />	
{/if}
{if $monographComponentId}
	<input type="hidden" name="monographComponentId" value="{$monographComponentId|escape}" />
{/if}

</form>
