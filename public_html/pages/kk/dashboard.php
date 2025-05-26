<?php
/**
 * KK Dashboard
 * 
 * Dashboard page for users with 'kk' role
 */

// Require session and authentication check
require_once __DIR__ . '/../../includes/session.php';

// Require kk role or higher to access this page
requireLogin('kk');

// Get current user
$user = $auth->getCurrentUser();
$roleName = $user['role_name'];

// Set page title
$title = "Panel de Control Administrador";

// Set page content
$content = <<<HTML
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Panel de Administración</h2>
            <div class="card-role">{$roleName}</div>
        </div>
        
        <div class="role-description">
            <p>Como <strong>Administrador (KK)</strong>, tienes un nivel de acceso muy elevado en el sistema. Tienes amplios permisos para gestionar usuarios y contenidos.</p>
        </div>
        
        <h3>Tus permisos incluyen:</h3>
        <ul>
            <li>Gestión de usuarios (crear, editar)</li>
            <li>Asignar rangos inferiores a 'KK' a los usuarios</li>
            <li>Acceder a la mayoría de secciones del sistema</li>
            <li>Ver registros de actividad detallados</li>
            <li>Gestionar contenidos de cualquier tipo</li>
            <li>Otorgar permisos especiales a usuarios de rangos inferiores</li>
        </ul>
        
        <div style="margin-top: 2rem;">
            <p><strong>Nota:</strong> Algunas acciones administrativas avanzadas están reservadas para el rango 'GOD'.</p>
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
                Asignar Permisos
            </a>
        </div>
    </div>
HTML;

// Include dashboard template
include __DIR__ . '/../../templates/dashboard_template.php';
