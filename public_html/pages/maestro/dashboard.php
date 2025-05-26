<?php
/**
 * Maestro Dashboard
 * 
 * Dashboard page for users with 'maestro' role
 */

// Require session and authentication check
require_once __DIR__ . '/../../includes/session.php';

// Require maestro role or higher to access this page
requireLogin('maestro');

// Get current user
$user = $auth->getCurrentUser();
$roleName = $user['role_name'];

// Set page title
$title = "Panel de Control Gestor";

// Set page content
$content = <<<HTML
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Panel de Gestión</h2>
            <div class="card-role">{$roleName}</div>
        </div>
        
        <div class="role-description">
            <p>Como <strong>Gestor (MAESTRO)</strong>, tienes la capacidad de gestionar contenidos y crear nuevos usuarios en el sistema. Este es el rango mínimo con permisos para registrar nuevos usuarios.</p>
        </div>
        
        <h3>Tus permisos incluyen:</h3>
        <ul>
            <li>Crear nuevos usuarios con rangos inferiores al tuyo</li>
            <li>Editar notas y asignar permisos a usuarios de rangos inferiores</li>
            <li>Publicar y gestionar contenidos en diversas secciones</li>
            <li>Configurar accesos especiales a contenidos</li>
            <li>Modificar aspectos visuales y organizativos del sistema</li>
            <li>Ver estadísticas y reportes utilizados</li>
        </ul>
        
        <div style="margin-top: 2rem;">
            <p><strong>Nota:</strong> Recuerda que tus acciones al crear usuarios quedarán registradas. Solo debes registrar usuarios de acuerdo con las políticas establecidas.</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Acciones Rápidas</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Crear Usuario
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Gestionar Contenidos
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Configurar Accesos
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Mi Perfil
            </a>
        </div>
    </div>
HTML;

// Include dashboard template
include __DIR__ . '/../../templates/dashboard_template.php';
