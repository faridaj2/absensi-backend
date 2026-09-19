<?php

namespace App\Services;

use App\Models\Instansi;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Proxy ke API eksternal data siswa di admin.darussalam2.com.
 *
 * Kontrak API:
 * - GET /api/gettable?formal={kode}&status=active&page={n}  → daftar siswa per kelas formal
 * - GET /api/gettable?diniyah={kode}&status=active&page={n} → daftar siswa per kelas diniyah
 * - GET /api/getSiswaById?id={id}                            → detail siswa
 * Auth: Bearer token statis (SISWA_API_TOKEN).
 * Response gettable: { paginate: { data: [...], last_page: N }, ... }
 */
class SiswaApiService
{
    public function daftarSiswa(int $instansiId, string $kelasId): array
    {
        if ($this->isMock()) {
            return $this->mockSiswa($kelasId);
        }

        $kolom = $this->kolomUntukInstansi($instansiId); // 'formal' | 'diniyah'

        return Cache::remember("siswa_api:kelas:{$kolom}:{$kelasId}", now()->addMinutes(10), function () use ($kolom, $kelasId) {
            $res = $this->requestPublic('/api/public/siswa', [
                $kolom => $kelasId,
                'status' => 'active',
            ]);

            if (! is_array($res)) {
                return [];
            }

            return collect($res)
                ->map(fn ($r) => [
                    'id' => (string) data_get($r, 'id'),
                    'nama' => \Illuminate\Support\Str::title(mb_strtolower((string) data_get($r, 'nama_siswa', ''))),
                    'nis' => data_get($r, 'nis'),
                    'kelas' => data_get($r, $kolom),
                ])
                ->values()
                ->all();
        });
    }

    public function namaSiswa(int $instansiId, string $siswaId): ?string
    {
        if ($this->isMock()) {
            return 'Siswa '.$siswaId;
        }

        $all = Cache::remember('siswa_api:all', now()->addMinutes(10), function () {
            $res = $this->requestPublic('/api/public/siswa');
            return is_array($res) ? $res : [];
        });

        $row = collect($all)->firstWhere('id', (int) $siswaId);

        if (! $row) {
            return null;
        }

        $nama = (string) ($row['nama_siswa'] ?? '');

        return $nama === '' ? null : \Illuminate\Support\Str::title(mb_strtolower($nama));
    }

    /**
     * Ambil master kelas dari admin.darussalam2.com (endpoint publik dengan API key).
     *
     * @return array{formal: array, diniyah: array, kamar: array}
     */
    public function daftarKelasDariAdmin(): array
    {
        if ($this->isMock()) {
            return ['formal' => [], 'diniyah' => [], 'kamar' => []];
        }

        return Cache::remember('siswa_api:master_kelas', now()->addMinutes(30), function () {
            $res = $this->requestPublic('/api/public/kelas');

            if ($res === null) {
                return ['formal' => [], 'diniyah' => [], 'kamar' => []];
            }

            return [
                'formal' => $res['formal'] ?? [],
                'diniyah' => $res['diniyah'] ?? [],
                'kamar' => $res['kamar'] ?? [],
            ];
        });
    }

    private function kolomUntukInstansi(int $instansiId): string
    {
        $instansi = Instansi::find($instansiId);
        $jenis = $instansi?->jenis_kelas_siswa;

        return $jenis === Instansi::KELAS_DINIYAH ? 'diniyah' : 'formal';
    }

    private function isMock(): bool
    {
        return (bool) config('services.siswa_api.mock', true);
    }

    private function baseUrl(): ?string
    {
        return rtrim((string) config('services.siswa_api.base_url'), '/') ?: null;
    }

    private function token(): ?string
    {
        return (string) config('services.siswa_api.token') ?: null;
    }

    /**
     * GET JSON dengan Bearer token. Return array|null.
     */
    private function request(string $path, array $query = []): ?array
    {
        $base = $this->baseUrl();
        $token = $this->token();

        if (! $base || ! $token) {
            Log::warning('SiswaApiService: base_url atau token belum diisi.');
            return null;
        }

        try {
            $http = Http::withToken($token)
                ->acceptJson()
                ->timeout(15);

            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $res = $http->get($base.$path, $query);

            if (! $res->successful()) {
                Log::warning('SiswaApiService: HTTP '.$res->status().' dari '.$base.$path);
                return null;
            }

            return $res->json();
        } catch (\Throwable $e) {
            Log::error('SiswaApiService: '.$e->getMessage(), ['path' => $path, 'query' => $query]);
            return null;
        }
    }

    /**
     * GET JSON via API key (untuk endpoint publik). Return array|null.
     */
    private function requestPublic(string $path, array $query = []): ?array
    {
        $base = $this->baseUrl();
        $apiKey = (string) config('services.siswa_api.api_key') ?: null;

        if (! $base || ! $apiKey) {
            Log::warning('SiswaApiService: base_url atau api_key belum diisi.');
            return null;
        }

        try {
            $http = Http::withHeaders(['X-API-Key' => $apiKey])
                ->acceptJson()
                ->timeout(15);

            if (app()->environment('local')) {
                $http = $http->withoutVerifying();
            }

            $res = $http->get($base.$path, $query);

            if (! $res->successful()) {
                Log::warning('SiswaApiService: HTTP '.$res->status().' dari '.$base.$path);
                return null;
            }

            return $res->json();
        } catch (\Throwable $e) {
            Log::error('SiswaApiService: '.$e->getMessage(), ['path' => $path, 'query' => $query]);
            return null;
        }
    }

    private function mockSiswa(string $kelasId): array
    {
        return [
            ['id' => $kelasId.'-001', 'nama' => 'Siswa Satu', 'nis' => '1001', 'kelas' => $kelasId],
            ['id' => $kelasId.'-002', 'nama' => 'Siswa Dua', 'nis' => '1002', 'kelas' => $kelasId],
            ['id' => $kelasId.'-003', 'nama' => 'Siswa Tiga', 'nis' => '1003', 'kelas' => $kelasId],
            ['id' => $kelasId.'-004', 'nama' => 'Siswa Empat', 'nis' => '1004', 'kelas' => $kelasId],
        ];
    }
}
