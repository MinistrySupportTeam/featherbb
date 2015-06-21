<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */
 
// Make sure no one attempts to run this script "directly"
if (!defined('PUN'))
	exit;
?>

<div class="linkst">
	<div class="inbox crumbsplus">
		<ul class="crumbs">
			<li><a href="admin_index.php"><?php echo $lang_admin_common['Admin'].' '.$lang_admin_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="admin_users.php"><?php echo $lang_admin_common['Users'] ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_admin_users['Results head'] ?></strong></li>
		</ul>
		<div class="pagepost">
			<p class="pagelink"><?php echo $paging_links ?></p>
		</div>
		<div class="clearer"></div>
	</div>
</div>


<form id="search-users-form" action="admin_users.php" method="post">
<div id="users2" class="blocktable">
	<h2><span><?php echo $lang_admin_users['Results head'] ?></span></h2>
	<div class="box">
		<div class="inbox">
			<table>
			<thead>
				<tr>
					<th class="tcl" scope="col"><?php echo $lang_admin_users['Results username head'] ?></th>
					<th class="tc2" scope="col"><?php echo $lang_admin_users['Results e-mail head'] ?></th>
					<th class="tc3" scope="col"><?php echo $lang_admin_users['Results title head'] ?></th>
					<th class="tc4" scope="col"><?php echo $lang_admin_users['Results posts head'] ?></th>
					<th class="tc5" scope="col"><?php echo $lang_admin_users['Results admin note head'] ?></th>
					<th class="tcr" scope="col"><?php echo $lang_admin_users['Results actions head'] ?></th>
<?php if ($can_action): ?>					<th class="tcmod" scope="col"><?php echo $lang_admin_users['Select'] ?></th>
<?php endif;
    ?>
				</tr>
			</thead>
			<tbody>
<?php
	$user_data = print_users($search['conditions'], $search['order_by'], $search['direction'], $start_from);
	
    if (!empty($user_data)) {
        foreach ($user_data as $user) {
            ?>
				<tr>
					<td class="tcl"><?php echo '<a href="profile.php?id='.$user['id'].'">'.pun_htmlspecialchars($user['username']).'</a>' ?></td>
					<td class="tc2"><a href="mailto:<?php echo pun_htmlspecialchars($user['email']) ?>"><?php echo pun_htmlspecialchars($user['email']) ?></a></td>
					<td class="tc3"><?php echo $user['user_title'] ?></td>
					<td class="tc4"><?php echo forum_number_format($user['num_posts']) ?></td>
					<td class="tc5"><?php echo($user['admin_note'] != '') ? pun_htmlspecialchars($user['admin_note']) : '&#160;' ?></td>
					<td class="tcr"><?php echo '<a href="admin_users.php?ip_stats='.$user['id'].'">'.$lang_admin_users['Results view IP link'].'</a> | <a href="search.php?action=show_user_posts&amp;user_id='.$user['id'].'">'.$lang_admin_users['Results show posts link'].'</a>' ?></td>
<?php if ($can_action): ?>					<td class="tcmod"><input type="checkbox" name="users[<?php echo $user['id'] ?>]" value="1" /></td>
<?php endif;
            ?>
				</tr>
<?php

        }
    } else {
        echo "\t\t\t\t".'<tr><td class="tcl" colspan="6">'.$lang_admin_users['No match'].'</td></tr>'."\n";
    }

    ?>
			</tbody>
			</table>
		</div>
	</div>
</div>

<div class="linksb">
	<div class="inbox crumbsplus">
		<div class="pagepost">
			<p class="pagelink"><?php echo $paging_links ?></p>
<?php if ($can_action): ?>			<p class="conr modbuttons"><a href="#" onclick="return select_checkboxes('search-users-form', this, '<?php echo $lang_admin_users['Unselect all'] ?>')"><?php echo $lang_admin_users['Select all'] ?></a> <?php if ($can_ban) : ?><input type="submit" name="ban_users" value="<?php echo $lang_admin_users['Ban'] ?>" /><?php endif;
    if ($can_delete) : ?><input type="submit" name="delete_users" value="<?php echo $lang_admin_users['Delete'] ?>" /><?php endif;
    if ($can_move) : ?><input type="submit" name="move_users" value="<?php echo $lang_admin_users['Change group'] ?>" /><?php endif;
    ?></p>
<?php endif;
    ?>
		</div>
		<ul class="crumbs">
			<li><a href="admin_index.php"><?php echo $lang_admin_common['Admin'].' '.$lang_admin_common['Index'] ?></a></li>
			<li><span>»&#160;</span><a href="admin_users.php"><?php echo $lang_admin_common['Users'] ?></a></li>
			<li><span>»&#160;</span><strong><?php echo $lang_admin_users['Results head'] ?></strong></li>
		</ul>
		<div class="clearer"></div>
	</div>
</div>
</form>