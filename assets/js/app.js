const portalModules = {
  directorio: {
    title: "Directorio",
    status: "Disponible",
    description:
      "Consulta contactos internos, responsables por área y canales de atención de Wolk-IT.",
    actions: ["Ver directorio", "Consultar responsables", "Contactar soporte interno"],
  },
  vacaciones: {
    title: "Solicitud de vacaciones",
    status: "Sesión informativa",
    description:
      "Módulo para registrar, consultar y dar seguimiento a solicitudes de vacaciones o ausencias.",
    actions: ["Iniciar solicitud", "Consultar lineamientos", "Ver estado"],
  },
  personal: {
    title: "Solicitud de personal",
    status: "Gestión interna",
    description:
      "Canal para requerimientos de personal, altas internas, apoyo operativo o nuevas posiciones.",
    actions: ["Crear solicitud", "Consultar requisitos", "Enviar a talento humano"],
  },
  equipo: {
    title: "Equipo y formación",
    status: "Operación",
    description:
      "Solicitudes de equipo, accesos, herramientas de trabajo y formación profesional.",
    actions: ["Solicitar equipo", "Solicitar acceso", "Solicitar capacitación"],
  },
  documentos: {
    title: "Documentos internos",
    status: "Drive",
    description:
      "Acceso a políticas, procedimientos, formatos, comunicados y documentos controlados.",
    actions: ["Abrir documentos", "Consultar políticas", "Ver formatos"],
  },
  capacitacion: {
    title: "Capacitación ISO 27001",
    status: "ISO",
    description:
      "Material de capacitación, concientización y buenas prácticas de seguridad de la información.",
    actions: ["Ver capacitación", "Responder cuestionario", "Consultar material"],
  },
  soporte: {
    title: "Soporte interno",
    status: "Mesa de ayuda",
    description:
      "Reporte de incidentes, dudas operativas, accesos, equipos y atención interna.",
    actions: ["Crear ticket", "Reportar incidente", "Contactar soporte"],
  },
};

const body = document.body;

function createPortalModal() {
  const modal = document.createElement("div");
  modal.className = "portal-modal";
  modal.id = "portalModal";
  modal.innerHTML = `
    <div class="portal-modal__backdrop" data-close-modal></div>
    <div class="portal-modal__panel" role="dialog" aria-modal="true" aria-labelledby="portalModalTitle">
      <div class="portal-modal__head">
        <div>
          <span class="portal-modal__tag" id="portalModalStatus">Disponible</span>
          <h2 id="portalModalTitle">Módulo</h2>
        </div>
        <button class="portal-modal__close" type="button" data-close-modal aria-label="Cerrar">×</button>
      </div>

      <p class="portal-modal__description" id="portalModalDescription"></p>

      <div class="portal-modal__actions" id="portalModalActions"></div>

      <div class="portal-modal__note">
        Esta es una vista funcional inicial. La conexión real puede realizarse después con formularios, Google Drive, Cognito, Lambda o el servicio interno que definas.
      </div>
    </div>
  `;

  body.appendChild(modal);
}

function openPortalModal(moduleKey) {
  const moduleData = portalModules[moduleKey] || {
    title: "Módulo del portal",
    status: "Próximamente",
    description:
      "Este módulo forma parte del portal del empleado y podrá conectarse posteriormente.",
    actions: ["Entendido"],
  };

  document.getElementById("portalModalTitle").textContent = moduleData.title;
  document.getElementById("portalModalStatus").textContent = moduleData.status;
  document.getElementById("portalModalDescription").textContent = moduleData.description;

  const actions = document.getElementById("portalModalActions");
  actions.innerHTML = "";

  moduleData.actions.forEach((action, index) => {
    const button = document.createElement("button");
    button.type = "button";
    button.className = index === 0 ? "btn" : "btn btn--ghost";
    button.textContent = action;

    button.addEventListener("click", () => {
      showToast(`Acción seleccionada: ${action}`);
    });

    actions.appendChild(button);
  });

  document.getElementById("portalModal").classList.add("is-open");
  body.classList.add("modal-open");
}

function closePortalModal() {
  const modal = document.getElementById("portalModal");

  if (!modal) return;

  modal.classList.remove("is-open");
  body.classList.remove("modal-open");
}

function createToast() {
  const toast = document.createElement("div");
  toast.className = "portal-toast";
  toast.id = "portalToast";
  body.appendChild(toast);
}

let toastTimer;

function showToast(message) {
  const toast = document.getElementById("portalToast");

  if (!toast) return;

  toast.textContent = message;
  toast.classList.add("is-visible");

  clearTimeout(toastTimer);

  toastTimer = setTimeout(() => {
    toast.classList.remove("is-visible");
  }, 2600);
}

function createSearchBox() {
  const servicesCard = document.querySelector("#servicios.card");

  if (!servicesCard) return;

  const sectionTitle = servicesCard.querySelector(".section-title");

  const searchBox = document.createElement("div");
  searchBox.className = "portal-search";
  searchBox.innerHTML = `
    <input
      type="search"
      id="portalSearch"
      placeholder="Buscar servicio interno..."
      aria-label="Buscar servicio interno"
    />
  `;

  sectionTitle.insertAdjacentElement("afterend", searchBox);
}

function filterPortalCards(query) {
  const cards = document.querySelectorAll(".portal-card");
  const normalizedQuery = query.trim().toLowerCase();

  cards.forEach((card) => {
    const text = card.textContent.toLowerCase();
    const isVisible = text.includes(normalizedQuery);

    card.style.display = isVisible ? "" : "none";
  });
}

function activateSidebarLink(hash) {
  const links = document.querySelectorAll(".sb-link, .sb-sub");

  links.forEach((link) => {
    link.classList.remove("is-active");

    if (link.getAttribute("href") === hash) {
      link.classList.add("is-active");
    }
  });
}

function initPortalCards() {
  document.querySelectorAll(".portal-card").forEach((card) => {
    card.addEventListener("click", (event) => {
      event.preventDefault();

      const moduleKey = card.id || card.getAttribute("href")?.replace("#", "");
      openPortalModal(moduleKey);
    });
  });
}

function initQuickLinks() {
  document.querySelectorAll(".quick-links a").forEach((link) => {
    link.addEventListener("click", (event) => {
      event.preventDefault();

      const text = link.textContent.replace("→", "").trim();
      showToast(`Acceso rápido seleccionado: ${text}`);
    });
  });
}

function initLoginButton() {
  const login = document.querySelector(".login-link");

  if (!login) return;

  login.addEventListener("click", (event) => {
    event.preventDefault();
    showToast("El login se integrará en una siguiente fase.");
  });
}

function initSidebar() {
  document.querySelectorAll(".sb-link, .sb-sub").forEach((link) => {
    link.addEventListener("click", () => {
      activateSidebarLink(link.getAttribute("href"));
    });
  });

  window.addEventListener("hashchange", () => {
    activateSidebarLink(window.location.hash || "#inicio");
  });
}

function initSearch() {
  const input = document.getElementById("portalSearch");

  if (!input) return;

  input.addEventListener("input", () => {
    filterPortalCards(input.value);
  });
}

function injectFunctionalStyles() {
  const style = document.createElement("style");

  style.textContent = `
    .modal-open {
      overflow: hidden;
    }

    .portal-search {
      margin: 0 0 14px;
    }

    .portal-search input {
      width: 100%;
      border: 1px solid var(--line);
      border-radius: 14px;
      padding: 12px 14px;
      background: #ffffff;
      color: var(--text);
      outline: none;
      font: inherit;
      box-shadow: 0 8px 18px rgba(15, 23, 42, 0.04);
    }

    .portal-search input:focus {
      border-color: rgba(14, 165, 168, 0.45);
      box-shadow: 0 0 0 4px rgba(14, 165, 168, 0.10);
    }

    .portal-modal {
      position: fixed;
      inset: 0;
      z-index: 100;
      display: none;
      align-items: center;
      justify-content: center;
      padding: 22px;
    }

    .portal-modal.is-open {
      display: flex;
    }

    .portal-modal__backdrop {
      position: absolute;
      inset: 0;
      background: rgba(15, 23, 42, 0.42);
      backdrop-filter: blur(4px);
    }

    .portal-modal__panel {
      position: relative;
      width: min(620px, 100%);
      background: #ffffff;
      border: 1px solid var(--line);
      border-radius: 20px;
      padding: 20px;
      box-shadow: 0 24px 70px rgba(15, 23, 42, 0.24);
      animation: modalIn 0.22s ease both;
    }

    @keyframes modalIn {
      from {
        opacity: 0;
        transform: translateY(12px) scale(0.98);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }

    .portal-modal__head {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      gap: 16px;
      border-bottom: 1px solid var(--line);
      padding-bottom: 14px;
      margin-bottom: 14px;
    }

    .portal-modal__head h2 {
      margin: 8px 0 0;
      font-family: "Montserrat", sans-serif;
      font-size: 22px;
      font-weight: 900;
      color: var(--text);
    }

    .portal-modal__tag {
      display: inline-flex;
      font-size: 12px;
      font-weight: 900;
      border: 1px solid rgba(14, 165, 168, 0.24);
      background: rgba(14, 165, 168, 0.10);
      color: #0f766e;
      border-radius: 999px;
      padding: 5px 10px;
    }

    .portal-modal__close {
      width: 36px;
      height: 36px;
      border-radius: 12px;
      border: 1px solid var(--line);
      background: #ffffff;
      cursor: pointer;
      color: var(--text);
      font-size: 22px;
      line-height: 1;
    }

    .portal-modal__description {
      color: var(--muted);
      line-height: 1.6;
      margin: 0 0 16px;
    }

    .portal-modal__actions {
      display: flex;
      gap: 10px;
      flex-wrap: wrap;
      margin-bottom: 14px;
    }

    .portal-modal__note {
      border: 1px solid var(--line);
      background: var(--bg-soft);
      border-radius: 14px;
      padding: 12px;
      color: var(--muted);
      font-size: 13px;
      line-height: 1.5;
    }

    .portal-toast {
      position: fixed;
      right: 22px;
      bottom: 22px;
      z-index: 120;
      max-width: 360px;
      background: #ffffff;
      color: var(--text);
      border: 1px solid var(--line);
      border-left: 5px solid var(--accent2);
      border-radius: 16px;
      padding: 14px 16px;
      box-shadow: 0 18px 44px rgba(15, 23, 42, 0.16);
      opacity: 0;
      transform: translateY(12px);
      pointer-events: none;
      transition: 0.2s ease;
      font-weight: 800;
      font-size: 13px;
    }

    .portal-toast.is-visible {
      opacity: 1;
      transform: translateY(0);
    }

    @media (max-width: 620px) {
      .portal-modal {
        padding: 14px;
      }

      .portal-modal__actions {
        display: grid;
      }

      .portal-toast {
        left: 14px;
        right: 14px;
        bottom: 14px;
      }
    }
  `;

  document.head.appendChild(style);
}

document.addEventListener("DOMContentLoaded", () => {
  injectFunctionalStyles();
  createPortalModal();
  createToast();
  createSearchBox();

  initPortalCards();
  initQuickLinks();
  initLoginButton();
  initSidebar();
  initSearch();

  activateSidebarLink(window.location.hash || "#inicio");

  document.addEventListener("click", (event) => {
    if (event.target.matches("[data-close-modal]")) {
      closePortalModal();
    }
  });

  document.addEventListener("keydown", (event) => {
    if (event.key === "Escape") {
      closePortalModal();
    }
  });
});