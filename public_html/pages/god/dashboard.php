<?php
/**
 * God Dashboard
 * 
 * Dashboard page for users with 'god' role
 */

// Require session and authentication check
require_once __DIR__ . '/../../includes/session.php';

// Require god role to access this page
requireLogin('god');

// Get current user
$user = $auth->getCurrentUser();
$roleName = $user['role_name'];

// Set page title
$title = "Panel de Control Administrador Supremo";

// Set page content
$content = <<<HTML
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Panel de Administración Suprema</h2>
            <div class="card-role">{$roleName}</div>
        </div>
        
        <div class="role-description">
            <p>Como <strong>Administrador Supremo (GOD)</strong>, tienes acceso total al sistema y todos sus componentes. Este es el nivel de acceso más alto disponible.</p>
        </div>
        
        <h3>Tus permisos incluyen:</h3>
        <ul>
            <li>Gestión completa de todos los usuarios (crear, editar, eliminar)</li>
            <li>Asignar cualquier rango a los usuarios</li>
            <li>Acceder a todas las secciones y funcionalidades del sistema</li>
            <li>Configurar parámetros globales del sistema</li>
            <li>Ver registros y estadísticas completas</li>
            <li>Gestionar contenidos de cualquier tipo</li>
            <li>Otorgar permisos especiales a usuarios de cualquier rango</li>
        </ul>
        
        <div style="margin-top: 2rem;">
            <p><strong>Nota:</strong> Con grandes poderes viene gran responsabilidad. Todas tus acciones quedarán registradas en el sistema.</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Acciones Rápidas</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Gestionar Usuarios
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Configuración del Sistema
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Ver Registros
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Gestionar Contenidos
            </a>
        </div>
    </div>
HTML;

// Include dashboard template
include __DIR__ . '/../../templates/dashboard_template.php';
