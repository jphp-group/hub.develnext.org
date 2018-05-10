<?php
namespace hub;

use markdown\Markdown;
use markdown\MarkdownOptions;
use php\http\HttpRouteHandler;
use php\http\HttpServerRequest;
use php\http\HttpServerResponse;
use twig\TwigEngine;

/**
 * Class HttpDocumentationHandler
 * @package hub
 */
class HttpWikiHandler extends HttpRouteHandler
{
    /**
     * @var TwigEngine
     */
    private $twig;

    /**
     * @var DNServiceClient
     */
    private $client;

    public function __construct(TwigEngine $twig, DNServiceClient $client)
    {
        parent::__construct('GET', '/wiki/**', function (HttpServerRequest $request, HttpServerResponse $response) {
            $query = $request->query('q');
            $response->contentType('text/html;charset=utf-8');

            $model = ['queryParam' => $query];

            if ($query) {
                $searchRes = $this->client->documentationSearch($query);

                if ($searchRes->isSuccess()) {
                    $model['sidebar'] = $this->client->documentationGet('_Sidebar')->body();
                    $model['results'] = $searchRes->body();
                    $response->body($this->twig->render('docs/search', $model));
                } else {
                    $response->status(500);
                    $response->body($this->twig->render('error/5xx'));
                }
            } else {
                $id = $request->attribute('**') ?: 'README';

                $res = $this->client->documentationGet($id);

                if ($res->isNotFound()) {
                    $response->status(404);
                    $response->body($this->twig->render('error/404'));
                } elseif ($res->isSuccess()) {
                    $model['sidebar'] = $this->client->documentationGet('_Sidebar')->body();
                    $model["article"] = $res->body();

                    $response->body($this->twig->render('docs/get', $model));
                } else {
                    $response->status(500);
                    $response->body($this->twig->render('error/5xx'));
                }
            }
        });
        $this->twig = $twig;
        $this->client = $client;
    }
}