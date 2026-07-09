<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quality_documents', function (Blueprint $table) {
            $table->string('process_key')->nullable()->after('code');
            $table->string('origin')->nullable()->after('document_type');
            $table->string('document_status')->nullable()->after('origin');
            $table->string('activity_status')->nullable()->after('document_status');
            $table->string('storage_type')->nullable()->after('activity_status');
            $table->string('current_version')->nullable()->after('storage_type');
            $table->date('last_updated_at')->nullable()->after('current_version');
            $table->string('retention_period')->nullable()->after('last_updated_at');
            $table->text('final_disposition')->nullable()->after('retention_period');
        });

        $processMap = [
            'gestion_humana' => 'gestion_humana',
            'operaciones' => 'gestion_operativa',
            'programacion' => 'gestion_planeacion_programacion',
            'juridico' => 'gestion_juridica',
            'comercial' => 'comercial_servicios_conexos',
            'calidad' => 'gestion_documental',
            'remuneraciones' => 'gestion_financiera',
            'facturacion' => 'gestion_financiera',
            'compras' => 'gestion_administrativa',
        ];

        $typeMap = [
            'formato' => 'formato',
            'indicador' => 'indicador_gestion',
            'instructivo' => 'instructivo',
            'manual' => 'manual',
            'matriz' => 'caracterizacion',
            'formulario' => 'formulario',
            'general' => 'documento_general',
        ];

        DB::table('quality_documents')->orderBy('id')->chunkById(100, function ($documents) use ($processMap, $typeMap): void {
            foreach ($documents as $document) {
                $updates = [];

                if ($document->root_process) {
                    $updates['process_key'] = $processMap[$document->root_process] ?? null;
                }

                if ($document->document_type) {
                    $updates['document_type'] = $typeMap[$document->document_type] ?? $document->document_type;
                }

                if ($updates !== []) {
                    DB::table('quality_documents')->where('id', $document->id)->update($updates);
                }
            }
        });

        Schema::table('quality_documents', function (Blueprint $table) {
            $table->dropColumn('root_process');
        });
    }

    public function down(): void
    {
        Schema::table('quality_documents', function (Blueprint $table) {
            $table->string('root_process')->nullable()->after('code');
        });

        $reverseProcessMap = [
            'gestion_humana' => 'gestion_humana',
            'gestion_operativa' => 'operaciones',
            'gestion_planeacion_programacion' => 'programacion',
            'gestion_juridica' => 'juridico',
            'comercial_servicios_conexos' => 'comercial',
            'gestion_documental' => 'calidad',
            'gestion_financiera' => 'remuneraciones',
            'gestion_administrativa' => 'compras',
        ];

        DB::table('quality_documents')->orderBy('id')->chunkById(100, function ($documents) use ($reverseProcessMap): void {
            foreach ($documents as $document) {
                if ($document->process_key) {
                    DB::table('quality_documents')->where('id', $document->id)->update([
                        'root_process' => $reverseProcessMap[$document->process_key] ?? null,
                    ]);
                }
            }
        });

        Schema::table('quality_documents', function (Blueprint $table) {
            $table->dropColumn([
                'process_key',
                'origin',
                'document_status',
                'activity_status',
                'storage_type',
                'current_version',
                'last_updated_at',
                'retention_period',
                'final_disposition',
            ]);
        });
    }
};
