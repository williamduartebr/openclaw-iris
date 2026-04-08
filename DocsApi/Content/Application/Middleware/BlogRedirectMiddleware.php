<?php

namespace Src\Content\Application\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class BlogRedirectMiddleware
{
    private const REDIRECTS_FILE = 'database/import-csv-redirect-301/blog_redirects.json';

    private array $redirects = [];

    public function handle(Request $request, Closure $next)
    {
        $slug = $request->route('slug');

        if (empty($slug)) {
            return $next($request);
        }

        $redirects = $this->getRedirects();

        $oldUrl = 'https://mercadoveiculos.com/blog/'.$slug;

        if (isset($redirects[$oldUrl])) {
            $newUrl = $redirects[$oldUrl];

            return redirect()->away($newUrl, 301);
        }

        return redirect()->to('/artigos', 301);
    }

    private function getRedirects(): array
    {
        if (! empty($this->redirects)) {
            return $this->redirects;
        }

        $cacheKey = 'blog_redirects_map';

        $this->redirects = Cache::remember($cacheKey, now()->addDays(7), function () {
            $filePath = base_path(self::REDIRECTS_FILE);

            if (! file_exists($filePath)) {
                return [];
            }

            $content = file_get_contents($filePath);
            $data = json_decode($content, true);

            return is_array($data) ? $data : [];
        });

        return $this->redirects;
    }
}
