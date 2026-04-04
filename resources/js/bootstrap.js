import axios from 'axios';

/**
 * Configuración segura de Axios para CRM-AC
 *
 * - NO se expone axios en window (previene manipulación desde consola del navegador)
 * - Token CSRF se lee automáticamente del meta tag
 * - Interceptores de error para manejar sesiones expiradas
 */

// Configurar CSRF token automáticamente desde el meta tag
const csrfToken = document.head.querySelector('meta[name="csrf-token"]');

if (csrfToken) {
    axios.defaults.headers.common['X-CSRF-TOKEN'] = csrfToken.content;
} else {
    console.warn('CRM-AC: CSRF token meta tag no encontrado. Las peticiones POST/PUT/DELETE fallarán.');
}

// Headers de seguridad por defecto
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// Configurar withCredentials para enviar cookies de sesión con peticiones AJAX
axios.defaults.withCredentials = true;

// Interceptor de respuesta: manejar errores de autenticación/autorización globalmente
axios.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response) {
            switch (error.response.status) {
                case 401: // No autenticado — sesión expirada
                    window.location.href = '/login';
                    break;

                case 419: // Token CSRF expirado — recargar página
                    alert('Tu sesión de seguridad ha expirado. La página se recargará.');
                    window.location.reload();
                    break;

                case 403: // Sin permisos
                    console.error('CRM-AC: No tienes permisos para esta acción.');
                    break;

                case 429: // Rate limited
                    console.error('CRM-AC: Demasiadas peticiones. Intenta de nuevo en un momento.');
                    break;
            }
        }
        return Promise.reject(error);
    }
);

export default axios;
