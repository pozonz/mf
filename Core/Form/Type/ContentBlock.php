<?php
namespace MillenniumFalcon\Core\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class ContentBlock extends AbstractType {

	public function getBlockPrefix() {
		return 'content_block';
	}

    public function configureOptions(OptionsResolver $resolver) {
        $resolver->setDefaults(array(
            'compound' => false,
        ));
    }
}
