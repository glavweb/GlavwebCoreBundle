<?php

namespace Glavweb\CoreBundle\Generator\Sonata;

use Sensio\Bundle\GeneratorBundle\Generator\Generator;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * @author Marek Stipek <mario.dweller@seznam.cz>
 * @author Simon Cosandey <simon.cosandey@simseo.ch>
 */
class TranslationGenerator extends Generator
{
    /** @var ModelManagerInterface */
    private $modelManager;

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
     * @throws \RuntimeException
     */
    public function generate(BundleInterface $bundle, $modelClass, $serviceId)
    {
        $normalizedServiceId = str_replace('.', '_', $serviceId);
        $this->file  = sprintf('%s/Resources/translations/%s.ru.yml', $bundle->getPath(), $normalizedServiceId);

        if (file_exists($this->file)) {
            throw new \RuntimeException(sprintf(
                'Unable to generate the admin class "%s". The file "%s" already exists.',
                $this->class,
                realpath($this->file)
            ));
        }

        /** @var \Doctrine\ORM\Mapping\ClassMetadata $metadata */
        $metadata = $this->modelManager->getEntityManager($modelClass)->getClassMetadata($modelClass);

        $fields = array();
        $fieldNames = $metadata->getFieldNames();
        foreach ($fieldNames as $fieldName) {
            $fieldMapping = $metadata->getFieldMapping($fieldName);
            $columnName = $fieldMapping['columnName'];
            $translate = isset($fieldMapping['options']['comment']) ?
                $fieldMapping['options']['comment'] :
                str_replace('_', ' ', ucfirst($columnName))
            ;

            $fields[$columnName] = $translate;
        }

        $this->renderFile('translation.yml.twig', $this->file, array(
            'normalizedServiceId' => $normalizedServiceId,
            'fields' => $fields
        ));
    }

    /**
     * @return string|null
     */
    public function getFile()
    {
        return $this->file;
    }
}
