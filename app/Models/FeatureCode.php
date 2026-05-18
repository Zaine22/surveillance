<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class FeatureCode extends Model
{
    use HasUuids;

    protected $table = 'feature_codes';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        // 'title',
        'feature_code',
        'remark',
        'image_path',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return asset('storage/' . $this->image_path);
    }
}
