<?php

/**
 * Copyright (C) 2015-2016 FeatherBB
 * based on code by (C) 2008-2015 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace FeatherBB\Model\Api;

use FeatherBB\Core\Error;

class Forum extends Api
{
    public function display($id)
    {
        $topic = new \FeatherBB\Model\Topic();

        try {
            $data = $topic->get_info_topic($id);
        } catch (Error $e) {
            return $this->errorMessage;
        }

        $data = $data->as_array();

        $data['moderators'] = unserialize($data['moderators']);

        return $data;
    }
}