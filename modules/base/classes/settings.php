<?php

//
// Open Web Analytics - An Open Source Web Analytics Framework
//
// Copyright 2006 Peter Adams. All rights reserved.
//
// Licensed under GPL v2.0 http://www.gnu.org/copyleft/gpl.html
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
//
// $Id$
//

/**
 * Settings Class
 * 
 * @author      Peter Adams <peter@openwebanalytics.com>
 * @copyright   Copyright &copy; 2006 Peter Adams <peter@openwebanalytics.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GPL v2.0
 * @category    owa
 * @package     owa
 * @version		$Revision$	      
 * @since		owa 1.0.0
 */
 
 class owa_settings {
 	
 	/**
 	 * Configuration Entity
 	 * 
 	 * @var object configuration entity
 	 */
 	var $config;
 	
 	var $default_config;
 	
 	var $db_settings = array();
 	
 	var $fetched_from_db;
 	
 	var $is_dirty;
 	
 	var $config_id;
 	
 	/**
 	 * Constructor
 	 * 
 	 * @param string id the id of the configuration array to load
 	 */	
 	function __construct() {
 	
 		// create configuration object
 		$this->config = owa_coreAPI::rawEntityFactory('base.configuration');
 		// load the default settings
 		$this->getDefaultConfig();
 		// include/load config file
 		$this->loadConfigFile();
 		// apply config constants
 		$this->applyConfigConstants();
 		// setup directory paths
 		$this->setupPaths();
 		
 		// set default timezone
		if (function_exists('date_default_timezone_set')) {
			date_default_timezone_set($this->get('base', 'timezone'));
		}
 		
 		// Todo: must remove config object dependancy from all classes generated by $this->load
 		// before we can uncomment this and remove it from owa_caller constructor or else there 
 		// is a race condition.
 		
 		//if ($this->isConfigFilePresent()) {
 		//	$this->load($this->get('base', 'configuration_id'));
 		//}
 			
 	}
 	
 	function isConfigFilePresent() {
 		
		$file = OWA_DIR.'owa-config.php';
		$oldfile = OWA_BASE_DIR.DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'owa-config.php';
		
		if (file_exists($file)) {
			return true;
		} elseif (file_exists($oldfile)) {
			return true;
		} else {
			return false;
		}
 	}
 	
 	function loadConfigFile() {
 	
 		/* LOAD CONFIG FILE */
		$file = OWA_DIR.'owa-config.php';
		$oldfile = OWA_BASE_DIR.DIRECTORY_SEPARATOR.'conf'.DIRECTORY_SEPARATOR.'owa-config.php';
		
		if (file_exists($file)) {
			include_once($file);
			$config_file_exists = true;
		} elseif (file_exists($oldfile)) {
			include_once($oldfile);
			$config_file_exists = true;
		} else {
			$config_file_exists = false;
		}
 	}
 	
 	function applyConfigConstants() {
 		
 		// Looks for log level constant
		if (defined('OWA_ERROR_LOG_LEVEL')) {
			$this->set('base', 'error_log_level', OWA_ERROR_LOG_LEVEL);
		}
		
		/* CONFIGURATION ID */
		
		if (defined('OWA_CONFIGURATION_ID')) {
			$this->set('base', 'configuration_id', OWA_CONFIGURATION_ID);
		}
		
		/* OBJECT CACHING */
	
		// Looks for object cache config constant
		// must comebefore user db values are fetched from db
		if (defined('OWA_CACHE_OBJECTS')) {
			$this->set('base', 'cache_objects', OWA_CACHE_OBJECTS);
		}
		
		/* DATABASE CONFIGURATION */
		
		// This needs to come before the fetch of user overrides from the DB
		// Constants defined in the config file have the final word
		// values passed from calling application must be applied prior
		// to the rest of the caller's overrides
		
		if (defined('OWA_DB_TYPE')) {
			$this->set('base', 'db_type', OWA_DB_TYPE);
		}
						
		if (defined('OWA_DB_NAME')) {
			$this->set('base', 'db_name', OWA_DB_NAME);
		}
		
		if (defined('OWA_DB_HOST')) {
			$this->set('base', 'db_host', OWA_DB_HOST);
		}
		
		if (defined('OWA_DB_USER')) {
			$this->set('base', 'db_user', OWA_DB_USER);
		}
		
		if (defined('OWA_DB_PASSWORD')) {
			$this->set('base', 'db_password', OWA_DB_PASSWORD);
		}
		
		/* SET ERROR HANDLER */
		if (defined('OWA_ERROR_HANDLER')) {
			$this->set('base', 'error_handler', OWA_ERROR_HANDLER);
		}
		
		if (defined('OWA_CONFIG_DO_NOT_FETCH_FROM_DB')) {
			$this->set('base', 'do_not_fetch_config_from_db', OWA_CONFIG_DO_NOT_FETCH_FROM_DB);
		}
		
		if (defined('OWA_PUBLIC_URL')) {
			$this->set('base', 'public_url', OWA_PUBLIC_URL);
		}
		
		if (defined('OWA_PUBLIC_PATH')) {
			$this->set('base', 'public_path', OWA_PUBLIC_PATH);
		}
		
		if (defined('OWA_QUEUE_EVENTS')) {
			$this->set('base', 'queue_events', OWA_QUEUE_EVENTS);
		}
		
		if (defined('OWA_EVENT_QUEUE_TYPE')) {
			$this->set('base', 'event_queue_type', OWA_EVENT_QUEUE_TYPE);
		}
		
		if (defined('OWA_USE_REMOTE_EVENT_QUEUE')) {
			$this->set('base', 'use_remote_event_queue', OWA_USE_REMOTE_EVENT_QUEUE);
		}
		
		if (defined('OWA_REMOTE_EVENT_QUEUE_TYPE')) {
			$this->set('base', 'remote_event_queue_type', OWA_REMOTE_EVENT_QUEUE_TYPE);
		}
		
		if (defined('OWA_REMOTE_EVENT_QUEUE_ENDPOINT')) {
			$this->set('base', 'remote_event_queue_endpoint', OWA_REMOTE_EVENT_QUEUE_ENDPOINT);
		}
		
 	}
 	
 	function applyModuleOverrides($module, $config) {
 		
 		// merge default config with overrides 
 		
 		if (!empty($config)) {
 		
 			$in_place_config = $this->config->get('settings');
 			
 			$old_array = $in_place_config[$module];
 			
	 		$new_array = array_merge($old_array, $config);
 		
			$in_place_config[$module] = $new_array; 
			 		
		 	$this->config->set('settings', $in_place_config);
		 	
		 	//print_r($this->config->get('settings'));
		 	
	 	}	
 	}
 	
 	/**
 	 * Loads configuration from data store
 	 * 
 	 * @param string id  the id of the configuration array to load
 	 */
 	function load($id = 1) {
			
			$this->config_id = $id; 
 
			$db_config = owa_coreAPI::entityFactory('base.configuration');
			$db_config->getByPk('id', $id);
			$db_settings = unserialize($db_config->get('settings'));
			
			//print $db_settings;
			// store copy of config for use with updates and set a flag
			if (!empty($db_settings)):
				
				// needed to get rid of legacy setting that used to be stored in the DB.
				if (array_key_exists('error_handler', $db_settings['base'])) {
					unset($db_settings['base']['error_handler']);
				}
			
				$this->db_settings = $db_settings;
				$this->config_from_db = true;
			endif;
						
			if (!empty($db_settings)):
				//print_r($db_settings);
				//$db_settings = unserialize($db_settings);
				
				$default = $this->config->get('settings');
				
				// merge default config with overrides fetched from data store
				
				$new_config = array();
				
				foreach ($db_settings as $k => $v) {
					
					if (is_array($default[$k])):
						$new_config[$k] = array_merge($default[$k], $db_settings[$k]);
					else:
						$new_config[$k] = $db_settings[$k];
					endif;
				}
				
				$this->config->set('settings', $new_config);
					
				
			endif;
			
			$db_id = $db_config->get('id');
			$this->config->set('id', $db_id);
	 			
 		return;
 		
 	}
 	
 	/**
 	 * Fetches a modules entire configuration array
 	 * 
 	 * @param string $module The name of module whose configuration values you want to fetch
 	 * @return array Config values
 	 */
 	function fetch($module = '') {
	 	$v = $this->config->get('settings');
	 	
 		if (!empty($module)):
 		
 			return $v[$module];
		else:
			return $v['base'];
		endif;
 	}
 	
 	/**
 	 * updates or creates configuration values
 	 * 
 	 * @return boolean 
 	 */
 	function save() {
 		
 		// serialize array of values prior to update
 		
		$config = owa_coreAPI::entityFactory('base.configuration');
		
		// if fetch from db flag is not true, try to fetch the config just in 
		// case if was cached or something wen wrong.
		// Then merge the new values into it.
		if ($this->config_from_db != true):
			
			$config->getByPk('id', $this->get('base', 'configuration_id'));
			
			$settings = $config->get('settings');
			
			if (!empty($settings)):
				
				$settings = unserialize($settings);
				
				$new_config = array();
				
				foreach ($this->db_settings as $k => $v) {
				
					if (!is_array($settings[$k])):
						$settings[$k] = array();
					endif;
					
					$new_config[$k] = array_merge($settings[$k], $this->db_settings[$k]);
					
				}
				
				$config->set('settings', serialize($new_config));	
			
				//$config->set('settings', serialize(array_merge($settings, $this->db_settings)));
			else:			
				$config->set('settings', serialize($this->db_settings));
			endif;
			
			// test to see if object exists
			$id = $config->get('id');
			
			// if it does just update
			if (!empty($id)):
				$status = $config->update();
				
			// else create the object
			else:
				$config->set('id', $this->get('base', 'configuration_id'));
				$status = $config->create();
			endif; 
			
		// update the config	
		else:
			$config->set('settings', serialize($this->db_settings));
			$config->set('id', $this->get('base', 'configuration_id'));
			$status = $config->update();
		endif;
		
 		return $status;
 		
 	}
 	
 	/**
 	 * Accessor Method
 	 * 
 	 * @param string $module the name of the module
 	 * @param string $key the configuration key
 	 * @return unknown
 	 */
 	function get($module, $key) {
 		
 		$values = $this->config->get('settings');
 		
 		if ($values[$module] && array_key_exists($key, $values[$module])) {
 			return $values[$module][$key];
 		} else {
 			return false;
 		}
 		
 	}
 	
 	/**
 	 * Sets configuration value. will not be persisted. NEEDED?
 	 * 
 	 * @param string $module the name of the module
 	 * @param string $key the configuration key
 	 * @param string $value the configuration value
 	 * @return boolean
 	 */
 	function set($module, $key, $value) {
 		
 		$values = $this->config->get('settings');
 		
 		$values[$module][$key] = $value;
 		
 		$this->config->set('settings', $values);
 		
 		return;
 	}
 	
 	
 	/**
 	 * Adds Setting value to be configuration and persistant data store
 	 * 
 	 * @param string $module the name of the module
 	 * @param string $key the configuration key
 	 * @param string $value the configuration value
 	 * @depricated 
 	 */
 	function setSetting($module, $key, $value) {
 	
 		return $this->set($module, $key, $value);
 	
 	}
 	
 	/**
 	 * Adds Setting value to be configuration and persistant data store
 	 * 
 	 * @param string $module the name of the module
 	 * @param string $key the configuration key
 	 * @param string $value the configuration value
 	 * @return 
 	 */
 	function persistSetting($module, $key, $value) {
 	
 		$this->set($module, $key, $value);
	 	$this->db_settings[$module][$key] = $value;
	 	$this->is_dirty = true;
 	}

 	
 	
 	/**
 	 * Adds Setting value to be configuration but DOES NOT add to persistant data store
 	 * 
 	 * @param string $module the name of the module
 	 * @param string $key the configuration key
 	 * @param string $value the configuration value
 	 * @return 
 	 */
 	function setSettingTemporary($module, $key, $value) {
 	
 		$this->set($module, $key, $value);
	 	
	 	return;
 	
 	}
 	
 	/**
 	 * Replaces all values of a particular module's configuration
 	 * @todo: search to see where else this is used. If unused then make it for use in persist only.
 	 */
 	function replace($module, $values, $persist = false) {
 		
 		if ($persist) {
 			$this->db_settings[$module] = $values; 
 			return;
 		}
 		
 		$settings = $this->config->get('settings');
 		
 		$settings[$module] = $values;
 		
 		$this->config->set('settings', $settings);
 	}
 	
 	/**
 	 * Alternate Constructor for base module settings
 	 * Needed for backwards compatability with older classes
 	 * 
 	 */
 	function &get_settings($id = 1) {
 		
 		
 		static $config2;
 		
 		if (!isset($config2)):
 			//print 'hello from alt constructor';
 			$config2 = &owa_coreAPI::configSingleton();
 		endif;
 		
 		return $config2->fetch('base');
 		
 	}
 	
 	function getDefaultConfig() {
 		
 		$config =  array('base' => array(
	
			'ns'							=> 'owa_',
			'visitor_param'					=> 'v',
			'session_param'					=> 's',
			'site_session_param'			=> 'ss',
			'last_request_param'			=> 'last_req',
			'first_hit_param'				=> 'first_hit',
			'feed_subscription_param'		=> 'sid',
			'source_param'					=> 'from',
			'graph_param'					=> 'graph',
			'period_param'					=> 'period',
			'document_param'				=> 'document',
			'referer_param'					=> 'referer',
			'site_id'						=> '',
			'configuration_id'				=> '1',
			'session_length'				=> 1800,
			'requests_table'				=> 'request',
			'sessions_table'				=> 'session',
			'referers_table'				=> 'referer',
			'ua_table'						=> 'ua',
			'os_table'						=> 'os',
			'documents_table'				=> 'document',
			'sites_table'					=> 'site',
			'hosts_table'					=> 'host',
			'config_table'					=> 'configuration',
			'version_table'					=> 'version',
			'feed_requests_table'			=> 'feed_request',
			'visitors_table'				=> 'visitor',
			'impressions_table'				=> 'impression',
			'clicks_table'					=> 'click',
			'exits_table'					=> 'exit',
			'users_table'					=> 'user',
			'db_type'						=> '',
			'db_name'						=> '',
			'db_host'						=> '',
			'db_user'						=> '',
			'db_password'					=> '',
			'db_force_new_connections'		=> true,
			'db_make_persistant_connections'=> false,
			'resolve_hosts'					=> true,
			'log_feedreaders'				=> true,
			'log_robots'					=> false,
			'log_sessions'					=> true,
			'log_dom_clicks'				=> true,
			'delay_first_hit'				=> true,
			'async_db'						=> false,
			'clean_query_string'			=> true,
			'fetch_refering_page_info'		=> true,
			'query_string_filters'			=> '',
			'async_log_dir'					=> OWA_DATA_DIR . 'logs/',
			'async_log_file'				=> 'events.txt',
			'async_lock_file'				=> 'owa.lock',
			'async_error_log_file'			=> 'events_error.txt',
			'notice_email'					=> '',
			'log_php_errors'				=> false,
			'error_handler'					=> 'production',
			'error_log_level'				=> 0,
			'error_log_file'				=> OWA_DATA_DIR . 'logs/errors.txt',
			'browscap.ini'					=> OWA_BASE_DIR . '/modules/base/data/php_browscap.ini',
			'search_engines.ini'			=> OWA_BASE_DIR . '/conf/search_engines.ini',
			'query_strings.ini'				=> OWA_BASE_DIR . '/conf/query_strings.ini',
			'db_class_dir'					=> OWA_BASE_DIR . '/plugins/db/',
			'templates_dir'					=> OWA_BASE_DIR . '/templates/',
			'plugin_dir'					=> OWA_BASE_DIR . '/plugins/',
			'module_dir'					=> OWA_BASE_DIR . '/modules',
			'public_path'					=> '',
			'geolocation_lookup'            => true,
			'geolocation_service'			=> 'hostip',
			'report_wrapper'				=> 'wrapper_default.tpl',
			'do_not_fetch_config_from_db'	=> false,
			'announce_visitors'				=> false,
			'public_url'					=> '',
			'base_url'						=> '',
			'action_url'					=> '',
			'images_url'					=> '',
			'reporting_url'					=> '',
			//'p3p_policy'					=> 'NOI NID ADMa OUR IND UNI COM NAV',
			'p3p_policy'					=> 'NOI ADM DEV PSAi COM NAV OUR OTRo STP IND DEM',
			'graph_link_template'			=> '%s?owa_action=graph&name=%s&%s', //action_url?...
			'link_template'					=> '%s?%s', // main_url?key=value....
			'owa_user_agent'				=> 'Open Web Analytics Bot '.OWA_VERSION,
			'fetch_owa_news'				=> true,
			'owa_rss_url'					=> 'http://www.openwebanalytics.com/?feed=rss2',
			'use_summary_tables'			=> false,
			'summary_framework'				=> '',
			'click_drawing_mode'			=> 'center_on_page',
			'log_clicks'					=> true,
			'log_dom_streams'				=> true,
			'timezone'						=> 'America/Los_Angeles',
			'log_dom_stream_percentage'		=> 50,
			'owa_wiki_link_template'		=> 'http://wiki.openwebanalytics.com/index.php?title=%s',
			'password_length'				=> 4,
			'modules'						=> array('base'),
			'mailer-from'					=> '',
			'mailer-fromName'				=> 'OWA Mailer',
			'mailer-host'					=> '',
			'mailer-port'					=> '',
			'mailer-smtpAuth'				=> '',
			'mailer-username'				=> '',
			'mailer-password'				=> '',
			'queue_events'					=> false,
			'event_queue_type'				=> '',
			'use_remote_event_queue'		=> true,
			'remote_event_queue_type'		=> 'http',
			'remote_event_queue_endpoint'	=> '',
			'cookie_domain'					=> '',
			'ws_timeout'					=> 10,
			'is_active'						=> true,
			'per_site_visitors'				=> false,
			'cache_objects'					=> true,
			'log_named_users'				=> true,
			'log_visitor_pii'				=> true,
			'do_not_log_ips'				=> '',
			'track_feed_links'				=> true,
			'theme'							=> '',
			'reserved_words'				=> array('do' => 'action'),
			'login_view'					=> 'base.login',
			'not_capable_view'				=> 'base.error',
			'start_page'					=> 'base.reportDashboard',
			'default_action'				=> 'base.loginForm',
			'default_cache_expiration_period' => 604800,
			'capabilities'					=> array('admin' => array('view_reports', 
																	  'edit_settings', 
																	  'edit_sites', 
																	  'edit_users', 
																	  'edit_modules'),
													 'analyst' => array('view_reports'), 
													 'viewer' => array('view_reports'), 
													 'everyone' => array())
			
			));
			
			// set default values
			$this->config->set('settings', $config);
			
			
			return;
 		
 	}
 	
 	function setupPaths() {
 		
 		//build base url
 		$base_url  = "http";
		
		if(isset($_SERVER['HTTPS'])) {
			$base_url .= 's';
		}
					
		$base_url .= '://'.$_SERVER['SERVER_NAME'];
			
		if($_SERVER['SERVER_PORT'] != 80) {
			$base_url .= ':'.$_SERVER['SERVER_PORT'];
		}
		
		// there is some plugin use case where this is needed i think. if not get rid of it.
		if (!defined('OWA_PUBLIC_URL')) {
			define('OWA_PUBLIC_URL', '');
		}
		
		// set base url
		$this->set('base', 'base_url', $base_url);					
		
		//set public path if not defined in config file
		$public_path = $this->get('base', 'public_path');
		
		if (empty($public_path)) {
			$public_path = OWA_PATH.DIRECTORY_SEPARATOR.'public'.DIRECTORY_SEPARATOR;
			$this->set('base','public_path', $public_path); 
		}
		
		// set various paths
		$public_url = $this->get('base', 'public_url');
		$main_url = $public_url.'index.php';
		$this->set('base','main_url', $main_url);
		$this->set('base','main_absolute_url', $main_url);
		$modules_url = $public_url.'modules'.DIRECTORY_SEPARATOR;
		$this->set('base','modules_url', $modules_url);
		$this->set('base','action_url',$public_url.'action.php');
		$this->set('base','images_url', $modules_url);
		$this->set('base','images_absolute_url',$modules_url);
		$this->set('base','log_url',$public_url.'log.php');
		
		// Set cookie domain
		if (!empty($_SERVER['HTTP_HOST'])) {
			$this->set('base','cookie_domain', $_SERVER['HTTP_HOST']);
		} else {		
			$this->set('base','cookie_domain', $_SERVER['SERVER_NAME']);
		}
 		
 	}
 	
 	function createConfigFile($config_values) {
 		
 		if (file_exists(OWA_DIR.'owa-config.php')) {
 			owa_coreAPI::error("Your config file already exists. If you need to change your configuration, edit that file at: ".OWA_DIR.'owa-config.php');
 			require_once(OWA_DIR . 'owa-config.php');
			return true;
 		}
 		
 		if (!file_exists(OWA_DIR.'owa-config-dist.php')) {
 			owa_coreAPI::error("We can't find the configuration file template. Are you sure you installed OWA's files correctly? Exiting.");
 			exit;
 		} else {
 			$configFileTemplate = file(OWA_DIR . 'owa-config-dist.php');
 			owa_coreAPI::debug('found sample config file.');
 		}
 		
 		$handle = fopen(OWA_DIR . 'owa-config.php', 'w');

		foreach ($configFileTemplate as $line_num => $line) {
			switch (substr($line,0,20)) {
				case "define('OWA_DB_TYPE'":
					fwrite($handle, str_replace("yourdbtypegoeshere", $config_values['db_type'], $line));
					break;
				case "define('OWA_DB_NAME'":
					fwrite($handle, str_replace("yourdbnamegoeshere", $config_values['db_name'], $line));
					break;
				case "define('OWA_DB_USER'":
					fwrite($handle, str_replace("yourdbusergoeshere", $config_values['db_user'], $line));
					break;
				case "define('OWA_DB_PASSW":
					fwrite($handle, str_replace("yourdbpasswordgoeshere", $config_values['db_password'], $line));
					break;
				case "define('OWA_DB_HOST'":
					fwrite($handle, str_replace("yourdbhostgoeshere", $config_values['db_host'], $line));
					break;
				case "define('OWA_PUBLIC_U":
					fwrite($handle, str_replace("http://domain/path/to/owa/", $config_values['public_url'], $line));
					break;
				default:
					fwrite($handle, $line);
			}
		}
		
		fclose($handle);
		chmod(OWA_DIR . 'owa-config.php', 0666);
		owa_coreAPI::debug('Config file created');
		require_once(OWA_DIR . 'owa-config.php');
		return true;
	
	}
	
	function reset($module) {
	
		if ($module) {
		
			$defaults = array();
			$defaults['install_complete'] = true;
			$defaults['schema_version'] = $this->get($module, 'schema_version');
			$this->replace('base', $defaults, true);	
			return $this->save();
		} else {
			return false;
		}			
	} 	
}

?>