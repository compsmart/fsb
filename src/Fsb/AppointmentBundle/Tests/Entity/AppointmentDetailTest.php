<?php

use Symfony\Component\Validator\Validation;
use Fsb\AppointmentBundle\Entity\Appointment;
use Fsb\AppointmentBundle\Entity\AppointmentDetail;
use Fsb\AppointmentBundle\Entity\AppointmentProject;
use Fsb\AppointmentBundle\Entity\AppointmentOutcome;
use Fsb\AppointmentBundle\Entity\Address;
use Fsb\AppointmentBundle\Tests\Entity\AppointmentDefaultEntityTest;

class AppointmentTest extends AppointmentDefaultEntityTest
{
	private $project;
	private $outcome;
	private $address;
		
	public function setUp()
	{
		$project = new AppointmentProject();
		$project->setName('project');
		$this->project = $project;
		
		$outcome = new AppointmentOutcome();
		$outcome->setName('outcome');
		$this->outcome = $outcome;
		
		$address = new Address();
		$address->setPostcode('postcode');
		$this->address = $address;
		
	}
	
	public function testValidation()
	{
		$appointmentDetail = new AppointmentDetail();
		
		$this->globalValidation($appointmentDetail);
		
		$appointmentDetail->setProject($this->project);
		$this->assertEquals(
				'project',
				$appointmentDetail->getProject()->getName(),
				'The project is saved in the appointmentDetail'
		);
		
		$appointmentDetail->setOutcome($this->outcome);
		$this->assertEquals(
				'outcome',
				$appointmentDetail->getOutcome()->getName(),
				'The outcome is saved in the appointmentDetail'
		);
		
		$appointmentDetail->setOutcomeReason('outcomeReason');
		$this->assertEquals(
				'outcomeReason',
				$appointmentDetail->getOutcomeReason(),
				'The outcome reason is saved in the appointmentDetail'
		);
		
		$appointmentDetail->setAddress($this->address);
		$this->assertEquals(
				'postcode',
				$appointmentDetail->getAddress()->getPostcode(),
				'The address is saved in the appointmentDetail'
		);
		
		$appointmentDetail->setRecordRef('recordRef');
		$this->assertEquals(
				'recordRef',
				$appointmentDetail->getRecordRef(),
				'The record Reference is saved in the appointmentDetail'
		);
		
		$appointmentDetail->setTitle('title');
		$this->assertEquals(
				'title',
				$appointmentDetail->getTitle(),
				'The title is saved in the appointmentDetail'
		);
		
		$appointmentDetail->setComment('comment');
		$this->assertEquals(
				'comment',
				$appointmentDetail->getComment(),
				'The comment is saved in the appointmentDetail'
		);
		
		$appointmentDetail->setTitle('title');
		$this->assertEquals(
				'title',
				$appointmentDetail->__toString(),
				'The title is saved in the appointmentDetail'
		);
	}
}