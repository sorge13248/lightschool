<?php

use App\Console\Commands\CleanExpiredDataExports;
use App\Console\Commands\ProcessUserDeletions;
use Illuminate\Support\Facades\Schedule;

Schedule::command(ProcessUserDeletions::class)->daily();
Schedule::command(CleanExpiredDataExports::class)->daily();
