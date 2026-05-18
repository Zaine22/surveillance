<?php
namespace App\Services;

use App\Models\FeatureCode;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class FeatureCodeService
{
    public function getAllFeatureCodes(array $filters = [])
    {
        $page    = $filters['page'] ?? 1;
        $perPage = $filters['per_page'] ?? 15;
        $search  = $filters['search'] ?? null;

        $query = FeatureCode::query();
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%");
                // ->orWhere('feature_code', 'like', "%{$search}%")
                // ->orWhere('remark', 'like', "%{$search}%");
            });
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function getFeatureCodeById($id)
    {
        try {
            $featureCode = FeatureCode::find($id);

            if (! $featureCode) {
                throw new \Exception("Feature code with ID {$id} not found.");
            }

            return $featureCode;
        } catch (\Exception $e) {
            Log::error('Failed to retrieve feature code: ' . $e->getMessage());
            throw $e;
        }
    }

    public function createFeatureCode(array $data)
    {
        try {
            $payload = Arr::only($data, [
                'title',
                'feature_code',
                'remark',
            ]);

            if (! empty($data['image']) && $data['image'] instanceof UploadedFile) {
                $payload['image_path'] = $this->uploadImage($data['image']);
            }

            // if (empty($payload['title']) && ! empty($payload['feature_code'])) {
            //     $payload['title'] = $payload['feature_code'];
            // }

            Log::info('Feature code payload', [
                'data' => $payload,
                'now'  => now()->format('Y-m-d H:i:s'),
            ]);

            return FeatureCode::create($payload);
        } catch (\Exception $e) {
            Log::error('Failed to create feature code: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateFeatureCode($id, array $data)
    {
        try {
            $featureCode = FeatureCode::find($id);

            if (! $featureCode) {
                return null;
            }

            $payload = Arr::only($data, [
                'title',
                'feature_code',
                'remark',
            ]);

            if (! empty($data['image']) && $data['image'] instanceof UploadedFile) {
                if (
                    $featureCode->image_path &&
                    Storage::disk('public')->exists($featureCode->image_path)
                ) {
                    Storage::disk('public')->delete($featureCode->image_path);
                }

                $payload['image_path'] = $this->uploadImage($data['image']);
            }

            /*
             * Figma has no title input.
             * So if title is not sent but feature_code is changed,
             * use feature_code as title.
             */
            // if (
            //     ! array_key_exists('title', $payload) &&
            //     ! empty($payload['feature_code'])
            // ) {
            //     $payload['title'] = $payload['feature_code'];
            // }

            $featureCode->update($payload);
            $featureCode->refresh();
            return $featureCode;
        } catch (\Exception $e) {
            Log::error('Failed to update feature code: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteFeatureCode($id)
    {
        try {
            $featureCode = FeatureCode::find($id);

            if (! $featureCode) {
                return null;
            }

            if (
                $featureCode->image_path &&
                Storage::disk('public')->exists($featureCode->image_path)
            ) {
                Storage::disk('public')->delete($featureCode->image_path);
            }

            return $featureCode->delete();
        } catch (\Exception $e) {
            Log::error('Failed to delete feature code: ' . $e->getMessage());
            throw $e;
        }
    }

    private function uploadImage(UploadedFile $image): string
    {
        return $image->store('feature-codes', 'public');
    }
}
