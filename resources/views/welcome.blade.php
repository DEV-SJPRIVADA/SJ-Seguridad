<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SJ Seguridad - Soluciones Integrales en Seguridad Privada</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #20214f;
            --accent: #1984c7;
            --accent-soft: #dceffc;
            --text-dark: #0f172a;
            --text-light: #64748b;
            --white: #ffffff;
            --bg-body: #f8fafc;
            --glass: rgba(255, 255, 255, 0.9);
            --shadow-soft: 0 10px 30px rgba(0, 0, 0, 0.05);
            --shadow-hover: 0 20px 40px rgba(32, 33, 79, 0.1);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; scroll-behavior: smooth; }

        body {
            font-family: 'Inter', sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
            background-color: var(--bg-body);
            overflow-x: hidden;
        }

        h1, h2, h3, h4 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
        }

        /* Header */
        header {
            position: fixed;
            top: 0;
            width: 100%;
            padding: 1.5rem 5%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 1000;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        header.scrolled {
            background: linear-gradient(90deg, var(--primary) 0%, var(--accent) 100%);
            padding: 1rem 5%;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.2);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .logo {
            height: 48px;
            transition: transform 0.3s ease;
        }

        .logo:hover { transform: scale(1.05); }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 2.5rem;
        }

        .nav-links a {
            text-decoration: none;
            color: var(--white);
            font-weight: 600;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        header.scrolled .nav-links a { color: var(--white); opacity: 0.9; }
        header.scrolled .nav-links a:hover { color: var(--white); opacity: 1; }

        .btn-login {
            background: var(--accent);
            color: white !important;
            padding: 0.75rem 1.75rem;
            border-radius: 50px;
            font-weight: 700 !important;
            box-shadow: 0 8px 20px rgba(25, 132, 199, 0.3);
            border: 2px solid transparent;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 25px rgba(25, 132, 199, 0.4);
            filter: brightness(1.1);
        }

        /* Hero Section */
        .hero {
            height: 100vh;
            min-height: 700px;
            background: linear-gradient(rgba(32, 33, 79, 0.75), rgba(32, 33, 79, 0.4)), url('/images/portada Sj.png');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            padding: 0 5%;
        }

        .hero-content {
            max-width: 950px;
            animation: fadeInUp 1.2s ease-out;
        }

        .hero h1 {
            font-size: clamp(2.5rem, 8vw, 4.8rem);
            line-height: 1.1;
            margin-bottom: 1.5rem;
            letter-spacing: -0.02em;
        }

        .hero p {
            font-size: clamp(1.1rem, 2vw, 1.4rem);
            margin-bottom: 2.5rem;
            opacity: 0.9;
            max-width: 750px;
            margin-left: auto;
            margin-right: auto;
            font-weight: 400;
        }

        /* Mission & Vision Stacked Layout */
        .mission-vision {
            padding: 8rem 5%;
            background: var(--white);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 4rem;
        }

        .mv-image-wrap {
            width: 85%; /* Aumentado a 85% para mayor impacto visual */
            max-width: 1200px;
            position: relative;
            margin: 0 auto;
        }

        .mv-container {
            width: 100%;
            max-width: 1200px;
            text-align: center;
        }

        .mv-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2.5rem;
            margin-top: 3rem;
            text-align: left;
        }

        .mv-card {
            background: #f8fafc;
            padding: 3rem;
            border-radius: 30px;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .mv-card:hover {
            background: var(--white);
            box-shadow: var(--shadow-hover);
            border-color: var(--accent-soft);
            transform: scale(1.02);
        }

        .mv-card h3 {
            color: var(--accent);
            margin-bottom: 1rem;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .mv-card p {
            color: var(--text-light);
            font-size: 1.05rem;
            line-height: 1.7;
        }

        .mv-image-wrap {
            position: relative;
            z-index: 1;
            padding: 20px;
            flex: 1;
            min-width: 320px;
            max-width: 600px;
        }

        .mv-image {
            width: 100%;
            border-radius: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            transition: transform 0.5s ease;
            display: block;
        }

        .mv-image:hover {
            transform: scale(1.02);
        }

        /* Values Section */
        .values {
            padding: 8rem 5%;
            background: #f1f5f9;
            text-align: center;
        }

        .values h2 {
            font-size: 3.5rem;
            color: var(--primary);
            margin-bottom: 5rem;
        }

        .values-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2.5rem;
            max-width: 1300px;
            margin: 0 auto;
        }

        .value-card {
            background: var(--white);
            padding: 3.5rem 2.5rem;
            border-radius: 35px;
            box-shadow: var(--shadow-soft);
            transition: all 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            text-align: left;
            border: 1px solid transparent;
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .value-card:hover {
            transform: translateY(-15px);
            box-shadow: var(--shadow-hover);
            border-color: var(--accent-soft);
        }

        .value-icon {
            width: 70px;
            height: 70px;
            background: var(--accent-soft);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--accent);
            transition: all 0.3s ease;
        }

        .value-card:hover .value-icon {
            background: var(--accent);
            color: white;
            transform: rotate(-5deg);
        }

        .value-card h4 {
            font-size: 1.5rem;
            color: var(--primary);
        }

        .value-card p {
            font-size: 0.95rem;
            color: var(--text-light);
            line-height: 1.7;
        }

        /* Footer */
        footer {
            background: var(--primary);
            color: white;
            padding: 6rem 5% 3rem;
            text-align: center;
        }

        .footer-logo { height: 70px; margin-bottom: 2.5rem; }

        .footer-links {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-bottom: 4rem;
        }

        .footer-links a {
            color: rgba(255,255,255,0.7);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .footer-links a:hover { color: white; }

        .copyright {
            padding-top: 3rem;
            border-top: 1px solid rgba(255,255,255,0.1);
            font-size: 0.9rem;
            opacity: 0.6;
        }

        /* Animations */
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        @media (max-width: 968px) {
            .nav-links { display: none; }
            .mission-vision { padding: 5rem 5%; }
            .mv-container h2 { font-size: 2.5rem; }
            .values h2 { font-size: 2.8rem; }
        }
    </style>
</head>
<body>
    <header id="header">
        <img src="{{ asset('images/Logo completo Sj.png') }}" alt="SJ Seguridad" class="logo">
        <nav class="nav-links">
            <a href="#inicio">Inicio</a>
            <a href="#quienes-somos">Quiénes Somos</a>
            <a href="#valores">Valores</a>
            @auth
                <a href="{{ route('dashboard') }}" class="btn-login">Panel Operativo</a>
            @else
                <a href="{{ route('login') }}" class="btn-login">Iniciar Sesión</a>
            @endauth
        </nav>
    </header>

    <section class="hero" id="inicio" style="background: linear-gradient(rgba(32, 33, 79, 0.75), rgba(32, 33, 79, 0.4)), url('{{ asset('images/portada Sj.png') }}'); background-size: cover; background-position: center;">
        <div class="hero-content">
            <span class="section-tag" style="color: white; opacity: 0.8;">Seguridad Integral & Confiable</span>
            <h1>Protegemos lo que más valoras</h1>
            <p>Generamos valor a través de soluciones innovadoras en seguridad privada, protegiendo personas, bienes y procesos con excelencia perdurable.</p>
            <div style="display: flex; gap: 1.5rem; justify-content: center;">
                <a href="{{ route('login') }}" class="btn-login" style="padding: 1.1rem 2.8rem; font-size: 1.1rem;">Acceder al Sistema</a>
                <a href="#quienes-somos" class="btn-login" style="background: rgba(255,255,255,0.1); backdrop-filter: blur(5px); border: 1px solid rgba(255,255,255,0.3); padding: 1.1rem 2.8rem; font-size: 1.1rem;">Conocenos</a>
            </div>
        </div>
    </section>

    <section class="mission-vision" id="quienes-somos">
        <!-- Imagen Grande Arriba (95% para máximo impacto) -->
        <div class="mv-image-wrap" style="width: 95%; max-width: 1300px; margin: 0 auto 3rem;">
            <div style="position: absolute; top: -50px; left: -50px; width: 220px; height: 220px; background: var(--accent-soft); border-radius: 50%; z-index: -1; opacity: 0.4;"></div>
            <img src="{{ asset('images/quienes somos.png') }}" alt="Equipo SJ Seguridad" class="mv-image" style="width: 100%; border-radius: 40px; box-shadow: 0 50px 100px rgba(32, 33, 79, 0.2);">
            <div style="position: absolute; bottom: -50px; right: -50px; width: 260px; height: 260px; border: 4px solid var(--accent-soft); border-radius: 40px; z-index: -1; opacity: 0.3;"></div>
        </div>

        <!-- Encabezado debajo de la imagen -->
        <div class="mv-container">
            <span class="section-tag" style="display: block; text-align: center; color: var(--accent); font-weight: 800; text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 1rem;">Nuestra Esencia</span>
            <h2 style="font-size: clamp(2rem, 4vw, 3.5rem); color: var(--primary); margin-bottom: 1.5rem; text-align: center;">Compromiso con la Excelencia</h2>
            <p style="color: var(--text-light); max-width: 800px; margin: 0 auto 4rem; font-size: 1.2rem; line-height: 1.7; text-align: center;">
                En SJ Seguridad, no solo vigilamos; transformamos la seguridad en un activo estratégico para su tranquilidad y crecimiento.
            </p>
            
            <!-- Misión y Visión una al lado de la otra -->
            <div class="mv-grid">
                <div class="mv-card" style="border-left: 6px solid var(--accent); margin-bottom: 0;">
                    <h3>
                        <div style="width: 44px; height: 44px; background: var(--accent-soft); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--accent);">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                        </div>
                        Nuestra Misión
                    </h3>
                    <p style="font-size: 1.05rem;">SJ Seguridad Privada Ltda., es una compañía que genera valor para los grupos de interés, desarrollando soluciones innovadoras e integrales en seguridad, protegiendo las personas, bienes, instalaciones y procesos de todos nuestros clientes y aliados de negocio, con un desempeño óptimo, exitoso y perdurable.</p>
                </div>
                
                <div class="mv-card" style="border-left: 6px solid var(--primary); margin-bottom: 0;">
                    <h3>
                        <div style="width: 44px; height: 44px; background: var(--brand-blue-pale); border-radius: 12px; display: flex; align-items: center; justify-content: center; color: var(--primary);">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </div>
                        Nuestra Visión
                    </h3>
                    <p style="font-size: 1.05rem;">Posicionarnos en el año 2028 a nivel nacional como un referente de la seguridad privada, siendo confiable e innovador en la gestión integral de riesgos, brindando soluciones transversales y de gran valor para todos los clientes.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="values" id="valores">
        <span class="section-tag">ADN Corporativo</span>
        <h2>Nuestros Valores</h2>
        <div class="values-grid">
            <!-- Liderazgo Transformador -->
            <article class="value-card">
                <div class="value-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                </div>
                <h4>Liderazgo Transformador</h4>
                <p>Creemos en las personas, brindamos autonomía, responsabilidad y recursos necesarios para tomar decisiones que contribuyan al éxito de la organización. Empoderamos a nuestro equipo para liderar experiencias memorables.</p>
            </article>

            <!-- Agilidad Empresarial -->
            <article class="value-card">
                <div class="value-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path></svg>
                </div>
                <h4>Agilidad Empresarial</h4>
                <p>Tenemos la capacidad de adaptarnos rápidamente a los cambios del mercado y tomar decisiones ágiles para mantener la calidad en el servicio y la competitividad constante.</p>
            </article>

            <!-- Espíritu de Equipo -->
            <article class="value-card">
                <div class="value-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <h4>Espíritu de Equipo</h4>
                <p>Abordamos desafíos de manera colaborativa, aprovechando la diversidad de perspectivas. Trabajamos juntos para encontrar soluciones creativas que impulsen el éxito organizacional.</p>
            </article>

            <!-- Resiliencia Organizacional -->
            <article class="value-card">
                <div class="value-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>
                </div>
                <h4>Resiliencia Organizacional</h4>
                <p>Capacidad de recuperación ágil frente a adversidades; aprendemos de las experiencias y nos fortalecemos continuamente frente a los desafíos del futuro.</p>
            </article>

            <!-- Comunicación Asertiva -->
            <article class="value-card">
                <div class="value-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
                </div>
                <h4>Comunicación Asertiva</h4>
                <p>Practicamos una comunicación abierta y transparente en todos los niveles, compartiendo y consolidando información desde el más profundo respeto mutuo.</p>
            </article>

            <!-- Diversidad e Inclusión -->
            <article class="value-card">
                <div class="value-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"></path><path d="M2 12h20"></path></svg>
                </div>
                <h4>Diversidad e Inclusión</h4>
                <p>Promovemos la diversidad de género, etnia y experiencias, creando un ambiente inclusivo donde todas las voces son valoradas y genuinamente respetadas.</p>
            </article>

            <!-- Innovación Abierta -->
            <article class="value-card">
                <div class="value-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"></path></svg>
                </div>
                <h4>Innovación Abierta</h4>
                <p>Propendemos por un crecimiento sostenible con una visión positiva. Incorporamos la innovación como vía constante para la búsqueda de formas creativas que generen valor.</p>
            </article>

            <!-- Sostenibilidad Ambiental -->
            <article class="value-card">
                <div class="value-icon">
                    <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 20A7 7 0 0 1 9.8 6.1a7 7 0 0 1 13.5 1.4A7 7 0 0 1 11 20z"></path><path d="M12 13v9M12 13l-4-4M12 13l4-4"></path></svg>
                </div>
                <h4>Sostenibilidad Ambiental</h4>
                <p>Comprometidos con buenas prácticas empresariales que minimicen el impacto ambiental y promuevan la conservación de recursos naturales para las futuras generaciones.</p>
            </article>
        </div>
    </section>

    <footer>
        <img src="{{ asset('images/Logo completo Sj.png') }}" alt="SJ Seguridad" class="footer-logo">
        <div class="footer-links">
            <a href="#inicio">Inicio</a>
            <a href="#quienes-somos">Quiénes Somos</a>
            <a href="#valores">Valores</a>
            <a href="{{ route('login') }}">Login</a>
        </div>
        <div class="copyright">
            &copy; {{ date('Y') }} SJ Seguridad Privada Ltda. - Excelencia en Seguridad Integral.
        </div>
    </footer>

    <script>
        const header = document.getElementById('header');
        window.addEventListener('scroll', () => {
            if (window.scrollY > 80) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>
