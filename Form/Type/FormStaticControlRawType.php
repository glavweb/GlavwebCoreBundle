<?php

namespace Glavweb\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class FormStaticControlRawType
 * @package GlavwebCoreBundle\Form\Type
 */
class FormStaticControlRawType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'required' => false,
            'disabled' => true,
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'bs_static_raw';
    }
    
    /**
     * Compatibility with Symfony 2
     */
    public function getName()
    {
        return 'bs_static_raw';
    }    
}
