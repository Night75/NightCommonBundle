<?php

namespace Night\CommonBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class DeleteBoxType extends AbstractType 
{
	
	public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
			 'label' => 'Supprimer le fichier',
			 "attr" => array("class" => "delete_box")
        ));
    }

	public function getParent(){
		return 'checkbox';
	}
	
	public function getName(){
		return 'delete_box';
	}
}
