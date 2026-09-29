import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import QuoteDetail from './QuoteDetail.vue';
import TicketDetail from './TicketDetail.vue';

const ticketMatch = window.location.pathname.match(/^\/tickets\/(\d+)\/?$/);
const quoteMatch = window.location.pathname.match(/^\/quotes\/(\d+)\/?$/);

function mountDesk(rootComponent, props) {
    createApp(rootComponent, props).mount('#app');
    requestAnimationFrame(() => {
        window.LegacyScroll?.bindBackToTop?.();
        window.LegacyScroll?.refreshBackToTop?.();
    });
}

if (ticketMatch) {
    mountDesk(TicketDetail, { ticketId: ticketMatch[1] });
} else if (quoteMatch) {
    mountDesk(QuoteDetail, { quoteId: quoteMatch[1] });
} else {
    mountDesk(App);
}
