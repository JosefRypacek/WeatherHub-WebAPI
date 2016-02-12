<?php

namespace App\Presenters;

use Nette\Application\UI;

class HomepagePresenter extends BasePresenter {

	protected function startup() {
		parent::startup();

		if (!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in');
		}

		\RadekDostal\NetteComponents\DateTimePicker\DatePicker::register();
	}

	protected function createComponentDateForm() {
		$form = new UI\Form;

		$form->addDatePicker('from', 'Od (00:00:00):', 10)
				//->setFormat('m/d/Y') // for datepicker option dateFormat: 'mm/dd/yy'
				->setAttribute('size', 10)
				->setReadOnly(FALSE)
				->setRequired();

		$form->addDatePicker('to', 'Do (23:59:59):', 10)
				//->setFormat('m/d/Y') // for datepicker option dateFormat: 'mm/dd/yy'
				->setAttribute('size', 10)
				->setReadOnly(FALSE)
				->setRequired();

		if ($this->getParameter('from') !== null && $this->getParameter('to') !== null) {
			$from = new \Nette\Utils\DateTime();
			$to = new \Nette\Utils\DateTime();
			$from->setTimestamp($this->getParameter('from'));
			$to->setTimestamp($this->getParameter('to'));
			// set default value
			$form->setDefaults(array(
				'from' => $from,
				'to' => $to
			));
		}

		$form->addSubmit('send', 'Nastavit');

		$form->onSuccess[] = array($this, 'dateFormSucceeded');
		return $form;
	}

	public function dateFormSucceeded(UI\Form $form, $values) {
		// set time to midnight
		$from = $values->from->setTime(0, 0, 0)->getTimestamp();
		$to = $values->to->setTime(23, 59, 59)->getTimestamp();
		$this->redirect('Homepage:', array('from' => $from, 'to' => $to));
	}

	public function renderDefault($from, $to) {
		if (!isset($from) || !isset($to)) {
			// all time - can be slow...
			$from = 0;
			$to = time();
		}
		// Send data to template
		$this->template->devices = $this->database->table('user')->get($this->user->getId())->related('device')->order('order');
		$this->template->from = $from;
		$this->template->to = $to;
	}

}
