<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('servicio_cliente_casos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('consecutivo')->unique();
            $table->date('fecha_recepcion')->index();
            $table->date('fecha_ejecucion');
            $table->date('fecha_verificacion')->nullable()->index();
            $table->date('fecha_plazo_respuesta')->index();
            $table->string('tipo_cliente', 20)->index();
            $table->foreignId('tipologia_id')->constrained('servicio_cliente_tipologias')->restrictOnDelete();
            $table->foreignId('fuente_id')->constrained('servicio_cliente_fuentes')->restrictOnDelete();
            $table->foreignId('commercial_client_id')->nullable()->constrained('commercial_clients')->nullOnDelete();
            $table->string('cliente_nombre', 255);
            $table->foreignId('city_id')->constrained('requisition_cities')->restrictOnDelete();
            $table->text('descripcion');
            $table->foreignId('categoria_id')->constrained('servicio_cliente_categorias')->restrictOnDelete();
            $table->text('correccion');
            $table->text('accion_realizar');
            $table->foreignId('responsable_id')->constrained('servicio_cliente_responsables')->restrictOnDelete();
            $table->unsignedInteger('dias_habiles')->nullable();
            $table->text('responsable_seguimiento')->nullable();
            $table->string('eficacia', 20)->default('abierto')->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servicio_cliente_casos');
    }
};
