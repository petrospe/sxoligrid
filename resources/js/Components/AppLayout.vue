<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue'
import { Link, router, usePage } from '@inertiajs/vue3'
import NavIcon from '@/Components/NavIcon.vue'
import { useI18n } from '@/i18n'

const page = usePage()
const { t, locale } = useI18n()
const menuOpen = ref(false)
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

    const roles = user.value.roles ?? []
    const has = (role) => roles.includes(role)

    if (has('owner') || has('admin')) {
        links.push(
            { href: '/admin', label: t('nav.dashboard') },
            { href: '/admin/users', label: t('nav.users') },
            { href: '/admin/courses', label: t('nav.courses') },
            { href: '/admin/schedules', label: t('nav.timetable') },
            { href: '/admin/enrollments', label: t('nav.enrollments') },
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

    if (has('teacher')) {
        links.push(
            { href: '/teacher', label: has('owner') || has('admin') ? t('nav.teacher_dashboard') : t('nav.dashboard') },
            { href: '/teacher/students', label: t('nav.students') },
            { href: '/teacher/events', label: t('nav.events') },
            { href: '/teacher/flex-suggestions', label: t('nav.suggestions') },
        )
    }

    if (has('student')) {
        links.push({ href: '/student', label: t('nav.profile') })
    }

    if (has('admin')) {
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
    menuOpen.value = false
    router.post('/logout')
}

function closeMenu() {
    menuOpen.value = false
}

function closeMenuAfterVisit() {
    window.setTimeout(closeMenu, 0)
}

function onEscape(event) {
    if (event.key === 'Escape') {
        closeMenu()
    }
}

let desktopQuery

function onDesktopChange(event) {
    if (event.matches) {
        closeMenu()
    }
}

onMounted(() => {
    desktopQuery = window.matchMedia('(min-width: 1024px)')
    desktopQuery.addEventListener('change', onDesktopChange)
    window.addEventListener('keydown', onEscape)
})

onUnmounted(() => {
    desktopQuery?.removeEventListener('change', onDesktopChange)
    window.removeEventListener('keydown', onEscape)
    document.body.style.overflow = ''
})

watch(menuOpen, (open) => {
    document.body.style.overflow = open ? 'hidden' : ''
})

watch(() => page.url, closeMenu)

function setLocale(code) {
    if (locale.value === code) {
        return
    }

    router.post('/locale', { locale: code }, { preserveScroll: true })
}
</script>

<template>
    <div class="min-h-screen lg:grid lg:grid-cols-[240px_minmax(0,1fr)]">
        <button
            v-if="menuOpen"
            type="button"
            class="fixed inset-0 z-20 bg-[#091E424D] lg:hidden"
            :aria-label="t('nav.close_menu')"
            @click="closeMenu"
        />
        <aside class="sticky top-0 z-30 flex flex-col border-b border-[#DFE1E6] bg-white lg:h-screen lg:border-r lg:border-b-0">
            <div class="flex items-center">
                <Link href="/" class="flex min-w-0 flex-1 items-center gap-2 px-3 py-3">
                    <img :src="'/branding/mark.png'" alt="" class="h-7 w-7 shrink-0 object-contain">
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold text-[#172B4D]">SxoliGrid</span>
                        <span class="block truncate text-xs text-[#626F86]">{{ t('tagline') }}</span>
                    </span>
                </Link>
                <button
                    type="button"
                    class="mr-2 grid h-9 w-9 place-items-center rounded-[3px] border border-[#DFE1E6] text-[#172B4D] hover:bg-[#091E420F] lg:hidden"
                    :aria-expanded="menuOpen"
                    aria-controls="app-menu"
                    :aria-label="menuOpen ? t('nav.close_menu') : t('nav.menu')"
                    @click="menuOpen = !menuOpen"
                >
                    <span class="relative block h-3.5 w-4" aria-hidden="true">
                        <span class="absolute left-0 h-0.5 w-4 rounded-full bg-current transition duration-200" :class="menuOpen ? 'top-1.5 rotate-45' : 'top-0'" />
                        <span class="absolute top-1.5 left-0 h-0.5 w-4 rounded-full bg-current transition duration-200" :class="menuOpen ? 'opacity-0' : ''" />
                        <span class="absolute left-0 h-0.5 w-4 rounded-full bg-current transition duration-200" :class="menuOpen ? 'top-1.5 -rotate-45' : 'top-3'" />
                    </span>
                </button>
            </div>
            <div
                id="app-menu"
                class="lg:flex lg:min-h-0 lg:flex-1 lg:flex-col"
                :class="menuOpen ? 'absolute top-full right-0 z-40 w-72 max-w-[calc(100vw-2.5rem)] max-h-[calc(100dvh-3.5rem)] overflow-y-auto border border-t-0 border-[#DFE1E6] bg-white shadow-[0_16px_32px_rgba(9,30,66,0.16)] lg:static lg:w-auto lg:max-w-none lg:max-h-none lg:overflow-visible lg:border-0 lg:shadow-none' : 'hidden lg:flex'"
            >
            <nav class="flex flex-col gap-0.5 px-2 py-2 lg:min-h-0 lg:flex-1 lg:overflow-y-auto lg:py-0 lg:pb-3" :aria-label="t('nav.schedule')">
                <Link
                    v-for="item in items"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-2 rounded-[3px] px-2 py-2 text-sm text-[#44546F] hover:bg-[#091E420F] lg:py-1.5"
                    :class="active(item.href) ? 'bg-[#E9F2FF] font-medium text-[#0C66E4] hover:bg-[#E9F2FF]' : ''"
                    @click="closeMenuAfterVisit"
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
                        <span class="block truncate text-xs text-[#626F86]">{{ user.roles.map((role) => t('roles.' + role)).join(' · ') }}</span>
                    </span>
                    <button class="shrink-0 text-xs font-medium text-[#44546F] hover:text-[#0C66E4]" type="button" @click="logout">
                        {{ t('nav.logout') }}
                    </button>
                </div>
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
