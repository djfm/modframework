<?php

class ModuleBase extends Module
{
	protected static $framework_initialized = false;


	public function __construct()
	{
		if(!static::$framework_initialized)
		{
			static::initialize_framework();
			static::$framework_initialized = true;
		}

		parent::__construct();
	}

	public function getHookList()
	{
		$hooks = array('backOfficeHeader');
		if(isset($this->hooks) && is_array($this->hooks))
		{
			$hooks = array_merge($hooks, $this->hooks);
		}
		return $hooks;
	}

	public function autoRegisterHooks()
	{
		foreach($this->getHookList() as $hook)
		{
			$ok = $this->registerHook($hook);
			if(!$ok)
			{
				return false;
			}
		}
		return true;
	}

	public function autoUnregisterHooks()
	{
		foreach($this->getHookList() as $hook)
		{
			$ok = $this->unregisterHook($hook);
			if(!$ok)
			{
				return false;
			}
		}
		return true;
	}

	/**
	* Initialize some general stuff, like smarty functions.
	*/
	private static function initialize_framework()
	{
		global $smarty;
		$smarty->register_function('module_action', array('ModuleBase', 'smartyGetModuleActionUrl'));
		session_start();
	}

	/**
	* Returns an URL for a module action (in smarty)
	*/
	public static function smartyGetModuleActionUrl($params, &$smarty)
	{
		$action = isset($params['action']) ? $params['action'] : 'default';

		if(isset($params['module']))
		{
			$module = $params['module'];
		}
		else
		{
			$m = array();
			if(preg_match('#/+modules/+(\w+)/+views/.*?\.tpl$#', $smarty->template_filepath, $m))
			{
				$module = $m[1];
			}
			else
			{
				$module = false;
			}
		}

		if($module)
		{
			$arguments = array();
			foreach($params as $key => $value)
			{
				if($key != 'module' && $key != 'action')
				{
					$arguments[$key] = $value;
				}
			}
			global $cookie;
			$url=static::getModuleActionUrl($module, $action, $arguments);
		}
		else
		{
			$url="";
		}
		

		return $url;
	}

	public static function getModuleActionUrl($module, $action, $arguments=array())
	{
		$extra_arguments = "";
		foreach($arguments as $key=>$value)
		{
			$extra_arguments .= "&$key=".urlencode($value);
		}
		return $url= "?token=".Tools::getAdminTokenLite('AdminModules', $cookie->id_lang)."&tab=AdminModules&configure=$module&moduleAction=$action$extra_arguments";
	}

	public function getResetUrl()
	{
		return $url= "?token=".Tools::getAdminTokenLite('AdminModules', $cookie->id_lang)."&tab=AdminModules&module_name={$this->name}&reset";
	}

	/*
	* Checks whether we are on version >= 1.5
	*/
	public static function prestaShopIs15()
	{
		return version_compare("1.5", _PS_VERSION_, "<=");
	}

	/**
	* Returns the view path with name $name, either that of the inheriting module if it exists, or that of the ModuleBase if it exists.
	* Returns false if the view does not exist, either in the inheriting module or the BaseModule.
	* Views are searched for in the "views" subfolder of the module.
	*/
	protected function viewPath($name)
	{
		if($specialized_path = $this->specializedViewPath($name))
		{
			return $specialized_path;
		}
		else
		{
			$generic_path = _PS_MODULE_DIR_ . "/modframework/views/$name.tpl";
			if(file_exists($generic_path))
			{
				return $generic_path;
			}
			else return false;
		}
	}

	protected function specializedViewPath($name)
	{
		$specialized_path = _PS_MODULE_DIR_ . "/{$this->name}/views/$name.tpl";
		if(file_exists($specialized_path))
		{
			return $specialized_path;
		}
		else return false;
	}

	/**
	* Renders the view with name $name and returns the corresponding HTML code if the view exists, returns false else.
	*/
	protected function renderView($name)
	{
		if($path = $this->viewPath($name))
		{
			global $smarty;
			return $smarty->fetch($path);
		}
		else
		{
			return false;
		}
	}

	public function prepareHeader()
	{
		global $smarty;
		$smarty->assign('displayName', $this->displayName);
		$smarty->assign('moduleName' , $this->name);
	}

	public function prepareFooter()
	{
		if(isset($this->devmode) && $this->devmode)
		{
			global $smarty;
			$smarty->assign('reseturl', $this->getResetUrl());
			$models  = $this->getModels();
			$model_links = array();
			foreach($models as $model)
			{
				$model_links[$model] = $this->getModuleActionUrl($this->name, lcfirst($model)."List");
			}
			$smarty->assign('devbar_models', $model_links);
			$smarty->assign('devbar', $this->renderView('devbar'));
		}
	}

	public function process()
	{

	}

	/**
	* Stores the key/value pair in the session's flash variable.
	*/
	public function flash($key, $value)
	{
		if(!isset($_SESSION['modframework_flash']))
		{
			$_SESSION['modframework_flash'] = array();
		}
		$_SESSION['modframework_flash'][$key] = $value;
	}

	/**
	* Assigns the flash variable to smarty and clears it.
	*/
	public function assignFlash()
	{
		global $smarty;

		if(!isset($_SESSION['modframework_flash']))
		{
			$_SESSION['modframework_flash'] = array();
		}
		$smarty->assign('flash', $_SESSION['modframework_flash']);

		$_SESSION['modframework_flash'] = array();
	}

	public function assignFromPost($model, &$validation)
	{
		require_once _PS_MODULE_DIR_."/{$this->name}/models/$model.php";
		
		$def    = $model::getObjectDefinition();

		if(isset($_POST[$def['identifier']]))
		{
			$object = new $model($_POST[$def['identifier']]);
		}
		else
		{
			$object = new $model;
		}

		foreach(array_merge(array_keys($object->getFields()), $object->getLanguageFieldsList()) as $name)
		{
			$postname = $model."_".$name;
			if(isset($_POST[$postname]))
			{
				$object->$name = $_POST[$postname];
			}
		}

		//Do not die (first argument) and return the first error if any (second argument).
		$validation = $object->validateFields(false, true);
		//Validate language fields if regular fields are OK.
		if($validation === true)
		{
			$validation = $object->validateFieldsLang(false, true);
		}

		return $object;
	}

	public function assignCrudBoilerPlate()
	{
		global $cookie;
		global $smarty;

		$smarty->assign('languages', Language::getLanguages(false));
		$smarty->assign('id_lang', $cookie->id_lang);
		$smarty->assign('module_name', $this->name);
	}

	public function crudNew($model, $object = null)
	{
		require_once _PS_MODULE_DIR_."/{$this->name}/models/$model.php";

		$this->assignCrudBoilerPlate();

		if($object === null)
		{
			$object     = new $model();
		}

		$fields = $object->getFormType();

		return array('fields' => $fields, 'model' => $model, 'operation' => 'new');
	}

	public function crudEdit($model, $object = null)
	{
		require_once _PS_MODULE_DIR_."/{$this->name}/models/$model.php";

		$this->assignCrudBoilerPlate();

		if($object === null)
		{
			$object = new $model((int)$_GET['object_identifier']);
		}

		$fields = $object->getFormType();

		return array('fields' => $fields, 'model' => $model, 'operation' => 'edit', 'identifier' => $object->identifier, 'id' => $object->id);
	}

	public function crudList($model)
	{
		global $cookie;
		require_once _PS_MODULE_DIR_."/{$this->name}/models/$model.php";

		$objects = $model::findAll();

		$types   = array();
		foreach($objects as $object)
		{
			$types[$object->id] = $object->getListType(array('id_lang' => $cookie->id_lang));
		}

		return array('model' => $model, 'types' => $types, 'id_lang' => $cookie->id_lang, 'module_name' => $this->name);
	}

	public function crudShow($model)
	{
		global $cookie;
		require_once _PS_MODULE_DIR_."/{$this->name}/models/$model.php";

		$object = new $model((int)$_GET['object_identifier']);
		$object->{$object->identifier} = $object->id;

		return array('model' => $model, 'type' => $object->getShowType(array('id_lang' => $cookie->id_lang)), 'module_name' => $this->name);
	}

	public function crudCreate($model)
	{
		$validation = false;
		
		$object = $this->assignFromPost($model, $validation);

		$extra_template_parameters=array();


		if(!Tools::isSubmit('form_listener'))
		{
			if($validation !== true)
			{
				$extra_template_parameters['validation_error'] = $validation;
			}
			else
			{
				if($saved = $object->save())
				{
					$extra_template_parameters['saved'] = true;
				}
				else
				{
					$extra_template_parameters['saved'] = false;
				}
			}
		}

		if((int)$object->id > 0)
		{
			$template_parameters = $this->crudEdit($model, $object);
		}
		else
		{
			$template_parameters = $this->crudNew($model, $object);
		}

		return array_merge($template_parameters, $extra_template_parameters);
	}

	public function crudDelete($model)
	{
		require_once _PS_MODULE_DIR_."/{$this->name}/models/$model.php";
		$object   = new $model((int)Tools::getValue('object_identifier'));
		if($object->delete())
		{

		}
		Tools::redirectAdmin(static::getModuleActionUrl($this->name, lcfirst($model)."List"));
		exit;
	}

	/**
	* Determines whether the Action is a valid CRUD Action
	*/
	public function isCrudAction($action)
	{
		$crud_method = false;
		$model 		 = false;

		$m   = array();
		if(preg_match('/^(create|new|edit|delete|show)(\w+)/', $action, $m))
		{
			$crud_method = $m[1];
			$model       = $m[2];
			$method      = 'crud'.ucfirst($crud_method);
		}
		else if(preg_match('/(\w+)List/', $action, $m))
		{
			$crud_method = 'list';
			$model       = ucfirst($m[1]);
			$method      = 'crudList';
		}

		if($model === false)
		{
			return false;
		}

		if(($crud_method == 'create' or $crud_method == 'delete') and $_SERVER['REQUEST_METHOD'] !== 'POST')
		{
			return false;
		}

		if(in_array($model, $this->getModels()))
		{
			return array('model' => $model, 'method' => $method, 'crud_method' => $crud_method);
		}
		else
		{
			return false;
		}
	}

	/**
	* Determines the Action & View to use from the request parameters
	*/
	public function determineActionAndView()
	{
		if(!($action = Tools::getValue('moduleAction')))
		{
			$action = 'default';
		}

		$method 	= strtolower($_SERVER['REQUEST_METHOD']).ucfirst($action).'Action';
		$view       = false;

		//"Real" route
		if($this->viewPath($action))
		{
			$view = $action;
		}
		//"Virtual" route
		else if($crud = $this->isCrudAction($action))
		{
			if($crud['crud_method'] == 'new' or $crud['crud_method'] == 'edit' or $crud['crud_method'] == 'create')
			{
				$view = 'formNewOrEdit';
			}
			else if($crud['crud_method'] == 'list' or $crud['crud_method'] == 'delete')
			{
				$view = 'crudList';
			}
			else if($crud['crud_method'] == 'show')
			{
				$view = 'crudShow';
			}
		}

		if($view == false)
		{
			return false;
		}

		if(method_exists($this, $method))
		{
			return array('method' => $method, 'view' => $view, 'type' => 'regular');
		}
		else if($crud)
		{
			return array('method' => $crud['method'], 'view' => $view, 'type' => 'crud', 'model' => $crud['model']);
		}
		else
		{
			return false;
		}
	}

	/**
	* We use this function as the entry point of our module since there are no Module Controllers in 1.4
	*/
	public function getContent()
	{
		$route = $this->determineActionAndView();

		if(!$route)
		{
			$route = array('type' => 'regular', 'method' => 'getErrorAction', 'view' => 'error');
			$this->flash('errors', array($this->l("Could not find route!")));
		}
		
		$template_parameters = false;
		if($route['type'] == 'crud')
		{
			$template_parameters = $this->{$route['method']}($route['model']);
		}
		else
		{
			$template_parameters = $this->{$route['method']}();
		}
		
		global $smarty;
		if(is_array($template_parameters))
		{	
			$smarty->assign($template_parameters);
		}

		$this->process();

		$this->assignFlash();

		$this->prepareHeader();
		$header =  $this->renderView('header');

		$body = $this->renderView($route['view']);

		if(isset($this->devmode) && $this->devmode)
		{
			$m = array();
			preg_match('#/modules/(.*)#', $this->viewPath($route['view']), $m);
			$smarty->assign('view_path', $m[1]);
		}

		$this->prepareFooter();
		$footer = $this->renderView('footer');

		$smarty->assign('header', $header);
		$smarty->assign('body'	, $body);

		

		$smarty->assign('footer', $footer);

		$html   = $this->renderView('layout');

		if($html !== false)
		{					
			//PrestaShop 1.5 needs to *return* the html.
			if(static::prestaShopIs15())
			{
				return $html;
			}
			//Whereas PrestaShop 1.4 *can echo* it: it then doesn't display the ugly hooks management tables.
			else
			{
				echo $html;
			}
		}
		

	}


	/**
	* Default route to display errors.
	*/
	public function getErrorAction()
	{
	}

	public function getModels()
	{
		$models = array();
		$dir = _PS_MODULE_DIR_ . "/{$this->name}/models";
		if(is_dir($dir))
		{
			foreach(scandir($dir) as $entry)
			{
				$m = array();
				if(preg_match('/(\w+)\.php$/', $entry, $m))
				{
					$models[] = $m[1];
				}
			}
		}
		return $models;
	}

	public function executeModelsSQL($operation)
	{
		$dir = _PS_MODULE_DIR_ . "/{$this->name}/models";
		if(is_dir($dir))
		{
			foreach(scandir($dir) as $entry)
			{
				$m = array();
				if(preg_match('/(\w+)\.php$/', $entry, $m))
				{
					$className =  $m[1];
					require_once "$dir/$entry";

					$sql = $className::$operation();

					foreach($sql as $statement)
					{
						$ok = Db::getInstance()->execute($statement);
						if(!$ok)
						{
							return false;
						}
					}

				}
			}
		}

		return true;
	}

	public function installModels()
	{
		return $this->executeModelsSQL('up_sql');
	}

	public function unInstallModels()
	{
		return $this->executeModelsSQL('down_sql');
	}

	public function install()
	{
		/**
		* I'm sorry but Late Static Binding is MANDATORY.
		*/
		if(version_compare(phpversion(), "5.3", "<"))
		{
			return false;
		}

		return $this->installModels() && parent::install() && $this->autoRegisterHooks();
	}

	public function uninstall()
	{
		return $this->unInstallModels() && parent::uninstall() && $this->autoUnregisterHooks();
	}

	public function getUrlFromPathRelativeToMe($path, $force_framework_version=false)
	{
		$url  = preg_match('#^HTTPS#', $_SERVER['SERVER_PROTOCOL']) ? "https://" : "http://";
		$url .= $_SERVER['SERVER_NAME'] . __PS_BASE_URI__;
		$url .= 'modules/' . ($force_framework_version ? 'modframework' : $this->name);
		$url .= '/' . $path;
		return $url;
	}

	/**
	* TODO: TEST
	*/
	public function generateJSIncludeCode($script)
	{
		$url = false;
		if(file_exists(_PS_MODULE_DIR_."{$this->name}/js/$script.js"))
		{
			$url = $this->getUrlFromPathRelativeToMe("js/$script.js");
		}
		else if(file_exists(_PS_MODULE_DIR_."modframework/js/$script.js"))
		{
			$url = $this->getUrlFromPathRelativeToMe("js/$script.js", true);
		}
		if($url !== false)
		{
			return "<script type='text/javascript' src='$url'></script>";
		}
		else return false;
	}

	public function hookBackOfficeHeader()
	{
		$code = "";
		/**
		* Only do this when we're on one of our module's pages!
		*/
		if(Tools::getValue('tab') == 'AdminModules' && Tools::getValue('configure') == $this->name)
		{
			if($route = $this->determineActionAndView())
			{
				$action = Tools::getValue('moduleAction');

				$files = array($action, $route['view']);

				if($route['type'] == 'crud')
				{
					$files[] = $route['view'] . $route['model'];
				}

				foreach($files as $file)
				{
					if($js = $this->generateJSIncludeCode($file))
					{
						$code .= "\n".$js;
					}
				}
				
			}
		}

		return $code;
	}

}