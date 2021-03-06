<?php
// src/Cituao/ExternoBundle/Form/Type/HojadevidaType.php
namespace Ingenieria\UsuarioBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Doctrine\ORM\EntityRepository;

class GrupoActualizarType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
			
		//$qb->leftJoin('u.Phonenumbers', 'p', 'WITH', 'p.area_code = 55')
        $builder
		->add('nombre','text', array('label' => 'Nombre:' , 'required' => true));
/*   
		->add('file')
		->add('tutor', 'entity', array('class' => 'IngenieriaProfesorBundle:Profesor', 'choices' => $this->profesores, 'empty_value' => 'Seleccione?'));
										
		/*	
		->add('tutor', 'entity', array('class' => 'IngenieriaProfesorBundle:Profesor',
										'query_builder' => function(EntityRepository $er) {
											return $er->createQueryBuilder('p')->orderBy('p.nombres', 'ASC');},
												'empty_value' => 'Seleccione?'));*/
		}

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'Ingenieria\DirectorBundle\Entity\Grupo', 'cascade_validation' => true
        ));
    }

    public function getName()
    {
        return 'grupo';
    }
}
