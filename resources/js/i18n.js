import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

export const dayValues = [1, 2, 3, 4, 5, 6, 7]

export function useI18n() {
    const page = usePage()
    const locale = computed(() => page.props.locale === 'en' ? 'en' : 'el')

    function t(key, replace = {}) {
        const value = String(key).split('.').reduce((node, part) => {
            if (node === null || node === undefined) {
                return undefined
            }

            return node[part]
        }, page.props.ui)

        if (typeof value !== 'string') {
            return key
        }

        return Object.entries(replace).reduce(
            (text, [name, replacement]) => text.replaceAll(`:${name}`, String(replacement)),
            value,
        )
    }

    function money(amount) {
        return new Intl.NumberFormat(locale.value === 'el' ? 'el-GR' : 'en-GB', {
            style: 'currency',
            currency: 'EUR',
        }).format(Number(amount || 0))
    }

    return { t, locale, money }
}
