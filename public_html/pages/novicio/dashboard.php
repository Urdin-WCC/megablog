<?php
/**
 * Novicio Dashboard
 * 
 * Dashboard page for users with 'novicio' role
 */

// Require session and authentication check
require_once __DIR__ . '/../../includes/session.php';

// Require novicio role or higher to access this page
requireLogin('novicio');

// Get current user
$user = $auth->getCurrentUser();
$roleName = $user['role_name'];

// Set page title
$title = "Panel de Control Intermedio";

// Set page content
$content = <<<HTML
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Panel de Usuario Intermedio</h2>
            <div class="card-role">{$roleName}</div>
        </div>
        
        <div class="role-description">
            <p>Como <strong>Usuario Intermedio (NOVICIO)</strong>, tienes acceso a varias funcionalidades del sistema y algunos contenidos adicionales respecto a los usuarios básicos.</p>
        </div>
        
        <h3>Tus permisos incluyen:</h3>
        <ul>
            <li>Acceso a contenidos intermedios de la plataforma</li>
            <li>Participación en algunas áreas especiales</li>
            <li>Personalización básica de tu perfil</li>
            <li>Posibilidad de comentar en ciertas secciones</li>
            <li>Visualización de algunos recursos exclusivos</li>
            <li>Recepción de notificaciones especiales</li>
        </ul>
        
        <div style="margin-top: 2rem;">
            <p><strong>Nota:</strong> Tu nivel de acceso te permite explorar varias secciones del sistema. Para avanzar al siguiente nivel, necesitarás la aprobación de un usuario con rango de Maestro o superior.</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Acciones Rápidas</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Explorar Contenidos
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Comentar
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Recursos Disponibles
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Mi Perfil
            </a>
        </div>
    </div>
HTML;

// Include dashboard template
include __DIR__ . '/../../templates/dashboard_template.php';
