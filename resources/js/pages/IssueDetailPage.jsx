import { useCallback, useEffect, useState } from 'react';
import { Link, useParams } from 'react-router-dom';
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

function formatTime(iso) {
  if (!iso) return '';
  return new Date(iso).toLocaleString();
}

export default function IssueDetailPage() {
  const { id } = useParams();
  const [issue, setIssue] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [status, setStatus] = useState('open');
  const [savingStatus, setSavingStatus] = useState(false);
  const [commentForm, setCommentForm] = useState({ author_name: '', body: '' });
  const [commentError, setCommentError] = useState(null);

  const load = useCallback(async () => {
    try {
      const res = await api.getIssue(id);
      setIssue(res.data);
      setStatus(res.data.status);
      setError(null);
    } catch (err) {
      setError(err);
    } finally {
      setLoading(false);
    }
  }, [id]);

  useEffect(() => {
    load();
  }, [load]);

  useEffect(() => {
    if (!issue || issue.summary_status !== 'pending') return undefined;

    const timer = setInterval(load, 3000);
    return () => clearInterval(timer);
  }, [issue?.summary_status, load]);

  const saveStatus = async () => {
    setSavingStatus(true);
    try {
      const res = await api.updateIssue(id, { status });
      setIssue(res.data);
    } catch (err) {
      setError(err);
    } finally {
      setSavingStatus(false);
    }
  };

  const submitComment = async (e) => {
    e.preventDefault();
    setCommentError(null);
    try {
      await api.addComment(id, commentForm);
      setCommentForm({ author_name: '', body: '' });
      await load();
    } catch (err) {
      setCommentError(err);
    }
  };

  if (loading) {
    return (
      <Layout>
        <p className="loading">Loading issue…</p>
      </Layout>
    );
  }

  if (!issue) {
    return (
      <Layout>
        <ErrorAlert error={error || { message: 'Issue not found' }} />
        <Link to="/" className="back-link">
          ← Back to list
        </Link>
      </Layout>
    );
  }

  const comments = issue.comments || [];

  return (
    <Layout>
      <Link to="/" className="back-link">
        ← Back to issues
      </Link>
      <ErrorAlert error={error} />

      <div className="detail-grid">
        <div className="card detail-main">
          <div className="issue-meta" style={{ marginBottom: '0.75rem' }}>
            <PriorityBadge priority={issue.priority} />
            <StatusBadge status={issue.status} />
            <CategoryBadge category={issue.category} />
            {issue.needs_attention && <AttentionBadge />}
            <SummaryBadge status={issue.summary_status} />
          </div>

          <h2>{issue.title}</h2>
          <p className="description">{issue.description}</p>

          <div className="summary-box">
            <h4>Smart summary</h4>
            {issue.summary_status === 'pending' && (
              <p className="summary-pending">Generating summary… (polling every 3s)</p>
            )}
            {issue.summary_status === 'failed' && (
              <p className="summary-pending">Summary generation failed. Check queue worker logs.</p>
            )}
            {issue.summary_status === 'ready' && issue.summary && (
              <>
                <p>{issue.summary}</p>
                {issue.suggested_next_action && (
                  <>
                    <h4 style={{ marginTop: '1rem' }}>Suggested next action</h4>
                    <p>{issue.suggested_next_action}</p>
                  </>
                )}
              </>
            )}
          </div>

          <section className="comments-section">
            <h3>Comments ({comments.length})</h3>
            {comments.length === 0 ? (
              <p style={{ color: 'var(--text-muted)' }}>No comments yet.</p>
            ) : (
              comments.map((c) => (
                <div key={c.id} className="comment">
                  <span className="comment-author">{c.author_name}</span>
                  <span className="comment-time">{formatTime(c.created_at)}</span>
                  <p className="comment-body">{c.body}</p>
                </div>
              ))
            )}

            <ErrorAlert error={commentError} />
            <form className="form-stack" onSubmit={submitComment} style={{ marginTop: '1rem' }}>
              <div className="field">
                <label htmlFor="author">Your name</label>
                <input
                  id="author"
                  value={commentForm.author_name}
                  onChange={(e) =>
                    setCommentForm((f) => ({ ...f, author_name: e.target.value }))
                  }
                  required
                />
              </div>
              <div className="field">
                <label htmlFor="body">Comment</label>
                <textarea
                  id="body"
                  value={commentForm.body}
                  onChange={(e) => setCommentForm((f) => ({ ...f, body: e.target.value }))}
                  required
                />
              </div>
              <button type="submit" className="btn btn-primary">
                Add comment
              </button>
            </form>
          </section>
        </div>

        <aside className="card detail-side">
          <h3 style={{ margin: '0 0 1rem', fontSize: '0.95rem' }}>Update status</h3>
          <div className="field">
            <label htmlFor="status">Status</label>
            <select id="status" value={status} onChange={(e) => setStatus(e.target.value)}>
              <option value="open">Open</option>
              <option value="in_progress">In progress</option>
              <option value="resolved">Resolved</option>
            </select>
          </div>
          <button
            type="button"
            className="btn btn-primary"
            style={{ width: '100%', marginTop: '0.5rem' }}
            onClick={saveStatus}
            disabled={savingStatus || status === issue.status}
          >
            {savingStatus ? 'Saving…' : 'Save status'}
          </button>
          <p style={{ fontSize: '0.75rem', color: 'var(--text-muted)', marginTop: '1rem' }}>
            Changing status alone does not re-run summary generation. Edit description via API to
            re-trigger.
          </p>
        </aside>
      </div>
    </Layout>
  );
}
