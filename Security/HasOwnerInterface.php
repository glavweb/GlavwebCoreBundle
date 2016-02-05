<?php

namespace Glavweb\CoreBundle\Security;

/**
 * Interface HasOwnerInterface
 * @package Glavweb\CoreBundle\Security
 */
interface HasOwnerInterface
{
    /**
     * Return array of owner fields
     *
     * @return array
     */
    public static function getOwnerFields();
}