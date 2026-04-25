<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { LayoutGrid, LayoutDashboard, Building2, Sun, Moon, Monitor, Users } from 'lucide-vue-next';
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
import { dashboard } from '@/routes';
import * as adminDashboard from '@/actions/App/Http/Controllers/Admin/AdminDashboardController';
import * as clinicRoutes from '@/actions/App/Http/Controllers/Admin/ClinicController';
import * as clinicUserRoutes from '@/actions/App/Http/Controllers/Clinic/UserController';
import { useAppearance } from '@/composables/useAppearance';
import type { NavItem, SharedPageProps } from '@/types';

const page = usePage<SharedPageProps>();
const { appearance, updateAppearance } = useAppearance();
const clinic = window.location.hostname.split('.')[0];

const context = computed(() => page.props.context ?? 'app');

const logoHref = computed(() =>
    context.value === 'admin' ? adminDashboard.index().url : dashboard(),
);

const navItems = computed<NavItem[]>(() => {
    if (context.value === 'admin') {
        return [
            { title: 'Panel de administración', href: adminDashboard.index().url, icon: LayoutDashboard },
            { title: 'Clínicas', href: clinicRoutes.index().url, icon: Building2 },
        ];
    }
    if (context.value === 'clinic') {
        return [
            { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
            { title: 'Usuarios', href: clinicUserRoutes.index(clinic).url, icon: Users },
        ];
    }

    return [
        { title: 'Dashboard', href: dashboard(), icon: LayoutGrid },
    ];
});
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
            <NavMain :items="navItems" />
        </SidebarContent>

        <SidebarFooter>
            <!-- Toggle de apariencia inline -->
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
