import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api/client';
import {
  AttentionBadge,
  CategoryBadge,
  PriorityBadge,
  StatusBadge,
  SummaryBadge,
} from '../components/Badges';
import ErrorAlert from '../components/ErrorAlert';
import Layout from '../components/Layout';

const emptyFilters = { status: '', category: '', priority: '' };

export default function IssueListPage() {
  const [issues, setIssues] = useState([]);
  const [filters, setFilters] = useState(emptyFilters);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  const load = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await api.listIssues(filters);
      setIssues(res.data || []);
    } catch (err) {
      setError(err);
    } finally {
      setLoading(false);
    }
  }, [filters]);

  useEffect(() => {
    load();
  }, [load]);

  return (
    <Layout
      action={
        <Link to="/create" className="btn btn-primary">
          + New issue
        </Link>
      }
    >
      <ErrorAlert error={error} />

      <div className="card filters">
        <div className="field">
          <label htmlFor="f-status">Status</label>
          <select
            id="f-status"
            value={filters.status}
            onChange={(e) => setFilters((f) => ({ ...f, status: e.target.value }))}
          >
            <option value="">All</option>
            <option value="open">Open</option>
            <option value="in_progress">In progress</option>
            <option value="resolved">Resolved</option>
          </select>
        </div>
        <div className="field">
          <label htmlFor="f-priority">Priority</label>
          <select
            id="f-priority"
            value={filters.priority}
            onChange={(e) => setFilters((f) => ({ ...f, priority: e.target.value }))}
          >
            <option value="">All</option>
            <option value="low">Low</option>
            <option value="medium">Medium</option>
            <option value="high">High</option>
          </select>
        </div>
        <div className="field">
          <label htmlFor="f-category">Category</label>
          <select
            id="f-category"
            value={filters.category}
            onChange={(e) => setFilters((f) => ({ ...f, category: e.target.value }))}
          >
            <option value="">All</option>
            <option value="billing">Billing</option>
            <option value="access">Access</option>
            <option value="incident">Incident</option>
            <option value="general">General</option>
          </select>
        </div>
      </div>

      {loading ? (
        <p className="loading">Loading issues…</p>
      ) : issues.length === 0 ? (
        <div className="card empty-state">No issues match these filters.</div>
      ) : (
        <div className="issue-list">
          {issues.map((issue) => (
            <Link key={issue.id} to={`/issues/${issue.id}`} className="card issue-row">
              <div className="issue-row-top">
                <h3>{issue.title}</h3>
                {issue.needs_attention && <AttentionBadge />}
              </div>
              <div className="issue-meta">
                <PriorityBadge priority={issue.priority} />
                <StatusBadge status={issue.status} />
                <CategoryBadge category={issue.category} />
                <SummaryBadge status={issue.summary_status} />
              </div>
            </Link>
          ))}
        </div>
      )}
    </Layout>
  );
}
