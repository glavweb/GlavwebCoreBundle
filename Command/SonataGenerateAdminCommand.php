<?php

namespace Glavweb\CoreBundle\Command;

use Glavweb\CoreBundle\Generator\Sonata\AdminGenerator;
use Glavweb\CoreBundle\Generator\Sonata\ControllerGenerator;
use Glavweb\CoreBundle\Generator\Sonata\TranslationGenerator;
use Glavweb\CoreBundle\Manipulator\Sonata\ServicesManipulator;
use Sensio\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;
use Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper;
use Sonata\AdminBundle\Command\GenerateAdminCommand;
use Sonata\AdminBundle\Command\Validators;
use Sonata\AdminBundle\Model\ModelManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * @author Marek Stipek <mario.dweller@seznam.cz>
 * @author Simon Cosandey <simon.cosandey@simseo.ch>
 */
class SonataGenerateAdminCommand extends GenerateAdminCommand
{
    /** @var string[] */
    private $managerTypes;

    /**
     * {@inheritDoc}
     */
    public function configure()
    {
        parent::configure();

        $this
            ->setName('glavweb:sonata:admin:generate')
        ;
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $modelClass         = Validators::validateClass($input->getArgument('model'));
        $modelClassBasename = current(array_slice(explode('\\', $modelClass), -1));

        $bundle = $this->getBundle($input->getOption('bundle') ?: $this->getBundleNameFromClass($modelClass));
        $adminClassBasename = $input->getOption('admin') ?: $modelClassBasename . 'Admin';
        $adminClassBasename = Validators::validateAdminClassBasename($adminClassBasename);

        $managerType  = $input->getOption('manager') ?: $this->getDefaultManagerType();
        $modelManager = $this->getModelManager($managerType);

        $skeletonDirectory = __DIR__ . '/../Resources/skeleton/Sonata';
        $adminGenerator = new AdminGenerator($modelManager, $skeletonDirectory);

        $id = $input->getOption('id') ?: $this->getAdminServiceId($bundle->getName(), $adminClassBasename);

        try {
            $adminGenerator->generate($bundle, $adminClassBasename, $modelClass, $id);
            $output->writeln(sprintf(
                '%sThe admin class "<info>%s</info>" has been generated under the file "<info>%s</info>".',
                PHP_EOL,
                $adminGenerator->getClass(),
                realpath($adminGenerator->getFile())
            ));
        } catch (\Exception $e) {
            $this->writeError($output, $e->getMessage());
        }

        if ($controllerClassBasename = $input->getOption('controller')) {
            $controllerClassBasename = Validators::validateControllerClassBasename($controllerClassBasename);
            $controllerGenerator = new ControllerGenerator($skeletonDirectory);

            try {
                $controllerGenerator->generate($bundle, $controllerClassBasename);
                $output->writeln(sprintf(
                    '%sThe controller class "<info>%s</info>" has been generated under the file "<info>%s</info>".',
                    PHP_EOL,
                    $controllerGenerator->getClass(),
                    realpath($controllerGenerator->getFile())
                ));
            } catch (\Exception $e) {
                $this->writeError($output, $e->getMessage());
            }
        }

        if ($servicesFile = $input->getOption('services')) {
            $adminClass = $adminGenerator->getClass();
            $file = sprintf('%s/Resources/config/%s', $bundle->getPath(), $servicesFile);
            $servicesManipulator = new ServicesManipulator($file);

            $controllerName = $controllerClassBasename
                ? sprintf('%s:%s', $bundle->getName(), substr($controllerClassBasename, 0, -10))
                : 'SonataAdminBundle:CRUD'
            ;

            try {
                $servicesManipulator->addResource($id, $modelClass, $adminClass, $controllerName, $managerType);

                $output->writeln(sprintf(
                    '%sThe service "<info>%s</info>" has been appended to the file <info>"%s</info>".',
                    PHP_EOL,
                    $id,
                    realpath($file)
                ));
            } catch (\Exception $e) {
                $this->writeError($output, $e->getMessage());
            }
        }

        // Translation file
        try {
            $translationGenerator = new TranslationGenerator($modelManager, $skeletonDirectory);
            $translationGenerator->generate($bundle, $modelClass, $id);
            $output->writeln(sprintf(
                '%sThe translation file "<info>%s</info>" has been generated.',
                PHP_EOL,
                realpath($controllerGenerator->getFile())
            ));

        } catch (\Exception $e) {
            $this->writeError($output, $e->getMessage());
        }


        return 0;
    }

    /**
     * {@inheritDoc}
     */
    protected function interact(InputInterface $input, OutputInterface $output)
    {
        $questionHelper = $this->getQuestionHelper();
        $questionHelper->writeSection($output, 'Welcome to the Sonata admin generator');
        $modelClass = $this->askAndValidate(
            $input,
            $output,
            'The fully qualified model class',
            $input->getArgument('model'),
            'Sonata\AdminBundle\Command\Validators::validateClass'
        );
        $modelClassBasename = current(array_slice(explode('\\', $modelClass), -1));
        $bundleName = $this->askAndValidate(
            $input,
            $output,
            'The bundle name',
            $input->getOption('bundle') ?: $this->getBundleNameFromClass($modelClass),
            'Sensio\Bundle\GeneratorBundle\Command\Validators::validateBundleName'
        );
        $adminClassBasename = $this->askAndValidate(
            $input,
            $output,
            'The admin class basename',
            $input->getOption('admin') ?: $modelClassBasename . 'Admin',
            'Sonata\AdminBundle\Command\Validators::validateAdminClassBasename'
        );

        if (count($this->getAvailableManagerTypes()) > 1) {
            $managerType = $this->askAndValidate(
                $input,
                $output,
                'The manager type',
                $input->getOption('manager') ?: $this->getDefaultManagerType(),
                array($this, 'validateManagerType')
            );
            $input->setOption('manager', $managerType);
        }

        if ($this->askConfirmation($input, $output, 'Do you want to generate a controller', 'no', '?')) {
            $controllerClassBasename = $this->askAndValidate(
                $input,
                $output,
                'The controller class basename',
                $input->getOption('controller') ?: $modelClassBasename . 'AdminController',
                'Sonata\AdminBundle\Command\Validators::validateControllerClassBasename'
            );
            $input->setOption('controller', $controllerClassBasename);
        }

        if ($this->askConfirmation($input, $output, 'Do you want to update the services YAML configuration file', 'yes', '?')) {
            $path = $this->getBundle($bundleName)->getPath() . '/Resources/config/';
            $servicesFile = $this->askAndValidate(
                $input,
                $output,
                'The services YAML configuration file',
                is_file($path . 'admin.yml') ? 'admin.yml' : 'services.yml',
                'Sonata\AdminBundle\Command\Validators::validateServicesFile'
            );
            $id = $this->askAndValidate(
                $input,
                $output,
                'The admin service ID',
                $this->getAdminServiceId($bundleName, $adminClassBasename),
                'Sonata\AdminBundle\Command\Validators::validateServiceId'
            );
            $input->setOption('services', $servicesFile);
            $input->setOption('id', $id);
        }

        $input->setArgument('model', $modelClass);
        $input->setOption('admin', $adminClassBasename);
        $input->setOption('bundle', $bundleName);
    }

    /**
     * @param string $managerType
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function validateManagerType($managerType)
    {
        $managerTypes = $this->getAvailableManagerTypes();

        if (!isset($managerTypes[$managerType])) {
            throw new \InvalidArgumentException(sprintf(
                'Invalid manager type "%s". Available manager types are "%s".',
                $managerType,
                implode('", "', $managerTypes)
            ));
        }

        return $managerType;
    }

    /**
     * @param string $class
     *
     * @return string|null
     *
     * @throws \InvalidArgumentException
     */
    private function getBundleNameFromClass($class)
    {
        $application = $this->getApplication();
        /* @var $application Application */

        foreach ($application->getKernel()->getBundles() as $bundle) {
            if (strpos($class, $bundle->getNamespace() . '\\') === 0) {
                return $bundle->getName();
            };
        }

        return null;
    }

    /**
     * @param string $name
     *
     * @return BundleInterface
     */
    private function getBundle($name)
    {
        return $this->getKernel()->getBundle($name);
    }

    /**
     * @param OutputInterface $output
     * @param string $message
     */
    private function writeError(OutputInterface $output, $message)
    {
        $output->writeln(sprintf("\n<error>%s</error>", $message));
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @param string $questionText
     * @param mixed $default
     * @param callable $validator
     *
     * @return mixed
     */
    private function askAndValidate(InputInterface $input, OutputInterface $output, $questionText, $default, $validator)
    {
        $questionHelper = $this->getQuestionHelper();

        if ($questionHelper instanceof DialogHelper) {
            // @todo remove this BC code for SensioGeneratorBundle 2.3/2.4 after dropping  support for Symfony 2.3

            return $questionHelper->askAndValidate($output, $questionHelper->getQuestion($questionText, $default), $validator, false, $default);
        }

        $question = new Question($questionHelper->getQuestion($questionText, $default), $default);

        $question->setValidator($validator);

        return $questionHelper->ask($input, $output, $question);
    }

    private function askConfirmation(InputInterface $input, OutputInterface $output, $questionText, $default, $separator)
    {
        $questionHelper = $this->getQuestionHelper();

        if ($questionHelper instanceof DialogHelper) {
            // @todo remove this BC code for SensioGeneratorBundle 2.3/2.4 after dropping  support for Symfony 2.3
             $question = $questionHelper->getQuestion($questionText, $default, $separator);

             return $questionHelper->askConfirmation($output, $question, ($default === 'no' ? false : true));
        }

        $question = new ConfirmationQuestion($questionHelper->getQuestion(
            $questionText,
            $default,
            $separator
        ), ($default === 'no' ? false : true));

        return $questionHelper->ask($input, $output, $question);
    }

    /**
     * @return string
     *
     * @throws \RuntimeException
     */
    private function getDefaultManagerType()
    {
        if (!$managerTypes = $this->getAvailableManagerTypes()) {
            throw new \RuntimeException('There are no model managers registered.');
        }

        return current($managerTypes);
    }

    /**
     * @param string $managerType
     *
     * @return ModelManagerInterface
     */
    private function getModelManager($managerType)
    {
        return $this->getContainer()->get('sonata.admin.manager.' . $managerType);
    }

    /**
     * @param string $bundleName
     * @param string $adminClassBasename
     *
     * @return string
     */
    private function getAdminServiceId($bundleName, $adminClassBasename)
    {
        $suffix = substr($adminClassBasename, -5) == 'Admin' ? substr($adminClassBasename, 0, -5) : $adminClassBasename;
        $suffix = str_replace('\\', '.', $suffix);

        return Container::underscore($suffix);
    }

    /**
     * @return string[]
     */
    private function getAvailableManagerTypes()
    {
        $container = $this->getContainer();

        if (!$container instanceof Container) {
            return array();
        }

        if ($this->managerTypes === null) {
            $this->managerTypes = array();

            foreach ($container->getServiceIds() as $id) {
                if (strpos($id, 'sonata.admin.manager.') === 0) {
                    $managerType = substr($id, 21);
                    $this->managerTypes[$managerType] = $managerType;
                }
            }
        }

        return $this->managerTypes;
    }

    /**
     * @return KernelInterface
     */
    private function getKernel()
    {
        $application = $this->getApplication();
        /* @var $application Application */

        return $application->getKernel();
    }

    /**
     * @return QuestionHelper|DialogHelper
     */
    private function getQuestionHelper()
    {
        if (class_exists('Sensio\Bundle\GeneratorBundle\Command\Helper\DialogHelper')) {
            // @todo remove this BC code for SensioGeneratorBundle 2.3/2.4 after dropping  support for Symfony 2.3
            $questionHelper = $this->getHelper('dialog');

            if (!$questionHelper instanceof DialogHelper) {
                $questionHelper = new DialogHelper();
                $this->getHelperSet()->set($questionHelper);
            }
        } else {
            $questionHelper = $this->getHelper('question');

            if (!$questionHelper instanceof QuestionHelper) {
                $questionHelper = new QuestionHelper();
                $this->getHelperSet()->set($questionHelper);
            }

        }

        return $questionHelper;
    }
}
