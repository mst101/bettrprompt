<?php

namespace App\ValueObjects;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

readonly class N8nErrorDetails implements Arrayable, JsonSerializable
{
    public function __construct(
        public ?string $httpCode = null,
        public ?string $errorType = null,
        public ?string $description = null,
        public ?string $apiMessage = null,
        public ?string $nodeName = null,
        public ?string $time = null,
        public ?string $rawError = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            httpCode: $data['http_code'] ?? null,
            errorType: $data['error_type'] ?? null,
            description: $data['description'] ?? null,
            apiMessage: $data['api_message'] ?? null,
            nodeName: $data['node_name'] ?? null,
            time: $data['time'] ?? null,
            rawError: $data['raw_error'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'http_code' => $this->httpCode,
            'error_type' => $this->errorType,
            'description' => $this->description,
            'api_message' => $this->apiMessage,
            'node_name' => $this->nodeName,
            'time' => $this->time,
            'raw_error' => $this->rawError,
        ], fn ($value) => $value !== null);
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
