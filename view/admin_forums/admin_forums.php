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

	<div class="blockform">
		<h2><span><?php echo $lang_admin_forums['Add forum head'] ?></span></h2>
		<div class="box">
			<form method="post" action="admin_forums.php?action=adddel">
<?php

$is_forum = check_forums();

if ($is_forum)
{

?>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_forums['Create new subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_forums['Add forum label'] ?><div><input type="submit" name="add_forum" value="<?php echo $lang_admin_forums['Add forum'] ?>" tabindex="2" /></div></th>
									<td>
										<select name="add_to_cat" tabindex="1">
											<?php get_categories_add(); ?>
										</select>
										<span><?php echo $lang_admin_forums['Add forum help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
<?php

}
else
{

?>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_common['None'] ?></legend>
						<div class="infldset">
							<p><?php echo $lang_admin_forums['No categories exist'] ?></p>
						</div>
					</fieldset>
				</div>
<?php

}

?>
			</form>
		</div>
<?php

$is_category = check_categories();

if ($is_category)
{

?>
		<h2 class="block2"><span><?php echo $lang_admin_forums['Edit forums head'] ?></span></h2>
		<div class="box">
			<form id="edforum" method="post" action="admin_forums.php?action=edit">
				<p class="submittop"><input type="submit" name="update_positions" value="<?php echo $lang_admin_forums['Update positions'] ?>" tabindex="3" /></p>
<?php

$cur_index = 4;
$cur_category = 0;

$forum_data = get_forums();

foreach ($forum_data as $forum)
{
	if ($forum['cid'] != $cur_category) // A new category since last iteration?
	{
		if ($cur_category != 0)
			echo "\t\t\t\t\t\t\t".'</tbody>'."\n\t\t\t\t\t\t\t".'</table>'."\n\t\t\t\t\t\t".'</div>'."\n\t\t\t\t\t".'</fieldset>'."\n\t\t\t\t".'</div>'."\n";

?>
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_forums['Category subhead'] ?> <?php echo pun_htmlspecialchars($forum['cat_name']) ?></legend>
						<div class="infldset">
							<table>
							<thead>
								<tr>
									<th class="tcl"><?php echo $lang_admin_common['Action'] ?></th>
									<th class="tc2"><?php echo $lang_admin_forums['Position label'] ?></th>
									<th class="tcr"><?php echo $lang_admin_forums['Forum label'] ?></th>
								</tr>
							</thead>
							<tbody>
<?php

		$cur_category = $forum['cid'];
	}

?>
								<tr>
									<td class="tcl"><a href="admin_forums.php?edit_forum=<?php echo $forum['fid'] ?>" tabindex="<?php echo $cur_index++ ?>"><?php echo $lang_admin_forums['Edit link'] ?></a> | <a href="admin_forums.php?del_forum=<?php echo $forum['fid'] ?>" tabindex="<?php echo $cur_index++ ?>"><?php echo $lang_admin_forums['Delete link'] ?></a></td>
									<td class="tc2"><input type="text" name="position[<?php echo $forum['fid'] ?>]" size="3" maxlength="3" value="<?php echo $forum['disp_position'] ?>" tabindex="<?php echo $cur_index++ ?>" /></td>
									<td class="tcr"><strong><?php echo pun_htmlspecialchars($forum['forum_name']) ?></strong></td>
								</tr>
<?php

}

?>
							</tbody>
							</table>
						</div>
					</fieldset>
				</div>
				<p class="submitend"><input type="submit" name="update_positions" value="<?php echo $lang_admin_forums['Update positions'] ?>" tabindex="<?php echo $cur_index++ ?>" /></p>
			</form>
		</div>
<?php

}

?>
	</div>
	<div class="clearer"></div>
</div>