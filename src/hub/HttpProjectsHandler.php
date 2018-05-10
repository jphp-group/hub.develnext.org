<?php
namespace hub;

use php\http\HttpResourceHandler;
use php\http\HttpRouteHandler;
use php\http\HttpServerRequest;
use php\http\HttpServerResponse;
use function print_r;
use twig\TwigEngine;
use function var_dump;

class HttpProjectsHandler extends HttpRouteHandler
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

        parent::__construct('GET', '/projects/list/{top}', function (HttpServerRequest $req, HttpServerResponse $res) {
            $res->contentType('text/html;charset=utf-8');
            $top = $req->attribute('top');

            if ($top === "search") {
                $model = [
                    'list' => [],
                    'error' => false,
                    'queryParam' => $req->query('q'),
                    'popularParam' => !!$req->query('popular')
                ];

                if ($req->query('q')) {
                    $call = $this->client->projectSearch(
                        $req->query('q'), $req->query('popular') ? 'popular' : null
                    );

                    if ($call->isSuccess()) {
                        $model['list'] = $call->body();
                    } else {
                        $model['error'] = true;
                    }
                }

                $res->body($this->twig->render('project/search', $model));
                return;
            }

            $call = $this->client->projectTopList($top === "all" ? "latest" : $top);

            if ($call->isSuccess()) {
                $model['topName'] = $top;
                $model['list'] = $call->body();

                $res->body($this->twig->render('project/topList', $model));
            } else {
                $res->status(404);
                $res->body($this->twig->render('error/404'));
            }
        });
    }
}