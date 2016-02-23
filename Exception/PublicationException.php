<?php

namespace Sidus\PublishingBundle\Exception;


use Symfony\Component\HttpFoundation\Response;

class PublicationException extends \Exception
{
    /** @var Response */
    protected $response;

    /**
     * @param string $message
     * @param int $code
     * @param Response $response
     */
    public function __construct($message, $code, Response $response = null)
    {
        parent::__construct($message, $code);
        $this->response = $response;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    public function addMessage($message)
    {
        $this->message .= "\n" . $message;
    }
}
