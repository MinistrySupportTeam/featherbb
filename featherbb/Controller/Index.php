<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Utils;

class Index
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->model = new \FeatherBB\Model\Index();
        load_textdomain('featherbb', FEATHER_ROOT.'featherbb/lang/'.$this->feather->user->language.'/index.mo');
    }

    public function display()
    {
        if ($this->feather->user->g_read_board == '0') {
            throw new \FeatherBB\Error(__('No view'), 403);
        }

        $this->feather->view2->setPageInfo(array(
            'title' => array(Utils::escape($this->feather->forum_settings['o_board_title'])),
            'active_page' => 'index',
            'is_indexed' => true,
            'index_data' => $this->model->print_categories_forums(),
            'stats' => $this->model->collect_stats(),
            'online'    =>    $this->model->fetch_users_online(),
            'forum_actions'        =>    $this->model->get_forum_actions(),
            'cur_cat'   => 0
        ))->addTemplate('index.php')->display();
    }
}
