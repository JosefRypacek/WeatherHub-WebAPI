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
		$grid = new \Ublaboo\DataGrid\DataGrid($this, $name);

                $grid->setPagination(false);

                // probably had filters from grido in session... this is temporary...
                $grid->setStrictSessionFilterValues(false);

                $grid->setPrimaryKey('int_id_grido');
                $grid->setDataSource($this->database->table('device')->where(['user_id' => $this->user->getId()]));
                $grid->setDefaultSort(['order' => 'ASC']);

		$grid->addColumnText('name', 'Název')
                    ->setEditableCallback(function($id, $value): void {
                        $this->database->table('device')->where(['int_id_grido' => $id])->update(['name' => $value]);
                    })
                    ->setEditableInputType('text', ['class' => 'form-control']);

		$grid->addColumnNumber('order', 'Pořadí')
                    ->setEditableCallback(function($id, $value): void {
                        $this->database->table('device')->where(['int_id_grido' => $id])->update(['order' => $value]);
                    })
                    ->setEditableInputType('text', ['class' => 'form-control']);

		$grid->addColumnText('color', 'Barva')
                    ->setEditableCallback(function($id, $value): void {
                        $this->database->table('device')->where(['int_id_grido' => $id])->update(['color' => $value]);
                    })
                    ->setEditableInputType('text', ['class' => 'form-control']);
	}

	protected function createComponentUserGrid($name)
	{
		$grid = new \Ublaboo\DataGrid\DataGrid($this, $name);

                $grid->setPagination(false);

                // probably had filters from grido in session... this is temporary...
                $grid->setStrictSessionFilterValues(false);
		
                $grid->setDataSource($this->database->table('user')->where(['id' => $this->user->getId()]));


		$grid->addColumnNumber('updatenames', 'Synchronizovat jména? (0/1)')
                    ->setEditableCallback(function($id, $value): void {
                        $this->database->table('user')->where(['id' => $id])->update(['updatenames' => $value]);
                    })
                    ->setEditableInputType('text', ['class' => 'form-control']);

		$grid->addColumnText('devicetoken', 'devicetoken')
                    ->setEditableCallback(function($id, $value): void {
                        $this->database->table('user')->where(['id' => $id])->update(['devicetoken' => $value]);
                    })
                    ->setEditableInputType('text', ['class' => 'form-control']);

		$grid->addColumnNumber('vendorid', 'vendorid')
                    ->setEditableCallback(function($id, $value): void {
                        $this->database->table('user')->where(['id' => $id])->update(['vendorid' => $value]);
                    })
                    ->setEditableInputType('text', ['class' => 'form-control']);

		$grid->addColumnText('phoneid', 'phoneid')
                    ->setEditableCallback(function($id, $value): void {
                        $this->database->table('user')->where(['id' => $id])->update(['phoneid' => $value]);
                    })
                    ->setEditableInputType('text', ['class' => 'form-control']);
	}

}
