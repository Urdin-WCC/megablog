<?php
/**
 * Sacerdote Dashboard
 * 
 * Dashboard page for users with 'sacerdote' role
 */

// Require session and authentication check
require_once __DIR__ . '/../../includes/session.php';

// Require sacerdote role or higher to access this page
requireLogin('sacerdote');

// Get current user
$user = $auth->getCurrentUser();
$roleName = $user['role_name'];

// Set page title
$title = "Panel de Control Moderador";

// Set page content
$content = <<<HTML
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Panel de Moderación</h2>
            <div class="card-role">{$roleName}</div>
        </div>
        
        <div class="role-description">
            <p>Como <strong>Moderador (SACERDOTE)</strong>, tienes un nivel de acceso significativo en el sistema. Puedes moderar contenidos y gestionar algunos aspectos de usuarios con menor rango.</p>
        </div>
        
        <h3>Tus permisos incluyen:</h3>
        <ul>
            <li>Moderación de contenidos y comentarios</li>
            <li>Ver y editar perfiles de usuarios de rangos inferiores</li>
            <li>Acceder a contenidos especiales y áreas restringidas</li>
            <li>Revisar reportes y notificaciones del sistema</li>
            <li>Asistir a usuarios de rangos inferiores</li>
            <li>Recomendar cambios de rango para usuarios destacados</li>
        </ul>
        
        <div style="margin-top: 2rem;">
            <p><strong>Nota:</strong> La gestión completa de usuarios y la configuración del sistema están reservadas para rangos superiores.</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Acciones Rápidas</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Moderar Contenidos
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Ver Reportes
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Gestionar Recomendaciones
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Mi Perfil
            </a>
        </div>
    </div>
HTML;

// Include dashboard template
include __DIR__ . '/../../templates/dashboard_template.php';
