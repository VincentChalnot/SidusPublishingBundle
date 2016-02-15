<?php

namespace Sidus\PublishingBundle\Publishing;

class Pusher implements PusherInterface
{
    /** @var string */
    protected $code;

    /** @var string */
    protected $url;

    /** @var array */
    protected $options;

    /**
     * Pusher constructor.
     * @param string $code
     * @param string $url
     * @param array $options
     */
    public function __construct($code, $url, array $options)
    {
        $this->code = $code;
        $this->url = $url;
        $this->options = $options;
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
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * @param mixed $data
     * @return bool
     */
    public function push($data)
    {
        // @todo
    }
}