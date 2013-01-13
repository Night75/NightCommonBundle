<?php

namespace Night\CommonBundle\Form\Extension;

use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\Util\PropertyPath;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class FormTypeExtension extends AbstractTypeExtension 
{

	protected $properties_options = array(
			"",
		);
	/**
	 * Returns the name of the type being extended.
	 *
	 * @return string The name of the type being extended
	 */
	public function getExtendedType() {
		return 'form';
	}

	/**
	 * Add the image_path option
	 *
	 * @param \Symfony\Component\OptionsResolver\OptionsResolverInterface $resolver
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setOptional(array( "no_label", "no_sublabel"));
	}

	/**
	 * Pass the image url to the view
	 *
	 * @param \Symfony\Component\Form\FormView $view
	 * @param \Symfony\Component\Form\FormInterface $form
	 * @param array $options
	 */
	public function buildView(FormView $view, FormInterface $form, array $options) {
		// ===== no_label
		$value = false;
		if (array_key_exists("no_label", $options)) {
			$value = $options["no_label"];
		}
		$view->set("no_label", $value);
		
		// ===== no_sublabel
		$value = false;
		if (array_key_exists("no_sublabel", $options)) {
			$value = $options["no_sublabel"];
		}
		$view->set("no_sublabel", $value);
	}

	
}