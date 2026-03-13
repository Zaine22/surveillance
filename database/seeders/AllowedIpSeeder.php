<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AllowedIpSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('allowed_ips')->insert([
            [
                'id'          => Str::uuid(),
                'ip_address'  => '18.138.40.125',
                'description' => 'Zaine',
                'status'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);

        DB::table('allowed_ips')->insert([
            [
                'id'          => Str::uuid(),
                'ip_address'  => '127.0.0.1',
                'description' => 'Local',
                'status'      => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ]);

    }
}
