<?php
namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LexiconTranslationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $translations = $this->translations->keyBy('language');

        $parentLanguage = $this->language;
        $parentKeywords = $this->keywords;

        return [
            'id'           => $this->id,
            'keywords'     => $this->keywords,
            'translations' => [
                'zh' => $translations->get('zh')?->keywords ?? ($parentLanguage === 'zh' ? $parentKeywords : null),
                'en' => $translations->get('en')?->keywords ?? ($parentLanguage === 'en' ? $parentKeywords : null),
                'ja' => $translations->get('ja')?->keywords ?? ($parentLanguage === 'ja' ? $parentKeywords : null),
            ],
        ];
    }
}
