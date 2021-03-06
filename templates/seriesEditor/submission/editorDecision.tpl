{**
 * editorDecision.tpl
 *
 * Copyright (c) 2003-2010 John Willinsky
 * Distributed under the GNU GPL v2. For full terms see the file docs/COPYING.
 *
 * Subtemplate defining the editor decision table.
 *
 * $Id$
 *}
<div id="editorDecision">
<h3>{translate key="submission.editorDecision"}</h3>

<table width="100%" class="data">
<tr valign="top">
	<td class="label" width="20%">{translate key="editor.monograph.selectDecision"}</td>
	<td width="80%" class="value">
		<form method="post" action="{url op="recordDecision"}">
			<input type="hidden" name="monographId" value="{$submission->getMonographId()}" />
			<select name="decision" size="1" class="selectMenu"{if not $allowRecommendation} disabled="disabled"{/if}>
				{html_options_translate options=$editorDecisionOptions selected=$lastDecision}
			</select>
			<input type="submit" onclick="return confirm('{translate|escape:"jsparam" key="editor.submissionReview.confirmDecision"}')" name="submit" value="{translate key="editor.monograph.recordDecision"}" {if not $allowRecommendation}disabled="disabled"{/if} class="button" />
			{if not $allowRecommendation}<br />{translate key="editor.monograph.cannotRecord}{/if}
		</form>
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="editor.monograph.decision"}</td>
	<td class="value">
		{foreach from=$submission->getDecisions($reviewType, $round) item=editorDecision key=decisionKey}
			{if $decisionKey neq 0} | {/if}
			{assign var="decision" value=$editorDecision.decision}
			{translate key=$editorDecisionOptions.$decision}&nbsp;&nbsp;{$editorDecision.dateDecided|date_format:$dateFormatShort}
		{foreachelse}
			{translate key="common.none"}
		{/foreach}
	</td>
</tr>
<tr valign="top">
	<td class="label">{translate key="submission.notifyAuthor"}</td>
	<td class="value">
		{url|assign:"notifyAuthorUrl" op="emailEditorDecisionComment" monographId=$submission->getMonographId()}

		{if $decision == SUBMISSION_EDITOR_DECISION_DECLINE}
			{* The last decision was a decline; notify the user that sending this message will archive the submission. *}
			{translate|escape:"quotes"|assign:"confirmString" key="editor.submissionReview.emailWillArchive"}
			{icon name="mail" url=$notifyAuthorUrl onclick="return confirm('$confirmString')"}
		{else}
			{icon name="mail" url=$notifyAuthorUrl}
		{/if}

		&nbsp;&nbsp;&nbsp;&nbsp;
		{translate key="submission.editorAuthorRecord"}
		{if $submission->getMostRecentEditorDecisionComment()}
			{assign var="comment" value=$submission->getMostRecentEditorDecisionComment()}
			<a href="javascript:openComments('{url op="viewEditorDecisionComments" path=$submission->getMonographId() anchor=$comment->getCommentId()}');" class="icon">{icon name="comment"}</a>&nbsp;&nbsp;{$comment->getDatePosted()|date_format:$dateFormatShort}
		{else}
			<a href="javascript:openComments('{url op="viewEditorDecisionComments" path=$submission->getMonographId()}');" class="icon">{icon name="comment"}</a>{translate key="common.noComments"}
		{/if}
	</td>
</tr>
</table>

<form method="post" action="{url op="editorReview"}" enctype="multipart/form-data">
<input type="hidden" name="monographId" value="{$submission->getMonographId()}" />
{assign var=authorFiles value=$submission->getAuthorFileRevisions($reviewType, $round)}
{assign var=editorFiles value=$submission->getEditorFileRevisions($reviewType, $round)}

{assign var="authorRevisionExists" value=false}
{foreach from=$authorFiles item=authorFile}
	{assign var="authorRevisionExists" value=true}
{/foreach}

{assign var="editorRevisionExists" value=false}
{foreach from=$editorFiles item=editorFile}
	{assign var="editorRevisionExists" value=true}
{/foreach}
{if $reviewFile}
	{assign var="reviewVersionExists" value=1}
{/if}

<table class="data" width="100%">
	{if $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}
		<tr>
			<td width="20%">&nbsp;</td>
			<td width="80%">
				{translate key="editor.monograph.resubmitFileForPeerReview"}
				<input type="submit" name="resubmit" {if !($editorRevisionExists or $authorRevisionExists or $reviewVersionExists)}disabled="disabled" {/if}value="{translate key="form.resubmit"}" class="button" />
			</td>
		</tr>
	{elseif $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT}
		<tr valign="top">
			<td width="20%">&nbsp;</td>
			<td width="80%">
				{if !($editorRevisionExists or $authorRevisionExists or $reviewVersionExists)} {* or !$submission->getMostRecentEditorDecisionComment()*}{assign var=copyeditingUnavailable value=1}{else}{assign var=copyeditingUnavailable value=0}{/if}
				{translate key="editor.monograph.sendFileToNextWorkflowProcess" nextProcessTitle=$nextProcessTitle}
				<input type="submit" {if $copyeditingUnavailable}disabled="disabled" {/if}name="setAcceptedFile" value="{translate key="form.send"}" class="button" />
				{if $copyeditingUnavailable}
					<br/>
					<span class="instruct">{translate key="editor.submissionReview.cannotSendToCopyediting"}</span>
				{/if}
			</td>
		</tr>
	{/if}

	{if $reviewFile}
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.reviewVersion"}</td>
			<td width="50%" class="value">
				{if $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT || $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}
					<input type="radio" name="editorDecisionFile" value="{$reviewFile->getFileId()},{$reviewFile->getRevision()}" />
				{/if}
				<a href="{url op="downloadFile" path=$submission->getMonographId()|to_array:$reviewFile->getFileId():$reviewFile->getRevision()}" class="file">{$reviewFile->getFileName()|escape}</a>&nbsp;&nbsp;
				{$reviewFile->getDateModified()|date_format:$dateFormatShort}
				{if $copyeditFile && $copyeditFile->getSourceFileId() == $reviewFile->getFileId()}
					&nbsp;&nbsp;&nbsp;&nbsp;{translate key="submission.sent"}&nbsp;&nbsp;{$copyeditFile->getDateUploaded()|date_format:$dateFormatShort}
				{/if}
			</td>
		</tr>
	{/if}
	{assign var="firstItem" value=true}
	{foreach from=$authorFiles item=authorFile key=key}
		<tr valign="top">
			{if $firstItem}
				{assign var="firstItem" value=false}
				<td width="20%" rowspan="{$authorFiles|@count}" class="label">{translate key="submission.authorVersion"}</td>
			{/if}
			<td width="80%" class="value">
				{if $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT || $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}<input type="radio" name="editorDecisionFile" value="{$authorFile->getFileId()},{$authorFile->getRevision()}" /> {/if}<a href="{url op="downloadFile" path=$submission->getMonographId()|to_array:$authorFile->getFileId():$authorFile->getRevision()}" class="file">{$authorFile->getFileName()|escape}</a>&nbsp;&nbsp;
				{$authorFile->getDateModified()|date_format:$dateFormatShort}
				{if $copyeditFile && $copyeditFile->getSourceFileId() == $authorFile->getFileId()}
					&nbsp;&nbsp;&nbsp;&nbsp;{translate key="submission.sent"}&nbsp;&nbsp;{$copyeditFile->getDateUploaded()|date_format:$dateFormatShort}
				{/if}
			</td>
		</tr>
	{foreachelse}
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.authorVersion"}</td>
			<td width="80%" class="nodata">{translate key="common.none"}</td>
		</tr>
	{/foreach}
	{assign var="firstItem" value=true}
	{foreach from=$editorFiles item=editorFile key=key}
		<tr valign="top">
			{if $firstItem}
				{assign var="firstItem" value=false}
				<td width="20%" rowspan="{$editorFiles|@count}" class="label">{translate key="submission.editorVersion"}</td>
			{/if}
			<td width="80%" class="value">
				{if $lastDecision == SUBMISSION_EDITOR_DECISION_ACCEPT || $lastDecision == SUBMISSION_EDITOR_DECISION_RESUBMIT}<input type="radio" name="editorDecisionFile" value="{$editorFile->getFileId()},{$editorFile->getRevision()}" /> {/if}<a href="{url op="downloadFile" path=$submission->getMonographId()|to_array:$editorFile->getFileId():$editorFile->getRevision()}" class="file">{$editorFile->getFileName()|escape}</a>&nbsp;&nbsp;
				{$editorFile->getDateModified()|date_format:$dateFormatShort}&nbsp;&nbsp;&nbsp;&nbsp;
				{if $copyeditFile && $copyeditFile->getSourceFileId() == $editorFile->getFileId()}
					{translate key="submission.sent"}&nbsp;&nbsp;{$copyeditFile->getDateUploaded()|date_format:$dateFormatShort}&nbsp;&nbsp;&nbsp;&nbsp;
				{/if}
				<a href="{url op="deleteMonographFile" path=$submission->getMonographId()|to_array:$editorFile->getFileId():$editorFile->getRevision()}" class="action">{translate key="common.delete"}</a>
			</td>
		</tr>
	{foreachelse}
		<tr valign="top">
			<td width="20%" class="label">{translate key="submission.editorVersion"}</td>
			<td width="80%" class="nodata">{translate key="common.none"}</td>
		</tr>
	{/foreach}
	<tr valign="top">
		<td class="label">&nbsp;</td>
		<td class="value">
			<input type="file" name="upload" class="uploadField" />
			<input type="submit" name="submit" value="{translate key="common.upload"}" class="button" />
		</td>
	</tr>

</table>

</form>
</div>
