<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class CurrentDatetimeTest extends TestCase
{
    public function test_current_datetime_is_displayed_correctly()
    {
        $response = $this->get('/attendance');
        $current = Carbon::now()->format('Y-m-d H:i');
        $response->assertSee($current);
    }
}
