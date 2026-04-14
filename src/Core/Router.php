<?php
/**
 * Router – lightweight front-controller router.
 *
 * Matches the incoming HTTP method + URI against a list of registered routes
 * and invokes the matching controller action.
 *
 * Route patterns support named URL segments in curly-brace syntax:
 *   /groups/{id}           → matches /groups/42, passes ['id' => '42']
 *   /groups/{id}/events/{eid} → nested params work too
 *
 * Named captures are extracted and passed as an associative $params array to
 * the controller method, e.g.:
 *   public function show(array $params): void  { $id = (int)$params['id']; }
 *
 * Handler string format:  'ClassName@methodName'
 * A new instance of the class is created for every matched request.
 *
 * If no route matches, a 404 response is returned.
 */
class Router
{
    /**
     * Registered route definitions.
     *
     * Each entry:
     *   'method'  → 'GET' or 'POST'
     *   'regex'   → compiled regex pattern (with named groups for params)
     *   'handler' → 'ControllerClass@actionMethod'
     *
     * @var array<int, array{method: string, regex: string, handler: string}>
     */
    private array $routes = [];

    /**
     * Register a GET route.
     *
     * @param string $pattern  URI pattern, e.g. '/groups/{id}'.
     * @param string $handler  Handler in 'Class@method' notation.
     */
    public function get(string $pattern, string $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    /**
     * Register a POST route.
     *
     * @param string $pattern  URI pattern, e.g. '/groups/{id}/delete'.
     * @param string $handler  Handler in 'Class@method' notation.
     */
    public function post(string $pattern, string $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    /**
     * Compile and store a route definition.
     *
     * Converts {param} placeholders in the pattern into named regex capture
     * groups, then stores the route for later matching in dispatch().
     *
     * @param string $method   HTTP method ('GET' or 'POST').
     * @param string $pattern  URI pattern with optional {param} placeholders.
     * @param string $handler  'ClassName@methodName'.
     */
    private function add(string $method, string $pattern, string $handler): void
    {
        // Replace {word} with a named capture group that matches one URI segment
        $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . $regex . '$#';
        $this->routes[] = ['method' => $method, 'regex' => $regex, 'handler' => $handler];
    }

    /**
     * Match the current request against all registered routes and dispatch.
     *
     * Strips the query string from the URI before matching.
     * Only string-keyed (named) capture groups are passed to the controller.
     * Falls through to a 404 response if no route matches.
     */
    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        // Strip query string; normalise to always have a leading slash
        $uri = strtok($_SERVER['REQUEST_URI'], '?');
        $uri = '/' . trim($uri, '/');

        foreach ($this->routes as $route) {
            // Skip routes that don't match the HTTP method
            if ($route['method'] !== $method) {
                continue;
            }
            // Skip routes whose pattern doesn't match the URI
            if (!preg_match($route['regex'], $uri, $matches)) {
                continue;
            }

            // Verify CSRF token for every state-mutating request
            if ($method === 'POST') {
                Csrf::verify();
            }

            // preg_match returns both numeric and named captures; keep only named ones
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

            // Instantiate the controller and call the action method
            [$class, $action] = explode('@', $route['handler'], 2);
            (new $class())->$action($params);
            return;
        }

        // No route matched → 404
        http_response_code(404);
        render('errors/404', ['pageTitle' => '404']);
    }
}
