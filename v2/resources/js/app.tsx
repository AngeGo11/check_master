import '../css/app.css';
import { createInertiaApp } from '@inertiajs/react';

createInertiaApp({
  title: (title) => title ? `${title} · Check Master` : 'Check Master',
  progress: { color: '#0f766e' },
});
