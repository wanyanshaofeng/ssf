<?php


namespace Ssf;


use App\Http\Controllers\Controller;
use Ssf\Traits\GetInstances;
use Ssf\Traits\SsfJson;
use stdClass;
use Swoole\Http\Request;
use Swoole\Http\Server;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

class RouterFound
{
    use GetInstances, SsfJson;

    public function found(Request $request, Response $response, $handler, $vars, Server $http): Response
    {
        if ($handler == 'reload') {
            return $this->reload($response, $http);
        }
        $_COOKIE = $request->cookie;
        foreach ($request->server as $key => $item)
            $_SERVER[strtoupper($key)] = $item;
        if ($request->header['x-real-ip'] ?? null)
            $_SERVER['REMOTE_ADDR'] = $request->header['x-real-ip'];

        [$controller, $method] = explode('@', $handler);

        /**
         * @var Controller $controller
         */
        $controller = sprintf("\\App\\Http\\Controllers\\%s", $controller);
        try {
            return $controller::getInstance()->$method($response, $request, $vars);
        } catch (Throwable $e) {
            $whoops = new Run;
            $whoops->allowQuit(false);
            $whoops->writeToOutput(false);
            $handler = new PrettyPageHandler;
            $handler->handleUnconditionally(true);
            $whoops->pushHandler($handler);
            $html = $whoops->handleException($e);
            $response->setContent($html);
            return $response;
        }
    }

    private function reload(Response $response, Server $http): Response
    {
        $http->reload();
        $data = new stdClass();
        $data->info = "Reload current server success";
        return $this->renderJson($response, $data);
    }
}