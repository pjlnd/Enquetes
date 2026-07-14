import { useState } from 'react'
import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'

export default function Register() {
  const { register } = useAuth()
  const navigate = useNavigate()
  const [name, setName] = useState('')
  const [email, setEmail] = useState('')
  const [password, setPassword] = useState('')
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')
    setLoading(true)
    try {
      await register(name, email, password)
      navigate('/')
    } catch (err) {
      setError(err.response?.data?.error || 'Erro ao criar conta.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="container" style={{ maxWidth: 400 }}>
      <div className="card">
        <h2>Criar conta</h2>
        {error && <div className="error">{error}</div>}
        <form onSubmit={handleSubmit}>
          <label>Nome</label>
          <input type="text" value={name} onChange={(e) => setName(e.target.value)} required />

          <label>Email</label>
          <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} required />

          <label>Senha (mínimo 6 caracteres)</label>
          <input type="password" value={password} onChange={(e) => setPassword(e.target.value)} required minLength={6} />

          <button className="btn" type="submit" disabled={loading} style={{ width: '100%' }}>
            {loading ? 'Criando...' : 'Criar conta'}
          </button>
        </form>
        <p style={{ marginTop: 16, fontSize: 14 }}>
          Já tem conta? <Link to="/login">Entrar</Link>
        </p>
      </div>
    </div>
  )
}
