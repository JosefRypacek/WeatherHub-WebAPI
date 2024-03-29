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

		$diff = $this->from->diff($this->to);
		$diffDays = $diff->format('%a');
		if ($diffDays > 6 * 4 * 7) {
//			$eachNth = 32;
			$groupMinutes = 120;
		} else if ($diffDays > 3 * 4 * 7) {
//			$eachNth = 8;
			$groupMinutes = 60;
		} else if ($diffDays > 4 * 7) {
//			$eachNth = 4;
			$groupMinutes = 40;
		} elseif ($diffDays > 7) {
//			$eachNth = 2;
			$groupMinutes = 20;
		} else {
//			$eachNth = 1;
			$groupMinutes = 5;
		}

		// Send data to template
//		$this->template->eachNth = $eachNth;
		$this->template->groupMinutes = $groupMinutes;
		$this->template->db = $this->database; // This is not best solution, but... Easy :)
		$this->template->devices = $this->database->table('user')->get($this->user->getId())->related('device')->order('order');
		$this->template->from = $this->from->getTimestamp();
		$this->template->to = $this->to->getTimestamp();
		$this->template->deviceTypeList = $this->deviceTypeList;
	}

	public function actionGet1Y()
	{
		// Initialize DateTime objects
		$this->from = new \Nette\Utils\DateTime();
		$this->to = new \Nette\Utils\DateTime();
		$this->from->sub(new \DateInterval('P1Y')); // Today and 2 days in the past
		$this->from->setTime(0, 0, 0);

		// Setup the graph
                \mitoteam\jpgraph\MtJpGraph::load(['line', 'date']);
		$graph = new \Graph(1900, 800);
		$graph->SetScale('datlin');
		
		$graph->xaxis->SetPos("min");
		$graph->yaxis->HideTicks(false, false);
		$graph->xgrid->Show();
		$graph->xgrid->SetColor('#E3E3E3');


		$graph->xaxis->SetLabelAngle(90); // Set the angle for the labels to 90 degrees
		$graph->xaxis->scale->SetDateFormat('j. n. Y');
		$graph->xaxis->scale->SetDateAlign(DAYADJ_1); // Adjust the start/end to a specific alignment

		// Prepare data
		$devices = $this->database->table('user')->get($this->user->getId())->related('device')->order('order');

		foreach ($devices as $device) {
		    if (in_array($device->type, $this->deviceTypeList['t1'])) {
			$datay1 = array();
			$datax1 = array();
			// nette related is (or may be) memory killer!
			// can use pure query - is 5x better (have 5 devices - is there any corelation?)

                        // these queries (also in default.lette) are modified to work with mode only_full_group_by ("device_id, " added into group() even not needed
                        // agregating data (3600s) to fit into memory limit
			$related = $device->related('measurement')->select('ROUND(AVG(measurement.ts),0) AS ts, ROUND(AVG(t1),1) AS t1')->where(array('ts >=' => $this->from->getTimestamp(), 'ts <=' => $this->to->getTimestamp()))->group('device_id, CONCAT(device_id, \'_\', FLOOR(ts/(3600)))');
			foreach ($related as $value) {
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
		}


		$graph->legend->SetFrameWeight(1); // border around legend
		$graph->legend->Pos(0.5,0.01, 'center', 'top');

		$graph->Stroke(); // generate graph
		$this->terminate();
	}

}
