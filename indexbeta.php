<?php

declare(strict_types=1);

require_once __DIR__ . '/config/bootstrap.php';

$pageTitle = 'Centro de mando | Wolk-It Portal interno';
$pageDescription = 'Consulta los accesos, formularios, documentos y recursos internos dentro de Wolk IT.';

require_once __DIR__ . '/includes/head.php';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

?>

<section class="intro-block">
    <div class="eyebrow">Bienvenida al sistema interno de Wolk IT</div>

    <h1>Centro de mando</h1>

    <p>
        Consulta los accesos, formularios, documentos y recursos internos dentro de Wolk IT.
    </p>

    <div class="hero-actions">
        <a class="btn btn--primary" href="<?= e(base_url('solicitudes')) ?>">
            Crear solicitud
        </a>
    </div>
</section>

<section class="top-grid">
    <article class="card about-preview">
        <div class="card-head">
            <div>
                <div class="eyebrow">Acerca de Wolk IT</div>
                <h2 class="card-title">Conoce la visión de la empresa</h2>
            </div>

            <span class="pill">Uso interno</span>
        </div>

        <p class="card-sub">
            Consulta nuestra misión, visión y valores de Wolk IT.
        </p>

        <div class="hero-actions" style="margin-top:16px;">
            <button class="btn btn--ghost" type="button" data-about-open>
                Ver más
            </button>
        </div>
    </article>

    <article class="card newsletter-card">
        <div class="card-head">
            <div>
                <div class="eyebrow">Newsletter del mes</div>
                <h2 class="card-title" id="newsletterCurrentTitle">Newsletter del mes</h2>
            </div>

            <span class="pill" id="newsletterCurrentPill">Mes actual</span>
        </div>

        <p class="card-sub">
            Revisa el comunicado interno del mes correspondiente sin salir del sistema.
        </p>

        <div class="newsletter-meta">
            <span class="tag tag--ok">Actualización automática</span>
            <span class="tag tag--info" id="newsletterMonthTag">Mes actual</span>
        </div>

        <div class="hero-actions" style="margin-top:0;">
            <button class="btn btn--primary" type="button" data-newsletter-open>
                Ver newsletter
            </button>
        </div>
    </article>
</section>

<section class="card section">
    <div class="section-head">
        <div>
            <div class="eyebrow">Certificaciones</div>
            <h2>Estándares y cumplimiento</h2>
            <p>
                Referencias internas relacionadas con seguridad, continuidad, servicios y mejores prácticas.
            </p>
        </div>
    </div>

    <div class="cert-slider" data-cert-slider>
        <div class="cert-track" data-cert-track>
            <article class="cert-slide">
                <div class="cert-badge">ISO<br>27001</div>

                <div class="cert-copy">
                    <h3>Seguridad de la información</h3>
                    <p>
                        Lineamientos para proteger la confidencialidad, integridad y disponibilidad de la información.
                    </p>

                    <div class="cert-nav">
                        <button class="cert-btn" type="button" data-cert-prev>‹</button>
                        <button class="cert-btn" type="button" data-cert-next>›</button>
                    </div>
                </div>
            </article>

            <article class="cert-slide">
                <div class="cert-badge">ISO<br>20000</div>

                <div class="cert-copy">
                    <h3>Gestión de servicios</h3>
                    <p>
                        Base documental para mantener ordenados los procesos de operación, soporte y mejora del servicio.
                    </p>

                    <div class="cert-nav">
                        <button class="cert-btn" type="button" data-cert-prev>‹</button>
                        <button class="cert-btn" type="button" data-cert-next>›</button>
                    </div>
                </div>
            </article>

            <article class="cert-slide">
                <div class="cert-badge">WOLK<br>IT</div>

                <div class="cert-copy">
                    <h3>Gestión interna</h3>
                    <p>
                        Accesos internos para solicitudes, formatos, documentos, directorio y recursos de operación.
                    </p>

                    <div class="cert-nav">
                        <button class="cert-btn" type="button" data-cert-prev>‹</button>
                        <button class="cert-btn" type="button" data-cert-next>›</button>
                    </div>
                </div>
            </article>
        </div>
    </div>
</section>

<section class="card section">
    <div class="section-head">
        <div>
            <div class="eyebrow">Accesos principales</div>
            <h2>Solicitudes y recursos internos</h2>
            <p>
                Accede a los formularios y herramientas internas de uso frecuente.
            </p>
        </div>
    </div>

    <div class="service-groups">
        <div class="service-board">
            <div class="service-board__head">
                <h3 class="service-board__title">Gestión interna</h3>
                <span class="pill">Solicitudes</span>
            </div>

            <div class="service-list">
                <a class="service-item" href="<?= e(base_url('solicitudes')) ?>">
                    <span class="service-ico" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <path d="M18 12h28"></path>
                            <path d="M20 20h24"></path>
                            <path d="M20 28h18"></path>
                            <path d="M20 36h14"></path>
                            <rect x="14" y="8" width="36" height="48" rx="4"></rect>
                        </svg>
                    </span>

                    <span class="service-copy">
                        <strong>Solicitudes generales</strong>
                        <span>Canal para registrar requerimientos internos y seguimiento operativo.</span>
                    </span>

                    <span class="service-right">
                        <span class="tag tag--info">Gestión</span>
                        <span class="service-arrow">›</span>
                    </span>
                </a>

                <a class="service-item" href="<?= e(base_url('vacaciones')) ?>">
                    <span class="service-ico service-ico--green" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <rect x="12" y="16" width="40" height="34" rx="4"></rect>
                            <path d="M12 24h40"></path>
                            <path d="M22 12v8"></path>
                            <path d="M42 12v8"></path>
                            <path d="M23 34h6"></path>
                            <path d="M35 34h6"></path>
                            <path d="M23 42h6"></path>
                        </svg>
                    </span>

                    <span class="service-copy">
                        <strong>Vacaciones y permisos</strong>
                        <span>Formulario para solicitar vacaciones, ausencias o permisos internos.</span>
                    </span>

                    <span class="service-right">
                        <span class="tag tag--ok">TH</span>
                        <span class="service-arrow">›</span>
                    </span>
                </a>

                <a class="service-item" href="<?= e(base_url('solicitud-personal')) ?>">
                    <span class="service-ico service-ico--blue" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <circle cx="32" cy="18" r="8"></circle>
                            <path d="M20 40c2-9 22-9 24 0"></path>
                            <path d="M24 27h16v10H24z"></path>
                            <path d="M32 18v9"></path>
                            <path d="M32 37v7"></path>
                            <path d="M18 44h28"></path>
                            <circle cx="14" cy="50" r="6"></circle>
                            <circle cx="32" cy="50" r="6"></circle>
                            <circle cx="50" cy="50" r="6"></circle>
                        </svg>
                    </span>

                    <span class="service-copy">
                        <strong>Solicitud de personal</strong>
                        <span>Requerimientos de personal, altas internas o apoyo operativo.</span>
                    </span>

                    <span class="service-right">
                        <span class="tag tag--warning">Gestión</span>
                        <span class="service-arrow">›</span>
                    </span>
                </a>

                <a class="service-item" href="<?= e(base_url('equipo-formacion')) ?>">
                    <span class="service-ico service-ico--blue" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <rect x="12" y="16" width="40" height="28" rx="3"></rect>
                            <path d="M24 52h16"></path>
                            <path d="M32 44v8"></path>
                            <circle cx="32" cy="30" r="6"></circle>
                            <path d="M32 20v3"></path>
                            <path d="M32 37v3"></path>
                        </svg>
                    </span>

                    <span class="service-copy">
                        <strong>Equipo y formación</strong>
                        <span>Solicitud relacionada con equipo, accesos o formación interna.</span>
                    </span>

                    <span class="service-right">
                        <span class="tag tag--info">Recursos</span>
                        <span class="service-arrow">›</span>
                    </span>
                </a>

                <a class="service-item" href="<?= e(base_url('devolucion-equipo')) ?>">
                    <span class="service-ico service-ico--green" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <rect x="14" y="14" width="36" height="28" rx="3"></rect>
                            <path d="M24 50h16"></path>
                            <path d="M32 42v8"></path>
                            <path d="M22 28h20"></path>
                            <path d="M30 22l-8 6 8 6"></path>
                        </svg>
                    </span>

                    <span class="service-copy">
                        <strong>Devolución de equipo</strong>
                        <span>Registro para devolución o entrega de equipo asignado.</span>
                    </span>

                    <span class="service-right">
                        <span class="tag tag--warning">Control</span>
                        <span class="service-arrow">›</span>
                    </span>
                </a>
            </div>
        </div>

        <div class="service-board">
            <div class="service-board__head">
                <h3 class="service-board__title">Evaluaciones y cumplimiento</h3>
                <span class="pill">Interno</span>
            </div>

            <div class="service-list">
                <a class="service-item" href="<?= e(base_url('autoevaluacion-preventa')) ?>">
                    <span class="service-ico" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <path d="M16 14h32v36H16z"></path>
                            <path d="M24 24h18"></path>
                            <path d="M24 32h18"></path>
                            <path d="M24 40h10"></path>
                            <path d="M19 24l3 3 5-6"></path>
                        </svg>
                    </span>

                    <span class="service-copy">
                        <strong>Autoevaluación preventa</strong>
                        <span>Formato de revisión interna para actividades de preventa.</span>
                    </span>

                    <span class="service-right">
                        <span class="tag tag--info">Preventa</span>
                        <span class="service-arrow">›</span>
                    </span>
                </a>

                <a class="service-item" href="<?= e(base_url('autoevaluacion-operaciones')) ?>">
                    <span class="service-ico service-ico--blue" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <path d="M16 14h32v36H16z"></path>
                            <path d="M24 24h18"></path>
                            <path d="M24 32h18"></path>
                            <path d="M24 40h10"></path>
                            <path d="M19 24l3 3 5-6"></path>
                        </svg>
                    </span>

                    <span class="service-copy">
                        <strong>Autoevaluación operaciones</strong>
                        <span>Formato de revisión interna para actividades operativas.</span>
                    </span>

                    <span class="service-right">
                        <span class="tag tag--ok">Operaciones</span>
                        <span class="service-arrow">›</span>
                    </span>
                </a>

                <a class="service-item" href="<?= e(base_url('capacitacion-iso27001')) ?>">
                    <span class="service-ico service-ico--green" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <path d="M14 18h36v32H14z"></path>
                            <path d="M22 26h20"></path>
                            <path d="M22 34h20"></path>
                            <path d="M22 42h12"></path>
                            <path d="M50 18l-8-6H22l-8 6"></path>
                        </svg>
                    </span>

                    <span class="service-copy">
                        <strong>Capacitación ISO 27001</strong>
                        <span>Acceso a recursos de capacitación sobre seguridad de la información.</span>
                    </span>

                    <span class="service-right">
                        <span class="tag tag--warning">ISO</span>
                        <span class="service-arrow">›</span>
                    </span>
                </a>

                <a class="service-item" href="<?= e(base_url('uso-equipos-fuera-oficina')) ?>">
                    <span class="service-ico" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <rect x="12" y="18" width="40" height="26" rx="3"></rect>
                            <path d="M24 52h16"></path>
                            <path d="M32 44v8"></path>
                            <path d="M20 30h24"></path>
                            <path d="M42 24l6-6"></path>
                            <path d="M48 18v8"></path>
                        </svg>
                    </span>

                    <span class="service-copy">
                        <strong>Uso de equipos fuera de oficina</strong>
                        <span>Registro para autorización o control de equipos fuera de oficina.</span>
                    </span>

                    <span class="service-right">
                        <span class="tag tag--info">Control</span>
                        <span class="service-arrow">›</span>
                    </span>
                </a>
            </div>
        </div>

        <div class="service-board">
            <div class="service-board__head">
                <h3 class="service-board__title">Recursos</h3>
                <span class="pill">Consulta</span>
            </div>

            <div class="service-list">
                <a class="service-item" href="<?= e(base_url('documentos')) ?>">
                    <span class="service-ico service-ico--blue" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <path d="M18 10h22l8 8v36H18z"></path>
                            <path d="M40 10v10h8"></path>
                            <path d="M24 30h18"></path>
                            <path d="M24 38h18"></path>
                            <path d="M24 46h10"></path>
                        </svg>
                    </span>

                    <span class="service-copy">
                        <strong>Documentos internos</strong>
                        <span>Consulta manuales, formatos, políticas y documentos autorizados.</span>
                    </span>

                    <span class="service-right">
                        <span class="tag tag--ok">Documentos</span>
                        <span class="service-arrow">›</span>
                    </span>
                </a>

                <button class="service-item" type="button" data-about-open>
                    <span class="service-ico service-ico--green" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <circle cx="32" cy="32" r="22"></circle>
                            <path d="M32 28v16"></path>
                            <path d="M32 20h.01"></path>
                        </svg>
                    </span>

                    <span class="service-copy">
                        <strong>Acerca de Wolk IT</strong>
                        <span>Consulta misión, visión y valores de la empresa.</span>
                    </span>

                    <span class="service-right">
                        <span class="tag tag--info">Wolk IT</span>
                        <span class="service-arrow">›</span>
                    </span>
                </button>

                <button class="service-item" type="button" data-newsletter-open>
                    <span class="service-ico" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <rect x="12" y="16" width="40" height="34" rx="4"></rect>
                            <path d="M20 26h24"></path>
                            <path d="M20 34h24"></path>
                            <path d="M20 42h14"></path>
                        </svg>
                    </span>

                    <span class="service-copy">
                        <strong>Newsletter del mes</strong>
                        <span>Consulta el comunicado interno vigente.</span>
                    </span>

                    <span class="service-right">
                        <span class="tag tag--warning">Mes actual</span>
                        <span class="service-arrow">›</span>
                    </span>
                </button>

                <a class="service-item" href="<?= e(base_url('soporte')) ?>">
                    <span class="service-ico service-ico--blue" aria-hidden="true">
                        <svg viewBox="0 0 64 64">
                            <circle cx="32" cy="32" r="20"></circle>
                            <path d="M32 18v8"></path>
                            <path d="M32 38v8"></path>
                            <path d="M18 32h8"></path>
                            <path d="M38 32h8"></path>
                        </svg>
                    </span>

                    <span class="service-copy">
                        <strong>Soporte interno</strong>
                        <span>Canal para soporte técnico y seguimiento de incidencias.</span>
                    </span>

                    <span class="service-right">
                        <span class="tag tag--info">Soporte</span>
                        <span class="service-arrow">›</span>
                    </span>
                </a>
            </div>
        </div>
    </div>
</section>

<div class="newsletter-modal" id="newsletterModal" aria-hidden="true">
    <div class="newsletter-modal__backdrop" data-newsletter-close></div>

    <div class="newsletter-modal__panel">
        <div class="newsletter-modal__head">
            <div>
                <span class="pill" id="newsletterModalPill">Newsletter</span>
                <h2 id="newsletterTitle">Innovación al Día</h2>
                <p id="newsletterSubtitle">
                    Consulta el comunicado interno correspondiente al mes actual.
                </p>
            </div>

            <button class="newsletter-modal__close" type="button" data-newsletter-close>
                ×
            </button>
        </div>

        <div class="newsletter-tabs" id="newsletterTabs"></div>

        <div class="newsletter-modal__body">
            <iframe
                class="newsletter-frame"
                id="newsletterFrame"
                title="Newsletter interno"
                src=""
                loading="lazy"></iframe>
        </div>

        <div class="newsletter-modal__actions">
            <a class="btn btn--ghost" href="#" target="_blank" rel="noopener" id="newsletterOpenDrive">
                Abrir en Drive
            </a>

            <button class="btn btn--primary" type="button" data-newsletter-close>
                Cerrar
            </button>
        </div>
    </div>
</div>

<div class="about-modal" id="aboutModal" aria-hidden="true">
    <div class="about-modal__backdrop" data-about-close></div>

    <div class="about-modal__panel">
        <div class="about-modal__head">
            <div>
                <span class="pill">Wolk IT</span>
                <h2>Acerca de Wolk IT</h2>
                <p>
                    Consulta nuestra misión, visión y valores.
                </p>
            </div>

            <button class="about-modal__close" type="button" data-about-close>
                ×
            </button>
        </div>

        <div class="about-modal__body">
            <div class="about-modal__content">
                <h3 class="about-main-title">
                    Wolk IT Services conecta personas, tecnología y operación con soluciones que acompañan el crecimiento de nuestros clientes.
                </h3>

                <div class="about-copy">
                    <p>
                        En Wolk IT trabajamos para ofrecer servicios tecnológicos confiables, cercanos y alineados a las necesidades de cada organización.
                    </p>

                    <p>
                        Este espacio reúne información interna para mantener claridad, orden y continuidad en la operación diaria.
                    </p>
                </div>

                <div class="about-image-grid">
                    <article class="about-image-card about-image-card--mision">
                        <div class="about-image-card__body">
                            <h3>Misión</h3>
                            <div class="about-image-card__line"></div>
                            <p>
                                Acompañar a nuestros clientes con soluciones tecnológicas que aporten valor, continuidad y confianza.
                            </p>
                        </div>
                    </article>

                    <article class="about-image-card about-image-card--vision">
                        <div class="about-image-card__body">
                            <h3>Visión</h3>
                            <div class="about-image-card__line"></div>
                            <p>
                                Crear conexiones entre las personas y la tecnología, siendo un referente de innovación en el mercado para el continente Americano.
                            </p>
                        </div>
                    </article>

                    <article class="about-image-card about-image-card--valores">
                        <div class="about-image-card__body">
                            <h3>Valores</h3>
                            <div class="about-image-card__line"></div>
                            <p>
                                Trabajo en Equipo, Calidad, Innovación, Responsabilidad, Centrado en el Cliente, Integridad.
                            </p>
                        </div>
                    </article>
                </div>
            </div>
        </div>

        <div class="about-modal__actions">
            <button class="btn btn--ghost" type="button" data-about-close>
                Cerrar
            </button>
        </div>
    </div>
</div>

<?php

require_once __DIR__ . '/includes/footer.php';
