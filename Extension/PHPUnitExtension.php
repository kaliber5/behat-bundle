<?php

namespace Kaliber5\BehatBundle\Extension;

use Behat\Testwork\ServiceContainer\Extension as ExtensionInterface;
use Behat\Testwork\ServiceContainer\ExtensionManager;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Class PHPUnitExtension
 *
 * loads the PHPUnit Functions.php file to use assertions in the behat tests
 *
 * @package Kaliber5\BehatBundle\Extension
 */
class PHPUnitExtension implements ExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getConfigKey()
    {
        return 'phpunit_assertions_extension';
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(ExtensionManager $extensionManager)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function configure(ArrayNodeDefinition $builder)
    {
        $builder
            ->children()
                ->scalarNode('path')
                    ->info('Path to PHPUnit Framework/assert/Functions.php')
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ;
    }

    /**
     * {@inheritDoc}
     */
    public function load(ContainerBuilder $container, array $config)
    {
        require_once $config['path'];
    }
}
