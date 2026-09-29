import axios from 'axios';

/** List tables — keep in sync with API max per_page and legacy admin.html */
export const DESK_PAGE_SIZE = 100;

const client = axios.create({
    headers: { Accept: 'application/json' },
});

function listParams(params = {}) {
    return { per_page: DESK_PAGE_SIZE, ...params };
}

export async function fetchDashboard() {
    const { data } = await client.get('/api/v1/dashboard');
    return data;
}

export async function fetchTickets(params = {}) {
    const { data } = await client.get('/api/v1/tickets', { params: listParams(params) });
    return data.data;
}

export async function fetchAccounts() {
    const { data } = await client.get('/api/v1/accounts');
    return data.data;
}

export async function createTicket(payload) {
    const { data } = await client.post('/api/v1/tickets', payload);
    return data.data;
}

export async function fetchTicket(id) {
    const { data } = await client.get(`/api/v1/tickets/${id}`);
    return data.data;
}

export async function updateTicket(id, payload) {
    const { data } = await client.patch(`/api/v1/tickets/${id}`, payload);
    return data.data;
}

export async function fetchQuotes(params = {}) {
    const { data } = await client.get('/api/v1/quotes', { params: listParams(params) });
    return data.data;
}

export async function fetchQuote(id) {
    const { data } = await client.get(`/api/v1/quotes/${id}`);
    return data.data;
}

export async function fetchProvisioningOrders(params = {}) {
    const { data } = await client.get('/api/v1/provisioning-orders', { params: listParams(params) });
    return data.data;
}
