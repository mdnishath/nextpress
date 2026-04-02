import { Component } from '@wordpress/element';
import { logger } from '../utils/logger';

interface Props {
  name: string;
  children: React.ReactNode;
  fallback?: React.ReactNode;
}

interface State {
  hasError: boolean;
  error: Error | null;
  errorInfo: string;
}

/**
 * Error boundary that catches React rendering errors.
 * Logs the failing component name + error for easy debugging.
 *
 * Usage: <ErrorBoundary name="ContentEditor"><ContentEditor .../></ErrorBoundary>
 */
export class ErrorBoundary extends Component<Props, State> {
  state: State = { hasError: false, error: null, errorInfo: '' };

  static getDerivedStateFromError(error: Error): Partial<State> {
    return { hasError: true, error };
  }

  componentDidCatch(error: Error, info: React.ErrorInfo) {
    const stack = info.componentStack || '';
    logger.error(
      'ErrorBoundary',
      `Crash in <${this.props.name}>: ${error.message}`,
      { error: error.toString(), componentStack: stack },
    );
    this.setState({ errorInfo: stack });
  }

  handleRetry = () => {
    this.setState({ hasError: false, error: null, errorInfo: '' });
  };

  render() {
    if (this.state.hasError) {
      if (this.props.fallback) return this.props.fallback;

      return (
        <div
          style={{
            padding: 16,
            margin: 8,
            background: '#fef2f2',
            border: '1px solid #fecaca',
            borderRadius: 8,
            fontSize: 13,
            color: '#991b1b',
          }}
        >
          <div style={{ fontWeight: 700, marginBottom: 6 }}>
            Error in {this.props.name}
          </div>
          <div style={{ color: '#b91c1c', fontFamily: 'monospace', fontSize: 12, marginBottom: 8 }}>
            {this.state.error?.message}
          </div>
          {this.state.errorInfo && (
            <details style={{ marginBottom: 8 }}>
              <summary style={{ cursor: 'pointer', fontSize: 12, color: '#6b7280' }}>
                Component Stack
              </summary>
              <pre
                style={{
                  fontSize: 11,
                  whiteSpace: 'pre-wrap',
                  color: '#6b7280',
                  marginTop: 4,
                  maxHeight: 200,
                  overflow: 'auto',
                }}
              >
                {this.state.errorInfo}
              </pre>
            </details>
          )}
          <button
            onClick={this.handleRetry}
            style={{
              padding: '6px 14px',
              background: '#ef4444',
              color: '#fff',
              border: 'none',
              borderRadius: 6,
              fontSize: 12,
              fontWeight: 600,
              cursor: 'pointer',
            }}
          >
            Retry
          </button>
        </div>
      );
    }

    return this.props.children;
  }
}
