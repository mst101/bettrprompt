<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

readonly class N8nErrorResponse implements Arrayable, JsonSerializable
{
    public function __construct(
        public int $promptRunId,
        public bool $success,
        public string $error,
        public N8nErrorDetails $details,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            promptRunId: $data['prompt_run_id'],
            success: $data['success'] ?? false,
            error: $data['error'],
            details: N8nErrorDetails::fromArray($data['details'] ?? []),
        );
    }

    public function toArray(): array
    {
        return [
            'prompt_run_id' => $this->promptRunId,
            'success' => $this->success,
            'error' => $this->error,
            'details' => $this->details->toArray(),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
