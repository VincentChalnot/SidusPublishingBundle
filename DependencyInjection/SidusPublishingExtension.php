<?php

namespace Sidus\PublishingBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\BadMethodCallException;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

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
     * @param array $configs
     * @param ContainerBuilder $container
     * @throws \Exception
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

    /**
     * @param string $code
     * @param array $pusherConfiguration
     * @param ContainerBuilder $container
     * @throws BadMethodCallException
     */
    protected function createPusherService($code, array $pusherConfiguration, ContainerBuilder $container)
    {
        $definition = new Definition(new Parameter('sidus_eav_publishing.pusher.default.class'), [
            $code,
            $pusherConfiguration['url'],
            $pusherConfiguration['options'],
        ]);
        $definition->addTag('sidus.pusher');
        $sId = 'sidus_eav_publishing.pusher.'.$code;
        $container->setDefinition($sId, $definition);
    }


    /**
     * @param string $code
     * @param array $publisherConfiguration
     * @param ContainerBuilder $container
     * @throws BadMethodCallException
     */
    protected function createPublisherService($code, array $publisherConfiguration, ContainerBuilder $container)
    {
        $options = array_merge(['queue' => $this->globalConfiguration['queue']], $publisherConfiguration['options']);
        $definition = new Definition(new Parameter('sidus_eav_publishing.publisher.default.class'), [
            $code,
            $publisherConfiguration['entity'],
            $publisherConfiguration['format'],
            $publisherConfiguration['serializer'],
            $publisherConfiguration['pushers'],
            $options,
        ]);
        $definition->addTag('sidus.publisher');
        $sId = 'sidus_eav_publishing.publisher.'.$code;
        $container->setDefinition($sId, $definition);
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
