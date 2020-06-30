<?php

namespace MillenniumFalcon\Core\SymfonyKernel;

use Symfony\Component\HttpFoundation\Response;

class RedirectException extends \Exception
{

    protected $url;
    protected $statusCode;

    const MESSAGE = [
        Response::HTTP_MOVED_PERMANENTLY => "Moved permanently",
        Response::HTTP_FOUND => "Found",
        Response::HTTP_TEMPORARY_REDIRECT => "Temporary rediect",
        Response::HTTP_PERMANENTLY_REDIRECT => "Permanently redirect"
    ];

    public function __construct($url, int $statusCode = Response::HTTP_FOUND)
    {
        parent::__construct(static::MESSAGE[$statusCode] ?? 'Redirect', $statusCode);
        $this->url = $url;
        $this->statusCode = $statusCode;
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return mixed
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}