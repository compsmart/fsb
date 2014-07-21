<?php
namespace Fsb\CalendarBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Fsb\UserBundle\Util\Util;
use Fsb\CalendarBundle\Entity\Filter;

class DiaryController extends DefaultController
{
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
		$postcode_filter = isset($session_fitler["postcode"]) ? $session_fitler["postcode"] : null;
		$range_filter = isset($session_fitler["range"]) ? $session_fitler["range"] : null;
		 
		$searchFormSubmitted = ($projects_filter || $recruiter_filter || $outcomes_filter || $postcode_filter || $range_filter)? true : false;
	
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
	
		 
		/******************************************************************************************************************************/
		/************************************************** Get the Rules ***********************************************************/
		/******************************************************************************************************************************/
		
		$ruleList = $em->getRepository('RuleBundle:Rule')->findBy(array(
				'recruiter' => $recruiter->getId()
		));
		
		
		/******************************************************************************************************************************/
		/************************************************** Postcode Filter ***********************************************************/
		/******************************************************************************************************************************/
		 
		$postcode_coord = Util::postcodeToCoords($postcode_filter);
		$postcode_lat = $postcode_coord['lat'];
		$postcode_lon = $postcode_coord['lng'];
		$distance = $range_filter*1.1515;
		 
		/******************************************************************************************************************************/
		/************************************************** Form creation *************************************************************/
		/******************************************************************************************************************************/
		 
		$filter = new Filter();
		$filter->setRecruiter($recruiter);
		$searchForm   = $this->getFilterForm($filter);
		 
		/******************************************************************************************************************************/
		/************************************************** Unavailable Dates *************************************************************/
		/******************************************************************************************************************************/
		 
		$unavailableDateList = $em->getRepository('RuleBundle:UnavailableDate')->getUnavailableDatesByRecruiter($recruiter->getId());
	
		$auxList = array();
		foreach ($unavailableDateList as $unavailableDate) {
			$auxList[$unavailableDate["unavailableDate"]->format('m/d/Y')] = $unavailableDate["reason"];
		}
		$unavailableDateList = $auxList;
		 
		/******************************************************************************************************************************/
		/************************************************** Get the Appointments from the current day *********************************/
		/******************************************************************************************************************************/
		//If you are filter by recruiter or the user logged is a recruiter, we search the appointments by recruiter
		if ($recruiter->getRole() == 'ROLE_RECRUITER') {
			$appointmentList = $em->getRepository('AppointmentBundle:Appointment')->findAppointmentsFromDay($day, $month, $year,$recruiter->getId(),  $projects_filter, $outcomes_filter, $postcode_lat, $postcode_lon, $distance);
		}
		//In any other case, we search all the appointments
		else {
			$appointmentList = $em->getRepository('AppointmentBundle:Appointment')->findAppointmentsFromDay($day, $month, $year, null, $projects_filter, $outcomes_filter, $postcode_lat, $postcode_lon, $distance);
		}
	
		//Prepare the array structure to be printed in the calendar
		$auxList = array();
		foreach ($appointmentList as $appointment) {
			$aux = array();
			$aux["id"] = $appointment["id"];
			$aux["date"] = $appointment["date"];
			$offset = date_format($appointment["date"],"D d M Y");
			$aux["title"] = $appointment["title"];
			$aux["comment"] = $appointment["comment"];
			$aux["recruiter"] = $appointment["recruiter"];
			$aux["outcome"] = $appointment["outcome"];
			$aux["outcomeReason"] = $appointment["outcomeReason"];
			$aux["project"] = $appointment["project"];
			$aux["recordRef"] = $appointment["recordRef"];
			$aux["postcode"] = $appointment["postcode"];
			$aux["map"] = Util::getMapUrl($appointment["lat"], $appointment["lon"], $appointment["postcode"]);
			$aux["color"] = Util::getColorById($appointment["outcome_id"]);
	
			$auxList[$offset][$aux["id"]] = $aux;
		}
		 
	
		$appointmentList = $auxList;
		 
		/******************************************************************************************************************************/
		/************************************************** Get Appointments for the mini calendar ************************************/
		/******************************************************************************************************************************/
	
		//Appointments in the current month
		$appointmentMiniCalendarList = $em->getRepository('AppointmentBundle:Appointment')->findNumAppointmentsByMonth($month, $year, $recruiter->getId(), $projects_filter, $outcomes_filter, $postcode_lat, $postcode_lon, $distance);
		//Prepare the array structure to be printed in the calendar
		$auxList = array();
		foreach ($appointmentMiniCalendarList as $appointment) {
			$auxList[(int)$appointment["day"]] = $appointment["numapp"];
		}
		$appointmentMiniCalendarList = $auxList;
		
		/******************************************************************************************************************************/
		/************************************************** Get upcoming appointments *************************************************/
		/******************************************************************************************************************************/
		$upcomingAppointmentList = $this->getUpcomingAppointments($recruiter);
		
		/******************************************************************************************************************************/
		/************************************************** Get appointmentOutcome chart *************************************************/
		/******************************************************************************************************************************/
		$appointmentOutcomesChart = $this->getAppointmentOutcomeChart($recruiter);
		
		/******************************************************************************************************************************/
		/************************************************** Get appointmentsByMonth chart *************************************************/
		/******************************************************************************************************************************/
		$appointmentsByMonthChart = $this->getAppointmentsByMonthChart($recruiter);
		
		/******************************************************************************************************************************/
		/************************************************** Get appointmentsByWeek chart *************************************************/
		/******************************************************************************************************************************/
		$appointmentsByWeekChart = $this->getAppointmentsByWeekChart($recruiter);
		
		/******************************************************************************************************************************/
		/************************************************** Render ********************************************************************/
		/******************************************************************************************************************************/
		 
		return $this->render('CalendarBundle:Diary:diary.html.twig', array(
				'recruiter' => $recruiter,
				'recruiter_url' => $recruiter_id,
				'appointment_list' => $appointmentList,
				'unavailableDateList' => $unavailableDateList,
				'day' => $day,
				'month' => $month,
				"year" => $year,
				'searchForm' => $searchForm->createView(),
				'searchFormSubmitted' => $searchFormSubmitted,
				'appointmentMiniCalendarList' => $appointmentMiniCalendarList,
				'ruleList' => $ruleList,
				'upcomingAppointmentList' => $upcomingAppointmentList,
				'appointmentOutcomesChart' => $appointmentOutcomesChart,
				'appointmentsByMonthChart' => $appointmentsByMonthChart,
				'appointmentsByWeekChart' => $appointmentsByWeekChart,
		));
	}
}