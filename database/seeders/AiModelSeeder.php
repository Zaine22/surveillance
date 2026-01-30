<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AiModelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $models = [
            [
                'id' => Str::uuid(),
                'name' => 'GPT-4',
                'type' => 'text-generation',
                'version' => '4.0',
                'description' => 'OpenAI\'s most advanced multimodal model with strong reasoning capabilities',
                'health_checked_at' => now(),
                'content' => json_encode([
                    'provider' => 'OpenAI',
                    'context_length' => 128000,
                    'max_tokens' => 4096,
                    'capabilities' => ['text', 'vision', 'json_mode'],
                ]),
                'health_status' => 'stable',
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Claude 3 Opus',
                'type' => 'text-generation',
                'version' => '3.0',
                'description' => 'Anthropic\'s most capable model for highly complex tasks',
                'health_checked_at' => now(),
                'content' => json_encode([
                    'provider' => 'Anthropic',
                    'context_length' => 200000,
                    'max_tokens' => 4096,
                    'capabilities' => ['text', 'vision', 'file_upload'],
                ]),
                'health_status' => 'slightly_busy',
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Gemini Pro',
                'type' => 'multimodal',
                'version' => '1.5',
                'description' => 'Google\'s multimodal model excelling in reasoning across text, images, and audio',
                'health_checked_at' => now(),
                'content' => json_encode([
                    'provider' => 'Google',
                    'context_length' => 1000000,
                    'max_tokens' => 8192,
                    'capabilities' => ['text', 'vision', 'audio'],
                ]),
                'health_status' => 'normal',
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Llama 3',
                'type' => 'text-generation',
                'version' => '70B',
                'description' => 'Meta\'s open-source large language model with strong performance',
                'health_checked_at' => now()->subHours(2),
                'content' => json_encode([
                    'provider' => 'Meta',
                    'context_length' => 8192,
                    'max_tokens' => 4096,
                    'capabilities' => ['text', 'function_calling'],
                ]),
                'health_status' => 'stable',
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => Str::uuid(),
                'name' => 'Midjourney v6',
                'type' => 'image-generation',
                'version' => '6.0',
                'description' => 'Advanced AI image generation model with high-quality artistic capabilities',
                'health_checked_at' => now()->subDays(1),
                'content' => json_encode([
                    'provider' => 'Midjourney',
                    'image_size' => '1024x1024',
                    'styles' => ['realistic', 'anime', 'artistic'],
                    'capabilities' => ['image_generation', 'inpainting'],
                ]),
                'health_status' => 'busy',
                'status' => 'enabled',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('ai_models')->insert($models);
    }
}
