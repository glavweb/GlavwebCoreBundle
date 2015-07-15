<?php

namespace Glavweb\CoreBundle\Manipulator\Sonata;

use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Marek Stipek <mario.dweller@seznam.cz>
 * @author Simon Cosandey <simon.cosandey@simseo.ch>
 */
class ServicesManipulator
{
    /** @var string */
    private $file;

    /** @var string */
    private $template = '    %s:
        class: %s
        arguments: [~, %s, %s]
        tags:
            - {name: sonata.admin, manager_type: %s, group: admin, label: %s, label_translator_strategy: "sonata.admin.label.strategy.underscore"}
        calls:
            - [ setTranslationDomain, ["%s"]]

';



    /**
     * @param string $file
     */
    public function __construct($file)
    {
        $this->file = (string) $file;
    }

    /**
     * @param string $serviceId
     * @param string $modelClass
     * @param string $adminClass
     * @param string $controllerName
     * @param string $managerType
     * @throws \RuntimeException
     */
    public function addResource($serviceId, $modelClass, $adminClass, $controllerName, $managerType)
    {
        $code = "services:\n";

        if (is_file($this->file)) {
            $code = rtrim(file_get_contents($this->file));
            $data = (array) Yaml::parse($code);

            if ($code !== '') {
                $code .= "\n";
            }

            if (array_key_exists('services', $data)) {
                if (array_key_exists($serviceId, (array) $data['services'])) {
                    throw new \RuntimeException(sprintf(
                        'The service "%s" is already defined in the file "%s".',
                        $serviceId,
                        realpath($this->file)
                    ));
                }

                if ($data['services'] !== null) {
                    $code .= "\n";
                }
            } else {
                $code .= $code === '' ? '' : "\n" . "services:\n";
            }
        }

        $normalizedServiceId = str_replace('.', '_', $serviceId);
        $label             = 'dashboard.label_' . $normalizedServiceId;
        $translationDomain = $normalizedServiceId;

        $code .= sprintf(
            $this->template,
            $serviceId,
            $adminClass,
            $modelClass,
            $controllerName,
            $managerType,
            $label,
            $translationDomain
        );
        @mkdir(dirname($this->file), 0777, true);

        if (@file_put_contents($this->file, $code) === false) {
            throw new \RuntimeException(sprintf(
                'Unable to append service "%s" to the file "%s". You will have to do it manually.',
                $serviceId,
                $this->file
            ));
        };
    }
}
