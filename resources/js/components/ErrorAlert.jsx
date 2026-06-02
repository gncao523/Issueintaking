export default function ErrorAlert({ error }) {
  if (!error) return null;

  const fieldErrors = error.errors
    ? Object.entries(error.errors).flatMap(([field, messages]) =>
        messages.map((msg) => `${field}: ${msg}`),
      )
    : [];

  return (
    <div className="alert-error" role="alert">
      <strong>{error.message || 'Something went wrong'}</strong>
      {fieldErrors.length > 0 && (
        <ul>
          {fieldErrors.map((line) => (
            <li key={line}>{line}</li>
          ))}
        </ul>
      )}
    </div>
  );
}
