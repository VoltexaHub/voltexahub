<?php

namespace Tests;

use App\Models\Group;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function makeAdmin(): User
    {
        $group = Group::factory()->create(['permissions' => ['is_admin' => true]]);
        return User::factory()->create(['group_id' => $group->id]);
    }
}
