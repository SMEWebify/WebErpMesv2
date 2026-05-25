import '@testing-library/jest-dom';

// Stub CSRF meta tag present in all Laravel layouts
document.head.innerHTML = '<meta name="csrf-token" content="test-csrf-token">';

// Stub localStorage (jsdom has it but make it clean between tests)
beforeEach(() => localStorage.clear());
