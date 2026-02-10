<?php

namespace App\Services;

use App\Models\SystemNotice;
use Illuminate\Support\Facades\Log;

class SystemNoticeService
{
    public function getAllNotices()
    {
        return SystemNotice::all();
    }

    public function getNoticeById($id)
    {
        try {
            $notice = SystemNotice::find($id);
            if (! $notice) {
                throw new \Exception("System notice with ID {$id} not found.");
            }

            return $notice;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve system notice: '.$e->getMessage());
            throw $e;
        }
    }

    public function createNotice($data)
    {
        $systemNotice = SystemNotice::create($data);

        return $systemNotice;
    }

    public function updateNotice($id, $data)
    {
        $notice = SystemNotice::find($id);
        if ($notice) {
            $notice->update($data);

            return $notice;
        }

        return null;
    }
}
