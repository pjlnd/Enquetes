import { useEffect, useState } from 'react'
import { useNavigate, useParams } from 'react-router-dom'
import api from '../api/axios'
import { useAuth } from '../context/AuthContext.jsx'

export default function EditPoll() {
  const { id } = useParams()
  const { user } = useAuth()
  const navigate = useNavigate()

  const [title, setTitle] = useState('')
  const [description, setDescription] = useState('')
  const [expiresAt, setExpiresAt] = useState('')
  const [loading, setLoading] = useState(true)
  const [saving, setSaving] = useState(false)
  const [error, setError] = useState('')

  useEffect(() => {
    api
      .get(`/polls/${id}`)
      .then((res) => {
        const poll = res.data.poll

        if (!user || user.id !== poll.author_id) {
          navigate(`/polls/${id}`)
          return
        }

        setTitle(poll.title)
        setDescription(poll.description || '')
        // datetime-local espera "YYYY-MM-DDTHH:mm", o banco devolve "YYYY-MM-DD HH:mm:ss"
        setExpiresAt(poll.expires_at ? poll.expires_at.slice(0, 16).replace(' ', 'T') : '')
      })
      .catch(() => setError('Não foi possível carregar a enquete.'))
      .finally(() => setLoading(false))
  }, [id, user, navigate])

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')
    setSaving(true)
    try {
      await api.put(`/polls/${id}`, {
        title,
        description,
        expires_at: expiresAt || null,
      })
      navigate(`/polls/${id}`)
    } catch (err) {
      setError(err.response?.data?.error || 'Erro ao salvar as alterações.')
    } finally {
      setSaving(false)
    }
  }

  if (loading) return <div className="container">Carregando...</div>

  return (
    <div className="container">
      <div className="card">
        <h2>Editar enquete</h2>
        {error && <div className="error">{error}</div>}
        <form onSubmit={handleSubmit}>
          <label>Título</label>
          <input value={title} onChange={(e) => setTitle(e.target.value)} required />

          <label>Descrição</label>
          <textarea rows={3} value={description} onChange={(e) => setDescription(e.target.value)} />

          <label>Data de expiração</label>
          <input type="datetime-local" value={expiresAt} onChange={(e) => setExpiresAt(e.target.value)} />

          <p style={{ fontSize: 13, color: '#999', marginTop: -6 }}>
            As opções de voto não podem ser alteradas depois de criadas, para não invalidar votos já registrados.
          </p>

          <div style={{ display: 'flex', gap: 8 }}>
            <button className="btn" type="submit" disabled={saving}>
              {saving ? 'Salvando...' : 'Salvar alterações'}
            </button>
            <button
              type="button"
              className="btn btn-secondary"
              onClick={() => navigate(`/polls/${id}`)}
            >
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}