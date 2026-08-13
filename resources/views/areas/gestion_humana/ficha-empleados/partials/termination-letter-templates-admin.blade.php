<div class="panel ficha-empleados-letter-templates">
    <div class="panel__header">
        <h4 class="panel-title">Plantillas Word — cartas de desvinculacion</h4>
        <p class="panel-text">
            Suba un archivo <code>.docx</code> por documento. Use placeholders en corchetes, por ejemplo
            <code>[NOMBRE]</code>, <code>[CEDULA]</code>, <code>[FECHA_TERMINACION]</code>.
        </p>
    </div>
    <div class="panel__body section-stack">
        @if (! empty($terminationLetterPlaceholders))
            <details class="ficha-empleados-letter-templates__placeholders">
                <summary class="text-caption">Variables disponibles</summary>
                <ul class="ficha-empleados-letter-templates__placeholder-list">
                    @foreach ($terminationLetterPlaceholders as $key => $description)
                        <li><code>[{{ $key }}]</code> — {{ $description }}</li>
                    @endforeach
                </ul>
            </details>
        @endif

        <div class="data-table-wrap">
            <table class="data-table" style="width:100%">
                <thead>
                    <tr>
                        <th>Causal</th>
                        <th>Documento</th>
                        <th>Estado plantilla</th>
                        <th style="width:280px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($terminationLetterTemplates->flatten() as $template)
                        <tr>
                            <td><code>{{ $template->termination_cause_code }}</code></td>
                            <td>{{ $template->label }}</td>
                            <td>
                                @if ($template->hasTemplateFile())
                                    <span class="status-pill status-pill--success">Cargada</span>
                                @else
                                    <span class="status-pill status-pill--muted">Pendiente</span>
                                @endif
                            </td>
                            <td class="table-actions ficha-empleados-letter-templates__actions">
                                <form
                                    method="POST"
                                    action="{{ route('gestion-humana.ficha-empleados.catalogs.termination-letter-template.upload', ['causeCode' => $template->termination_cause_code, 'documentKey' => $template->document_key]) }}"
                                    enctype="multipart/form-data"
                                    class="ficha-empleados-letter-templates__upload-form"
                                >
                                    @csrf
                                    <input type="file" name="template" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document" class="form-input form-input--sm" required>
                                    <button type="submit" class="btn btn--primary btn--sm">Subir</button>
                                </form>
                                @if ($template->hasTemplateFile())
                                    <a
                                        href="{{ route('gestion-humana.ficha-empleados.catalogs.termination-letter-template.download', ['causeCode' => $template->termination_cause_code, 'documentKey' => $template->document_key]) }}"
                                        class="btn btn--secondary btn--sm"
                                    >Descargar</a>
                                    <form
                                        method="POST"
                                        action="{{ route('gestion-humana.ficha-empleados.catalogs.termination-letter-template.delete', ['causeCode' => $template->termination_cause_code, 'documentKey' => $template->document_key]) }}"
                                        onsubmit="return confirm('Eliminar esta plantilla Word?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn--danger btn--sm">Quitar</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-muted">Sin plantillas de cartas configuradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
