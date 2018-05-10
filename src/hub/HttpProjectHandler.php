<?php
namespace hub;

use Exception;
use php\http\HttpResourceHandler;
use php\http\HttpRouteHandler;
use php\http\HttpServerRequest;
use php\http\HttpServerResponse;
use function print_r;
use twig\TwigEngine;

class HttpProjectHandler extends HttpRouteHandler
{
    /**
     * @var TwigEngine
     */
    private $twig;
    /**
     * @var DNServiceClient
     */
    private $client;

    /**
     * HttpProjectHandler constructor.
     * @param TwigEngine $twig
     * @param DNServiceClient $client
     */
    public function __construct(TwigEngine $twig, DNServiceClient $client)
    {
        $this->twig = $twig;
        $this->client = $client;

        parent::__construct('GET', '/project/{id}', function (HttpServerRequest $req, HttpServerResponse $res) {
            $res->contentType('text/html;charset=utf-8');

            $call = $this->client->projectGet($req->attribute('id'));

            $model = ['call' => $call, 'project' => null, 'contents' => [], 'projectDownloadUrl' => null];
            $project = $call->body();

            if ($call->isSuccess() && $project) {
                $contentCall = $this->client->fileGetContents($project['archive']['fileId']);
                $downloadUrl = $this->client->getEndpoint() . "/projects/{$project['id']}/{$project['downloadKey']}/download";

                $model['project'] = $project;
                $model['projectDownloadUrl'] = $downloadUrl;

                if ($contentCall->isSuccess()) {
                    $model['contents'] = $contentCall->body();
                } else {
                    $model['contents'] = null;
                }
            } else {
                $res->status(404);
            }

            $res->body($this->twig->render('project/get', $model));
        });
    }
}