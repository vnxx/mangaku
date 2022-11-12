<?php

namespace api;

class Route
{

    protected $path, $fullPath, $pathParams;

    public function __construct()
    {
        $splitPath = (explode('?', $_SERVER['REQUEST_URI']));
        $this->path = $splitPath[0];
        $this->pathParams = isset($splitPath[1]) ? '?' . $splitPath[1] : '';
    }

    public function call($callback, $target, $params)
    {
        $cahedTime = 1800; // 30 minutes in seconds
        $fileName = __DIR__ . '/../../.cache/' . 'cached-' . str_replace("/", "-", $this->fullPath) . $this->pathParams . '.json';

        // check is chace exist
        if (file_exists($fileName) && ((time() - $cahedTime) < filemtime($fileName))) {
            $file = file_get_contents($fileName, true);
            echo $file;
            exit();
        }

        $controller = new $callback;
        header('Content-Type: application/json');
        http_response_code(200);
        $response = json_encode($controller->$target(...$params));

        // create cache
        file_put_contents($fileName, $response);

        echo $response;
        exit();
    }

    public function get($path, $callback, $target)
    {
        $path = $path == '/' ? '/api' : '/api' . $path;
        $params = [];
        $match = 0;

        $current_path = array_values(array_filter(explode('/', $this->path), function ($val) {
            return $val != null;
        }));
        $current_route = array_values(array_filter(explode('/', $path)));

        if (count($current_path) == 0 || $current_path[0] != 'api') {
            return null;
        }

        if ($current_path[0] == 'api' && $this->path == $path) {
            $this->fullPath = implode("/", $current_path);
            $this->call($callback, $target, $params);
        }

        if (count($current_path) == count($current_route)) {
            foreach ($current_route as $key => $routeVal) {
                if ($routeVal[0] == '{') {
                    array_push($params, $current_path[$key]);
                    continue;
                } else {
                    if ($routeVal == $current_path[$key]) {
                        $match++;
                    } else {
                        $match = 0;
                        break;
                    }
                }
            }

            if ($match > 0 && count($current_path) - count($params) == $match) {
                $this->fullPath = implode("/", $current_path);
                $this->call($callback, $target, $params);
            };
        }

        return null;
    }
}
