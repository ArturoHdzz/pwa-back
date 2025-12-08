<?php

namespace App\services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class SupabaseStorageService
{
    protected string $url;
    protected string $key;
    protected string $bucket;

    public function __construct()
    {
        $this->url    = rtrim(config('services.supabase.url'), '/');
        $this->key    = config('services.supabase.key');
        $this->bucket = config('services.supabase.bucket', 'Taks');
    }

    /**
     * Sube un archivo al bucket de Supabase y devuelve la URL pública.
     */
    public function upload(UploadedFile $file, string $folderPath = ''): string
    {
        // Nombre único del archivo
        $filename = Str::uuid()->toString() . '_' . $file->getClientOriginalName();

        // Ruta dentro del bucket
        $objectPath = trim($folderPath . '/' . $filename, '/');

        // Llamada al API REST de Supabase Storage
        $response = Http::withHeaders([
                'apikey'       => $this->key,
                'Authorization'=> 'Bearer ' . $this->key,
            ])
            ->attach('file', file_get_contents($file->getRealPath()), $filename)
            ->post($this->url . "/storage/v1/object/{$this->bucket}", [
                'objectName' => $objectPath,
            ]);

        if (! $response->successful()) {
            // Puedes hacer throw, o solo log y seguir con local
            \Log::error('Error subiendo archivo a Supabase', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new \RuntimeException('No se pudo subir el archivo a Supabase');
        }

        // Si tu bucket es público, la URL pública sigue este formato:
        $publicUrl = $this->url . "/storage/v1/object/public/{$this->bucket}/{$objectPath}";

        return $publicUrl;
    }
}
