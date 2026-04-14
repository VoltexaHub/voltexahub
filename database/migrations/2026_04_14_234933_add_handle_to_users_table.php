<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('handle', 32)->nullable()->after('name');
        });

        $taken = [];
        foreach (DB::table('users')->select('id', 'name')->get() as $row) {
            $base = Str::slug((string) $row->name, '');
            if ($base === '') $base = 'user'.$row->id;
            $base = substr($base, 0, 28);

            $candidate = $base;
            $n = 1;
            while (in_array($candidate, $taken, true)
                || DB::table('users')->where('handle', $candidate)->exists()) {
                $candidate = substr($base, 0, 28).$n++;
            }
            $taken[] = $candidate;

            DB::table('users')->where('id', $row->id)->update(['handle' => $candidate]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('handle');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['handle']);
            $table->dropColumn('handle');
        });
    }
};
