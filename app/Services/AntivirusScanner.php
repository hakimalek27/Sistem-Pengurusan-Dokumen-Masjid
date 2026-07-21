<?php

namespace App\Services;

use Throwable;

class AntivirusScanner
{
    /** @return array{status:string,signature:?string,message:?string} */
    public function scan(string $contents): array
    {
        if (! config('diwan.clamav.enabled')) {
            return ['status' => 'disabled', 'signature' => null, 'message' => null];
        }

        $errno = 0;
        $error = '';
        $socket = @fsockopen(config('diwan.clamav.host'), (int) config('diwan.clamav.port'), $errno, $error, (float) config('diwan.clamav.timeout'));
        if (! is_resource($socket)) {
            return ['status' => 'unavailable', 'signature' => null, 'message' => $error ?: 'ClamAV tidak dapat dicapai'];
        }

        stream_set_timeout($socket, (int) config('diwan.clamav.timeout'));
        try {
            $this->writeAll($socket, "zINSTREAM\0");
            foreach (str_split($contents, 1024 * 1024) as $chunk) {
                $this->writeAll($socket, pack('N', strlen($chunk)).$chunk);
            }
            $this->writeAll($socket, pack('N', 0));
            $response = trim((string) stream_get_line($socket, 8192, "\0"));
        } catch (Throwable $exception) {
            return ['status' => 'error', 'signature' => null, 'message' => $exception->getMessage()];
        } finally {
            fclose($socket);
        }

        if (str_ends_with($response, 'OK')) {
            return ['status' => 'clean', 'signature' => null, 'message' => null];
        }
        if (str_contains($response, 'FOUND')) {
            preg_match('/:\s*(.+?)\s+FOUND$/', $response, $matches);
            $signature = trim($matches[1] ?? '');

            return ['status' => 'infected', 'signature' => $signature ?: 'unknown', 'message' => $response];
        }

        return ['status' => 'error', 'signature' => null, 'message' => $response ?: 'Respons ClamAV kosong'];
    }

    /** @param resource $socket */
    protected function writeAll($socket, string $payload): void
    {
        $written = 0;
        $length = strlen($payload);

        while ($written < $length) {
            $count = @fwrite($socket, substr($payload, $written));
            if ($count === false || $count === 0) {
                $meta = stream_get_meta_data($socket);
                throw new \RuntimeException(($meta['timed_out'] ?? false)
                    ? 'Sambungan ClamAV tamat masa.'
                    : 'Sambungan ClamAV terputus semasa imbasan.');
            }
            $written += $count;
        }
    }
}
