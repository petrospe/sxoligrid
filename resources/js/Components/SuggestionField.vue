<script setup>
import { computed, ref } from 'vue'

const model = defineModel({ type: String, required: true })

const props = defineProps({
    options: { type: Array, required: true },
    label: { type: String, required: true },
})

const open = ref(false)

const matches = computed(() => {
    const query = model.value.trim().toLocaleLowerCase('el')
    const exact = props.options.some((option) => option.toLocaleLowerCase('el') === query)

    if (query === '' || exact) {
        return props.options
    }

    return props.options.filter((option) => option.toLocaleLowerCase('el').includes(query))
})

function choose(option) {
    model.value = option
    open.value = false
}
</script>

<template>
    <div class="relative mt-1">
        <input
            v-model="model"
            class="field pr-8"
            autocomplete="off"
            required
            @focus="open = true"
            @input="open = true"
            @blur="open = false"
        >
        <button
            class="absolute inset-y-0 right-0 flex w-8 items-center justify-center text-[#44546F]"
            type="button"
            tabindex="-1"
            :aria-label="label"
            :aria-expanded="open"
            @mousedown.prevent="open = !open"
        >
            <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                <path d="M4 6l4 4 4-4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
        </button>
        <ul v-if="open && matches.length" class="absolute z-20 mt-1 max-h-48 w-full overflow-auto rounded-[3px] border border-[#DFE1E6] bg-white py-1 shadow-sm">
            <li v-for="option in matches" :key="option">
                <button class="w-full px-2 py-1.5 text-left text-sm hover:bg-[#F7F8F9]" type="button" @mousedown.prevent="choose(option)">
                    {{ option }}
                </button>
            </li>
        </ul>
    </div>
</template>
