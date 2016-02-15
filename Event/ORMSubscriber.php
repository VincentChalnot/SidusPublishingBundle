<?php

namespace Sidus\PublishingBundle\Event;

use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Events;
use Sidus\PublishingBundle\Entity\PublishableInterface;
use Sidus\PublishingBundle\Publishing\PublisherInterface;

class ORMSubscriber implements EventSubscriber
{
    /** @var PublisherInterface[] */
    protected $publishers = [];

    /** @var PublisherInterface[] */
    protected $activePublishers = [];

    /** @var bool */
    protected $debug;

    /**
     * @param bool $debug
     */
    public function __construct($debug = false)
    {
        $this->debug = $debug;
    }


    public function getSubscribedEvents()
    {
        return [
            Events::postPersist,
            Events::postUpdate,
            Events::postRemove,
        ];
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postPersist(LifecycleEventArgs $args)
    {
        $this->postUpdate($args);
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postUpdate(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$entity instanceof PublishableInterface) {
            return;
        }
        foreach ($this->publishers as $publisher) {
            if ($publisher->isSupported($entity)) {
                $publisher->update($entity);
                $this->activePublishers[] = $publisher;
            }
        }
        if ($this->debug) {
            $this->commit();
        }
    }

    /**
     * @param LifecycleEventArgs $args
     */
    public function postRemove(LifecycleEventArgs $args)
    {
        $entity = $args->getObject();
        if (!$entity instanceof PublishableInterface) {
            return;
        }
        foreach ($this->publishers as $publisher) {
            if ($publisher->isSupported($entity)) {
                $publisher->remove($entity);
                $this->activePublishers[] = $publisher;
            }
        }
        if ($this->debug) {
            $this->commit();
        }
    }

    /**
     * Push all entities on remote and reset publishers
     */
    public function commit()
    {
        foreach ($this->activePublishers as $key => $publisher) {
            $publisher->push();
        }
        $this->activePublishers = [];
    }

    /**
     * Trigger commit on kernel terminate
     */
    public function onKernelTerminate()
    {
        if (!$this->debug) {
            $this->commit();
        }
    }

    /**
     * @return PublisherInterface[]
     */
    public function getPublishers()
    {
        return $this->publishers;
    }

    /**
     * @param PublisherInterface $publisher
     */
    public function addPublisher(PublisherInterface $publisher)
    {
        $this->publishers[] = $publisher;
    }
}