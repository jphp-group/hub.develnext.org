<?php
namespace hub;

use httpclient\HttpClient;
use httpclient\HttpRequest;
use httpclient\HttpResponse;

class DNServiceClient
{
    /**
     * @var string
     */
    protected $endpoint = 'https://api.develnext.org';

    /**
     * @var HttpClient
     */
    protected $client;

    /**
     * DNServiceClient constructor.
     * @param string $endpoint
     */
    public function __construct(string $endpoint = null)
    {
        if (isset($endpoint)) {
            $this->endpoint = $endpoint;
        }

        $this->client = new HttpClient($endpoint);
        $this->client->connectTimeout = '15s';
        $this->client->readTimeout = '120s';
        $this->client->requestType = 'JSON';
        $this->client->responseType = 'JSON';
    }

    /**
     * @param string $id
     * @return HttpResponse
     */
    public function documentationGet(string $id): HttpResponse
    {
        return $this->client->get("/documentation/get", ['id' => $id]);
    }

    /**
     * @param string $query
     * @return HttpResponse
     */
    public function documentationSearch(string $query): HttpResponse
    {
        return $this->client->get('/documentation/search', ['q' => $query]);
    }

    /**
     * @param string $fileId
     * @param string $path
     * @return HttpResponse
     */
    public function fileGetContents(string $fileId, string $path = null): HttpResponse
    {
        return $this->client->get("/file/file/$fileId/contents", ['path' => $path]);
    }

    /**
     * @param string $fileId
     * @param string $path
     * @return HttpResponse
     */
    public function fileDownloadContents(string $fileId, string $path): HttpResponse
    {
        $req = new HttpRequest('GET', "/file/file/$fileId/contents/download", [], ['path' => $path]);
        $req->responseType('STREAM');

        return $this->client->send($req);
    }

    /**
     * @param string $id
     * @return HttpResponse
     */
    public function projectGet(string $id): HttpResponse
    {
        return $this->client->get("/project/projects/$id");
    }

    /**
     * @param string $top
     * @return HttpResponse
     */
    public function projectTopList(string $top): HttpResponse
    {
        return $this->client->get("/project/projects/list/$top");
    }

    /**
     * @param string $query
     * @param string|null $order
     * @param string|null $owner
     * @return HttpResponse
     */
    public function projectSearch(string $query, string $order = null, string $owner = null): HttpResponse
    {
        return $this->client->get("/project/projects/search", ['q' => $query, 'order' => $order, 'owner' => $owner]);
    }

    /**
     * @param string $id
     * @return HttpResponse
     */
    public function accountGet(string $id): HttpResponse
    {
        return $this->client->get("/auth/account/$id");
    }

    public function getEndpoint(): string
    {
        return $this->endpoint;
    }
}