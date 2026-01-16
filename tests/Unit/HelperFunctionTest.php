<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Tests\TestCase;

class HelperFunctionTest extends TestCase
{
    /**
     * Test that getVisitorIdFromCookie extracts UUID from pipe-separated format
     */
    public function test_extracts_uuid_from_pipe_separated_format(): void
    {
        $visitorId = (string) Str::uuid();
        $hash = 'test_hash_'.Str::random(10);
        $cookieValue = $hash.'|'.$visitorId;

        // Create a mock request with the cookie value
        $request = Request::create('/', 'GET', [], ['visitor_id' => $cookieValue]);

        $result = getVisitorIdFromCookie($request);

        $this->assertEquals($visitorId, $result);
    }

    /**
     * Test that getVisitorIdFromCookie returns plain UUID
     */
    public function test_returns_plain_uuid(): void
    {
        $visitorId = (string) Str::uuid();
        $request = Request::create('/', 'GET', [], ['visitor_id' => $visitorId]);

        $result = getVisitorIdFromCookie($request);

        $this->assertEquals($visitorId, $result);
    }

    /**
     * Test that getVisitorIdFromCookie returns null for missing cookie
     */
    public function test_returns_null_for_missing_cookie(): void
    {
        $request = Request::create('/', 'GET');

        $result = getVisitorIdFromCookie($request);

        $this->assertNull($result);
    }

    /**
     * Test that getVisitorIdFromCookie handles encrypted cookies
     */
    public function test_handles_encrypted_cookies(): void
    {
        $visitorId = (string) Str::uuid();
        $hash = 'test_hash_'.Str::random(10);
        $cookieValue = $hash.'|'.$visitorId;
        $encryptedCookie = \Illuminate\Support\Facades\Crypt::encryptString($cookieValue);

        $request = Request::create('/', 'GET', [], ['visitor_id' => $encryptedCookie]);

        $result = getVisitorIdFromCookie($request);

        $this->assertEquals($visitorId, $result);
    }

    /**
     * Test that getVisitorIdFromCookie rejects invalid values
     */
    public function test_rejects_invalid_uuid(): void
    {
        $request = Request::create('/', 'GET', [], ['visitor_id' => 'not-a-uuid']);

        $result = getVisitorIdFromCookie($request);

        $this->assertNull($result);
    }
}
