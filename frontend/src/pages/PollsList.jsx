import { useEffect, useState } from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../context/AuthContext'
import api from '../api/axios'

export default function PollsList() {
  const { user } = useAuth()
  const [polls, setPolls] = useState([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    api
      .get('/polls')
      .then((res) => setPolls(res.data.polls))
      .catch(() => setError('Não foi possível carregar as enquetes.'))
      .finally(() => setLoading(false))
  }, [])

  if (loading) return <div className="container">Carregando...</div>

  return (
    <div className="container">
      <h2>Enquetes</h2>
      {error && <div className="error">{error}</div>}

      {polls.length === 0 && <p>Nenhuma enquete criada ainda.</p>}

      {polls.map((poll) => (
        <Link key={poll.id} to={`/polls/${poll.id}`} className="card" style={{ display: 'block' }}>
          <h3 style={{ margin: '0 0 6px' }}>{poll.title}</h3>
          {poll.description && <p style={{ color: '#666', margin: '0 0 8px' }}>{poll.description}</p>}
          <small style={{ color: '#999' }}>
            por {user && user.id === poll.author_id ? <strong style={{ color: '#4147d5' }}>você</strong> : poll.author_name} · {poll.total_votes} voto(s)
          </small>
        </Link>
      ))}
    </div>
  )
}
