<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;

trait CreatesApplication
{
    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        // require bootstrap file
        $app = require __DIR__ . '/../bootstrap/app.php';

        // bootstrap the kernel
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }
}
