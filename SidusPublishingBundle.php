<?php

namespace Sidus\PublishingBundle;

use Sidus\PublishingBundle\DependencyInjection\Compiler\GenericCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class SidusPublishingBundle extends Bundle
{
    /**
     * Adding compiler passes to inject services into configuration handlers
     *
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new GenericCompilerPass(
            'sidus_eav_publishing.doctrine_orm.subscriber',
            'sidus.publisher',
            'addPublisher'));
    }
}
