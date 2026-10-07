<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('kaldis:weekly-reminder')->weeklyOn(1, '09:00');
Schedule::command('kaldis:deadline-check')->weeklyOn(1, '12:05');
