<?php

namespace Mate\Forms\Facades;

use Illuminate\Support\Facades\Facade;
use Mate\Forms\Forms as FormImplementation;

class Forms extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @see \Mate\Forms\Forms
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return FormImplementation::class;
    }
}
