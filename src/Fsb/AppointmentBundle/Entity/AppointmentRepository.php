<?php

namespace Fsb\AppointmentBundle\Entity;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * AppointmentRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class AppointmentRepository extends EntityRepository
{
	/**
	 * Get The appointments of a recruiter and in a particular month
	 *
	 * @param int $recruiter_id
	 * @param int $month
	 * @param int $year
	 * 
	 * @return array Appointments
	 */
	public function findNumAppointmentsByRecruiterAndByMonth($recruiter_id,$month,$year, $projects = null, $outcomes = null, $postcode_lat = null, $postcode_lon = null, $distance = null) {
	
		$em = $this->getEntityManager();
		
		$query = $em->createQueryBuilder()
		->select(array('SUBSTRING(a.startDate, 9, 2) AS day', 'COUNT(a.id) AS numapp'))
		->from('AppointmentBundle:Appointment', 'a')
		->innerJoin('a.appointmentDetail', 'ad')
		->innerJoin('ad.address', 'adr')
		->where('a.recruiter = :recruiter_id')
		->andWhere('SUBSTRING(a.startDate, 6, 2) = :month')
		->andWhere('SUBSTRING(a.startDate, 1, 4) = :year')
		->groupBy('day')
		->orderBy('a.startDate', 'ASC')
		
		->setParameter('recruiter_id', $recruiter_id)
		->setParameter('month', (int)$month)
		->setParameter('year', (int)$year)
		;
		
		if (count($projects) > 0) {
			$query
			->andWhere('ad.project IN (:projects)')
			->setParameter('projects', $projects);
		}
		
		if (count($outcomes) > 0) {
			$query
			->andWhere('ad.outcome IN (:outcomes)')
			->setParameter('outcomes', $outcomes);
		}
		
		if ($postcode_lat && $postcode_lon && $distance) {
			
			$query
			->andWhere($query->expr()->between(':lat', 'adr.lat - :distance', 'adr.lat + :distance'))
			->andWhere($query->expr()->between(':lon', 'adr.lon - :distance', 'adr.lon + :distance'))
			->andWhere('
					((((	
						ACOS(
							SIN(:lat*PI()/180) * SIN(adr.lat*PI()/180) +
							COS(:lat*PI()/180) * COS(adr.lat*PI()/180) * COS((:lon - adr.lon)*PI()/180)
						)
					)*180/PI())*160*1.1515)) <= :distance')
			->setParameter('lat', $postcode_lat)
						->setParameter('lon', $postcode_lon)
						->setParameter('lat', $postcode_lat)
						->setParameter('distance', $distance)
			;
		} 
		
		$appointment_ar = $query->getQuery()->getResult();
	
		return $appointment_ar;
	}
	
	/**
	 * Get The appointments of a recruiter and in a particular month
	 *
	 * @param int $recruiter_id
	 * @param int $month
	 * @param int $year
	 *
	 * @return array Appointments
	 */
	public function findAppointmentsByRecruiterAndByMonth($recruiter_id,$month,$year, $projects = null, $outcomes = null, $postcode_lat = null, $postcode_lon = null, $distance = null) {
	
		$em = $this->getEntityManager();
	
		$query = $em->createQueryBuilder()
		->select('a.id, ad.title, adr.lat, adr.lon')
		->from('AppointmentBundle:Appointment', 'a')
		->innerJoin('a.appointmentDetail', 'ad')
		->innerJoin('ad.address', 'adr')
		->where('a.recruiter = :recruiter_id')
		->andWhere('SUBSTRING(a.startDate, 6, 2) = :month')
		->andWhere('SUBSTRING(a.startDate, 1, 4) = :year')
		->orderBy('a.startDate', 'ASC')
	
		->setParameter('recruiter_id', $recruiter_id)
		->setParameter('month', (int)$month)
		->setParameter('year', (int)$year)
		;
	
		if (count($projects) > 0) {
			$query
			->andWhere('ad.project IN (:projects)')
			->setParameter('projects', $projects);
		}
	
		if (count($outcomes) > 0) {
			$query
			->andWhere('ad.outcome IN (:outcomes)')
			->setParameter('outcomes', $outcomes);
		}
	
		if ($postcode_lat && $postcode_lon && $distance) {
			
			$query
			->andWhere($query->expr()->between(':lat', 'adr.lat - :distance', 'adr.lat + :distance'))
			->andWhere($query->expr()->between(':lon', 'adr.lon - :distance', 'adr.lon + :distance'))
			->andWhere('
					((((	
						ACOS(
							SIN(:lat*PI()/180) * SIN(adr.lat*PI()/180) +
							COS(:lat*PI()/180) * COS(adr.lat*PI()/180) * COS((:lon - adr.lon)*PI()/180)
						)
					)*180/PI())*160*1.1515)) <= :distance')
			->setParameter('lat', $postcode_lat)
						->setParameter('lon', $postcode_lon)
						->setParameter('lat', $postcode_lat)
						->setParameter('distance', $distance)
			;
		}
	
		$appointment_ar = $query->getQuery()->getResult();
	
		return $appointment_ar;
	}
	
	/**
	 * Get The appointments of a recruiter and in a particular day
	 *
	 * @param int $recruiter_id
	 * @param $day
	 * @param int $month
	 * @param int $year
	 *
	 * @return array Appointments
	 */
	public function findAppointmentsByRecruiterAndByDay($recruiter_id,$day,$month,$year, $projects = null, $outcomes = null, $postcode_lat = null, $postcode_lon = null, $distance = null) {
	
		$em = $this->getEntityManager();
	
		$query = $em->createQueryBuilder()
		->select(array(
				'SUBSTRING(a.startDate, 11, 3) AS hour', 
				'SUBSTRING(a.startDate, 15, 2) AS minute',
				'a.id, ad.title', 
				'ad.comment',
				'CONCAT(ud.firstname, :space, ud.lastname) as recruiter',
				'p.name as project',
				'o.name as outcome',
				'o.id as outcome_id',
				'ad.outcomeReason',
				'ad.recordRef',
				'adr.postcode',
				'adr.lat',
				'adr.lon'
		))
		->from('AppointmentBundle:Appointment', 'a')
		->innerJoin('a.appointmentDetail', 'ad')
		->innerJoin('ad.project', 'p')
		->innerJoin('ad.outcome', 'o')
		->innerJoin('ad.address', 'adr')
		->innerJoin('a.recruiter', 'u')
		->innerJoin('u.userDetail', 'ud')
		->where('a.recruiter = :recruiter_id')
		->andWhere('SUBSTRING(a.startDate, 9, 2) = :day')
		->andWhere('SUBSTRING(a.startDate, 6, 2) = :month')
		->andWhere('SUBSTRING(a.startDate, 1, 4) = :year')
		->orderBy('a.startDate', 'ASC')
		
		->setParameter('recruiter_id', $recruiter_id)
		->setParameter('day', (int)$day)
		->setParameter('month', (int)$month)
		->setParameter('year', (int)$year)
		->setParameter('space', " ")
		;
		
		if (count($projects) > 0) {
			$query
			->andWhere('ad.project IN (:projects)')
			->setParameter('projects', $projects);
		}
		
		if (count($outcomes) > 0) {
			$query
			->andWhere('ad.outcome IN (:outcomes)')
			->setParameter('outcomes', $outcomes);
		}
		
		if ($postcode_lat && $postcode_lon && $distance) {
			
			$query
			->andWhere($query->expr()->between(':lat', 'adr.lat - :distance', 'adr.lat + :distance'))
			->andWhere($query->expr()->between(':lon', 'adr.lon - :distance', 'adr.lon + :distance'))
			->andWhere('
					((((	
						ACOS(
							SIN(:lat*PI()/180) * SIN(adr.lat*PI()/180) +
							COS(:lat*PI()/180) * COS(adr.lat*PI()/180) * COS((:lon - adr.lon)*PI()/180)
						)
					)*180/PI())*160*1.1515)) <= :distance')
			->setParameter('lat', $postcode_lat)
						->setParameter('lon', $postcode_lon)
						->setParameter('lat', $postcode_lat)
						->setParameter('distance', $distance)
			;
		}
		
		$appointment_ar = $query->getQuery()->getResult();
	
		return $appointment_ar;
	}
	
	/**
	 * Get The appointments of a recruiter and since a particular day
	 *
	 * @param int $recruiter_id
	 * @param $day
	 * @param int $month
	 * @param int $year
	 *
	 * @return array Appointments
	 */
	public function findAppointmentsByRecruiterFromDay($recruiter_id,$day,$month,$year, $projects = null, $outcomes = null, $postcode_lat = null, $postcode_lon = null, $distance = null) {
	
		$em = $this->getEntityManager();
	
		
		$query = $em->createQueryBuilder()
		->select(array(
				'a.startDate as date, a.id, ad.title, ad.comment',
				'ud.firstname as recruiter',
				'p.name as project',
				'o.name as outcome',
				'o.id as outcome_id',
				'ad.outcomeReason',
				'ad.recordRef',
				'adr.postcode',
				'adr.lat',
				'adr.lon'
		))
		->from('AppointmentBundle:Appointment', 'a')
		->innerJoin('a.appointmentDetail', 'ad')
		->innerJoin('ad.project', 'p')
		->innerJoin('ad.outcome', 'o')
		->innerJoin('ad.address', 'adr')
		->innerJoin('a.recruiter', 'u')
		->innerJoin('u.userDetail', 'ud')
		->where('a.recruiter = :recruiter_id')
		->andWhere('a.recruiter = :recruiter_id')
		->andWhere('a.startDate > :date')
		->orderBy('a.startDate', 'ASC')
		
		->setParameter('recruiter_id', $recruiter_id)
		->setParameter('date', new \DateTime($year.'-'.$month.'-'.$day.' 00:00:00'))
		;
		
		if (count($projects) > 0) {
			$query
			->andWhere('ad.project IN (:projects)')
			->setParameter('projects', $projects);
		}
		
		if (count($outcomes) > 0) {
			$query
			->andWhere('ad.outcome IN (:outcomes)')
			->setParameter('outcomes', $outcomes);
		}
		
		if ($postcode_lat && $postcode_lon && $distance) {
			
			$query
			->andWhere($query->expr()->between(':lat', 'adr.lat - :distance', 'adr.lat + :distance'))
			->andWhere($query->expr()->between(':lon', 'adr.lon - :distance', 'adr.lon + :distance'))
			->andWhere('
					((((	
						ACOS(
							SIN(:lat*PI()/180) * SIN(adr.lat*PI()/180) +
							COS(:lat*PI()/180) * COS(adr.lat*PI()/180) * COS((:lon - adr.lon)*PI()/180)
						)
					)*180/PI())*160*1.1515)) <= :distance')
			->setParameter('lat', $postcode_lat)
						->setParameter('lon', $postcode_lon)
						->setParameter('lat', $postcode_lat)
						->setParameter('distance', $distance)
			;
		}
		
		$appointment_ar = $query->getQuery()->getResult();
	
		return $appointment_ar;
	}
	
	/**
	 * 
	 * Find the appointments that are between two dates for a particular recruiter
	 * 
	 * @param \DateTime $startDate
	 * @param \DateTime $endDate
	 */
	public function findAppointmentsWithCollision(\DateTime $startDate, \DateTime $endDate, $recruiter_id) {
		$em = $this->getEntityManager();
		
		$query = $em->createQueryBuilder()
		->select(array('a.id'))
		->from('AppointmentBundle:Appointment', 'a')
		->innerJoin('a.appointmentDetail', 'ad')
		->innerJoin('ad.address', 'adr')
		->where('a.recruiter = :recruiter_id')
		->andWhere('
				(((SUBSTRING(a.startDate, 1, 10)) <= SUBSTRING(:startDate, 1, 10) AND (SUBSTRING(a.endDate, 1, 10)) >= SUBSTRING(:endDate, 1, 10)) OR
				((SUBSTRING(a.startDate, 1, 10)) <= SUBSTRING(:startDate, 1, 10) AND (SUBSTRING(a.endDate, 1, 10)) <= SUBSTRING(:endDate, 1, 10) AND (SUBSTRING(a.endDate, 1, 10)) > SUBSTRING(:startDate, 1, 10)) OR
				((SUBSTRING(a.startDate, 1, 10)) >= SUBSTRING(:startDate, 1, 10) AND (SUBSTRING(a.endDate, 1, 10)) <= SUBSTRING(:endDate, 1, 10)) OR
				((SUBSTRING(a.startDate, 1, 10)) >= SUBSTRING(:startDate, 1, 10) AND (SUBSTRING(a.endDate, 1, 10)) >= SUBSTRING(:endDate, 1, 10) AND (SUBSTRING(a.startDate, 1, 10)) <= SUBSTRING(:endDate, 1, 10)))
				AND
				(((SUBSTRING(a.startDate, 12, 8)) <= SUBSTRING(:startDate, 12, 8) AND (SUBSTRING(a.endDate, 12, 8)) >= SUBSTRING(:endDate, 12, 8)) OR
				((SUBSTRING(a.startDate, 12, 8)) <= SUBSTRING(:startDate, 12, 8) AND (SUBSTRING(a.endDate, 12, 8)) <= SUBSTRING(:endDate, 12, 8) AND (SUBSTRING(a.endDate, 12, 8)) > SUBSTRING(:startDate, 12, 8)) OR
				((SUBSTRING(a.startDate, 12, 8)) >= SUBSTRING(:startDate, 12, 8) AND (SUBSTRING(a.endDate, 12, 8)) <= SUBSTRING(:endDate, 12, 8)) OR
				((SUBSTRING(a.startDate, 12, 8)) >= SUBSTRING(:startDate, 12, 8) AND (SUBSTRING(a.endDate, 12, 8)) >= SUBSTRING(:endDate, 12, 8) AND (SUBSTRING(a.startDate, 12, 8)) <= SUBSTRING(:endDate, 12, 8)))
		')
		->orderBy('a.startDate', 'ASC')
		
		->setParameter('recruiter_id', $recruiter_id)
		->setParameter('startDate', $startDate)
		->setParameter('endDate', $endDate)
		;
		
		$appointment_ar = $query->getQuery()->getResult();
		
		return $appointment_ar;
	}
	
	/**
	 * Get The appointments by filter
	 *
	 * @param int $recruiter_id
	 * @param int $month
	 * @param int $year
	 *
	 * @return array Appointments
	 */
	public function findAppointmentsByFilter($month,$year, $recruiters = null, $projects = null, $outcomes = null, $postcode_lat = null, $postcode_lon = null, $distance = null) {
	
		$em = $this->getEntityManager();
	
		$query = $em->createQueryBuilder()
		->select('a, ad, adr')
		->from('AppointmentBundle:Appointment', 'a')
		->innerJoin('a.appointmentDetail', 'ad')
		->innerJoin('ad.address', 'adr')
		->where('SUBSTRING(a.startDate, 6, 2) = :month')
		->andWhere('SUBSTRING(a.startDate, 1, 4) = :year')
		->orderBy('a.startDate', 'ASC')
	
		->setParameter('month', (int)$month)
		->setParameter('year', (int)$year)
		;
	
		if (count($recruiters) > 0) {
			$query
			->andWhere('a.recruiter IN (:recruiters)')
			->setParameter('recruiters', $recruiters);
		}
		
		if (count($projects) > 0) {
			$query
			->andWhere('ad.project IN (:projects)')
			->setParameter('projects', $projects);
		}
	
		if (count($outcomes) > 0) {
			$query
			->andWhere('ad.outcome IN (:outcomes)')
			->setParameter('outcomes', $outcomes);
		}
	
		if ($postcode_lat && $postcode_lon && $distance) {
				
			$query
			->andWhere($query->expr()->between(':lat', 'adr.lat - :distance', 'adr.lat + :distance'))
			->andWhere($query->expr()->between(':lon', 'adr.lon - :distance', 'adr.lon + :distance'))
			->andWhere('
					((((
						ACOS(
							SIN(:lat*PI()/180) * SIN(adr.lat*PI()/180) +
							COS(:lat*PI()/180) * COS(adr.lat*PI()/180) * COS((:lon - adr.lon)*PI()/180)
						)
					)*180/PI())*160*1.1515)) <= :distance')
						->setParameter('lat', $postcode_lat)
						->setParameter('lon', $postcode_lon)
						->setParameter('lat', $postcode_lat)
						->setParameter('distance', $distance)
						;
		}
	
		$appointment_ar = $query->getQuery()->getResult();
	
		return $appointment_ar;
	}
	
	/**
	 * Get appointmentOutcomes groupped by month
	 * 
	 * @param unknown $year
	 * @param string $recruiters
	 * @param string $appointmentSetters
	 * @return multitype:
	 */
	public function findNumAppointmentOutcomesByMonth ($year, $recruiters = null,  $appointmentSetters = null) {
		
		$em = $this->getEntityManager();
		
		$query = $em->createQueryBuilder()
		->select('SUBSTRING(a.startDate, 6, 2) as month, ao.name, count(a) as num_appointments')
		->from('AppointmentBundle:Appointment', 'a')
		->innerJoin('a.appointmentDetail', 'ad')
		->innerJoin('ad.outcome', 'ao')
		->where('SUBSTRING(a.startDate, 1, 4) = :year')
		->groupBy('month, ao.id')
		
		->setParameter('year', (int)$year)
		;
		
		if (count($recruiters) > 0) {
			$query
			->andWhere('a.recruiter IN (:recruiters)')
			->setParameter('recruiters', $recruiters);
		}
		
		if (count($appointmentSetters) > 0) {
			$query
			->andWhere('a.appointment_setter IN (:appointmentSetters)')
			->setParameter('appointmentSetters', $appointmentSetters);
		}
		
		$appointmentOutcomes_ar = $query->getQuery()->getResult();
		
		return $appointmentOutcomes_ar;
	}
	
	
	/**
	 * Get appointmentOutcomes groupped by month
	 *
	 * @param unknown $year
	 * @param string $recruiters
	 * @param string $appointmentSetters
	 * @return multitype:
	 */
	public function findNumAppointmentOutcomesGroupByRecruiter ($startDate = null,  $endDate = null) {
	
		$em = $this->getEntityManager();
	
		$query = $em->createQueryBuilder()
		->select('r.id as recruiter_id, ao.name, count(a) as num_appointments')
		->from('AppointmentBundle:Appointment', 'a')
		->innerJoin('a.appointmentDetail', 'ad')
		->innerJoin('ad.outcome', 'ao')
		->innerJoin('a.recruiter', 'r')
		->groupBy('a.recruiter, ao.id')
		;
		
		if ($startDate && $endDate) {
			$query
			->where('a.startDate >= :startDate')
			->andWhere('a.endDate <= :endDate')
			->setParameter('startDate', $startDate)
			->setParameter('endDate', $endDate);
		}
		
		if ($startDate && !$endDate) {
			$query
			->where('a.startDate >= :startDate')
			->setParameter('startDate', $startDate);
		}
		
		if (!$startDate && $endDate) {
			$query
			->where('a.endDate <= :endDate')
			->setParameter('endDate', $endDate);
		}
	
		$appointmentOutcomes_ar = $query->getQuery()->getResult();
	
		return $appointmentOutcomes_ar;
	}
	
	/**
	 * Get appointmentOutcomes 
	 *
	 * @param $recruiter_id if you want to get the num of appointment outcomes for one recruiter (could be null)
	 *
	 * @return multitype:
	 */
	public function findNumAppointmentOutcomes ($recruiter_id = null) {
	
		$em = $this->getEntityManager();
	
		$query = $em->createQueryBuilder()
		->select('count(a)')
		->from('AppointmentBundle:Appointment', 'a')
		->innerJoin('a.appointmentDetail', 'ad')
		->innerJoin('ad.outcome', 'ao')
		->innerJoin('a.recruiter', 'r')
		->groupBy('ao.id')
		->orderBy('ao.id')
		;
		
		if ($recruiter_id) {
			$query
			->where('r.id = :recruiter_id')
			->setParameter('recruiter_id', $recruiter_id)
			;
		}
	
		$appointmentOutcomes_ar = $query->getQuery()->getResult();
	
		return $appointmentOutcomes_ar;
	}

	/**
	 * Get appointments by week
	 *
	 * @param $date
	 * @param $recruiter_id if you want to get the num of appointments for one recruiter (could be null)
	 *
	 * @return multitype:
	 */
	public function findNumAppointmentsThisWeek ($recruiter_id = null) {
	
		$em = $this->getEntityManager();
		
		$firstWeekDay = date('Y-m-d 00:00:00',strtotime('monday this week'));
		$lastWeekDay = date('Y-m-d 23:59:59',strtotime('sunday this week'));
	
		$query = $em->createQueryBuilder()
		->select('count(a) as num, SUBSTRING(a.startDate,9,2) as day')
		->from('AppointmentBundle:Appointment', 'a')
		->innerJoin('a.appointmentDetail', 'ad')
		->innerJoin('a.recruiter', 'r')
		->where('a.startDate >= :firstWeekDay')
		->andWhere('a.startDate <= :lastWeekDay')
		->groupBy('a.startDate')
		
		->setParameter('firstWeekDay', $firstWeekDay)
		->setParameter('lastWeekDay', $lastWeekDay)
		;
	
		if ($recruiter_id) {
			$query
			->andWhere('r.id = :recruiter_id')
			->setParameter('recruiter_id', $recruiter_id)
			;
		}
	
		$appointmentOutcomes_ar = $query->getQuery()->getResult();
	
		return $appointmentOutcomes_ar;
	}
	
	/**
	 * Get appointments by week
	 *
	 * @param $date
	 * @param $recruiter_id if you want to get the num of appointments for one recruiter (could be null)
	 *
	 * @return multitype:
	 */
	public function findNumAppointmentsThisYear ($recruiter_id = null) {
	
		$em = $this->getEntityManager();
	
		$currentYear = date('Y',strtotime('now'));
		$nextYear = date('Y',strtotime('next year'));
		
		$query = $em->createQueryBuilder()
		->select('count(a) as num, SUBSTRING(a.startDate,6,2) as month')
		->from('AppointmentBundle:Appointment', 'a')
		->innerJoin('a.appointmentDetail', 'ad')
		->innerJoin('a.recruiter', 'r')
		->where('SUBSTRING(a.startDate,1,4) >= :currentYear')
		->andWhere('SUBSTRING(a.startDate,1,4) < :nextYear')
		->groupBy('month')
	
		->setParameter('currentYear', $currentYear)
		->setParameter('nextYear', $nextYear)
		;
	
		if ($recruiter_id) {
			$query
			->andWhere('r.id = :recruiter_id')
			->setParameter('recruiter_id', $recruiter_id)
			;
		}
	
		$appointmentOutcomes_ar = $query->getQuery()->getResult();
	
		return $appointmentOutcomes_ar;
	}
	
	
	/**
	 * Get The upcoming appointments of a recruiter
	 *
	 * @param int $recruiter_id
	 * @param $currentDate
	 *
	 * @return array Appointments
	 */
	public function findUpcomingAppointmentsByRecruiter($recruiter_id,$currentDate) {
	
		$em = $this->getEntityManager();
	
		$query = $em->createQueryBuilder()
		->select(array(
				'a'
		))
		->from('AppointmentBundle:Appointment', 'a')
		->where('a.recruiter = :recruiter_id')
		->andWhere('a.startDate >= :currentDate')
		->orderBy('a.startDate', 'ASC')
	
		->setParameter('recruiter_id', $recruiter_id)
		->setParameter('currentDate', $currentDate)
		->setMaxResults(3)
		;
	
		$appointment_ar = $query->getQuery()->getResult();
	
		return $appointment_ar;
	}
	
	/**
	 * Get The upcoming appointments
	 *
	 * @param $currentDate
	 *
	 * @return array Appointments
	 */
	public function findUpcomingAppointments($currentDate) {
	
		$em = $this->getEntityManager();
	
		$query = $em->createQueryBuilder()
		->select(array(
				'a'
		))
		->from('AppointmentBundle:Appointment', 'a')
		->where('a.startDate >= :currentDate')
		->orderBy('a.startDate', 'ASC')
	
		->setParameter('currentDate', $currentDate)
		->setMaxResults(3)
		;
	
		$appointment_ar = $query->getQuery()->getResult();
	
		return $appointment_ar;
	}
}
