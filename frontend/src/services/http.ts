import axios from 'axios'

const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? 'http://localhost:8000',

  headers: {
    Accept: 'application/json',
    'Content-Type': 'application/json',
  },

  withCredentials: true,
  withXSRFToken: true,
})

export default http
