<?php

namespace Sidus\PublishingBundle\Publishing;

use JMS\Serializer\SerializerInterface;
use Sidus\PublishingBundle\Entity\PublishableInterface;
use Sidus\PublishingBundle\Event\PublicationEvent;
use Sidus\PublishingBundle\Exception\PublicationException;
use Symfony\Component\Finder\Exception\AccessDeniedException;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use UnexpectedValueException;

class Publisher implements PublisherInterface
{
    /** @var string */
    protected $code;

    /** @var string */
    protected $entityName;

    /** @var string */
    protected $format;

    /** @var SerializerInterface */
    protected $serializer;

    /** @var PusherInterface[] */
    protected $pushers;

    /** @var array */
    protected $options;

    /** @var string */
    protected $queueDirectory;

    /** @var string */
    protected $publicationEventClass;

    /**
     * @param string $code
     * @param string $entityName
     * @param string $format
     * @param SerializerInterface $serializer
     * @param PusherInterface[] $pushers
     * @param array $options
     * @throws UnexpectedValueException
     */
    public function __construct(
        $code,
        $entityName,
        $format,
        SerializerInterface $serializer,
        array $pushers,
        array $options = []
    ) {
        $this->code = $code;
        $this->entityName = $entityName;
        $this->format = $format;
        $this->serializer = $serializer;
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
    }

    /**
     * @param PublishableInterface $entity
     * @throws FileException
     * @throws AccessDeniedException
     */
    public function create(PublishableInterface $entity)
    {
        $this->handlePublication($entity, PublicationEvent::CREATE);
    }

    /**
     * @param PublishableInterface $entity
     * @throws FileException
     * @throws AccessDeniedException
     */
    public function update(PublishableInterface $entity)
    {
        $this->handlePublication($entity, PublicationEvent::UPDATE);
    }

    /**
     * @param PublishableInterface $entity
     */
    public function remove(PublishableInterface $entity)
    {
        $this->handlePublication($entity, PublicationEvent::REMOVE);
    }

    protected function handlePublication(PublishableInterface $entity, $eventName)
    {
        $event = new $this->publicationEventClass($entity, $eventName);
        $serialized = $this->getSerializer()->serialize($event, $this->getFormat());
        $f = $this->getFileName($event);
        if (false === file_put_contents($f, $serialized)) {
            throw new FileException("Unable to write to file {$f}");
        }
    }

    /**
     * @return bool
     * @throws \Exception
     */
    public function publish()
    {
        foreach ([PublicationEvent::CREATE, PublicationEvent::UPDATE, PublicationEvent::REMOVE] as $eventType) {
            $finder = new Finder();
            /** @var \Symfony\Component\Finder\SplFileInfo[] $files */
            $files = $finder
                ->in($this->getBaseDirectory($eventType))
                ->name('*.' . $this->format)
                ->sortByModifiedTime()
                ->files();
            foreach ($files as $file) {
                foreach ($this->getPushers() as $pusher) {
                    $publicationUuid = substr($file->getBasename(), 0, -strlen($this->format) - 1);
                    $errorFilePath = "{$this->getBaseDirectory('error')}/{$publicationUuid}.{$this->getFormat()}";
                    try {
                        $pusher->$eventType($publicationUuid, $file->getContents());
                    } catch (PublicationException $e) {
                        $r = $e->getResponse();
                        if ($r && $r->getContent()) {
                            file_put_contents($errorFilePath, $r->getContent());
                            $e->addMessage("Check {$errorFilePath} for more informations");
                            throw $e;
                        }
                    }
                    unlink($file->getRealPath());
                    unlink($errorFilePath);
                }
            }
        }
        return true;
    }

    /**
     * @param $entity
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
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
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
     * @param PublicationEvent $event
     * @return string
     * @throws AccessDeniedException
     */
    protected function getFileName(PublicationEvent $event)
    {
        return "{$this->getBaseDirectory($event->event)}/{$event->publicationUuid}.{$this->getFormat()}";
    }

    /**
     * @param string $eventType
     * @return string
     * @throws AccessDeniedException
     */
    protected function getBaseDirectory($eventType = null)
    {
        $directory = rtrim($this->queueDirectory, '/').'/'.$this->getCode();
        if ($eventType) {
            $directory .= '/' . $eventType;
        }
        if (!@mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new AccessDeniedException("Unable to create base directory {$directory}");
        }
        return $directory;
    }
}