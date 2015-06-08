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
		<h2><span><?php echo $lang_admin_categories['Add categories head'] ?></span></h2>
		<div class="box">
			<form method="post" action="admin_categories.php">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_categories['Add categories subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_categories['Add category label'] ?><div><input type="submit" name="add_cat" value="<?php echo $lang_admin_categories['Add new submit'] ?>" tabindex="2" /></div></th>
									<td>
										<input type="text" name="new_cat_name" size="35" maxlength="80" tabindex="1" />
										<span><?php printf($lang_admin_categories['Add category help'], '<a href="admin_forums.php">'.$lang_admin_common['Forums'].'</a>') ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>

<?php if (!empty($cat_list)): ?>		<h2 class="block2"><span><?php echo $lang_admin_categories['Delete categories head'] ?></span></h2>
		<div class="box">
			<form method="post" action="admin_categories.php">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_categories['Delete categories subhead'] ?></legend>
						<div class="infldset">
							<table class="aligntop">
								<tr>
									<th scope="row"><?php echo $lang_admin_categories['Delete category label'] ?><div><input type="submit" name="del_cat" value="<?php echo $lang_admin_common['Delete'] ?>" tabindex="4" /></div></th>
									<td>
										<select name="cat_to_delete" tabindex="3">
<?php

	foreach ($cat_list as $cur_cat)
		echo "\t\t\t\t\t\t\t\t\t\t\t".'<option value="'.$cur_cat['id'].'">'.pun_htmlspecialchars($cur_cat['cat_name']).'</option>'."\n";

?>
										</select>
										<span><?php echo $lang_admin_categories['Delete category help'] ?></span>
									</td>
								</tr>
							</table>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
<?php endif; ?>

<?php if (!empty($cat_list)): ?>		<h2 class="block2"><span><?php echo $lang_admin_categories['Edit categories head'] ?></span></h2>
		<div class="box">
			<form method="post" action="admin_categories.php">
				<div class="inform">
					<fieldset>
						<legend><?php echo $lang_admin_categories['Edit categories subhead'] ?></legend>
						<div class="infldset">
							<table id="categoryedit">
							<thead>
								<tr>
									<th class="tcl" scope="col"><?php echo $lang_admin_categories['Category name label'] ?></th>
									<th scope="col"><?php echo $lang_admin_categories['Category position label'] ?></th>
								</tr>
							</thead>
							<tbody>
<?php

	foreach ($cat_list as $cur_cat)
	{

?>
								<tr>
									<td class="tcl"><input type="text" name="cat[<?php echo $cur_cat['id'] ?>][name]" value="<?php echo pun_htmlspecialchars($cur_cat['cat_name']) ?>" size="35" maxlength="80" /></td>
									<td><input type="text" name="cat[<?php echo $cur_cat['id'] ?>][order]" value="<?php echo $cur_cat['disp_position'] ?>" size="3" maxlength="3" /></td>
								</tr>
<?php

	}

?>
							</tbody>
							</table>
							<div class="fsetsubmit"><input type="submit" name="update" value="<?php echo $lang_admin_common['Update'] ?>" /></div>
						</div>
					</fieldset>
				</div>
			</form>
		</div>
<?php endif; ?>	</div>
	<div class="clearer"></div>
</div>