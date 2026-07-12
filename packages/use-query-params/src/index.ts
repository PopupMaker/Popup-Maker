export * from 'use-query-params';
export * from 'serialize-query-params';
export { ReactRouter6Adapter } from 'use-query-params/adapters/react-router-6';

// react-router must live in exactly one bundle: the adapter above reads
// react-router's NavigationContext, so consumers must use the same module
// instance for their <Router>. Import BrowserRouter from this package —
// importing it from react-router-dom directly bundles a second copy whose
// context the adapter can't see (null-context crash).
export { BrowserRouter } from 'react-router-dom';
