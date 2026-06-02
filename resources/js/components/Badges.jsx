export function PriorityBadge({ priority }) {
  return <span className={`badge badge-priority-${priority}`}>{priority}</span>;
}

export function StatusBadge({ status }) {
  return <span className="badge badge-status">{status.replace('_', ' ')}</span>;
}

export function CategoryBadge({ category }) {
  return <span className="badge badge-category">{category}</span>;
}

export function AttentionBadge() {
  return <span className="badge badge-attention">Needs attention</span>;
}

export function SummaryBadge({ status }) {
  return <span className={`badge badge-summary-${status}`}>Summary: {status}</span>;
}
