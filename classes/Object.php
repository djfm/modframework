<?php

class Object extends ObjectModel
{
	protected static $fields_for_getfields = array();

	public $identifier;

	public static function getObjectDefinition()
	{
		if(static::$object_definition === false)
		{
			static::$object_definition = array();

			if(!isset(static::$description['identifier']))
			{
				static::$description['identifier'] = 'id_'.static::$description['table'];
			}

			static::$object_definition['table'] 		= static::$description['table'];
			static::$object_definition['lang']			= false;
			static::$object_definition['identifier']	= static::$description['identifier'];
			static::$object_definition['fields']		= array();
			static::$object_definition['autodate']	 	= isset(static::$description['autodate']) && static::$description['autodate'];

			if(static::$object_definition['autodate'])
			{
				static::$description['fields']['date_add'] = 'datetime ?';
				static::$description['fields']['date_upd'] = 'datetime ?';
			}

			$exp = '/^\s*([a-z]+)(?:\s*\(\s*(\d+)\s*\))?\s*(lang)?\s*(\?)?\s*(\w+)?\s*$/';

			foreach(static::$description['fields'] as $name => $def)
			{
				$m = array();

				if(preg_match($exp, $def, $m))
				{
					$type 		= strtolower($m[1]);
					$size 		= isset($m[2]) ? $m[2] : false;
					$lang 		= isset($m[3]) && ($m[3] == 'lang');
					$optional 	= isset($m[4]) && ($m[4] == '?');
					$validator  = isset($m[5]) ? $m[5] : false;

					if($validator === false)
					{
						if($type === 'int')
						{
							$validator = 'isInt';
						}
						else if($type == 'datetime')
						{
							$validator = 'isDateFormat';
						}
						else if($type == 'date')
						{
							$validator = 'isDate';
						}
					}

					static::$object_definition['fields'][$name] = array(
						'type' 		=> $type,
						'size' 		=> $size,
						'lang' 		=> $lang,
						'required' 	=> !$optional,
						'validator' => $validator
					);

					if($lang)
					{
						static::$object_definition['lang'] = true;
					}

					if(!$lang)
					{
						static::$fields_for_getfields[] = $name;
					}

				}
			}

		}

		return static::$object_definition;
	}

	public static function fieldToSQL($field, $def)
	{
		$sql_type = strtoupper($def['type']);

		if($sql_type == 'STRING')
		{
			$sql_type = "VARCHAR ({$def['size']})";
		}
		$sql = $field." ".$sql_type;

		if($def['required'])
		{
			$sql .= " NOT NULL";
		}

		return $sql;
	}

	public static function fieldsToSQL($fields)
	{
		$sql   = "";
		$first = true;
		foreach($fields as $field => $def)
		{
			if($first)
			{
				$first = false;
			}
			else
			{
				$sql  .= ", ";
			}

			$sql .= static::fieldToSQL($field, $def);
		}
		return $sql;
	}

	public static function up_sql()
	{
		$definition = static::getObjectDefinition();
		$statements = array();

		$fields 	 = array();
		$fields_lang = array();

		foreach($definition['fields'] as $name => $def)
		{
			if($def['lang'])
			{
				$fields_lang[$name] = $def;
			}
			else
			{
				$fields[$name] = $def;
			}
		}

		$iddef = $definition['identifier']." INT NOT NULL AUTO_INCREMENT PRIMARY KEY";

		$statements[] = "CREATE TABLE IF NOT EXISTS "._DB_PREFIX_.$definition['table']." ($iddef, ".static::fieldsToSQL($fields).");";

		if(!empty($fields_lang))
		{
			$id           = $definition['identifier']." INT NOT NULL";
			$statements[] =  "CREATE TABLE IF NOT EXISTS "._DB_PREFIX_.$definition['table']."_lang ($id, id_lang INT NOT NULL, "
							.static::fieldsToSQL($fields_lang)
							.", PRIMARY KEY(".$definition['identifier'].", id_lang));";
		}

		echo "<pre>".print_r($statements,1)."</pre>";

		return $statements;
	}

	public static function down_sql()
	{
		$definition = static::getObjectDefinition();

		$lang = false;
		foreach($definition['fields'] as $name => $def)
		{
			if($def['lang'])
			{
				$lang = true;
				break;
			}
		}

		$statements = array();

		$statements[] = "DROP TABLE IF EXISTS "._DB_PREFIX_.$definition['table'];

		if($lang)
		{
			$statements[] = "DROP TABLE IF EXISTS "._DB_PREFIX_.$definition['table']."_lang";
		}

		return $statements;
	}

	public function __construct($id = NULL, $id_lang = NULL)
	{
		$definition = static::getObjectDefinition();
		
		if(!isset($this->fieldsSize))
		{
			$this->fieldsSize = array();
		}

		if(!isset($this->fieldsRequiredLang))
		{
			$this->fieldsRequiredLang = array();
		}

		if(!isset($this->fieldsRequired))
		{
			$this->fieldsRequired = array();
		}

		if(!isset($this->fieldsValidateLang))
		{
			$this->fieldsValidateLang = array();
		}

		if(!isset($this->fieldsValidate))
		{
			$this->fieldsValidate = array();
		}

		if(!isset($this->identifier))
		{
			$this->identifier = $definition['identifier'];
		}

		$this->table = $definition['table'];

		foreach($definition['fields'] as $name => $def)
		{
			if($def['size'])
			{
				$this->fieldsSize[$name] = $def['size'];
			}

			if($def['required'])
			{
				if($def['lang'])
				{
					$this->fieldsRequiredLang[] = $name;
				}
				else
				{
					$this->fieldsRequired[] 	= $name;
				}
			}

			if($def['lang'])
			{
				$this->fieldsValidateLang[$name] = $def['validator'] ? $def['validator'] : 'isAnything';
			}
			else
			{
				$this->fieldsValidate[$name] 	 =  $def['validator'] ? $def['validator'] : 'isAnything';
			}

			if(!isset($this->$name))$this->$name = null;
		}		

		parent::__construct($id, $id_lang);
	}

	public static function protect($field, $value)
	{
		$definition = static::getObjectDefinition();
		$type  		= $definition['fields'][$field]['type'];

		if($type == 'int')
		{
			$value = (int)$value;
		}
		else if($type == 'float')
		{
			$value = (float)$value;
		}
		else if($type == 'double')
		{
			$value = (double)$value;
		}
		else if($type == 'string' or $type == 'text')
		{
			$value = pSQL($value);
		}
		else if($type == 'datetime')
		{
			$value = date_format(new DateTime($value), 'Y-m-d H:i:s');
		}
		else if($type == 'date')
		{
			$value = date_format(new DateTime($value), 'Y-m-d');
		}
		else
		{
			$value = null;
		}

		return $value;
	}

	public function getFields()
	{
		$definition = static::getObjectDefinition();
		$fields = array();
		foreach(static::$fields_for_getfields as $field)
		{
			$fields[$field] = static::protect($field, $this->$field);
		}
		return $fields;
	}

	public function getLanguageFieldsList()
	{
		return array_keys($this->fieldsValidateLang);
	}

	public function getTranslationsFieldsChild()
	{
		parent::validateFieldsLang();
		if(empty($this->fieldsValidateLang))return array();

		return parent::getTranslationsFields(array_keys($this->fieldsValidateLang));
	}

	public static function titleize($name)
	{
		return implode(' ', array_map('ucfirst', explode('_', $name)));
	}

	public function makeType($fieldList, $kind, $options = array())
	{
		$def = static::getObjectDefinition();
		$type = array();

		foreach($fieldList as $maybe_field => $maybe_spec)
		{
			if(is_array($maybe_spec))
			{
				$field = $maybe_field;
				$spec  = $maybe_spec;
			}
			else
			{
				$field = $maybe_spec;
				$spec  = $def['fields'][$field];
			}
			
			$typeoverride = lcfirst($kind).'TypeOverride';
			if(method_exists($this, $typeoverride) && is_array($override = $this->$typeoverride($field)))
			{
				$spec = array_merge($spec, $override);
			}

			$spec['id'] 	= ($field == $def['identifier']);

			if($spec['lang'] && isset($options['id_lang']) && $options['id_lang'])
			{
				$spec['value']	= $this->{$field}[$options['id_lang']];
			}
			else if(!$spec['id'])
			{
				$spec['value']	= $this->$field;
			}
			else
			{
				$spec['value'] = $this->id;
				$spec['title'] = 'ID';
			}

			if(!isset($spec['title']))
			{
				$spec['title'] = static::titleize($field);
			}

			$type[$field] = $spec;
		}


		return $type;
	}

	public function getListFields()
	{
		$def = static::getObjectDefinition();
		return array_merge(array($def['identifier']),array_keys($def['fields']));
	}

	public function getListType($options = array())
	{
		return $this->makeType($this->getListFields(), 'List', $options);
	}

	public function getFormFields()
	{
		$def 	= static::getObjectDefinition();
		return array_diff(array_keys($def['fields']), array('date_add', 'date_upd'));
	}

	public function getFormType($options = array())
	{
		return $this->makeType($this->getFormFields(), 'Form', $options);
	}

	public function getShowFields()
	{
		$def = static::getObjectDefinition();
		return array_keys($def['fields']);
	}

	public function getShowType($options = array())
	{
		return $this->makeType($this->getShowFields(), 'Show', $options);
	}

	public static function findAll($conditions = array(), $pagination = array())
	{
		$def = static::getObjectDefinition();

		$sql = "SELECT DISTINCT t." . $def['identifier'] . " FROM " . _DB_PREFIX_.$def['table'] . " t";
		if($def['lang'])
		{
			$sql .= " INNER JOIN " . _DB_PREFIX_.$def['table'] . "_lang tl ON tl." . $def['identifier'] . " = t." . $def['identifier'];
		}

		if(!empty($conditions))
		{
			$wheres = array();
			foreach($condition as $field => $value)
			{
				$t = $def['fields'][$field]['lang'] ? 'tl' : 't';
				if(is_array($value))
				{
					$in = array();
					foreach($value as $val)
					{
						$in[] = static::protect($field, $val);
					}
					$wheres[] = "$t.$field IN (" . implode(', ', $in) . ")";
				}
				else
				{
					$wheres[] = "$t.$field = " . static::protect($field, $value);
				}
			}
			$sql .= " WHERE " . implode(' AND ', $wheres);
		}

		if(isset($pagination['limit']))
		{
			$sql .= " LIMIT " . (int)$pagination['limit'];
		}
		if(isset($pagination['offset']))
		{
			$sql .= " OFFSET " . (int)$pagination['offset'];
		}

		$objects = array();

		foreach(Db::getInstance()->ExecuteS($sql) as $row)
		{
			$class     = get_called_class();
			$objects[] = new $class($row[$def['identifier']]);
		}

		return $objects;
	}

	public function language_field($name, $id_lang)
	{
		return $this->{$name}[$id_lang];
	}

	public function l($str)
	{
		return $str;
	}

}