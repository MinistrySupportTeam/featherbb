<?php

/**
* Copyright (C) 2015 FeatherBB
* based on code by (C) 2008-2012 FluxBB
* and Rickard Andersson (C) 2002-2008 PunBB
* License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
*/

namespace model;
use DB;

class debug
{
    protected static $app;

    public static function get_queries()
    {
        $data = array();
        $data['raw'] = array_combine(DB::get_query_log()[0], DB::get_query_log()[1]);
        $data['total_time'] = array_sum(array_keys($data['raw']));
        return $data;
    }

    public static function get_info()
    {
        self::$app = \Slim\Slim::getInstance();

        $data = array(
            'nb_queries' => count(DB::get_query_log()[0]),
            'exec_time' => (get_microtime() - self::$app->start));
        $data['mem_usage'] = (function_exists('memory_get_usage')) ? file_size(memory_get_usage()) : 'N/A';
        $data['mem_peak_usage'] = (function_exists('memory_get_peak_usage')) ? file_size(memory_get_peak_usage()) : 'N/A';
        return $data;
    }
}
