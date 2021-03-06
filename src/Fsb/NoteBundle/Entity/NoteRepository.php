<?php

namespace Fsb\NoteBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * NoteRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class NoteRepository extends EntityRepository
{
	
	
	/**
	 * Return the notes in a day for a recruiter
	 *
	 * @return boolean
	 *
	 */
	public function findNotesByDay(\DateTime $day, $recruiter_id = null){
	
		$eManager = $this->getEntityManager();
	
		$query = $eManager->createQueryBuilder()
		->select(array('n.startDate', 'n.endDate', 'n as note'))
		->from('NoteBundle:Note', 'n')
		->where('SUBSTRING(n.startDate,1,10) = :day')
		->orderBy('n.startDate', 'ASC')
	
		->setParameter('day', $day->format('Y-m-d'))
		;
		
		if ($recruiter_id) {
			$query
			->andWhere('n.recruiter = :recruiter_id')
			->setParameter('recruiter_id', $recruiter_id);
		}
	
		$NoteList = $query->getQuery()->getResult();
	
		return $NoteList;
	}
}
