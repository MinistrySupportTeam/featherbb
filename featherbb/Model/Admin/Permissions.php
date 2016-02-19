<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Admin;

use FeatherBB\Core\Database as DB;
use FeatherBB\Core\Url;
use FeatherBB\Model\Cache;

class Permissions
{
    public function update_permissions()
    {
        $form = array_map('intval', Input::post('form'));
        $form = Container::get('hooks')->fire('model.admin.permissions.update_permissions.form', $form);

        foreach ($form as $key => $input) {
            // Make sure the input is never a negative value
            if ($input < 0) {
                $input = 0;
            }

            // Only update values that have changed
            if (array_key_exists('p_'.$key, $this->config) && Config::get('forum_settings')['p_'.$key] != $input) {
                DB::for_table('config')->where('conf_name', 'p_'.$key)
                                                           ->update_many('conf_value', $input);
            }
        }

        // Regenerate the config cache
        Container::get('cache')->store('config', Cache::get_config());
        // $this->clear_feed_cache();

        Router::redirect(Router::pathFor('adminPermissions'), __('Perms updated redirect'));
    }
}
