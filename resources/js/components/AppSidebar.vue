<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Briefcase,
    Building2,
    FileText,
    LayoutGrid,
    Search,
    UserRound,
    UserSearch,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as employerJobsIndex } from '@/routes/employer/jobs';
import { edit as employerProfileEdit } from '@/routes/employer/profile';
import { index as talentIndex } from '@/routes/employer/talent';
import { index as jobsIndex } from '@/routes/jobs';
import { index as myApplicationsIndex } from '@/routes/jobseeker/applications';
import { edit as jobseekerProfileEdit } from '@/routes/jobseeker/profile';
import type { NavItem, User } from '@/types';

const page = usePage();
const user = computed(() => page.props.auth.user as User);

const employerNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    { title: 'My jobs', href: employerJobsIndex(), icon: Briefcase },
    { title: 'Talent search', href: talentIndex(), icon: UserSearch },
    { title: 'Company profile', href: employerProfileEdit(), icon: Building2 },
];

const jobseekerNavItems: NavItem[] = [
    { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    { title: 'Find jobs', href: jobsIndex(), icon: Search },
    { title: 'My applications', href: myApplicationsIndex(), icon: FileText },
    { title: 'My profile', href: jobseekerProfileEdit(), icon: UserRound },
];

const mainNavItems = computed<NavItem[]>(() =>
    user.value?.role === 'employer' ? employerNavItems : jobseekerNavItems,
);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
