<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_request_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->cascadeOnDelete()->index();
            $table->string('original_name', 255);
            $table->string('stored_path', 500);
            $table->string('mime_type', 127)->nullable();
            $table->unsignedInteger('size_bytes');
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();

            $table->index(['purchase_request_id', 'sort_order'], 'pra_request_sort_idx');
        });

        $this->backfillLegacyPedidoFiles();

        DB::table('purchase_requests')->update(['archivo_pedido_path' => null]);
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_attachments');
    }

    private function backfillLegacyPedidoFiles(): void
    {
        $rows = DB::table('purchase_requests')
            ->whereNotNull('archivo_pedido_path')
            ->where('archivo_pedido_path', '!=', '')
            ->get(['id', 'archivo_pedido_path']);

        $now = now();

        foreach ($rows as $row) {
            $legacyPath = (string) $row->archivo_pedido_path;
            $originalName = str_replace(['\\', '/'], '', basename($legacyPath));
            $originalName = $originalName !== '' ? $originalName : 'archivo';
            $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            $storedPath = 'purchase-requests/'.$row->id.'/'.Str::uuid()->toString().($extension !== '' ? '.'.$extension : '');

            $mimeType = null;
            $sizeBytes = 0;

            if (Storage::disk('public')->exists($legacyPath)) {
                Storage::disk('local')->put($storedPath, Storage::disk('public')->get($legacyPath));
                $size = Storage::disk('public')->size($legacyPath);
                $sizeBytes = is_int($size) ? $size : 0;
                $mime = Storage::disk('public')->mimeType($legacyPath);
                $mimeType = is_string($mime) ? mb_substr($mime, 0, 127) : null;
            }

            DB::table('purchase_request_attachments')->insert([
                'purchase_request_id' => $row->id,
                'original_name' => mb_substr($originalName, 0, 255),
                'stored_path' => $storedPath,
                'mime_type' => $mimeType,
                'size_bytes' => $sizeBytes,
                'sort_order' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
