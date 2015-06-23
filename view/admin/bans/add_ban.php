<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
// Make sure no one attempts to run this script "directly"
if (!defined('PUN')) {
    exit;
}
?>

	<div class="blockform">
		<h2><span><?php echo $lang_admin_bans['Ban advanced head'] ?></span></h2>
		<div class="box">
			<form id="bans2" method="post" action="">
				<div class="inform">
				<input type="hidden" name="mode" value="<?php echo $ban['mode'] ?>" />
<?php if ($ban['mode'] == 'edit'): ?>				<input type="hidden" name="ban_id" value="<?php echo $ban['id'] ?>" />
<?php endif; ?>				<fieldset>
						<legend><?php echo $lang_admin_bans['Ban advanced subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_bans['Username label'] ?></th>
									<td>
										<input type="text" name="ban_user" size="25" maxlength="25" value="<?php if (isset($ban['ban_user'])) {
    echo pun_htmlspecialchars($ban['ban_user']);
} ?>" tabindex="1" />
										<span><?php echo $lang_admin_bans['Username help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_bans['IP label'] ?></th>
									<td>
										<input type="text" name="ban_ip" size="45" maxlength="255" value="<?php if (isset($ban['ip'])) {
    echo pun_htmlspecialchars($ban['ip']);
} ?>" tabindex="2" />
										<span><?php echo $lang_admin_bans['IP help'] ?><?php if ($ban['ban_user'] != '' && isset($ban['user_id'])) {
    printf(' '.$lang_admin_bans['IP help link'], '<a href="admin_users.php?ip_stats='.$ban['user_id'].'">'.$lang_admin_common['here'].'</a>');
} ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_bans['E-mail label'] ?></th>
									<td>
										<input type="text" name="ban_email" size="40" maxlength="80" value="<?php if (isset($ban['email'])) {
    echo pun_htmlspecialchars($ban['email']);
} ?>" tabindex="3" />
										<span><?php echo $lang_admin_bans['E-mail help'] ?></span>
									</td>
								</tr>
							</table>
							<p class="topspace"><strong class="warntext"><?php echo $lang_admin_bans['Ban IP range info'] ?></strong></p>
						</div>
					</fieldset>
				</div>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_bans['Message expiry subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_bans['Ban message label'] ?></th>
									<td>
										<input type="text" name="ban_message" size="50" maxlength="255" value="<?php if (isset($ban['message'])) {
    echo pun_htmlspecialchars($ban['message']);
} ?>" tabindex="4" />
										<span><?php echo $lang_admin_bans['Ban message help'] ?></span>
									</td>
								</tr>
								<tr>
									<th scope="row"><?php echo $lang_admin_bans['Expire date label'] ?></th>
									<td>
										<input type="text" name="ban_expire" size="17" maxlength="10" value="<?php if (isset($ban['expire'])) {
    echo $ban['expire'];
} ?>" tabindex="5" />
										<span><?php echo $lang_admin_bans['Expire date help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="add_edit_ban" value="<?php echo $lang_admin_common['Save'] ?>" tabindex="6" /></p>
			</form>
		</div>
	</div>
	<div class="clearer"></div>
</div>