<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AllowedIpSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();

        $ips = [

            // Taiwan IP control
            ['52.95.120.33', '新北市'],
            ['104.16.24.19', '新竹市'],
            ['54.239.28.140', '台南市'],
            ['212.58.244.70', '高雄市1'],
            ['194.95.249.112', '高雄市2'],
            ['104.16.24.18', '台中市'],
            ['142.250.190.14', '宜蘭市'],
            ['52.124.20.180', '彰化市'],
            ['45.32.36.255', '資訊處'],
        ];

        $data = array_map(function ($item) use ($now) {
            return [
                'id'          => (string) Str::uuid(),
                'ip_address'  => $item[0],
                'description' => $item[1],
                'status'      => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }, $ips);

        DB::table('allowed_ips')->insert($data);
    }
}
