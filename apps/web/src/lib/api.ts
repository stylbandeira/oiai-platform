import axios from "axios";

const apiUrl = (import.meta.env.VITE_API_URL ?? "").replace(/\/$/, "");

const api = axios.create({
    baseURL: `${apiUrl}/api`,
    withCredentials: false,
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
});

api.interceptors.request.use(config => {
    const token = localStorage.getItem('token');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`
    }

    // config.headers['Access-Control-Allow-Origin'] = 'http://localhost:3000';
    return config;
});

export default api;
