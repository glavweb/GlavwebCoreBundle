<?php

namespace Glavweb\CoreBundle\Form\Type;

use Braincrafted\Bundle\BootstrapBundle\Form\Type\FormStaticControlType;
use Symfony\Component\Form\AbstractType;

/**
 * Class FormStaticControlRawType
 * @package GlavwebCoreBundle\Form\Type
 */
class FormStaticControlRawType extends FormStaticControlType
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'bs_static_raw';
    }
}
