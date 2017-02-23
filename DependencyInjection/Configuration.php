<?php

namespace Sidus\PublishingBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Builder\NodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\DependencyInjection\Parameter;
use Symfony\Component\DependencyInjection\Reference;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link
 * http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    protected $root;

    /**
     * @param string $root
     */
    public function __construct($root = 'sidus_publishing')
    {
        $this->root = $root;
    }

    /**
     * {@inheritdoc}
     * @throws \RuntimeException
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root($this->root);
        $rootNode
            ->children()
            ->booleanNode('enabled')->defaultTrue()->end()
            ->scalarNode('publication_event_class')->defaultValue('Sidus\PublishingBundle\Event\PublicationEvent')->end(
            )
            ->append($this->getQueueTreeBuilder())
            ->append($this->getPublishersTreeBuilder())
            ->end();

        return $treeBuilder;
    }

    /**
     * @return NodeDefinition
     * @throws \RuntimeException
     */
    protected function getQueueTreeBuilder()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('queue');
        $node
            ->isRequired()
            ->children()
            ->scalarNode('base_directory')->isRequired()->end()
            ->integerNode('override_timeout')->defaultValue(120)->end()
            ->scalarNode('lockfile')->defaultValue('.lock')->end()
            ->end();

        return $node;
    }

    /**
     * @return NodeDefinition
     * @throws \RuntimeException
     */
    protected function getPublishersTreeBuilder()
    {
        $builder = new TreeBuilder();
        $node = $builder->root('publishers');
        $dataGridDefinition = $node
            ->useAttributeAsKey('code')
            ->prototype('array')
            ->performNoDeepMerging()
            ->children();

        $this->appendPublisherDefinition($dataGridDefinition);

        $dataGridDefinition->end()
            ->end()
            ->end();

        return $node;
    }

    /**
     * @param NodeBuilder $dataGridDefinition
     */
    protected function appendPublisherDefinition(NodeBuilder $dataGridDefinition)
    {
        $dataGridDefinition
            ->scalarNode('entity')->isRequired()->end()
            ->scalarNode('format')->isRequired()->end()
            ->scalarNode('class')->defaultValue(new Parameter('sidus_eav_publishing.publisher.generic'))->end()
            ->scalarNode('serializer')->defaultValue(new Reference('serializer'))->end()
            ->arrayNode('pushers')
            ->prototype('scalar')->end()
            ->end()
            ->variableNode('options')->defaultValue([])->end();
    }
}
