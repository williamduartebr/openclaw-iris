<?php

namespace Src\Content\Application\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MediaAssetResolver
{
    public function resolve(int $mediaId): ?array
    {
        $row = DB::table('media_assets')
            ->where('id', $mediaId)
            ->select(['id', 'status', 'processed_path', 'original_path'])
            ->first();

        if (! $row) {
            return null;
        }

        return [
            'id' => $row->id,
            'status' => $row->status,
            'final_url' => $row->processed_path
                ? Storage::disk('s3')->url($row->processed_path)
                : ($row->original_path ? Storage::disk('s3')->url($row->original_path) : null),
            'processed_path' => $row->processed_path,
        ];
    }

    public function resolveMany(array $mediaIds): array
    {
        if (empty($mediaIds)) {
            return [];
        }

        $rows = DB::table('media_assets')
            ->whereIn('id', $mediaIds)
            ->select(['id', 'status', 'processed_path', 'original_path'])
            ->get();

        $result = [];
        foreach ($rows as $row) {
            $result[$row->id] = [
                'id' => $row->id,
                'status' => $row->status,
                'final_url' => $row->processed_path
                    ? Storage::disk('s3')->url($row->processed_path)
                    : ($row->original_path ? Storage::disk('s3')->url($row->original_path) : null),
                'processed_path' => $row->processed_path,
            ];
        }

        return $result;
    }
}
