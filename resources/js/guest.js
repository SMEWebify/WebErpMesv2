import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

import { createRoot } from 'react-dom/client';
import React from 'react';

async function mountChatLive() {
    const elements = document.querySelectorAll('[data-react="chat-live"]');
    if (!elements.length) return;

    const { default: ChatLive } = await import('./components/ChatLive.jsx');

    elements.forEach((el) => {
        const parse = (k) => { try { return JSON.parse(el.dataset[k] ?? 'null'); } catch { return null; } };
        createRoot(el).render(
            React.createElement(ChatLive, {
                endpoints: parse('endpoints') ?? {},
                trans:     parse('trans')     ?? {},
            })
        );
    });
}

mountChatLive();
