import { Link, useNavigate } from 'react-router-dom'
import { useAuth } from '../context/AuthContext.jsx'

export default function Navbar() {
  const { user, logout } = useAuth()
  const navigate = useNavigate()

  function handleLogout() {
    logout()
    navigate('/login')
  }

  return (
    <nav className="navbar">
      <Link to="/" style={{ fontWeight: 700, color: '#1f2430' }}>
        📊 Enquetes
      </Link>
      <div style={{ display: 'flex', gap: 12, alignItems: 'center' }}>
        {user ? (
          <>
            <Link to="/polls/new" className="btn">
              Nova enquete
            </Link>
            <span style={{ fontSize: 14 }}>Olá, {user.name}</span>
            <button className="btn btn-secondary" onClick={handleLogout}>
              Sair
            </button>
          </>
        ) : (
          <>
            <Link to="/login">Entrar</Link>
            <Link to="/register" className="btn">
              Criar conta
            </Link>
          </>
        )}
      </div>
    </nav>
  )
}
