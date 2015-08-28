<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller\admin;

class plugins
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        require FEATHER_ROOT . 'include/common_admin.php';
    }

    public function __autoload($class_name)
    {
        require FEATHER_ROOT . $class_name . '.php';
    }

    public function index()
    {
        if (!$this->user->is_admmod) {
            message(__('No permission'), '403');
        }

        // Update permissions
        // if ($this->feather->request->isPost()) {
        //     $this->model->update_permissions();
        // }

        // $this->header->setTitle($page_title)->setActivePage('admin')->enableAdminConsole()->display();

        generate_admin_menu('plugins');

        $pluginsList = \FeatherBB\Plugin::getPluginsList();

        $this->feather->view2->setPageInfo(array(
                'admin_console' => true,
                'active_page' => 'admin',
                'focus_element' => array('bans', 'new_ban_user'),
                'pluginsList'    =>    $pluginsList,
                'title' => array(feather_escape($this->config['o_board_title']), __('Admin'), 'Plugins'),
            )
        )->addTemplate('admin/plugins.php')->display();

        // $this->feather->render('admin/plugins.php', array(
        //         'pluginsList'    =>    $pluginsList
        //     )
        // );
        //
        // $this->footer->display();
    }

    public function activate()
    {
        if (!$this->user->is_admmod) {
            message(__('No permission'), '403');
        }

        // Update permissions
        // if ($this->feather->request->isPost()) {
        //     $this->model->update_permissions();
        // }

        // The plugin to load should be supplied via GET
        $plugin = $this->request->get('plugin') ? $this->request->get('plugin') : null;
        if (!$plugin) {
            message(__('Bad request'), '404');
        }

        // Require all valid filenames...
        \FeatherBB\Plugin::getPluginsList();
        // And make sure the plugin actually extends base Plugin class
        if (!property_exists('\plugin\\'.$plugin, 'isFeatherPlugin')) {
            message(sprintf(__('No plugin message'), feather_escape($plugin)));
        }

        try {
            \FeatherBB\Plugin::activate($plugin);
            redirect(get_link('admin/plugins/'), 'Plugin '.feather_escape($plugin).' activated');
        } catch (\Exception $e) {
            redirect(get_link('admin/plugins/'), feather_escape($e->getMessage()));
        }

    }

    public function display()
    {
        if (!$this->user->is_admmod) {
            message(__('No permission'), '403');
        }

        // The plugin to load should be supplied via GET
        $plugin = $this->request->get('plugin') ? $this->request->get('plugin') : '';
        if (!preg_match('%^AM?P_(\w*?)\.php$%i', $plugin)) {
            message(__('Bad request'), '404');
        }

        // AP_ == Admins only, AMP_ == admins and moderators
        $prefix = substr($plugin, 0, strpos($plugin, '_'));
        if ($this->user->g_moderator == '1' && $prefix == 'AP') {
            message(__('No permission'), '403');
        }

        // Make sure the file actually exists
        if (!file_exists(FEATHER_ROOT.'plugins/'.$plugin)) {
            message(sprintf(__('No plugin message'), feather_escape($plugin)));
        }

        // Construct REQUEST_URI if it isn't set TODO?
        if (!isset($_SERVER['REQUEST_URI'])) {
            $_SERVER['REQUEST_URI'] = (isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '').'?'.(isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : '');
        }

        // Attempt to load the plugin. We don't use @ here to suppress error messages,
        // because if we did and a parse error occurred in the plugin, we would only
        // get the "blank page of death"
        include FEATHER_ROOT.'plugins/'.$plugin;
        if (!defined('FEATHER_PLUGIN_LOADED')) {
            message(sprintf(__('Plugin failed message'), feather_escape($plugin)));
        }

        $this->feather->view2->setPageInfo(array(
                'title' => array(feather_escape($this->config['o_board_title']), __('Admin'), str_replace('_', ' ', substr($plugin, strpos($plugin, '_') + 1, -4))),
                'active_page' => 'admin',
                'admin_console' => true,
            )
        )->addTemplate('admin/loader.php')->display();
    }
}
