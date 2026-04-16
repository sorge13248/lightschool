<?php

/*
|--------------------------------------------------------------------------
| Pest bootstrap
|--------------------------------------------------------------------------
|
| All Feature tests share the base TestCase which provides an HTTP client
| pre-configured against APP_URL and a cookie jar for session handling.
|
*/

uses(Tests\TestCase::class)->in('Feature');
