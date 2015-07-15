<?php

namespace Glavweb\CoreBundle\Generator\Sonata;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * @author Marek Stipek <mario.dweller@seznam.cz>
 * @author Simon Cosandey <simon.cosandey@simseo.ch>
 */
class AdminGenerator extends Generator
{
    /** @var ModelManagerInterface */
    private $modelManager;

    /** @var string|null */
    private $class;

    /** @var string|null */
    private $file;

    /**
     * @param ModelManagerInterface $modelManager
     * @param array|string $skeletonDirectories
     */
    public function __construct(ModelManagerInterface $modelManager, $skeletonDirectories)
    {
        $this->modelManager = $modelManager;
        $this->setSkeletonDirs($skeletonDirectories);
    }

    /**
     * @param BundleInterface $bundle
     * @param string $adminClassBasename
     * @param string $modelClass
     * @param $serviceId
     */
    public function generate(BundleInterface $bundle, $adminClassBasename, $modelClass, $serviceId)
    {
        $this->class = sprintf('%s\Admin\%s', $bundle->getNamespace(), $adminClassBasename);
        $this->file = sprintf('%s/Admin/%s.php', $bundle->getPath(), str_replace('\\', '/', $adminClassBasename));
        $parts = explode('\\', $this->class);

        if (file_exists($this->file)) {
            throw new \RuntimeException(sprintf(
                'Unable to generate the admin class "%s". The file "%s" already exists.',
                $this->class,
                realpath($this->file)
            ));
        }

        $normalizedServiceId = str_replace('.', '_', $serviceId);
        $baseRoutePattern = str_replace('_', '-', $normalizedServiceId);
        $baseRouteName = $normalizedServiceId;

        $this->renderFile('Admin.php.twig', $this->file, array(
            'classBasename'    => array_pop($parts),
            'namespace'        => implode('\\', $parts),
            'fields'           => $this->modelManager->getExportFields($modelClass),
            'baseRoutePattern' => $baseRoutePattern,
            'baseRouteName'    => $baseRouteName
        ));
    }

    /**
     * @return string|null
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @return string|null
     */
    public function getFile()
    {
        return $this->file;
    }
}
