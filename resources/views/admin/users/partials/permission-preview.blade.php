@props(['selectedRole' => null])

<aside class="user-permissions-layout__preview" aria-labelledby="permission-preview-heading">
    <div class="card card--muted user-permission-preview">
        <h4 id="permission-preview-heading" class="panel-title">Resumen de acceso</h4>
        <p class="text-small text-muted block-spaced-sm">
            Los permisos marcados se suman a las capacidades base del rol seleccionado.
        </p>

        <div class="user-permission-preview__role">
            <p class="text-caption">Rol principal</p>
            <p id="permission-preview-role" class="text-small text-small--strong">
                {{ $selectedRole ? ucfirst($selectedRole) : 'Sin rol' }}
            </p>
        </div>

        <div class="user-permission-preview__count">
            <p class="text-caption">Permisos adicionales</p>
            <p id="permission-preview-count" class="text-small text-small--strong">0 permisos adicionales</p>
        </div>

        <p id="permission-preview-empty" class="text-small text-muted block-spaced-sm">
            Sin permisos adicionales asignados.
        </p>

        <ul id="permission-preview-tags" class="user-permission-tags" aria-live="polite"></ul>
    </div>
</aside>
