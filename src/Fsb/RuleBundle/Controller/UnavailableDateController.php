<?php

namespace Fsb\RuleBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Fsb\RuleBundle\Entity\UnavailableDate;
use Fsb\RuleBundle\Form\UnavailableDateType;
use Fsb\UserBundle\Util\Util;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fsb\RuleBundle\Entity\AvailabilityFilter;
use Fsb\RuleBundle\Form\AvailabilityFilterType;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\Form;

/**
 * UnavailableDate controller.
 *
 */
class UnavailableDateController extends Controller
{

    /**
     * Lists all UnavailableDate entities.
     *
     */
    public function indexAction()
    {
        $eManager = $this->getDoctrine()->getManager();

        $entities = $eManager->getRepository('RuleBundle:UnavailableDate')->findAll();

        return $this->render('RuleBundle:UnavailableDate:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    
    
    /**
     * Check if the endTime is posterior to the startTime 
     *
     * @param UnavailableDate $unavailableDate
     */
    private function endTimeAfterStartTime(UnavailableDate $unavailableDate, Form $form) {
    
    	if (!$unavailableDate->getAllDay()) {
    		if (strtotime($unavailableDate->getEndTime()->format('H:i:s')) <= strtotime($unavailableDate->getStartTime()->format('H:i:s'))) {
    			$form->addError(new FormError("The endTime has to be posterior to the startTime"));
    		}
    	}
    	
    	return true;
    }
    
    /**
     * Check if Other reason has been selected
     *
     * @param UnavailableDate $unavailableDate
     */
    private function otherReasonSelected(UnavailableDate $unavailableDate, Form $form) {
    
    	if (($unavailableDate->getReason() == "Other") && ($unavailableDate->getOtherReason() == "")) {
    		$form->addError(new FormError("Please specify other reason"));
    	}
    
    	return true;
    }
    
    /**
     * Check if is possible to create a new unavailableDate
     *
     * @param UnavailableDate $unavailableDate
     */
    private function checkNewUnavailableDateRestrictions(UnavailableDate $unavailableDate, Form $form) {
    	 
    	//The endTime has to be posterior to the startTime
    	$this->endTimeAfterStartTime($unavailableDate, $form);
    	
    	//Other reason selected
    	$this->otherReasonSelected($unavailableDate, $form);
    	 
    	return true;
    }
    
  
    /**
     * Creates a new UnavailableDate entity.
     *
     */
    public function createAction(Request $request)
    {
    	$userLogged = $this->get('security.context')->getToken()->getUser();
    	
    	if (!$userLogged) {
    		throw $this->createNotFoundException('Unable to find this user.');
    	}
    	
        $unavailableDate = new UnavailableDate();
        $form = $this->createCreateForm($unavailableDate);
        $form->handleRequest($request);
        
        //Before save the unavailableDate we have to check some constraints
        $this->checkNewUnavailableDateRestrictions($unavailableDate, $form);
        
        $currentDate = $unavailableDate->getUnavailableDate();
        $day = $currentDate->format('d');
        $month = $currentDate->format('m');
        $year = $currentDate->format('Y');
        
        if ($form->isValid()) {
            $eManager = $this->getDoctrine()->getManager();
            
            if ($unavailableDate->getAllDay()) {
            	$unavailableDate->setStartTime(null);
            	$unavailableDate->setEndTime(null);
            }
            elseif (!$unavailableDate->getStartTime() or !$unavailableDate->getEndTime()) {
            	$unavailableDate->setAllDay(true);
            	$unavailableDate->setStartTime(null);
            	$unavailableDate->setEndTime(null);
            }
            
            
            Util::setCreateAuditFields($unavailableDate, $userLogged->getId());
            
            $eManager->persist($unavailableDate);
            $eManager->flush();
            
            $this->get('session')->getFlashBag()->set(
            		'success',
            		array(
            				'alert' => 'success',
            				'title' => 'Set Unavailable!',
            				'message' => 'The availability has changed'
            		)
            );
            
            return $this->redirect($this->generateUrl('calendar_day', array('day' => $day, 'month' => $month, 'year' => $year)));
        }

        return $this->render('RuleBundle:UnavailableDate:new.html.twig', array(
            'entity' => $unavailableDate,
            'form'   => $form->createView(),
        	'day' => $day,
        	'month' => $month,
        	'year' => $year,
        ));
    }

    /**
    * Creates a form to create a UnavailableDate entity.
    *
    * @param UnavailableDate $unavailableDate The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createCreateForm(UnavailableDate $unavailableDate)
    {
        $form = $this->createForm(new UnavailableDateType(), $unavailableDate, array(
            'action' => $this->generateUrl('unavailableDate_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array(
        		'label' => 'Set',
        		'attr' => array('class' => 'ui-btn ui-corner-all ui-shadow ui-btn-b ui-btn-icon-left ui-icon-check')
        ));

        return $form;
    }

    /**
     * Displays a form to create a new UnavailableDate entity.
     *
     */
    public function newAction()
    {
        $unavailableDate = new UnavailableDate();
        $form   = $this->createCreateForm($unavailableDate);

        return $this->render('RuleBundle:UnavailableDate:new.html.twig', array(
            'entity' => $unavailableDate,
            'form'   => $form->createView(),
        	'recruiter_id' => null,
        ));
    }
    
    /**
     * Displays a form to create a new Unavailable entity for a particular date
     *
     */
    public function newDateAction($hour, $minute, $day, $month, $year, $recruiter_id = null)
    {
    	$eManager = $this->getDoctrine()->getManager();
    	
    	$unavailableDate = new UnavailableDate();
    	 
    	$unavailableDateDay = new \DateTime($day.'-'.$month.'-'.$year.' '.$hour.':'.$minute.':00');
    	$unavailableDateDay->format('d-m-Y');
    	
    	$startTime = new \DateTime($day.'-'.$month.'-'.$year.' '.$hour.':'.$minute.':00');
    	$startTime->format('H:i');
    	
    	$endTime = new \DateTime($day.'-'.$month.'-'.$year.' '.$hour.':'.$minute.':00');
    	$endTime->format('H:i');
    	$endTime->modify('+30 minutes');
    	 
    	$unavailableDate->setUnavailableDate($unavailableDateDay);
    	$unavailableDate->setStartTime($startTime);
    	$unavailableDate->setEndTime($endTime);
    	
    	if ($recruiter_id) {
	    	$recruiter = $eManager->getRepository('UserBundle:User')->find($recruiter_id);
	    	
	    	if (!$recruiter) {
	    		throw $this->createNotFoundException('Unable to find this recruiter.');
	    	}
	    	
	    	$unavailableDate->setRecruiter($recruiter);
    	}
    	
    	$form   = $this->createCreateForm($unavailableDate);
    
    	
    	return $this->render('RuleBundle:UnavailableDate:new.html.twig', array(
            'entity' => $unavailableDate,
            'form'   => $form->createView(),
    		'recruiter_id' => $recruiter_id,
    		'day' => $day,
    		'month' => $month,
    		'year' => $year,
        ));
    }

    /**
     * Finds and displays a UnavailableDate entity.
     *
     */
    public function showAction($unavailableDateId)
    {
        $eManager = $this->getDoctrine()->getManager();

        $unavailableDate = $eManager->getRepository('RuleBundle:UnavailableDate')->find($unavailableDateId);

        if (!$unavailableDate) {
            throw $this->createNotFoundException('Unable to find UnavailableDate entity.');
        }

        $deleteForm = $this->createDeleteForm($unavailableDateId);

        return $this->render('RuleBundle:UnavailableDate:show.html.twig', array(
            'entity'      => $unavailableDate,
            'delete_form' => $deleteForm->createView(),        ));
    }

    /**
     * Displays a form to edit an existing UnavailableDate entity.
     *
     */
    public function editAction($unavailableDateId, $recruiter_id = null)
    {
        $eManager = $this->getDoctrine()->getManager();

        $unavailableDate = $eManager->getRepository('RuleBundle:UnavailableDate')->find($unavailableDateId);

        if (!$unavailableDate) {
            throw $this->createNotFoundException('Unable to find UnavailableDate entity.');
        }

        $editForm = $this->createEditForm($unavailableDate);
        $deleteForm = $this->createDeleteForm($unavailableDateId);
        
        $currentDate = $unavailableDate->getUnavailableDate();
        $day = $currentDate->format('d');
        $month = $currentDate->format('m');
        $year = $currentDate->format('Y');

        return $this->render('RuleBundle:UnavailableDate:edit.html.twig', array(
            'entity'      => $unavailableDate,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        	'recruiter_id' => $recruiter_id,
        	'day' => $day,
        	'month' => $month,
        	'year' => $year,
        ));
    }

    /**
    * Creates a form to edit a UnavailableDate entity.
    *
    * @param UnavailableDate $unavailableDate The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(UnavailableDate $unavailableDate)
    {
        $form = $this->createForm(new UnavailableDateType(), $unavailableDate, array(
            'action' => $this->generateUrl('unavailableDate_update', array('unavailableDateId' => $unavailableDate->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array(
        		'label' => 'Update',
        		'attr' => array('class' => 'ui-btn ui-corner-all ui-shadow ui-btn-b ui-btn-icon-left ui-icon-edit')
        ));

        return $form;
    }
    /**
     * Edits an existing UnavailableDate entity.
     *
     */
    public function updateAction(Request $request, $unavailableDateId)
    {
        $eManager = $this->getDoctrine()->getManager();

        $unavailableDate = $eManager->getRepository('RuleBundle:UnavailableDate')->find($unavailableDateId);

        if (!$unavailableDate) {
            throw $this->createNotFoundException('Unable to find UnavailableDate entity.');
        }

        $deleteForm = $this->createDeleteForm($unavailableDateId);
        $editForm = $this->createEditForm($unavailableDate);
        $editForm->handleRequest($request);
        
        //Before save the unavailableDate we have to check some constraints
        $this->checkNewUnavailableDateRestrictions($unavailableDate, $editForm);
        
        $currentDate = $unavailableDate->getUnavailableDate();
        $day = $currentDate->format('d');
        $month = $currentDate->format('m');
        $year = $currentDate->format('Y');
        
        $recruiter_id = $unavailableDate->getRecruiter()->getId();
        if ($this->get('security.context')->isGranted('ROLE_RECRUITER')) {
        	$recruiter_id = null;
        }

        if ($editForm->isValid()) {
            $eManager->flush();
            
            $this->get('session')->getFlashBag()->set(
            		'success',
            		array(
            				'alert' => 'success',
            				'title' => 'Update availability!',
            				'message' => 'The availability has been modified'
            		)
            );

            return $this->redirect($this->generateUrl('calendar_day', array('day' => $day, 'month' => $month, 'year' => $year, 'recruiter_id' => $recruiter_id)));
            
        }

        return $this->render('RuleBundle:UnavailableDate:edit.html.twig', array(
            'entity'      => $unavailableDate,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        	'recruiter_id' => $recruiter_id,
       		'day' => $day,
       		'month' => $month,
       		'year' => $year,
        ));
    }
    /**
     * Deletes a UnavailableDate entity.
     *
     */
    public function deleteAction(Request $request, $unavailableDateId)
    {
        $form = $this->createDeleteForm($unavailableDateId);
        $form->handleRequest($request);
        $form->submit($request);

        if ($form->isValid()) {
            $eManager = $this->getDoctrine()->getManager();
            $unavailableDate = $eManager->getRepository('RuleBundle:UnavailableDate')->find($unavailableDateId);

            if (!$unavailableDate) {
                throw $this->createNotFoundException('deleteAction - Unable to find UnavailableDate entity.');
            }
            
            $eManager->remove($unavailableDate);
            $eManager->flush();
            
            $this->get('session')->getFlashBag()->set(
            		'success',
            		array(
            				'alert' => 'success',
            				'title' => 'Set Available!',
            				'message' => 'The time is now available'
            		)
            );
        }
        else {
        	$this->get('session')->getFlashBag()->set(
        			'success',
        			array(
        					'alert' => 'error',
        					'title' => 'ERROR!',
        					'message' => 'The availability has NOT been changed'
        			)
        	);
        }
        
        $url = $this->getRequest()->headers->get("referer");
    	return new RedirectResponse($url);
    }

    /**
     * Creates a form to delete a UnavailableDate entity by id.
     *
     * @param mixed $unavailableDateId The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($unavailableDateId)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('unavailableDate_delete', array('unavailableDateId' => $unavailableDateId)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array(
            		'label' => 'Delete',
            		'attr' => array('class' => 'ui-btn ui-corner-all ui-shadow ui-btn-a ui-btn-icon-left ui-icon-delete')
            ))
            ->getForm()
        ;
    }
    
    /******************************************************************************************************************************/
    /******************************************************************************************************************************/
    /******************************************************************************************************************************/
    /******************************** SEARCH AVAILABILITY ACTION ******************************************************************/
    /******************************************************************************************************************************/
    /******************************************************************************************************************************/
    /******************************************************************************************************************************/
    
    /**
     * Creates a form in order to search the available recruiters.
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createSearchAvailabilityForm(AvailabilityFilter $filter, $month, $year)
    {
    
    	$form = $this->createForm(new AvailabilityFilterType(), $filter, array(
    			'action' => $this->generateUrl('unavailableDate_search_availability', array('month' => $month, 'year' => $year)),
    			'method' => 'POST',
    	));
    
    	$form->add('submit', 'submit', array(
    			'label' => 'Apply',
    			'attr' => array('class' => 'ui-btn ui-corner-all ui-shadow ui-btn-b ui-btn-icon-left ui-icon-check')
    	));
    
    	return $form;
    }
    
    /**
     *
     * @param unknown $month
     * @param unknown $year
     * @param AvailabilityFilter $filter
     * @return Ambigous <multitype:, unknown>
     */
    private function getAvailableRecruitersByMonthAndYear($month, $year, AvailabilityFilter $filter) {
    	 
    	$eManager = $this->getDoctrine()->getManager();
    	 
    	$currentDate = new \DateTime('1-'.$month.'-'.$year);
    	$monthDays = $currentDate->format('t');
    	 
    	//Get the unavailable dates for that month an year
    	$unavailableDateList = $eManager->getRepository('RuleBundle:UnavailableDate')->findUnavailableDatesByMonthAndYear($month, $year, $filter->getStartTime(), $filter->getEndTime());
    	 
    	$auxList = array();
    	foreach ($unavailableDateList as $unavailableDate) {
    		$auxList[$unavailableDate['day']][$unavailableDate['id']] = $unavailableDate['id'];
    	}
    	$unavailableDateList = $auxList;
    	 
    	//Get all the recruiters
    	if (count($filter->getRecruiters()) > 0) {
    		$recruiterList = $filter->getRecruiters();
    	}
    	else {
    		$recruiterList = $eManager->getRepository('UserBundle:User')->findUsersByRole('ROLE_RECRUITER');
    	}
    	$auxList = array();
    	foreach ($recruiterList as $recruiter) {
    		$auxList[$recruiter->getId()] = $recruiter;
    	}
    	$recruiterList = $auxList;
    	 
    	//Build the month array with the available recruiters
    	$auxList = array();
    	for ($i=1; $i<=$monthDays; $i++) {
    		$recruiterListAux = $recruiterList;
    		$day = new \DateTime($i.'-'.$month.'-'.$year);
    		if (isset($unavailableDateList[$day->format('Y-m-d')])){
    			foreach ($unavailableDateList[$day->format('Y-m-d')] as $rec) {
    				unset($recruiterListAux[$rec]);
    			}
    		}
    		$auxList[$day->format('m/d/Y')] = $recruiterListAux;
    	}
    	$availableRecruitList = $auxList;
    	 
    	 
    	return $availableRecruitList;
    }
    
    /**
     * Search recruiter available.
     *
     */
    public function searchAvailabilityAction($month,$year)
    {
    	$eManager = $this->getDoctrine()->getManager();
    	 
    	/******************************************************************************************************************************/
    	/************************************************** Build the form with the session values if are isset ***********************/
    	/******************************************************************************************************************************/
    	$session = $this->getRequest()->getSession();
    	 
    	$filter = new AvailabilityFilter();
    	 
    	$session_fitler = $session->get('availability_filter');
    
    	$recruiters_filter = isset($session_fitler["recruiters"]) ? $session_fitler["recruiters"] : null;
    	$startTime_filter = isset($session_fitler["startTime"]) ? $session_fitler["startTime"] : null;
    	$endTime_filter = isset($session_fitler["endTime"]) ? $session_fitler["endTime"] : null;
    	
    	$searchFormSubmitted = ($recruiters_filter || $startTime_filter || $endTime_filter)? true : false;
    	 
    	if ($recruiters_filter) {
    		$recruiter_ar = new ArrayCollection();
    		 
    		foreach ($recruiters_filter as $recruiter) {
    			$recruiter_ar->add($eManager->getRepository('UserBundle:User')->find($recruiter));
    		}
    		 
    		$filter->setRecruiters($recruiter_ar);
    	}
    
    	if ($startTime_filter && !$filter->getStartTime()) {
    		$filter->setStartTime($startTime_filter);
    	}
    
    	if ($endTime_filter && !$filter->getEndTime()) {
    		$filter->setEndTime($endTime_filter);
    	}
    	 
    	/******************************************************************************************************************************/
    	/************************************************** Form creation *************************************************************/
    	/******************************************************************************************************************************/
    	
    	$form = $this->createSearchAvailabilityForm($filter, $month, $year);
    	 
    	 
    	/******************************************************************************************************************************/
    	/************************************************** Get the values submited in the form *************** ***********************/
    	/******************************************************************************************************************************/
    	$request = $this->getRequest();
    	$form->handleRequest($request);
    	 
    	if ($form->isValid()) {
    		$recruiter_ar = array();
    		foreach ($filter->getRecruiters() as $recruiter) {
    			array_push($recruiter_ar,$recruiter->getId());
    		}
    
    		//Save the form fields in the session
    		$this->getRequest()->getSession()->set('availability_filter',array(
    				"recruiters" => ($filter->getRecruiters()) ? $recruiter_ar : null,
    				"startTime" => ($filter->getStartTime()) ? $filter->getStartTime() : null,
    				"endTime" => ($filter->getEndTime()) ? $filter->getEndTime() : null,
    		));
    		
    		
    		$url = $this->getRequest()->headers->get("referer");
    		return new RedirectResponse($url);
    	}
    	 
    	/******************************************************************************************************************************/
    	/************************************************** Get the available recruiters **********************************************/
    	/******************************************************************************************************************************/
    	$recruiterList = $this->getAvailableRecruitersByMonthAndYear($month, $year, $filter);
    	 
    	 
    	/******************************************************************************************************************************/
    	/************************************************** Get the general unavailable dates for all recruiters (bank holidays, ...) */
    	/******************************************************************************************************************************/
    	$unavaiCommonDateList = $eManager->getRepository('RuleBundle:UnavailableDate')->findUnavailableDatesForAllRecruiters($month, $year);
    	$auxList = array();
    	foreach ($unavaiCommonDateList as $unavailableDate) {
    		$day = new \DateTime($unavailableDate['day']);
    		$day = $day->format("m/d/Y");
    		$auxList[$day] = $unavailableDate['reason'];
    	}
    	$unavaiCommonDateList = $auxList;
    	 
    	 
    	/******************************************************************************************************************************/
    	/********************************************* RENDER *************************************************************************/
    	/******************************************************************************************************************************/
    	return $this->render('RuleBundle:UnavailableDate:searchAvailability.html.twig', array(
    			'recruiterList' => $recruiterList,
    			'unavailableCommonDateList' => $unavaiCommonDateList,
    			'month' => $month,
    			"year" => $year,
    			'searchAvailabilityForm' => $form->createView(),
    			'searchAvailabilityFormSubmitted' => $searchFormSubmitted,
    	));
    }
    
    /**
     * Clean the data of the search availability filter.
     *
     */
    public function cleanSearchAvailabilityAction()
    {
    	$this->getRequest()->getSession()->remove('availability_filter');
    
    	 
    	$url = $this->getRequest()->headers->get("referer");$url = $this->getRequest()->headers->get("referer");
    	return new RedirectResponse($url);
    }
}
