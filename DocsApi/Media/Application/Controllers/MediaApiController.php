<?php

namespace Src\Media\Application\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Src\Media\Application\Jobs\GenerateMediaImage;
use Src\Media\Application\Requests\GenerateImageRequest;
use Src\Media\Application\Requests\ListMediaRequest;
use Src\Media\Application\Resources\MediaAssetCollectionResource;
use Src\Media\Application\Resources\MediaAssetResource;
use Src\Media\Application\Services\MediaGenerationService;
use Src\Media\Domain\Exceptions\ProviderUnavailableException;
use Src\Media\Domain\Models\MediaAsset;

class MediaApiController extends Controller
{
    public function __construct(
        private readonly MediaGenerationService $generationService,
    ) {}

    public function generate(GenerateImageRequest $request): JsonResponse
    {
        try {
            $asset = $this->generationService->createAsset($request->validated());

            GenerateMediaImage::dispatch($asset->id);

            return (new MediaAssetResource($asset))
                ->response()
                ->setStatusCode(202);

        } catch (ProviderUnavailableException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error_code' => 'provider_unavailable',
                'details' => [
                    'provider' => $e->provider,
                    'reason' => $e->reason,
                ],
            ], 503);
        }
    }

    public function show(int $id): JsonResponse
    {
        $asset = MediaAsset::find($id);

        if (! $asset) {
            return response()->json(['message' => 'Media asset not found.'], 404);
        }

        return (new MediaAssetResource($asset))
            ->response()
            ->setStatusCode(200);
    }

    public function index(ListMediaRequest $request): JsonResponse
    {
        $query = MediaAsset::query();

        if ($status = $request->validated('status')) {
            $query->where('status', $status);
        }

        if ($provider = $request->validated('provider')) {
            $query->where('provider', $provider);
        }

        if ($search = $request->validated('search')) {
            $query->where('prompt', 'like', "%{$search}%");
        }

        $sort = $request->validated('sort', '-created_at');
        $direction = str_starts_with($sort, '-') ? 'desc' : 'asc';
        $column = ltrim($sort, '-');

        $query->orderBy($column, $direction);

        $perPage = $request->validated('per_page', 15);
        $paginated = $query->paginate($perPage);

        return MediaAssetCollectionResource::collection($paginated)
            ->response()
            ->setStatusCode(200);
    }

    public function retry(int $id): JsonResponse
    {
        $asset = MediaAsset::find($id);

        if (! $asset) {
            return response()->json(['message' => 'Media asset not found.'], 404);
        }

        if (! $asset->status->isRetryable()) {
            return response()->json([
                'message' => "Cannot retry asset in '{$asset->status->value}' status. Only 'failed' assets can be retried.",
            ], 422);
        }

        $asset = $this->generationService->retryGeneration($asset);

        GenerateMediaImage::dispatch($asset->id);

        return (new MediaAssetResource($asset))
            ->response()
            ->setStatusCode(202);
    }

    public function reprocess(int $id): JsonResponse
    {
        $asset = MediaAsset::find($id);

        if (! $asset) {
            return response()->json(['message' => 'Media asset not found.'], 404);
        }

        if (! $asset->status->isReprocessable()) {
            return response()->json([
                'message' => "Cannot reprocess asset in '{$asset->status->value}' status.",
            ], 422);
        }

        if (! $asset->original_path) {
            return response()->json([
                'message' => 'No original file available for reprocessing.',
            ], 422);
        }

        $asset = $this->generationService->reprocessAsset($asset);

        return (new MediaAssetResource($asset))
            ->response()
            ->setStatusCode(202);
    }
}
