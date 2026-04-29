<?php
declare(strict_types=1);

namespace App\Core;

abstract class Controller extends BaseController
{
    public function __construct(protected Request $request)
    {
    }
}
