<?php

namespace Tests\Unit\Services;

use App\Models\Visitor;
use App\Services\VisitorLimitService;
use Tests\TestCase;

class VisitorLimitServiceTest extends TestCase
{
    private VisitorLimitService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(VisitorLimitService::class);
    }

    public function test_has_exceeded_limit_returns_false_for_null_visitor_id(): void
    {
        $this->assertFalse($this->service->hasExceededLimit(null));
    }

    public function test_has_exceeded_limit_returns_false_for_nonexistent_visitor(): void
    {
        $fakeId = \Illuminate\Support\Str::uuid();
        $this->assertFalse($this->service->hasExceededLimit($fakeId));
    }

    public function test_has_exceeded_limit_returns_false_when_visitor_has_no_completed_prompts(): void
    {
        $visitor = Visitor::factory()->create();

        $this->assertFalse($this->service->hasExceededLimit($visitor->id));
    }

    public function test_check_limit_returns_true_for_authenticated_user(): void
    {
        $fakeId = \Illuminate\Support\Str::uuid();
        $this->assertTrue($this->service->checkLimit(true, $fakeId));
    }

    public function test_check_limit_returns_true_for_unauthenticated_user_without_exceeded_limit(): void
    {
        $visitor = Visitor::factory()->create();

        $this->assertTrue($this->service->checkLimit(false, $visitor->id));
    }

    public function test_check_limit_returns_true_for_unauthenticated_user_with_null_visitor_id(): void
    {
        $this->assertTrue($this->service->checkLimit(false, null));
    }

    public function test_create_web_error_response_returns_redirect(): void
    {
        $response = $this->service->createWebErrorResponse();

        $this->assertInstanceOf(\Illuminate\Http\RedirectResponse::class, $response);
    }

    public function test_create_api_error_response_returns_json(): void
    {
        $response = $this->service->createApiErrorResponse();

        $this->assertInstanceOf(\Illuminate\Http\JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
    }
}
