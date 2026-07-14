import { Route, Routes } from 'react-router-dom'
import Navbar from './components/Navbar.jsx'
import ProtectedRoute from './components/ProtectedRoute.jsx'
import Login from './pages/Login.jsx'
import Register from './pages/Register.jsx'
import PollsList from './pages/PollsList.jsx'
import PollDetail from './pages/PollDetail.jsx'
import CreatePoll from './pages/CreatePoll.jsx'
import EditPoll from './pages/EditPoll.jsx'

export default function App() {
  return (
    <>
      <Navbar />
      <Routes>
        <Route path="/" element={<PollsList />} />
        <Route path="/login" element={<Login />} />
        <Route path="/register" element={<Register />} />
        <Route path="/polls/:id" element={<PollDetail />} />
        <Route
          path="/polls/new"
          element={
            <ProtectedRoute>
              <CreatePoll />
            </ProtectedRoute>
          }
        />
        <Route path='/polls/:id/edit'
        element={
          <ProtectedRoute>
            <EditPoll />
          </ProtectedRoute>
        }
        />
      </Routes>
    </>
  )
}
