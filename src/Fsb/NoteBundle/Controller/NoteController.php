<?php

namespace Fsb\NoteBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Fsb\NoteBundle\Entity\Note;
use Fsb\NoteBundle\Form\NoteType;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Fsb\UserBundle\Util\Util;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * Note controller.
 *
 */
class NoteController extends Controller
{

    /**
     * Lists all Note entities.
     *
     */
    public function indexAction()
    {
        $em = $this->getDoctrine()->getManager();

        $entities = $em->getRepository('NoteBundle:Note')->findAll();

        return $this->render('NoteBundle:Note:index.html.twig', array(
            'entities' => $entities,
        ));
    }
    /**
     * Creates a new Note entity.
     *
     */
    public function createAction(Request $request)
    {
    	$userLogged = $this->get('security.context')->getToken()->getUser();
    	 
    	if (!$userLogged) {
    		throw $this->createNotFoundException('Unable to find this user.');
    	}
    	
        $entity = new Note();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            
            Util::setCreateAuditFields($entity, $userLogged->getId());
            
            $em->persist($entity);
            $em->flush();
            
            $this->get('session')->getFlashBag()->set(
            		'success',
            		array(
            				'title' => 'Note Created!',
            				'message' => 'The note has been created'
            		)
            );

            $url = $this->getRequest()->headers->get("referer");
            return new RedirectResponse($url);
        }

        return $this->render('NoteBundle:Note:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
    * Creates a form to create a Note entity.
    *
    * @param Note $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createCreateForm(Note $entity)
    {
        $form = $this->createForm(new NoteType(), $entity, array(
            'action' => $this->generateUrl('note_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array(
        		'label' => 'Create',
        		'attr' => array('class' => 'ui-btn ui-corner-all ui-shadow ui-btn-b ui-btn-icon-left ui-icon-check')
        ));

        return $form;
    }

    /**
     * Displays a form to create a new Note entity.
     *
     */
    public function newAction()
    {
        $entity = new Note();
        $form   = $this->createCreateForm($entity);

        return $this->render('NoteBundle:Note:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }
    
    /**
     * Displays a form to create a new Note entity by recruiter.
     *
     * @param $recruiter_id
     *
     */
    public function newByRecruiterIdAction($hour, $minute, $day, $month, $year, $id)
    {
    	$userLogged = $this->get('security.context')->getToken()->getUser();
    	 
    	$em = $this->getDoctrine()->getManager();
    	 
    	//Get the recruiter if exist
    	$recruiter = $em->getRepository('UserBundle:User')->find($id);
    	
    	if (!$recruiter) {
    		throw $this->createNotFoundException('Unable to find Recruiter entity.');
    	}
    	 
    	//Check if the recruiter is trying to access to the note of another user
    	if ($this->get('security.context')->isGranted('ROLE_RECRUITER')) {
    		if ($userLogged != $recruiter) {
    			throw new AccessDeniedException();
    		}
    	}
    	
    	$entity = new Note();
    	
    	$date = new \DateTime($day.'-'.$month.'-'.$year.' '.$hour.':'.$minute.':00');
    	
    	$endDate = new \DateTime($day.'-'.$month.'-'.$year.' '.$hour.':'.$minute.':00');
    	$endDate->modify('+1 hour');
    	
    	$entity->setStartDate($date);
    	$entity->setEndDate($endDate);
    	
    	
    	$entity->setRecruiter($recruiter);
    	$form   = $this->createCreateForm($entity);
    
    	return $this->render('NoteBundle:Note:new.html.twig', array(
    			'entity' => $entity,
    			'form'   => $form->createView(),
    	));
    }

    /**
     * Finds and displays a Note entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('NoteBundle:Note')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Note entity.');
        }

        $deleteForm = $this->createDeleteForm($id);

        return $this->render('NoteBundle:Note:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),        ));
    }

    /**
     * Displays a form to edit an existing Note entity.
     *
     */
    public function editAction($id)
    {
    	$userLogged = $this->get('security.context')->getToken()->getUser();
    	
    	if (!$userLogged) {
    		throw $this->createNotFoundException('Unable to find this user.');
    	}
    	
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('NoteBundle:Note')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Note entity.');
        }
        
        //Check if the recruiter is trying to access to the note of another user
        if ($this->get('security.context')->isGranted('ROLE_RECRUITER')) {
        	if ($userLogged != $entity->getRecruiter()) {
        		throw new AccessDeniedException();
        	}
        }

        
        
        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('NoteBundle:Note:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
    * Creates a form to edit a Note entity.
    *
    * @param Note $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Note $entity)
    {
        $form = $this->createForm(new NoteType(), $entity, array(
            'action' => $this->generateUrl('note_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array(
        		'label' => 'Update',
        		'attr' => array('class' => 'ui-btn ui-corner-all ui-shadow ui-btn-b ui-btn-icon-left ui-icon-edit')
        ));

        return $form;
    }
    /**
     * Edits an existing Note entity.
     *
     */
    public function updateAction(Request $request, $id)
    {
    	$userLogged = $this->get('security.context')->getToken()->getUser();
    	 
    	if (!$userLogged) {
    		throw $this->createNotFoundException('Unable to find this user.');
    	}
    	
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('NoteBundle:Note')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Note entity.');
        }
        
        //Check if the recruiter is trying to access to the note of another user
        if ($this->get('security.context')->isGranted('ROLE_RECRUITER')) {
        	if ($userLogged != $entity->getRecruiter()) {
        		throw new AccessDeniedException();
        	}
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        
        $editForm->handleRequest($request);
        
        $editForm->submit($request);

        if ($editForm->isValid()) {
        	
        	Util::setModifyAuditFields($entity, $userLogged->getId());
        	
        	$em->persist($entity);
        	
            $em->flush();

            $this->get('session')->getFlashBag()->set(
            		'success',
            		array(
            				'title' => 'Note Changed!',
            				'message' => 'The note has been updated'
            		)
            );
            
            $url = $this->getRequest()->headers->get("referer");
    		return new RedirectResponse($url);
        }

        return $this->render('NoteBundle:Note:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    /**
     * Deletes a Note entity.
     *
     */
    public function deleteAction(Request $request, $id)
    {
    	$userLogged = $this->get('security.context')->getToken()->getUser();
    	
    	if (!$userLogged) {
    		throw $this->createNotFoundException('Unable to find this user.');
    	}
    	
    	$em = $this->getDoctrine()->getManager();
    	$entity = $em->getRepository('NoteBundle:Note')->find($id);
    	
    	if (!$entity) {
    		throw $this->createNotFoundException('Unable to find Note entity.');
    	}
    	
    	//Check if the recruiter is trying to access to the note of another user
    	if ($this->get('security.context')->isGranted('ROLE_RECRUITER')) {
    		if ($userLogged != $entity->getRecruiter()) {
    			throw new AccessDeniedException();
    		}
    	}
    	
    	$form = $this->createDeleteForm($id);
        $form->handleRequest($request);

        if ($form->isValid()) {
        	
            $em->remove($entity);
            $em->flush();
            
            $this->get('session')->getFlashBag()->set(
            		'success',
            		array(
            				'title' => 'Note Deleted!',
            				'message' => 'The note has been deleted'
            		)
            );
        }

        $url = $this->getRequest()->headers->get("referer");
        return new RedirectResponse($url);
    }

    /**
     * Creates a form to delete a Note entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('note_delete', array('id' => $id)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array(
            		'label' => 'Delete',
            		'attr' => array('class' => 'ui-btn ui-corner-all ui-shadow ui-btn-b ui-btn-icon-left ui-icon-delete')
            ))
            ->getForm()
        ;
    }
}
