<?php

namespace App\Presenters;

use Nette\Application\UI;

class ChartsPresenter extends BasePresenter {

	/**
	 * from, to
	 * DateTime - always set by renderDefault
	 */
	private $from, $to;

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

		// set default values
		$form->setDefaults(array(
			'from' => $this->from,
			'to' => $this->to
		));


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

	public function actionDefault($from, $to) {
		// Initialize DateTime objects
		$this->from = new \Nette\Utils\DateTime();
		$this->to = new \Nette\Utils\DateTime();

		// Set default / requested dates
		if (!isset($from) || !isset($to)) {
			// Default - show 3 last days (!= 72 hours) 
			$this->from->sub(new \DateInterval('P2D')); // Today and 2 days in the past
			$this->from->setTime(0, 0, 0);
		} else {
			$this->from->setTimestamp($from);
			$this->to->setTimestamp($to);
		}
		
		// Send data to template
		$this->template->devices = $this->database->table('user')->get($this->user->getId())->related('device')->order('order');
		$this->template->from = $this->from->getTimestamp();
		$this->template->to = $this->to->getTimestamp();
	}

}
