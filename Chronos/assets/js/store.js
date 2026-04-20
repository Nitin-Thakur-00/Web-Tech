const store = {
    user: null,
    theme: localStorage.getItem('theme') || 'dark',

    async init() {
        document.documentElement.setAttribute('data-theme', this.theme);

        if (document.querySelector('meta[name="csrf-token"]')) {
            await this.loadUser();
        }
    },

    async loadUser(force = false) {
        const cacheKey = 'chronos_user';
        const cacheTimestampKey = 'chronos_user_ts';
        const cacheTTL = 5 * 60 * 1000;

        if (!force) {
            const cached = sessionStorage.getItem(cacheKey);
            const cachedTimestamp = Number(sessionStorage.getItem(cacheTimestampKey) || 0);

            if (cached && Date.now() - cachedTimestamp < cacheTTL) {
                try {
                    this.user = JSON.parse(cached);
                    this.dispatchUser();
                    return this.user;
                } catch (error) {
                    sessionStorage.removeItem(cacheKey);
                    sessionStorage.removeItem(cacheTimestampKey);
                }
            }
        }

        const response = await API.getUserProfile();
        this.user = response.data;
        sessionStorage.setItem(cacheKey, JSON.stringify(this.user));
        sessionStorage.setItem(cacheTimestampKey, String(Date.now()));
        this.dispatchUser();
        return this.user;
    },

    invalidateUser() {
        sessionStorage.removeItem('chronos_user');
        sessionStorage.removeItem('chronos_user_ts');
    },

    dispatchUser() {
        if (!this.user) {
            return;
        }

        window.dispatchEvent(new CustomEvent('userLoaded', { detail: this.user }));
    },
};

store.init();
