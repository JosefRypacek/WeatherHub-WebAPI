<?php

namespace App\Presenters;

use Nette\Application\UI;


class SettingsPresenter extends BasePresenter
{

	protected function startup()
	{
		parent::startup();

		if (!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in');
		}
	}

	protected function createComponentGrid($name)
	{
		$grid = new \Grido\Grid($this, $name);
		$grid->setFilterRenderType(\Grido\Components\Filters\Filter::RENDER_INNER);
		$grid->setModel($this->database->table('device')->where(['user_id' => $this->user->getId()]));

		$grid->setPrimaryKey('int_id_grido');

		$grid->addColumnText('name', 'Název');
		$grid->addColumnNumber('order', 'Pořadí')
				->setEditable();
		$grid->addColumnText('color', 'Barva')
				->setEditable();
	}

}
