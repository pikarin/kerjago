<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import { Button } from '@/components/ui/button';
import { dashboard, home, login, register } from '@/routes';
import { index as jobsIndex } from '@/routes/jobs';
import type { User } from '@/types';

const page = usePage();
const user = page.props.auth.user as User | null;
</script>

<template>
    <div class="flex min-h-screen flex-col bg-background">
        <header
            class="sticky top-0 z-10 border-b bg-background/95 backdrop-blur"
        >
            <div
                class="mx-auto flex h-14 w-full max-w-6xl items-center justify-between gap-4 px-4"
            >
                <div class="flex items-center gap-6">
                    <Link :href="home()" class="flex items-center gap-2">
                        <AppLogo />
                    </Link>
                    <nav class="flex items-center gap-4 text-sm">
                        <Link
                            :href="jobsIndex()"
                            class="text-muted-foreground transition-colors hover:text-foreground"
                        >
                            Find jobs
                        </Link>
                    </nav>
                </div>
                <div class="flex items-center gap-2">
                    <template v-if="user">
                        <Button as-child variant="outline" size="sm">
                            <Link :href="dashboard()">Dashboard</Link>
                        </Button>
                    </template>
                    <template v-else>
                        <Button as-child variant="ghost" size="sm">
                            <Link :href="login()">Log in</Link>
                        </Button>
                        <Button as-child size="sm">
                            <Link :href="register()">Sign up</Link>
                        </Button>
                    </template>
                </div>
            </div>
        </header>
        <main class="mx-auto w-full max-w-6xl flex-1 px-4 py-8">
            <slot />
        </main>
    </div>
</template>
