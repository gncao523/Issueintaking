import { Link } from 'react-router-dom';

export default function Layout({ children, action }) {
  return (
    <div className="app-shell">
      <header className="app-header">
        <div>
          <h1>
            <Link to="/" style={{ color: 'inherit', textDecoration: 'none' }}>
              Issue Intake
            </Link>
          </h1>
          <p>Support tickets with smart async summaries</p>
        </div>
        {action}
      </header>
      {children}
    </div>
  );
}
