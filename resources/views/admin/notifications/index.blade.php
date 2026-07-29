<x-app-layout>
    <div class="page-section">
        <div class="app-container notif-config-page page-stack">
            @if (session('status'))
                <div class="alert alert--success" role="status">{{ session('status') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert--danger" role="alert">
                    @foreach ($errors->all() as $error)
                        <p style="margin: 0;">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <div class="panel notif-config-intro">
                <div class="panel__header">
                    <p class="eyebrow">Administracion</p>
                    <h2 class="panel-title">Configuracion de notificaciones</h2>
                    <p class="panel-text">
                        Busque el aviso, escriba el correo y pulse <strong>Agregar</strong>.
                        Para quitarlo, use <strong>Eliminar</strong> en la lista.
                    </p>
                </div>
            </div>

            @forelse ($moduleGroups as $group)
                <section class="panel notif-config-module" aria-labelledby="notif-module-{{ $group['module'] }}">
                    <div class="panel__header">
                        <h3 id="notif-module-{{ $group['module'] }}" class="panel-title">{{ $group['module_label'] }}</h3>
                    </div>
                    <div class="panel__body">
                        @foreach ($group['types'] as $type)
                            <article class="notif-config-type" id="notification-type-{{ $type['id'] }}">
                                <h4 class="notif-config-type__title">{{ $type['label'] }}</h4>
                                @if ($type['description'])
                                    <p class="notif-config-type__desc">{{ $type['description'] }}</p>
                                @endif

                                <form
                                    method="POST"
                                    action="{{ route('admin.notifications.types.emails.attach', ['notification_type' => $type['id']]) }}"
                                    class="notif-config-type__add"
                                >
                                    @csrf
                                    <div class="form-field" style="margin: 0;">
                                        <x-input-label for="email-type-{{ $type['id'] }}" value="Correo destinatario" />
                                        <input
                                            id="email-type-{{ $type['id'] }}"
                                            name="email"
                                            type="email"
                                            class="form-input"
                                            placeholder="nombre@empresa.com"
                                            required
                                            autocomplete="email"
                                            value="{{ old('email') }}"
                                        >
                                    </div>
                                    <button type="submit" class="btn btn--primary">Agregar</button>
                                </form>

                                @if (count($type['emails']) === 0)
                                    <p class="notif-config-empty">Sin destinatarios. Si no agrega correos, se usara el respaldo del sistema al enviar.</p>
                                @else
                                    <ul class="notif-config-recipients">
                                        @foreach ($type['emails'] as $assignedEmail)
                                            <li class="notif-config-recipient">
                                                <span class="notif-config-recipient__email">{{ $assignedEmail['name'] }}</span>
                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.notifications.types.emails.detach', ['notification_type' => $type['id'], 'notification_email' => $assignedEmail['id']]) }}"
                                                    onsubmit="return confirm('Quitar este correo del aviso?');"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn--danger btn--sm">Eliminar</button>
                                                </form>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endif
                            </article>
                        @endforeach
                    </div>
                </section>
            @empty
                <div class="panel">
                    <div class="panel__body">
                        <p class="text-muted">No hay avisos configurables.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
