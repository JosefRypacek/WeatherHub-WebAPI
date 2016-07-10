<?php

namespace App\Presenters;

use Nette;


class BaseBasePresenter extends Nette\Application\UI\Presenter
{

	/** @var Nette\Database\Context @inject */
	public $database;

}
