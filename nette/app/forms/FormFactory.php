<?php

namespace App\Forms;

use Nette;
use Nette\Application\UI\Form;


class FormFactory
{

	/**
	 * @return Form
	 */
	public function create()
	{
		return new Form;
	}

}
