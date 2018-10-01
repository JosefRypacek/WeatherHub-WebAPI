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

	protected function createComponentDeviceGrid($name)
	{
		$grid = new \Grido\Grid();
		$this->addComponent($grid, $name);
		
		$grid->setFilterRenderType(\Grido\Components\Filters\Filter::RENDER_INNER);
		$grid->setModel($this->database->table('device')->where(['user_id' => $this->user->getId()]));

		$grid->setPrimaryKey('int_id_grido');

		$grid->addColumnText('name', 'Název')
				->setEditable();
		$grid->addColumnNumber('order', 'Pořadí')
				->setEditable();
		$grid->addColumnText('color', 'Barva')
				->setEditable();
	}

	protected function createComponentUserGrid($name)
	{
		$grid = new \Grido\Grid();
		$this->addComponent($grid, $name);
		
		$grid->setFilterRenderType(\Grido\Components\Filters\Filter::RENDER_INNER);
		$grid->setModel($this->database->table('user')->where(['id' => $this->user->getId()]));


		$grid->addColumnNumber('updatenames', 'Synchronizovat jména?')
				->setReplacement([0 => 'NE', 1 => 'ANO'])
				->setEditable();

//				Tried to setup constraints - check is working, update is NOT working :(
//
//				->setEditableCallback(function($id, $new, $old, $column) {
//					return (intval($new) === 0 || intval($new) === 1);
//				})


		$grid->addColumnText('devicetoken', 'devicetoken')
				->setCustomRender(function($item) {
					return \Nette\Utils\Strings::truncate($item->devicetoken, 30);
				})
				->setEditable();
		$grid->addColumnNumber('vendorid', 'vendorid')
				->setEditable();
		$grid->addColumnText('phoneid', 'phoneid')
				->setEditable();
	}

}
