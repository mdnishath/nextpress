/**
 * NextPress Builder Logger.
 *
 * Logs to console in development, stores recent entries for debugging.
 * Access via window.__npbLogs in browser console.
 */

type LogLevel = 'debug' | 'info' | 'warn' | 'error';

interface LogEntry {
  level: LogLevel;
  source: string;
  message: string;
  data?: unknown;
  timestamp: string;
}

const MAX_ENTRIES = 200;
const entries: LogEntry[] = [];

function isDebug(): boolean {
  return (
    typeof window !== 'undefined' &&
    ((window as Record<string, unknown>).__npbDebug === true ||
      new URLSearchParams(window.location.search).has('npb_debug'))
  );
}

function log(level: LogLevel, source: string, message: string, data?: unknown): void {
  const entry: LogEntry = {
    level,
    source,
    message,
    data,
    timestamp: new Date().toISOString(),
  };

  entries.push(entry);
  if (entries.length > MAX_ENTRIES) entries.shift();

  // Always log errors and warnings
  if (level === 'error') {
    console.error(`[NPB:${source}]`, message, data ?? '');
  } else if (level === 'warn') {
    console.warn(`[NPB:${source}]`, message, data ?? '');
  } else if (isDebug()) {
    // Debug/info only in debug mode
    const fn = level === 'info' ? console.info : console.log;
    fn(`[NPB:${source}]`, message, data ?? '');
  }
}

export const logger = {
  debug: (source: string, message: string, data?: unknown) => log('debug', source, message, data),
  info: (source: string, message: string, data?: unknown) => log('info', source, message, data),
  warn: (source: string, message: string, data?: unknown) => log('warn', source, message, data),
  error: (source: string, message: string, data?: unknown) => log('error', source, message, data),
  getEntries: () => [...entries],
  getErrors: () => entries.filter((e) => e.level === 'error'),
  clear: () => { entries.length = 0; },
};

// Expose on window for console debugging
if (typeof window !== 'undefined') {
  (window as Record<string, unknown>).__npbLogs = logger;
}
