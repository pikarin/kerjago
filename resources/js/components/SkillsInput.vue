<script setup lang="ts">
import { X } from '@lucide/vue';
import { ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';

const skills = defineModel<string[]>({ required: true });

const draft = ref('');

function addSkill(): void {
    const skill = draft.value.trim().replace(/,+$/, '');

    if (skill !== '' && !skills.value.includes(skill)) {
        skills.value = [...skills.value, skill];
    }

    draft.value = '';
}

function removeSkill(skill: string): void {
    skills.value = skills.value.filter((existing) => existing !== skill);
}

function handleBackspace(): void {
    if (draft.value === '' && skills.value.length > 0) {
        skills.value = skills.value.slice(0, -1);
    }
}
</script>

<template>
    <div class="grid gap-2">
        <div v-if="skills.length > 0" class="flex flex-wrap gap-1.5">
            <Badge
                v-for="skill in skills"
                :key="skill"
                variant="secondary"
                class="gap-1 pr-1"
            >
                {{ skill }}
                <button
                    type="button"
                    class="rounded-full p-0.5 hover:bg-foreground/10"
                    :aria-label="`Remove ${skill}`"
                    @click="removeSkill(skill)"
                >
                    <X class="size-3" />
                </button>
            </Badge>
        </div>
        <Input
            v-model="draft"
            type="text"
            placeholder="Type a skill and press Enter"
            @keydown.enter.prevent="addSkill"
            @keydown.,.prevent="addSkill"
            @keydown.backspace="handleBackspace"
            @blur="addSkill"
        />
    </div>
</template>
