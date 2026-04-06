<?php
/**
 * Router – lightweight front-controller router.
 *
 * Supports named URL segments such as /groups/{id}, which are extracted and
 * passed as an associative array to the matched controller action.
 *
 * Usage:
 *   $router->get('/groups/{id}', 'GroupController@show');
 *   $router->dispatch();
 */
class Router
{
    /** @var array<int, array{method: string, regex: string, handler: string}> */
    private array $routes = [];

    public function get(string $pattern, string $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, string $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    private function add(string $method, string $pattern, string $handler): void
    {
        // Convert {param} placeholders to named capture groups
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        $this->routes[] = ['method' => $method, 'regex' => $regex, 'handler' => $handler];
    }

    /**
     * Match the current request against registered routes and invoke the handler.
     * Falls back to a 404 view if no route matches.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = strtok($_SERVER['REQUEST_URI'], '?');
        $uri    = '/' . trim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $uri, $matches)) {
                continue;
            }

            // Keep only string-keyed (named) captures as route params
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            [$class, $action] = explode('@', $route['handler'], 2);
            (new $class())->$action($params);
            return;
        }

        http_response_code(404);
        render('errors/404', ['pageTitle' => '404']);
    }
}
