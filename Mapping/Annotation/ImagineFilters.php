<?php

namespace Glavweb\CoreBundle\Mapping\Annotation;

/**
 * Class ImagineFilters
 * @package Glavweb\UploaderBundle\Mapping\Annotation
 *
 * @Annotation
 * @Target({"PROPERTY"})
 */
class ImagineFilters
{
    /**
     * @var array
     */
    public $filters;

    /**
     * @var string
     */
    public $property;

    /**
     * @return array
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @return string
     */
    public function getProperty()
    {
        return $this->property;
    }
}
