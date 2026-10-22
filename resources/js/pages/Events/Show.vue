<script setup>
import { Head, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const { t } = useI18n()

const props = defineProps({
    event: { type: Object, required: true },
    people: { type: Array, required: true },
    roles: { type: Array, required: true },
    baseUrl: { type: String, required: true },
})

const form = useForm({
    user_id: '',
    role_in_event: 'performer',
})

function submit() {
    form.post(`${props.baseUrl}/participants`, { onSuccess: () => form.reset('user_id') })
}

function remove(participant) {
    router.delete(`${props.baseUrl}/participants/${participant.id}`)
}
</script>

<template>
    <Head :title="event.title" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ event.title }}</h1>
        <p class="mt-2 text-[#44546F]">{{ event.event_date }} · {{ event.location || t('common.no_location') }}</p>
        <p v-if="event.description" class="mt-3 max-w-2xl">{{ event.description }}</p>
        <form class="card mt-6 grid gap-3 md:grid-cols-[1fr_1fr_auto]" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.person') }}
                <select v-model="form.user_id" class="field mt-1" required>
                    <option value="" disabled>{{ t('common.choose') }}</option>
                    <option v-for="person in people" :key="person.id" :value="person.id">{{ person.name }} · {{ t('roles.' + person.role) }}</option>
                </select>
            </label>
            <label class="text-sm">{{ t('fields.event_role') }}
                <select v-model="form.role_in_event" class="field mt-1">
                    <option v-for="role in roles" :key="role" :value="role">{{ t('event_roles.' + role) }}</option>
                </select>
            </label>
            <button class="btn self-end" :disabled="form.processing">{{ t('actions.add') }}</button>
        </form>
        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[480px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.name') }}</th><th>{{ t('fields.school_role') }}</th><th>{{ t('fields.event_role_short') }}</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="participant in event.participants" :key="participant.id" class="border-t border-[#DFE1E6]">
                        <td class="py-3">{{ participant.user.name }}</td>
                        <td>{{ t('roles.' + participant.user.role) }}</td>
                        <td>{{ t('event_roles.' + participant.role_in_event) }}</td>
                        <td class="text-right"><button class="btn-danger" type="button" @click="remove(participant)">{{ t('actions.remove') }}</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
