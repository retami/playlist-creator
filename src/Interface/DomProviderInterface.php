<?php

declare(strict_types=1);

namespace PlaylistCreator\Interface;

use Carbon\Carbon;
use PHPHtmlParser\Dom;

interface DomProviderInterface
{
    public function fetchDOM(Carbon $datetime): Dom;
}
