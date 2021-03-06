<?php

namespace Fsb\RuleBundle\Form\Rule;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;

class RuleType extends AbstractType
{
        /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('rule')
            ->add('description')
            ->add('recruiter', 'entity', array(
            		'class'         => 'Fsb\\UserBundle\\Entity\\User',
            		'empty_value'   => 'Select a recruiter',
            		'query_builder' => function(EntityRepository $repository) {
            			return $repository->findUsersByRoleQuery('ROLE_RECRUITER');
            		},
            ))
        ;
    }
    
    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Fsb\RuleBundle\Entity\Rule'
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'fsb_rulebundle_rule';
    }
}
