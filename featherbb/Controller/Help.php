<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

use FeatherBB\Core\Error;
use FeatherBB\Core\Utils;
use FeatherBB\Core\Url;

class Help
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        load_textdomain('featherbb', $this->feather->forum_env['FEATHER_ROOT'].'featherbb/lang/'.$this->user->language.'/help.mo');
    }

    public function __autoload($class_name)
    {
        require $this->feather->forum_env['FEATHER_ROOT'] . $class_name . '.php';
    }

    public function display()
    {
        $this->feather->template->setPageInfo(array(
            'title' => array(Utils::escape($this->config['o_board_title']), __('Help')),
            'active_page' => 'help',
        ))->addTemplate('help.php')->display();
    }
}
