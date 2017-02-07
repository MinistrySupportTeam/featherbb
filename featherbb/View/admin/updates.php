<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

// Make sure no one attempts to run this script "directly"
if (!isset($feather)) {
    exit;
}

Container::get('hooks')->fire('view.admin.updates.start');

if (empty($upgrade_results)): ?>
    <div class="blockform">
        <h2><span><?= __('Available updates') ?></span></h2>
        <div class="box">
            <form id="upgrade-core" method="post" action="<?= Router::pathFor('adminUpgradeCore') ?>">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <div class="inform">
                    <fieldset>
                        <legend><?= __('FeatherBB core') ?></legend>
                        <div class="infldset">
                            <p>
                                <?= $core_updates_message; ?>
                            </p>
                        </div>
                    </fieldset>
                    <?php if ($core_updates): ?><p class="buttons"><input type="submit" name="upgrade-core" value="<?= __('Upgrade core') ?>" /></p><?php endif; ?>
                </div>
            </form>
            <form id="upgrade-plugins" method="post" action="<?= Router::pathFor('adminUpgradePlugins') ?>">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Plugins') ?></legend>
                        <div class="infldset">
<?php
if (!empty($plugin_updates)) {
// Init valid updates
$valid_plugin_updates = 0;
?>
                            <table>
                            <thead>
                                <tr>
                                    <th class="tcl" scope="col"></th>
                                    <th class="tcr" scope="col"></th>
                                </tr>
                            </thead>
                            <tbody>
<?php foreach ($plugin_updates as $plugin): ?>
                                <tr>
                                    <td class="tcl">
                                        <?php if(!isset($plugin->errors)): ?><input type="checkbox" name="plugin_updates[<?= $plugin->name ?>]" value="<?= $plugin->version ?>" checked /><?php endif; ?>
                                        <strong><?= $plugin->title; ?></strong> <small><?= $plugin->version; ?></small>
                                    </td>
                                    <td class="tcr">
<?php if(!isset($plugin->errors)): ++$valid_plugin_updates; ?>
                                        <a href="https://github.com/featherbb/<?= $plugin->name; ?>/releases/tag/<?= $plugin->last_version; ?>" target="_blank"><?= $plugin->last_version; ?></a>
                                        <a href="http://marketplace.featherbb.org/plugins/view/<?= $plugin->name; ?>/changelog" target="_blank"><?= __('View changelog') ?></a>
<?php else: echo $plugin->errors; endif; ?>
                                    </td>
                                </tr>
<?php endforeach; ?>
                            </tbody>
                            </table>
<?php

} else {
    echo "\t\t\t\t\t\t\t".'<p>'.__('All plugins up to date').'</p>'."\n";
}

?>
                        </div>
                    </fieldset>
                    <?php if ($valid_plugin_updates > 0): ?><p class="buttons"><input type="submit" name="upgrade-plugins" value="<?= __('Upgrade plugins') ?>" /></p><?php endif; ?>
                </div>
            </form>
            <form id="upgrade-themes" method="post" action="<?= Router::pathFor('adminUpgradeThemes') ?>">
                <input type="hidden" name="csrf_name" value="<?= $csrf_name; ?>"><input type="hidden" name="csrf_value" value="<?= $csrf_value; ?>">
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Themes') ?></legend>
                        <div class="infldset">
<?php
if (!empty($theme_updates)) {
    ?>
                            <table>
                            <thead>
                                <tr>
                                    <th class="tcl" scope="col"><?= __('Theme') ?></th>
                                    <th class="tcr" scope="col"><?= __('Latest version label') ?></th>
                                </tr>
                            </thead>
                            <tbody>
<?php foreach ($theme_updates as $theme): ?>
                                <tr>
                                    <td class="tcl">
                                        <input type="checkbox" name="theme_updates[<?= $theme->name ?>]" value="<?= $theme->version ?>" checked />
                                        <strong><?= $theme->title; ?></strong> <small><?= $theme->version; ?></small>
                                    </td>
                                    <td>
                                        <a href="https://github.com/featherbb/<?= $theme->name; ?>/releases/tag/<?= $theme->last_version; ?>" target="_blank"><?= $theme->last_version; ?></a>
                                        <a href="http://marketplace.featherbb.org/themes/view/<?= $theme->name; ?>/changelog" target="_blank"><?= __('View changelog') ?></a>
                                    </td>
                                </tr>
<?php endforeach; ?>
                            </tbody>
                            </table>
<?php

} else {
    echo "\t\t\t\t\t\t\t".'<p>'.__('All themes up to date').'</p>'."\n";
}

?>
                        </div>
                    </fieldset>
                </div>
                <?php if (!empty($theme_updates)): ?><p class="buttons"><input type="submit" name="upgrade-themes" value="<?= __('Upgrade themes') ?>" /></p><?php endif; ?>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="blockform">
        <h2><span><?= __('Upgrade results') ?></span></h2>
        <div class="box">
            <div class="fakeform">
                <div class="inform">
                    <fieldset>
                        <legend><?= __('Upgrade results') ?></legend>
                        <div class="infldset">
                            <!-- <p>The pre-defined groups Guests, Administrators, Moderators and Members cannot be removed. However, they can be edited. Please note that in some groups, some options are unavailable (e.g. the <em>edit posts</em> permission for guests). Administrators always have full permissions.</p> -->
                            <table>
<?php foreach ($upgrade_results as $key => $result): ?>
                                <tr>
                                    <th scope="row"><?= Utils::escape($key) ?></th>
                                    <td>
                                        <span><?= Utils::escape($result['message']); ?></span>
<?php if (!empty($result['errors'])) { ?>
                                        <span class="clearb">
                                            <?= __('Errors label'); ?><br>
                                            <?php foreach ($result['errors'] as $error) { echo "\t\t\t\t\t\t\t\t\t\t".Utils::escape($error).'<br>'."\n"; } ?>
                                        </span>
<?php } ?>
<?php if (!empty($result['warnings'])) { ?>
                                        <span class="clearb">
                                            <?= __('Warnings label'); ?><br>
                                            <?php foreach ($result['warnings'] as $warning) { echo "\t\t\t\t\t\t\t\t\t\t".Utils::escape($warning).'<br>'."\n"; } ?>
                                        </span>
<?php } ?>
                                    </td>
                                </tr>
<?php endforeach; ?>
                            </table>
                        </div>
                    </fieldset>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>
    <div class="clearer"></div>
</div>
<?php
Container::get('hooks')->fire('view.admin.updates.end');
