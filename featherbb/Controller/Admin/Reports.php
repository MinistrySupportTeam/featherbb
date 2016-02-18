<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller\Admin;

use FeatherBB\Core\AdminUtils;
use FeatherBB\Core\Url;
use FeatherBB\Core\Utils;

class Reports
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = Container::get('user');
        $this->request = $this->feather->request;
        $this->model = new \FeatherBB\Model\Admin\Reports();
        load_textdomain('featherbb', Config::get('forum_env')['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/admin/reports.mo');
    }

    public function display()
    {
        Container::get('hooks')->fire('controller.admin.reports.display');

        // Zap a report
        if ($this->feather->request->isPost()) {
            $zap_id = intval(key($this->request->post('zap_id')));
            $this->model->zap_report($zap_id);
            Router::redirect(Router::pathFor('adminReports'), __('Report zapped redirect'));
        }

        AdminUtils::generateAdminMenu('reports');

        View::setPageInfo(array(
                'title' => array(Utils::escape($this->config['o_board_title']), __('Admin'), __('Reports')),
                'active_page' => 'admin',
                'admin_console' => true,
                'report_data'   =>  $this->model->get_reports(),
                'report_zapped_data'   =>  $this->model->get_zapped_reports(),
            )
        )->addTemplate('admin/reports.php')->display();
    }
}
