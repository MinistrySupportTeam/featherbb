<?php
/**
 *
 * Copyright (C) 2015 FeatherBB
 * based on code by (C) 2008-2012 FluxBB
 * and Rickard Andersson (C) 2002-2008 PunBB
 * License: http://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 *
 * $app = new \Slim\Slim();
 * $app->add(new \Slim\Extras\Middleware\FeatherBBLoader(array $config));
 *
 */

namespace FeatherBB;
use DB;

class Core extends \Slim\Middleware
{
    protected $forum_env,
              $forum_settings;
    protected $headers = array(
        'Cache-Control' => 'no-cache, no-store, must-revalidate',
        'Pragma' => 'no-cache',
        'Content-type' => 'text/html',
        'X-Frame-Options' => 'deny');

	public function __construct(array $data)
	{
        // Handle empty values in data
        $data = array_merge(array('config_file' => 'include/config.php',
                                  'cache_dir' => 'cache/',
                                  'debug'   => false), $data);
        // Define some core variables
        $this->forum_env['FEATHER_ROOT'] = realpath(dirname(__FILE__).'/../../').'/';
        $this->forum_env['FORUM_CACHE_DIR'] = is_writable($this->forum_env['FEATHER_ROOT'].$data['cache_dir']) ? realpath($this->forum_env['FEATHER_ROOT'].$data['cache_dir']).'/' : null;
        $this->forum_env['FORUM_CONFIG_FILE'] = $this->forum_env['FEATHER_ROOT'].$data['config_file'];
        $this->forum_env['FEATHER_DEBUG'] = $this->forum_env['FEATHER_SHOW_QUERIES'] = ($data['debug'] == 'all');
        $this->forum_env['FEATHER_SHOW_INFO'] = ($data['debug'] == 'info' || $data['debug'] == 'all');
        // Populate forum_env
        $this->forum_env = array_merge(self::load_default_forum_env(), $this->forum_env);
        $this->env_to_globals($this->forum_env); // Legacy

        // Load files
        require $this->forum_env['FEATHER_ROOT'].'include/utf8/utf8.php';
        require $this->forum_env['FEATHER_ROOT'].'include/functions.php';
        require $this->forum_env['FEATHER_ROOT'].'include/classes/pomo/MO.php';
        require $this->forum_env['FEATHER_ROOT'].'include/l10n.php';
        require $this->forum_env['FEATHER_ROOT'].'include/classes/database.class.php';
        require $this->forum_env['FEATHER_ROOT'].'plugins/test/plugintest.php';

        // Force POSIX locale (to prevent functions such as strtolower() from messing up UTF-8 strings)
        setlocale(LC_CTYPE, 'C');
	}

    public static function load_default_forum_env()
    {
        return array(
                'FEATHER' => true, // Legacy
                'FEATHER_ROOT' => '',
                'FORUM_CONFIG_FILE' => 'include/config.php',
                'FORUM_CACHE_DIR' => 'cache/',
                'FORUM_VERSION' => '1.0.0',
                'FORUM_NAME' => 'FeatherBB',
                'FORUM_DB_REVISION' => 21,
                'FORUM_SI_REVISION' => 2,
                'FORUM_PARSER_REVISION' => 2,
                'FEATHER_UNVERIFIED' => 0,
                'FEATHER_ADMIN' => 1,
                'FEATHER_MOD' => 2,
                'FEATHER_GUEST' => 3,
                'FEATHER_MEMBER' => 4,
                'FEATHER_MAX_POSTSIZE' => 32768,
                'FEATHER_SEARCH_MIN_WORD' => 3,
                'FEATHER_SEARCH_MAX_WORD' => 20,
                'FORUM_MAX_COOKIE_SIZE' => 4048,
                'FEATHER_DEBUG' => false,
                'FEATHER_SHOW_QUERIES' => false,
                'FEATHER_SHOW_INFO' => false
                );
    }

    public static function load_default_forum_settings()
    {
        return array(
                // Database
                'db_type' => 'mysqli',
                'db_host' => '',
                'db_name' => '',
                'db_user' => '',
                'db_pass' => '',
                'db_prefix' => '',
                // Cookies
                'cookie_name' => 'feather_cookie',
                'cookie_seed' => 'changeme', // MUST BE CHANGED !!!
                );
    }

    public static function init_db(array $config, $log_queries = false)
    {
        $config['db_prefix'] = (!empty($config['db_prefix'])) ? $config['db_prefix'] : '';
        switch ($config['db_type']) {
            case 'mysql':
                DB::configure('mysql:host='.$config['db_host'].';dbname='.$config['db_name']);
                DB::configure('driver_options', array(\PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'));
                break;
            case 'sqlite';
            case 'sqlite3';
                DB::configure('sqlite:./'.$config['db_name']);
                break;
            case 'pgsql':
                DB::configure('pgsql:host='.$config['db_host'].'dbname='.$config['db_name']);
                break;
        }
        DB::configure('username', $config['db_user']);
        DB::configure('password', $config['db_pass']);
        DB::configure('prefix', $config['db_prefix']);
        if ($log_queries) {
            DB::configure('logging', true);
        }
        DB::configure('id_column_overrides', array(
            $config['db_prefix'].'groups' => 'g_id',
        ));
    }

    // Getters / setters for Slim container (avoid magic get error)

    public function set_forum_env($key, $value = null)
    {
        $tmp = (!is_array($key) && !is_null($value)) ? array($key, $value) : $key;
        foreach ($tmp as $key => $value) {
            $this->app->container->get('forum_env')[$key] = $value;
        }

    }

    public function set_forum_settings($key, $value = null)
    {
        $tmp = (!is_array($key) && !is_null($value)) ? array($key, $value) : $key;
        foreach ($tmp as $key => $value) {
            $this->app->container->get('forum_settings')[$key] = $value;
        }
    }

    // Legacy function, to ensure backward compatibility with globals
    public function env_to_globals(array $vars)
    {
        foreach ($vars as $key => $value) {
            define($key, $value);
        }
    }

    public function hydrate($name, array $data)
    {
        $this->app->container[$name] = $data;
    }

    // Headers

    public function set_headers()
    {
        foreach ($this->headers as $label => $value) {
            $this->app->response->headers->set($label, $value);
        }
        $this->app->response()->headers()->set('X-Powered-By', $this->forum_env['FORUM_NAME']);
        $this->app->expires(0);
    }

    public function call()
    {
        global $forum_time_formats, $forum_date_formats, $feather_config; // Legacy

        // Set headers
        $this->set_headers();

        // Block prefetch requests
        if ((isset($this->app->environment['HTTP_X_MOZ'])) && ($this->app->environment['HTTP_X_MOZ'] == 'prefetch')) {
            return $this->app->response->setStatus(403); // Send forbidden header
        }

        // Populate Slim object with forum_env vars
        $this->hydrate('forum_env', $this->forum_env);
        // Record start time
        $this->app->start = get_microtime();
        // Define now var
        $this->app->now = function () {
            return time();
        };
        // Load FeatherBB cache
        $this->app->container->singleton('cache', function ($container) {
            $path = $container->forum_env['FORUM_CACHE_DIR'];
            return new \FeatherBB\Cache(array('name' => 'feather',
                                               'path' => $path,
                                               'extension' => '.cache'));
        });
        // Load FeatherBB view
        $this->app->container->singleton('view2', function() {
            return new \FeatherBB\View();
        });
        // Load FeatherBB hooks
        $this->app->container->singleton('hooks', function () {
            return new \FeatherBB\Hooks();
        });
        // Load FeatherBB email class
        $this->app->container->singleton('email', function () {
            return new \FeatherBB\Email();
        });

        if (!is_file($this->forum_env['FORUM_CONFIG_FILE'])) {
            $installer = new \controller\install;
            $installer->run();
            return;
        }

        // Load config from disk
        $config_file = json_decode(file_get_contents($this->forum_env['FORUM_CONFIG_FILE']), true);
        if (!is_null($config_file)) {
            $this->forum_settings = array_merge(self::load_default_forum_settings(), $config_file);
        } else {
            $this->app->response->setStatus(500); // Send forbidden header
            return $this->app->response->setBody('Wrong config file format');
        }

        // Init DB and configure Slim
        self::init_db($this->forum_settings, $this->forum_env['FEATHER_SHOW_INFO']);
        $this->app->config(array('debug' => $this->forum_env['FEATHER_DEBUG'],
                                 'cookies.encrypt' => true,
                                 'cookies.secret_key' => $this->forum_settings['cookie_seed']));

        if (!$this->app->cache->isCached('config')) {
            $this->app->cache->store('config', \model\cache::get_config());
        }

        $feather_config = $this->app->cache->retrieve('config');
        // Finalize forum_settings array
        $this->forum_settings = array_merge($feather_config, $this->forum_settings);

        // Set default style and assets
        $this->app->view2->setStyle($this->forum_settings['o_default_style']);
        $this->app->view2->addAsset('js', 'style/FeatherBB/phone.min.js');
        $this->app->view2->addAsset('js', 'js/common.js');

        // Populate FeatherBB Slim object with forum_settings vars
        $this->hydrate('forum_settings', $this->forum_settings);
        $this->app->config = $this->forum_settings; // Legacy
        extract($this->forum_settings); // Legacy

        new \plugin\plugintest();

        // Define time formats
        $forum_time_formats = array($this->forum_settings['o_time_format'], 'H:i:s', 'H:i', 'g:i:s a', 'g:i a');
        $forum_date_formats = array($this->forum_settings['o_date_format'], 'Y-m-d', 'Y-d-m', 'd-m-Y', 'm-d-Y', 'M j Y', 'jS M Y');

        $this->app->config('templates.path', $this->app->forum_env['FEATHER_ROOT'].'style/FeatherBB/view/');
        // Call FeatherBBAuth middleware
        $this->next->call();
    }
}
