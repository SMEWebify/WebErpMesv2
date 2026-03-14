const CACHE_TTL_MS = 30000;

export class WemFormulaPlugin {
    constructor({ dataApiBase = '/api/spreadsheet/data' } = {}) {
        this.dataApiBase = dataApiBase;
        this.cache = new Map();
    }

    getCache(key) {
        const item = this.cache.get(key);
        if (!item) return null;

        if (Date.now() - item.at > CACHE_TTL_MS) {
            this.cache.delete(key);
            return null;
        }

        return item.value;
    }

    setCache(key, value) {
        this.cache.set(key, { value, at: Date.now() });
        return value;
    }

    async fetchJson(path) {
        const key = path;
        const cached = this.getCache(key);
        if (cached !== null) {
            return cached;
        }

        const response = await fetch(`${this.dataApiBase}${path}`, {
            headers: {
                Accept: 'application/json',
            },
            credentials: 'same-origin',
        });

        const data = await response.json();

        return this.setCache(key, data);
    }

    async stock(reference) {
        const data = await this.fetchJson(`/stock/${encodeURIComponent(reference)}`);
        return Number(data.quantity || 0);
    }

    async commandesEnCours() {
        const data = await this.fetchJson('/orders?status=open');
        return Array.isArray(data) ? data.length : 0;
    }

    async caMois(month) {
        const data = await this.fetchJson(`/revenue?month=${encodeURIComponent(month)}`);
        return Number(data.total_ht || 0);
    }

    async delaiMoyen() {
        const data = await this.fetchJson('/production/kpis');
        return Number(data.avg_lead_time_days || 0);
    }

    async commandesRetard() {
        const data = await this.fetchJson('/production/kpis');
        return Number(data.late_orders || 0);
    }

    register(univerApi) {
        if (!univerApi || typeof univerApi.registerFunction !== 'function') {
            return;
        }

        univerApi.registerFunction('WEM.STOCK', async (reference) => this.stock(reference));
        univerApi.registerFunction('WEM.COMMANDES_EN_COURS', async () => this.commandesEnCours());
        univerApi.registerFunction('WEM.CA_MOIS', async (month) => this.caMois(month));
        univerApi.registerFunction('WEM.DELAI_MOYEN', async () => this.delaiMoyen());
        univerApi.registerFunction('WEM.COMMANDES_RETARD', async () => this.commandesRetard());
    }
}
