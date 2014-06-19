<?php

namespace Fsb\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\Constraints\Date;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Doctrine\ORM\EntityRepository;
use Fsb\CalendarBundle\Form\FilterType;
use Symfony\Component\HttpFoundation\Request;
use Fsb\AppointmentBundle\Entity\Appointment;
use Fsb\CalendarBundle\Entity\Filter;
use Doctrine\Tests\Common\DataFixtures\TestEntity\User;
use Doctrine\Common\Collections\ArrayCollection;
use Fsb\AppointmentBundle\Entity\AppointmentOutcome;
use Fsb\UserBundle\Util\Util;

class DefaultController extends Controller
{
	/**
	 * 
	 * @return \Symfony\Component\HttpFoundation\RedirectResponse
	 */
    public function indexAction()
    {
    	//Get the current month
		$currentDate = new \DateTime('now');
		$currentMonth = $currentDate->format("m");
		$currentYear = $currentDate->format("Y");
		
		//Redirect to the monthView for the current month
		$url = $this->generateUrl("calendar_month", array(
				"month" => $currentMonth,
				"year" => $currentYear
		));
		return new RedirectResponse($url);
    }
    
    /**
     * Get the searchForm with the filter filled with the session data
     *
     * @param Filter $filter
     * @return \Symfony\Component\Form\Form
     */
    private function getFilterForm($filter) {
    	$em = $this->getDoctrine()->getManager();
    	$session = $this->getRequest()->getSession();
    	 
    	$session_fitler = $session->get('filter');
    	$projects_filter = isset($session_fitler["projects"]) ? $session_fitler["projects"] : null;
    	$recruiter_filter = isset($session_fitler["recruiter"]) ? $session_fitler["recruiter"] : null;
    	$outcomes_filter = isset($session_fitler["outcomes"]) ? $session_fitler["outcomes"] : null;
	    	
	    	
    	if ($projects_filter) {
    		$project_ar = new ArrayCollection();
    		 
    		foreach ($projects_filter as $project) {
    			$project_ar->add($em->getRepository('AppointmentBundle:AppointmentProject')->find($project));
    		}
    		 
    		$filter->setProjects($project_ar);
    	}
    	if ($recruiter_filter && !$filter->getRecruiter()) {
    		$filter->setRecruiter($em->getRepository('UserBundle:User')->find($recruiter_filter));
    	}
    	if ($outcomes_filter) {
    		$outcome_ar = new ArrayCollection();
    		 
    		foreach ($outcomes_filter as $outcome) {
    			$outcome_ar->add($em->getRepository('AppointmentBundle:AppointmentOutcome')->find($outcome));
    		}
    		 
    		$filter->setOutcomes($outcome_ar);
    	}
    	    
    	return $this->createSearchForm($filter);
    }
    
    
    /**
     * 
     * @param unknown $month
     * @param unknown $year
     * @param string $recruiter_id
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function monthAction($month,$year, $recruiter_id = null) {
    	
    	$em = $this->getDoctrine()->getManager();
    	
    	/******************************************************************************************************************************/
    	/************************************************** FILTER FORM ***************************************************************/
    	/******************************************************************************************************************************/
    	
    	$session = $this->getRequest()->getSession();
    	
    	$session_fitler = $session->get('filter');
    	$projects_filter = isset($session_fitler["projects"]) ? $session_fitler["projects"] : null;
    	$recruiter_filter = isset($session_fitler["recruiter"]) ? $session_fitler["recruiter"] : null;
    	$outcomes_filter = isset($session_fitler["outcomes"]) ? $session_fitler["outcomes"] : null;
    	 
    	
    	/******************************************************************************************************************************/
    	/************************************************** Recruiter *************************************************************/
    	/******************************************************************************************************************************/
    	if ($recruiter_id) {
    		$recruiter = $em->getRepository('UserBundle:User')->find($recruiter_id);
    	}
    	
    	elseif ($recruiter_filter) {
    		$recruiter = $em->getRepository('UserBundle:User')->find($recruiter_filter);
    	}
    	
    	else {
    		//Recruiter (User logged)
    		$recruiter = $this->get('security.context')->getToken()->getUser();
    	}
    	
    	
    	if (!$recruiter) {
    		throw $this->createNotFoundException('Unable to find this recruiter.');
    	}
    	
    	$filter = new Filter();
    	$filter->setRecruiter($recruiter);
    	$searchForm   = $this->getFilterForm($filter);
    	
    	
    	/******************************************************************************************************************************/
    	/************************************************** Bank Holidays *************************************************************/
    	/******************************************************************************************************************************/
    	
    	$bankHolidayList = $em->getRepository('RuleBundle:UnavailableDate')->findBankHolidays();
    	
    	$auxList = array();
    	foreach ($bankHolidayList as $bankHoliday) {
    		array_push($auxList, $bankHoliday["unavailableDate"]->format('m/d/Y'));
    	}
    	$bankHolidayList = $auxList;
    	
    	
    	/******************************************************************************************************************************/
    	/************************************************** Get The Previous (month) Appointments ***************************************************************/
    	/******************************************************************************************************************************/
    	
    	$currentDate = new \DateTime('1-'.$month.'-'.$year);
    	
    	$prevDate = new \DateTime($currentDate->format('Y-m-d').' - 1 months');
    	$prevMonth = $prevDate->format('m');
    	$prevYear = $prevDate->format('Y');
    	
    	//Appointments in the prev month
    	$appointmentPrevList = $em->getRepository('AppointmentBundle:Appointment')->findNumAppointmentsByRecruiterAndByMonth($recruiter->getId(), $prevMonth, $prevYear, $projects_filter, $outcomes_filter);
    	//Prepare the array structure to be printed in the calendar
    	$auxList = array();
    	foreach ($appointmentPrevList as $appointment) {
    		$auxList[(int)$appointment["day"]] = $appointment["numapp"];
    	}
    	$appointmentPrevList = $auxList;
    	
    	
    	/******************************************************************************************************************************/
    	/************************************************** Get The Current (month) Appointments ***************************************************************/
    	/******************************************************************************************************************************/
    	   
    	//Appointments in the current month
    	$appointmentList = $em->getRepository('AppointmentBundle:Appointment')->findNumAppointmentsByRecruiterAndByMonth($recruiter->getId(), $month, $year, $projects_filter, $outcomes_filter);
    	//Prepare the array structure to be printed in the calendar
    	$auxList = array();
    	foreach ($appointmentList as $appointment) {
    		$auxList[(int)$appointment["day"]] = $appointment["numapp"];
    	}
    	$appointmentList = $auxList;
    	
    	/******************************************************************************************************************************/
    	/************************************************** Get The Next (month) Appointments ***************************************************************/
    	/******************************************************************************************************************************/
    	   
    	$nextDate = new \DateTime($currentDate->format('Y-m-d').' + 1 months');
    	$nextMonth = $nextDate->format('m');
    	$nextYear = $nextDate->format('Y');
    	
    	//Appointments in the next month
    	$appointmentNextList = $em->getRepository('AppointmentBundle:Appointment')->findNumAppointmentsByRecruiterAndByMonth($recruiter->getId(), $nextMonth, $nextYear, $projects_filter, $outcomes_filter);
    	//Prepare the array structure to be printed in the calendar
    	$auxList = array();
    	foreach ($appointmentNextList as $appointment) {
    		$auxList[(int)$appointment["day"]] = $appointment["numapp"];
    	}
    	$appointmentNextList = $auxList;
    	
    	
    	/******************************************************************************************************************************/
    	/************************************************** Render ***************************************************************/
    	/******************************************************************************************************************************/
    	   
    	return $this->render('CalendarBundle:Default:month.html.twig', array(
    			'recruiter' => $recruiter,
    			'recruiter_url' => $recruiter_id,
    			'appointmentList' => $appointmentList,
    			'appointmentPrevList' => $appointmentPrevList,
    			'appointmentNextList' => $appointmentNextList,
    			'bankHolidayList' => $bankHolidayList,
    			'month' => $month,
    			"year" => $year,
    			'searchForm' => $searchForm->createView(),
    	));
    }
    
    
    /**
     * 
     * @param unknown $day
     * @param unknown $month
     * @param unknown $year
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function dayAction($day,$month,$year, $recruiter_id = null) {
    
    	$em = $this->getDoctrine()->getManager();
    	
    	/******************************************************************************************************************************/
    	/************************************************** FILTER FORM ***************************************************************/
    	/******************************************************************************************************************************/
    	 
    	$session = $this->getRequest()->getSession();
    	$session_fitler = $session->get('filter');
    	$projects_filter = isset($session_fitler["projects"]) ? $session_fitler["projects"] : null;
    	$recruiter_filter = isset($session_fitler["recruiter"]) ? $session_fitler["recruiter"] : null;
    	$outcomes_filter = isset($session_fitler["outcomes"]) ? $session_fitler["outcomes"] : null;
    	 
    	
    	/******************************************************************************************************************************/
    	/************************************************** Recruiter *************************************************************/
    	/******************************************************************************************************************************/
    	if ($recruiter_id) {
    		$recruiter = $em->getRepository('UserBundle:User')->find($recruiter_id);
    	}
    	
    	elseif ($recruiter_filter) {
    		$recruiter = $em->getRepository('UserBundle:User')->find($recruiter_filter);
    	}
    	
    	else {
    		//Recruiter (User logged)
    		$recruiter = $this->get('security.context')->getToken()->getUser();
    	}
    	
    	
    	if (!$recruiter) {
    		throw $this->createNotFoundException('Unable to find this recruiter.');
    	}

    	
    	$filter = new Filter();
    	$filter->setRecruiter($recruiter);
    	$searchForm   = $this->getFilterForm($filter);
    	
    	/******************************************************************************************************************************/
    	/************************************************** Get The Current (day) Appointments ***************************************************************/
    	/******************************************************************************************************************************/
    	   
    	$appointmentList = $em->getRepository('AppointmentBundle:Appointment')->findAppointmentsByRecruiterAndByDay($recruiter->getId(), $day, $month, $year, $projects_filter, $outcomes_filter);
    	
    	//Prepare the array structure to be printed in the calendar
    	$auxList = array();
    	foreach ($appointmentList as $appointment) {
    			$aux = array();
    			$aux["id"] = $appointment["id"];
    			$aux["minute"] = $appointment["minute"];
    			$aux["hour"] = $appointment["hour"];
	    		$aux["title"] = $appointment["title"];
	    		$aux["comment"] = $appointment["comment"];
	    		$aux["record"] = $appointment["record"];
	    		$aux["recruiter"] = $appointment["recruiter"];
	    		$aux["outcome"] = $appointment["outcome"];
	    		$aux["outcomeReason"] = $appointment["outcomeReason"];
	    		$aux["project"] = $appointment["project"];
	    		
	    		if ($aux["minute"] < 30) {
	    			$auxList[(int)$appointment["hour"]][0][$appointment["id"]] = $aux;
	    		}
	    		else {
	    			$auxList[(int)$appointment["hour"]][30][$appointment["id"]] = $aux;
	    		}
    	}
    	
    	$appointmentList = $auxList;
    	
    	
    	
    	/******************************************************************************************************************************/
    	/************************************************** Render ********************************************************************/
    	/******************************************************************************************************************************/
    	   
    	return $this->render('CalendarBundle:Default:day.html.twig', array(
    			'recruiter' => $recruiter,
    			'recruiter_url' => $recruiter_id,
    			'appointment_list' => $appointmentList,
    			'day' => $day,
    			'month' => $month,
    			"year" => $year,
    			'searchForm' => $searchForm->createView(),
    	));
    }
    
    /**
     * 
     * @param unknown $day
     * @param unknown $month
     * @param unknown $year
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function diaryAction($day,$month,$year, $recruiter_id = null) {
    
    	$em = $this->getDoctrine()->getManager();
    	
    	/******************************************************************************************************************************/
    	/************************************************** FILTER FORM ***************************************************************/
    	/******************************************************************************************************************************/
    	 
    	$session = $this->getRequest()->getSession();
    	$session_fitler = $session->get('filter');
    	$projects_filter = isset($session_fitler["projects"]) ? $session_fitler["projects"] : null;
    	$recruiter_filter = isset($session_fitler["recruiter"]) ? $session_fitler["recruiter"] : null;
    	$outcomes_filter = isset($session_fitler["outcomes"]) ? $session_fitler["outcomes"] : null;
    	 
    
    	/******************************************************************************************************************************/
    	/************************************************** Recruiter *************************************************************/
    	/******************************************************************************************************************************/
    	if ($recruiter_id) {
    		$recruiter = $em->getRepository('UserBundle:User')->find($recruiter_id);
    	}
    	
    	elseif ($recruiter_filter) {
    		$recruiter = $em->getRepository('UserBundle:User')->find($recruiter_filter);
    	}
    	
    	else {
    		//Recruiter (User logged)
    		$recruiter = $this->get('security.context')->getToken()->getUser();
    	}
    	
    	
    	if (!$recruiter) {
    		throw $this->createNotFoundException('Unable to find this recruiter.');
    	}
    
    	$filter = new Filter();
    	$filter->setRecruiter($recruiter);
    	$searchForm   = $this->getFilterForm($filter);
    	
    	/******************************************************************************************************************************/
    	/************************************************** Get The Current (day) Appointments ***************************************************************/
    	/******************************************************************************************************************************/
    	
    	$appointmentList = $em->getRepository('AppointmentBundle:Appointment')->findAppointmentsByRecruiterFromDay($recruiter->getId(), $day, $month, $year, $projects_filter, $outcomes_filter);
    	 
    	//Prepare the array structure to be printed in the calendar
    	$auxList = array();
    	foreach ($appointmentList as $appointment) {
    		$aux = array();
    		$aux["id"] = $appointment["id"];
    		$aux["date"] = $appointment["date"];
    		$offset = date_format($appointment["date"],"D d M");
    		$aux["title"] = $appointment["title"];
    		$aux["comment"] = $appointment["comment"];
    		$aux["record"] = $appointment["record"];
    		$aux["recruiter"] = $appointment["recruiter"];
    		$aux["outcome"] = $appointment["outcome"];
    		$aux["outcomeReason"] = $appointment["outcomeReason"];
    		$aux["project"] = $appointment["project"];
    		
    		$auxList[$offset][$aux["id"]] = $aux;
    	}
    	
    	 
    	$appointmentList = $auxList;
    	
    	/******************************************************************************************************************************/
    	/************************************************** Render ********************************************************************/
    	/******************************************************************************************************************************/
    	
    	return $this->render('CalendarBundle:Default:diary.html.twig', array(
    			'recruiter' => $recruiter,
    			'recruiter_url' => $recruiter_id,
    			'appointment_list' => $appointmentList,
    			'day' => $day,
    			'month' => $month,
    			"year" => $year,
    			'searchForm' => $searchForm->createView(),
    	));
    }
    
    
    /**
     * Creates a form to apply a calendar search filter.
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createSearchForm(Filter $filter)
    {
        
    	$form = $this->createForm(new FilterType(), $filter, array(
    			'action' => $this->generateUrl('appointment_create'),
    			'method' => 'POST',
    	));
    
    	$form->add('submit', 'submit', array(
    			'label' => 'Apply',
    			'attr' => array('class' => 'ui-btn ui-corner-all ui-shadow ui-btn-b ui-btn-icon-left ui-icon-check')
    	));
    
    	return $form;
    }
    

    /**
     * Apply a calendar search filter.
     *
     */
    public function searchAction(Request $request)
    {
    	$filter = new Filter();
    	$form = $this->createSearchForm($filter);
    	
    	$form->handleRequest($request);
    	
    	if ($form->isValid()) {
	        
    		
    		$project_ar = array();
    		foreach ($filter->getProjects() as $project) {
    			array_push($project_ar,$project->getId());
    		}
    		
    		$outcome_ar = array();
    		foreach ($filter->getOutcomes() as $outcome) {
    			array_push($outcome_ar,$outcome->getId());
    		}
    		
    		$this->getRequest()->getSession()->set('filter',array(
    				"projects" => ($filter->getProjects()) ? $project_ar : null,
    				"recruiter" => ($filter->getRecruiter()) ? $filter->getRecruiter()->getId() : null,
    				"outcomes" => ($filter->getOutcomes()) ? $outcome_ar : null,
    		));
	    	
    		$url = $this->getRequest()->headers->get("referer");
    		return new RedirectResponse($url);
	    }
    	
    	return $this->render('CalendarBundle:Default:index.html.twig', array(
    			'searchForm' => $form->createView(),
    	));
    
    }
}
