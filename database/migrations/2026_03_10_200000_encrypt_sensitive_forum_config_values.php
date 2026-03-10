<?php

use App\Models\ForumConfig;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Encrypt sensitive forum_config values
        $rows = DB::table('forum_config')->get();

        foreach ($rows as $row) {
            if (!ForumConfig::isSensitive($row->key) || $row->value === null || $row->value === '') {
                continue;
            }

            // Skip if already encrypted
            try {
                Crypt::decryptString($row->value);
                continue;
            } catch (DecryptException) {
                // Not yet encrypted — encrypt it
            }

            DB::table('forum_config')
                ->where('id', $row->id)
                ->update(['value' => Crypt::encryptString($row->value)]);
        }

        // Encrypt two_factor_secret on users
        $users = DB::table('users')
            ->whereNotNull('two_factor_secret')
            ->where('two_factor_secret', '!=', '')
            ->get(['id', 'two_factor_secret']);

        foreach ($users as $user) {
            try {
                Crypt::decryptString($user->two_factor_secret);
                continue;
            } catch (DecryptException) {
                // Not yet encrypted
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update(['two_factor_secret' => Crypt::encryptString($user->two_factor_secret)]);
        }
    }

    public function down(): void
    {
        // Decrypt forum_config values back to plain text
        $rows = DB::table('forum_config')->get();

        foreach ($rows as $row) {
            if (!ForumConfig::isSensitive($row->key) || $row->value === null || $row->value === '') {
                continue;
            }

            try {
                $decrypted = Crypt::decryptString($row->value);
            } catch (DecryptException) {
                continue;
            }

            DB::table('forum_config')
                ->where('id', $row->id)
                ->update(['value' => $decrypted]);
        }

        // Decrypt two_factor_secret back to plain text
        $users = DB::table('users')
            ->whereNotNull('two_factor_secret')
            ->where('two_factor_secret', '!=', '')
            ->get(['id', 'two_factor_secret']);

        foreach ($users as $user) {
            try {
                $decrypted = Crypt::decryptString($user->two_factor_secret);
            } catch (DecryptException) {
                continue;
            }

            DB::table('users')
                ->where('id', $user->id)
                ->update(['two_factor_secret' => $decrypted]);
        }
    }
};
