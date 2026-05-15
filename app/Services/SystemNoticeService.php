<?php
namespace App\Services;

use App\Jobs\ExpireSystemNoticeJob;
use App\Jobs\PublishSystemNoticeJob;
use App\Models\SystemNotice;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class SystemNoticeService
{
    public function getAllNotices(array $filters = [])
    {
        $page    = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 15;
        $search  = $filters['search'] ?? null;
        $status  = $filters['status'] ?? null;

        $query = SystemNotice::with('creator');

        if ($search) {
            $query->where('title', 'like', "%{$search}%");
        }

        if ($status) {
            $query->where('status', $status);
        }

        return $query->orderBy('publish_date', 'desc')->paginate($perPage, ['*'], 'page', $page);
    }

    public function getNoticeById($id)
    {
        try {
            $notice = SystemNotice::with('creator')->find($id);
            if (! $notice) {
                throw new \Exception("System notice with ID {$id} not found.");
            }

            return $notice;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve system notice: ' . $e->getMessage());
            throw $e;
        }
    }

    // public function createNotice($data)
    // {
    //     if (auth()->check()) {
    //         $data['created_by'] = auth()->id();
    //     }

    //     Log::info('System notice payload', [
    //         'data'         => $data,
    //         'app_timezone' => config('app.timezone'),
    //         'now'          => now()->format('Y-m-d H:i:s P'),
    //     ]);

    //     $systemNotice = SystemNotice::create($data);

    //     // Publish Job
    //     if ($systemNotice->publish_date > now()) {
    //         PublishSystemNoticeJob::dispatch($systemNotice->id)
    //         ->delay($systemNotice->publish_date);
    //     }

    //     // Expire Job
    //     if ($systemNotice->expire_at > now()) {
    //         ExpireSystemNoticeJob::dispatch($systemNotice->id)
    //             ->delay($systemNotice->expire_at);
    //     }

    //     return $systemNotice;
    // }

    public function createNotice($data)
    {
        if (auth()->check()) {
            $data['created_by'] = auth()->id();
        }

        if (! empty($data['publish_date'])) {
            $date                 = substr(str_replace('T', ' ', $data['publish_date']), 0, 10);
            $data['publish_date'] = $date . ' 00:00:00';
        }

        if (! empty($data['expire_at'])) {
            $date              = substr(str_replace('T', ' ', $data['expire_at']), 0, 10);
            $data['expire_at'] = $date . ' 23:59:59';
        }

        Log::info('System notice payload', [
            'data' => $data,
            'now'  => now()->format('Y-m-d H:i:s'),
        ]);

        $systemNotice = SystemNotice::create($data);

        // Publish Job
        if (! empty($systemNotice->publish_date)) {
            $publishAt = Carbon::parse($systemNotice->publish_date);

            if ($publishAt->isToday() || $publishAt->isPast()) {
                PublishSystemNoticeJob::dispatch($systemNotice->id);
            } else {
                PublishSystemNoticeJob::dispatch($systemNotice->id)
                    ->delay($publishAt);
            }
        }

        // Expire Job
        if (! empty($systemNotice->expire_at)) {
            $expireAt = Carbon::parse($systemNotice->expire_at);

            if ($expireAt->isFuture()) {
                ExpireSystemNoticeJob::dispatch($systemNotice->id)
                    ->delay($expireAt);
            }
        }

        return $systemNotice;
    }

    // public function updateNotice($id, $data)
    // {
    //     $notice = SystemNotice::find($id);
    //     if ($notice) {
    //         $notice->update($data);

    //         return $notice;
    //     }

    //     return null;
    // }

    // public function updateNotice($id, $data)
    // {
    //     $notice = SystemNotice::find($id);

    //     if (! $notice) {
    //         return null;
    //     }

    //     if (! empty($data['publish_date'])) {
    //         $date                 = substr(str_replace('T', ' ', $data['publish_date']), 0, 10);
    //         $data['publish_date'] = $date . ' 00:00:00';
    //     }

    //     if (! empty($data['expire_at'])) {
    //         $date              = substr(str_replace('T', ' ', $data['expire_at']), 0, 10);
    //         $data['expire_at'] = $date . ' 23:59:59';
    //     }

    //     $notice->update($data);
    //     $notice->refresh();

    //     // Publish Job
    //     if (! empty($notice->publish_date)) {
    //         $publishAt = Carbon::parse($notice->publish_date);

    //         if ($publishAt->isToday() || $publishAt->isPast()) {
    //             PublishSystemNoticeJob::dispatch($notice->id);
    //         } else {
    //             PublishSystemNoticeJob::dispatch($notice->id)
    //                 ->delay($publishAt);
    //         }
    //     }

    //     // Expire Job
    //     if (! empty($notice->expire_at)) {
    //         $expireAt = Carbon::parse($notice->expire_at);

    //         if ($expireAt->isFuture()) {
    //             ExpireSystemNoticeJob::dispatch($notice->id)
    //                 ->delay($expireAt);
    //         }
    //     }

    //     return $notice;
    // }

    public function updateNotice($id, $data)
    {
        $notice = SystemNotice::find($id);

        if (! $notice) {
            return null;
        }

        /*
     * Validate status
     * Only allowed: pending, published, removed
     */
        if (array_key_exists('status', $data)) {
            $allowedStatuses = ['pending', 'published', 'removed'];

            $data['status'] = strtolower(trim($data['status']));

            if (! in_array($data['status'], $allowedStatuses, true)) {
                throw ValidationException::withMessages([
                    'status' => 'Invalid status. Allowed statuses are pending, published, removed.',
                ]);
            }
        }

        if (! empty($data['publish_date'])) {
            $date                 = substr(str_replace('T', ' ', $data['publish_date']), 0, 10);
            $data['publish_date'] = $date . ' 00:00:00';
        }

        if (! empty($data['expire_at'])) {
            $date              = substr(str_replace('T', ' ', $data['expire_at']), 0, 10);
            $data['expire_at'] = $date . ' 23:59:59';
        }

        $notice->update($data);
        $notice->refresh();

        /*
     * Do not dispatch jobs if notice is removed
     */
        if ($notice->status === 'removed') {
            return $notice;
        }

        // Publish Job
        if (! empty($notice->publish_date) && $notice->status === 'pending') {
            $publishAt = Carbon::parse($notice->publish_date);

            if ($publishAt->isToday() || $publishAt->isPast()) {
                PublishSystemNoticeJob::dispatch($notice->id);
            } else {
                PublishSystemNoticeJob::dispatch($notice->id)
                    ->delay($publishAt);
            }
        }

        // Expire Job
        if (! empty($notice->expire_at) && in_array($notice->status, ['pending', 'published'], true)) {
            $expireAt = Carbon::parse($notice->expire_at);

            if ($expireAt->isFuture()) {
                ExpireSystemNoticeJob::dispatch($notice->id)
                    ->delay($expireAt);
            }
        }

        return $notice;
    }

    public function getActiveNotices()
    {
        return SystemNotice::with('creator')
            ->where('status', 'published')
            ->where('publish_date', '<=', now())
            ->where('expire_at', '>', now())
            ->orderBy('publish_date', 'desc')
            ->get();
    }
}
