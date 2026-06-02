import { useState } from 'react';
import { Link, useNavigate } from 'react-router-dom';
import { api } from '../api/client';
import ErrorAlert from '../components/ErrorAlert';
import IssueForm from '../components/IssueForm';
import Layout from '../components/Layout';

const initial = {
  title: '',
  description: '',
  priority: 'medium',
  category: 'general',
};

export default function CreateIssuePage() {
  const navigate = useNavigate();
  const [values, setValues] = useState(initial);
  const [error, setError] = useState(null);
  const [submitting, setSubmitting] = useState(false);

  const submit = async () => {
    setSubmitting(true);
    setError(null);
    try {
      const res = await api.createIssue(values);
      navigate(`/issues/${res.data.id}`);
    } catch (err) {
      setError(err);
      setSubmitting(false);
    }
  };

  return (
    <Layout>
      <Link to="/" className="back-link">
        ← Back to issues
      </Link>
      <div className="create-page">
        <div className="card">
          <h2>Create issue</h2>
          <ErrorAlert error={error} />
          <IssueForm
            values={values}
            onChange={setValues}
            onSubmit={submit}
            submitLabel={submitting ? 'Creating…' : 'Create issue'}
          />
        </div>
      </div>
    </Layout>
  );
}
