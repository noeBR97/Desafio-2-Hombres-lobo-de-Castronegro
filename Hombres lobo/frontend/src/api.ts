import axios from 'axios';

const baseURL = window.location.hostname === 'localhost'
  ? 'http://localhost:8000' // navegador local
  : 'http://backend:8000';  // front dentro de Docker

const api = axios.create({ baseURL });

export default api;