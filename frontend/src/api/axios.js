import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL
})

// Anexa o token JWT (se existir) em toda requisicao
api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) {
    config.headers.Authorization = `Bearer ${token}`
  }
  return config
})

export default api
