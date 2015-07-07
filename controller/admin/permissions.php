<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class permissions
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->db = $this->feather->db;
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->header = new \controller\header();
        $this->footer = new \controller\footer();
        $this->model = new \model\admin\permissions();
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }
    
    public function display()
    {
        global $lang_common, $lang_admin_common, $lang_admin_permissions;

        require FEATHER_ROOT.'include/common_admin.php';

        if (!$this->user['is_admmod']) {
            message($lang_common['No permission'], false, '403 Forbidden');
        }

        define('FEATHER_ADMIN_CONSOLE', 1);

        // Load the admin_options.php language file
        require FEATHER_ROOT.'lang/'.$admin_language.'/permissions.php';

        // Update permissions
        if ($this->feather->request->isPost()) {
            $this->model->update_permissions($this->feather);
        }

        $page_title = array(pun_htmlspecialchars($this->config['o_board_title']), $lang_admin_common['Admin'], $lang_admin_common['Permissions']);

        define('FEATHER_ACTIVE_PAGE', 'admin');

        $this->header->display();

        generate_admin_menu('permissions');

        $this->feather->render('admin/permissions.php', array(
                'lang_admin_permissions'    =>    $lang_admin_permissions,
                'lang_admin_common'    =>    $lang_admin_common,
                'feather_config'    =>    $this->config,
            )
        );

        $this->footer->display();
    }
}
