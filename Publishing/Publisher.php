<?php

namespace Sidus\PublishingBundle\Publishing;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Sidus\PublishingBundle\Entity\PublishableInterface;
use Sidus\PublishingBundle\Event\PublicationEventInterface;
use Sidus\PublishingBundle\Exception\PublicationException;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use UnexpectedValueException;

/**
 * This class handle the publication of entities through configured pushers
 *
 * @author Vincent Chalnot <vincent@sidus.fr>
 */
class Publisher implements PublisherInterface
{
    /** @var string */
    protected $code;

    /** @var string */
    protected $entityName;

    /** @var string */
    protected $format;

    /** @var PusherInterface[] */
    protected $pushers;

    /** @var array */
    protected $options;

    /** @var string */
    protected $queueDirectory;

    /** @var string */
    protected $publicationEventClass;

    /** @var bool */
    protected $enabled;

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param string             $code
     * @param string             $entityName
     * @param string             $format
     * @param ContainerInterface $container
     * @param PusherInterface[]  $pushers
     * @param array              $options
     *
     * @throws UnexpectedValueException
     */
    public function __construct(
        $code,
        $entityName,
        $format,
        ContainerInterface $container,
        array $pushers,
        array $options = []
    ) {
        $this->code = $code;
        $this->entityName = $entityName;
        $this->format = $format;
        $this->container = $container;
        $this->pushers = $pushers;
        $this->options = $options;
        if (!isset($options['queue']['base_directory'])) {
            throw new UnexpectedValueException('The queue.directory option must be set');
        }
        $this->queueDirectory = $options['queue']['base_directory'];
        if (!isset($options['publication_event_class'])) {
            throw new UnexpectedValueException('The publication_event_class option must be set');
        }
        $this->publicationEventClass = $options['publication_event_class'];
        $this->enabled = (bool) $this->options['enabled'];
    }

    /**
     * @param PublishableInterface $entity
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\Serializer\Exception\LogicException
     * @throws \Symfony\Component\Serializer\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Serializer\Exception\CircularReferenceException
     * @throws FileException
     * @throws AccessDeniedException
     */
    public function create(PublishableInterface $entity)
    {
        $this->handlePublication($entity, PublicationEventInterface::CREATE);
    }

    /**
     * @param PublishableInterface $entity
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\Serializer\Exception\LogicException
     * @throws \Symfony\Component\Serializer\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Serializer\Exception\CircularReferenceException
     * @throws FileException
     * @throws AccessDeniedException
     */
    public function update(PublishableInterface $entity)
    {
        $this->handlePublication($entity, PublicationEventInterface::UPDATE);
    }

    /**
     * @param PublishableInterface $entity
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\Serializer\Exception\LogicException
     * @throws \Symfony\Component\Serializer\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Serializer\Exception\CircularReferenceException
     * @throws AccessDeniedException
     * @throws FileException
     */
    public function delete(PublishableInterface $entity)
    {
        $this->handlePublication($entity, PublicationEventInterface::DELETE);
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function publish()
    {
        if (!$this->enabled) {
            return false;
        }
        $eventTypes = [
            PublicationEventInterface::CREATE,
            PublicationEventInterface::UPDATE,
            PublicationEventInterface::DELETE,
        ];

        foreach ($eventTypes as $eventType) {
            $finder = new Finder();
            /** @var \Symfony\Component\Finder\SplFileInfo[] $files */
            $files = $finder
                ->in($this->getBaseDirectory($eventType))
                ->name('*.'.$this->format)
                ->sortByModifiedTime()
                ->files();

            foreach ($files as $file) {
                $publicationUuid = substr($file->getBasename(), 0, -\strlen($this->format) - 1);
                $errorFilePath = "{$this->getBaseDirectory('error')}/{$publicationUuid}.{$this->getFormat()}";

                foreach ($this->getPushers() as $pusher) {
                    try {
                        $pusher->$eventType($publicationUuid, $file->getContents());
                    } /** @noinspection PhpRedundantCatchClauseInspection */ catch (PublicationException $e) {
                        $r = $e->getResponse();
                        if ($r && $r->getContent()) {
                            file_put_contents($errorFilePath, $r->getContent());
                            $e->addMessage("Check {$errorFilePath} for more informations");
                            throw $e;
                        }
                    }
                }

                unlink($file->getRealPath());
                if (file_exists($errorFilePath)) {
                    unlink($errorFilePath);
                }
            }
        }

        return true;
    }

    /**
     * @param mixed $entity
     *
     * @return bool
     */
    public function isSupported($entity)
    {
        return is_a($entity, $this->getEntityName());
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getEntityName()
    {
        return $this->entityName;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @return PusherInterface[]
     */
    public function getPushers()
    {
        return $this->pushers;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param PublishableInterface $entity
     * @param string               $eventName
     *
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
     * @throws \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
     * @throws \Symfony\Component\Serializer\Exception\LogicException
     * @throws \Symfony\Component\Serializer\Exception\InvalidArgumentException
     * @throws \Symfony\Component\Serializer\Exception\CircularReferenceException
     * @throws AccessDeniedException
     *
     * @throws FileException
     */
    protected function handlePublication(PublishableInterface $entity, $eventName)
    {
        if (!$this->enabled) {
            return;
        }
        $class = $this->publicationEventClass;
        $event = new $class($entity, $eventName);

        $serialized = $this->container->get('serializer')->serialize($event, $this->getFormat());
        $f = $this->getFileName($eventName, $entity->getPublicationUuid());
        if (false === file_put_contents($f, $serialized)) {
            throw new FileException("Unable to write to file {$f}");
        }
    }

    /**
     * @param string $eventName
     * @param string $publicationUuid
     *
     * @throws AccessDeniedException
     *
     * @return string
     */
    protected function getFileName($eventName, $publicationUuid)
    {
        return "{$this->getBaseDirectory($eventName)}/{$publicationUuid}.{$this->getFormat()}";
    }

    /**
     * @param string $eventType
     *
     * @throws AccessDeniedException
     *
     * @return string
     */
    protected function getBaseDirectory($eventType = null)
    {
        $directory = rtrim($this->queueDirectory, '/').'/'.$this->getCode();
        if ($eventType) {
            $directory .= '/'.$eventType;
        }
        if (!@mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new AccessDeniedException("Unable to create base directory {$directory}");
        }

        return $directory;
    }
}
