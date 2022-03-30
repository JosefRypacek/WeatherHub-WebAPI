<?php

namespace App\Presenters;

use Nette;


class BaseBasePresenter extends Nette\Application\UI\Presenter
{

	/** @var Nette\Database\Context @inject */
	public $database;
	
	public $deviceTypeList = [];


	protected function startup()
	{
		parent::startup();

		$this->deviceTypeList['t1'] = [1, 2, 3, 4, 5, 6, 7, 8, 9];
		$this->deviceTypeList['t2'] = [1, 4, 5, 6, 7, 9];
		$this->deviceTypeList['h'] = [3, 4, 5, 6, 7, 9];
		$this->deviceTypeList['r'] = [8];
		$this->deviceTypeList['wsgd'] = [11];
	}

}
