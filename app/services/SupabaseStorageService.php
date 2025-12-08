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
        // Ej: tasks/{task_id}/submissions/{profile_id}/{uuid_nombre.pdf}
        $objectPath = trim($folderPath . '/' . $filename, '/');

        // Contenido binario del archivo
        $fileContents = file_get_contents($file->getRealPath());

        // Llamada al API REST de Supabase Storage
        $response = Http::withHeaders([
                'apikey'        => $this->key,
                'Authorization' => 'Bearer ' . $this->key,
                'Content-Type'  => 'application/octet-stream',
            ])
            // ⚠️ Si en LOCAL vuelve el error de SSL, puedes añadir ->withOptions(['verify' => false])
            ->withBody($fileContents, 'application/octet-stream')
            ->post($this->url . "/storage/v1/object/{$this->bucket}/{$objectPath}");

        if (! $response->successful()) {
            \Log::error('Error subiendo archivo a Supabase', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new \RuntimeException('No se pudo subir el archivo a Supabase');
        }

        // Si el bucket es público, esta es la URL pública
        $publicUrl = $this->url . "/storage/v1/object/public/{$this->bucket}/{$objectPath}";

        return $publicUrl;
    }

}
