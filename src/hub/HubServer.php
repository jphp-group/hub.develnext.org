<?php

namespace hub;

use HttpRequest;
use hub\DNServiceClient;
use function is_array;
use function is_object;
use function is_string;
use const JPHP_VERSION;
use php\http\{
    HttpRedirectHandler, HttpResourceHandler, HttpServer, HttpServerRequest, HttpServerResponse
};

use php\lang\System;
use php\lang\ThreadLocal;
use php\lib\arr;
use php\lib\fs;
use php\lib\str;
use php\time\Time;
use php\util\Locale;
use Throwable;
use twig\TwigEngine;
use twig\TwigExtension;
use twig\TwigStreamLoader;
use function var_dump;

class HubServer
{
    /**
     * @var HttpServer
     */
    protected $server;

    /**
     * @var TwigEngine
     */
    protected $twig;

    /**
     * @var DNServiceClient
     */
    protected $client;

    /**
     * @var ThreadLocal
     */
    protected $currentRequest;

    /**
     * HubServer constructor.
     */
    public function __construct()
    {
        $this->currentRequest = new ThreadLocal();

        $config = fs::isFile("./application.yml") ? fs::parse("./application.yml") : [];

        $port = $config['server']['port'] ?? 8080;
        $host = $config['server']['host'] ?? 'localhost';

        $server = new HttpServer(
            $port,
            $host
        );

        echo "-> Starting http server at $host:$port\n";

        $server->get('/assets/**', new HttpResourceHandler('./assets/'));

        $server->stopAtShutdown(true);

        $server->addFilter(function (HttpServerRequest $req, HttpServerResponse $res) {
            $this->currentRequest->set($req);
            $res->header('X-Provided-By', 'JPHP ' . JPHP_VERSION);
        });

        $server->setRequestLogHandler(function (HttpServerRequest $req, HttpServerResponse $res) {
            if (!fs::ext($req->path())) {
                echo str::format("-> %s %s%s", $req->method(), $req->path(), $req->query() ? "?{$req->query()}" : ""), "\n";
            }
        });

        $this->initTwig();
        $this->client = new DNServiceClient($config['api']['develnext']);
        $this->server = $server;
    }

    protected function initTwig()
    {
        $loader = new HubTwigStreamLoader();
        $loader->setPrefix('./views/');
        $loader->setSuffix('.twig');

        $twig = new TwigEngine($loader);
        $twigExtension = new TwigExtension();
        $twigExtension->addGlobalVar('assetDir', '/assets/');

        $twigExtension->addFilter('fileSize', function ($length) {
            $length = (int) $length;

            if ($length < 1024) {
                return $length . " b";
            } else if ($length < 1024 * 1024) {
                return round(($length / 1024), 2) . " Kb";
            } else {
                return str::format("%.2f", ($length / 1024.0 / 1024.0)) . " Mb";
            }
        });

        $twigExtension->addFilter('format', function ($value, array $args) {
            if (!$value) {
                return "Unknow Date & Time";
            }

            if (!($value instanceof Time)) {
                $value = new Time($value);
            }

            return $value->toString($args['format'], Locale::RUSSIA());
        }, ['format']);

        $twigExtension->addFunction('href', function (array $args) {
            return $args['url'];
        }, ['url']);

        $twigExtension->addFunction('urlPath', function (array $args) {
            return $this->request()->path();
        });

        $twigExtension->addFunction('assetPath', function (array $args) {
            return "/assets/{$args['path']}";
        }, ['path']);

        $twigExtension->addFunction('bundlePublicName', function (array $args) {
            $bundle = $args['bundle'];
            $bundle = arr::last(str::split($bundle, '.'));

            if (str::endsWith($bundle, "Bundle")) {
                $bundle = str::sub($bundle, 0, str::length($bundle) - 6);
            }

            return $bundle;
        }, ['bundle']);

        $twigExtension->addTest('starts', function ($value, array $args) {
            if (is_array($value)) {
                return false;
            }

            return str::startsWith($value, $args['prefix']);
        }, ['prefix']);

        $twig->addExtension($twigExtension);

        $this->twig = $twig;
    }

    public function run()
    {
        $this->server->get('/', function (HttpServerRequest $request, HttpServerResponse $response) {
            $response->body('index');
        });

        echo "-> Server is ready to work.\n";
        $this->server->run();
    }

    public function addWiki(): HubServer
    {
        $this->server->get('/wiki', new HttpRedirectHandler('/wiki/'));
        $this->server->addHandler(new HttpWikiHandler($this->twig, $this->client));
        return $this;
    }

    public function addProjects(): HubServer
    {
        $this->server->get('/', new HttpRedirectHandler('/projects/list/all'));

        $this->server->addHandler(new HttpProjectsHandler($this->twig, $this->client));
        $this->server->get('/projects/', new HttpRedirectHandler('/projects/list/all'));
        $this->server->addHandler(new HttpProjectHandler($this->twig, $this->client));

        $this->server->get('/file/{id}/contents/download', function (HttpServerRequest $req, HttpServerResponse $res) {
            $call = $this->client->fileDownloadContents($req->attribute('id'), $req->query('path'));

            if ($call->isSuccess() && $call->body()) {
                $res->body($call->body());
            } else {
                $res->status(404);
            }
        });

        return $this;
    }

    public function addAccount(): HubServer
    {
        $this->server->get('/account/{id}', function (HttpServerRequest $req, HttpServerResponse $res) {
            $call = $this->client->accountGet($req->attribute('id'));
            $res->contentType('text/html;charset=utf-8');

            if ($call->isNotFound()) {
                $res->status(404);
                $res->body($this->twig->render("error/404"));
            } else {
                $model['projects'] = $this->client->projectTopList("owner/{$req->attribute('id')}")->body();
                $model['account'] = $call->body();

                $res->body($this->twig->render("account/get", $model));
            }
        });

        return $this;
    }

    public function request(): ?HttpServerRequest
    {
        return $this->currentRequest->get();
    }
}