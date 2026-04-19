<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_core_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('groups'), 'Table groups does not exist');
        $this->assertTrue(Schema::hasTable('categories'), 'Table categories does not exist');
        $this->assertTrue(Schema::hasTable('forums'), 'Table forums does not exist');
        $this->assertTrue(Schema::hasTable('threads'), 'Table threads does not exist');
        $this->assertTrue(Schema::hasTable('posts'), 'Table posts does not exist');
        $this->assertTrue(Schema::hasTable('reports'), 'Table reports does not exist');
        $this->assertTrue(Schema::hasTable('mod_logs'), 'Table mod_logs does not exist');
        $this->assertTrue(Schema::hasTable('settings'), 'Table settings does not exist');
        $this->assertTrue(Schema::hasTable('post_reactions'), 'Table post_reactions does not exist');
    }
}
