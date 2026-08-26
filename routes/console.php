<?php
use Illuminate\Support\Facades\Schedule;
Schedule::command('143:process-due')->everyMinute()->withoutOverlapping();
Schedule::command('143:achievements')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('143:prune')->dailyAt('03:15')->withoutOverlapping();
