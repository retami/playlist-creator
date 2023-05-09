<?php

declare(strict_types=1);

namespace PlaylistCreator\Model;

final class SearchResult
{
    public function __construct(public readonly string $title, public readonly string $id)
    {
    }
}
