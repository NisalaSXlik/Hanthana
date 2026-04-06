export async function api(controller, action, data = null, config = {}) {
    const method = (config.method || 'POST').toUpperCase();
    const query = config.query || {};
    const qs = new URLSearchParams({
        controller,
        action,
        ...query,
    });

    const url = `index.php?${qs.toString()}`;

    const headers = {
        Accept: 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
    };

    const options = {
        method,
        headers,
    };

    if (data instanceof FormData) {
        options.body = data;
    } else if (data && method !== 'GET') {
        headers['Content-Type'] = 'application/json';
        options.body = JSON.stringify(data);
    }

    const response = await fetch(url, options);
    const text = await response.text();

    let payload;
    try {
        payload = text ? JSON.parse(text) : {};
    } catch (e) {
        payload = { status: 'error', errors: ['Invalid JSON response from server.'] };
    }

    if (!response.ok) {
        const fallback = payload?.errors || [`Request failed with status ${response.status}.`];
        throw new Error(Array.isArray(fallback) ? fallback.join(' ') : String(fallback));
    }

    return payload;
}