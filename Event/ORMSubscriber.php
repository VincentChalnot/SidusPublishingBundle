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

    /** @var bool */
    protected $disabled = false;

    /**
     * @param bool $debug
     */
    public function __construct($debug = false)
    {
        $this->debug = $debug;
    }


    public function getSubscribedEvents()
    {
        if ($this->disabled) {
            return [];
        }
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
        if ($this->disabled) {
            return;
        }
        $entity = $args->getObject();
        if (!$entity instanceof PublishableInterface) {
            return;
        }
        foreach ($this->publishers as $publisher) {
            if ($publisher->isSupported($entity)) {
                $publisher->create($entity);
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
    public function postUpdate(LifecycleEventArgs $args)
    {
        if ($this->disabled) {
            return;
        }
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
        if ($this->disabled) {
            return;
        }
        $entity = $args->getObject();
        if (!$entity instanceof PublishableInterface) {
            return;
        }
        foreach ($this->publishers as $publisher) {
            if ($publisher->isSupported($entity)) {
                $publisher->delete($entity);
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
        if ($this->disabled) {
            return;
        }
        foreach ($this->activePublishers as $key => $publisher) {
            $publisher->publish();
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

    /**
     * @return boolean
     */
    public function isDisabled()
    {
        return $this->disabled;
    }

    /**
     * @param boolean $disabled
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;
    }
}
