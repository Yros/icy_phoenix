{IMG_TBL}<div class="forumline nav-div">
	<p class="nav-header">
		<a href="{U_PORTAL}">{L_HOME}</a>{NAV_SEP}<a href="{U_LOGIN_LOGOUT}" class="nav-current">{L_LOGIN_LOGOUT}</a>
	</p>
	<div class="nav-links"><div class="nav-links-left">{CURRENT_TIME}</div>&nbsp;</div>
</div>{IMG_TBR}

<form action="{S_LOGIN_ACTION}" method="post">

{IMG_THL}{IMG_THC}<span class="forumlink">{L_ENTER_PASSWORD}</span>{IMG_THR}<table class="forumlinenb" width="100%" cellspacing="0" cellpadding="0">
<tr>
	<td class="row1g row-center" width="150" style="padding:20px; padding-top: 30px;"><img src="images/icy_phoenix_small.png" alt="" /></td>
	<td class="row1g row-center" style="padding:20px; padding-top: 30px;">
		<div style="margin: auto; width: 250px">
		<table cellpadding="3" cellspacing="1">
		<tr>
			<td align="right" nowrap="nowrap"><span class="gen">{L_USERNAME}:</span></td>
			<td><input type="text" name="username" class="post" size="25" maxlength="40" value="{USERNAME}" /></td>
		</tr>
		<tr>
			<td align="right" nowrap="nowrap"><span class="gen">{L_PASSWORD}:</span></td>
			<td><input type="password" name="password" class="post" size="25" maxlength="32" /></td>
		</tr>
		<!-- BEGIN switch_allow_autologin -->
		<tr align="center">
			<td colspan="2" nowrap="nowrap"><span class="genmed">{L_AUTOLOGIN}:&nbsp;<input type="checkbox" name="autologin" checked="checked" /></span></td>
		</tr>
		<!-- END switch_allow_autologin -->
		<!-- BEGIN switch_login_type -->
		<tr align="center">
			<td colspan="2" nowrap="nowrap"><span class="genmed">{L_STATUS}:&nbsp;<input type="radio" name="online_status" value="default" checked="checked" />{L_DEFAULT}&nbsp;&nbsp;<input type="radio" name="online_status" value="hidden" />{L_HIDDEN}&nbsp;&nbsp;<input type="radio" name="online_status" value="visible" />{L_VISIBLE}&nbsp;&nbsp;</span></td>
		</tr>
		<!-- END switch_login_type -->
		<tr align="center"><td colspan="2">{S_HIDDEN_FIELDS}<input type="submit" name="login" class="mainoption" value="{L_LOGIN}" /></td></tr>
		<tr align="center">
			<td colspan="2" nowrap="nowrap">
				<div class="gensmall">
					<a href="{U_REGISTER}" class="gensmall">{L_REGISTER}</a>&nbsp;&#8226;&nbsp;<a href="{U_SEND_PASSWORD}" class="gensmall">{L_SEND_PASSWORD}</a>
					<!-- BEGIN switch_resend_activation_email -->
					&nbsp;&#8226;&nbsp;<a href="{U_RESEND_ACTIVATION_EMAIL}" class="gensmall">{L_RESEND_ACTIVATION_EMAIL}</a>
					<!-- END switch_resend_activation_email -->
				</div>
			</td>
		</tr>
		</table>
		</div>
	</td>
	<td class="row1g row-center" width="150" style="padding:20px;padding-top:30px;"><img src="images/icy_phoenix_small_l.png" alt="" /></td>
</tr>
</table>{IMG_TFL}{IMG_TFC}{IMG_TFR}

</form>