<?php

namespace App\Presenters;

use Nette;
use \App\Model\UserManager;


class SignPresenter extends BaseBasePresenter
{
	
	/** @var UserManager */
	private $userManager;

	/**
	 * @param UserManager $userManager
	 */
	public function __construct(UserManager $userManager)
	{
	    parent::__construct();
	    $this->userManager = $userManager;
	}

	protected function createComponentSignInForm()
	{
		$form = new Nette\Application\UI\Form;
		$form->addText('username', 'Uživatelské jméno:')
				->setRequired('Prosím vyplňte své uživatelské jméno.');

		$form->addPassword('password', 'Heslo:')
				->setRequired('Prosím vyplňte své heslo.');

		$form->addCheckbox('remember', 'Zůstat přihlášen');

		$form->addSubmit('send', 'Přihlásit');

		$form->onSuccess[] = [$this, 'signInFormSucceeded'];
		return $form;
	}

	public function signInFormSucceeded($form)
	{
		$values = $form->values;

		if ($values->remember) {
			$this->user->setExpiration('14 days');
		} else {
			$this->user->setExpiration('20 minutes');
		}

		try {
			$this->getUser()->login($values->username, $values->password);
			$this->redirect('Charts:');
		} catch (Nette\Security\AuthenticationException $e) {
			$form->addError('Nesprávné přihlašovací jméno nebo heslo.');
		} catch (\App\Model\LoginProtectionException $e) {
			$form->addError($e->getMessage());
		}
	}

	public function actionIn()
	{
		if ($this->user->isLoggedIn()) {
			$this->flashMessage('Už jsi přihlášen!');
			$this->redirect('Charts:');
		}
	}

	public function actionOut()
	{
		$this->user->logout();
		$this->flashMessage('Byl jsi odhlášen!');
		$this->redirect('Charts:');
	}

	protected function createComponentSignChangeForm()
	{
		$form = new Nette\Application\UI\Form;
		$form->addText('username', 'Uživatelské jméno:')
				->setDisabled()
				->setDefaultValue($this->user->getIdentity()->username);

		$form->addPassword('password', 'Heslo:')
				->setRequired('Prosím vyplňte své heslo.');

		$form->addSubmit('send', 'Změnit');

		$form->onSuccess[] = [$this, 'signChangeFormSucceeded'];
		$form->addProtection();
		return $form;
	}

	public function signChangeFormSucceeded($form)
	{
		$values = $form->values;

		$this->userManager->setPassword($this->user->getId(), $values->password);
		$this->flashMessage('Heslo bylo změněno');
		$this->redirect('Charts:');
	}

	public function actionChange()
	{
		if (!$this->user->isLoggedIn()) {
			$this->flashMessage('Nejsi přihlášen!');
			$this->redirect('Charts:');
		}
	}

}
