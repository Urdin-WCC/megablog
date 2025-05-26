<?php
/**
 * Iniciado Dashboard
 * 
 * Dashboard page for users with 'iniciado' role
 */

// Require session and authentication check
require_once __DIR__ . '/../../includes/session.php';

// Require iniciado role or higher to access this page
requireLogin('iniciado');

// Get current user
$user = $auth->getCurrentUser();
$roleName = $user['role_name'];

// Set page title
$title = "Panel de Control Avanzado";

// Set page content
$content = <<<HTML
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Panel de Usuario Avanzado</h2>
            <div class="card-role">{$roleName}</div>
        </div>
        
        <div class="role-description">
            <p>Como <strong>Usuario Avanzado (INICIADO)</strong>, tienes acceso a funcionalidades ampliadas del sistema y a ciertos contenidos exclusivos.</p>
        </div>
        
        <h3>Tus permisos incluyen:</h3>
        <ul>
            <li>Acceso a contenidos y recursos exclusivos</li>
            <li>Participación en áreas especiales del sistema</li>
            <li>Configuración avanzada de tu perfil personal</li>
            <li>Posibilidad de contribuir en algunas secciones de contenidos</li>
            <li>Acceso a estadísticas y análisis básicos</li>
            <li>Comunicación con usuarios de rangos similares o superiores</li>
        </ul>
        
        <div style="margin-top: 2rem;">
            <p><strong>Nota:</strong> Para obtener permisos adicionales o rangos superiores, deberás ser promocionado por un usuario con rango de Maestro o superior.</p>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Acciones Rápidas</h2>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Contenidos Exclusivos
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Contribuir
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Estadísticas
            </a>
            <a href="#" class="btn btn-primary" style="text-align: center; text-decoration: none;">
                Mi Perfil
            </a>
        </div>
    </div>
HTML;

// Include dashboard template
include __DIR__ . '/../../templates/dashboard_template.php';
