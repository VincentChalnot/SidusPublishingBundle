<?php

namespace Sidus\PublishingBundle\Command;

use Sidus\PublishingBundle\Publishing\Publisher;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PushPublicationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sidus:publishing:push')
            ->setDescription('Push publications waiting in queue on configured remotes');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Publisher $publisher */
        $publisher = $this->getContainer()->get('sidus_eav_publishing.publisher.data');
        $publisher->publish();
    }
}