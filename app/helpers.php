<?php

function inertiaPaginated($paginator, $resource): array
{
    return [
        'data' => $resource::collection($paginator->items())->resolve(),
        'meta' => [
            'currentPage' => $paginator->currentPage(),
            'lastPage' => $paginator->lastPage(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
            'perPage' => $paginator->perPage(),
            'path' => $paginator->path(),
            'total' => $paginator->total(),
            'hasMorePages' => $paginator->hasMorePages(),
            'nextPageUrl' => $paginator->nextPageUrl(),
            'prevPageUrl' => $paginator->previousPageUrl(),
        ],
    ];
}
