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
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->model = new \model\admin\permissions();
        load_textdomain('featherbb', FEATHER_ROOT.'lang/'.$this->user->language.'/admin/permissions.mo');
        require FEATHER_ROOT . 'include/common_admin.php';
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }

    public function display()
    {
        if (!$this->user->is_admmod) {
            message(__('No permission'), '403');
        }

        // Update permissions
        if ($this->feather->request->isPost()) {
            $this->model->update_permissions();
        }

        generate_admin_menu('permissions');

        $this->feather->view2->setPageInfo(array(
                'title' => array(feather_escape($this->config['o_board_title']), __('Admin'), __('Permissions')),
                'active_page' => 'admin',
                'admin_console' => true,
            )
        )->addTemplate('admin/permissions.php')->display();
    }
}
