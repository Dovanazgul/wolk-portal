CREATE DATABASE wolk_nexus
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE wolk_nexus;

CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    description TEXT NULL,
    is_system_role TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE areas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    status ENUM('activo','inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    status ENUM('activo','inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area_id INT NULL,
    primary_department_id INT NULL,
    full_name VARCHAR(180) NOT NULL,
    email VARCHAR(180) NULL UNIQUE,
    position_name VARCHAR(180) NOT NULL,
    password_hash VARCHAR(255) NULL,
    phone VARCHAR(50) NULL,
    status ENUM('activo','inactivo') DEFAULT 'activo',
    last_login_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (area_id) REFERENCES areas(id),
    FOREIGN KEY (primary_department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

CREATE TABLE user_departments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    department_id INT NOT NULL,
    is_primary TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_department (user_id, department_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_role (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE nexus_modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    route VARCHAR(255) NULL,
    icon_name VARCHAR(80) NULL,
    sort_order INT DEFAULT 0,
    status ENUM('activo','inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE role_module_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    module_id INT NOT NULL,
    can_view TINYINT(1) DEFAULT 0,
    can_create TINYINT(1) DEFAULT 0,
    can_update TINYINT(1) DEFAULT 0,
    can_delete TINYINT(1) DEFAULT 0,
    can_approve TINYINT(1) DEFAULT 0,
    can_manage TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_role_module (role_id, module_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES nexus_modules(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE area_module_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    area_id INT NOT NULL,
    module_id INT NOT NULL,
    can_view TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_area_module (area_id, module_id),
    FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES nexus_modules(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE department_module_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    department_id INT NOT NULL,
    module_id INT NOT NULL,
    can_view TINYINT(1) DEFAULT 0,
    can_create TINYINT(1) DEFAULT 0,
    can_update TINYINT(1) DEFAULT 0,
    can_approve TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_department_module (department_id, module_id),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES nexus_modules(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE document_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    sort_order INT DEFAULT 0,
    status ENUM('activo','inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NULL,
    owner_area_id INT NULL,
    owner_department_id INT NULL,
    title VARCHAR(220) NOT NULL,
    code VARCHAR(80) NULL UNIQUE,
    document_type ENUM(
        'manual',
        'formato',
        'politica',
        'procedimiento',
        'proceso',
        'registro',
        'guia',
        'presentacion',
        'otro'
    ) DEFAULT 'otro',
    description TEXT NULL,
    google_drive_url TEXT NULL,
    download_url TEXT NULL,
    version_label VARCHAR(50) DEFAULT '1.0',
    visibility ENUM('publico','restringido','privado') DEFAULT 'restringido',
    status ENUM('borrador','vigente','obsoleto') DEFAULT 'vigente',
    visible_in_nexus TINYINT(1) DEFAULT 1,
    published_at DATETIME NULL,
    created_by INT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES document_categories(id),
    FOREIGN KEY (owner_area_id) REFERENCES areas(id),
    FOREIGN KEY (owner_department_id) REFERENCES departments(id),
    FOREIGN KEY (created_by) REFERENCES users(id),
    FOREIGN KEY (updated_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE document_role_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    role_id INT NOT NULL,
    can_view TINYINT(1) DEFAULT 1,
    can_edit TINYINT(1) DEFAULT 0,
    can_download TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_document_role (document_id, role_id),
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE document_area_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    area_id INT NOT NULL,
    can_view TINYINT(1) DEFAULT 1,
    can_edit TINYINT(1) DEFAULT 0,
    can_download TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_document_area (document_id, area_id),
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (area_id) REFERENCES areas(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE document_department_access (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    department_id INT NOT NULL,
    can_view TINYINT(1) DEFAULT 1,
    can_edit TINYINT(1) DEFAULT 0,
    can_download TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_document_department (document_id, department_id),
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE document_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NOT NULL,
    version_label VARCHAR(50) NOT NULL,
    change_summary TEXT NULL,
    file_url TEXT NULL,
    download_url TEXT NULL,
    status ENUM('vigente','historial','obsoleto') DEFAULT 'historial',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE nexus_links (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NULL,
    owner_area_id INT NULL,
    owner_department_id INT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    url TEXT NOT NULL,
    link_type ENUM('interno','google_drive','google_docs','google_sheets','monday','formulario','externo') DEFAULT 'externo',
    button_label VARCHAR(80) DEFAULT 'Abrir',
    visibility ENUM('publico','restringido','privado') DEFAULT 'restringido',
    status ENUM('activo','inactivo') DEFAULT 'activo',
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES nexus_modules(id),
    FOREIGN KEY (owner_area_id) REFERENCES areas(id),
    FOREIGN KEY (owner_department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

CREATE TABLE request_catalog (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_area_id INT NULL,
    owner_department_id INT NULL,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(150) NOT NULL UNIQUE,
    description TEXT NULL,
    request_type ENUM(
        'vacaciones',
        'soporte',
        'cambio',
        'documento',
        'rh',
        'operaciones',
        'administracion',
        'comercial',
        'otro'
    ) DEFAULT 'otro',
    form_url TEXT NULL,
    visibility ENUM('publico','restringido','privado') DEFAULT 'restringido',
    status ENUM('activo','inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_area_id) REFERENCES areas(id),
    FOREIGN KEY (owner_department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

CREATE TABLE change_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_user_id INT NULL,
    requester_name VARCHAR(180) NULL,
    requester_email VARCHAR(180) NULL,
    area_id INT NULL,
    department_id INT NULL,
    change_type ENUM('producto_servicio','administrativo','proyecto','documental','sistema','otro') DEFAULT 'otro',
    title VARCHAR(220) NOT NULL,
    detailed_description TEXT NOT NULL,
    reason TEXT NULL,
    expected_impact TEXT NULL,
    priority ENUM('baja','media','alta','critica') DEFAULT 'media',
    status ENUM('registrada','en_revision','aprobada','rechazada','implementada','cerrada') DEFAULT 'registrada',
    approval_notes TEXT NULL,
    approved_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_user_id) REFERENCES users(id),
    FOREIGN KEY (area_id) REFERENCES areas(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE support_tickets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    requester_user_id INT NULL,
    requester_name VARCHAR(180) NULL,
    requester_email VARCHAR(180) NULL,
    area_id INT NULL,
    department_id INT NULL,
    title VARCHAR(220) NOT NULL,
    description TEXT NOT NULL,
    category VARCHAR(120) NULL,
    priority ENUM('baja','media','alta','critica') DEFAULT 'media',
    status ENUM('abierto','en_proceso','en_espera','resuelto','cerrado') DEFAULT 'abierto',
    assigned_to INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (requester_user_id) REFERENCES users(id),
    FOREIGN KEY (area_id) REFERENCES areas(id),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (assigned_to) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE support_ticket_comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    user_id INT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES support_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_area_id INT NULL,
    owner_department_id INT NULL,
    title VARCHAR(220) NOT NULL,
    content TEXT NOT NULL,
    image_url TEXT NULL,
    visibility ENUM('publico','restringido','privado') DEFAULT 'restringido',
    status ENUM('borrador','publicado','oculto') DEFAULT 'publicado',
    published_at DATETIME NULL,
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_area_id) REFERENCES areas(id),
    FOREIGN KEY (owner_department_id) REFERENCES departments(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE ai_conversations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    title VARCHAR(220) NULL,
    status ENUM('activa','archivada') DEFAULT 'activa',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE ai_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT NOT NULL,
    role ENUM('user','assistant','system') NOT NULL,
    message LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (conversation_id) REFERENCES ai_conversations(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE ai_questions_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    conversation_id INT NULL,
    question TEXT NOT NULL,
    answer LONGTEXT NULL,
    source_document_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (conversation_id) REFERENCES ai_conversations(id),
    FOREIGN KEY (source_document_id) REFERENCES documents(id)
) ENGINE=InnoDB;

CREATE TABLE knowledge_chunks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    document_id INT NULL,
    title VARCHAR(220) NULL,
    content LONGTEXT NOT NULL,
    keywords TEXT NULL,
    status ENUM('activo','inactivo') DEFAULT 'activo',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (document_id) REFERENCES documents(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    action VARCHAR(180) NOT NULL,
    module_name VARCHAR(120) NULL,
    record_id INT NULL,
    details TEXT NULL,
    ip_address VARCHAR(80) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(120) NOT NULL UNIQUE,
    setting_value TEXT NULL,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO roles (name, slug, description, is_system_role) VALUES
('SUPERADMIN', 'superadmin', 'Acceso total a Nexus, usuarios, permisos, configuración crítica y administración completa.', 1),
('DIRECCION', 'direccion', 'Acceso directivo general a Nexus, excepto configuración crítica de SUPERADMIN.', 1),
('ADMIN', 'admin', 'Acceso administrativo general sin configuración crítica.', 1),
('CISO', 'ciso', 'Rol de seguridad, cumplimiento, control documental e ISO.', 1),
('CTO', 'cto', 'Rol de gestión tecnológica, operación técnica y soporte estratégico.', 1),
('OPERACIONES', 'operaciones', 'Acceso operativo a documentos, solicitudes, soporte, cambios e IA documental autorizada.', 1),
('COMERCIAL', 'comercial', 'Acceso comercial a documentos, formatos, solicitudes y recursos autorizados.', 1),
('GERENTE', 'gerente', 'Acceso de gestión, revisión y aprobación según área o departamento.', 1),
('ADMINISTRACION', 'administracion', 'Acceso administrativo, legal, contable y de talento humano autorizado.', 1);

INSERT INTO areas (name, slug, description) VALUES
('Dirección', 'direccion', 'Alta dirección de Wolk IT.'),
('Operaciones', 'operaciones', 'Implementación, servicios administrados, soporte y operación.'),
('Comercial', 'comercial', 'Gestión comercial, cuentas y oportunidades.'),
('Preventa', 'preventa', 'Ingeniería de preventa y dimensionamiento.'),
('Administracion', 'administracion', 'Gestión administrativa interna.'),
('Legal', 'legal', 'Gestión legal y contractual.'),
('Talento Humano', 'talento_humano', 'Gestión de talento humano.'),
('Contabilidad', 'contabilidad', 'Gestión contable y financiera.');

INSERT INTO departments (name, slug, description) VALUES
('Dirección', 'direccion', 'Departamento directivo.'),
('Operaciones', 'operaciones', 'Departamento de operaciones.'),
('Comercial', 'comercial', 'Departamento comercial.'),
('Administración', 'administracion', 'Departamento administrativo, legal, talento humano y contable.');

INSERT INTO users (area_id, primary_department_id, full_name, email, position_name) VALUES
((SELECT id FROM areas WHERE slug = 'direccion'), (SELECT id FROM departments WHERE slug = 'direccion'), 'Hugo Alexander Jimenez Vazquez', NULL, 'CEO'),
((SELECT id FROM areas WHERE slug = 'direccion'), (SELECT id FROM departments WHERE slug = 'direccion'), 'Erasmo Esquivel Duran', NULL, 'CTO'),
((SELECT id FROM areas WHERE slug = 'direccion'), (SELECT id FROM departments WHERE slug = 'direccion'), 'Fernando Carcamo Luna', NULL, 'CCO'),
((SELECT id FROM areas WHERE slug = 'direccion'), (SELECT id FROM departments WHERE slug = 'direccion'), 'Erick Cristopher Agüeros Rosas', NULL, 'CFO'),
((SELECT id FROM areas WHERE slug = 'preventa'), (SELECT id FROM departments WHERE slug = 'comercial'), 'Jose Roberto Benitez Garcia', NULL, 'Gerente de Preventa'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Luis Ivan Vazquez Covarrubias', NULL, 'Gerente de implementación'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Carlos Enrique Cornejo Leon', NULL, 'Gerente de servicios administrados'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Ignacio Mendoza Trejo', NULL, 'CISO'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Aldo Eliu Ramírez González', NULL, 'PM'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Marisol Medina Alarcón', NULL, 'PM'),
((SELECT id FROM areas WHERE slug = 'administracion'), (SELECT id FROM departments WHERE slug = 'administracion'), 'Julia Patricia Guerra Fierro', NULL, 'Responsable de Administración'),
((SELECT id FROM areas WHERE slug = 'legal'), (SELECT id FROM departments WHERE slug = 'administracion'), 'Maria Isabel Palomino Martinez', NULL, 'Responsable de Legal'),
((SELECT id FROM areas WHERE slug = 'talento_humano'), (SELECT id FROM departments WHERE slug = 'administracion'), 'Arely Joselinne Morales Rodríguez', NULL, 'Coordinadora de Talento Humano'),
((SELECT id FROM areas WHERE slug = 'contabilidad'), (SELECT id FROM departments WHERE slug = 'administracion'), 'Guillermo Emmanuel Rodríguez Brito', NULL, 'Contador'),
((SELECT id FROM areas WHERE slug = 'comercial'), (SELECT id FROM departments WHERE slug = 'comercial'), 'Romina María Garcia Benítez', NULL, 'Gerente Comercial'),
((SELECT id FROM areas WHERE slug = 'comercial'), (SELECT id FROM departments WHERE slug = 'comercial'), 'Claudia Alejandra Méndez Mazon', NULL, 'KAM'),
((SELECT id FROM areas WHERE slug = 'comercial'), (SELECT id FROM departments WHERE slug = 'comercial'), 'Claudia Puente Rocha', NULL, 'KAM'),
((SELECT id FROM areas WHERE slug = 'comercial'), (SELECT id FROM departments WHERE slug = 'comercial'), 'Gregorio Tancitaro Lopez Maciel', NULL, 'KAM'),
((SELECT id FROM areas WHERE slug = 'comercial'), (SELECT id FROM departments WHERE slug = 'comercial'), 'Guadalupe Monserrat Zamora Rendon', NULL, 'KAM'),
((SELECT id FROM areas WHERE slug = 'comercial'), (SELECT id FROM departments WHERE slug = 'comercial'), 'Julio Cesar Marquez Mejia', NULL, 'KAM'),
((SELECT id FROM areas WHERE slug = 'comercial'), (SELECT id FROM departments WHERE slug = 'comercial'), 'Veronica Medina Ponce', NULL, 'KAM'),
((SELECT id FROM areas WHERE slug = 'comercial'), (SELECT id FROM departments WHERE slug = 'comercial'), 'Bertha Alicia Montiel Gutierrez', NULL, 'KAM'),
((SELECT id FROM areas WHERE slug = 'comercial'), (SELECT id FROM departments WHERE slug = 'comercial'), 'Nathalie Guarin', NULL, 'KAM'),
((SELECT id FROM areas WHERE slug = 'comercial'), (SELECT id FROM departments WHERE slug = 'comercial'), 'Dennis Alejandra Canales Jiménez', NULL, 'Hunter'),
((SELECT id FROM areas WHERE slug = 'preventa'), (SELECT id FROM departments WHERE slug = 'comercial'), 'Miriam Stephania Chaine Ibanez', NULL, 'Ingeniero de Preventa'),
((SELECT id FROM areas WHERE slug = 'preventa'), (SELECT id FROM departments WHERE slug = 'comercial'), 'Erick Nolasco Vasquez', NULL, 'Ingeniero de Preventa'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Luis Angel Ortega Huesca', NULL, 'Ingeniero de Implementación'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Daniel Urrutia Salazar', NULL, 'Ingeniero de Implementación'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Kyo Emanuel Dominguez Romero', NULL, 'Ingeniero de Servicios Administrados'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Alejandro Ruiz Jimenez', NULL, 'Ingeniero de Servicios Administrados'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Alfredo Mota Meneses', NULL, 'Ingeniero de Servicios Administrados'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Eraclio Israel Beltrán Galarza', NULL, 'Ingeniero de Servicios Administrados'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Alfredo Isaac Rosas Reyes', NULL, 'Ingeniero de Servicios Administrados'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Salvador García Cabrera', NULL, 'Ingeniero de Implementación'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Valeria Michelle Mendoza Moreno', NULL, 'Ingeniero de Implementación'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Oyuki Yamanic Polanco Sosa', NULL, 'Ingeniero de Servicios Administrados'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Eber Manuel Velazco Maldonado', NULL, 'Ingeniero de Servicios Administrados'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Miguel Nazario Priego Vera', NULL, 'Ingeniero de Servicios Administrados'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Diego Alonso Garcia Valdes', NULL, 'Ingeniero de Servicios Administrados'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Oscar Eduardo Solis García', NULL, 'Ingeniero de Servicios Administrados');

INSERT INTO user_departments (user_id, department_id, is_primary)
SELECT id, primary_department_id, 1
FROM users
WHERE primary_department_id IS NOT NULL;

INSERT INTO user_departments (user_id, department_id, is_primary)
SELECT u.id, d.id, 0
FROM users u
JOIN departments d ON d.slug = 'operaciones'
WHERE u.full_name = 'Erasmo Esquivel Duran';

INSERT INTO user_departments (user_id, department_id, is_primary)
SELECT u.id, d.id, 0
FROM users u
JOIN departments d ON d.slug = 'comercial'
WHERE u.full_name = 'Fernando Carcamo Luna';

INSERT INTO user_departments (user_id, department_id, is_primary)
SELECT u.id, d.id, 0
FROM users u
JOIN departments d ON d.slug = 'administracion'
WHERE u.full_name = 'Erick Cristopher Agüeros Rosas';

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.slug = 'superadmin'
WHERE u.position_name IN ('CISO', 'CTO');

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.slug = 'ciso'
WHERE u.position_name = 'CISO';

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.slug = 'cto'
WHERE u.position_name = 'CTO';

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.slug = 'admin'
WHERE u.position_name = 'CEO';

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.slug = 'direccion'
WHERE u.area_id = (SELECT id FROM areas WHERE slug = 'direccion');

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.slug = 'operaciones'
WHERE u.primary_department_id = (SELECT id FROM departments WHERE slug = 'operaciones');

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.slug = 'comercial'
WHERE u.primary_department_id = (SELECT id FROM departments WHERE slug = 'comercial');

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.slug = 'administracion'
WHERE u.primary_department_id = (SELECT id FROM departments WHERE slug = 'administracion');

INSERT INTO user_roles (user_id, role_id)
SELECT u.id, r.id
FROM users u
JOIN roles r ON r.slug = 'gerente'
WHERE u.position_name LIKE 'Gerente%'
   OR u.position_name LIKE 'Responsable%'
   OR u.position_name LIKE 'Coordinadora%';

INSERT INTO nexus_modules (name, slug, description, route, icon_name, sort_order) VALUES
('Inicio', 'inicio', 'Pantalla principal de Nexus.', '/', 'home', 1),
('Documentos', 'documentos', 'Consulta de documentos internos autorizados.', '/documentos', 'files', 2),
('Formatos', 'formatos', 'Consulta y descarga de formatos internos.', '/formatos', 'clipboard', 3),
('Solicitudes', 'solicitudes', 'Acceso a solicitudes internas.', '/solicitudes', 'form', 4),
('Soporte', 'soporte', 'Registro y seguimiento de soporte técnico.', '/soporte', 'support', 5),
('Directorio', 'directorio', 'Directorio interno de colaboradores.', '/directorio', 'users', 6),
('Cambios', 'cambios', 'Registro y control de solicitudes de cambio.', '/cambios', 'refresh', 7),
('IA Documental', 'ia_documental', 'Consulta asistida sobre documentación interna.', '/doc-ai', 'bot', 8),
('Administración', 'administracion', 'Administración general de Nexus.', '/admin', 'settings', 9),
('Usuarios y permisos', 'usuarios_permisos', 'Gestión de usuarios, roles, áreas, departamentos y accesos.', '/admin/usuarios', 'shield', 10),
('Configuración crítica', 'configuracion_critica', 'Configuración exclusiva de SUPERADMIN.', '/admin/configuracion', 'lock', 11);

INSERT INTO role_module_permissions (
    role_id, module_id, can_view, can_create, can_update, can_delete, can_approve, can_manage
)
SELECT r.id, m.id, 1, 1, 1, 1, 1, 1
FROM roles r
CROSS JOIN nexus_modules m
WHERE r.slug = 'superadmin';

INSERT INTO role_module_permissions (
    role_id, module_id, can_view, can_create, can_update, can_delete, can_approve, can_manage
)
SELECT r.id, m.id,
CASE WHEN m.slug <> 'configuracion_critica' THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('documentos','formatos','solicitudes','soporte','cambios','ia_documental') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('documentos','formatos','solicitudes','soporte','cambios','ia_documental') THEN 1 ELSE 0 END,
0,
CASE WHEN m.slug IN ('cambios','documentos') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('administracion','usuarios_permisos') THEN 1 ELSE 0 END
FROM roles r
CROSS JOIN nexus_modules m
WHERE r.slug = 'direccion';

INSERT INTO role_module_permissions (
    role_id, module_id, can_view, can_create, can_update, can_delete, can_approve, can_manage
)
SELECT r.id, m.id,
CASE WHEN m.slug NOT IN ('configuracion_critica') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('documentos','formatos','solicitudes','soporte','cambios','ia_documental','administracion') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('documentos','formatos','solicitudes','soporte','cambios','ia_documental','administracion') THEN 1 ELSE 0 END,
0,
CASE WHEN m.slug IN ('documentos','cambios') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('administracion','usuarios_permisos') THEN 1 ELSE 0 END
FROM roles r
CROSS JOIN nexus_modules m
WHERE r.slug = 'admin';

INSERT INTO role_module_permissions (
    role_id, module_id, can_view, can_create, can_update, can_delete, can_approve, can_manage
)
SELECT r.id, m.id, 1, 1, 1, 1, 1, 1
FROM roles r
CROSS JOIN nexus_modules m
WHERE r.slug IN ('ciso','cto');

INSERT INTO role_module_permissions (
    role_id, module_id, can_view, can_create, can_update, can_delete, can_approve, can_manage
)
SELECT r.id, m.id,
CASE WHEN m.slug NOT IN ('administracion','usuarios_permisos','configuracion_critica') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('solicitudes','soporte','cambios') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('solicitudes','soporte','cambios') THEN 1 ELSE 0 END,
0,
0,
0
FROM roles r
CROSS JOIN nexus_modules m
WHERE r.slug = 'operaciones';

INSERT INTO role_module_permissions (
    role_id, module_id, can_view, can_create, can_update, can_delete, can_approve, can_manage
)
SELECT r.id, m.id,
CASE WHEN m.slug IN ('inicio','documentos','formatos','solicitudes','soporte','directorio') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('solicitudes','soporte') THEN 1 ELSE 0 END,
0,
0,
0,
0
FROM roles r
CROSS JOIN nexus_modules m
WHERE r.slug = 'comercial';

INSERT INTO role_module_permissions (
    role_id, module_id, can_view, can_create, can_update, can_delete, can_approve, can_manage
)
SELECT r.id, m.id,
CASE WHEN m.slug NOT IN ('usuarios_permisos','configuracion_critica') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('solicitudes','soporte','cambios') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('solicitudes','soporte','cambios') THEN 1 ELSE 0 END,
0,
CASE WHEN m.slug IN ('cambios') THEN 1 ELSE 0 END,
0
FROM roles r
CROSS JOIN nexus_modules m
WHERE r.slug = 'gerente';

INSERT INTO role_module_permissions (
    role_id, module_id, can_view, can_create, can_update, can_delete, can_approve, can_manage
)
SELECT r.id, m.id,
CASE WHEN m.slug IN ('inicio','documentos','formatos','solicitudes','soporte','directorio','cambios') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('solicitudes','soporte','cambios') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('solicitudes','soporte','cambios') THEN 1 ELSE 0 END,
0,
0,
0
FROM roles r
CROSS JOIN nexus_modules m
WHERE r.slug = 'administracion';

INSERT INTO area_module_permissions (area_id, module_id, can_view)
SELECT a.id, m.id, 1
FROM areas a
CROSS JOIN nexus_modules m
WHERE m.slug IN ('inicio','documentos','formatos','solicitudes','soporte','directorio');

INSERT INTO area_module_permissions (area_id, module_id, can_view)
SELECT a.id, m.id, 1
FROM areas a
CROSS JOIN nexus_modules m
WHERE a.slug IN ('direccion','operaciones','preventa')
AND m.slug IN ('cambios','ia_documental');

INSERT INTO area_module_permissions (area_id, module_id, can_view)
SELECT a.id, m.id, 1
FROM areas a
CROSS JOIN nexus_modules m
WHERE a.slug IN ('direccion','operaciones','administracion')
AND m.slug = 'administracion';

INSERT INTO area_module_permissions (area_id, module_id, can_view)
SELECT a.id, m.id, 1
FROM areas a
CROSS JOIN nexus_modules m
WHERE a.slug = 'direccion'
AND m.slug = 'usuarios_permisos';

INSERT INTO department_module_permissions (
    department_id, module_id, can_view, can_create, can_update, can_approve
)
SELECT d.id, m.id, 1, 1, 1, 1
FROM departments d
CROSS JOIN nexus_modules m
WHERE d.slug = 'direccion'
AND m.slug <> 'configuracion_critica';

INSERT INTO department_module_permissions (
    department_id, module_id, can_view, can_create, can_update, can_approve
)
SELECT d.id, m.id,
CASE WHEN m.slug IN ('inicio','documentos','formatos','solicitudes','soporte','directorio','cambios','ia_documental') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('solicitudes','soporte','cambios') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('solicitudes','soporte','cambios') THEN 1 ELSE 0 END,
CASE WHEN m.slug = 'cambios' THEN 1 ELSE 0 END
FROM departments d
CROSS JOIN nexus_modules m
WHERE d.slug = 'operaciones';

INSERT INTO department_module_permissions (
    department_id, module_id, can_view, can_create, can_update, can_approve
)
SELECT d.id, m.id,
CASE WHEN m.slug IN ('inicio','documentos','formatos','solicitudes','soporte','directorio') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('solicitudes','soporte') THEN 1 ELSE 0 END,
0,
0
FROM departments d
CROSS JOIN nexus_modules m
WHERE d.slug = 'comercial';

INSERT INTO department_module_permissions (
    department_id, module_id, can_view, can_create, can_update, can_approve
)
SELECT d.id, m.id,
CASE WHEN m.slug IN ('inicio','documentos','formatos','solicitudes','soporte','directorio','cambios','administracion') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('solicitudes','soporte','cambios') THEN 1 ELSE 0 END,
CASE WHEN m.slug IN ('solicitudes','soporte','cambios') THEN 1 ELSE 0 END,
0
FROM departments d
CROSS JOIN nexus_modules m
WHERE d.slug = 'administracion';

INSERT INTO document_categories (name, slug, description, sort_order) VALUES
('Manuales', 'manuales', 'Documentos de consulta interna.', 1),
('Formatos', 'formatos', 'Plantillas y formatos operativos.', 2),
('Procedimientos', 'procedimientos', 'Procedimientos internos del sistema de gestión.', 3),
('Políticas', 'politicas', 'Lineamientos internos vigentes.', 4),
('Registros', 'registros', 'Evidencias y registros de operación.', 5),
('Presentaciones', 'presentaciones', 'Material de apoyo y presentaciones internas.', 6),
('ISO 20000', 'iso-20000', 'Documentación relacionada con el sistema de gestión de servicios.', 7),
('Seguridad de la Información', 'seguridad-informacion', 'Documentación relacionada con seguridad de la información.', 8);

INSERT INTO request_catalog (
    owner_area_id, owner_department_id, title, slug, description, request_type, form_url, visibility
) VALUES
((SELECT id FROM areas WHERE slug = 'talento_humano'), (SELECT id FROM departments WHERE slug = 'administracion'), 'Solicitud de vacaciones', 'solicitud-vacaciones', 'Formulario para registrar solicitudes de vacaciones.', 'vacaciones', 'https://forms.monday.com/forms/9932cad5d6a3f644d0f4e6fb24e9168f?r=use1', 'restringido'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Solicitud de soporte técnico', 'solicitud-soporte-tecnico', 'Registro de incidentes o requerimientos de soporte.', 'soporte', NULL, 'restringido'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Solicitud de cambio', 'solicitud-cambio', 'Registro formal de cambios para Nexus o procesos internos.', 'cambio', NULL, 'restringido'),
((SELECT id FROM areas WHERE slug = 'operaciones'), (SELECT id FROM departments WHERE slug = 'operaciones'), 'Solicitud documental', 'solicitud-documental', 'Solicitud relacionada con documentos, formatos o evidencias.', 'documento', NULL, 'restringido');

INSERT INTO system_settings (setting_key, setting_value) VALUES
('system_name', 'Nexus'),
('system_full_name', 'Wolk Nexus'),
('system_subtitle', 'Sistema interno de documentos, solicitudes, soporte e ISO'),
('company_name', 'Wolk IT Services'),
('default_timezone', 'America/Mexico_City'),
('access_control_mode', 'roles_areas_departments'),
('superadmin_rule', 'CISO y CTO cuentan con acceso SUPERADMIN.'),
('admin_rule', 'CEO cuenta con acceso ADMIN.'),
('direction_rule', 'Dirección tiene acceso general excepto configuración crítica de SUPERADMIN.');