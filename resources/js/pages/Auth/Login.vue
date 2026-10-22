<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const form = useForm({
    email: '',
    password: '',
    remember: false,
})

function submit() {
    form.post('/login')
}
</script>

<template>
    <Head :title="t('nav.login')" />
    <AppLayout>
        <form class="card mx-auto max-w-md" @submit.prevent="submit">
            <h1 class="text-xl font-semibold">{{ t('nav.login') }}</h1>
            <p class="mt-2 text-sm text-[#44546F]">{{ t('auth.hint') }}</p>
            <label class="mt-5 block text-sm font-medium" for="email">Email</label>
            <input id="email" v-model="form.email" class="field mt-1" type="email" autocomplete="username" required>
            <p v-if="form.errors.email" class="error">{{ form.errors.email }}</p>
            <label class="mt-4 block text-sm font-medium" for="password">{{ t('fields.password') }}</label>
            <input id="password" v-model="form.password" class="field mt-1" type="password" autocomplete="current-password" required>
            <p v-if="form.errors.password" class="error">{{ form.errors.password }}</p>
            <label class="mt-4 flex items-center gap-2 text-sm">
                <input v-model="form.remember" type="checkbox">
                {{ t('auth.remember') }}
            </label>
            <button class="btn mt-6 w-full" type="submit" :disabled="form.processing">{{ t('auth.submit') }}</button>
        </form>
    </AppLayout>
</template>
