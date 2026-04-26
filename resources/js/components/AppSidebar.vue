<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Building2, LayoutDashboard, LayoutGrid, Monitor, Moon, Sun, Users } from 'lucide-vue-next';
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
    SidebarSeparator,
} from '@/components/ui/sidebar';
import * as adminDashboard from '@/actions/App/Http/Controllers/Admin/AdminDashboardController';
import * as clinicRoutes from '@/actions/App/Http/Controllers/Admin/ClinicController';
import * as clinicUserRoutes from '@/actions/App/Http/Controllers/Clinic/UserController';
import { useAppearance } from '@/composables/useAppearance';
import { clinicSlug } from '@/composables/useClinicSlug';
import { dashboard } from '@/routes';
import type { NavItem, SharedPageProps } from '@/types';

const page = usePage<SharedPageProps>();
const { appearance, updateAppearance } = useAppearance();
const clinic = clinicSlug();

const context = computed(() => page.props.context ?? 'app');
const authUser = computed(() => page.props.auth?.user);
const permissions = computed((): string[] => page.props.auth?.permissions ?? []);
const isSuperAdmin = computed(() => authUser.value?.is_super_admin === true);

const logoHref = computed(() => (context.value === 'admin' ? adminDashboard.index().url : dashboard()));

function canSee(permission?: string): boolean {
    if (!permission) {
        return true;
    }

    if (isSuperAdmin.value) {
        return true;
    }

    if (context.value !== 'clinic') {
        return true;
    }

    return permissions.value.includes(permission);
}

type NavItemWithPermission = NavItem & { permission?: string };

const navItems = computed<NavItemWithPermission[]>(() => {
    if (context.value === 'admin') {
        return [
            { title: 'Panel de administración', href: adminDashboard.index().url, icon: LayoutDashboard },
            { title: 'Clínicas', href: clinicRoutes.index().url, icon: Building2 },
        ];
    }

    if (context.value === 'clinic') {
        return [
            { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
            { title: 'Usuarios', href: clinicUserRoutes.index(clinic).url, icon: Users, permission: 'users.view' },
        ];
    }

    return [{ title: 'Dashboard', href: dashboard(), icon: LayoutGrid }];
});

const visibleNavItems = computed(() => navItems.value.filter((item) => canSee(item.permission)));
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="logoHref">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="visibleNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <SidebarMenu>
                <SidebarMenuItem>
                    <div class="flex items-center justify-between gap-1 px-2 py-1">
                        <span class="text-xs text-muted-foreground group-data-[collapsible=icon]:hidden">Tema</span>
                        <div class="flex gap-0.5 group-data-[collapsible=icon]:flex-col">
                            <button
                                :class="['rounded p-1 transition-colors', appearance === 'light' ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-muted-foreground hover:text-foreground']"
                                title="Tema claro"
                                @click="updateAppearance('light')"
                            >
                                <Sun class="h-3.5 w-3.5" />
                            </button>
                            <button
                                :class="['rounded p-1 transition-colors', appearance === 'dark' ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-muted-foreground hover:text-foreground']"
                                title="Tema oscuro"
                                @click="updateAppearance('dark')"
                            >
                                <Moon class="h-3.5 w-3.5" />
                            </button>
                            <button
                                :class="['rounded p-1 transition-colors', appearance === 'system' ? 'bg-sidebar-accent text-sidebar-accent-foreground' : 'text-muted-foreground hover:text-foreground']"
                                title="Tema del sistema"
                                @click="updateAppearance('system')"
                            >
                                <Monitor class="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </SidebarMenuItem>
            </SidebarMenu>
            <SidebarSeparator />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
