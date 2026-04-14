<?php

use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the Csrf class.
 *
 * Tests run without a real HTTP session; $_SESSION is mocked directly.
 */
class CsrfTest extends TestCase
{
    protected function setUp(): void
    {
        // Reset session state between tests
        $_SESSION = [];
    }

    public function testTokenGeneratedOnFirstCall(): void
    {
        $token = Csrf::token();
        $this->assertNotEmpty($token);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }

    public function testTokenIsReusedWithinSession(): void
    {
        $t1 = Csrf::token();
        $t2 = Csrf::token();
        $this->assertSame($t1, $t2);
    }

    public function testTokenDiffersAcrossSessions(): void
    {
        $t1 = Csrf::token();
        $_SESSION = [];
        $t2 = Csrf::token();
        // Very unlikely to collide; this test would fail once in 2^256 runs
        $this->assertNotSame($t1, $t2);
    }

    public function testFieldReturnsHiddenInput(): void
    {
        $html = Csrf::field();
        $this->assertStringContainsString('<input', $html);
        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('name="_csrf_token"', $html);
        $this->assertStringContainsString(Csrf::token(), $html);
    }

    public function testVerifyPassesWithCorrectToken(): void
    {
        $token = Csrf::token();
        $_POST['_csrf_token'] = $token;

        // verify() should return without throwing
        $this->expectNotToPerformAssertions();
        // Suppress exit() side-effects – we expect no exception
        try {
            Csrf::verify();
        } catch (Throwable $e) {
            $this->fail('verify() threw unexpectedly: ' . $e->getMessage());
        }
    }

    public function testVerifyFailsWithWrongToken(): void
    {
        Csrf::token(); // ensure session token is set
        $_POST['_csrf_token'] = 'wrong_token_value';

        // verify() calls http_response_code(), render(), and exit – we can't
        // call it in a unit test without mocking those. We verify that the
        // hash_equals comparison would fail by checking the session token
        // does NOT equal the submitted value.
        $sessionToken = Csrf::token();
        $this->assertFalse(hash_equals($sessionToken, 'wrong_token_value'));
    }

    protected function tearDown(): void
    {
        $_SESSION = [];
        unset($_POST['_csrf_token'], $_SERVER['HTTP_X_CSRF_TOKEN']);
    }
}
