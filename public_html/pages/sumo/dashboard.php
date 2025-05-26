<?php
/**
 * Sumo Dashboard
 * 
 * Dashboard page for users with 'sumo' role
 */

// Require session and authentication check
require_once __DIR__ . '/../../includes/session.php';

// Require sumo role or higher to access this page
requireLogin('sumo');

// Get current user
$user = $auth->getCurrentUser();
$roleName = $user['role_name'];

// Set page title
$title = "Panel de Control Supervisor";

// Set page content
$content = <<<HTML
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Panel de Supervisión</h2>
            <div class="card-role">{$roleName}</div>
        </div>
        
        <div class="role-description">
            <p>Como <strong>Supervisor (SUMO)</strong>, tienes un nivel de acceso elevado en el sistema. Puedes gestionar usuarios y supervisar contenidos importantes.</p>
        </div>
        
        <h3>Tus permisos incluyen:</h3>
        <ul>
            <li>Gestión de usuarios (crear, editar usuarios de rango inferior)</li>
            <li>Asignar rangos inferiores a 'SUMO' a los usuarios</li>
            <li>Acceder a secciones importantes del sistema</li>
            <li>Ver registros de actividad de usuarios que supervisa</li>
            <li>Gestionar contenidos asignados a tu supervisión</li>
            <li>Otorgar permisos de acceso a contenidos a usuarios inferiores</li>
        </ul>
        
        <div style="margin-top: 2rem;">
            <p><strong>Nota:</strong> Algunas acciones administrativas avanzadas están reservadas para los rangos superiores.</p>
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
                Ver Registros
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Gestionar Contenidos
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Mi Perfil
            </a>
        </div>
    </div>
HTML;

// Include dashboard template
include __DIR__ . '/../../templates/dashboard_template.php';
