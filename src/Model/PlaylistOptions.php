<?php

declare(strict_types=1);

namespace PlaylistCreator\Model;

class PlaylistOptions
{
    public function __construct(public readonly ?string $id, public readonly ?string $title)
    {
    }
}
