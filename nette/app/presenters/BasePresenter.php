<?php

namespace App\Presenters;

use Nette;


class BasePresenter extends BaseBasePresenter
{

	protected function startup()
	{
		parent::startup();

		if (!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in');
		}
	}

}
