<?php
/**
 * Adepto Dashboard
 * 
 * Dashboard page for users with 'adepto' role
 */

// Require session and authentication check
require_once __DIR__ . '/../../includes/session.php';

// Require adepto role or higher to access this page
requireLogin('adepto');

// Get current user
$user = $auth->getCurrentUser();
$roleName = $user['role_name'];

// Set page title
$title = "Panel de Control Básico";

// Set page content
$content = <<<HTML
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Panel de Usuario Básico</h2>
            <div class="card-role">{$roleName}</div>
        </div>
        
        <div class="role-description">
            <p>Como <strong>Usuario Básico (ADEPTO)</strong>, tienes acceso a las funcionalidades fundamentales del sistema. Este es el nivel inicial para todos los usuarios recién incorporados.</p>
        </div>
        
        <h3>Tus permisos incluyen:</h3>
        <ul>
            <li>Acceso a contenidos básicos de la plataforma</li>
            <li>Visualización de tu perfil personal</li>
            <li>Lectura de publicaciones públicas</li>
            <li>Interacción limitada con otros usuarios</li>
            <li>Acceso a recursos introductorios</li>
            <li>Solicitud de asistencia a usuarios de rangos superiores</li>
        </ul>
        
        <div style="margin-top: 2rem;">
            <p><strong>Nota:</strong> A medida que participes activamente en el sistema, podrías ser promocionado a rangos superiores por usuarios con nivel de Maestro o superior.</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Acciones Rápidas</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Ver Contenidos
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Recursos Básicos
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Solicitar Ayuda
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Mi Perfil
            </a>
        </div>
    </div>
HTML;

// Include dashboard template
include __DIR__ . '/../../templates/dashboard_template.php';
