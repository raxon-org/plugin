<?php

namespace Plugin;

trait Console
{
    public function console_interactive(): void
    {
        @flush();
        if (@ob_get_level() > 0) {
            @ob_end_flush();
        }
        @ob_implicit_flush(true);
    }
}