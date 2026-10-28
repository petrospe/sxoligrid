<script setup>
import { computed } from 'vue'
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
    users: { type: Array, required: true },
    roles: { type: Array, required: true },
})

const actor = computed(() => usePage().props.auth.user)
const form = useForm({
    name: '',
    email: '',
    password: '',
    roles: ['student'],
    phone: '',
    can_view_finances: false,
})

function hasRole(role) {
    return form.roles.includes(role)
}

function submit() {
    form.post('/admin/users', {
        onSuccess: () => form.reset(),
    })
}

function remove(user) {
    if (window.confirm(t('confirm.delete_user', { name: user.name }))) {
        router.delete(`/admin/users/${user.id}`)
    }
}
</script>

<template>
    <Head :title="t('nav.users')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('nav.users') }}</h1>
        <form class="card mt-6 grid gap-3 md:grid-cols-2" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.name') }}
                <input v-model="form.name" class="field mt-1" required>
                <span v-if="form.errors.name" class="error">{{ form.errors.name }}</span>
            </label>
            <label class="text-sm">Email
                <input v-model="form.email" class="field mt-1" type="email" required>
                <span v-if="form.errors.email" class="error">{{ form.errors.email }}</span>
            </label>
            <label class="text-sm">{{ t('fields.password') }}
                <input v-model="form.password" class="field mt-1" type="password" required>
                <span v-if="form.errors.password" class="error">{{ form.errors.password }}</span>
            </label>
            <fieldset class="text-sm">
                <legend>{{ t('fields.roles') }}</legend>
                <p class="mt-1 text-[#626F86]">{{ t('admin.roles_hint') }}</p>
                <div class="mt-2 flex flex-wrap gap-x-4 gap-y-2">
                    <label v-for="role in roles" :key="role" class="flex items-center gap-2">
                        <input v-model="form.roles" :value="role" class="h-4 w-4" type="checkbox">
                        {{ t('roles.' + role) }}
                    </label>
                </div>
                <span v-if="form.errors.roles || form.errors['roles.0']" class="error">{{ form.errors.roles || form.errors['roles.0'] }}</span>
            </fieldset>
            <label class="text-sm">{{ t('fields.phone') }}
                <input v-model="form.phone" class="field mt-1">
            </label>
            <label v-if="actor.roles.includes('owner') && hasRole('admin') && !hasRole('owner')" class="flex items-center gap-2 text-sm md:mt-6">
                <input v-model="form.can_view_finances" type="checkbox">
                {{ t('admin.finance_access') }}
            </label>
            <div class="md:col-span-2">
                <button class="btn" :disabled="form.processing">{{ t('actions.create') }}</button>
            </div>
        </form>
        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[720px] text-left text-sm">
                <thead class="text-[#626F86]">
                    <tr>
                        <th class="py-2">{{ t('fields.name') }}</th>
                        <th>Email</th>
                        <th>{{ t('fields.phone') }}</th>
                        <th>{{ t('fields.role') }}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in props.users" :key="user.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3 font-medium">{{ user.name }}</td>
                        <td>{{ user.email }}</td>
                        <td>{{ user.phone || '—' }}</td>
                        <td>{{ user.roles.map((role) => t('roles.' + role)).join(' · ') }}</td>
                        <td class="space-x-2 text-right">
                            <Link class="btn-soft" :href="`/admin/users/${user.id}/edit`">{{ t('actions.edit') }}</Link>
                            <button v-if="user.id !== actor.id" class="btn-danger" type="button" @click="remove(user)">{{ t('actions.delete') }}</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
