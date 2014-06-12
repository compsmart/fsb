<?php

namespace Fsb\UserBundle\Entity;

use Doctrine\ORM\EntityRepository;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends EntityRepository
{
	/**
	 * Get All the users by a role
	 *
	 * @param Role $role Get the Users  by a role
	 *
	 * @return array Recruiters
	 */
	public function findUsersByRole($role){
	
		$query = $this->findUsersByRoleQuery($role);
		
		$user_ar = $query->getQuery()->getResult();
	
		return $user_ar;
	}
	
	/**
	 * Get the Query to find all the users by a role
	 *
	 * @param Role $role Get the Users  by a role
	 *
	 * @return array Recruiters
	 */
	public function findUsersByRoleQuery($role)
	{
		$em = $this->getEntityManager();
	
		$query = $em->createQueryBuilder()
			->select('u')
			->from('UserBundle:User', 'u')
			->innerJoin('u.role', 'ur')
			->where('ur.name = :role')
			->orderBy('ur.name', 'ASC')
			->setParameter('role', $role)	
		;
		
		return $query;
	}
}
