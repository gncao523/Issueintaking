const PRIORITIES = ['low', 'medium', 'high'];
const CATEGORIES = ['billing', 'access', 'incident', 'general'];
const STATUSES = ['open', 'in_progress', 'resolved'];

export default function IssueForm({
  values,
  onChange,
  onSubmit,
  submitLabel,
  showStatus = false,
}) {
  const set = (field) => (e) => onChange({ ...values, [field]: e.target.value });

  return (
    <form
      className="form-stack"
      onSubmit={(e) => {
        e.preventDefault();
        onSubmit();
      }}
    >
      <div className="field">
        <label htmlFor="title">Title</label>
        <input id="title" value={values.title} onChange={set('title')} required />
      </div>
      <div className="field">
        <label htmlFor="description">Description</label>
        <textarea
          id="description"
          value={values.description}
          onChange={set('description')}
          required
        />
      </div>
      <div className="field">
        <label htmlFor="priority">Priority</label>
        <select id="priority" value={values.priority} onChange={set('priority')}>
          {PRIORITIES.map((p) => (
            <option key={p} value={p}>
              {p}
            </option>
          ))}
        </select>
      </div>
      <div className="field">
        <label htmlFor="category">Category</label>
        <select id="category" value={values.category} onChange={set('category')}>
          {CATEGORIES.map((c) => (
            <option key={c} value={c}>
              {c}
            </option>
          ))}
        </select>
      </div>
      {showStatus && (
        <div className="field">
          <label htmlFor="status">Status</label>
          <select id="status" value={values.status} onChange={set('status')}>
            {STATUSES.map((s) => (
              <option key={s} value={s}>
                {s.replace('_', ' ')}
              </option>
            ))}
          </select>
        </div>
      )}
      <div className="form-actions">
        <button type="submit" className="btn btn-primary">
          {submitLabel}
        </button>
      </div>
    </form>
  );
}
