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

		$fields = $object->prepareFormType('new');

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

		$fields = $object->prepareFormType('edit');

		return array('fields' => $fields, 'model' => $model, 'operation' => 'edit', 'object' => $object);
	}

	public function crudList($model)
	{
		global $cookie;
		require_once _PS_MODULE_DIR_."/{$this->name}/models/$model.php";

		$objects = $model::findAll();
		$type    = $model::prepareListType();

		return array('model' => $model, 'objects' => $objects, 'type' => $type, 'id_lang' => $cookie->id_lang, 'module_name' => $this->name);
	}

	public function crudShow($model)
	{
		global $cookie;
		require_once _PS_MODULE_DIR_."/{$this->name}/models/$model.php";

		$object = new $model((int)$_GET['object_identifier'], $cookie->id_lang);
		$object->{$object->identifier} = $object->id;

		return array('model' => $model, 'object' => $object, 'type' => $object->prepareShowType(), 'id_lang' => $cookie->id_lang, 'module_name' => $this->name);
	}

	/**
	* Handles the request.
	*/
	public function answerWithAction($action)
	{
		$http_method = strtolower($_SERVER['REQUEST_METHOD']);
		
		$method = $http_method.ucfirst($action).'Action';

		$template_parameters = false;

		if(method_exists($this, $method))
		{
			$template_parameters = $this->$method();
			$view = $action;
		}
		else
		{
			/**
			* Index
			*/
			$m = array();
			if(preg_match('/^(\w*?)List$/', $action, $m))
			{
				$model = ucfirst($m[1]);
				$template_parameters = $this->crudList($model);
				if($this->specializedViewPath($action) !== false)
				{
					$view = $action;
				}
				else
				{
					$view = 'crudList';
				}
			}
			/**
			* Show
			*/
			else if(preg_match('/^show(\w+)$/', $action, $m) && $http_method=='get')
			{
				$model = $m[1];
				$template_parameters = $this->crudShow($model);
				if($this->specializedViewPath($action) !== false)
				{
					$view = $action;
				}
				else
				{
					$view = 'crudShow';
				}
			}
			/**
			* New
			*/
			else if(preg_match('/^new(\w+)$/', $action, $m) && $http_method=='get')
			{
				$model = $m[1];
				$template_parameters = $this->crudNew($model);
				if($this->specializedViewPath($action) !== false)
				{
					$view = $action;
				}
				else
				{
					$view = 'formNewOrEdit';
				}
			}
			/**
			* Edit
			*/
			else if(preg_match('/^edit(\w+)$/', $action, $m) && $http_method=='get')
			{
				$model = $m[1];
				$template_parameters = $this->crudEdit($model);
				if($this->specializedViewPath($action) !== false)
				{
					$view = $action;
				}
				else
				{
					$view = 'formNewOrEdit';
				}
			}
			/**
			* Create
			*/
			else if(preg_match('/^create(\w+)$/', $action, $m) && $http_method=='post')
			{
				$model 		= $m[1];
				$validation = false;
				
				$view = 'formNewOrEdit';

				$object = $this->assignFromPost($model, $validation);

				$extra_template_parameters=array();

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

				if((int)$object->id > 0)
				{
					$template_parameters = $this->crudEdit($model, $object);
				}
				else
				{
					$template_parameters = $this->crudNew($model, $object);
				}

				$template_parameters = array_merge($template_parameters, $extra_template_parameters);
			}
			/**
			* Delete
			*/
			else if(preg_match('/^delete(\w+)$/', $action, $m) && $http_method == 'post')
			{
				$model = $m[1];
				require_once _PS_MODULE_DIR_."/{$this->name}/models/$model.php";
				$object   = new $model((int)Tools::getValue('object_identifier'));
				if($object->delete())
				{

				}
				Tools::redirectAdmin(static::getModuleActionUrl($this->name, lcfirst($model)."List"));
				exit;
			}
		}

		if($template_parameters !== false)
		{	
			//If the action returns an array, we want to display the corresponding view with the array's parameters assigned.
			if(is_array($template_parameters) or ($template_parameters === null))
			{

				$this->assignFlash();

				global $smarty;

				if(is_array($template_parameters))
				{	
					$smarty->assign($template_parameters);
				}

				$this->prepareHeader();
				$header =  $this->renderView('header');

				$this->process();

				$body = $this->renderView($view);

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
					//Whereas PrestaShop 1.4 *echo's* it.
					else
					{
						echo $html;
					}
				}
			}
		}
		else
		{
			$this->flash('errors', array($this->l("Could not find route named '$action'!")));
			Tools::redirectAdmin(static::getModuleActionUrl($this->name, 'error'));
		}
	}

	/**
	* We use this function as the entry point of our module since there are no Module Controllers in 1.4
	*/
	public function getContent()
	{
		if($action = Tools::getValue("moduleAction"))
		{
			$this->answerWithAction($action);
		}
		else
		{
			if($this->specializedViewPath('default'))
			{
				return $this->answerWithAction('default');
			}
			else
			{
				$html = $this->l("<p>This module has no specific configuration options.</p>");
				if(static::prestaShopIs15())
				{
					return $html;
				}
				//Whereas PrestaShop 1.4 *echo's* it.
				else
				{
					echo $html;
				}
			}
		}
	}


	/**
	* Default route to display errors.
	*/
	public function getErrorAction()
	{
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
		return $this->installModels() && parent::install();
	}

	public function uninstall()
	{
		return $this->unInstallModels() && parent::uninstall();
	}

}