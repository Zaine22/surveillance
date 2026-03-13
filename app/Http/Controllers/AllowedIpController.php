<?php
namespace App\Http\Controllers;

use App\Http\Requests\BulkAllowedIpRequest;
use App\Models\AllowedIp;
use Illuminate\Support\Str;

class AllowedIpController extends Controller
{
    public function index()
    {
        $records = AllowedIp::where('status', true)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($item) => "{$item->description}, {$item->ip_address}");

        return response()->json([
            'records' => $records,
        ]);
    }
    public function bulkStore(BulkAllowedIpRequest $request)
    {
        $records = $request->validated()['records'];

        $lines = preg_split('/\r\n|\r|\n/', $records);

        $inserted = [];
        $skipped  = [];

        foreach ($lines as $line) {

            $line = trim($line);

            if (! $line) {
                continue;
            }

            $parts = explode(',', $line);

            if (count($parts) !== 2) {
                $skipped[] = $line;
                continue;
            }

            $description = trim($parts[0]);
            $ip          = trim($parts[1]);

            if (! filter_var($ip, FILTER_VALIDATE_IP)) {
                $skipped[] = $line;
                continue;
            }

            if (AllowedIp::where('ip_address', $ip)->exists()) {
                $skipped[] = $ip;
                continue;
            }

            AllowedIp::create([
                'id'          => Str::uuid(),
                'ip_address'  => $ip,
                'description' => $description,
                'status'      => true,
            ]);

            $inserted[] = $ip;
        }

        return response()->json([
            'message'  => 'Whitelist processed',
            'inserted' => $inserted,
            'skipped'  => $skipped,
        ]);
    }
}
