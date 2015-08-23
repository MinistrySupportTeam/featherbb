<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

namespace controller;

class header
{
    public function __construct()
    {
        $this->feather = \Slim\Slim::getInstance();
        $this->start = $this->feather->start;
        $this->config = $this->feather->config;
        $this->user = $this->feather->user;
        $this->request = $this->feather->request;
        $this->model = new \model\header();
    }

    private $title;

    private $page;

    private $focus_element;

    private $paging_links;

    private $required_fields;

    private $page_head;

    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }

    public function setFocusElement($focus_element)
    {
        $this->focus_element = $focus_element;

        return $this;
    }

    public function setPagingLinks($paging_links)
    {
        $this->paging_links = $paging_links;

        return $this;
    }

    public function setRequiredFields($required_fields)
    {
        $this->required_fields = $required_fields;

        return $this;
    }

    public function setPageHead($page_head)
    {
        $this->page_head = $page_head;

        return $this;
    }

    public function display()
    {
        if (!defined('FEATHER_HEADER')) {
            define('FEATHER_HEADER', 1);
        }

        // Render the header
        $this->title = isset($this->title) ? $this->title : feather_escape($this->config['o_board_title']);

        // Define $p if it's not set to avoid a PHP notice
        $this->page = isset($this->page) ? $this->page : null;

        // Set default safe values
        $this->page_head = isset($this->page_head) ? $this->page_head : null;
        $this->paging_links = isset($this->paging_links) ? $this->paging_links : null;
        $this->required_fields = isset($this->required_fields) ? $this->required_fields : null;

        $navlinks = $this->getNavlinks();
        $page_info = $this->getStatus();

        $focus_element = isset($this->focus_element) ? ' onload="document.getElementById(\''.$this->focus_element[0].'\').elements[\''.$this->focus_element[1].'\'].focus();"' : '';

        $this->feather->render('header.php', array(
                'page_title' => $this->title,
                'p' => $this->page,
                'feather_user' => $this->user,
                'feather_config' => $this->config,
                '_SERVER' => $_SERVER,
                'page_head' => $this->page_head,
                'paging_links' => $this->paging_links,
                'required_fields' => $this->required_fields,
                'feather' => $this->feather,
                'focus_element' => $focus_element,
                'navlinks' => $navlinks,
                'page_info' => $page_info,
            )
        );
    }

    private function getNavlinks()
    {
        $links = array();

        // Index should always be displayed
        $links[] = '<li id="navindex"'.((FEATHER_ACTIVE_PAGE == 'index') ? ' class="isactive"' : '').'><a href="'.get_base_url().'/">'.__('Index').'</a></li>';

        if ($this->user->g_read_board == '1' && $this->user->g_view_users == '1') {
            $links[] = '<li id="navuserlist"'.((FEATHER_ACTIVE_PAGE == 'userlist') ? ' class="isactive"' : '').'><a href="'.get_link('userlist/').'">'.__('User list').'</a></li>';
        }

        if ($this->config['o_rules'] == '1' && (!$this->user->is_guest || $this->user->g_read_board == '1' || $this->config['o_regs_allow'] == '1')) {
            $links[] = '<li id="navrules"'.((FEATHER_ACTIVE_PAGE == 'rules') ? ' class="isactive"' : '').'><a href="'.get_link('rules/').'">'.__('Rules').'</a></li>';
        }

        if ($this->user->g_read_board == '1' && $this->user->g_search == '1') {
            $links[] = '<li id="navsearch"'.((FEATHER_ACTIVE_PAGE == 'search') ? ' class="isactive"' : '').'><a href="'.get_link('search/').'">'.__('Search').'</a></li>';
        }

        if ($this->user->is_guest) {
            $links[] = '<li id="navregister"'.((FEATHER_ACTIVE_PAGE == 'register') ? ' class="isactive"' : '').'><a href="'.get_link('register/').'">'.__('Register').'</a></li>';
            $links[] = '<li id="navlogin"'.((FEATHER_ACTIVE_PAGE == 'login') ? ' class="isactive"' : '').'><a href="'.get_link('login/').'">'.__('Login').'</a></li>';
        } else {
            $links[] = '<li id="navprofile"'.((FEATHER_ACTIVE_PAGE == 'profile') ? ' class="isactive"' : '').'><a href="'.get_link('user/'.$this->user->id.'/').'">'.__('Profile').'</a></li>';

            if ($this->user->is_admmod) {
                $links[] = '<li id="navadmin"'.((FEATHER_ACTIVE_PAGE == 'admin') ? ' class="isactive"' : '').'><a href="'.get_link('admin/').'">'.__('Admin').'</a></li>';
            }

            $links[] = '<li id="navlogout"><a href="'.get_link('logout/id/'.$this->user->id.'/token/'.feather_hash($this->user->id.feather_hash($this->request->getIp()))).'/">'.__('Logout').'</a></li>';
        }

        // Are there any additional navlinks we should insert into the array before imploding it?
        if ($this->user->g_read_board == '1' && $this->config['o_additional_navlinks'] != '') {
            if (preg_match_all('%([0-9]+)\s*=\s*(.*?)\n%s', $this->config['o_additional_navlinks']."\n", $extra_links)) {
                // Insert any additional links into the $links array (at the correct index)
                $num_links = count($extra_links[1]);
                for ($i = 0; $i < $num_links; ++$i) {
                    array_splice($links, $extra_links[1][$i], 0, array('<li id="navextra'.($i + 1).'">'.$extra_links[2][$i].'</li>'));
                }
            }
        }

        $navlinks = '<div id="brdmenu" class="inbox">'."\n\t\t\t".'<ul>'."\n\t\t\t\t".implode("\n\t\t\t\t", $links)."\n\t\t\t".'</ul>'."\n\t\t".'</div>';

        return $navlinks;
    }

    private function getStatus()
    {
        $page_statusinfo = $page_topicsearches = array();

        if ($this->user->is_guest) {
            $page_statusinfo = '<p class="conl">'.__('Not logged in').'</p>';
        } else {
            $page_statusinfo[] = '<li><span>'.__('Logged in as').' <strong>'.feather_escape($this->user->username).'</strong></span></li>';
            $page_statusinfo[] = '<li><span>'.sprintf(__('Last visit'), format_time($this->user->last_visit)).'</span></li>';

            if ($this->user->is_admmod) {
                if ($this->config['o_report_method'] == '0' || $this->config['o_report_method'] == '2') {
                    if ($this->model->get_reports()) {
                        $page_statusinfo[] = '<li class="reportlink"><span><strong><a href="'.get_link('admin/reports/').'">'.__('New reports').'</a></strong></span></li>';
                    }
                }

                if ($this->config['o_maintenance'] == '1') {
                    $page_statusinfo[] = '<li class="maintenancelink"><span><strong><a href="'.get_link('admin/maintenance/').'">'.__('Maintenance mode enabled').'</a></strong></span></li>';
                }
            }

            if ($this->user->g_read_board == '1' && $this->user->g_search == '1') {
                $page_topicsearches[] = '<a href="'.get_link('search/show/replies/').'" title="'.__('Show posted topics').'">'.__('Posted topics').'</a>';
                $page_topicsearches[] = '<a href="'.get_link('search/show/new/').'" title="'.__('Show new posts').'">'.__('New posts header').'</a>';
            }
        }

        // Quick searches
        if ($this->user->g_read_board == '1' && $this->user->g_search == '1') {
            $page_topicsearches[] = '<a href="'.get_link('search/show/recent/').'" title="'.__('Show active topics').'">'.__('Active topics').'</a>';
            $page_topicsearches[] = '<a href="'.get_link('search/show/unanswered/').'" title="'.__('Show unanswered topics').'">'.__('Unanswered topics').'</a>';
        }

        // Generate all that jazz
        $page_info = '<div id="brdwelcome" class="inbox">';

        // The status information
        if (is_array($page_statusinfo)) {
            $page_info .= "\n\t\t\t".'<ul class="conl">';
            $page_info .= "\n\t\t\t\t".implode("\n\t\t\t\t", $page_statusinfo);
            $page_info .= "\n\t\t\t".'</ul>';
        } else {
            $page_info .= "\n\t\t\t".$page_statusinfo;
        }

        // Generate quicklinks
        if (!empty($page_topicsearches)) {
            $page_info .= "\n\t\t\t".'<ul class="conr">';
            $page_info .= "\n\t\t\t\t".'<li><span>'.__('Topic searches').' '.implode(' | ', $page_topicsearches).'</span></li>';
            $page_info .= "\n\t\t\t".'</ul>';
        }

        $page_info .= "\n\t\t\t".'<div class="clearer"></div>'."\n\t\t".'</div>';

        return $page_info;
    }
}
