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
        $updated = [];
        $deleted = [];
        $skipped  = [];

        // Collect all IPs and descriptions from the input
        $inputIps = [];
        $inputDescriptions = [];
        $validLines = [];

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

            // Allow entries without IP addresses (e.g., just names)
            if ($ip && ! filter_var($ip, FILTER_VALIDATE_IP)) {
                $skipped[] = $line;
                continue;
            }

            $validLines[] = ['description' => $description, 'ip' => $ip];
            if ($ip) {
                $inputIps[] = $ip;
            }
            $inputDescriptions[] = $description;
        }

        // Delete records that are not in the input list
        $recordsToDelete = AllowedIp::where('status', true)
            ->where(function($query) use ($inputIps, $inputDescriptions) {
                $query->whereNotIn('ip_address', $inputIps)
                      ->orWhereNotIn('description', $inputDescriptions);
            })
            ->get();

        foreach ($recordsToDelete as $record) {
            $deleted[] = "{$record->description}, {$record->ip_address}";
            $record->delete();
        }

        // Insert or update records from the input
        foreach ($validLines as $line) {
            $description = $line['description'];
            $ip = $line['ip'];

            // Check if record exists by IP or description
            $existing = null;
            if ($ip) {
                $existing = AllowedIp::where('ip_address', $ip)->first();
            }
            if (!$existing) {
                $existing = AllowedIp::where('description', $description)->first();
            }

            if ($existing) {
                // Update existing record
                $existing->update([
                    'ip_address' => $ip ?: null,
                    'description' => $description,
                    'status' => true,
                ]);
                $updated[] = $description;
            } else {
                // Create new record
                AllowedIp::create([
                    'id'          => Str::uuid(),
                    'ip_address'  => $ip ?: null,
                    'description' => $description,
                    'status'      => true,
                ]);
                $inserted[] = $description;
            }
        }

        return response()->json([
            'message' => '白名單已處理',
            'inserted' => $inserted,
            'updated' => $updated,
            'deleted' => $deleted,
            'skipped'  => $skipped,
        ]);
    }
}
