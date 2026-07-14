import { useEffect, useRef, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import api from '../api/axios'
import { useAuth } from '../context/AuthContext.jsx'

export default function PollDetail() {
  const { id } = useParams()
  const { user } = useAuth()
  const navigate = useNavigate()

  const [poll, setPoll] = useState(null)
  const [options, setOptions] = useState([])
  const [userVote, setUserVote] = useState(null)
  const [error, setError] = useState('')
  const [voting, setVoting] = useState(false)
  const [showDeleteModal, setShowDeleteModal] = useState(false)

  // Carrega os dados iniciais da enquete
  useEffect(() => {
    api
      .get(`/polls/${id}`)
      .then((res) => {
        setPoll(res.data.poll)
        setOptions(res.data.options)
        setUserVote(res.data.user_vote)
      })
      .catch(() => setError('Enquete não encontrada.'))
  }, [id])

  // Polling: pergunta ao backend a cada 3s se os resultados mudaram.
  // (Trocamos o SSE por isso porque o servidor embutido do PHP no Windows
  // roda single-thread — sem pcntl_fork — e uma conexao SSE aberta por
  // minutos trava qualquer outra requisicao. Em producao, com Apache/Nginx
  // de verdade, o stream.php original volta a ser uma opcao viavel.)
  useEffect(() => {
    const interval = setInterval(() => {
      api
        .get(`/polls/${id}/results`)
        .then((res) => setOptions(res.data.options))
        .catch(() => { })
    }, 3000)

    return () => clearInterval(interval)
  }, [id])

  const totalVotes = options.reduce((sum, o) => sum + Number(o.votes), 0)

  async function handleVote(optionId) {
    if (!user) {
      navigate('/login')
      return
    }

    setVoting(true)
    setError('')
    try {
      await api.post(`/polls/${id}/vote`, { poll_option_id: optionId })
      setUserVote(optionId)
    } catch (err) {
      setError(err.response?.data?.error || 'Erro ao votar.')
    } finally {
      setVoting(false)
    }
  }

  async function handleDelete() {
    await api.delete(`/polls/${id}`)
    navigate('/')
  }

  if (error && !poll) return <div className="container error">{error}</div>
  if (!poll) return <div className="container">Carregando...</div>

  const isOwner = user && user.id === poll.author_id
  const isExpired = poll.expires_at && new Date(poll.expires_at) < new Date()

  return (
    <div className="container">
      <div className="card">
        <h2 style={{ marginTop: 0 }}>{poll.title}</h2>
        {poll.description && <p style={{ color: '#666' }}>{poll.description}</p>}
        <small style={{ color: '#999' }}>por {isOwner ? <strong style={{ color: '#4147d5' }}>você</strong> : poll.author_name}</small>
        {isExpired && <p className="error">Esta enquete já foi encerrada.</p>}

        {error && <div className="error">{error}</div>}

        <div style={{ marginTop: 20 }}>
          {options.map((opt) => {
            const pct = totalVotes > 0 ? Math.round((opt.votes / totalVotes) * 100) : 0
            const isSelected = userVote === opt.id

            return (
              <div key={opt.id} className="option-row">
                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                  <span>
                    {opt.option_text} {isSelected && '✅'}
                  </span>
                  <span style={{ color: '#666', fontSize: 14 }}>
                    {opt.votes} voto(s) · {pct}%
                  </span>
                </div>
                <div className="progress-bar">
                  <div className="progress-fill" style={{ width: `${pct}%` }} />
                </div>
                {userVote === null && !isExpired && (
                  <button
                    className="btn btn-secondary"
                    style={{ marginTop: 6 }}
                    disabled={voting}
                    onClick={() => handleVote(opt.id)}
                  >
                    Votar
                  </button>
                )}
              </div>
            )
          })}
        </div>

        <p style={{ color: '#999', fontSize: 13, marginTop: 16 }}>
          Total de votos: {totalVotes} · atualização em tempo real ativa
        </p>

        {isOwner && (
          <div style={{ marginTop: 16, display: 'flex', gap: 8 }}>
            <button className="btn btn-secondary" onClick={() => navigate(`/polls/${id}/edit`)}>
              Editar enquete
            </button>
            <button className="btn btn-danger" onClick={() => setShowDeleteModal(true)}>
              Excluir enquete
            </button>
          </div>
        )}
      </div>
      {showDeleteModal && (
        <div className="modal-overlay">
          <div className="modal-box">
            <h3 style={{ marginTop: 0 }}>Excluir enquete</h3>
            <p style={{ color: '#666' }}>
              Tem certeza que deseja excluir esta enquete? <br />Essa ação não pode ser desfeita.
            </p>
            <div style={{ display: 'flex', gap: 8, justifyContent: 'flex-end' }}>
              <button className="btn btn-secondary" onClick={() => setShowDeleteModal(false)}>
                Cancelar
              </button>
              <button className="btn btn-danger" onClick={handleDelete}>
                Excluir
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}
