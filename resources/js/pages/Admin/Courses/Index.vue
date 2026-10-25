<script setup>
import { ref } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

defineProps({
    courses: { type: Array, required: true },
    types: { type: Array, required: true },
})

const { t } = useI18n()

const form = useForm({
    title: '',
    description: '',
    type: 'group',
})

function submit() {
    form.post('/admin/courses', {
        onSuccess: () => form.reset('title', 'description'),
    })
}

const sectionForms = {}

function sectionForm(courseId) {
    if (!sectionForms[courseId]) {
        sectionForms[courseId] = useForm({ name: '' })
    }

    return sectionForms[courseId]
}

function addSection(course) {
    sectionForm(course.id).post(`/admin/courses/${course.id}/sections`, {
        preserveScroll: true,
        onSuccess: () => sectionForm(course.id).reset('name'),
    })
}

function remove(course) {
    if (window.confirm(t('confirm.delete_course', { name: course.title }))) {
        router.delete(`/admin/courses/${course.id}`)
    }
}

function removeSection(course, section) {
    if (window.confirm(t('confirm.delete_section', { name: section.name }))) {
        router.delete(`/admin/courses/${course.id}/sections/${section.id}`)
    }
}

const editingId = ref(null)
const editForm = useForm({ name: '' })

function startEdit(section) {
    editingId.value = section.id
    editForm.name = section.name
    editForm.clearErrors()
}

function cancelEdit() {
    editingId.value = null
    editForm.reset()
    editForm.clearErrors()
}

function saveSection(course, section) {
    editForm.put(`/admin/courses/${course.id}/sections/${section.id}`, {
        preserveScroll: true,
        onSuccess: () => cancelEdit(),
    })
}
</script>

<template>
    <Head :title="t('nav.courses')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('nav.courses') }}</h1>
        <p class="mt-2 max-w-3xl text-sm text-[#44546F]">{{ t('admin.courses_hint') }}</p>
        <form class="card mt-6 grid gap-3 md:grid-cols-2" @submit.prevent="submit">
            <label class="text-sm">{{ t('fields.title') }}
                <input v-model="form.title" class="field mt-1" required>
                <span v-if="form.errors.title" class="error">{{ form.errors.title }}</span>
            </label>
            <label class="text-sm">{{ t('fields.type') }}
                <select v-model="form.type" class="field mt-1">
                    <option v-for="type in types" :key="type" :value="type">{{ t('course_types.' + type) }}</option>
                </select>
            </label>
            <label class="text-sm md:col-span-2">{{ t('fields.description') }}
                <textarea v-model="form.description" class="field mt-1" rows="2"></textarea>
            </label>
            <div class="md:col-span-2">
                <button class="btn" :disabled="form.processing">{{ t('actions.create') }}</button>
            </div>
        </form>

        <div class="card mt-6 overflow-x-auto">
            <table class="w-full min-w-[640px] text-left text-sm">
                <thead class="text-[#626F86]"><tr><th class="py-2">{{ t('fields.title') }}</th><th>{{ t('fields.type') }}</th><th>{{ t('fields.section') }}</th><th></th></tr></thead>
                <tbody>
                    <tr v-for="course in courses" :key="course.id" class="border-t border-[#DFE1E6] align-top">
                        <td class="py-3 font-medium">{{ course.title }}</td>
                        <td>{{ t('course_types.' + course.type) }}</td>
                        <td class="py-3">
                            <p v-if="course.sections.length === 0" class="text-[#626F86]">{{ t('admin.no_sections') }}</p>
                            <article v-for="section in course.sections" :key="section.id" class="mb-4">
                                <div class="flex items-start justify-between gap-3">
                                    <form v-if="editingId === section.id" class="flex flex-wrap items-end gap-2" @submit.prevent="saveSection(course, section)">
                                        <label class="text-sm">{{ t('fields.section') }}
                                            <input v-model="editForm.name" class="field mt-1" required>
                                            <span v-if="editForm.errors.name" class="error">{{ editForm.errors.name }}</span>
                                        </label>
                                        <button class="btn" :disabled="editForm.processing">{{ t('actions.save') }}</button>
                                        <button class="btn-soft" type="button" @click="cancelEdit">{{ t('actions.cancel') }}</button>
                                    </form>
                                    <h2 v-else class="font-medium">{{ section.name }}</h2>
                                    <div v-if="editingId !== section.id" class="space-x-2">
                                        <button class="btn-soft" type="button" @click="startEdit(section)">{{ t('actions.edit') }}</button>
                                        <button class="btn-danger" type="button" @click="removeSection(course, section)">{{ t('actions.delete') }}</button>
                                    </div>
                                </div>
                                <ul v-if="section.schedules.length > 0" class="mt-1 space-y-1 text-[#44546F]">
                                    <li v-for="slot in section.schedules" :key="slot.id">
                                        {{ t('days.' + slot.day_of_week) }} {{ slot.start_time }}–{{ slot.end_time }} · {{ slot.room }}
                                    </li>
                                </ul>
                                <ul v-if="section.users.length > 0" class="mt-1 space-y-1">
                                    <li v-for="user in section.users" :key="user.id">
                                        {{ user.name }} · {{ t('roles.' + user.role) }}
                                        <span v-if="user.status"> · {{ t('enrollment_statuses.' + user.status) }}</span>
                                    </li>
                                </ul>
                                <p v-else class="mt-1 text-[#626F86]">{{ t('admin.no_section_users') }}</p>
                            </article>
                            <form class="mt-2 flex flex-wrap items-end gap-2" @submit.prevent="addSection(course)">
                                <label class="text-sm">{{ t('fields.section') }}
                                    <input v-model="sectionForm(course.id).name" class="field mt-1" required>
                                    <span v-if="sectionForm(course.id).errors.name" class="error">{{ sectionForm(course.id).errors.name }}</span>
                                </label>
                                <button class="btn" :disabled="sectionForm(course.id).processing">{{ t('admin.add_section') }}</button>
                            </form>
                        </td>
                        <td class="space-x-2 text-right">
                            <Link class="btn-soft" :href="`/admin/courses/${course.id}/edit`">{{ t('actions.edit') }}</Link>
                            <button class="btn-danger" type="button" @click="remove(course)">{{ t('actions.delete') }}</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </AppLayout>
</template>
