<?php

use App\Models\PromptRun;
use App\Models\QuestionAnalytic;
use App\Models\User;
use App\Models\Visitor;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->visitor = Visitor::factory()->create();
});

describe('QuestionRatingController', function () {
    describe('Authentication & Authorization', function () {
        test('authenticated user can rate their prompt\'s questions', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create([
                'workflow_stage' => '1_completed',
            ]);

            // Create question analytics for this prompt run
            $analytic = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);

            $response = $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                [
                    'rating' => 5,
                    'explanation' => 'Great question!',
                ]
            );

            $response->assertOk();
            $response->assertJson(['message' => __('messages.api.question_rating_saved')]);

            // Verify rating was saved
            $updated = $analytic->fresh();
            expect($updated->user_rating)->toBe(5);
            expect($updated->rating_explanation)->toBe('Great question!');
        });

        test('guest visitor can rate questions on accessible prompt run', function () {
            $visitor = $this->visitor;
            $visitorIdString = (string) $visitor->id;

            $promptRun = PromptRun::factory()->create([
                'visitor_id' => $visitor->id,
                'workflow_stage' => '1_completed',
            ]);

            $analytic = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);

            $response = $this
                ->withHeaders([
                    'Cookie' => 'visitor_id='.$visitorIdString,
                ])
                ->postJson(
                    route('api.questions.rate', [
                        'promptRun' => $promptRun->id,
                        'questionId' => 'q-1',
                    ]),
                    [
                        'rating' => 4,
                        'explanation' => 'Good question',
                    ]
                );

            $response->assertOk();

            $updated = $analytic->fresh();
            expect($updated->user_rating)->toBe(4);
        });

        test('unauthenticated user without visitor cookie cannot rate', function () {
            $user = User::factory()->create();
            $promptRun = PromptRun::factory()->for($user)->create();

            $response = $this->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 5]
            );

            $response->assertForbidden();
        });

        test('user cannot rate questions on another user\'s prompt run', function () {
            $otherUser = User::factory()->create();
            $promptRun = PromptRun::factory()->for($otherUser)->create();

            $analytic = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);

            $response = $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 5]
            );

            $response->assertForbidden();
        });

        test('visitor cannot rate questions on another visitor\'s prompt run', function () {
            $otherVisitor = Visitor::factory()->create();
            $promptRun = PromptRun::factory()->for($otherVisitor)->create();

            $analytic = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);

            $response = $this->post(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 5],
                ['HTTP_COOKIE' => 'visitor_id='.$this->visitor->id]
            );

            $response->assertForbidden();
        });

        test('admin can rate questions on any prompt run', function () {
            $admin = User::factory()->create(['is_admin' => true]);
            $otherUser = User::factory()->create();
            $promptRun = PromptRun::factory()->for($otherUser)->create();

            $analytic = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);

            $response = $this->actingAs($admin)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 5]
            );

            $response->assertOk();

            $updated = $analytic->fresh();
            expect($updated->user_rating)->toBe(5);
        });

        test('returns 404 for non-existent prompt run', function () {
            $response = $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => 99999,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 5]
            );

            $response->assertNotFound();
        });
    });

    describe('Validation', function () {
        test('rating is required', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();

            $response = $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                []
            );

            $response->assertUnprocessable();
            $response->assertJsonValidationErrors('rating');
        });

        test('rating must be an integer', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();

            $response = $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 'five']
            );

            $response->assertUnprocessable();
        });

        test('rating must be between 1 and 5', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();

            // Below range
            $response = $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 0]
            );
            $response->assertUnprocessable();

            // Above range
            $response = $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 6]
            );
            $response->assertUnprocessable();
        });

        test('explanation is optional', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $analytic = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);

            $response = $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 3]
            );

            $response->assertOk();

            $updated = $analytic->fresh();
            expect($updated->user_rating)->toBe(3);
            expect($updated->rating_explanation)->toBeNull();
        });

        test('explanation must be a string if provided', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();

            $response = $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                [
                    'rating' => 4,
                    'explanation' => ['not' => 'string'],
                ]
            );

            $response->assertUnprocessable();
        });

        test('explanation cannot exceed 1000 characters', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();

            $response = $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                [
                    'rating' => 4,
                    'explanation' => str_repeat('a', 1001),
                ]
            );

            $response->assertUnprocessable();
        });

        test('explanation can be exactly 1000 characters', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $analytic = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);

            $response = $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                [
                    'rating' => 4,
                    'explanation' => str_repeat('a', 1000),
                ]
            );

            $response->assertOk();

            $updated = $analytic->fresh();
            expect(strlen($updated->rating_explanation))->toBe(1000);
        });
    });

    describe('Rating Storage', function () {
        test('stores rating with all required fields', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $analytic = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);

            $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                [
                    'rating' => 5,
                    'explanation' => 'Excellent question!',
                ]
            );

            $updated = $analytic->fresh();
            expect($updated->user_rating)->toBe(5);
            expect($updated->rating_explanation)->toBe('Excellent question!');
        });

        test('stores only rating without explanation', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $analytic = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);

            $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 3]
            );

            $updated = $analytic->fresh();
            expect($updated->user_rating)->toBe(3);
            expect($updated->rating_explanation)->toBeNull();
        });

        test('updates existing rating when resubmitted', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $analytic = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
                'user_rating' => 2,
                'rating_explanation' => 'Not great',
            ]);

            // Update rating
            $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                [
                    'rating' => 5,
                    'explanation' => 'Actually excellent!',
                ]
            );

            $updated = $analytic->fresh();
            expect($updated->user_rating)->toBe(5);
            expect($updated->rating_explanation)->toBe('Actually excellent!');
        });

        test('can rate multiple questions in same prompt run', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $analytic1 = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);
            $analytic2 = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-2',
            ]);

            $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 5]
            );

            $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-2',
                ]),
                ['rating' => 3]
            );

            $updated1 = $analytic1->fresh();
            $updated2 = $analytic2->fresh();

            expect($updated1->user_rating)->toBe(5);
            expect($updated2->user_rating)->toBe(3);
        });

        test('stores rating with various values', function () {
            foreach ([1, 2, 3, 4, 5] as $rating) {
                $promptRun = PromptRun::factory()->for($this->user)->create();
                $analytic = QuestionAnalytic::factory()->for($promptRun)->create([
                    'question_id' => 'q-1',
                ]);

                $this->actingAs($this->user)->postJson(
                    route('api.questions.rate', [
                        'promptRun' => $promptRun->id,
                        'questionId' => 'q-1',
                    ]),
                    ['rating' => $rating]
                );

                $updated = $analytic->fresh();
                expect($updated->user_rating)->toBe($rating);
            }
        });
    });

    describe('Response Format', function () {
        test('returns success message', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);

            $response = $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 4]
            );

            $response->assertJson(['message' => __('messages.api.question_rating_saved')]);
        });

        test('returns 200 OK status code', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);

            $response = $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 4]
            );

            expect($response->status())->toBe(200);
        });

        test('returns correct JSON content type', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);

            $response = $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 4]
            );

            expect($response->headers->get('Content-Type'))->toContain('application/json');
        });
    });

    describe('Edge Cases', function () {
        test('handles special characters in explanation', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $analytic = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);

            $specialText = "Great! 🎉 It's \"special\" & works well.";

            $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                [
                    'rating' => 5,
                    'explanation' => $specialText,
                ]
            );

            $updated = $analytic->fresh();
            expect($updated->rating_explanation)->toBe($specialText);
        });

        test('handles unicode characters in explanation', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $analytic = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);

            $unicodeText = 'Excellent! 非常好! Excelente!';

            $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                [
                    'rating' => 5,
                    'explanation' => $unicodeText,
                ]
            );

            $updated = $analytic->fresh();
            expect($updated->rating_explanation)->toBe($unicodeText);
        });

        test('can rate questions at different workflow stages', function () {
            foreach (['0_completed', '1_completed', '2_completed'] as $stage) {
                $promptRun = PromptRun::factory()->for($this->user)->create([
                    'workflow_stage' => $stage,
                ]);
                $analytic = QuestionAnalytic::factory()->for($promptRun)->create([
                    'question_id' => 'q-1',
                ]);

                $response = $this->actingAs($this->user)->postJson(
                    route('api.questions.rate', [
                        'promptRun' => $promptRun->id,
                        'questionId' => 'q-1',
                    ]),
                    ['rating' => 5]
                );

                $response->assertOk();
            }
        });

        test('rating persists across multiple rates in same session', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $analytic1 = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);
            $analytic2 = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-2',
            ]);

            // Rate first question
            $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 5]
            );

            // Rate second question
            $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-2',
                ]),
                ['rating' => 3]
            );

            // Verify first rating still exists
            $updated1 = $analytic1->fresh();
            expect($updated1->user_rating)->toBe(5);

            // Verify second rating exists
            $updated2 = $analytic2->fresh();
            expect($updated2->user_rating)->toBe(3);
        });
    });

    describe('Data Integrity', function () {
        test('does not affect other questions when rating one', function () {
            $promptRun = PromptRun::factory()->for($this->user)->create();
            $analytic1 = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);
            $analytic2 = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-2',
                'user_rating' => 4,
            ]);

            // Rate first question
            $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 5]
            );

            // Verify second question's rating unchanged
            $updated2 = $analytic2->fresh();
            expect($updated2->user_rating)->toBe(4);
        });

        test('does not affect other prompt runs when rating one', function () {
            $promptRun1 = PromptRun::factory()->for($this->user)->create();
            $promptRun2 = PromptRun::factory()->for($this->user)->create();

            $analytic1 = QuestionAnalytic::factory()->for($promptRun1)->create([
                'question_id' => 'q-1',
            ]);
            $analytic2 = QuestionAnalytic::factory()->for($promptRun2)->create([
                'question_id' => 'q-1',
                'user_rating' => 3,
            ]);

            // Rate question in first prompt run
            $this->actingAs($this->user)->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun1->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 5]
            );

            // Verify second prompt run's question rating unchanged
            $updated2 = $analytic2->fresh();
            expect($updated2->user_rating)->toBe(3);
        });
    });

    describe('Guest Visitor Permissions', function () {
        test('visitor can rate questions on their own prompt run', function () {
            $visitor = $this->visitor;
            $visitorIdString = (string) $visitor->id;

            $promptRun = PromptRun::factory()->create([
                'visitor_id' => $visitor->id,
            ]);
            $analytic = QuestionAnalytic::factory()->for($promptRun)->create([
                'question_id' => 'q-1',
            ]);

            $response = $this
                ->withHeaders([
                    'Cookie' => 'visitor_id='.$visitorIdString,
                ])
                ->postJson(
                    route('api.questions.rate', [
                        'promptRun' => $promptRun->id,
                        'questionId' => 'q-1',
                    ]),
                    ['rating' => 5]
                );

            $response->assertOk();

            $updated = $analytic->fresh();
            expect($updated->user_rating)->toBe(5);
        });

        test('visitor cannot rate questions without valid visitor cookie', function () {
            $promptRun = PromptRun::factory()->for($this->visitor)->create();

            $response = $this->postJson(
                route('api.questions.rate', [
                    'promptRun' => $promptRun->id,
                    'questionId' => 'q-1',
                ]),
                ['rating' => 5]
            );

            $response->assertForbidden();
        });
    });
});
