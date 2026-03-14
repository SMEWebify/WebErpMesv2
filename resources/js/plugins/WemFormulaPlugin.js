import { IFunctionService, BaseFunction, NumberValueObject, StringValueObject } from '@univerjs/engine-formula';

const CACHE_TTL_MS = 30000;

export class WemFormulaPlugin {
    constructor({ dataApiBase = '/api/spreadsheet/data' } = {}) {
        this.dataApiBase = dataApiBase;
        this.cache = new Map();
        this._prefetch();
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

    fetchSync(path) {
        const cached = this.getCache(path);
        if (cached !== null) return cached;

        const xhr = new XMLHttpRequest();
        xhr.open('GET', `${this.dataApiBase}${path}`, false);
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.withCredentials = true;
        xhr.send();

        if (xhr.status === 200) {
            return this.setCache(path, JSON.parse(xhr.responseText));
        }
        return null;
    }

    async _prefetch() {
        for (const path of ['/orders?status=open', '/production/kpis', '/orders/summary', '/orders/late', '/context']) {
            try {
                const res = await fetch(`${this.dataApiBase}${path}`, {
                    headers: { Accept: 'application/json' },
                    credentials: 'same-origin',
                });
                this.setCache(path, await res.json());
            } catch (e) { /* silencieux */ }
        }
    }

    register(univer) {
        const self = this;

        try {
            const functionService = univer.__getInjector().get(IFunctionService);

            const formulas = [
                {
                    name: 'WEM_STOCK',
                    calculate(ref) {
                        const data = self.fetchSync(`/stock/${encodeURIComponent(ref?.getValue?.()?.toString() || '')}`);
                        return NumberValueObject.create(Number(data?.quantity || 0));
                    },
                },
                {
                    name: 'WEM_COMMANDES_EN_COURS',
                    calculate() {
                        const data = self.fetchSync('/orders?status=open');
                        return NumberValueObject.create(Array.isArray(data) ? data.length : 0);
                    },
                },
                {
                    name: 'WEM_CA_MOIS',
                    calculate(month) {
                        const m = month?.getValue?.()?.toString() || '';
                        const data = self.fetchSync(`/revenue?month=${encodeURIComponent(m)}`);
                        return NumberValueObject.create(Number(data?.total_ht || 0));
                    },
                },
                {
                    name: 'WEM_DELAI_MOYEN',
                    calculate() {
                        const data = self.fetchSync('/production/kpis');
                        return NumberValueObject.create(Number(data?.avg_lead_time_days || 0));
                    },
                },
                {
                    name: 'WEM_COMMANDES_RETARD',
                    calculate() {
                        const data = self.fetchSync('/production/kpis');
                        return NumberValueObject.create(Number(data?.late_orders || 0));
                    },
                },
                {
                    name: 'WEM_LISTE_COMMANDES_RETARD',
                    calculate() {
                        const data = self.fetchSync('/orders/late');
                        const refs = Array.isArray(data)
                            ? data.map((order) => order?.reference).filter(Boolean)
                            : [];
                        return StringValueObject.create(refs.join(', '));
                    },
                },
                {
                    name: 'WEM_LISTE_COMMANDES',
                    calculate(status) {
                        const requestedStatus = status?.getValue?.()?.toString()?.trim() || 'all';
                        const allowed = ['all', 'open', 'closed'];
                        const normalizedStatus = allowed.includes(requestedStatus) ? requestedStatus : 'all';
                        const data = self.fetchSync(`/orders?status=${encodeURIComponent(normalizedStatus)}`);
                        const refs = Array.isArray(data)
                            ? data.map((order) => order?.reference).filter(Boolean)
                            : [];
                        return StringValueObject.create(refs.join(', '));
                    },
                },
                {
                    name: 'WEM_CA_COMMANDES_OUVERTES',
                    calculate() {
                        const data = self.fetchSync('/orders/summary');
                        return NumberValueObject.create(Number(data?.open_orders_total_ht || 0));
                    },
                },
                {
                    name: 'WEM_MOIS_COURANT',
                    calculate() {
                        const data = self.fetchSync('/context');
                        return StringValueObject.create((data?.current_month || '').toString());
                    },
                },
                {
                    name: 'WEM_AUJOURDHUI',
                    calculate() {
                        const data = self.fetchSync('/context');
                        return StringValueObject.create((data?.today || '').toString());
                    },
                },
                {
                    name: 'WEM_ANNEE_COURANTE',
                    calculate() {
                        const data = self.fetchSync('/context');
                        return NumberValueObject.create(Number(data?.current_year || 0));
                    },
                },
            ];

            formulas.forEach(({ name, calculate }) => {
                class WemFunction extends BaseFunction {
                    calculate(...args) {
                        return calculate(...args);
                    }
                }
                Object.defineProperty(WemFunction, 'name', { value: name });
                functionService.registerExecutors(new WemFunction(name));
            });

            console.log('[WEM] Formules enregistrées');

        } catch (e) {
            console.warn('[WEM] Impossible d\'enregistrer les formules :', e);
        }
    }
}
