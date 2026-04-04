/**
 * CRM-AC — Módulo API Seguro
 *
 * Helper centralizado para comunicación con el backend.
 * Todas las peticiones pasan por este módulo para garantizar:
 * - Tokens CSRF automáticos
 * - Manejo de errores consistente
 * - Headers de seguridad
 *
 * USO:
 *   import api from './api';
 *   const { data } = await api.get('/api/inventory/products');
 *   await api.post('/api/inventory/products', { name: 'Producto', price: 100 });
 */

import axios from './bootstrap';

const api = {
    /**
     * Petición GET segura
     * @param {string} url - Ruta relativa (ej: '/api/inventory/products')
     * @param {object} params - Query params opcionales
     * @returns {Promise}
     */
    get(url, params = {}) {
        return axios.get(url, { params });
    },

    /**
     * Petición POST segura (CSRF incluido automáticamente)
     * @param {string} url
     * @param {object} data - Body de la petición
     * @returns {Promise}
     */
    post(url, data = {}) {
        return axios.post(url, data);
    },

    /**
     * Petición PUT segura
     * @param {string} url
     * @param {object} data
     * @returns {Promise}
     */
    put(url, data = {}) {
        return axios.put(url, data);
    },

    /**
     * Petición PATCH segura
     * @param {string} url
     * @param {object} data
     * @returns {Promise}
     */
    patch(url, data = {}) {
        return axios.patch(url, data);
    },

    /**
     * Petición DELETE segura
     * @param {string} url
     * @param {object} data - Body opcional
     * @returns {Promise}
     */
    delete(url, data = {}) {
        return axios.delete(url, { data });
    },
};

export default api;
