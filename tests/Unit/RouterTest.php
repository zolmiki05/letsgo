<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Router class.
 *
 * Tests route registration and the internal regex compilation.
 * dispatch() itself cannot be fully unit-tested without mocking the global
 * HTTP environment, but the route-matching logic can be verified via reflection.
 */
class RouterTest extends TestCase
{
    private Router $router;

    protected function setUp(): void
    {
        $this->router = new Router();
    }

    private function getRoutes(): array
    {
        $ref = new ReflectionProperty(Router::class, 'routes');
        $ref->setAccessible(true);
        return $ref->getValue($this->router);
    }

    public function testGetRouteRegistered(): void
    {
        $this->router->get('/test', 'FakeController@action');
        $routes = $this->getRoutes();
        $this->assertCount(1, $routes);
        $this->assertSame('GET', $routes[0]['method']);
        $this->assertSame('FakeController@action', $routes[0]['handler']);
    }

    public function testPostRouteRegistered(): void
    {
        $this->router->post('/test', 'FakeController@action');
        $routes = $this->getRoutes();
        $this->assertSame('POST', $routes[0]['method']);
    }

    public function testSimplePatternMatchesUri(): void
    {
        $this->router->get('/login', 'AuthController@loginForm');
        $routes = $this->getRoutes();
        $this->assertMatchesRegularExpression($routes[0]['regex'], '/login');
    }

    public function testParamPatternExtractsId(): void
    {
        $this->router->get('/groups/{id}', 'GroupController@show');
        $routes = $this->getRoutes();
        $matched = preg_match($routes[0]['regex'], '/groups/42', $matches);
        $this->assertSame(1, $matched);
        $this->assertSame('42', $matches['id']);
    }

    public function testPatternDoesNotMatchPartialUri(): void
    {
        $this->router->get('/login', 'AuthController@loginForm');
        $routes = $this->getRoutes();
        $this->assertDoesNotMatchRegularExpression($routes[0]['regex'], '/login/extra');
    }

    public function testMultipleParamsExtracted(): void
    {
        $this->router->get('/events/{id}/comments/{cid}', 'EventController@deleteComment');
        $routes = $this->getRoutes();
        $matched = preg_match($routes[0]['regex'], '/events/7/comments/3', $matches);
        $this->assertSame(1, $matched);
        $this->assertSame('7',  $matches['id']);
        $this->assertSame('3',  $matches['cid']);
    }
}
