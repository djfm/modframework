<?php

require_once __DIR__ . '/classes/ModuleBase.php';

class ModFramework extends ModuleBase
{
	public function __construct()
	{
		$this->name 		= 'modframework';
		$this->tab  		= 'administration';
		$this->version 		= '0.5';
		$this->author  		= 'djfm';
		$this->displayName 	= $this->l('ModFramework');
		$this->description 	= $this->l('A Framework to Facilitate Robust PrestaShop Module Development, doesn\'t do Anything by Itself');

		parent::__construct();
	}

}