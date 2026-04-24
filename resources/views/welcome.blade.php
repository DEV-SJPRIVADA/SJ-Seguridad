<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>SJ Seguridad</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @endif
    </head>
    <body class="welcome-page">
            <div class="welcome-shell">
                <header class="welcome-header">
                    <div class="welcome-brand">
                        <p class="welcome-brand__eyebrow">SJ Seguridad</p>
                        <p class="welcome-brand__text">Plataforma modular para control operativo y administrativo.</p>
                    </div>

                    <nav class="welcome-nav">
                        @auth
                            <a href="{{ route('dashboard') }}" class="welcome-btn welcome-btn--ghost">
                                Ir al panel
                            </a>
                        @else
                            <a href="{{ route('login') }}" class="welcome-btn welcome-btn--accent">
                                Iniciar sesion
                            </a>
                        @endauth
                    </nav>
                </header>

                <main class="welcome-main">
                    <section>
                        <p class="welcome-chip">
                            Base SCRUM lista para crecer por areas
                        </p>
                        <h1 class="welcome-title">
                            Seguridad, control de acceso y una estructura reutilizable desde el primer sprint.
                        </h1>
                        <p class="welcome-text">
                            La base inicial incorpora autenticacion, administracion de usuarios, permisos por visualizacion y funcionalidad,
                            y criterios para evolucionar el sistema sin perder compatibilidad con Laragon, Laravel y futuros despliegues en Hostinger.
                        </p>

                        <div class="welcome-actions">
                            <a href="{{ route('login') }}" class="welcome-btn welcome-btn--light">
                                Acceder al sistema
                            </a>
                            <a href="#fundamentos" class="welcome-btn welcome-btn--ghost">
                                Revisar fundamentos
                            </a>
                        </div>
                    </section>

                    <section class="welcome-side">
                        <div class="welcome-side__grid">
                            <article class="welcome-side__card">
                                <p class="welcome-side__label">Acceso</p>
                                <h2 class="welcome-side__title">Ingreso controlado</h2>
                                <p class="welcome-side__text">
                                    Registro publico desactivado. El alta de usuarios queda centralizada en administracion.
                                </p>
                            </article>
                            <article class="welcome-side__card">
                                <p class="welcome-side__label">Seguridad</p>
                                <h2 class="welcome-side__title">Medidas base</h2>
                                <p class="welcome-side__text">
                                    Usuarios inactivos bloqueados, cambio de contrasena inicial y control de permisos por area.
                                </p>
                            </article>
                            <article class="welcome-side__card welcome-side__card--wide">
                                <p class="welcome-side__label">Arquitectura</p>
                                <h2 class="welcome-side__title">Preparada para crecimiento incremental</h2>
                                <p class="welcome-side__text">
                                    Cada modulo nuevo debe entrar como funcionalidad aislada, revisando impacto global y reutilizando
                                    controladores, middleware, politicas, componentes Blade y convenciones de pruebas.
                                </p>
                            </article>
                        </div>
                    </section>
                </main>

                <section id="fundamentos" class="welcome-footer">
                    <article class="welcome-footer__card">
                        <h3 class="welcome-footer__title">Compatibilidad</h3>
                        <p class="welcome-footer__text">
                            Base montada sobre Laravel 11 y PHP 8.2 para priorizar estabilidad con paquetes y entornos de hosting compartido.
                        </p>
                    </article>
                    <article class="welcome-footer__card">
                        <h3 class="welcome-footer__title">Reutilizacion</h3>
                        <p class="welcome-footer__text">
                            Areas, permisos y roles quedan centralizados en configuracion y seeders para evitar logica duplicada.
                        </p>
                    </article>
                    <article class="welcome-footer__card">
                        <h3 class="welcome-footer__title">Operacion</h3>
                        <p class="welcome-footer__text">
                            Se documentaron criterios de revision continua para errores, seguridad y cambios transversales del proyecto.
                        </p>
                    </article>
                </section>
            </div>
    </body>
</html>
