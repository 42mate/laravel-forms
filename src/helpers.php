<?php

use Mate\Forms\Forms;

if (! function_exists('forms')) {
    /**
     * @return \Spatie\Html\Html
     */
    function forms()
    {
        return app(Forms::class);
    }
}
