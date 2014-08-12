<?php

use Symfony\Component\Validator\Validation;
use Fsb\AppointmentBundle\Entity\AppointmentProject;
use Fsb\AppointmentBundle\Test\Entity\AppointmentDefaultEntityTest;

class AppointmentProjectTest extends AppointmentDefaultEntityTest
{

	public function setUp()
	{

	}

	public function testValidation()
	{
		$appointmentProject = new AppointmentProject();
		
		$this->globalValidation($appointmentProject);
		
		$appointmentProject->setName('name');
		$this->assertEquals(
				'name',
				$appointmentProject->getName(),
				'The name is saved in the appointmentProject'
		);
		
	}
	
}