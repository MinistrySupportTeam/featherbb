<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace model\admin;

use DB;

class censoring
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->hook = $this->feather->hooks;
    }

    public function add_word()
    {
        $search_for = feather_trim($this->request->post('new_search_for'));
        $replace_with = feather_trim($this->request->post('new_replace_with'));

        if ($search_for == '') {
            message(__('Must enter word message'));
        }

        $set_search_word = array('search_for' => $search_for,
                                'replace_with' => $replace_with);

        $set_search_word = $this->hook->fire('add_censoring_word_data', $set_search_word);

        $result = DB::for_table('censoring')
            ->create()
            ->set($set_search_word)
            ->save();

        // Regenerate the censoring cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_censoring_cache();

        redirect(get_link('admin/censoring/'), __('Word added redirect'));
    }

    public function update_word()
    {
        $id = intval(key($this->request->post('update')));

        $search_for = feather_trim($this->request->post('search_for')[$id]);
        $replace_with = feather_trim($this->request->post('replace_with')[$id]);

        if ($search_for == '') {
            message(__('Must enter word message'));
        }

        $set_search_word = array('search_for' => $search_for,
                                'replace_with' => $replace_with);

        $set_search_word = $this->hook->fire('update_censoring_word_start', $set_search_word);

        $result = DB::for_table('censoring')
            ->find_one($id)
            ->set($set_search_word)
            ->save();

        // Regenerate the censoring cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_censoring_cache();

        redirect(get_link('admin/censoring/'), __('Word updated redirect'));
    }

    public function remove_word()
    {
        $id = intval(key($this->request->post('remove')));
        $id = $this->hook->fire('remove_censoring_word_start', $id);

        $result = DB::for_table('censoring')->find_one($id);
        $result = $this->hook->fireDB('remove_censoring_word', $result);
        $result = $result->delete();

        // Regenerate the censoring cache
        if (!defined('FORUM_CACHE_FUNCTIONS_LOADED')) {
            require FEATHER_ROOT.'include/cache.php';
        }

        generate_censoring_cache();

        redirect(get_link('admin/censoring/'),  __('Word removed redirect'));
    }

    public function get_words()
    {
        $word_data = array();

        $word_data = DB::for_table('censoring')
                        ->order_by_asc('id');
        $word_data = $this->hook->fireDB('update_censoring_word_query', $word_data);
        $word_data = $word_data->find_array();

        return $word_data;
    }
}
