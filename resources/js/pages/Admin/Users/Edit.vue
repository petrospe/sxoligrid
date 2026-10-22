<script setup>
import { computed } from 'vue'
import { Head, useForm, usePage } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
    user: { type: Object, required: true },
    roles: { type: Array, required: true },
})

const actor = computed(() => usePage().props.auth.user)
const form = useForm({
    name: props.user.name,
    email: props.user.email,
    password: '',
    role: props.user.role,
    phone: props.user.phone ?? '',
    can_view_finances: props.user.can_view_finances,
})

function submit() {
    form.put(`/admin/users/${props.user.id}`)
}
</script>

<template>
    <Head :title="user.name" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('admin.edit_user') }}</h1>
        <form class="card mt-6 grid max-w-2xl gap-3" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.name') }}
                <input v-model="form.name" class="field mt-1" required>
                <span v-if="form.errors.name" class="error">{{ form.errors.name }}</span>
            </label>
            <label class="text-sm">Email
                <input v-model="form.email" class="field mt-1" type="email" required>
                <span v-if="form.errors.email" class="error">{{ form.errors.email }}</span>
            </label>
            <label class="text-sm">{{ t('fields.new_password') }}
                <input v-model="form.password" class="field mt-1" type="password" :placeholder="t('common.password_unchanged')">
                <span v-if="form.errors.password" class="error">{{ form.errors.password }}</span>
            </label>
            <label class="text-sm">{{ t('fields.role') }}
                <select v-model="form.role" class="field mt-1">
                    <option v-for="role in roles" :key="role" :value="role">{{ t('roles.' + role) }}</option>
                </select>
                <span v-if="form.errors.role" class="error">{{ form.errors.role }}</span>
            </label>
            <label class="text-sm">{{ t('fields.phone') }}
                <input v-model="form.phone" class="field mt-1">
            </label>
            <label v-if="actor.role === 'owner' && form.role === 'admin'" class="flex items-center gap-2 text-sm">
                <input v-model="form.can_view_finances" type="checkbox">
                {{ t('admin.finance_access') }}
            </label>
            <p v-else-if="user.role === 'owner'" class="text-sm text-[#44546F]">{{ t('admin.owner_always_sees_finances') }}</p>
            <button class="btn w-fit" :disabled="form.processing">{{ t('actions.save') }}</button>
        </form>
    </AppLayout>
</template>
