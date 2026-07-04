<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { BriefcaseBusiness, UserSearch } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

const role = ref<'jobseeker' | 'employer'>('jobseeker');

const roles = [
    {
        value: 'jobseeker' as const,
        label: 'Jobseeker',
        description: 'Find and apply for jobs',
        icon: UserSearch,
    },
    {
        value: 'employer' as const,
        label: 'Employer',
        description: 'Post jobs and hire talent',
        icon: BriefcaseBusiness,
    },
];

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});
</script>

<template>
    <Head title="Register" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label>I want to</Label>
                <input type="hidden" name="role" :value="role" />
                <div class="grid grid-cols-2 gap-3">
                    <button
                        v-for="option in roles"
                        :key="option.value"
                        type="button"
                        class="flex flex-col items-start gap-1 rounded-lg border p-3 text-left transition-colors"
                        :class="
                            role === option.value
                                ? 'border-primary bg-primary/5 ring-1 ring-primary'
                                : 'border-input hover:bg-muted'
                        "
                        :aria-pressed="role === option.value"
                        @click="role = option.value"
                    >
                        <component
                            :is="option.icon"
                            class="size-5 text-primary"
                        />
                        <span class="text-sm font-medium">{{
                            option.label
                        }}</span>
                        <span class="text-xs text-muted-foreground">{{
                            option.description
                        }}</span>
                    </button>
                </div>
                <InputError :message="errors.role" />
            </div>

            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Full name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="email@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Password</Label>
                <PasswordInput
                    id="password"
                    required
                    :tabindex="3"
                    autocomplete="new-password"
                    name="password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    required
                    :tabindex="4"
                    autocomplete="new-password"
                    name="password_confirmation"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                />
                <InputError :message="errors.password_confirmation" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="5"
                :disabled="processing"
                data-test="register-user-button"
            >
                <Spinner v-if="processing" />
                Create account
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink
                :href="login()"
                class="underline underline-offset-4"
                :tabindex="6"
                >Log in</TextLink
            >
        </div>
    </Form>
</template>
