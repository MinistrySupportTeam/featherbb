<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model\admin;

class permissions
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
    }
 
    public function update_permissions()
    {
        global $lang_admin_permissions;

        confirm_referrer(get_link_r('admin/permissions/'));

        $form = array_map('intval', $this->request->post('form'));

        foreach ($form as $key => $input) {
            // Make sure the input is never a negative value
            if ($input < 0) {
                $input = 0;
            }

            // Only update values that have changed
            if (array_key_exists('p_'.$key, $this->config) && $this->config['p_'.$key] != $input) {
                $this->db->query('UPDATE '.$this->db->prefix.'config SET conf_value='.$input.' WHERE conf_name=\'p_'.$this->db->escape($key).'\'') or error('Unable to update board config', __FILE__, __LINE__, $this->db->error());
            }
        }

        // Regenerate the config cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_config_cache();

        redirect(get_link('admin/permissions/'), $lang_admin_permissions['Perms updated redirect']);
    }
}