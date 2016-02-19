<?php

namespace Sidus\PublishingBundle\Publishing;

use Circle\RestClientBundle\Services\RestClient;

class RestPusher implements PusherInterface
{
    /** @var RestClient */
    protected $restClient;

    /** @var string */
    protected $url;

    /** @var array */
    protected $options;

    /**
     * Pusher constructor.
     * @param RestClient $restClient
     * @param string $url
     * @param array $options
     */
    public function __construct(RestClient $restClient, $url, array $options)
    {
        $this->restClient = $restClient;
        $this->url = $url;
        $this->options = $options;
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
     * @inheritDoc
     */
    public function post($data)
    {
        return $this->restClient->post($this->url, $data, $this->options);
    }

    /**
     * @inheritDoc
     */
    public function put($publicationId, $data)
    {
        return $this->restClient->put($this->url, $data, $this->options);
    }

    /**
     * @inheritDoc
     */
    public function delete($publicationId)
    {
        return $this->restClient->delete($this->url, $this->options);
    }
}