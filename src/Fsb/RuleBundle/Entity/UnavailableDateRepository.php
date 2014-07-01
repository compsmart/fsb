<?php

namespace Fsb\RuleBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UnavailableDateRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UnavailableDateRepository extends EntityRepository
{
	
	/**
	 * Return the unavailable date if this day is unavailable for a recruiter
	 *
	 * @return boolean
	 *
	 */
	public function getUnavailableDatesByRecruiter($recruiter_id){
	
		$em = $this->getEntityManager();
		
		$query = $em->createQueryBuilder()
		->select(array('ud.unavailableDate', 'r.reason', 'ud.id'))
		->from('RuleBundle:UnavailableDate', 'ud')
		->innerJoin('ud.reason', 'r')
		->where('ud.recruiter = :recruiter_id')
		->orWhere('ud.recruiter is null')
		->andWhere('ud.allDay = true')
		->orderBy('ud.unavailableDate', 'ASC')
		
		->setParameter('recruiter_id', $recruiter_id)
		;
		
		$unavailableDate = $query->getQuery()->getResult();
		
		return $unavailableDate;
	}
	
	/**
	 * Return the unavailable times for a day for a recruiter
	 *
	 * @return boolean
	 *
	 */
	public function getUnavailableTimesByRecruiter($recruiter_id, $day){
	
		$em = $this->getEntityManager();
	
		$query = $em->createQueryBuilder()
		->select(array('ud.startTime', 'ud.endTime', 'ud.id'))
		->from('RuleBundle:UnavailableDate', 'ud')
		->innerJoin('ud.reason', 'r')
		->where('ud.recruiter = :recruiter_id')
		->andWhere('ud.allDay = false')
		->andWhere('ud.unavailableDate = :day')
		->orderBy('ud.unavailableDate', 'ASC')
	
		->setParameter('recruiter_id', $recruiter_id)
		->setParameter('day', $day)
		;
	
		$unavailableDate = $query->getQuery()->getResult();
	
		return $unavailableDate;
	}
	
	/**
	 * Get The unavailable Dates by month and year and time
	 *
	 * @param int $month
	 * @param int $year
	 * @param time $startTime
	 * @param time $endTime
	 *
	 * @return array UnavailableDate
	 */
	public function findUnavailableDatesByMonthAndYear($month,$year, $startTime = null, $endTime = null) {
	
		$em = $this->getEntityManager();
	
		$query = $em->createQueryBuilder();
	
		//Get the unavailable dates for a month and a year
		$query = $em->createQueryBuilder()
		->select(array('SUBSTRING(ud.unavailableDate,1,11) AS day', 'r.id'))
		->from('RuleBundle:UnavailableDate', 'ud')
		->innerJoin('ud.recruiter', 'r')
		->where('SUBSTRING(ud.unavailableDate, 6, 2) = :month')
		->andWhere('SUBSTRING(ud.unavailableDate, 1, 4) = :year')
		->andWhere('ud.allDay = true')
		->orderBy('ud.unavailableDate', 'ASC')
	
		->setParameter('month', (int)$month)
		->setParameter('year', (int)$year)
		;
		
		if ($startTime && $endTime) {
			$query
			->orWhere('(ud.allDay = false AND
					   ud.startTime <= :startTime AND
					   ud.endTime >= :endTime AND
					   SUBSTRING(ud.unavailableDate, 6, 2) = :month AND
					   SUBSTRING(ud.unavailableDate, 1, 4) = :year)'
			)
			->setParameter('startTime', $startTime)
			->setParameter('endTime', $endTime);
		}
		
		if ($startTime && !$endTime) {
			$query
			->orWhere('ud.allDay = false AND ud.startTime = :startTime')
			->setParameter('startTime', $startTime);
		}
		
		if (!$startTime && $endTime) {
			$query
			->orWhere('ud.allDay = false AND ud.endTime = :endTime')
			->setParameter('endTime', $endTime);
		}
	
		$unavailableDate_ar = $query->getQuery()->getResult();
	
		return $unavailableDate_ar;
	}
	
	/**
	 * Return the general unavailable dates for all recruiters (Bank Holidays, ...)
	 *
	 * @return boolean
	 *
	 */
	public function findUnavailableDatesForAllRecruiters(){
	
		$em = $this->getEntityManager();
	
		$query = $em->createQueryBuilder()
		->select(array('SUBSTRING(ud.unavailableDate,1,11) AS day', 'r.reason', 'ud.id'))
		->from('RuleBundle:UnavailableDate', 'ud')
		->innerJoin('ud.reason', 'r')
		->where('ud.recruiter is null')
		->andWhere('ud.allDay = true')
		->orderBy('ud.unavailableDate', 'ASC')
	
		;
	
		$unavailableDate = $query->getQuery()->getResult();
	
		return $unavailableDate;
	}
}
