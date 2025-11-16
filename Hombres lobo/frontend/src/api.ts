import axios from 'axios';

const api = axios.create({
  baseURL: 'http://backend:8000/api', // Laravel dentro de Docker
});

export default api;