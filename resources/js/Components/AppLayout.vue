<script setup>
import { computed, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import NavIcon from '@/Components/NavIcon.vue'
import { useI18n } from '@/i18n'

const page = usePage()
const { t, locale } = useI18n()
const user = computed(() => page.props.auth?.user ?? null)
const success = computed(() => page.props.flash?.success ?? null)
const error = computed(() => page.props.flash?.error ?? null)

watch(locale, (value) => {
    document.documentElement.lang = value
}, { immediate: true })

const icons = {
    '/': 'calendar',
    '/login': 'login',
    '/admin': 'dashboard',
    '/admin/users': 'users',
    '/admin/courses': 'book',
    '/admin/schedules': 'calendar',
    '/admin/enrollments': 'user-plus',
    '/admin/packages': 'box',
    '/admin/subscriptions': 'refresh',
    '/admin/payments': 'card',
    '/admin/events': 'flag',
    '/admin/private-lessons': 'user',
    '/admin/flex-suggestions': 'bulb',
    '/owner/finances': 'chart',
    '/teacher': 'dashboard',
    '/teacher/students': 'users',
    '/teacher/events': 'flag',
    '/teacher/flex-suggestions': 'bulb',
    '/student': 'user',
    '/admin/settings': 'settings',
}

const items = computed(() => {
    const links = [{ href: '/', label: t('nav.schedule'), icon: icons['/'] }]

    if (!user.value) {
        links.push({ href: '/login', label: t('nav.login'), icon: icons['/login'] })

        return links
    }

    if (user.value.role === 'owner' || user.value.role === 'admin') {
        links.push(
            { href: '/admin', label: t('nav.dashboard') },
            { href: '/admin/users', label: t('nav.users') },
            { href: '/admin/courses', label: t('nav.courses') },
            { href: '/admin/schedules', label: t('nav.timetable') },
            { href: '/admin/enrollments', label: t('nav.enrollments') },
            { href: '/admin/packages', label: t('nav.packages') },
            { href: '/admin/subscriptions', label: t('nav.subscriptions') },
            { href: '/admin/payments', label: t('nav.payments') },
            { href: '/admin/events', label: t('nav.events') },
            { href: '/admin/private-lessons', label: t('nav.private_lessons') },
            { href: '/admin/flex-suggestions', label: t('nav.suggestions') },
        )
    }

    if (user.value.can_view_finances) {
        links.push({ href: '/owner/finances', label: t('nav.finances') })
    }

    if (user.value.role === 'teacher') {
        links.push(
            { href: '/teacher', label: t('nav.dashboard') },
            { href: '/teacher/students', label: t('nav.students') },
            { href: '/teacher/events', label: t('nav.events') },
            { href: '/teacher/flex-suggestions', label: t('nav.suggestions') },
        )
    }

    if (user.value.role === 'student') {
        links.push({ href: '/student', label: t('nav.profile') })
    }

    if (user.value.role === 'admin') {
        links.push({ href: '/admin/settings', label: t('nav.settings') })
    }

    return links.map((link) => ({ ...link, icon: icons[link.href] ?? 'dashboard' }))
})

const initials = computed(() => {
    const name = user.value?.name ?? ''

    return name
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part[0])
        .join('')
        .toLocaleUpperCase(locale.value)
})

function active(href) {
    const url = page.url.split('?')[0]

    if (['/', '/admin', '/teacher', '/student'].includes(href)) {
        return url === href
    }

    return url === href || url.startsWith(`${href}/`)
}

function logout() {
    router.post('/logout')
}

function setLocale(code) {
    if (locale.value === code) {
        return
    }

    router.post('/locale', { locale: code }, { preserveScroll: true })
}
</script>

<template>
    <div class="min-h-screen lg:grid lg:grid-cols-[240px_minmax(0,1fr)]">
        <aside class="flex flex-col border-b border-[#DFE1E6] bg-white lg:sticky lg:top-0 lg:h-screen lg:border-r lg:border-b-0">
            <Link href="/" class="flex items-center gap-2 px-3 py-3">
                <img :src="'/branding/mark.png'" alt="" class="h-7 w-7 shrink-0 object-contain">
                <span class="min-w-0">
                    <span class="block truncate text-sm font-semibold text-[#172B4D]">SxoliGrid</span>
                    <span class="block truncate text-xs text-[#626F86]">{{ t('tagline') }}</span>
                </span>
            </Link>
            <nav class="flex gap-1 overflow-x-auto px-2 pb-3 lg:block lg:min-h-0 lg:flex-1 lg:space-y-0.5 lg:overflow-y-auto" :aria-label="t('nav.schedule')">
                <Link
                    v-for="item in items"
                    :key="item.href"
                    :href="item.href"
                    class="flex shrink-0 items-center gap-2 rounded-[3px] px-2 py-1.5 text-sm whitespace-nowrap text-[#44546F] hover:bg-[#091E420F]"
                    :class="active(item.href) ? 'bg-[#E9F2FF] font-medium text-[#0C66E4] hover:bg-[#E9F2FF]' : ''"
                >
                    <NavIcon :name="item.icon" />
                    {{ item.label }}
                </Link>
            </nav>
            <div class="border-t border-[#DFE1E6] px-3 py-3">
                <div class="flex gap-1" role="group" :aria-label="locale">
                    <button
                        v-for="code in ['el', 'en']"
                        :key="code"
                        type="button"
                        class="rounded-[3px] px-1.5 py-0.5 text-[11px] font-semibold uppercase"
                        :class="locale === code ? 'bg-[#E9F2FF] text-[#0C66E4]' : 'text-[#626F86] hover:bg-[#091E420F]'"
                        @click="setLocale(code)"
                    >
                        {{ code }}
                    </button>
                </div>
                <div v-if="user" class="mt-3 flex items-center gap-2">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-[#0C66E4] text-xs font-semibold text-white">{{ initials }}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-medium text-[#172B4D]">{{ user.name }}</span>
                        <span class="block truncate text-xs text-[#626F86]">{{ t('roles.' + user.role) }}</span>
                    </span>
                    <button class="shrink-0 text-xs font-medium text-[#44546F] hover:text-[#0C66E4]" type="button" @click="logout">
                        {{ t('nav.logout') }}
                    </button>
                </div>
            </div>
        </aside>
        <main class="min-w-0 px-4 py-5 lg:px-6">
            <p v-if="success" class="mb-4 rounded-[3px] border border-[#7EE2B8] bg-[#DCFFF1] px-3 py-2 text-sm text-[#216E4E]">{{ success }}</p>
            <p v-if="error" class="mb-4 rounded-[3px] border border-[#FD9891] bg-[#FFECEB] px-3 py-2 text-sm text-[#AE2E24]">{{ error }}</p>
            <slot />
        </main>
    </div>
</template>
