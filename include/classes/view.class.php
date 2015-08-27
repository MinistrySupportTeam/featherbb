<?php

/**
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

 namespace FeatherBB;

 class View
 {
     protected $templatesDirectory,
               $templates,
               $app,
               $data,
               $page,
               $assets,
               $validation = array(
                   'page_number' => 'intval',
                   'active_page' => 'strval',
                   //'focus_element' => 'strval',
                   'is_indexed' => 'boolval',
                   'admin_console' => 'boolval',
                   'has_reports' => 'boolval',
                   'paging_links' => 'strval',
                   //'required_fields' => 'strval',
                   'has_reports' => 'boolval',
                   'footer_style' => 'strval',
                   'fid' => 'intval',
                   'pid' => 'intval',
                   'tid' => 'intval');

     /**
      * Constructor
      */
     public function __construct()
     {
         $this->data = $this->page = new \Slim\Helper\Set();
         $this->app = \Slim\Slim::getInstance();
     }

     /********************************************************************************
      * Data methods
      *******************************************************************************/

     /**
      * Does view data have value with key?
      * @param  string  $key
      * @return boolean
      */
     public function has($key)
     {
         return $this->data->has($key);
     }

     /**
      * Return view data value with key
      * @param  string $key
      * @return mixed
      */
     public function get($key)
     {
         return $this->data->get($key);
     }

     /**
      * Set view data value with key
      * @param string $key
      * @param mixed $value
      */
     public function set($key, $value)
     {
         $this->data->set($key, $value);
     }

     /**
      * Set view data value as Closure with key
      * @param string $key
      * @param mixed $value
      */
     public function keep($key, \Closure $value)
     {
         $this->data->keep($key, $value);
     }

     /**
      * Return view data
      * @return array
      */
     public function all()
     {
         return $this->data->all();
     }

     /**
      * Replace view data
      * @param  array  $data
      */
     public function replace(array $data)
     {
         $this->data->replace($data);
     }

     /**
      * Clear view data
      */
     public function clear()
     {
         $this->data->clear();
     }

     /********************************************************************************
      * Legacy data methods
      *******************************************************************************/

     /**
      * DEPRECATION WARNING! This method will be removed in the next major point release
      *
      * Get data from view
      */
     public function getData($key = null)
     {
         if (!is_null($key)) {
             return isset($this->data[$key]) ? $this->data[$key] : null;
         }

         return $this->data->all();
     }

     /**
      * DEPRECATION WARNING! This method will be removed in the next major point release
      *
      * Set data for view
      */
     public function setData()
     {
         $args = func_get_args();
         if (count($args) === 1 && is_array($args[0])) {
             $this->data->replace($args[0]);
         } elseif (count($args) === 2) {
             // Ensure original behavior is maintained. DO NOT invoke stored Closures.
             if (is_object($args[1]) && method_exists($args[1], '__invoke')) {
                 $this->data->set($args[0], $this->data->protect($args[1]));
             } else {
                 $this->data->set($args[0], $args[1]);
             }
         } else {
             throw new \InvalidArgumentException('Cannot set View data with provided arguments. Usage: `View::setData( $key, $value );` or `View::setData([ key => value, ... ]);`');
         }
     }

     /**
      * DEPRECATION WARNING! This method will be removed in the next major point release
      *
      * Append data to view
      * @param  array $data
      */
     public function appendData($data)
     {
         if (!is_array($data)) {
             throw new \InvalidArgumentException('Cannot append view data. Expected array argument.');
         }
         $this->data->replace($data);
     }

     /********************************************************************************
      * Resolve template paths
      *******************************************************************************/

     /**
      * Set the base directory that contains view templates
      * @param   string $directory
      * @throws  \InvalidArgumentException If directory is not a directory
      */
     public function setTemplatesDirectory($directory)
     {
         $this->templatesDirectory = rtrim($directory, DIRECTORY_SEPARATOR);
     }

     /**
      * Get templates base directory
      * @return string
      */
     public function getTemplatesDirectory()
     {
         return $this->templatesDirectory;
     }

     /**
      * Get fully qualified path to template file using templates base directory
      * @param  string $file The template file pathname relative to templates base directory
      * @return string
      */
      public function getTemplatePathname($file)
      {
          $pathname = $this->templatesDirectory . DIRECTORY_SEPARATOR . ltrim($file, DIRECTORY_SEPARATOR);
          if (!is_file($pathname)) {
              $pathname = $this->app->forum_env['FEATHER_ROOT'] . 'view/' . ltrim($file, DIRECTORY_SEPARATOR); // Fallback on default view
              if (!is_file($pathname)) {
                  throw new \RuntimeException("View cannot add template `$file` to stack because the template does not exist");
              }
          }
          return (string) $pathname;
      }

     /********************************************************************************
      * Rendering
      *******************************************************************************/

     public function display($data = null)
     {
         echo $this->fetch($data);
     }

     public function fetch($data = null)
     {
         // Force flash messages
         if (isset($this->app->environment['slim.flash'])) {
             $this->data->set('flash', $this->app->environment['slim.flash']);
         }
         $data = array_merge($this->getDefaultPageInfo(), $this->page->all(), $this->data->all(), (array) $data);
         $data['feather'] = \Slim\Slim::getInstance();
         $data['assets'] = $this->getAssets();
         $data = $this->app->hooks->fire('view.alter_data', $data);
         return $this->render($data);
     }

     protected function render($data = null)
     {
         extract($data);
         ob_start();

         require $this->getTemplatePathname('header.new.php');
         foreach ($this->getTemplates() as $tpl) {
             require $tpl;
         }
         require $this->getTemplatePathname('footer.new.php');
         return ob_get_clean();
     }

     /********************************************************************************
      * Getters and setters
      *******************************************************************************/

     public function setStyle($style)
     {
         if (!is_dir($this->app->forum_env['FEATHER_ROOT'].'style/'.$style.'/view/')) {
             throw new \InvalidArgumentException('The style '.$style.' doesn\'t exist');
         }
         $this->data->set('style', (string) $style);
         $this->setTemplatesDirectory($this->app->forum_env['FEATHER_ROOT'].'style/'.$style.'/view');
         $this->addAsset('css', 'style/'.$style.'.css');
         return $this;
     }

     public function getStyle()
     {
         return $this->data['style'];
     }

     public function setPageInfo(array $data)
     {
         foreach ($data as $key => $value) {
             list($key, $value) = $this->validate($key, $value);
             $this->page->set($key, $value);
         }
         return $this;
     }

     public function getPageInfo()
     {
         return $this->page->all();
     }

     protected function validate($key, $value)
     {
         $key = (string) $key;
         if (isset($this->validation[$key])) {
             if (function_exists($this->validation[$key])) {
                 $value = $this->validation[$key]($value);
             }
         }
         return array($key, $value);
     }

     public function addAsset($type, $asset, $params = null)
     {
         $type = (string) $type;
         if (!in_array($type, array('js', 'css'))) {
             throw new \Exception('Invalid asset type : ' . $type);
         }
         if (!is_file($this->app->forum_env['FEATHER_ROOT'].$asset)) {
             throw new \Exception('The asset file ' . $asset . ' does not exist');
         }
         $this->assets[$type][] = array(
             'file' => $asset,
             'params' => $params
         );
     }

     public function getAssets()
     {
         return $this->assets;
     }

     public function addTemplate($tpl, $priority = 10)
     {
         $tpl = (array) $tpl;
         foreach ($tpl as $key => $tpl_file) {
             $this->templates[(int) $priority][] = $this->getTemplatePathname((string) $tpl_file);
         }
         return $this;
     }

     public function getTemplates()
     {
         $output = array();
         if (count($this->templates) > 1) {
             ksort($this->templates);
         }
         foreach ($this->templates as $priority) {
             if (!empty($priority)) {
                 foreach ($priority as $tpl) {
                    $output[] = $tpl;
                 }
             }
         }
         return $output;
     }

     public function __call($method, $args)
     {
         $method = mb_substr(preg_replace_callback('/([A-Z])/', function ($c) {
             return "_" . strtolower($c[1]);
         }, $method), 4);
         if (empty($args)) {
             $args = null;
         }
         list($key, $value) = $this->validate($method, $args);
         $this->page->set($key, $value);
     }

     protected function getDefaultPageInfo()
     {
         if (!$this->app->cache->isCached('quickjump')) {
             $this->app->cache->store('quickjump', \model\cache::get_quickjump());
         }

         $data = array(
             'title' => feather_escape($this->app->forum_settings['o_board_title']),
             'page_number' => null,
             'active_page' => 'index',
             'focus_element' => null,
             'is_indexed' => true,
             'admin_console' => false,
             'page_head' => null,
             'paging_links' => null,
             'required_fields' => null,
             'footer_style' => null,
             'quickjump' => $this->app->cache->retrieve('quickjump'),
             'fid' => null,
             'pid' => null,
             'tid' => null,
         );

         if ($this->app->user->is_admmod) {
             $data['has_reports'] = \model\header::get_reports();
         }

         return $data;
     }
 }
