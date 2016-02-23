<?php

namespace Sidus\PublishingBundle\Publishing;

use Circle\RestClientBundle\Services\RestClient;
use Sidus\PublishingBundle\Exception\PublicationException;
use Symfony\Component\HttpFoundation\Response;

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
     * @throws PublicationException
     */
    public function create($publicationUuid, $data)
    {
        $response = $this->restClient->post($this->url, $data, $this->options);
        $this->checkError($response, 'POST');
    }

    /**
     * @inheritDoc
     * @throws PublicationException
     */
    public function update($publicationUuid, $data)
    {
        $url = $this->url . '/' . $publicationUuid;
        $response = $this->restClient->put($url, $data, $this->options);
        $this->checkError($response, 'PUT', $url);
    }

    /**
     * @inheritDoc
     * @throws PublicationException
     */
    public function delete($publicationUuid, $data)
    {
        $url = $this->url . '/' . $publicationUuid;
        $response = $this->restClient->delete($url, $this->options);
        $this->checkError($response, 'DELETE', $url);
    }

    /**
     * @param Response $response
     * @param string $method
     * @param string $url
     * @throws PublicationException
     */
    protected function checkError(Response $response, $method, $url = null)
    {
        if ($response->getStatusCode() === Response::HTTP_OK) {
            return;
        }
        if (null === $url) {
            $url = $this->url;
        }
        throw new PublicationException("Failed to send ({$method}) data to {$url}", $response->getStatusCode(), $response);
    }
}