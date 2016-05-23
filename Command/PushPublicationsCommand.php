<?php

namespace Sidus\PublishingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This is more an example than a full-featured command
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class PushPublicationsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this->setName('sidus:publishing:push')
            ->setDescription('Push publications waiting in queue on configured remotes');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $publishers = $this->getContainer()->get('sidus_eav_publishing.doctrine_orm.subscriber')->getPublishers();
        foreach ($publishers as $publisher) {
            $publisher->publish();
        }
    }
}
