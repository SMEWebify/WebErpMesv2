// Importez bootstrap.js pour charger les dépendances de Laravel
import './bootstrap';

import { createApp } from 'vue';
import KanbanBoard from './components/KanbanBoard.vue';

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

const app = createApp({});

// Enregistrez le composant KanbanBoard globalement
app.component('kanban-board', KanbanBoard);

// Montez l'application sur l'élément avec l'ID #card
app.mount('#card');