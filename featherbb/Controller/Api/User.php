<?php

/**
 * Copyright (C) 2015-2017 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Controller;

class User extends Api
{
    private $model;
    
    public function __construct()
    {
        $this->model = new \FeatherBB\Model\Api\User();
    }

    public function display($req, $res, $args)
    {
        return json_encode($this->model->display($args['id']), JSON_PRETTY_PRINT);
    }
}
