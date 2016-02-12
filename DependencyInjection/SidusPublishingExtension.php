<?php

namespace Sidus\PublishingBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class SidusPublishingExtension extends Extension
{
    /** @var array */
    protected $globalConfiguration;

    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->createConfigurationParser();
        $this->globalConfiguration = $this->processConfiguration($configuration, $configs);

        $container->setParameter('sidus_eav_publishing.queue.configuration', $this->globalConfiguration['queue']);

        foreach ($this->globalConfiguration['pushers'] as $code => $pusherConfiguration) {
            $this->createPusherService($code, $pusherConfiguration, $container);
        }

        foreach ($this->globalConfiguration['publishers'] as $code => $publisherConfiguration) {
            $this->createPublisherService($code, $publisherConfiguration, $container);
        }

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');
    }

    protected function createPusherService($code, $pusherConfiguration, $container)
    {
    }


    protected function createPublisherService($code, $pusherConfiguration, $container)
    {
    }

    /**
     * Allows the configuration class to be different in inherited classes
     * @return Configuration
     */
    protected function createConfigurationParser()
    {
        return new Configuration();
    }
}
