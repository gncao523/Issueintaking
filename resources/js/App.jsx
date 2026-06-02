import { Route, Routes } from 'react-router-dom';
import CreateIssuePage from './pages/CreateIssuePage';
import IssueDetailPage from './pages/IssueDetailPage';
import IssueListPage from './pages/IssueListPage';

export default function App() {
  return (
    <Routes>
      <Route path="/" element={<IssueListPage />} />
      <Route path="/create" element={<CreateIssuePage />} />
      <Route path="/issues/:id" element={<IssueDetailPage />} />
    </Routes>
  );
}
