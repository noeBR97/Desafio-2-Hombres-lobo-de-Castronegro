import axios from 'axios';

const api = axios.create({
  baseURL: 'http://backend:8000/api', // Laravel dentro de Docker
  // Si algún día pruebas sin Docker, cambias esto a:
  // baseURL: 'http://localhost:8000/api',
});

export default api;