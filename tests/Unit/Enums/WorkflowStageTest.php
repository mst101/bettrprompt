<?php

namespace Tests\Unit\Enums;

use App\Enums\WorkflowStage;
use PHPUnit\Framework\TestCase;

class WorkflowStageTest extends TestCase
{
    public function test_pre_analysis_processing_is_processing(): void
    {
        $this->assertTrue(WorkflowStage::PreAnalysisProcessing->isProcessing());
    }

    public function test_analysis_processing_is_processing(): void
    {
        $this->assertTrue(WorkflowStage::AnalysisProcessing->isProcessing());
    }

    public function test_generation_processing_is_processing(): void
    {
        $this->assertTrue(WorkflowStage::GenerationProcessing->isProcessing());
    }

    public function test_completed_stages_are_not_processing(): void
    {
        $this->assertFalse(WorkflowStage::PreAnalysisCompleted->isProcessing());
        $this->assertFalse(WorkflowStage::AnalysisCompleted->isProcessing());
        $this->assertFalse(WorkflowStage::GenerationCompleted->isProcessing());
    }

    public function test_failed_stages_are_not_processing(): void
    {
        $this->assertFalse(WorkflowStage::PreAnalysisFailed->isProcessing());
        $this->assertFalse(WorkflowStage::AnalysisFailed->isProcessing());
        $this->assertFalse(WorkflowStage::GenerationFailed->isProcessing());
    }

    public function test_pre_analysis_completed_is_completed(): void
    {
        $this->assertTrue(WorkflowStage::PreAnalysisCompleted->isCompleted());
    }

    public function test_analysis_completed_is_completed(): void
    {
        $this->assertTrue(WorkflowStage::AnalysisCompleted->isCompleted());
    }

    public function test_generation_completed_is_completed(): void
    {
        $this->assertTrue(WorkflowStage::GenerationCompleted->isCompleted());
    }

    public function test_processing_stages_are_not_completed(): void
    {
        $this->assertFalse(WorkflowStage::PreAnalysisProcessing->isCompleted());
        $this->assertFalse(WorkflowStage::AnalysisProcessing->isCompleted());
        $this->assertFalse(WorkflowStage::GenerationProcessing->isCompleted());
    }

    public function test_failed_stages_are_not_completed(): void
    {
        $this->assertFalse(WorkflowStage::PreAnalysisFailed->isCompleted());
        $this->assertFalse(WorkflowStage::AnalysisFailed->isCompleted());
        $this->assertFalse(WorkflowStage::GenerationFailed->isCompleted());
    }

    public function test_pre_analysis_failed_is_failed(): void
    {
        $this->assertTrue(WorkflowStage::PreAnalysisFailed->isFailed());
    }

    public function test_analysis_failed_is_failed(): void
    {
        $this->assertTrue(WorkflowStage::AnalysisFailed->isFailed());
    }

    public function test_generation_failed_is_failed(): void
    {
        $this->assertTrue(WorkflowStage::GenerationFailed->isFailed());
    }

    public function test_processing_stages_are_not_failed(): void
    {
        $this->assertFalse(WorkflowStage::PreAnalysisProcessing->isFailed());
        $this->assertFalse(WorkflowStage::AnalysisProcessing->isFailed());
        $this->assertFalse(WorkflowStage::GenerationProcessing->isFailed());
    }

    public function test_completed_stages_are_not_failed(): void
    {
        $this->assertFalse(WorkflowStage::PreAnalysisCompleted->isFailed());
        $this->assertFalse(WorkflowStage::AnalysisCompleted->isFailed());
        $this->assertFalse(WorkflowStage::GenerationCompleted->isFailed());
    }

    public function test_get_workflow_number_for_pre_analysis(): void
    {
        $this->assertEquals(0, WorkflowStage::PreAnalysisProcessing->getWorkflowNumber());
        $this->assertEquals(0, WorkflowStage::PreAnalysisCompleted->getWorkflowNumber());
        $this->assertEquals(0, WorkflowStage::PreAnalysisFailed->getWorkflowNumber());
    }

    public function test_get_workflow_number_for_analysis(): void
    {
        $this->assertEquals(1, WorkflowStage::AnalysisProcessing->getWorkflowNumber());
        $this->assertEquals(1, WorkflowStage::AnalysisCompleted->getWorkflowNumber());
        $this->assertEquals(1, WorkflowStage::AnalysisFailed->getWorkflowNumber());
    }

    public function test_get_workflow_number_for_generation(): void
    {
        $this->assertEquals(2, WorkflowStage::GenerationProcessing->getWorkflowNumber());
        $this->assertEquals(2, WorkflowStage::GenerationCompleted->getWorkflowNumber());
        $this->assertEquals(2, WorkflowStage::GenerationFailed->getWorkflowNumber());
    }

    public function test_values_returns_all_enum_values(): void
    {
        $values = WorkflowStage::values();

        $this->assertCount(9, $values);
        $this->assertContains('0_processing', $values);
        $this->assertContains('0_completed', $values);
        $this->assertContains('0_failed', $values);
        $this->assertContains('1_processing', $values);
        $this->assertContains('1_completed', $values);
        $this->assertContains('1_failed', $values);
        $this->assertContains('2_processing', $values);
        $this->assertContains('2_completed', $values);
        $this->assertContains('2_failed', $values);
    }
}
