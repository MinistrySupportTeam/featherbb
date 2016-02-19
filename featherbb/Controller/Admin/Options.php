<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Utils;

class Options
{
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Admin\Options();
        load_textdomain('featherbb', Config::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.Container::get('user')->language.'/admin/options.mo');
    }

    public function display($req, $res, $args)
    {
        Container::get('hooks')->fire('controller.admin.options.display');

        if (Request::isPost()) {
            $this->model->update_options();
        }

        AdminUtils::generateAdminMenu('options');

        View::setPageInfo(array(
                'title' => array(Utils::escape(ForumSettings::get('o_board_title')), __('Admin'), __('Options')),
                'active_page' => 'admin',
                'admin_console' => true,
                'languages' => $this->model->get_langs(),
                'styles' => $this->model->get_styles(),
                'times' => $this->model->get_times(),
            )
        )->addTemplate('admin/options.php')->display();
    }
}
