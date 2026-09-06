<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\File;

class LogController extends Controller
{
    /**
     * Display parsed and sanitized system logs.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $level = strtolower((string) $request->input('level'));

        $logPath = storage_path('logs/laravel.log');
        $rawLogs = [];

        if (File::exists($logPath)) {
            // Read up to 2MB from end of file to prevent memory exhaustion
            $fileSize = File::size($logPath);
            $maxBytes = 2 * 1024 * 1024;
            $handle = fopen($logPath, 'r');
            if ($handle) {
                if ($fileSize > $maxBytes) {
                    fseek($handle, -$maxBytes, SEEK_END);
                    fgets($handle); // discard possible partial line
                }
                $content = stream_get_contents($handle);
                fclose($handle);

                $rawLogs = $this->parseLogContent($content);
            }
        }

        $logsCollection = collect($rawLogs);

        // Filter by log level
        if ($level !== '' && $level !== 'all') {
            $logsCollection = $logsCollection->filter(function ($item) use ($level) {
                return strtolower($item['level']) === $level;
            });
        }

        // Filter by search query
        if ($search !== '') {
            $logsCollection = $logsCollection->filter(function ($item) use ($search) {
                return stripos($item['message'], $search) !== false || stripos($item['context'], $search) !== false;
            });
        }

        // Paginate results
        $page = (int) $request->input('page', 1);
        $perPage = 25;
        $items = $logsCollection->slice(($page - 1) * $perPage, $perPage)->values();
        $paginatedLogs = new LengthAwarePaginator(
            $items,
            $logsCollection->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $counts = [
            'total' => count($rawLogs),
            'error' => collect($rawLogs)->where('level', 'ERROR')->count(),
            'warning' => collect($rawLogs)->where('level', 'WARNING')->count(),
            'info' => collect($rawLogs)->where('level', 'INFO')->count(),
        ];

        return view('admin.logs.index', [
            'logs' => $paginatedLogs,
            'search' => $search,
            'level' => $level,
            'counts' => $counts,
            'logFileSize' => File::exists($logPath) ? round(File::size($logPath) / 1024, 1) . ' KB' : '0 KB',
        ]);
    }

    /**
     * Parse and sanitize standard Laravel log content into structured entries.
     */
    protected function parseLogContent(string $content): array
    {
        $pattern = '/^\[(\d{4}-\d{2}-\d{2}[ T]\d{2}:\d{2}:\d{2}(?:\.\d+)?(?:[+-]\d{2}:\d{2}|Z)?)\]\s+([a-zA-Z0-9_-]+)\.([a-zA-Z]+):\s+(.*)$/m';
        preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE);

        if (empty($matches[0])) {
            return [];
        }

        $parsed = [];
        $count = count($matches[0]);

        for ($i = 0; $i < $count; $i++) {
            $timestamp = $matches[1][$i][0];
            $environment = $matches[2][$i][0];
            $level = strtoupper($matches[3][$i][0]);
            $headline = $matches[4][$i][0];

            $offset = $matches[0][$i][1];
            $nextOffset = ($i + 1 < $count) ? $matches[0][$i + 1][1] : strlen($content);
            $fullBlock = substr($content, $offset, $nextOffset - $offset);

            // Separate headline from stacktrace / context
            $lines = explode("\n", $fullBlock);
            array_shift($lines); // remove first headline
            $context = implode("\n", array_slice($lines, 0, 15)); // limit stack lines for performance

            // Sanitize secrets from message and context
            $sanitizedMessage = $this->sanitizeLogSecrets($headline);
            $sanitizedContext = $this->sanitizeLogSecrets($context);

            $parsed[] = [
                'timestamp' => $timestamp,
                'environment' => $environment,
                'level' => $level,
                'message' => $sanitizedMessage,
                'context' => $sanitizedContext,
            ];
        }

        // Return latest entries first
        return array_reverse($parsed);
    }

    /**
     * Scrub sensitive keys, tokens, and passwords from logs.
     */
    protected function sanitizeLogSecrets(string $text): string
    {
        $patterns = [
            '/(?:password|passwd|secret|key|token|bearer|authorization)=([^\s&]+)/i' => '$1=••••••••',
            '/(?:eyJ[a-zA-Z0-9_-]{10,}\.[a-zA-Z0-9_-]{10,}\.[a-zA-Z0-9_-]{10,})/' => '••••JWT_TOKEN••••',
            '/(?:sk_live_[a-zA-Z0-9_-]{10,})/' => '••••API_KEY••••',
        ];

        return preg_replace(array_keys($patterns), array_values($patterns), $text) ?? $text;
    }
}
