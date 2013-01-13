<?php

namespace Night\CommonBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Util\PropertyPath;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CollectionTypeExtension extends AbstractTypeExtension {

	/**
	 * Returns the name of the type being extended.
	 *
	 * @return string The name of the type being extended
	 */
	public function getExtendedType() {
		return 'collection';
	}

	/**
	 * Add the image_path option
	 *
	 * @param \Symfony\Component\OptionsResolver\OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setOptional(array('clean_labels'));
	}

	/**
	 * Pass the image url to the view
	 *
	 * @param \Symfony\Component\Form\FormView $view
	 * @param \Symfony\Component\Form\FormInterface $form
	 * @param array $options
	 */
	public function buildView(FormView $view, FormInterface $form, array $options) {
		$value = false;
		
		if (array_key_exists('clean_labels', $options)) {
			$parentData = $form->getParent()->getData();
				
			if (null !== $parentData) {
				// Le constructeur de PropertyPath n'accepte que des chaines de caracteres
				if(!is_bool($options['clean_labels'])){
					throw new \UnexpectedValueException("La valeur de clean_labels doit être un booléen");		
				} 
				$value = $options['clean_labels'];
			}
		}
		$view->set('clean_labels', $value);
	}
	
}