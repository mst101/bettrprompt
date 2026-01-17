<?php

//
// use App\Models\PromptQualityMetric;
// use App\Models\PromptRun;
// use App\Models\User;
//
// beforeEach(function () {
//    $this->user = User::factory()->create();
// });
//
// describe('PromptRatingController', function () {
//    describe('Authentication & Authorization', function () {
//        test('authenticated user can rate their own prompt run', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create([
//                'workflow_stage' => '2_completed',
//            ]);
//
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                [
//                    'rating' => 5,
//                    'explanation' => 'Excellent prompt!',
//                ]
//            );
//
//            $response->assertOk();
//            $response->assertJson(['message' => 'Rating saved successfully']);
//
//            $this->assertDatabaseHas('prompt_quality_metrics', [
//                'prompt_run_id' => $promptRun->id,
//                'user_rating' => 5,
//                'rating_explanation' => 'Excellent prompt!',
//            ]);
//        });
//
//        test('unauthenticated user cannot rate prompt run', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create([
//                'workflow_stage' => '2_completed',
//            ]);
//
//            $response = $this->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                [
//                    'rating' => 5,
//                    'explanation' => 'Good',
//                ]
//            );
//
//            $response->assertUnauthorized();
//        });
//
//        test('user cannot rate another user\'s prompt run', function () {
//            $otherUser = User::factory()->create();
//            $promptRun = PromptRun::factory()->for($otherUser)->create([
//                'workflow_stage' => '2_completed',
//            ]);
//
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                ['rating' => 5]
//            );
//
//            $response->assertForbidden();
//        });
//
//        test('admin can rate any user\'s prompt run', function () {
//            $admin = User::factory()->create(['is_admin' => true]);
//            $otherUser = User::factory()->create();
//            $promptRun = PromptRun::factory()->for($otherUser)->create([
//                'workflow_stage' => '2_completed',
//            ]);
//
//            $response = $this->actingAs($admin)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                ['rating' => 4, 'explanation' => 'Admin review']
//            );
//
//            $response->assertOk();
//
//            $this->assertDatabaseHas('prompt_quality_metrics', [
//                'prompt_run_id' => $promptRun->id,
//                'user_rating' => 4,
//            ]);
//        });
//
//        test('returns 404 for non-existent prompt run', function () {
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', 99999),
//                ['rating' => 5]
//            );
//
//            $response->assertNotFound();
//        });
//    });
//
//    describe('Validation', function () {
//        test('rating is required', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                []
//            );
//
//            $response->assertUnprocessable();
//            $response->assertJsonValidationErrors('rating');
//        });
//
//        test('rating must be an integer', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                ['rating' => 'five']
//            );
//
//            $response->assertUnprocessable();
//            $response->assertJsonValidationErrors('rating');
//        });
//
//        test('rating must be between 1 and 5', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//
//            // Test below range (0)
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                ['rating' => 0]
//            );
//            $response->assertUnprocessable();
//
//            // Test above range (6)
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                ['rating' => 6]
//            );
//            $response->assertUnprocessable();
//
//            // Test negative
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                ['rating' => -1]
//            );
//            $response->assertUnprocessable();
//        });
//
//        test('explanation is optional', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                ['rating' => 3]
//            );
//
//            $response->assertOk();
//
//            $this->assertDatabaseHas('prompt_quality_metrics', [
//                'prompt_run_id' => $promptRun->id,
//                'user_rating' => 3,
//                'rating_explanation' => null,
//            ]);
//        });
//
//        test('explanation must be a string if provided', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                [
//                    'rating' => 4,
//                    'explanation' => ['array' => 'value'],
//                ]
//            );
//
//            $response->assertUnprocessable();
//            $response->assertJsonValidationErrors('explanation');
//        });
//
//        test('explanation cannot exceed 1000 characters', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                [
//                    'rating' => 4,
//                    'explanation' => str_repeat('a', 1001),
//                ]
//            );
//
//            $response->assertUnprocessable();
//            $response->assertJsonValidationErrors('explanation');
//        });
//
//        test('explanation can be exactly 1000 characters', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                [
//                    'rating' => 4,
//                    'explanation' => str_repeat('a', 1000),
//                ]
//            );
//
//            $response->assertOk();
//        });
//
//        test('explanation can be null', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                [
//                    'rating' => 4,
//                    'explanation' => null,
//                ]
//            );
//
//            $response->assertOk();
//
//            $this->assertDatabaseHas('prompt_quality_metrics', [
//                'prompt_run_id' => $promptRun->id,
//                'rating_explanation' => null,
//            ]);
//        });
//
//        test('explanation can be an empty string', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                [
//                    'rating' => 4,
//                    'explanation' => '',
//                ]
//            );
//
//            $response->assertOk();
//        });
//    });
//
//    describe('Rating Storage', function () {
//        test('stores rating with all required fields', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//
//            $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                [
//                    'rating' => 5,
//                    'explanation' => 'Outstanding work!',
//                ]
//            );
//
//            $metric = PromptQualityMetric::where('prompt_run_id', $promptRun->id)->first();
//
//            expect($metric)->not->toBeNull();
//            expect($metric->prompt_run_id)->toBe($promptRun->id);
//            expect($metric->user_rating)->toBe(5);
//            expect($metric->rating_explanation)->toBe('Outstanding work!');
//        });
//
//        test('stores only rating without explanation', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//
//            $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                ['rating' => 3]
//            );
//
//            $metric = PromptQualityMetric::where('prompt_run_id', $promptRun->id)->first();
//
//            expect($metric->user_rating)->toBe(3);
//            expect($metric->rating_explanation)->toBeNull();
//        });
//
//        test('updates existing rating when resubmitted', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//
//            // Submit initial rating
//            $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                ['rating' => 2, 'explanation' => 'Not great']
//            );
//
//            // Verify initial rating stored
//            $metricsCount = PromptQualityMetric::where('prompt_run_id', $promptRun->id)->count();
//            expect($metricsCount)->toBe(1);
//
//            // Update rating
//            $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                ['rating' => 5, 'explanation' => 'Actually excellent!']
//            );
//
//            // Verify only one record exists (updated, not created)
//            $metricsCount = PromptQualityMetric::where('prompt_run_id', $promptRun->id)->count();
//            expect($metricsCount)->toBe(1);
//
//            // Verify data is updated
//            $metric = PromptQualityMetric::where('prompt_run_id', $promptRun->id)->first();
//            expect($metric->user_rating)->toBe(5);
//            expect($metric->rating_explanation)->toBe('Actually excellent!');
//        });
//
//        test('can clear explanation by updating with empty string', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//
//            // Submit with explanation
//            $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                ['rating' => 4, 'explanation' => 'Good prompt']
//            );
//
//            // Update to clear explanation
//            $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                ['rating' => 4, 'explanation' => '']
//            );
//
//            $metric = PromptQualityMetric::where('prompt_run_id', $promptRun->id)->first();
//            expect($metric->rating_explanation)->toBeEmpty();
//        });
//
//        test('stores rating with various values', function () {
//            foreach ([1, 2, 3, 4, 5] as $rating) {
//                $promptRun = PromptRun::factory()->for($this->user)->create();
//
//                $this->actingAs($this->user)->postJson(
//                    route('api.prompt-runs.rate', $promptRun->id),
//                    ['rating' => $rating]
//                );
//
//                $metric = PromptQualityMetric::where('prompt_run_id', $promptRun->id)->first();
//                expect($metric->user_rating)->toBe($rating);
//            }
//        });
//    });
//
//    describe('Response Format', function () {
//        test('returns success message', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                ['rating' => 4]
//            );
//
//            $response->assertJson(['message' => 'Rating saved successfully']);
//        });
//
//        test('returns 200 OK status code', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                ['rating' => 4]
//            );
//
//            expect($response->status())->toBe(200);
//        });
//
//        test('returns correct JSON content type', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                ['rating' => 4]
//            );
//
//            expect($response->headers->get('Content-Type'))->toContain('application/json');
//        });
//    });
//
//    describe('Edge Cases', function () {
//        test('can rate a prompt run at any workflow stage', function () {
//            foreach (['0_completed', '1_completed', '2_completed'] as $stage) {
//                $promptRun = PromptRun::factory()->for($this->user)->create([
//                    'workflow_stage' => $stage,
//                ]);
//
//                $response = $this->actingAs($this->user)->postJson(
//                    route('api.prompt-runs.rate', $promptRun->id),
//                    ['rating' => 5]
//                );
//
//                $response->assertOk();
//            }
//        });
//
//        test('can rate multiple prompt runs independently', function () {
//            $promptRun1 = PromptRun::factory()->for($this->user)->create();
//            $promptRun2 = PromptRun::factory()->for($this->user)->create();
//
//            $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun1->id),
//                ['rating' => 5]
//            );
//
//            $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun2->id),
//                ['rating' => 2]
//            );
//
//            $metric1 = PromptQualityMetric::where('prompt_run_id', $promptRun1->id)->first();
//            $metric2 = PromptQualityMetric::where('prompt_run_id', $promptRun2->id)->first();
//
//            expect($metric1->user_rating)->toBe(5);
//            expect($metric2->user_rating)->toBe(2);
//        });
//
//        test('handles special characters in explanation', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//            $specialText = "Great! 🎉 It's \"special\" & works well. <script>alert('xss')</script>";
//
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                [
//                    'rating' => 5,
//                    'explanation' => $specialText,
//                ]
//            );
//
//            $response->assertOk();
//
//            $metric = PromptQualityMetric::where('prompt_run_id', $promptRun->id)->first();
//            expect($metric->rating_explanation)->toBe($specialText);
//        });
//
//        test('handles unicode characters in explanation', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//            $unicodeText = 'Excellent! 非常好! Excelente! بممتاز!';
//
//            $response = $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                [
//                    'rating' => 5,
//                    'explanation' => $unicodeText,
//                ]
//            );
//
//            $response->assertOk();
//
//            $metric = PromptQualityMetric::where('prompt_run_id', $promptRun->id)->first();
//            expect($metric->rating_explanation)->toBe($unicodeText);
//        });
//    });
//
//    describe('Data Integrity', function () {
//        test('preserves existing data when updating rating', function () {
//            $promptRun = PromptRun::factory()->for($this->user)->create();
//            $timestamp = now()->subDay();
//
//            // Create initial metric with specific timestamp
//            PromptQualityMetric::create([
//                'prompt_run_id' => $promptRun->id,
//                'user_rating' => 3,
//                'rating_explanation' => 'Initial rating',
//                'created_at' => $timestamp,
//            ]);
//
//            // Update via API
//            $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun->id),
//                ['rating' => 5]
//            );
//
//            $metric = PromptQualityMetric::where('prompt_run_id', $promptRun->id)->first();
//            expect($metric->user_rating)->toBe(5);
//            expect($metric->created_at->format('Y-m-d'))->toBe($timestamp->format('Y-m-d'));
//        });
//
//        test('does not affect other prompt runs when rating one', function () {
//            $promptRun1 = PromptRun::factory()->for($this->user)->create();
//            $promptRun2 = PromptRun::factory()->for($this->user)->create();
//
//            // Create metric for promptRun2
//            PromptQualityMetric::create([
//                'prompt_run_id' => $promptRun2->id,
//                'user_rating' => 4,
//            ]);
//
//            // Rate promptRun1
//            $this->actingAs($this->user)->postJson(
//                route('api.prompt-runs.rate', $promptRun1->id),
//                ['rating' => 5]
//            );
//
//            // Verify promptRun2's rating unchanged
//            $metric2 = PromptQualityMetric::where('prompt_run_id', $promptRun2->id)->first();
//            expect($metric2->user_rating)->toBe(4);
//        });
//    });
// });
