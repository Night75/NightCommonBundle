<?php

namespace Night\CommonBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Util\PropertyPath;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FileTypeExtension extends AbstractTypeExtension 
{

	protected $properties_options = array(
			"filename",
		);
	/**
	 * Returns the name of the type being extended.
	 *
	 * @return string The name of the type being extended
	 */
	public function getExtendedType() {
		return 'file';
	}

	/**
	 * Add the image_path option
	 *
	 * @param \Symfony\Component\OptionsResolver\OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setOptional($this->properties_options);
	}

	/**
	 * Pass the image url to the view
	 *
	 * @param \Symfony\Component\Form\FormView $view
	 * @param \Symfony\Component\Form\FormInterface $form
	 * @param array $options
	 */
	public function buildView(FormView $view, FormInterface $form, array $options) {
		
		foreach($this->properties_options as $opt){
			$value = null;
			
			if (array_key_exists($opt, $options)) {
				$parentData = $form->getParent()->getData();

				if (null !== $parentData) {
					// Le constructeur de PropertyPath n'accepte que des chaines de caracteres
					if(!is_string($options[$opt])){
						throw new \UnexpectedValueException(sprintf(
							"La valeur attribuée à l'option filename doit être une proprété de l'entité %s.", get_class($parentData)
						));
					} 

					try{		
						$propertyPath = new PropertyPath($options[$opt]);
						$value = $propertyPath->getValue($parentData);
					}
					catch(\Exception $e){
						throw new \UnexpectedValueException(sprintf(
							"La valeur attribuée à l'option doit être une proprété de l'entité %s.", get_class($parentData)
						));
					}	
				} 

				$view->set($opt, $value);
			} // Fin de if -- array_key_exists
		} // Fin du foreach
	
	}

	
}