<?php

namespace App\Presenters;

use Nette;

/**
 * Base presenter for all application presenters.
 */
abstract class BasePresenter extends Nette\Application\UI\Presenter {

	/** @var Nette\Database\Context */
	protected $database;

	public function __construct(Nette\Database\Context $database) {
		$this->database = $database;
	}

}
