export function emptyPrices(methods) {
    return Object.fromEntries(methods.map((method) => [method, '']))
}

export function billingPayload(data) {
    const prices = {}

    for (const method of data.billing_methods) {
        prices[method] = data.prices[method]
    }

    return {
        title: data.title,
        description: data.description,
        type: data.type,
        billing_methods: data.billing_methods,
        prices,
    }
}
