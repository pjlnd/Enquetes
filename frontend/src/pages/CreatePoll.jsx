import { useState } from 'react'
import { useNavigate } from 'react-router-dom'
import api from '../api/axios'

export default function CreatePoll() {
  const navigate = useNavigate()
  const [title, setTitle] = useState('')
  const [description, setDescription] = useState('')
  const [expiresAt, setExpiresAt] = useState('')
  const [options, setOptions] = useState(['', ''])
  const [error, setError] = useState('')
  const [loading, setLoading] = useState(false)

  function updateOption(index, value) {
    const next = [...options]
    next[index] = value
    setOptions(next)
  }

  function addOption() {
    if (options.length >= 8) return
    setOptions([...options, ''])
  }

  function removeOption(index) {
    if (options.length <= 2) return
    setOptions(options.filter((_, i) => i !== index))
  }

  async function handleSubmit(e) {
    e.preventDefault()
    setError('')

    const cleanOptions = options.map((o) => o.trim()).filter(Boolean)
    if (cleanOptions.length < 2) {
      setError('Adicione pelo menos 2 opções válidas.')
      return
    }

    setLoading(true)
    try {
      const res = await api.post('/polls', {
        title,
        description,
        expires_at: expiresAt || null,
        options: cleanOptions,
      })
      navigate(`/polls/${res.data.poll_id}`)
    } catch (err) {
      setError(err.response?.data?.error || 'Erro ao criar enquete.')
    } finally {
      setLoading(false)
    }
  }

  return (
    <div className="container">
      <div className="card">
        <h2>Nova enquete</h2>
        {error && <div className="error">{error}</div>}
        <form onSubmit={handleSubmit}>
          <label>Título</label>
          <input value={title} onChange={(e) => setTitle(e.target.value)} required />

          <label>Descrição (opcional)</label>
          <textarea rows={3} value={description} onChange={(e) => setDescription(e.target.value)} />

          <label>Data de expiração (opcional)</label>
          <input type="datetime-local" value={expiresAt} onChange={(e) => setExpiresAt(e.target.value)} />

          <label>Opções (mínimo 2, máximo 8)</label>
          {options.map((opt, i) => (
            <div key={i} style={{ display: 'flex', gap: 8 }}>
              <input
                value={opt}
                onChange={(e) => updateOption(i, e.target.value)}
                placeholder={`Opção ${i + 1}`}
              />
              {options.length > 2 && (
                <button type="button" className="btn btn-danger" onClick={() => removeOption(i)}>
                  x
                </button>
              )}
            </div>
          ))}

          {options.length < 8 && (
            <button type="button" className="btn btn-secondary" onClick={addOption} style={{ marginBottom: 16 }}>
              + Adicionar opção
            </button>
          )}

          <button className="btn" type="submit" disabled={loading} style={{ width: '100%' }}>
            {loading ? 'Criando...' : 'Criar enquete'}
          </button>
        </form>
      </div>
    </div>
  )
}
