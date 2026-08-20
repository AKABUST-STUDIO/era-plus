<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('google-calendar:renew-channels')->daily();
Schedule::command('travel-expense-extractions:prune')->daily();
