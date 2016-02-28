<?php

namespace App\Presenters;

use Nette\Application\UI;


class ChartsPresenter extends BasePresenter
{

	/**
	 * from, to
	 * DateTime - always set by renderDefault
	 */
	private $from, $to;

	protected function startup()
	{
		parent::startup();

		if (!$this->user->isLoggedIn()) {
			$this->redirect('Sign:in');
		}

		\RadekDostal\NetteComponents\DateTimePicker\DatePicker::register();
	}

	protected function createComponentDateForm()
	{
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

	public function dateFormSucceeded(UI\Form $form, $values)
	{
		// set time to midnight
		$from = $values->from->setTime(0, 0, 0)->getTimestamp();
		$to = $values->to->setTime(23, 59, 59)->getTimestamp();
		$this->redirect('Charts:', array('from' => $from, 'to' => $to));
	}

	public function actionDefault($from, $to)
	{
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

	public function actionGet1Y()
	{
		// Initialize DateTime objects
		$this->from = new \Nette\Utils\DateTime();
		$this->to = new \Nette\Utils\DateTime();
		$this->from->sub(new \DateInterval('P1Y')); // Today and 2 days in the past
		$this->from->setTime(0, 0, 0);

		require_once (__DIR__ . '/../../../jpgraph/jpgraph.php');
		require_once (__DIR__ . '/../../../jpgraph/jpgraph_line.php');
		require_once(__DIR__ . '/../../../jpgraph/jpgraph_date.php');


		// Setup the graph
		$graph = new \Graph(1900, 800);
		$graph->SetScale('datlin');

		$graph->yaxis->HideTicks(false, false);
		$graph->xgrid->Show();
		$graph->xgrid->SetColor('#E3E3E3');


		$graph->xaxis->SetLabelAngle(90); // Set the angle for the labels to 90 degrees
		$graph->xaxis->scale->SetDateFormat('j. n. Y - H:i'); // The automatic format string for dates can be overridden
		$graph->xaxis->scale->SetTimeAlign(MINADJ_5); // Adjust the start/end to a specific alignment
		// Prepare data
		$devices = $this->database->table('user')->get($this->user->getId())->related('device')->order('order');

		foreach ($devices as $device) {
			$datay1 = array();
			$datax1 = array();
			foreach ($device->related('measurement')->where(array('ts >=' => $this->from->getTimestamp(), 'ts <=' => $this->to->getTimestamp())) as $value) {
				$datax1[] = $value->ts;
				$datay1[] = $value->t1;
			}

			// Create line
			$p1 = new \LinePlot($datay1, $datax1);
			$graph->Add($p1);
			$color = $device->color ? $device->color : '#000000';
			$p1->SetColor($color);
			$p1->SetLegend($device->name);
		}


		$graph->legend->SetFrameWeight(1); // border around legend

		$graph->Stroke(); // generate graph
	}

}
