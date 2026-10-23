<script setup>
import { Head, useForm } from '@inertiajs/vue3'
import AppLayout from '@/Components/AppLayout.vue'
import { useI18n } from '@/i18n'

const props = defineProps({
    demo_installed: { type: Boolean, required: true },
    examples: { type: Array, required: true },
    landing: { type: Object, required: true },
})

const { t } = useI18n()
const restore = useForm({
    backup: null,
})
const demo = useForm({
    example: 'general',
})
const landingForm = useForm({
    el: { ...props.landing.el },
    en: { ...props.landing.en },
    logo: null,
})

function chooseLogo(event) {
    landingForm.logo = event.target.files?.[0] ?? null
}

function saveLanding() {
    landingForm
        .transform((data) => {
            if (data.logo !== null) {
                return data
            }

            const { logo, ...text } = data

            return text
        })
        .post('/admin/settings/landing', { forceFormData: true })
}

function chooseBackup(event) {
    const file = event.target.files?.[0] ?? null
    restore.backup = file
}

function submitRestore() {
    if (restore.backup === null) {
        return
    }

    if (!window.confirm(t('confirm.restore_database'))) {
        return
    }

    restore.post('/admin/settings/restore', { forceFormData: true })
}

function installDemo(key) {
    if (!window.confirm(t('confirm.install_demo'))) {
        return
    }

    demo.example = key
    demo.post('/admin/settings/demo')
}

function removeDemo(key) {
    if (!window.confirm(t('confirm.remove_demo'))) {
        return
    }

    demo.example = key
    demo.delete('/admin/settings/demo')
}
</script>

<template>
    <Head :title="t('nav.settings')" />
    <AppLayout>
        <h1 class="text-xl font-semibold">{{ t('settings.heading') }}</h1>
        <p class="mt-1 max-w-2xl text-[#44546F]">{{ t('settings.intro') }}</p>

        <form class="card mt-6" @submit.prevent="saveLanding">
            <h2 class="font-semibold">{{ t('settings.landing_section') }}</h2>
            <p class="mt-1 text-sm text-[#44546F]">{{ t('settings.landing_hint') }}</p>
            <img :src="landing.logo_url" alt="" class="mx-auto mt-4 max-h-56 w-auto">
            <label class="mt-4 block text-sm" for="logo">{{ t('settings.field_logo') }}
                <input id="logo" class="field mt-1" type="file" accept="image/png,image/jpeg,image/webp" @change="chooseLogo">
            </label>
            <p class="mt-1 text-sm text-[#626F86]">{{ t('settings.field_logo_hint') }}</p>
            <p v-if="landingForm.errors.logo" class="error">{{ landingForm.errors.logo }}</p>
            <div class="mt-5 grid gap-6 lg:grid-cols-2">
                <fieldset v-for="locale in ['el', 'en']" :key="locale" class="space-y-3">
                    <legend class="font-medium">{{ t(locale === 'el' ? 'settings.language_el' : 'settings.language_en') }}</legend>
                    <label class="block text-sm">{{ t('settings.field_name') }}
                        <input v-model="landingForm[locale].name" class="field mt-1" required maxlength="120">
                    </label>
                    <p v-if="landingForm.errors[`${locale}.name`]" class="error">{{ landingForm.errors[`${locale}.name`] }}</p>
                    <label class="block text-sm">{{ t('settings.field_title') }}
                        <input v-model="landingForm[locale].title" class="field mt-1" required maxlength="160">
                    </label>
                    <p v-if="landingForm.errors[`${locale}.title`]" class="error">{{ landingForm.errors[`${locale}.title`] }}</p>
                    <label class="block text-sm">{{ t('settings.field_subtitle') }}
                        <input v-model="landingForm[locale].subtitle" class="field mt-1" required maxlength="200">
                    </label>
                    <p v-if="landingForm.errors[`${locale}.subtitle`]" class="error">{{ landingForm.errors[`${locale}.subtitle`] }}</p>
                    <label class="block text-sm">{{ t('settings.field_description') }}
                        <textarea v-model="landingForm[locale].description" class="field mt-1 min-h-24" required maxlength="2000" />
                    </label>
                    <p v-if="landingForm.errors[`${locale}.description`]" class="error">{{ landingForm.errors[`${locale}.description`] }}</p>
                    <label class="block text-sm">{{ t('settings.field_details') }}
                        <textarea v-model="landingForm[locale].details" class="field mt-1 min-h-24" required maxlength="5000" />
                    </label>
                    <p v-if="landingForm.errors[`${locale}.details`]" class="error">{{ landingForm.errors[`${locale}.details`] }}</p>
                </fieldset>
            </div>
            <button class="btn mt-5" type="submit" :disabled="landingForm.processing">{{ t('settings.landing_save') }}</button>
        </form>

        <section class="card mt-4">
            <h2 class="font-semibold">{{ t('settings.backup_title') }}</h2>
            <p class="mt-1 text-sm text-[#44546F]">{{ t('settings.backup_hint') }}</p>
            <a class="btn mt-4" href="/admin/settings/backup">{{ t('settings.backup_action') }}</a>
        </section>

        <form class="card mt-4" @submit.prevent="submitRestore">
            <h2 class="font-semibold">{{ t('settings.restore_title') }}</h2>
            <p class="mt-1 text-sm text-[#44546F]">{{ t('settings.restore_hint') }}</p>
            <label class="mt-4 block text-sm" for="backup">{{ t('settings.restore_file') }}
                <input id="backup" class="field mt-1" type="file" accept=".sqlite,application/vnd.sqlite3,application/x-sqlite3" required @change="chooseBackup">
            </label>
            <p v-if="restore.errors.backup" class="error">{{ restore.errors.backup }}</p>
            <button class="btn mt-4" type="submit" :disabled="restore.processing || restore.backup === null">{{ t('settings.restore_action') }}</button>
        </form>

        <section class="card mt-4">
            <h2 class="font-semibold">{{ t('settings.demo_title') }}</h2>
            <p class="mt-1 text-sm text-[#44546F]">{{ t('settings.demo_hint') }}</p>
            <p v-if="demo.errors.demo" class="error">{{ demo.errors.demo }}</p>
            <p v-if="demo.errors.example" class="error">{{ demo.errors.example }}</p>
            <div class="mt-4 space-y-4">
                <article v-for="example in examples" :key="example.key" class="border-t border-[#DFE1E6] pt-4">
                    <h3 class="font-medium">{{ t('demo_examples.' + example.key) }}</h3>
                    <p class="mt-1 text-sm text-[#44546F]">{{ t('demo_examples.' + example.key + '_hint') }}</p>
                    <p v-if="example.installed" class="mt-2 text-sm text-[#626F86]">{{ t('settings.demo_present') }}</p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <button class="btn" type="button" :disabled="demo.processing || example.installed" @click="installDemo(example.key)">{{ t('settings.demo_add') }}</button>
                        <button class="btn-danger" type="button" :disabled="demo.processing || !example.installed" @click="removeDemo(example.key)">{{ t('settings.demo_remove') }}</button>
                    </div>
                </article>
            </div>
        </section>
    </AppLayout>
</template>
