{IMG_TBL}<div class="forumline nav-div">
	<p class="nav-header">
		<a href="{U_PORTAL}">{L_HOME}</a>{NAV_SEP}<a href="{U_INDEX}">{L_INDEX}</a>{NAV_CAT_DESC}
	</p>
	<div class="nav-links">
		<div class="nav-links-left">{CURRENT_TIME}<!-- BEGIN switch_user_logged_in --><br />{MARKED_READ}<!-- END switch_user_logged_in --></div>
		<!-- BEGIN switch_user_logged_in -->
		<!-- IF U_MARK_READ -->{U_MARK_READ}<!-- ENDIF --><!-- IF S_WATCH_FORUM --><!-- IF U_MARK_READ -->&nbsp;|&nbsp;<!-- ENDIF -->{S_WATCH_FORUM}<!-- ENDIF --><br />
		<!-- IF U_MARK_ALWAYS_READ -->{U_MARK_ALWAYS_READ}<!-- ENDIF -->
		<!-- END switch_user_logged_in -->
		<!-- BEGIN switch_user_logged_out -->&nbsp;<!-- END switch_user_logged_out -->
	</div>
</div>{IMG_TBR}

{XS_NEWS}

{CALENDAR_BOX}

<!-- BEGIN switch_forum_rules -->
{IMG_TBL}<table class="forumline" width="100%" cellspacing="0">
<tr><td class="row-header"><span><!-- BEGIN switch_display_title -->{L_FORUM_RULES}<!-- END switch_display_title -->&nbsp;</span></td></tr>
<tr><td class="row1g-left" width="100%"><div class="post-text">{FORUM_RULES}</div></td></tr>
</table>{IMG_TBR}
<br />
<!-- END switch_forum_rules -->
<table class="empty-table" width="100%" cellspacing="0" cellpadding="0" border="0">
<tr>
	<td align="left" valign="bottom" width="65%">
		<span class="gensmall">{L_MODERATOR}:&nbsp;{MODERATORS}</span><br /><span class="gensmall">{TOTAL_USERS_ONLINE}</span><br/><span class="gensmall">{LOGGED_IN_USER_LIST}</span><br /><span class="gensmall">{BOT_LIST}&nbsp;</span><br />
	</td>
	<td align="right" valign="bottom" rowspan="2">
		<span class="gen">{PAGE_NUMBER}</span><br />
		<span class="pagination">{PAGINATION}</span>
		<!-- BEGIN extended_pagination -->
		<br /><form method="post" action="{U_VIEW_FORUM}" style="display:inline;"><span class="gen">{L_GO_TO_PAGE_NUMBER}&nbsp;<input type="text" name="page_number" value="" size="3" class="post">&nbsp;&nbsp;<input type="submit" name="submit" value="{L_SUBMIT}" class="liteoption" /></span></form>
		<!-- END extended_pagination -->
	</td>
</tr>
<tr><td align="left" valign="middle"><a href="{U_POST_NEW_TOPIC}"><img src="{POST_IMG}" alt="{L_POST_NEW_TOPIC}" title="{L_POST_NEW_TOPIC}" /></a></td></tr>
</table>
{BOARD_INDEX}
<form method="post" action="{S_POST_DAYS_ACTION}" style="display:inline;">
{IMG_THL}{IMG_THC}<a href="{U_VIEW_FORUM}" class="forumlink">{FORUM_NAME}</a>{IMG_THR}<table class="forumlinenb" width="100%" cellspacing="0" cellpadding="0">
<tr>
	<th colspan="2">{L_TOPICS}</th>
	<th width="150">{L_AUTHOR}</th>
	<th width="50">{L_VIEWS}</th>
	<th width="50">{L_REPLIES}</th>
	<!-- BEGIN rating_switch -->
	<th width="100">{L_RATING}</th>
	<!-- END rating_switch -->
	<th>{L_LASTPOST}</th>
</tr>
<!-- BEGIN topicrow -->
<!-- BEGIN divider -->
<tr><td class="forum-buttons2" colspan="7" align="left"><span>{topicrow.divider.L_DIV_HEADERS}</span></td></tr>
<!-- END divider -->
<tr>
	<td class="row1 row-center" width="20">
		{topicrow.U_MARK_ALWAYS_READ}
		<!-- <img src="{topicrow.TOPIC_FOLDER_IMG}" width="15" height="15" alt="{topicrow.L_TOPIC_FOLDER_ALT}" title="{topicrow.L_TOPIC_FOLDER_ALT}" /> -->
	</td>
	<td class="row1h{topicrow.CLASS_NEW} row-forum" width="100%" onclick="window.location.href='{topicrow.U_VIEW_TOPIC}'">
		<span class="topiclink{topicrow.CLASS_NEW}">
			{topicrow.NEWEST_POST_IMG}{topicrow.TOPIC_ATTACHMENT_IMG}{topicrow.TOPIC_TYPE}<a href="{topicrow.U_VIEW_TOPIC}" class="{topicrow.TOPIC_CLASS}">{topicrow.TOPIC_TITLE}</a>{topicrow.CALENDAR_TITLE}
		</span><br />
		<!-- BEGIN switch_topic_desc -->
		<span class="gensmall">&nbsp;{topicrow.switch_topic_desc.TOPIC_DESCRIPTION}</span><br />
		<!-- END switch_topic_desc -->
		{topicrow.GOTO_PAGE}
	</td>
	<td class="row3 row-center-small" nowrap="nowrap" style="padding-left:2px;padding-right:2px;">{topicrow.TOPIC_AUTHOR}</td>
	<td class="row2 row-center-small">{topicrow.VIEWS}</td>
	<td class="row2 row-center-small">{topicrow.REPLIES}</td>
	<!-- BEGIN rate_switch_msg -->
	<td class="row2 row-center-small"><img src="images/rates/rate_{topicrow.TOPIC_RATING}.png" alt="{topicrow.TOPIC_RATING}" /></td>
	<!-- END rate_switch_msg -->
	<td class="row3 row-center-small" style="padding-top:0;" nowrap="nowrap">{topicrow.LAST_POST_TIME}<br />{topicrow.LAST_POST_AUTHOR} {topicrow.LAST_POST_IMG}</td>
</tr>
<!-- END topicrow -->
<!-- BEGIN switch_no_topics -->
<tr><td class="row1 row-center" colspan="7" height="30"><span class="gen">{L_NO_TOPICS}</span></td></tr>
<!-- END switch_no_topics -->
<tr>
	<td class="cat" valign="middle" colspan="7">
		<table class="empty-table" width="100%" cellspacing="0" cellpadding="0" border="0">
		<tr>
			<td valign="middle" nowrap="nowrap"><span class="genmed">&nbsp;{L_DISPLAY_TOPICS}:</span></td>
			<td valign="middle" nowrap="nowrap">&nbsp;{S_SELECT_TOPIC_DAYS}&nbsp;<input type="submit" class="liteoption jumpbox" value="{L_GO}" name="submit" /></td>
			<td valign="middle" align="right" width="100%"><span class="genmed">{S_TIMEZONE}&nbsp;</span></td>
		</tr>
		</table>
	</td>
</tr>
</table>{IMG_TFL}{IMG_TFC}{IMG_TFR}
</form>
<br />

<table class="empty-table" width="100%" cellspacing="0" cellpadding="0" border="0">
<tr>
	<td align="left" valign="top" nowrap="nowrap">
		<a href="{U_POST_NEW_TOPIC}"><img src="{POST_IMG}" alt="{L_POST_NEW_TOPIC}" title="{L_POST_NEW_TOPIC}" /></a><br />
		<div style="margin-right:30px;">{IMG_TBL}<div id="icon_description_h" style="display: none;">
			<table class="forumline" width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td class="row-header">
					<div style="display:inline;{SHOW_HIDE_PADDING}float:right;cursor:pointer;"><img src="{IMG_MAXIMISE}" onclick="javascript:ShowHide('icon_description','icon_description_h','icon_description');" alt="" />&nbsp;</div><span>{L_ICON_DESCRIPTION}</span>
				</td>
			</tr>
			</table>
		</div>
		<div id="icon_description">
			<table class="forumline" width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td class="row-header" colspan="6">
					<div style="display:inline;{SHOW_HIDE_PADDING}float:right;cursor:pointer;"><img src="{IMG_MINIMISE}" onclick="javascript:ShowHide('icon_description','icon_description_h','icon_description');" alt="" />&nbsp;</div><span>{L_ICON_DESCRIPTION}</span>
				</td>
			</tr>
			<tr>
				<td width="20" align="center"><img src="{FOLDER_NEW_IMG}" alt="{L_NEW_POSTS}" title="{L_NEW_POSTS}" /></td>
				<td class="gensmall">{L_NEW_POSTS}</td>
				<td>&nbsp;&nbsp;</td>
				<td width="20" align="center"><img src="{FOLDER_IMG}" alt="{L_NO_NEW_POSTS}" title="{L_NO_NEW_POSTS}" /></td>
				<td class="gensmall">{L_NO_NEW_POSTS}</td>
				<td>&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td width="20" align="center"><img src="{FOLDER_HOT_NEW_IMG}" alt="{L_NEW_POSTS_HOT}" title="{L_NEW_POSTS_HOT}" /></td>
				<td class="gensmall">{L_NEW_POSTS_HOT}</td>
				<td>&nbsp;&nbsp;</td>
				<td width="20" align="center"><img src="{FOLDER_HOT_IMG}" alt="{L_NO_NEW_POSTS_HOT}" title="{L_NO_NEW_POSTS_HOT}" /></td>
				<td class="gensmall">{L_NO_NEW_POSTS_HOT}</td>
				<td>&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td class="gensmall" align="center"><img src="{FOLDER_LOCKED_NEW_IMG}" alt="{L_NEW_POSTS_TOPIC_LOCKED}" title="{L_NEW_POSTS_TOPIC_LOCKED}" /></td>
				<td class="gensmall">{L_NEW_POSTS_LOCKED}</td>
				<td>&nbsp;&nbsp;</td>
				<td class="gensmall" align="center"><img src="{FOLDER_LOCKED_IMG}" alt="{L_NO_NEW_POSTS_TOPIC_LOCKED}" title="{L_NO_NEW_POSTS_TOPIC_LOCKED}" /></td>
				<td class="gensmall">{L_NO_NEW_POSTS_LOCKED}</td>
				<td>&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td class="gensmall" align="center"><img src="{FOLDER_STICKY_NEW_IMG}" alt="{L_NEW_POSTS_STICKY}" title="{L_NEW_POSTS_STICKY}" /></td>
				<td class="gensmall">{L_NEW_POSTS_STICKY}</td>
				<td>&nbsp;&nbsp;</td>
				<td class="gensmall" align="center"><img src="{FOLDER_STICKY_IMG}" alt="{L_NO_NEW_POSTS_STICKY}" title="{L_NO_NEW_POSTS_STICKY}" /></td>
				<td class="gensmall">{L_NO_NEW_POSTS_STICKY}</td>
				<td>&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td class="gensmall" align="center"><img src="{FOLDER_ANNOUNCE_NEW_IMG}" alt="{L_NEW_POSTS_ANNOUNCEMENT}" title="{L_NEW_POSTS_ANNOUNCEMENT}" /></td>
				<td class="gensmall">{L_NEW_POSTS_ANNOUNCEMENT}</td>
				<td>&nbsp;&nbsp;</td>
				<td class="gensmall" align="center"><img src="{FOLDER_ANNOUNCE_IMG}" alt="{L_NO_NEW_POSTS_ANNOUNCEMENT}" title="{L_NO_NEW_POSTS_ANNOUNCEMENT}" /></td>
				<td class="gensmall">{L_NO_NEW_POSTS_ANNOUNCEMENT}</td>
				<td>&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td class="gensmall" align="center"><img src="{FOLDER_GLOBAL_ANNOUNCE_NEW_IMG}" alt="{L_NEW_POSTS_GLOBAL_ANNOUNCEMENT}" title="{L_NEW_POSTS_GLOBAL_ANNOUNCEMENT}" /></td>
				<td class="gensmall">{L_NEW_POSTS_GLOBAL_ANNOUNCEMENT}</td>
				<td>&nbsp;&nbsp;</td>
				<td class="gensmall" align="center"><img src="{FOLDER_GLOBAL_ANNOUNCE_IMG}" alt="{L_NO_NEW_POSTS_GLOBAL_ANNOUNCEMENT}" title="{L_NO_NEW_POSTS_GLOBAL_ANNOUNCEMENT}" /></td>
				<td class="gensmall">{L_NO_NEW_POSTS_GLOBAL_ANNOUNCEMENT}</td>
				<td>&nbsp;&nbsp;</td>
			</tr>
			<tr>
				<td class="gensmall" align="center"><img src="{FOLDER_AR}" alt="{L_AR_POSTS}" title="{L_AR_POSTS}" /></td>
				<td class="gensmall">{L_AR_POSTS}</td>
				<td colspan="4">&nbsp;&nbsp;</td>
			</tr>
			</table>
		</div>{IMG_TBR}</div>
		<script type="text/javascript">
		<!--
		tmp = 'icon_description';
		if(GetCookie(tmp) == '2')
		{
			ShowHide('icon_description','icon_description_h','icon_description');
		}
		//-->
		</script>
		{JUMPBOX}<br />
	</td>
	<td width="40%" align="right" valign="top" nowrap="nowrap">
		<span class="pagination">{PAGINATION}</span><br />
		{IMG_TBL}<div id="topic_auth_list_h" style="display: none;">
			<table class="forumline" width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td class="row-header">
					<div style="display:inline;{SHOW_HIDE_PADDING}float:right;cursor:pointer;"><img src="{IMG_MAXIMISE}" onclick="javascript:ShowHide('topic_auth_list','topic_auth_list_h','topic_auth_list');" alt="" />&nbsp;</div><span>{L_PERMISSIONS_LIST}</span>
				</td>
			</tr>
			</table>
		</div>
		<div id="topic_auth_list">
			<table class="forumline" width="100%" cellspacing="0" cellpadding="0">
			<tr>
				<td class="row-header">
					<div style="display:inline;{SHOW_HIDE_PADDING}float:right;cursor:pointer;"><img src="{IMG_MINIMISE}" onclick="javascript:ShowHide('topic_auth_list','topic_auth_list_h','topic_auth_list');" alt="" />&nbsp;</div><span>{L_PERMISSIONS_LIST}</span>
				</td>
			</tr>
			<tr><td class="row1">{S_AUTH_LIST}</td></tr>
			</table>
		</div>{IMG_TBR}
		<script type="text/javascript">
		<!--
		tmp = 'topic_auth_list';
		if(GetCookie(tmp) == '2')
		{
			ShowHide('topic_auth_list','topic_auth_list_h','topic_auth_list');
		}
		//-->
		</script>
	</td>
</tr>
</table>
{FORUM_WORDGRAPH}