<?php

namespace Src\Content\Application\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Src\Shared\Application\Jobs\DispatchImageCompression;

/**
 * Serviço de upload e compressão de imagens de artigos.
 *
 * Faz upload para S3 (path temporário) e despacha compressão
 * assíncrona via image-compactor (RabbitMQ → Python → WebP).
 */
class ArticleImageStorageService
{
    /** @var string[] Tipos MIME compressíveis para WebP */
    private const COMPRESSIBLE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/bmp',
        'image/tiff',
    ];

    /** Dimensões máximas por tipo de imagem */
    private const IMAGE_OPTIONS = [
        'featured' => ['max_width' => 1200, 'max_height' => 675, 'quality' => 82],
        'content' => ['max_width' => 1200, 'max_height' => 800, 'quality' => 82],
    ];

    /**
     * Faz upload de imagem para S3 e enfileira compressão se aplicável.
     *
     * @param  string  $type  'featured' ou 'content'
     * @return array{path: string, url: string, compressed_path: string|null, compressed_url: string|null, queued_for_compression: bool}
     */
    public function upload(UploadedFile $file, int $articleId, string $type = 'featured'): array
    {
        $mimeType = (string) ($file->getMimeType() ?: $file->getClientMimeType());
        $extension = strtolower((string) ($file->getClientOriginalExtension() ?: $file->guessExtension() ?: 'bin'));
        $baseName = (string) Str::uuid();
        $isCompressible = in_array($mimeType, self::COMPRESSIBLE_MIMES, true) && $extension !== 'webp';

        if ($isCompressible) {
            return $this->uploadWithCompression($file, $articleId, $type, $baseName, $extension, $mimeType);
        }

        return $this->uploadDirect($file, $articleId, $type, $baseName, $extension);
    }

    /**
     * Upload com compressão assíncrona: salva em temp/ e despacha para o image-compactor.
     */
    private function uploadWithCompression(
        UploadedFile $file,
        int $articleId,
        string $type,
        string $baseName,
        string $extension,
        string $mimeType,
    ): array {
        $sourceDirectory = "temp/Content/Article/{$articleId}/{$type}";
        $targetPath = "Content/Article/{$articleId}/{$type}/{$baseName}.webp";
        $sourceFilename = "{$baseName}.{$extension}";

        $sourcePath = $this->storeFile($sourceDirectory, $file, $sourceFilename);

        $options = self::IMAGE_OPTIONS[$type] ?? self::IMAGE_OPTIONS['content'];

        DispatchImageCompression::dispatch(
            jobId: (string) Str::uuid(),
            tenantId: 0,
            entityType: 'article_image',
            entityId: $articleId,
            files: [[
                'file_id' => $baseName,
                'source_path' => $sourcePath,
                'target_path' => $targetPath,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $mimeType,
                'options' => [
                    'quality' => $options['quality'],
                    'max_width' => $options['max_width'],
                    'max_height' => $options['max_height'],
                    'format' => 'webp',
                    'strip_metadata' => true,
                ],
            ]],
        );

        return [
            'path' => $sourcePath,
            'url' => Storage::disk('s3')->url($sourcePath),
            'compressed_path' => $targetPath,
            'compressed_url' => Storage::disk('s3')->url($targetPath),
            'queued_for_compression' => true,
        ];
    }

    /**
     * Upload direto sem compressão (ex.: WebP já otimizado).
     */
    private function uploadDirect(
        UploadedFile $file,
        int $articleId,
        string $type,
        string $baseName,
        string $extension,
    ): array {
        $directory = "Content/Article/{$articleId}/{$type}";
        $filename = "{$baseName}.{$extension}";
        $path = $this->storeFile($directory, $file, $filename);

        return [
            'path' => $path,
            'url' => Storage::disk('s3')->url($path),
            'compressed_path' => null,
            'compressed_url' => null,
            'queued_for_compression' => false,
        ];
    }

    /**
     * Armazena arquivo no S3 com fallback de visibilidade.
     */
    private function storeFile(string $directory, UploadedFile $file, string $filename): string
    {
        $disk = Storage::disk('s3');

        $path = $disk->putFileAs($directory, $file, $filename, ['visibility' => 'public']);
        if (is_string($path) && $path !== '') {
            return $path;
        }

        $fallbackPath = $disk->putFileAs($directory, $file, $filename);
        if (is_string($fallbackPath) && $fallbackPath !== '') {
            return $fallbackPath;
        }

        throw new \RuntimeException('Falha ao enviar imagem para o S3.');
    }
}
